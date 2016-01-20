#!/bin/bash
if [ `lsb_release -c -s` = "wheezy" ]; then
	echo "System Detected: Debian Wheezy"
	# Will update /etc/apt/sources.list for dotdeb repo
	echo 'deb http://packages.dotdeb.org wheezy all' >> /etc/apt/sources.list
	echo 'deb http://packages.dotdeb.org wheezy-php56-zts all' >> /etc/apt/sources.list
	wget -qO - https://www.dotdeb.org/dotdeb.gpg | apt-key add -
	apt-get update
	# Removing Apache2
	apt-get remove apache2 apache2-doc apache2-mpm-prefork apache2-utils apache2.2-bin apache2.2-common
	# Installing nginx and php-fpm
fi

if [ `lsb_release -c -s` = "jessie" ]; then
        echo "System Detected: Debian Jessie"
	# Will update /etc/apt/sources.list for dotdeb repo
	echo 'deb http://packages.dotdeb.org jessie all' >> /etc/apt/sources.list
	wget -qO - https://www.dotdeb.org/dotdeb.gpg | apt-key add -
	apt-get update
	# Removing Apache2
	apt-get remove apache2 apache2-doc apache2-mpm-prefork apache2-utils apache2.2-bin apache2.2-common
	# Installing nginx and php-fpm
fi

