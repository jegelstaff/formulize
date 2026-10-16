---
layout: default
permalink: documentation/deploying_a_website/uploads_not_executable/
redirect_from:
 - deploying_a_website/uploads_not_executable/
title: Making sure the uploads folder can't run files
---

# Making sure the uploads folder can't run files

Files that people upload to your forms are stored in the ```uploads``` folder in the root of your website. That folder must never run programs. If it can, someone who is allowed to upload a file could upload a program and then run it on your server.

## What Formulize does for you

Formulize includes two precautions against this out of the box:

- __It refuses uploads of executable file types.__ File upload elements never accept files like ```.php```, ```.cgi```, ```.pl```, ```.py``` or ```.sh```, no matter what file types an individual element is set to allow.

- __It ships an ```.htaccess``` file__ in the ```uploads``` folder that tells Apache not to run anything in that folder.

These cover most setups, but they cannot cover every server, so it is worth confirming the folder is safe on your server.

## Formulize will check this

You do not have to remember to do this on your own. The installer warns you if the ```uploads``` folder can run programs, and every Formulize update checks again, so you will be told if anything changes on your server later. The rest of this page is there if you want to check and fix it yourself, or if you are on a server Formulize's built-in protection does not cover.

## When you still need to check

- On __nginx__ (and some other servers), ```.htaccess``` files are ignored completely, so the shipped file does nothing and you must add a rule yourself.
- On __Apache__, ```.htaccess``` files only take effect when the server is set to allow them. If ```AllowOverride``` is turned off for your website, the shipped file is ignored.

## How to check

Put a file called ```test.php``` in the ```uploads``` folder, containing this one line:

```
<?php echo 'uploads can run php';
```

Then go to ```https://www.mysite.com/uploads/test.php``` in a web browser, using your own site's address.

- If you see a "Forbidden" or "Not Found" page, or the browser downloads the file instead of running it, the folder is __safe__.
- If you see the words __uploads can run php__, the folder is __not safe__, and you should fix it using the steps below before your site is used.

Delete ```test.php``` when you are done.

## How to fix it

### On Apache

Make sure ```.htaccess``` files are allowed to take effect for your website, so the file Formulize ships can do its job. In the Apache configuration for your site, set ```AllowOverride``` to ```FileInfo Limit``` (or ```All```) for the website's directory, then reload Apache. This is the default on most hosts.

If the ```.htaccess``` file is missing from the ```uploads``` folder, you can recreate it. Its contents are below.

### On nginx

```.htaccess``` is ignored, so add this inside the ```server``` block for your site, then reload nginx:

```
location ^~ /uploads/ {
    location ~ \.(php|php3|php4|php5|php7|php8|phps|phtml|pht|phar|cgi|pl|py|jsp|asp|aspx|sh)$ {
        return 403;
    }
}
```

Run the check again afterwards to confirm the folder is now safe.

## The uploads/.htaccess file

This is the file Formulize ships in the ```uploads``` folder. If it has gone missing, recreate it with exactly this content:

```
# Uploaded files are user content and must never be executed as server-side scripts.
# The primary control below is an access denial (works on any PHP SAPI: mod_php, CGI, FastCGI, PHP-FPM).
# The php_flag lines are wrapped in <IfModule> guards on purpose: php_flag directives are only valid when
# PHP runs as an Apache module (mod_php); on FastCGI/FPM/CGI the guards make them a no-op. Use php_flag,
# NOT php_admin_flag: the php_admin_* forms are rejected in .htaccess context and raise a fatal parse error
# that makes the WHOLE folder return 500 (every file, not just scripts). Do not un-guard these lines.

# 1) Deny direct web access to server-side script types (matches double extensions too, e.g. x.php.jpg).
<FilesMatch "(?i)\.(php|php3|php4|php5|php7|php8|phps|phtml|pht|phar|cgi|fcgi|pl|py|pyw|wsgi|rb|erb|rhtml|lua|sh|jsp|jspx|asp|aspx|ashx|asmx|ascx|asax|cshtml|vbhtml|config|cfm|cfc|cfml|shtml|shtm|stm|htaccess|htpasswd)($|\.)">
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order allow,deny
		Deny from all
	</IfModule>
</FilesMatch>

# 2) Belt-and-suspenders: if PHP is running as mod_php, turn its engine off for this directory.
<IfModule mod_php.c>
	php_flag engine off
</IfModule>
<IfModule mod_php7.c>
	php_flag engine off
</IfModule>
<IfModule mod_php8.c>
	php_flag engine off
</IfModule>

# 3) Strip any handler/type mapping that could route these extensions to an interpreter.
<IfModule mod_mime.c>
	RemoveHandler .php .php3 .php4 .php5 .php7 .php8 .phps .phtml .pht .phar .cgi .fcgi .pl .py .pyw .wsgi .rb .erb .rhtml .lua .sh .jsp .jspx .asp .aspx .ashx .asmx .ascx .asax .cshtml .vbhtml .cfm .cfc .cfml .shtml .shtm .stm
	RemoveType .php .php3 .php4 .php5 .php7 .php8 .phps .phtml .pht .phar .cgi .fcgi .pl .py .pyw .wsgi .rb .erb .rhtml .lua .sh .jsp .jspx .asp .aspx .ashx .asmx .ascx .asax .cshtml .vbhtml .cfm .cfc .cfml .shtml .shtm .stm
</IfModule>
```
