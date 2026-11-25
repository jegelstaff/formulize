---
layout: default
permalink: documentation/deploying_a_website/site_folder/
title: The site folder
---

# The site folder

Everything that belongs to your site, rather than to Formulize, is in one folder: the site folder. A copy of the site folder and a dump of the database is a copy of your whole site. The rest of the Formulize files can be replaced with a newer version, or be read-only.

Unless you say otherwise, the site folder is `sites/default`, inside the Formulize files.

## What's in it

| In the site folder | What it holds | Back it up? |
|---|---|---|
| `settings.php` | How to reach the database, including the password, and the salt that passwords depend on | Yes |
| `private/` | What people on your site wrote: custom code, screen templates, configuration, notification templates, logs, and messages waiting to be sent | Yes |
| `uploads/` | Files people upload to forms, logos and appearance files, and files people download | Yes |
| `var/` | Files Formulize makes for itself, such as compiled templates | No. Formulize makes them again if they're deleted |

**Keep `settings.php` private.** Anyone who has it can reach your database, if the database server lets them in. Never change the salt in it once the site is live, or nobody will be able to log in.

## Backing up and moving a site

1. Dump the database.
2. Copy `settings.php`, `private/` and `uploads/` from the site folder. You can leave out `var/`.

To move the site, put the copy in the site folder on the new server, and load the dump into its database. If the database's address, name or login are different there, change them in `settings.php`.

## Keeping the site folder somewhere else

You can keep the site folder outside the Formulize files, for example outside the web root, or on a volume mounted into a container. Set `FORMULIZE_SITE_PATH` to the folder, in the web server or in the environment Formulize runs in:

```apache
# Apache, in the site's configuration or the .htaccess in the Formulize folder
SetEnv FORMULIZE_SITE_PATH /var/lib/formulize/mysite
```

```yaml
# Docker Compose
environment:
  FORMULIZE_SITE_PATH: /site
```

Then serve the `uploads/` folder in it at `/uploads` on your site, and make sure the web server won't run scripts from it:

```apache
Alias /uploads /var/lib/formulize/mysite/uploads
<Directory /var/lib/formulize/mysite/uploads>
	AllowOverride All
	Require all granted
</Directory>
```

The `.htaccess` in the uploads folder stops scripts from running there. With nginx, which doesn't read `.htaccess` files:

```nginx
location /uploads/ {
	alias /var/lib/formulize/mysite/uploads/;
	location ~* \.(php|phtml|phar|cgi|pl|py|sh)$ { deny all; }
}
```

Set `FORMULIZE_SITE_PATH` before you run the installer, so it puts the site's settings in that folder.

## Settings from the environment

Each value in `settings.php` can come from an environment variable instead, which takes the place of what's written in the file. This is the usual way to give a container its settings:

| Variable | Takes the place of |
|---|---|
| `FORMULIZE_DB_HOST` | The database server's address |
| `FORMULIZE_DB_NAME` | The database's name |
| `FORMULIZE_DB_USER`, `FORMULIZE_DB_PASS` | The database login |
| `FORMULIZE_DB_PREFIX` | The prefix of the site's tables |
| `FORMULIZE_DB_SALT` | The salt. Set it to the value the site was installed with, and never change it |
| `FORMULIZE_URL` | The site's address, such as `https://forms.example.com`, when Formulize can't work it out from the request |
| `FORMULIZE_TRUST_PROXY_HEADERS` | Set it to `1` when the site is behind a proxy that changes the address, such as a load balancer, so Formulize uses the X-Forwarded-Host and X-Forwarded-Port headers the proxy adds. Only do this when the site can't be reached except through the proxy |

## Checking that private files are private

When the site folder is inside the web root, the web server must never send `settings.php`, `private/` or `var/`. Formulize includes `.htaccess` files that refuse them, which Apache uses as long as `AllowOverride` is on.

To check, visit `https://your.site/sites/default/settings.php`. You should see a "Forbidden" error. If you see anything else, or a blank page, your web server isn't using the `.htaccess` files: keep the site folder outside the web root, or refuse the folder in the web server's configuration. With nginx:

```nginx
location ^~ /sites/ {
	location ~ ^/sites/[^/]+/uploads/ {
		location ~* \.(php|phtml|phar|cgi|pl|py|sh)$ { deny all; }
	}
	location ~ ^/sites/ { deny all; }
}
```

## Sites installed before the site folder

A site installed with an earlier version of Formulize keeps its settings in `mainfile.php`, and its other files in folders throughout the Formulize files. It keeps working as it did. Keep your site's own `mainfile.php` when you update the Formulize files.
