#!/bin/sh

if [ -z $1 ]; then
	echo "Usage: secure.sh [username]";
	exit 0
fi

echo "Create a password for $1"
if [ -f .htpasswd ]; then
	htpasswd .htpasswd $1
else
	htpasswd -c .htpasswd $1
fi

PWD=`pwd`
if [ ! -f .htaccess ]; then
	echo "Creating .htaccess file"
	echo "AuthType Basic" > .htaccess
	echo "AuthName \"Fanie Config\"" >> .htaccess
	echo "AuthUserFile $PWD/.htpasswd" >> .htaccess
	echo "Require valid-user" >> .htaccess
fi
