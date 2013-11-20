#!/bin/sh

if [ $# -ne 2 ]; then
	echo "Usage: $0 <version> <build>"
	exit 1
fi

# Base package:
# Add dependencies
# Adjust /etc path to apache2
if [ -f ../rpm/RPMS/noarch/it-core-$1-$2.noarch.rpm ]; then
	echo "Building it-core base package"
	alien --keep-version --generate --scripts ../rpm/RPMS/noarch/it-core-$1-$2.noarch.rpm
	cd it-core-$1/
	sed -e "s/Depends:.*/Depends: php5, php5-mysql, libapache2-mod-php5, mysql-server (>=5.0)/g" --in-place="" debian/control
	mv etc/httpd etc/apache2
	dpkg-buildpackage -d -b
	cd ..
	rm -rf it-core-$1/
	rm -rf it-core-$1.orig/
else
	echo "File not found: it-core-$1-$2.noarch.rpm"
fi

if [ -f ../rpm/RPMS/noarch/it-core-doc-$1-$2.noarch.rpm ]; then
	echo "Building it-core doc package"
	alien --keep-version --generate --scripts ../rpm/RPMS/noarch/it-core-doc-$1-$2.noarch.rpm
	cd it-core-doc-$1/
	sed -e "s/Depends:.*/Depends: it-core (=$1-$2)/g" --in-place="" debian/control
	dpkg-buildpackage -d -b
	cd ..
	rm -rf it-core-doc-$1/
	rm -rf it-core-doc-$1.orig/
else
	echo "File not found: it-core-doc-$1-$2.noarch.rpm"
fi

# Fannie package:
# Change apache user to www-data
if [ -f ../rpm/RPMS/noarch/it-core-fannie-$1-$2.noarch.rpm ]; then
	echo "Building it-core fannie package"
	alien --keep-version --generate --scripts ../rpm/RPMS/noarch/it-core-fannie-$1-$2.noarch.rpm
	cd it-core-fannie-$1/
	sed -e "s/Depends:.*/Depends: it-core (=$1-$2)/g" --in-place="" debian/control
	sed -e "s/apache/www-data/g" --in-place="" debian/postinst
	sed -e "s/apache/www-data/g" --in-place="" debian/preinst
	dpkg-buildpackage -d -b
	cd ..
	rm -rf it-core-fannie-$1/
	rm -rf it-core-fannie-$1.orig/
else
	echo "File not found: it-core-fannie-$1-$2.noarch.rpm"
fi

# IS4C-nf package:
# Change apache user to www-data
if [ -f ../rpm/RPMS/noarch/it-core-is4c-nf-$1-$2.noarch.rpm ]; then
	echo "Building it-core is4c-nf package"
	alien --keep-version --generate --scripts ../rpm/RPMS/noarch/it-core-is4c-nf-$1-$2.noarch.rpm
	cd it-core-is4c-nf-$1/
	sed -e "s/Depends:.*/Depends: it-core (=$1-$2)/g" --in-place="" debian/control
	sed -e "s/apache/www-data/g" --in-place="" debian/postinst
	dpkg-buildpackage -d -b
	cd ..
	rm -rf it-core-is4c-nf-$1/
	rm -rf it-core-is4c-nf-$1.orig/
else
	echo "File not found: it-core-is4c-nf-$1-$2.noarch.rpm"
fi

# mono scale driver
# Change apache user to www-data
if [ -f ../rpm/RPMS/noarch/it-core-posdriver-sph-$1-$2.noarch.rpm ]; then
	echo "Building it-core posdriver-sph package"
	alien --keep-version --generate --scripts ../rpm/RPMS/noarch/it-core-posdriver-sph-$1-$2.noarch.rpm
	cd it-core-posdriver-sph-$1/
	sed -e "s/apache/www-data/g" --in-place="" debian/postinst
	dpkg-buildpackage -d -b
	cd ..
	rm -rf it-core-posdriver-sph-$1/
	rm -rf it-core-posdriver-sph-$1.orig/
else
	echo "File not found: it-core-posdriver-sph-$1-$2.noarch.rpm"
fi

# ssd scale driver
if [ -f ../rpm/RPMS/noarch/it-core-posdriver-ssd-$1-$2.noarch.rpm ]; then
	echo "Building it-core posdriver-ssd package"
	alien --keep-version --generate --scripts ../rpm/RPMS/noarch/it-core-posdriver-ssd-$1-$2.noarch.rpm
	cd it-core-posdriver-ssd-$1/
	dpkg-buildpackage -d -b
	cd ..
	rm -rf it-core-posdriver-ssd-$1/
	rm -rf it-core-posdriver-ssd-$1.orig/
else
	echo "File not found: it-core-posdriver-sph-$1-$2.noarch.rpm"
fi

rm *.changes
mv *.deb DEBS/
