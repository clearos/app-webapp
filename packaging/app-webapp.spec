
Name: app-webapp
Epoch: 1
Version: 1.0.0
Release: 1%{dist}
Summary: **webapp_app_name**
License: GPLv3
Group: ClearOS/Apps
Source: %{name}-%{version}.tar.gz
Buildarch: noarch
Requires: %{name}-core = 1:%{version}-%{release}
Requires: app-base

%description
**webapp_app_description**

%package core
Summary: **webapp_app_name** - Core
License: LGPLv3
Group: ClearOS/Libraries
Requires: app-base-core
Requires: app-network-core
Requires: unzip

%description core
**webapp_app_description**

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
%dir /usr/clearos/apps/webapp
/usr/clearos/apps/webapp/deploy
/usr/clearos/apps/webapp/language
/usr/clearos/apps/webapp/libraries
