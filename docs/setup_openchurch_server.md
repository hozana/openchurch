## Openchurch server setup

### Create server on Scaleway
- Make sure ssh keys are added in scaleway (account > credentials)
- Create scaleway server (ubuntu bionic)
- Add new IP in gandi

### Initial setup
- ssh root@{machine}
- create password: `openssl rand -base64 18` and `passwd`
```
apt-get update
apt-get upgrade
apt-get dist-upgrade
```
- create user `hozana`: `adduser hozana` `usermod -aG sudo hozana`
- install sudo `apt-get install sudo`
- add ssh keys in /root/.ssh/authorized_keys and in /home/hozana/authorized_keys
- pimp shell with .inputrc
```
"\e[A": history-search-backward
"\e[B": history-search-forward
"\e[C": forward-char
"\e[D": backward-char
```

### Add necessary libraries
```
sudo apt-get update
sudo apt-get install -y  \
        apt-transport-https \
        build-essential \
        curl \
        git \
        net-tools \
        rsyslog \
        supervisor \
        zlib1g-dev \
        gnupg
```

### Install php 7.1
```
sudo apt-get install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php7.1 php7.1-common
sudo apt-get install -y php7.1-zip php7.1-gd php7.1-mysql php7.1-mbstring php7.1-xml php7.1-curl
```

### Create SSH key
```
ssh-keygen -t rsa -b 4096 -f ~/.ssh/id_rsa_openchurch
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_rsa_openchurch
```

Then add the ssh keys to the corresponding github project. Project hozana/openchurch > settings > Deploy keys > Add deploy key
```
cat ~/.ssh/id_rsa_openchurch.pub
```
Clone the repository
```
git clone git@github.com:hozana/openchurch.git
```

### Install mysql and setup databases and users
Generate a password.
```
sudo apt-get install mysql-server
sudo systemctl start mysql
sudo mysql -uroot -p

CREATE DATABASE openchurch;
INSERT INTO mysql.user (User,Host,authentication_string,ssl_cipher,x509_issuer,x509_subject) VALUES('openchurch','localhost',PASSWORD('****PASSWORD***'),'','','');
FLUSH PRIVILEGES;
GRANT ALL PRIVILEGES ON openchurch.* to openchurch@localhost;
FLUSH PRIVILEGES;
exit
```

### Setup .env
```
cd ~/openchurch
cp .env.dist .env
vim .env
```
Make sure to:
1. paste mysql password
2. host is 127.0.0.1
2. APP_ENV=prod
3. generate secret

### Install composer
```
sudo ~/openchurch/config/docker/install_composer.sh
cd openchurch
composer --ansi -n --no-dev install --optimize-autoloader
composer dump-autoload --optimize --no-dev --classmap-authoritative
```

### Set user permissions for openchurch var/ directory
```
HTTPDUSER=`ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
cd ~/openchurch
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var/cache var/log
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var/cache var/log
```

### Import database
```
cd
wget https://raw.githubusercontent.com/wiki/hozana/openchurch/20180806openchurch.sql
mysql -uopenchurch -p openchurch < ./20180806openchurch.sql
cd ~/openchurch
bin/console doctrine:migrations:migrate --env=prod
bin/console --ansi -n --env=prod cache:warmup
```

### Install ES
```
sudo apt-get install default-jre
wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | sudo apt-key add -
echo "deb https://artifacts.elastic.co/packages/6.x/apt stable main" | sudo tee -a /etc/apt/sources.list.d/elastic-6.x.list
sudo apt-get update
sudo apt-get install elasticsearch
sudo /bin/systemctl daemon-reload
sudo /bin/systemctl enable elasticsearch.service
sudo systemctl start elasticsearch.service
```
Wait for ES to start follow logs in `sudo journalctl --unit elasticsearch`, and then
```
curl 127.0.0.1:9200
```
should return:
```
{
  "name" : "MlLX8D-",
  "cluster_name" : "elasticsearch",
  "cluster_uuid" : "35oE4tKXT3ubXLoKPOwvPg",
  "version" : {
    "number" : "6.4.1",
    "build_flavor" : "default",
    "build_type" : "deb",
    "build_hash" : "e36acdb",
    "build_date" : "2018-09-13T22:18:07.696808Z",
    "build_snapshot" : false,
    "lucene_version" : "7.4.0",
    "minimum_wire_compatibility_version" : "5.6.0",
    "minimum_index_compatibility_version" : "5.0.0"
  },
  "tagline" : "You Know, for Search"
}
```
Index data:
```
bin/console fos:elastica:populate
```

### Add Node.js with the right version!
```
cd
curl -sL https://deb.nodesource.com/setup_8.x > install_node.sh
chmod +x install_node.sh
sudo ./install_node.sh && rm install_node.sh
sudo apt-get install -y nodejs
sudo npm install -g n
sudo n 8.12.0
curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt-get update && sudo apt-get install yarn
sudo chown -R hozana:hozana .config
cd ~/openchurch
yarn install
cd openchurch-admin && yarn install
cd ~/openchurch
yarn run build
```

### Configure SSL with letsencrypt
- Follow guide lines in https://certbot.eff.org/lets-encrypt/ubuntubionic-other
- Add auto-renew in root crontab:
```
sudo crontab -e
0 0 1 * * certbot renew
```
- Generate certificate (you need to stop apache first)
```
sudo service apache2 stop
sudo certbot certonly --standalone --email thomas@hozana.org -d open-church.io -d www.open-church.io -d api.open-church.io -d admin.open-church.io
sudo service apache2 start
```

### Apache configuration
Create log directory for apache
```
cd
mkdir logs
```

Copy paste this conf in `sudo vim /etc/apache2/sites-available/openchurch.conf`

```
<VirtualHost *:80>
    ServerAdmin thomas@hozana.org
    ServerName open-church.io
    ServerAlias api.open-church.io www.open-church.io

    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerAdmin thomas@hozana.org
    ServerName open-church.io
    ServerAlias api.open-church.io www.open-church.io

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/open-church.io/cert.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/open-church.io/privkey.pem
    SSLCertificateChainFile /etc/letsencrypt/live/open-church.io/fullchain.pem

    DocumentRoot /home/hozana/openchurch/public

    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1

    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Headers "X-Auth-Token, Access-Control-Allow-Headers, Origin, Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers"
    Header set Access-Control-Allow-Methods "GET,HEAD,OPTIONS,POST,PUT,DELETE,PATCH"
    Header set Access-Control-Allow-Credentials "true"

    <Directory "/home/hozana/openchurch/public">
        DirectoryIndex index.php
        Options -Indexes +FollowSymlinks
        AllowOverride All
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ index.php [QSA,L]

        Require all granted
    </Directory>

    ErrorLog /home/hozana/logs/api-error.log
    CustomLog /home/hozana/logs/api-access.log combined
    LogFormat "%{LB-ClientIP}i %h %l %u %t \"%r\" %>s %O \"%{Referer}i\" \"%{User-Agent}i\"" combined
</VirtualHost>
```
Then
```
sudo a2ensite openchurch.conf
sudo a2enmod headers
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo systemctl restart apache2
```

You can check your installation on [https://open-church.io/](https://open-church.io/).
