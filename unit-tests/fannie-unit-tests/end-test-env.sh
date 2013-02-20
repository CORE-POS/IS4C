#!/bin/sh

if [ $( pwd | xargs basename ) != "fannie-unit-tests" ]; then
	echo 'Run this script from the directory "fannie-unit-tests"'
	exit 1
fi

if [ ! -f "../../fannie/config.php.test-backup" ]; then
	echo 'Error: No config backup file available'
	exit 1	
fi

echo "Restoring backup configuration"
mv ../../fannie/config.php.test-backup ../../fannie/config.php

