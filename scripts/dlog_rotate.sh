#!/bin/bash
date="date -I"
year="date +%Y"

mysql -uroot -p_____ is4c_log -e "INSERT INTO dlog_"$year" (SELECT * FROM dtransactions)"

if [ $? == 0 ]
then
mysql -uroot -p_____ is4c_log -e "TRUNCATE TABLE dtransactions"
echo "`$date` - dtransactions archived and truncated." >> /pos/CORE/IS4C/fannie/logs/nightlylog.txt
else
mysqldump -uroot -p_____ is4c_log dtransactions > /pos/archives/dtransactions_`$date`.sql
mysqldump -uroot -p_____ is4c_log dlog_'$year' > /pos/archives/dlog_`$year`_`$date`.sql
echo "`$date` - ERROR - Archival failed. Backed up transaction tables." >> /pos/CORE/IS4C/fannie/logs/nightlylog.txt
fi