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

   If you use ```git``` on your website, make sure web access to hidden files is blocked. Go to ```https://www.mysite.com/.git/config``` and ```https://www.mysite.com/.github/workflows/e2e-test.yml``` (use your own site's address). You should see a "Not Found" page for both. If either one is accessible, see [blocking web access to .git and other hidden files](/documentation/deploying_a_website/#important-block-web-access-to-git-and-other-hidden-files).

3. Make sure [the folders that need to be writable](../writable_folders) are writable by the web server

4. Login to your website _with a webmaster account_.

5. _If a database and configuration update is required,_ you will be redirected to the admin side where you can click a button to apply the update. __Backup your files and database before applying updates__. If you are _not_ directed automatically to the admin side then there's nothing more to do. If your site uses the Anari theme, the update asks you about switching to Lyris before it runs. See [Switching your site to Lyris](/documentation/switching_to_lyris/).

6. Celebrate a succesful upgrade! 🎉


