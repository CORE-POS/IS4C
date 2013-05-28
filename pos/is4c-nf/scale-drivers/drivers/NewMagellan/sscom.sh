#!/bin/bash
# sscom.sh

# Usage: sscom.sh 

if [ "" -a $# -lt 1 ] ; then
	echo "Usage: sscom.sh "
	exit 0
fi

# --FUNCTIONALITY- - - - - - - - - - - - - - - - - -

# Menu of scanner-scale commands
#  with crude response-display and better some day.

# Reads responses from raw.txt, from:
# cat /dev/ttyS0 | tee raw.txt
# ?Which needs to be running in another terminal window?
# There may be better ways to do this.

# --COMMENTS - - - - - - - - - - - - - - - - - - - -

# 14Apr12 EL 

# --CONSTANTS- - - - - - - - - - - - - - - - - - - -

ECHO=/bin/echo
# Terminator
T="\r"
# Prefix
P=S
# Address=Scanner
N=${P}0
# Address=Scale
L=${P}1
# The port the ss is on.
PORT=/dev/ttyS0


# --VARIABLES- - - - - - - - - - - - - - - - - - - -

#NOW=`date "+%H:%M:%S"`

# --MAIN - - - - - - - - - - - - - - - - - - - - - -

RAW=`ps -ef | fgrep "tee raw.txt" | grep -v fgrep`
CAT=`ps -ef | fgrep "cat $PORT" | grep -v fgrep`
#if [ "1" -a "$RAW" = "" ] ; then
if [ "$CAT" = "" -o "$RAW" = "" ] ; then
	printf "raw.txt is not being gathered.\n"
	printf " In another window: cat $PORT | tee raw.txt\n"
	exit 0
else
	echo "CAT: >${CAT}<"
	echo "RAW: >${RAW}<"
	ps -ef | fgrep "tee raw.txt"
	echo "catting $PORT is OK"
	ls -l raw.txt
fi

printf "\nBe sure that in another window: cat $PORT | tee raw.txt\n\n"

while [ "1" ] ; do

# Display a list of Commands
echo ""
echo "1. GoodBeep"
echo "2. Scanner Status"
echo "3. Scale Status"
echo "4. Scale Monitor"
echo "5. Scale Weight Request"
echo "q. Quit"
echo ""
ANS=
printf " > "; read ANS

# Perform the Command and possibly display the results

case "$ANS" in
	1)
		# goodBeep. Works.
		$ECHO -e "${P}334${T}" > $PORT
		#$ECHO -e "${P}334${T}"
		;;
	2)
# Scanner Status.
		$ECHO -e "${P}03${T}" > $PORT
		$ECHO "Response:"
		sleep 1
		tail -1 raw.txt
		;;
	3)
# Scale Status.
		$ECHO -e "${L}3${T}" > $PORT
		$ECHO "Scale Status Response:"
		sleep 1
		tail -1 raw.txt
		;;
	4)
# Scale Monitor.
		CODE=${L}4
		$ECHO -e "${L}4${T}" > $PORT
		$ECHO "Scale Monitor Response ^${CODE}:"
		sleep 1
		tail -1 raw.txt
		# Check until you get one with the right heading.
		#RESP=`tail -1 raw.txt | grep "^$CODE"`
		;;
	5)
# Scale Weight Request.
		CODE=${L}1
		$ECHO -e "${L}1${T}" > $PORT
		$ECHO "Scale Weight Response ^${CODE}...:"
		sleep 1
		tail -1 raw.txt
		# Check until you get one with the right heading.
		#RESP=`tail -1 raw.txt | grep "^$CODE"`
		;;
	q)
		break
		;;
	*)
		$ECHO "Unknown command"
esac

done

printf "Goodbye.\n"
exit
