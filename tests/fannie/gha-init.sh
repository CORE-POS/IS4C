#/usr/bin/env sh
set -e

mysql -u root -proot -e "DROP DATABASE IF EXISTS unit_test_op;"
mysql -u root -proot -e "DROP DATABASE IF EXISTS unit_test_trans;"
mysql -u root -proot -e "DROP DATABASE IF EXISTS unit_test_archive;"
mysql -u root -proot -e "DROP DATABASE IF EXISTS unit_test_laneop;"
mysql -u root -proot -e "DROP DATABASE IF EXISTS unit_test_lanetrans;"

# create Fannie databases
mysql -u root -proot -e "CREATE DATABASE unit_test_op;"
mysql -u root -proot -e "CREATE DATABASE unit_test_trans;"
mysql -u root -proot -e "CREATE DATABASE unit_test_archive;"
mysql -u root -proot -e "CREATE DATABASE unit_test_laneop;"
mysql -u root -proot -e "CREATE DATABASE unit_test_lanetrans;"

# create default configuration file
cp fannie/config.php.dist fannie/config.php
# add path options
echo "\$FANNIE_ROOT = '"`pwd`"/fannie/';" >> fannie/config.php
echo "\$FANNIE_URL = '/fannie/';" >> fannie/config.php
# add database options
echo "\$FANNIE_SERVER = 'localhost';" >> fannie/config.php
echo "\$FANNIE_SERVER_USER = 'root';" >> fannie/config.php
echo "\$FANNIE_SERVER_PW = 'root';" >> fannie/config.php
echo "\$FANNIE_OP_DB = 'unit_test_op';" >> fannie/config.php
echo "\$FANNIE_TRANS_DB = 'unit_test_trans';" >> fannie/config.php
echo "\$FANNIE_ARCHIVE_DB = 'unit_test_archive';" >> fannie/config.php
echo "\$FANNIE_ARCHIVE_METHOD = 'partitions';" >> fannie/config.php
echo "\$FANNIE_LANES = array();" >> fannie/config.php
# set database driver based on environment variable
echo "\$FANNIE_SERVER_DBMS = '$DB_DRIVER';" >> fannie/config.php
echo "\$FANNIE_STORE_ID = '1';" >> fannie/config.php
echo "\$FANNIE_LANES = array(array('host'=>'localhost','type'=>'$DB_DRIVER','user'=>'root','pw'=>'','op'=>'unit_test_laneop','trans'=>'unit_test_lanetrans','offline'=>0));" >> fannie/config.php


