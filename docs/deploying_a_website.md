---
layout: default
permalink: documentation/deploying_a_website/
title: Deploying Formulize to a Website
redirect_from:
 - deploying_a_website/
 - developers/deploying_formulize/
 - developers/building/
 - install/
---

# Deploying Formulize to a Website

__If you're trying to update an existing Formulize system__, check out the [Updating Formulize page](../deploying_a_website/updating_formulize).

## What you'll need

- a web server with PHP and MariaDB (or MySQL) installed and ready to go
- a database created in MariaDB (or MySQL)
- a database user created, with full rights to the database in MariaDB (or MySQL)

## Quick Start

1. __Get the Formulize files onto your web server.__ We recommend using ```git```. See below, and __make sure you block web access to the ```.git``` folder__. You could also [download the latest release from GitHub](https://github.com/jegelstaff/formulize/zipball/{{ site.github.latest_release.tag_name }}), and transfer the files to your web server however you see fit.

2. __Put the files in the folder where you want Formulize to exist__, ie:
- if you want Formulize to exist at ```https://www.mysite.com``` then put the files in the web root.
- if you want Formulize to be in a subfolder like ```https://www.mysite.com/formulize``` then put the files in the ```/formulize``` folder, inside the web root.

3. __Make sure [the folders that need to be writable](../deploying_a_website/writable_folders) are writable by the web server user.__

4. __Open a web browser and go to the location where you put Formulize.__ The installer will appear. [Follow the steps for using the installer](../deploying_a_website/installing_formulize)

## Getting the files onto your server with git

We recommend cloning the GitHub repository so that it's easy to track any changes, and easy to update Formulize in the future.

```bash
# ssh to your server
# then go to the folder where you want to install Formulize
cd /path/where/you/want/formulize

# make sure the folder is empty
# delete any contents if necessary

# clone Formulize from GitHub into this folder
# don't forget the dot at the end!
git clone {{ site.github.repository_url }}.git .

# make your own branch to track changes
git checkout -b my-formulize-branch
```

### Important: block web access to .git and other hidden files

__Do this before you run the installer, and before your site is public.__

Cloning puts a ```.git``` folder in the root of your website. It holds a complete copy of your site's files and their history, including anything you commit to your branch later. If it can be reached through a web browser, anyone can download it. Other files and folders with names that start with a dot, such as ```.github```, don't belong on the web either, and the ```tests``` folder is only for development, so block those too.

On an Apache server, add these lines to the ```.htaccess``` file in the root of your website, above any other rewrite rules. Create the file if it isn't there. If it already has ```RewriteEngine On```, you don't need to add that line again.

```
RewriteEngine On
RewriteRule ^tests(/|$) - [R=404,L]
RedirectMatch 404 /\.(?!well-known)
```

On an nginx server, add this inside the ```server``` block for your site, then reload nginx. If your website is in a subfolder of its domain, add the subfolder to the second rule, such as ```^/system/tests(/|$)```.

```
location ~ /\.(?!well-known) {
    return 404;
}

location ~ ^/tests(/|$) {
    return 404;
}
```

Both leave the ```.well-known``` folder open, because services that issue security certificates need to reach it.

To check that it worked, go to ```https://www.mysite.com/.git/config``` and ```https://www.mysite.com/tests/README.md``` in a web browser (use your own site's address). You should see a "Not Found" page for both. If the browser shows or downloads a file instead, those files are still accessible, so check the settings above before going any further.

### Important: make sure the uploads folder can't run files

Files that people upload to your forms are stored in the ```uploads``` folder. Formulize already does two things to stop those files being run as programs: it refuses uploads of executable file types, and it ships an ```.htaccess``` file that blocks execution on Apache. Some servers, like nginx, ignore ```.htaccess```, so you should confirm that your server will not run files from the ```uploads``` folder, and set it up to prevent this if it does.

See [making sure the uploads folder can't run files](../deploying_a_website/uploads_not_executable) for how to check it and how to fix it on Apache and nginx.

### Important: block web access to the code folder

Formulize keeps what you write in the admin pages in the ```modules/formulize/code``` folder: form procedures, derived value formulas, the content of Text for display elements, and the text of a form's first page and Thank You page. Formulize reads these files itself, and they must not be reachable from a web browser, where they could show content meant only for some people, or run on their own.

On Apache, the ```.htaccess``` file that comes with Formulize in that folder blocks it, as long as your server lets ```.htaccess``` files take effect. On nginx, add this inside the ```server``` block for your site, then reload nginx. If your website is in a subfolder of its domain, add the subfolder to the start of the rule, such as ```^/system/modules/formulize/code/```.

```
location ~ ^/modules/formulize/code/ {
    deny all;
}
```

To check that it worked, go to ```https://www.mysite.com/modules/formulize/code/index.html``` in a web browser (use your own site's address). You should see a "Forbidden" page. If the browser goes back to the page you were on instead, the folder can still be reached, so check the settings above.

### Next steps

Regardless of how you get the files on your server, the next steps are the same:

- Make sure [the folders that need to be writable](../deploying_a_website/writable_folders) are writable by the web server user.

- Open a web browser and go to the location where you put Formulize. The installer will appear. [Follow the steps for using the installer](../deploying_a_website/installing_formulize)

## If you're using git, commit changes regularly to your branch

As you use Formulize, some configuration changes are stored in files on the server. These files will show up in ```git``` as changes.

Periodically, you should review the changes, and commit them to your branch. It's generally a good idea to automate this to run nightly, using ```cron``` and a bash script. That way, you have a regular history of changes.

For details of how to update your Formulize site using ```git```, check out the [Updating Formulize](../deploying_a_website/updating_formulize) page.

## If you're not using git, consider deleting some folders and files

Some of the folders and files are not necessary for the actual operation of Formulize on a live website.

If you are using ```git``` to manage the files, it's best __not__ to remove these, although you could. It would create merge challenges later if you do remove them.

However, if you're __not__ using ```git```, then these folders are basically taking up extra space and serving no purpose, so you might as well delete them:

* /.github/
* /.sauce/
* /.vscode/
* /docker/
* /docs/
* /install/ (only delete __after__ completing the installation!)
* /tests/
* /trust/ (do __not__ delete if you used this as your actual trust path!)
* .editorconfig
* .gitignore
* docker-compose.yml


