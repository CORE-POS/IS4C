#!/bin/sh

if [ $# -ne 1 ]; then
	echo "Usage: $0 <version>"
	exit 1
fi

cd ../..

# clean old sources
rm -rf dist/rpm/SOURCES/it-core-*
mkdir dist/rpm/SOURCES/it-core-$1

# tar once into a new it-core top level directory
# then extract and re-tar with the new top level directory
# this seems like the easiest way to leverage .gitignore
# in exlcuding files and get the top level directory name
# that rpmbuild demands
tar cf dist/rpm/SOURCES/it-core-$1/it-core-$1.tar.gz -X .gitignore documentation fannie license pos/is4c-nf
cd dist/rpm/SOURCES/it-core-$1/
tar xf it-core-$1.tar.gz
rm it-core-$1.tar.gz
cd ..
tar czf it-core-$1.tar.gz it-core-$1
rm -rf it-core-$1

# fix-up spec file for correct version
cd ../SPECS
sed -e "s/Version:.*/Version: $1/g" --in-place="" it-core.spec 
sed -e "s/Source0:.*/Source0: it-core-$1.tar.gz/g" --in-place="" it-core.spec 
cd ..

# update rpmmacros
PWD=`pwd`
echo "%_topdir $PWD" > ~/.rpmmacros

