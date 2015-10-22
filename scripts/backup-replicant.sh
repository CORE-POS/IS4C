#!/bin/sh
#
# Shell script to pause replication, flush information
# to disk, and backup raw database files via rsync
#

PASSWD="mysql-password"
DESTINATION="rsync-desctination"

mysqladmin -u root -p"$PASSWD" stop-slave
mysqladmin -u root -p"$PASSWD" flush-tables
mysqladmin -u root -p"$PASSWD" flush-logs

rsync -a --delete /var/lib/mysql $DESTINATION

mysqladmin -u root -p"$PASSWD" start-slave

