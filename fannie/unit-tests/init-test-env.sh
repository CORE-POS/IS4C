#!/bin/sh

if [ $( pwd | xargs basename ) != "unit-tests" ]; then
	echo 'Run this script from the directory "unit-tests"'
	exit 1
fi

if [ -f "../config.php.test-backup" ]; then
	echo 'Config backup (config.php.test-backup) already exists!'
	echo 'Cannot proceed. The existing backup would be destroyed.'
	exit 1
fi

if [ ! -f "../config.php" ]; then
	echo 'Warning: no config file found for fannie'
	echo 'Generating blank config'
	echo "<?php" > ../config.php
	echo "?>" >> ../config.php
fi

mv ../config.php ../config.php.test-backup

echo "Creating test configuration"
echo "<?php" > ../config.php
cd ../

FPATH=`pwd`
echo "\$FANNIE_ROOT = '$FPATH/';" >> config.php
echo "\$FANNIE_URL = '/fannie';" >> config.php

read -p "Test database host [127.0.0.1]:" host
if [ "$host" = "" ]; then
	echo "\$FANNIE_SERVER = '127.0.0.1';" >> config.php
else
	echo "\$FANNIE_SERVER = '$host';" >> config.php
fi

while true; do
	echo 'Database driver:'
	echo '1. MySQL (standard)'
	echo '2. MSSQL'
	echo '3. MySQL (mysqli)'
	echo '4. MySQL (pdo)'
    read -p "Enter type [1]:" dbt
    case $dbt in
	""  ) echo "\$FANNIE_SERVER_DBMS = 'MYSQL';" >> config.php; break;;
	"1" ) echo "\$FANNIE_SERVER_DBMS = 'MYSQL';" >> config.php; break;;
	"2" ) echo "\$FANNIE_SERVER_DBMS = 'MSSQL';" >> config.php; break;;
	"3" ) echo "\$FANNIE_SERVER_DBMS = 'MYSQLI';" >> config.php; break;;
	"4" ) echo "\$FANNIE_SERVER_DBMS = 'PDO_MYSQL';" >> config.php; break;;
        * ) echo "'$dbt' is not a valid choice"
    esac
done

read -p "Test database user [root]:" dbuser
if [ "$dbuser" = "" ]; then
	echo "\$FANNIE_SERVER_USER = 'root';" >> config.php
else
	echo "\$FANNIE_SERVER_USER = '$dbuser';" >> config.php
fi

while true; do
    read -p "Test database password:" dbpw
    case $dbpw in
	"" ) echo 'Password cannot be blank';;
	* ) echo "\$FANNIE_SERVER_PW = '$dbpw';" >> config.php; break;;
    esac
done

echo "\$FANNIE_OP_DB = '_unit_test_op';" >> config.php
echo "\$FANNIE_TRANS_DB = '_unit_test_trans';" >> config.php
echo "\$FANNIE_ARCHIVE_DB = '_unit_test_archive';" >> config.php

cd ../unit-tests/fannie-unit-tests/
echo "?>" >> ../../fannie/config.php

export phpunit="phpunit --bootstrap bootstrap.php"

echo ""
echo "SUMMARY"
echo "Existing configuration saved as config.php.test-backup"
echo "New testing configuration generated"

echo "Use bootstrap.php when running unit tests"
echo "InstallTest is a good place to start; that will create testing databases"

echo "Run end-test-env.sh to restore original configuration"
