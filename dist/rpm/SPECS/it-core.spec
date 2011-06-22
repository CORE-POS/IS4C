Name: it-core
Version: 0.1.0
Release: 1%{?dist}
Summary: IT CORE Point of Sale
BuildArch: noarch
AutoReqProv: no
Group:  Applications/Internet
License: GPLv2       
URL: http://github.com/gohanman/IS4C           
Source0: it-core-0.1.0.tar.gz
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

# debug script fails on filenames with spaces so disabling...
%define  debug_package %{nil}

%prep
%setup -q
# scrub binaries to mono requirements aren't auto-added
rm pos/is4c-nf/scale-drivers/drivers/NewMagellan/*.exe
rm pos/is4c-nf/scale-drivers/drivers/NewMagellan/*.dll

%build

%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d
mkdir -p $RPM_BUILD_ROOT%{_datadir}/it-core

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
?>
EOF

cp -r fannie $RPM_BUILD_ROOT%{_datadir}/it-core/
cp -r documentation $RPM_BUILD_ROOT%{_datadir}/it-core/
cp -r pos/is4c-nf $RPM_BUILD_ROOT%{_datadir}/it-core/
cp -r license $RPM_BUILD_ROOT%{_datadir}/it-core/

%clean
rm -rf $RPM_BUILD_ROOT

%post fannie
chown apache:apache %{_datadir}/it-core/fannie/config.php
chmod 644 %{_datadir}/it-core/fannie/config.php

%post is4c-nf
chown apache:apache %{_datadir}/it-core/is4c-nf/ini.php
chmod 644 %{_datadir}/it-core/it-core/is4c-nf/ini.php

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
%{_datadir}/it-core/is4c-nf

%changelog
