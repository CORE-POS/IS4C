#/usr/bin/env sh
set -e

mysql -u root -proot -e "DROP DATABASE IF EXISTS unit_test_opdata;"
mysql -u root -proot -e "DROP DATABASE IF EXISTS unit_test_translog;"
mysql -u root -proot -e "DROP DATABASE IF EXISTS core_trans;"

# create Fannie databases
mysql -u root -proot -e "CREATE DATABASE unit_test_opdata;"
mysql -u root -proot -e "CREATE DATABASE unit_test_translog;"
mysql -u root -proot -e "CREATE DATABASE core_trans;"

# create default configuration file
cp pos/is4c-nf/ini.php.dist pos/is4c-nf/ini.php
# strip closing PHP tag so we can add settings
sed -e "s/?>//g" --in-place="" pos/is4c-nf/ini.php
echo "\$CORE_LOCAL->set('laneno', 99);\n" >> pos/is4c-nf/ini.php
echo "\$CORE_LOCAL->set('store_id', 1);\n" >> pos/is4c-nf/ini.php
echo "\$CORE_LOCAL->set('localhost', 'localhost');\n" >> pos/is4c-nf/ini.php
echo "\$CORE_LOCAL->set('localUser', 'root');\n" >> pos/is4c-nf/ini.php
echo "\$CORE_LOCAL->set('localPass', 'root');\n" >> pos/is4c-nf/ini.php
echo "\$CORE_LOCAL->set('pDatabase', 'unit_test_opdata');\n" >> pos/is4c-nf/ini.php
echo "\$CORE_LOCAL->set('tDatabase', 'unit_test_translog');\n" >> pos/is4c-nf/ini.php
echo "\$CORE_LOCAL->set('DBMS', '$DB_DRIVER');\n" >> pos/is4c-nf/ini.php

cat << EOF > pos/is4c-nf/ini.json
{
    laneno: 99,
    store_id: 1,
    localhost: "localhost",
    localUser: "root",
    localPass: "root",
    pDatabase: "unit_test_opdata",
    tDatabase: "unit_test_translog",
    DBMS: "$DB_DRIVER"
}
EOF

