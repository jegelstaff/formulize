---
layout: default
permalink: updating_formulize/
title: Updating Formulize
---

# Updating Formulize

1. Backup your files and database.

2. Deploy the new files to your website. If you're using ```git```, you can do this:
```bash
# stash local changes in your site
git stash

# switch to a release from GitHub
git checkout v8.02

# update your site with the latest changes


3. Make sure [the folders that need to be writable](../writable_folders) are writable by the web server

4. Login to your website and go to the admin side, click on the Modules menu heading. Do not click on any of the menu entries under Modules, click on the heading itself.

5. In the list of installed modules, click the circular arrows on the row where Formulize is listed:

	![Click the circular arrows to update Formulize](../../images/formulize-update.PNG)

6. On the next page that appears, click the Update button to update Formulize configuration settings.

7. Go to the main admin page for Formulize. This is accessible from the Modules menu, by selecting 'Forms'

	![Go to the Formulize main admin page](../../images/menu-forms.PNG)

8. If a database update is required, there will be a large message about this in the upper right of the screen. If no update is required, then no message will appear. Click the "Apply Database Patch for Formulize" button to update the database.
	![Update the database](../../images/formulize-database-update.PNG)

	### updating with git

	stash
	pull --rebase
	stash pop

