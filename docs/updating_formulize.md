---
layout: default
permalink: deploying_a_website/updating_formulize/
title: Updating Formulize
---

# Updating Formulize

1. Backup your files and database.

2. Deploy the new files to your website. If you cloned the GitHub repository when installing Formulize, then you can do this:
	```bash
	# stash any local changes in your site that aren't committed yet
	git stash

	# fetch the release you want to update to, ie: 8.02
	git fetch origin v8.02

	# update the code in your site with that release
	git rebase v8.02

	# restore your local changes
	git stash pop
	```

3. Make sure [the folders that need to be writable](../writable_folders) are writable by the web server

4. Login to your website and go to the admin side, click on the _Modules_ menu heading. Do not click on any of the menu entries under Modules, __click on the heading itself__.

	![Click on the Modules heading](../../images/modules-heading.png)

5. In the list of installed modules, click the __circular arrows__ on the row where Formulize is listed:

	![Click the circular arrows to update Formulize](../../images/update-arrows.png)

6. On the next page that appears, click the __Update__ button at the bottom of the page.

	![Click the Update button](../../images/update-button.png)

7. Go to the main admin page for Formulize. This is accessible from the Modules menu.

	![Go to the Formulize main admin page](../../images/formulize-link.png)

8. _If a database update is required_, there will be a large message about this on the screen. _If no update is required, then no message will appear_.

	If there's a message, click the __Apply Database Patch for Formulize__ button to update the database.
	![Update the database](../../images/formulize-database-update.PNG)


