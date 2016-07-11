#!/bin/bash
# posd.sh

# Usage: posd.sh stop|start|restart|status|clean|swap|remove

if [ $# -lt 1 -o "$1" = "-h" -o "$1" = "--help" ] ; then
	echo "Usage: posd.sh stop|start|restart|status|clean|install|swap|remove"
	exit 0
fi

# --FUNCTIONALITY- - - - - - - - - - - - - - - - - -

#  + Run the functions of the scanner-scale driver/daemon wrapper.
#  + Swap in new versions of the scanner-scale driver/daemon wrapper.
#  + Remove the scanner-scale driver/daemon wrapper.

# --COMMENTS - - - - - - - - - - - - - - - - - - - -

#  2Nov12 EL Add -h and --help handling.
#            Add install option.
#            Add update-rc.d to remove.
# 27Oct12 EL Add remove option.
# 26Oct12 EL Adapted for sph / pos.exe from ../rs232/posd.sh

# Re Original:
#  9Sep12 EL Check that the named wrapper exists.
#            Change to posdriver-ssd-debian
# 14Apr12 EL Add swap for swapping in new versions of posdriver-ssd
# 13Apr12 EL As wrapper for service posdriver-ssd

# --FUNCTIONS- - - - - - - - - - - - - - - - - - - -

# See if the script is set to start at runlevel 3.
#  Is assumed to imply whether the script is set for any runlevel activity.
function checkrc() {
		INRC=`ls /etc/rc3.d/S* | grep $POSD`
}

# --CONSTANTS- - - - - - - - - - - - - - - - - - - -

POSD=posdriver-sph-debian
SPH=/var/www/IS4C/pos/is4c-nf/scale-drivers/drivers/NewMagellan
DHOME=/var/run/$POSD

# --VARIABLES- - - - - - - - - - - - - - - - - - - -

NOW=`date "+%H:%M:%S"`

# --MAIN - - - - - - - - - - - - - - - - - - - - - -

if [ "$1" = "swap" ] ; then
	if [ ! -f $SPH/$POSD ] ; then
		echo "$POSD does not exist at $SPH."
		exit 1
	fi
	if [ -f ${DHOME}/sphp.pid ] ; then
		ANS=
		printf "$POSD seems to be running.  OK to stop it? [y/N] > "; read ANS
		if [ "$ANS" = "y" ] ; then
			sudo service $POSD stop
			echo "Stopped."
		else
			echo "OK. Bye."
			exit 0
		fi
	fi
	cat $POSD | sudo tee /etc/init.d/$POSD > /dev/null
	RETVAL=$?
	if [ $RETVAL -eq 0 ] ; then
		echo "swap OK. now: $NOW"
		sudo chmod 755 /etc/init.d/$POSD
		ls -l /etc/init.d/$POSD
		ANS=
		printf "Do you want to start it now? [y/N] > "; read ANS
		if [ "$ANS" = "y" ] ; then
			sudo service $POSD start
			echo "Started. Bye."
		else
			echo "OK. Bye."
			exit 0
		fi
	else
		printf "swap failed because: >${RETVAL}<\n";
	fi
	exit
elif [ "$1" = "install" ] ; then
	if [ ! -f $SPH/$POSD ] ; then
		echo "$POSD does not exist to install from $SPH."
		exit 1
	fi
	if [ -f /etc/init.d/$POSD ] ; then
		echo "The wrapper: /etc/init.d/$POSD is already in place."
		checkrc;
		if [ "$INRC" != "" ] ; then
			echo " It is also set to start at boot."
		else
			ANS=
			printf " It is not set to start at boot. Do you want to do that now? [y/N] > "
			read ANS
			if [ "$ANS" = "y" ] ; then
				sudo update-rc.d $POSD defaults
				R1=$?
				if [ $R1 -eq 0 ] ; then
					echo "Done. Bye."
					exit 0
				else
					echo "Start-at-boot failed: $R1"
					exit 1
				fi
			else
				echo "OK. You may want to use this utility with the remove option. Bye."
				exit 0
			fi
		fi
		exit 1
	fi
	cat $POSD | sudo tee /etc/init.d/$POSD > /dev/null
	RETVAL=$?
	if [ $RETVAL -eq 0 ] ; then
		echo "Copied to /etc/init.d OK."
		sudo chmod 755 /etc/init.d/$POSD
		sudo update-rc.d $POSD defaults
		R2=$?
		if [ $R2 -eq 0 ] ; then
			echo "Set to start at boot OK."
		else
			echo "Start-at-boot failed: $R2."
		fi
		ANS=
		printf "Do you want to start it now? [y/N] > "; read ANS
		if [ "$ANS" = "y" ] ; then
			sudo service $POSD start
			echo "Started. Bye."
		else
			echo "OK. Bye."
			exit 0
		fi
	else
		printf "install failed because: >${RETVAL}<\n";
	fi
	exit
elif [ "$1" = "remove" ] ; then
	if [ ! -f /etc/init.d/$POSD ] ; then
		echo "The wrapper: /etc/init.d/$POSD doesn't exist."
		checkrc;
		if [ "$INRC" != "" ] ; then
			echo " But it's start-at-boot is set up. Remove that now? [y/N] > "
			if [ "$ANS" = "y" ] ; then
				sudo update-rc.d $POSD remove
				echo "Removed. Bye."
				exit 0
			fi
		fi
		exit 1
	fi
	if [ -f ${DHOME}/sphp.pid ] ; then
		ANS=
		printf "$POSD seems to be running.  OK to stop it? [y/N] > "; read ANS
		if [ "$ANS" = "y" ] ; then
			sudo service $POSD stop
			echo "Stopped."
		else
			echo "OK. Bye."
			exit 0
		fi
	fi
	if [ ! -f $SPH/$POSD ] ; then
		echo "There is no local copy of $POSD at $SPH"
		echo " Will not rm from /etc/init.d/";
		exit 1;
	else
		sudo rm /etc/init.d/$POSD
		sudo update-rc.d $POSD remove
		echo "Removed. Bye."
		exit 0
	fi
else
	if [ ! -f /etc/init.d/$POSD ] ; then
		echo "The wrapper: /etc/init.d/$POSD doesn't exist."
		if [ -f $SPH/$POSD ] ; then
			echo "It does exist at $SPH."
			echo "You could try using this utility with the install option to install it."
		fi
		exit 1
	fi
	sudo service $POSD $1
	exit
fi
