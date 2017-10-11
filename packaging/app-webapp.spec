
Name: app-webapp
Epoch: 1
Version: 2.4.0
Release: 1%{dist}
Summary: Web App Engine
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base
Requires: app-accounts
Requires: app-network
Requires: app-web-server
Requires: app-mariadb

%description
The Web App Engine provides libraries and tools for building web-based applications.

%package core
Summary: Web App Engine - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-base-core >= 1:1.5.24
Requires: app-certificate-manager-core
Requires: app-flexshare-core >= 1:2.4.3
Requires: app-groups-core
Requires: app-network-core
Requires: app-web-server-core >= 1:2.4.5
Requires: app-mariadb-core
Requires: openssl
Requires: zip

%description core
The Web App Engine provides libraries and tools for building web-based applications.

This package provides the core API and libraries.

%prep
%setup -q
%build

%install
mkdir -p -m 755 %{buildroot}/usr/clearos/apps/webapp
cp -r * %{buildroot}/usr/clearos/apps/webapp/


%post
logger -p local6.notice -t installer 'app-webapp - installing'

%post core
logger -p local6.notice -t installer 'app-webapp-core - installing'

if [ $1 -eq 1 ]; then
    [ -x /usr/clearos/apps/webapp/deploy/install ] && /usr/clearos/apps/webapp/deploy/install
fi

[ -x /usr/clearos/apps/webapp/deploy/upgrade ] && /usr/clearos/apps/webapp/deploy/upgrade

exit 0

%preun
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-webapp - uninstalling'
fi

%preun core
if [ $1 -eq 0 ]; then
    logger -p local6.notice -t installer 'app-webapp-core - uninstalling'
    [ -x /usr/clearos/apps/webapp/deploy/uninstall ] && /usr/clearos/apps/webapp/deploy/uninstall
fi

exit 0

%files
%defattr(-,root,root)
/usr/clearos/apps/webapp/controllers
/usr/clearos/apps/webapp/htdocs
/usr/clearos/apps/webapp/views

%files core
%defattr(-,root,root)
%exclude /usr/clearos/apps/webapp/packaging
%exclude /usr/clearos/apps/webapp/unify.json
%dir /usr/clearos/apps/webapp
/usr/clearos/apps/webapp/deploy
/usr/clearos/apps/webapp/language
/usr/clearos/apps/webapp/libraries
