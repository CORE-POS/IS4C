Name: it-core
Version: 1.0.0
Release: 2011.08.10
Summary: IT CORE Point of Sale
AutoReqProv: no
Group:  Applications/Internet
License: GPLv2       
BuildArch: noarch
URL: http://github.com/gohanman/IS4C           
Source0: it-core-1.0.0.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

#BuildRequires:  
Requires: php >= 5, php-mysql, php-xml, php-pear, php-ldap, mysql >= 5, httpd >= 2

%description
IT CORE is a collection of front end and back end
point of sale tools. This main package contains
the license and an Apache config addition

%package doc
Summary: IT CORE Documentation
Group: Applications/Internet
Requires: %{name} = %{version}-%{release}
%description doc
IT Core Web-based Documenation. Installs
to http://localhost/it-core/documentation/

%package fannie
Summary: IT CORE Backend 
Group: Applications/Internet
Requires: %{name} = %{version}-%{release}
%description fannie
IT Core Backend tool collection "Fannie". Installs
to http://localhost/it-core/fannie/

%package is4c-nf
Summary: IT CORE Frontend 
Group: Applications/Internet
Requires: %{name} = %{version}-%{release}
%description is4c-nf
A frameless IT CORE front end derived from IS4C
Installs to http://localhost/it-core/is4c-nf/

%package posdriver-sph
Summary: IT CORE Scale Driver 
Group: Applications/Internet
Requires: %{name} = %{version}-%{release}, it-core-is4c-nf = %{version}-%{release}, mono-core
BuildRequires: mono-devel
%description posdriver-sph
A mono-based driver for monitoring serial port(s)
and reading UDP input

%package posdriver-ssd
Summary: IT CORE Scale Driver 
Group: Applications/Internet
Requires: %{name} = %{version}-%{release}, it-core-is4c-nf = %{version}-%{release} 
%description posdriver-ssd
A C-based driver for monitoring serial port(s)

# debug script fails on filenames with spaces so disabling...
%define  debug_package %{nil}

%prep
%setup -q
# scrub binaries to mono requirements aren't auto-added
rm pos/is4c-nf/scale-drivers/drivers/NewMagellan/*.exe
rm pos/is4c-nf/scale-drivers/drivers/NewMagellan/*.dll
# ditto for libc binaries
rm pos/is4c-nf/scale-drivers/c-wrappers/nm

# fixup paths
sed -e 's/.*private static String MAGELLAN_OUTPUT_DIR.*/private static String MAGELLAN_OUTPUT_DIR = "ss-output";/g' --in-place="" pos/is4c-nf/scale-drivers/drivers/NewMagellan/SPH_Magellan_Scale.cs

%build
cd pos/is4c-nf/scale-drivers/drivers/NewMagellan && make
cd ../../../../../
cd pos/is4c-nf/scale-drivers/drivers/rs232 && make

%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/init.d/
mkdir -p $RPM_BUILD_ROOT%{_datadir}/it-core
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/run/posdriver-sph
mkdir -p $RPM_BUILD_ROOT%{_localstatedir}/run/posdriver-ssd

cat << 'EOF' > $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/it-core.conf
# simple apache redirection
Alias /it-core %{_datadir}/it-core

<Directory "%{_datadir}/it-core">
        Options Indexes FollowSymLinks
</Directory>
EOF

cat << 'EOF' > fannie/config.php
<?php
?>
EOF

cat << 'EOF' > pos/is4c-nf/ini.php
<?php
$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }
if (!isset($CORE_LOCAL))
        require_once($CORE_PATH."lib/LocalStorage/conf.php");
?>
EOF

cp -r fannie $RPM_BUILD_ROOT%{_datadir}/it-core/
cp -r documentation $RPM_BUILD_ROOT%{_datadir}/it-core/
cp -r pos/is4c-nf $RPM_BUILD_ROOT%{_datadir}/it-core/
cp -r license $RPM_BUILD_ROOT%{_datadir}/it-core/

# grab init scripts, create empty log files
mv $RPM_BUILD_ROOT%{_datadir}/it-core/is4c-nf/scale-drivers/drivers/NewMagellan/posdriver-sph $RPM_BUILD_ROOT%{_sysconfdir}/init.d
mv $RPM_BUILD_ROOT%{_datadir}/it-core/is4c-nf/scale-drivers/drivers/rs232/posdriver-ssd $RPM_BUILD_ROOT%{_sysconfdir}/init.d
touch $RPM_BUILD_ROOT%{_localstatedir}/run/posdriver-sph/pos.log
touch $RPM_BUILD_ROOT%{_localstatedir}/run/posdriver-ssd/ssd.log

%clean
rm -rf $RPM_BUILD_ROOT

%post fannie
chown apache:apache %{_datadir}/it-core/fannie/config.php
chmod 644 %{_datadir}/it-core/fannie/config.php

%post is4c-nf
chown apache:apache %{_datadir}/it-core/is4c-nf/ini.php
chmod 644 %{_datadir}/it-core/is4c-nf/ini.php

%post posdriver-sph
chown apache:apache %{_datadir}/it-core/is4c-nf/scale-drivers/drivers/NewMagellan/ports.conf
chmod -R 777 %{_datadir}/it-core/is4c-nf/scale-drivers/drivers/NewMagellan/ss-output

%post posdriver-ssd
chown nobody:nobody %{_localstatedir}/posdriver-ssd

%files
%defattr(-,root,root,-)
%{_datadir}/it-core/license
%{_sysconfdir}/httpd/conf.d/it-core.conf

%files doc
%defattr(-,root,root,-)
%{_datadir}/it-core/documentation

%files fannie
%defattr(-,root,root,-)
%{_datadir}/it-core/fannie

%files is4c-nf
%defattr(-,root,root,-)
%{_datadir}/it-core/is4c-nf/*.*
%{_datadir}/it-core/is4c-nf/DEV_README
%{_datadir}/it-core/is4c-nf/WFC_VS_RELEASE
%{_datadir}/it-core/is4c-nf/ajax-callbacks/
%{_datadir}/it-core/is4c-nf/cc-modules/
%{_datadir}/it-core/is4c-nf/graphics/
%{_datadir}/it-core/is4c-nf/gui-class-lib/
%{_datadir}/it-core/is4c-nf/gui-modules/
%{_datadir}/it-core/is4c-nf/install/
%{_datadir}/it-core/is4c-nf/js/
%{_datadir}/it-core/is4c-nf/lib/
%{_datadir}/it-core/is4c-nf/log/
%{_datadir}/it-core/is4c-nf/parser-class-lib/
%{_datadir}/it-core/is4c-nf/quickkeys/
%{_datadir}/it-core/is4c-nf/test/
%{_datadir}/it-core/is4c-nf/scale-drivers/c-wrappers/
%{_datadir}/it-core/is4c-nf/scale-drivers/drivers/Magellan/
%{_datadir}/it-core/is4c-nf/scale-drivers/php-wrappers/ScaleDriverWrapper.php

%files posdriver-sph
%defattr(-,root,root,-)
%{_datadir}/it-core/is4c-nf/scale-drivers/drivers/NewMagellan/
%{_datadir}/it-core/is4c-nf/scale-drivers/php-wrappers/NewMagellan.php
%{_datadir}/it-core/is4c-nf/scale-drivers/php-wrappers/NM_Ingenico.php
%{_sysconfdir}/init.d/posdriver-sph
%dir %{_localstatedir}/run/posdriver-sph
%ghost %{_localstatedir}/run/posdriver-sph/pos.log

%files posdriver-ssd
%defattr(-,root,root,-)
%{_datadir}/it-core/is4c-nf/scale-drivers/drivers/rs232/
%{_datadir}/it-core/is4c-nf/scale-drivers/php-wrappers/ssd.php
%{_sysconfdir}/init.d/posdriver-ssd
%dir %{_localstatedir}/run/posdriver-ssd
%ghost %{_localstatedir}/run/posdriver-ssd/ssd.log

%changelog
