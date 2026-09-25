---
layout: default
permalink: documentation/deploying_a_website/updating_formulize/
redirect_from:
 - deploying_a_website/updating_formulize/
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

   If you use ```git``` on your website, make sure web access to the ```.git``` folder is blocked. Go to ```https://www.mysite.com/.git/config``` (use your own site's address). You should see a "Not Found" page. If you don't, [block access to the .git folder](/deploying_a_website/#important-block-web-access-to-the-git-folder) now.

3. Make sure [the folders that need to be writable](../writable_folders) are writable by the web server

4. Login to your website _with a webmaster account_.

5. _If a database and configuration update is required,_ you will be redirected to the admin side where you can click a button to apply the update. __Backup your files and database before applying updates__. If you are _not_ directed automatically to the admin side then there's nothing more to do.

6. Celebrate a succesful upgrade! 🎉


