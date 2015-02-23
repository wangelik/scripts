# """""""""""""""""""""""""""""""""""""""""""""""""""""""""""
# VPNfetch 1.1 - SIMPLE VPN ACCOUNT FETCHING 
# 
#
# This script fetches current login data of most popular free
# vpn servers
# As the owners began to change the data on a regular basis, this
# could be quite useful.
# Just download config_files (e.g. openvpn) and pipe the output
# into the authenficitaion files.
#
# Usage:   $ python vpn.py [server_num] 
#          $ username
#          $ password
# 
# Example: $ python vpn.py 1 > auth.txt
# 
# Server:  
#          1. vpnbook.com -> http://www.vpnbook.com
#          2. freevpn.me  -> http://freevpn.me/accounts (first)
#          3. freevpn.me  -> http://freevpn.me/accounts (second)
#
# (c) 02.2015 by frederik wangelik [wangelik.net]


# import table
import urllib2 as url
import re as re
import sys as sys

# hardcoded data
server = ['http://www.vpnbook.com/freevpn','http://freevpn.me/accounts']
user   = ['vpnbook','freevpnme']
regexp = ['Password:.+<strong>(.*)</strong>','Password:</b>\s(.{9})</li>']

# fetching_core
def vpn_fetch(server,regexp):
        
        resp = url.urlopen(server)
        code = resp.read()
        data = re.findall(regexp, code)
        
        return data

# __main__
if len(sys.argv) == 2:
        
        if int(sys.argv[1]) == 1:
                login = user[0]+"\n"+vpn_fetch(server[0],regexp[0])[0]
        
        elif int(sys.argv[1]) == 2:
                login = user[1]+"\n"+vpn_fetch(server[1],regexp[1])[0]
        
        elif int(sys.argv[1]) == 3:
                login = user[1]+"\n"+vpn_fetch(server[1],regexp[1])[2]

        else: 
                login = 'Server not found'

        print login


else:
        print 'usage: python vpn.py [number]'

# EOF
