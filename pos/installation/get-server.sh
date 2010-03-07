#!/bin/sh

# TODO - Oh, a lot. Use user-defined variables. Use system-defined variable. Set environments.

cd /tmp
wget http://is4c.coop/download/pos.tar.gz
tar -xzf /tmp/pos.tar.gz
mv /tmp/pos /

/pos/installation/ubuntu/install-server.sh