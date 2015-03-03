<?php
# """""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""
# OwnPass 1.0 - OWNCLOUD PASSWORD RESET FRAMEWORK
#
#
# This framework helps you to reset or change your password of any
# owncloud server. Indeed there are other ways to do so, but 
# for instance inserting a new mail into mysql/sqlite manually 
# can be quite tricky for some users and is much more work anyway.
# Also the other options you find in the www like console, 
# sha1-hashing etc. are either outdated or linked to a lot of
# pre_googling before it actually works.
# By using this script, you can update your data with just one 
# command.
# Behind the scenes, you'll find a short version of PHPass working
# in order to provide the right hashing algorithms. Furthermore it
# automatically reads the salt (enc_string) and updates
# the mysql data. 
# The next step will be to integrate sqlite support
# as well as proper error handling and sql data fetching from file.
#
# Usage: 	1.  Edit settings of the php file
# 		1.1 Either include your salt manually or leave it
#		    empty and run this script inside the owncloud 
# 		    root folder (e.g. /var/www/owncloud) [default]
#		    Make sure you have +r permission to config.php
#		    otherwise nothing will get fetched (use sudo).
#		1.2 Set $new_password to your updated password
#		1.3 Either change sql_auto to 0 which will just
#		    print out the new hash for you, or leave it
#		    and set all the connection details below. This
#		    simply updates the database directly. In case
#		    you don't know the data, a look at config.php
#		    might be helpful. ATTENTION: this works only
#		    for mysql at the time.
#		2.  run the script: $ php ownpass.php
# 
# Example: 	1.  Settings: $salt='', $sql_auto = 1;
# 		2.  run script inside owncloud folder (no salt)
#		2.1 $ sudo php /var/www/owncloud/ownpass.php
#		3.  $ [+] reading salt information
#		    $ [+] found salt: 2993a02b0f89bdf8d2567e5a83cd42
#		    $ [+] generating new hash
#		    $ [+] hash: $2a$08$vJJoOHRsr1nDhftuX...eHNX8Ne/2
#		    $ [+] updating password in database
#		    $ [+] password updated successfully
#
#
# (c) 02.2015 by frederik wangelik [wangelik.net]
  

# settings
$salt         = ''; # or change it to the right salt in config.php
$new_password = '';                  # enter new password
$config_file  = 'config/config.php'; # change not needed normally

$sql_auto = 0; 		             # sql updating enabled/disabled
$owncloud_user = '';		     # username of changed password
$mysql_database = '';		     # database owncloud uses
$mysql_server = 'localhost';	     # this should be right
$mysql_user = '';		     # mysql user owncloud uses
$mysql_password = '';		     # mysql password owncloud uses
$mysql_table = 'oc_users';	     # dont change unless needed


# credits to PHPass Framework, visit the github project
class PasswordHash {
	var $itoa64;
	var $iteration_count_log2;
	var $portable_hashes;
	var $random_state;

	function PasswordHash($iteration_count_log2, $portable_hashes)
	{
		$this->itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

		if ($iteration_count_log2 < 4 || $iteration_count_log2 > 31)
			$iteration_count_log2 = 8;
		$this->iteration_count_log2 = $iteration_count_log2;

		$this->portable_hashes = $portable_hashes;

		$this->random_state = microtime();
		if (function_exists('getmypid'))
			$this->random_state .= getmypid();
	}

	function get_random_bytes($count)
	{
		$output = '';
		if (@is_readable('/dev/urandom') &&
		    ($fh = @fopen('/dev/urandom', 'rb'))) {
			$output = fread($fh, $count);
			fclose($fh);
		}

		if (strlen($output) < $count) {
			$output = '';
			for ($i = 0; $i < $count; $i += 16) {
				$this->random_state =
				    md5(microtime() . $this->random_state);
				$output .=
				    pack('H*', md5($this->random_state));
			}
			$output = substr($output, 0, $count);
		}

		return $output;
	}

	function encode64($input, $count)
	{
		$output = '';
		$i = 0;
		do {
			$value = ord($input[$i++]);
			$output .= $this->itoa64[$value & 0x3f];
			if ($i < $count)
				$value |= ord($input[$i]) << 8;
			$output .= $this->itoa64[($value >> 6) & 0x3f];
			if ($i++ >= $count)
				break;
			if ($i < $count)
				$value |= ord($input[$i]) << 16;
			$output .= $this->itoa64[($value >> 12) & 0x3f];
			if ($i++ >= $count)
				break;
			$output .= $this->itoa64[($value >> 18) & 0x3f];
		} while ($i < $count);

		return $output;
	}

	function gensalt_private($input)
	{
		$output = '$P$';
		$output .= $this->itoa64[min($this->iteration_count_log2 +
			((PHP_VERSION >= '5') ? 5 : 3), 30)];
		$output .= $this->encode64($input, 6);

		return $output;
	}

	function crypt_private($password, $setting)
	{
		$output = '*0';
		if (substr($setting, 0, 2) == $output)
			$output = '*1';

		$id = substr($setting, 0, 3);
		# We use "$P$", phpBB3 uses "$H$" for the same thing
		if ($id != '$P$' && $id != '$H$')
			return $output;

		$count_log2 = strpos($this->itoa64, $setting[3]);
		if ($count_log2 < 7 || $count_log2 > 30)
			return $output;

		$count = 1 << $count_log2;

		$salt = substr($setting, 4, 8);
		if (strlen($salt) != 8)
			return $output;

		# We're kind of forced to use MD5 here since it's the only
		# cryptographic primitive available in all versions of PHP
		# currently in use.  To implement our own low-level crypto
		# in PHP would result in much worse performance and
		# consequently in lower iteration counts and hashes that are
		# quicker to crack (by non-PHP code).
		if (PHP_VERSION >= '5') {
			$hash = md5($salt . $password, TRUE);
			do {
				$hash = md5($hash . $password, TRUE);
			} while (--$count);
		} else {
			$hash = pack('H*', md5($salt . $password));
			do {
				$hash = pack('H*', md5($hash . $password));
			} while (--$count);
		}

		$output = substr($setting, 0, 12);
		$output .= $this->encode64($hash, 16);

		return $output;
	}

	function gensalt_extended($input)
	{
		$count_log2 = min($this->iteration_count_log2 + 8, 24);
		# This should be odd to not reveal weak DES keys, and the
		# maximum valid value is (2**24 - 1) which is odd anyway.
		$count = (1 << $count_log2) - 1;

		$output = '_';
		$output .= $this->itoa64[$count & 0x3f];
		$output .= $this->itoa64[($count >> 6) & 0x3f];
		$output .= $this->itoa64[($count >> 12) & 0x3f];
		$output .= $this->itoa64[($count >> 18) & 0x3f];

		$output .= $this->encode64($input, 3);

		return $output;
	}

	function gensalt_blowfish($input)
	{
		# This one needs to use a different order of characters and a
		# different encoding scheme from the one in encode64() above.
		# We care because the last character in our encoded string will
		# only represent 2 bits.  While two known implementations of
		# bcrypt will happily accept and correct a salt string which
		# has the 4 unused bits set to non-zero, we do not want to take
		# chances and we also do not want to waste an additional byte
		# of entropy.
		$itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$output = '$2a$';
		$output .= chr(ord('0') + $this->iteration_count_log2 / 10);
		$output .= chr(ord('0') + $this->iteration_count_log2 % 10);
		$output .= '$';

		$i = 0;
		do {
			$c1 = ord($input[$i++]);
			$output .= $itoa64[$c1 >> 2];
			$c1 = ($c1 & 0x03) << 4;
			if ($i >= 16) {
				$output .= $itoa64[$c1];
				break;
			}

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 4;
			$output .= $itoa64[$c1];
			$c1 = ($c2 & 0x0f) << 2;

			$c2 = ord($input[$i++]);
			$c1 |= $c2 >> 6;
			$output .= $itoa64[$c1];
			$output .= $itoa64[$c2 & 0x3f];
		} while (1);

		return $output;
	}

	function HashPassword($password)
	{
		$random = '';

		if (CRYPT_BLOWFISH == 1 && !$this->portable_hashes) {
			$random = $this->get_random_bytes(16);
			$hash =
			    crypt($password, $this->gensalt_blowfish($random));
			if (strlen($hash) == 60)
				return $hash;
		}

		if (CRYPT_EXT_DES == 1 && !$this->portable_hashes) {
			if (strlen($random) < 3)
				$random = $this->get_random_bytes(3);
			$hash =
			    crypt($password, $this->gensalt_extended($random));
			if (strlen($hash) == 20)
				return $hash;
		}

		if (strlen($random) < 6)
			$random = $this->get_random_bytes(6);
		$hash =
		    $this->crypt_private($password,
		    $this->gensalt_private($random));
		if (strlen($hash) == 34)
			return $hash;

		# Returning '*' on error is safe here, but would _not_ be safe
		# in a crypt(3)-like function used _both_ for generating new
		# hashes and for validating passwords against existing hashes.
		return '*';
	}
}


# fetching salt by using regular expressions
echo "\n[+] reading salt information\n";

if(strlen($salt)<1) {

	$content = file_get_contents('config/config.php');
	preg_match('/passwordsalt\'\s=>\s\'([a-z0-9]+)\'/',$content,$match);
	$salt = $match[1];
}

echo "[+] found salt: $salt\n";


# generating hash by using phpass framework
$hasher   = new PasswordHash(8,FALSE);
$new_hash = $hasher->HashPassword($new_password.$salt);

echo "[+] generating new hash\n";
echo "[+] hash: $new_hash\n";


# sql auto updating with OOP mysqli
if($sql_auto>0) {

	echo "[+] updating password in database\n";
	$connection = new mysqli($mysql_server,$mysql_user,$mysql_password,$mysql_database);
	
	if($connection->connect_error)
		die("[x] connection to mysqld failed\n");
	
	$query = "UPDATE $mysql_table SET password='$new_hash' WHERE uid='$owncloud_user'";

	if($connection->query($query) === TRUE)
		echo "[+] password updated successfully\n";
	else
		echo "[x] error while updating password: $connection->error\n";

}


?>
