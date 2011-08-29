#!/bin/sh

RSYNC_OPTIONS="-av --delete"
TARGET="/mnt/ext3drive"
DRIVE="/dev/sda1"
PREFIX="/srv/www/htdocs/it/drive_status"

MOUNT_TEST=`mount | grep ext3drive`
if [ -z "$MOUNT_TEST" ]; then
	echo "Mounting drive"
	mount $DRIVE
fi

MOUNT_VERIFY=`mount | grep ext3drive`
if [ -z "$MOUNT_VERIFY" ]; then
	echo "Drive could not be mounted"
	exit
fi

echo "Setting lock file"
touch $PREFIX/op.lock

rsync $RSYNC_OPTIONS rsync://nexus/backup_key/ /mnt/ext3drive/key
rsync $RSYNC_OPTIONS rsync://nexus/backup_nexus/ /mnt/ext3drive/nexus

echo "Removing lock file"
rm $PREFIX/op.lock

UMOUNT_TEST=`mount | grep ext3drive`
if [ -n "$UMOUNT_TEST" ]; then
	echo "Unmounting drive"
	umount $DRIVE
fi

DATE=`date +"%D %I:%M:%S%p"`
echo "Last updated $DATE" > $PREFIX/date.log
