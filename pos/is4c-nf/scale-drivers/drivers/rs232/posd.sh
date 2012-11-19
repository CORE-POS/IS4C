#!/bin/bash
# posd.sh

# Usage: posd.sh stop|start|restart|status|clean|swap

if [ $# -lt 1 ] ; then
	echo "Usage: posd.sh stop|start|restart|status|clean|swap"
	exit 0
fi

# --FUNCTIONALITY- - - - - - - - - - - - - - - - - -

#  + Run the functions of the scanner-scale driver/daemon wrapper.
#  + Swap in new versions of the scanner-scale driver/daemon wrapper.

# --COMMENTS - - - - - - - - - - - - - - - - - - - -

#  9Sep12 EL Check that the named wrapper exists.
#            Change to posdriver-ssd-debian
# 13May12 EL Change POSD from posdriver-ssd to posdriver2-ssd to be
#             different from the git upstream version, WFC.
# 14Apr12 EL Add swap for swapping in new versions of posdriver-ssd
# 13Apr12 EL As wrapper for service posdriver-ssd

# --CONSTANTS- - - - - - - - - - - - - - - - - - - -

#POSD=posdriver2-ssd
POSD=posdriver-ssd-debian
SSD=/var/www/IS4C/pos/is4c-nf/scale-drivers/drivers/rs232

# --VARIABLES- - - - - - - - - - - - - - - - - - - -

NOW=`date "+%H:%M:%S"`

# --MAIN - - - - - - - - - - - - - - - - - - - - - -

if [ "$1" = "swap" ] ; then
	if [ ! -f $SSD/$POSD ] ; then
		echo "$POSD does not exist at $SSD."
		exit 1
	fi
	cat $POSD | sudo tee /etc/init.d/$POSD > /dev/null
	RETVAL=$?
	if [ $? -eq 0 ] ; then
		echo "swap OK. now: $NOW"
		sudo chmod 755 /etc/init.d/$POSD
		ls -l /etc/init.d/$POSD
	else
		printf "swap failed because: >${RETVAL}<\n";
	fi
	exit
else
	if [ ! -f /etc/init.d/$POSD ] ; then
		echo "The wrapper: /etc/init.d/$POSD doesn't exist."
		if [ -f $SSD/$POSD ] ; then
			echo "It does exist at $SSD."
			echo "You could try using this utility with the swap option to install it."
		fi
		exit 1
	fi
	sudo service $POSD $1
	exit
fi
