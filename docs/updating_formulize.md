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

4. Login to your website. _If a database and configuration update is required,_ you will be redirected to the admin side where you can click a button to apply the update. __Backup your files and database before applying updates__.


