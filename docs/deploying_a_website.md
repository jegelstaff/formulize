---
layout: default
permalink: deploying_a_website/
title: Deploying Formulize to a Website
redirect_from:
 - developers/deploying_formulize/
 - developers/building/
 - install/
---

# Deploying Formulize to a Website

__If you're trying to update an existing Formulize system__, check out the [Updating Formulize page](../updating_formulize).

The basic process for installing Formulize is pretty straight forward:

1. Get the Formulize files onto your web server

2. Put them in the folder where you want Formulize to exist, ie:
- if you want Formulize to exist at ```https://www.mysite.com``` then put the files in the root of that website
- if you want Formulize to be in a subfolder like ```https://www.mysite.com/formulize``` then put the files in the ```/formulize``` folder inside the root of the website.

3. Make sure [the folders that need to be writable](../writable_folders) are writable by the web server user.

4. Open a web browser and go to the location where you put Formulize. The installer will appear. [Follow the steps for using the installer](../installing_formulize)

## Getting the files onto the web server

The old fashioned way would be FTP, or who knows what. Anything will work, you just need to have the files on the server.

_However_, we recommend that you use ```git``` to clone the GitHub repository so that it's easy to update Formulize with future changes.

If you use ```git```, it's also easy to track the changes you make to your website. If you customize the templates for any screens, or add any custom code, or make any changes to Formulize code itself, it's easy to manage all that through ```git```.

### Getting the files on your server with git

```bash
# ssh to your server
cd /path/where/you/want/formulize

# make sure the folder is empty
# delete any contents if necessary

# clone Formulize from GitHub into this folder
# don't forget the dot at the end!
git clone {{ site.github.repository_url }}.git .

# make your own branch to track changes
git checkout -b my-formulize-branch
```

Regardless of how you get the files on your server, the next steps are the same:

- Make sure [the folders that need to be writable](../writable_folders) are writable by the web server user.

- Open a web browser and go to the location where you put Formulize. The installer will appear. [Follow the steps for using the installer](../installing_formulize)

### If you're using git, commit changes regularly to your branch

As you use Formulize, some configuration changes are stored in files on the server. These files will show up in ```git``` as changes.

Periodically, you should review the changes, and commit them to your branch. It's generally a good idea to automate this to run nightly, using ```cron``` and a bash script. That way, you have a regular history of changes.

For details of how to update your Formulize site using ```git```, check out the [Updating Formulize](../updating_formulize) page.

### If you're not using git, consider deleting some folders and files

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


