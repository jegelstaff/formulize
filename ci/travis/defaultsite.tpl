<VirtualHost *:80>
	ServerName local.dev
	DocumentRoot /var/www
	ErrorLog /tmp/error.log
<Directory "/var/www">
Options +Includes
Options +FollowSymLinks -Indexes
</Directory>
</VirtualHost>