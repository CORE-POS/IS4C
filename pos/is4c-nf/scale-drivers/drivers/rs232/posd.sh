#!/bin/bash
# posd.sh

# Usage: posd.sh stop|start|status|clean|swap

if [ $# -lt 1 ] ; then
	echo "Usage: posd.sh stop|start|status|clean|swap"
	exit 0
fi

# --COMMENTS - - - - - - - - - - - - - - - - - - - -

# 14Apr12 EL Add swap for swapping in new versions of posdriver-ssd
# 13Apr12 EL As wrapper for service posdriver-ssd

# --CONSTANTS- - - - - - - - - - - - - - - - - - - -

POSD=posdriver-ssd

# --VARIABLES- - - - - - - - - - - - - - - - - - - -

NOW=`date "+%H:%M:%S"`

# --MAIN - - - - - - - - - - - - - - - - - - - - - -

if [ "$1" = "swap" ] ; then
	cat $POSD | sudo tee /etc/init.d/$POSD > /dev/null
	RETVAL=$?
	if [ $? -eq 0 ] ; then
		echo "swap OK. now: $NOW"
		ls -l /etc/init.d/$POSD
	else
		printf "swap failed because: >${RETVAL}<\n";
	fi
	exit
else
	sudo service posdriver-ssd $1
	exit
fi
