#!/bin/sh

# simple script to find everywhere the drive is mounted
# and attempt to unmount it from each spot

DRIVE="/dev/sda1"
MOUNT_POINT=`mount | grep $DRIVE | awk '{ print $3 }'`

for i in $MOUNT_POINT; do
	umount $i
done
