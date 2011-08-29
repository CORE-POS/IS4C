#!/bin/sh

if [ $# != 1 ]; then
	echo "Usage: case-search.sh <string>"
	echo "Matches files containing the string but in a different case"
	exit
fi

find . -name '*.php' | xargs grep -i $1 | grep -v $1
