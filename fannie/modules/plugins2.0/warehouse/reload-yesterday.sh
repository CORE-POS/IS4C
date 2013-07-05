#!/bin/sh
#
# simple script to load yesterday's data
# temporary solution until plugins can tie
# into Fannie's cron system

my_folder=`dirname "$0"`
cd "$my_folder"

yesterday=`date --date=yesterday +"%F"`

php CwLoadDataPage.php -a -d "$yesterday"
