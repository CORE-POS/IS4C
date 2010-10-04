#!/bin/sh

# remove usernames & passwords before committing

mv config.php config.php.bak
echo "<?php" > config.php
echo "?>" >> config.php
chmod 666 config.php
wget -O - localhost/git/fannie/install.php &> /dev/null

cp inventory/unfidownload.py inventory/unfidownload.bak
sed -i -e "s/USERNAME=.*/USERNAME=\"\"/g" inventory/unfidownload.py
sed -i -e "s/PASSWORD=.*/PASSWORD=\"\"/g" inventory/unfidownload.py
