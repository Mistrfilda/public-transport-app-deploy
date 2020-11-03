# Deploy for public transport app

Deploy is divided into second project, since my Raspberry PI runs only in my network :)

- Simple unifi [dns forwarding ](.docs/unifi.md)
- My rpi 4b  [Raspberry pi](.docs/raspberry4b-pi.md)
- My old rpi 3b  [Raspberry pi](.docs/raspberry3b-pi.md)
- php 7.4 [upgrade ](.docs/php74.md)

# Usefull bash scripts

- [Simple database dump](scripts/database-dump.sh)

# Raspberry pi instalation

Run on localhost

If you are using raspbbery pi with sd card - It's better to disable swap to be used on raspbbery pi (reboot is required)
```bash
sudo systemctl disable dphys-swapfile.service
sudo reboot
```


Update before running anything else
```bash
sudo apt-get update
sudo apt-get upgrade
```

Apache instalation (for php 7.3)
```bash
sudo apt install apache2 -y
sudo apt install php libapache2-mod-php -y
```

PHP 7.4
```bash
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ buster main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
sudo apt upgrade
sudo apt install php7.4 php7.4-common php7.4-mysql php7.4-cli
```

Setup virtualhost

```bash
cd /etc/apache2/sites-available
sudo nano mysite-pid.conf
```

```apacheconfig
<VirtualHost *:80>
	# The ServerName directive sets the request scheme, hostname and port that
	# the server uses to identify itself. This is used when creating
	# redirection URLs. In the context of virtual hosts, the ServerName
	# specifies what hostname must appear in the request's Host: header to
	# match this virtual host. For the default virtual host (this file) this
	# value is not decisive as it is used as a last resort host regardless.
	# However, you must set it for any further virtual host explicitly.
	#ServerName www.example.com

	ServerAdmin __EMAIL__
	DocumentRoot /var/www/sites/kuchar-pid.cz/www

	# Available loglevels: trace8, ..., trace1, debug, info, notice, warn,
	# error, crit, alert, emerg.
	# It is also possible to configure the loglevel for particular
	# modules, e.g.
	#LogLevel info ssl:warn

	ErrorLog ${APACHE_LOG_DIR}/error.log
	CustomLog ${APACHE_LOG_DIR}/access.log combined

	# For most configuration files from conf-available/, which are
	# enabled or disabled at a global level, it is possible to
	# include a line for only one particular virtual host. For example the
	# following line enables the CGI configuration for this host only
	# after it has been globally disabled with "a2disconf".
	#Include conf-available/serve-cgi-bin.conf
</VirtualHost>
```

Enable virtualhost
```bash
sudo a2ensite mysite-pid.conf
sudo systemctl restart apache2
```

PHP config
```bash
sudo apt-get install php-mysql
sudo nano /etc/php/7.3/apache2/php.ini
```

Enable these lines:
```apacheconfig
extension=pdo_mysql
```

Enable mod rewrite for .htaccess
```bash
sudo a2enmod rewrite
```

Next go apache conf and edit lines
```bash
sudo nano /etc/apache2/apache2.conf 
```
```apacheconfig
<Directory /var/www/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

And then restart apache
```bash
sudo service apache2 restart
```

Create deployer user
```bash
sudo useradd deployer
sudo usermod -a -G www-data deployer

sudo mkdir /home/deployer && sudo chown -R deployer:deployer /home/deployer
```

You can also copy contents of .bash_profile or .bashrc into deployer .bash_profile

Create project folder and change ownership
```bash
sudo mkdir /var/www/sites/kuchar-pid.cz
sudo chown -R deployer:www-data /var/www/sites
```

Install composer + node js+ yarn
```bash
sudo apt install composer -y
curl -sL https://deb.nodesource.com/setup_12.x | sudo -E bash -
sudo apt-get install -y nodejs

curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt update && sudo apt install yarn -y
```

This project uses node-sass package but this package is not supported on arm procesors out of box. Best way how to solve this is to create some random folder (for example in home directory) and install node-sass package there.

```bash
cd
npm install node-sass
```

After successfull installation and build, use path to builded binging.node in /etc/enviroment

```bash
sudo nano /etc/enviroment
```

Put something like this there

```bash
SASS_BINARY_PATH=/home/deployer/node-sass-cache/node_modules/node-sass/build/Release/obj.target/binding.node
```

Install Mysql
```bash
sudo apt install mariadb-server -y
sudo mysql
```

```mysql
GRANT ALL ON *.* TO 'admin'@'localhost' IDENTIFIED BY 'password' WITH GRANT OPTION;
FLUSH PRIVILEGES;
```

Now you can connect to mysql database from your favourite gui :D and create db and user for project.


Install rabbitmq and enable full admin access (insecure but since our PI is only in local network :D)
```bash
sudo apt install rabbitmq-server -y
sudo systemctl enable rabbitmq-server
sudo systemctl start rabbitmq-server
sudo rabbitmq-plugins enable rabbitmq_management
sudo rabbitmqctl add_user admin Ab123456
sudo rabbitmqctl set_user_tags admin administrator
sudo rabbitmqctl set_permissions -p / admin ".*" ".*" ".*"
```

Now go to http://<PI-REMOTE-URL>:15672/ and create user for application (gui is better :D)


Clone project (only for testing purposes)
```bash
git clone https://github.com/Mistrfilda/public-transport-app.git kuchar-pid.cz/ 
```

Set acl for temp and log folder
```bash
sudo mkdir /var/www/sites
sudo chown -R deployer:www-data /var/www/sites
sudo setfacl -dR -m u:www-data:rwX -m u:deployer:rwX log/
sudo setfacl -R -m u:www-data:rwX -m u:deployer:rwX log/
sudo setfacl -dR -m u:www-data:rwX -m u:deployer:rwX temp/
sudo setfacl -R -m u:www-data:rwX -m u:deployer:rwX temp/
```

Next steps run as deployer user
```bash
sudo su - deployer
```

Run composer install and yarn install
```bash
composer install
yarn install
```

Edit config/config.local.neon (if not exists, create one)
```apacheconfig
parameters:
	database:
		host: ''
		user: ''
		password: ''
		dbname: ''

	pid:
		accessToken: ''

rabbitmq:
	connections:
		default!:
			user: ''
			password: ''
			host: ''
			port: ''
```

Run prepared composer command to build assets, recreate rabbitmq queues and run migrations

```bash
composer deploy-prod
```

Create admin user
```bash
bin/console user:create "Filip" "Mistrfilda" "filda.kuchar@seznam.cz" "password"
```

# Crontab (runs as deployer user)

Generate every 3 minutes prague vehicle positions, download new stop times and stops every midnight and generate statistics. Statistics should be generated every night. 

```bash
*/2 * * * * cd /var/www/sites/kuchar-pid.cz/ && bin/console requests:generate '{"generateDepartureTables":false,"generateVehiclePositions":true, "generateTransportRestrictions": false, "generateParkingLots": false}' '{}'
5 0 * * * cd /var/www/sites/kuchar-pid.cz/ && bin/console requests:generate '{"generateDepartureTables":true,"generateVehiclePositions":false, "generateTransportRestrictions": false, "generateParkingLots": false}' '{}'
50 0 * * * cd /var/www/sites/kuchar-pid.cz/ && bin/console prague:statistic:generate 2
10 1 * * * cd /var/www/sites/kuchar-pid.cz/ && bin/console  prague:import:stop
*/30 * * * * cd /var/www/sites/kuchar-pid.cz/ && bin/console prague:requests:halfHour
```

# Supervisor for queues

```bash
sudo apt install supervisor -y
```


Departure table consumer 

```bash
sudo nano /etc/supervisor/conf.d/departure-table-consumer.conf
```

```bash
[program:departure_table_consumer]
command=/var/www/sites/kuchar-pid.cz/bin/console rabbitmq:consumer pragueDepartureTableConsumer 300
user=deployer
autostart=true
autorestart=true
startretries=10
stderr_logfile=/var/log/supervisor/departure.table.er.log
stdout_logfile=/var/log/supervisor/departure.table.out.log
```

Vehicle position consumer

```bash
sudo nano /etc/supervisor/conf.d/vehicle-position-consumer.conf
```

```bash
[program:vehicle_position_consumer]
command=/var/www/sites/kuchar-pid.cz/bin/console rabbitmq:consumer pragueVehiclePositionConsumer 300
user=deployer
autostart=true
autorestart=true
startretries=10
stderr_logfile=/var/log/supervisor/vehicle.position.table.er.log
stdout_logfile=/var/log/supervisor/vehicle.position.out.log
```

Parking lots consumer

```bash
sudo nano /etc/supervisor/conf.d/parking-lot-consumer.conf
```

```bash
[program:parking_lot_consumer]
command=/var/www/sites/kuchar-pid.cz/bin/console rabbitmq:consumer pragueParkingLotConsumer 300
user=deployer
autostart=true
autorestart=true
startretries=10
stderr_logfile=/var/log/supervisor/parking.lot.table.er.log
stdout_logfile=/var/log/supervisor/parking.lot.out.log
```

Transport restrictions consumer

```bash
sudo nano /etc/supervisor/conf.d/tranposrt-restriction-consumer.conf
```

```bash
[program:tranposrt_restriction_consumer]
command=/var/www/sites/kuchar-pid.cz/bin/console rabbitmq:consumer pragueTransportRestrictionConsumer 300
user=deployer
autostart=true
autorestart=true
startretries=10
stderr_logfile=/var/log/supervisor/transport.restriction.table.er.log
stdout_logfile=/var/log/supervisor/transport.restriction.out.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

Usefull comands
```bash
sudo supervisorctl
departure_table_consumer         RUNNING   pid 1222, uptime 0:04:19
vehicle_position_consumer        RUNNING   pid 1223, uptime 0:04:19
supervisor> start departure_table_consumer 
supervisor> stop departure_table_consumer 
supervisor> restart departure_table_consumer
```


# Deployer

Prerequisites: configured deployer user

In this project, you can find simple Deployer.php configuration (https://deployer.org/)

Setup:

```bash
sudo su - deployer
cd
git clone https://github.com/Mistrfilda/public-transport-app-deploy.git deploy
cd deploy/
composer install
```

Create folder for deployer and install acl (run as user with sudo)
```bash
sudo mkdir /var/www/deployer
sudo chown -R deployer:www-data /var/www/deployer
sudo apt install acl -y
```

After that simply run  - suggestion: on first run go to deploy.php file and comment line with run('yarn install') - first run will always fail

```bash
cd /deploy
vendor/bin/dep deploy
```

After that, it is neccesary to go to releases folder and set config/config.local.neon in shared directory

```bash
cd /var/www/deployer/public-transport-app/config
nano config.local.neon
```

Fill it with parameters specified in first section of this readme.

After that, run deploy once more

```bash
cd /deploy
vendor/bin/dep deploy
```

If everything went smooth, now its time to create symlink - directory in www/sites must be deleted if exists before creating symlink

```bash
ln -s /var/www/deployer/public-transport-app/current /var/www/sites/kuchar-pid.cz
```

And thats it! Now it is possible to simply deploy with one command!

# Stop all services
```bash
sudo systemctl stop rabbitmq-server
sudo systemctl stop mysql
sudo systemctl stop apache2
```

or to not start at all
```bash
sudo systemctl disable rabbitmq-server
sudo systemctl disable apache2
sudo systemctl disable mysql
```