#-----------------------------------------------------------------------------------
#! /usr/bin/env python
# --------------------------------------------------------
# SQUARE ROOT CALCULATOR USING HERON TECHNIQUE
#
# description:
#	simple s_root approximation function _square_root(param1)
#	usage: heron.py => enter positive number
#
# options: 
# 	return_param_1 = number of needed steps
#	return_param_2 = final result of square root
#
# (c) fred
# --------------------------------------------------------
# settings [don't change unless you are babo]
steps = 1000 # number of max loop_steps
debug = 5    # max number of lines for debugging output

# app_description
print   '[-----------------------------]\n'+ \
	'|-- SQUARE ROOT  CALCULATOR --|\n'+ \
	'|-----------------------------|\n'+ \
	'|--  using heron technique  --|\n'+ \
	'[-----------------------------]'

# normal input
num = raw_input('[*] Please insert number: ')

# func_for_calculation using black magic c0de :)
def _square_root(num):

	# lazy error_checking for failheads
	try: num = float(num)
	except ValueError: 
		print '[!] Value has to be a number'
		return -1
	if(num<=0): 
		print '[!] Value has to be positive'; 
		return -2

	print '[-] Calculating square root of: '+str(num)

	# calculating entrypoint by using awesome tayl0r-series of binom. series at 1
	# x0 = ([num+1]/2)
	_x_ = (num+1)/2
	print '[-] Entrypoint at: '+str(_x_)
	if(int(debug)>0): print '[-] Debug-information:'

	# black magic :>
	for i in range(0, steps):
		_x_  = (_x_+(num/_x_))/2 # temp value of x^(1/2)
		_ac_ = (_x_*_x_)/num     # temp accuracy: (x^(1/2)^2)/orig_number
		if i<debug:              # print temp value, temp accuracy and square
			print '['+str(i+1)+'] value: '+str(_x_) \
			+'  \tx^2: '+str(_x_*_x_)+'\taccuracy: '+str(_ac_)
		# check by type_cast to str char_by_char for len(_x_) cause no
		# external routines were allowed such as substr(), find() etc..
		# accuracy max 10^-10 due to length of [float]
		if(str(_x_*_x_)==str(num)): break

	print '[.] .......................'
	if(i==steps-1):
		print '[!] '+str(steps-1)+' steps are reached: cant guarantee full accuracy'

	res1 = str(i+1)		# final number of steps
	res2 = str(_x_)		# final result
	print '['+res1+'] Found square root with accuracy of 10^-10: ['+res2+']' # output
	return(res1,res2)	# return the shit to _main_ if needed (addr of call func)

# simple call root_function, usage of return[0],return[1] possible as well
_square_root(num)

# german debugging analysis of both methods as comment
#-----------------------------------------------------------------
# Wie unten dargestellt gibt es einige Unterschiede was die Rechenschritte der einzelnen
# Methoden anbelangt. Das Heron-Verfahren geht hierbei wesentlich effizienter vor, besonders
# was Wurzeln aus grossen Zahlen oder irrationale Wurzeln angeht, dies beruht auf der
# Iterationsvorschrift der rekursiven Folge. Die Intervallverschachtelung
# hingegen erarbeitet sich systematisch neue Teilintervalle und muss jedes einzeln pruefen, was 
# besonders bei grossen Zahlen wesentlich mehr Rechenschritte benoetigt.
# Ersichtlich nach der folgenden Analyse fuer a=2 oder a=44.234527 Es werden je
# zwischen 31 und 36 mehr Rechenschritte des Intervallverfahrens benoetigt.
# Lediglich unter einer Bedingung ist letzteres Verfahren schneller und effizienter:
# sollte die Wurzel*2^k fuer ein k e N die Potenz ergeben, stoesst der Intervallteiler durch
# die Halbierungen sehr schnell darauf z.B. a=16=4^2=4*2*2=4*2^2 oder etwa a=256
#
#----------------------[CHECK FOR a = 16]-------------------------
# $ python heron.py
# [*] Please insert number: 16
# [-] Calculating square root of: 16.0
# [-] Entrypoint at: 8.5
# [-] Debug-information:
# [1] value: 5.19117647059  	x^2: 26.9483131488	accuracy: 1.6842695718
# [2] value: 4.13666472255  	x^2: 17.1119950268	accuracy: 1.06949968917
# [3] value: 4.0022575248  	x^2: 16.0180652948	accuracy: 1.00112908093
# [4] value: 4.00000063669  	x^2: 16.0000050935	accuracy: 1.00000031835
# [5] value: 4.0  		x^2: 16.0		accuracy: 1.0
# [.] .......................
# [5] Found square root with accuracy of 10^-10: [4.0]
#
# $ python intervall.py
# [*] Please insert number: 16
# [-] Calculating square root of: 16.0
# [-] Lower limit: 0 Upper limit: 16.0
# [-] Debug-information:
# [1] value: 8.0	Intervall: [0,16.0]
# [2] value: 4.0	Intervall: [0,8.0]
# [.] .......................
# [2] Found square root with accuracy of 10^-10: [4.0]
#
#----------------------[CHECK FOR a = 2]--------------------------
# $ python heron.py
# [*] Please insert number: 2
# [-] Calculating square root of: 2.0
# [-] Entrypoint at: 1.5
# [-] Debug-information:
# [1] value: 1.41666666667  	x^2: 2.00694444444	accuracy: 1.00347222222
# [2] value: 1.41421568627  	x^2: 2.0000060073	accuracy: 1.00000300365
# [3] value: 1.41421356237  	x^2: 2.0		accuracy: 1.0
# [.] .......................
# [3] Found square root with accuracy of 10^-10: [1.41421356237]
#
# $ python intervall.py
# [*] Please insert number: 2
# [-] Calculating square root of: 2.0
# [-] Lower limit: 0 Upper limit: 2.0
# [-] Debug-information:
# [1] value: 1.0	Intervall: [0,2.0]
# [2] value: 1.5	Intervall: [1.0,2.0]
# [3] value: 1.25	Intervall: [1.0,1.5]
# [4] value: 1.375	Intervall: [1.25,1.5]
# [5] value: 1.4375	Intervall: [1.375,1.5]
# [.] .......................
# [39] Found square root with accuracy of 10^-10: [1.41421356237]
#
#----------------------[CHECK FOR a = 44.234527]--------------------------
# $ python heron.py
# [*] Please insert number: 44.234527
# [-] Calculating square root of: 44.234527
# [-] Entrypoint at: 22.6172635
# [-] Debug-information:
# [1] value: 12.2865247431  	x^2: 150.958690263	accuracy: 3.41268914807
# [2] value: 7.94338599987  	x^2: 63.097381143	accuracy: 1.42642829984
# [3] value: 6.75605517249  	x^2: 45.6442814937	accuracy: 1.03187000267
# [4] value: 6.65172250663  	x^2: 44.2454123052	accuracy: 1.00024608164
# [5] value: 6.65090427457  	x^2: 44.2345276695	accuracy: 1.00000001514
# [.] .......................
# [6] Found square root with accuracy of 10^-10: [6.65090422424]
#
# $ python intervall.py
# [*] Please insert number: 44.234527
# [-] Calculating square root of: 44.234527
# [-] Lower limit: 0 Upper limit: 44.234527
# [-] Debug-information:
# [1] value: 22.1172635		Intervall: [0,44.234527]
# [2] value: 11.05863175	Intervall: [0,22.1172635]
# [3] value: 5.529315875	Intervall: [0,11.05863175]
# [4] value: 8.2939738125	Intervall: [5.529315875,11.05863175]
# [5] value: 6.91164484375	Intervall: [5.529315875,8.2939738125]
# [.] .......................
# [37] Found square root with accuracy of 10^-10: [6.65090422424]
#---------------------------------------------------------------------

# EOF 12.2013
