#!/bin/sh

if [ $( pwd | xargs basename ) != "unit-tests" ]; then
	echo 'Run this script from the directory "unit-tests"'
	exit 1
fi

if [ ! -f "../config.php.test-backup" ]; then
	echo 'Error: No config backup file available'
	exit 1	
fi

echo "Restoring backup configuration"
mv ../config.php.test-backup ../config.php

