#!/bin/sh

VERSION=1.0.0
RELEASE=`date +%Y.%m.%d`

git pull

# build RPMs
cd rpm
./setup-here.sh $VERSION
cd SPECS
sed -e "s/Release:.*/Release: $RELEASE/g" --in-place="" it-core.spec 
cd ..
rpmbuild -ba SPECS/it-core.spec

# build DEBs
cd ../deb
export PERL5LIB="/usr/share/perl5/"
./make-debs.sh $VERSION $RELEASE

# remove old binaries
cd ..
find . -name '*.rpm' -mtime +7 | xargs rm
find . -name '*.deb' -mtime +7 | xargs rm
