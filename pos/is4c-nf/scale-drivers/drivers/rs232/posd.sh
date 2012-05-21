#!/bin/bash
# posd.sh

# Usage: posd.sh stop|start|status|clean|swap

if [ $# -lt 1 ] ; then
	echo "Usage: posd.sh stop|start|status|clean|swap"
	exit 0
fi

# --COMMENTS - - - - - - - - - - - - - - - - - - - -

# 13May12 EL Change POSD from posdriver-ssd to posdriver2-ssd to be
# different from the git upstream version, WFC.
# 14Apr12 EL Add swap for swapping in new versions of posdriver-ssd
# 13Apr12 EL As wrapper for service posdriver-ssd

# --CONSTANTS- - - - - - - - - - - - - - - - - - - -

POSD=posdriver2-ssd-debian

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
	sudo service $POSD $1
	exit
fi
