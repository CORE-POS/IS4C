#!/bin/sh

# TODO - Oh, a lot. Use user-defined variables. Use system-defined variable. Set environments.

cd /tmp
wget http://is4c.coop/download/IS4C.tgz
tar -xzf /tmp/IS4C.tgz
cp -r /tmp/IS4C/pos /

# /pos/installation/ubuntu/install-lane.sh
python /pos/installation/install_lane.py

cat /tmp/IS4C/README
