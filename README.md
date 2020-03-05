# Deploy for public transport app

Deploy is divided into second project, since my Raspberry PI runs only in my network :)

# Raspberry pi instalation

Run on localhost

```bash
sudo apt-get update
sudo apt-get upgrade
```

Apache instalation
```bash
sudo apt install apache2 -y
sudo apt install php libapache2-mod-php -y
```

Setup virtualhost

```bash
cd /etc/apache2/site-available
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
sudo a2dissite mysite-pid.conf
sudo systemctl restart apache2
```

PHP config
```bash
sudo apt-get install php-mysql
sudo nano /etc/php/7.3/php.ini
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
```

Create project folder and change ownership
```bash
sudo mkdir /var/www/sites/kuchar-pid.cz
sudo chown -R deployer:www-data /var/www/sites
```

Install composer + yarn
```bash
sudo apt install composer
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
sudo apt update && sudo apt install yarn
```

Install rabbitmq and enable full admin access (insecure but since our PI is only in local network :D)
```bash
sudo apt install rabbitmq-server
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

## Crontab

Generate every 3 minutes prague vehicle positions and download new stop times every midnight

```bash
*/3 5-22 * * * cd /var/www/sites/kuchar-pid.cz/ && bin/console requests:generate '{"generateDepartureTables":false,"generateVehiclePositions":true}' '{}'
5 0 * * * cd /var/www/sites/kuchar-pid.cz/ && bin/console requests:generate '{"generateDepartureTables":true,"generateVehiclePositions":false}' '{}'
```

## Supervisor for queues

```bash
sudo apt install supervisor
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
stderr_logfile=/var/log/supervisor/vehicle.position.table.er.log
stdout_logfile=/var/log/supervisor/vehicle.position.out.log
```