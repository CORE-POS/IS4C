#!/bin/sh

echo "Compiling scanner scale daemon"

cd /pos/is4c/rs232
gcc ssd.c -o ssd

echo "... Done"

echo "Installing device drivers"

if [ /etc/init.d/ssd ]; then
  rm /etc/init.d/ssd
fi

if [ /etc/init.d/lptty ]; then
  rm /etc/init.d/lptty
fi


ln -s /pos/is4c/rs232/ssd /etc/init.d/ssd
ln -s /pos/is4c/rs232/lptty /etc/init.d/lptty

/pos/installation/ubuntu/rclocal_lane.pl

/pos/installation/ubuntu/startssd.pl
/etc/init.d/lptty

echo "... Done"
