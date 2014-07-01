#!/bin/sh

if [ "$1" == "" ]; then
    echo "Usage: untab.sh [directory]"
    echo "Purpose: expands tabs in PHP files to four spaces"
    exit 1
elif [ ! -d "$1" ]; then
    echo "missing directory: $1"
    exit 1
fi

find "$1" -name '*.php' ! -type d ! -type l -exec bash -c 'expand -t 4 "$0" > /tmp/e && mv /tmp/e "$0"' {} \;
