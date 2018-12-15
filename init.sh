#!/bin/bash
cd
echo 'Updating yum'
yum -y update
echo 'Configuring NTP'
yum -y install ntp
ntpdate pool.ntp.org
systemctl start ntpd
echo 'Installing epel-release'
yum -y install epel-release
echo 'Installing Remi'
yum -y install http://rpms.remirepo.net/enterprise/remi-release-7.rpm
yum-config-manager --enable remi-php72
yum update
echo 'Installing PHP'
yum -y install php72
echo 'Installing Modules'
yum -y install php72-php-fpm php72-php-gd php72-php-json php72-php-mbstring php72-php-mysqlnd php72-php-xml php72-php-xmlrpc php72-php-opcache
echo 'Installing git'
yum -y install git
echo 'Installing wget'
yum -y install wget
echo 'Installing nginx'
yum -y install nginx
echo 'Adding nginx rule to firewall'
sudo firewall-cmd --permanent --zone=public --add-service=http
sudo firewall-cmd --permanent --zone=public --add-service=https
sudo firewall-cmd --reload
echo 'Configuring PHP for nginx'
systemctl enable php72-php-fpm.service
systemctl start php72-php-fpm.service
systemctl status php72-php-fpm.service
sed -i 's/user = apache/user = nginx/g' /etc/opt/remi/php72/php-fpm.d/www.conf
sed -i 's/group = apache/group = nginx/g' /etc/opt/remi/php72/php-fpm.d/www.conf
systemctl restart php72-php-fpm.service
cd /etc/nginx
mv nginx.conf nginx.conf.backup
wget https://raw.githubusercontent.com/NERDev/GIFTron-Back-End/master/nginx.conf
mkdir /usr/share/NERDev/
cd /usr/share/NERDev/
mkdir -p webroot/giftron/api/v1
mkdir -p git/GIFTron
mkdir data
#chown nginx:nginx webroot -R
systemctl restart nginx
systemctl enable nginx
cd git/GIFTron
git clone https://github.com/NERDev/GIFTron-Back-End.git
echo 'Bootstrapping this machine...'
cd GIFTron-Back-End/v1
php72 api.php build