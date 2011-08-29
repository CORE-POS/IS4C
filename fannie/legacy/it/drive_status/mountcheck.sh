#!/bin/sh

MOUNT_CHECK=`mount | grep sda1`
if [ -n "$MOUNT_CHECK" ]; then
	echo "The drive is currently mounted"
	POSITION_CHECK=`mount | grep /mnt/ext3drive`
	if [ -n "$POSITION_CHECK" ]; then
		echo "The drive is mounted in the correct spot"
	else
		echo "The drive is not mounted correctly"
	fi
else
	echo "The drive is not currently mounted"
fi

