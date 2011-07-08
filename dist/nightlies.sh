#!/bin/sh

VERSION=0.1.0
RELEASE=`date +%Y.%m.%d`

# build RPMs
cd rpm
if [ 1 == 0 ]; then
	./setup-here.sh $VERSION
	cd SPECS
	sed -e "s/Release:.*/Release: $RELEASE/g" --in-place="" it-core.spec 
	cd ..
	rpmbuild -ba SPECS/it-core.spec
fi

# build DEBs
cd ../deb
export PERL5LIB="/usr/share/perl5/"
./make-debs.sh $VERSION $RELEASE

# remove old binaries
cd ..
find . -name '*.rpm' -mtime +7 | xargs rm
find . -name '*.deb' -mtime +7 | xargs rm
