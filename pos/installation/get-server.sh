#!/bin/sh

# TODO - Oh, a lot. Use user-defined variables. Use system-defined variable. Set environments.

cd /tmp
wget http://is4c.coop/download/IS4C.tgz
tar -xzf /tmp/IS4C.tgz
mv /tmp/pos /

/pos/installation/ubuntu/install-server.sh