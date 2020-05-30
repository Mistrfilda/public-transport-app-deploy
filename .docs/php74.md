# Raspbian PHP 7.4 setup

Upgrading from php 7.3


Add new sources for apt and php 

```bash
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ buster main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
```


Disable old php 7.3 in apache

```bash
sudo a2dismod php7.3
```

Install php 7.4

```bash
sudo apt install php7.4 php7.4-common php7.4-mysql php7.4-cli
```

Enable php 7.4 in apache (it should be done automaticaly during apt install)

```bash
sudo apt install libapache2-mod-php7.4
sudo a2enmod php7.4
```