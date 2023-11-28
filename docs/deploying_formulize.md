---
layout: default
permalink: developers/deploying_formulize/
---

# <a name="deploying-formulize"></a>Deploying Formulize

The Formulize code repository contains all the files and folders that are necessary for the operation of a [local development environment using Docker](../development_environment/).

Some of the folders and files are not necessary for the operation of Formulize on a live website. If you are deploying Formulize to a website, the unncessary files and folders are:

* /.git/
* /.vscode/
* /ci/
* /docker/
* /docs/
* /install/ (*Only necessary* if you are installing Formulize for the first time. *Not necessary* for site migrations or updates.)
* /trust/
* .editorconfig
* .gitignore
* .travis.yml
* .docker-compose.yml
* README.md
* SECURITY.md


# <a name="installing-formulize"></a>Installing Formulize

1. If you are installing Formulize for the first time, make sure you do include the /install/ folder when you deploy the files to the website. You can exclude all [the other files and folders listed above](#deploying-formulize).
2. Create a database on your server, note the name, and the username and password used to access the database.
3. Create a "trust" folder on the website, preferrably outside the web root (but some server configurations can't read a folder outside the web root)
4. In a web browser, browse to the root folder where you placed the files. The Formulize installer will appear and you can follow the steps to setup Formulize.
5. When prompted, type in the path to the "trust" folder. You will need to have already created this folder manually yourself.
6. When prompted for the database information, fill that in based on the database you created in step 2.
7. When prompted, create the username and password for the initial webmaster user.
8. When at the "install modules" step, select all the modules including Formulize.
9. When you reach the last page, click on the Home icon in the lower right to go to the homepage of your website and login with the username and password for the webmaster user, that you created in step 7.

# <a name="updating-formulize"></a>Updating Formulize

1. Backup your files and database.
2. Deploy the new files to your website. You can exclude all [the files and folders listed above](#deploying-formulize).
3. Login to your website and go to the admin side, click on the Modules menu heading. Do not click on any of the menu entries under Modules, click on the heading itself.
4. In the list of installed modules, click the circular arrows on the row where Formulize is listed:

	![Click the circular arrows to update Formulize](../../images/formulize-update.PNG)
5. On the next page that appears, click the Update button to update Formulize configuration settings.
6. Go to the main admin page for Formulize. This is accessible from the Modules menu, by selecting 'Forms'

	![Go to the Formulize main admin page](../../images/menu-forms.PNG)
7. If a database update is required, there will be a large message about this in the upper right of the screen. If no update is required, then no message will appear. Click the "Apply Database Patch for Formulize" button to update the database.
	![Update the database](../../images/formulize-database-update.PNG)


