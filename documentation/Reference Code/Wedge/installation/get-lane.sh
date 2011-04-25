#!/bin/sh

# TODO - Oh, a lot. Use user-defined variables. Use system-defined variable. Set environments.

cd /tmp
wget http://is4c.coop/download/IS4C.2.2_dev.tgz
tar -xzf /tmp/IS4C.2.2_dev.tgz
cp -r /tmp/IS4C/pos /

/pos/installation/ubuntu/install-lane.sh

cat /tmp/IS4C/README
