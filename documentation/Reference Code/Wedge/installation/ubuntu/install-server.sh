#!/bin/bash

aptitude update -y
aptitude safe-upgrade -y
echo -e "Enter the password for the root user in MySQL: \c"
read -s ROOT_PASSWORD
echo "mysql-server mysql-server/root_password select $ROOT_PASSWORD" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again select $ROOT_PASSWORD" | debconf-set-selections
echo "python-mysqldb mysql-server/root_password select $ROOT_PASSWORD" | debconf-set-selections
echo "python-mysqldb mysql-server/root_password_again select $ROOT_PASSWORD" | debconf-set-selections
aptitude install -y mysql-server apache2 php5 libapache2-mod-php5 python-mysqldb php5-mysql php-image-barcode php-fpdf

python /pos/installation/install_server.py

/pos/installation/ubuntu/php_server.pl
/pos/installation/ubuntu/apache_server.pl