---
layout: default
permalink: install/
---

# Installing Formulize

1. If upgrading, backup your files and database!
2. Download Formulize from the GitHub releases: https://github.com/jegelstaff/formulize/releases
3. Copy the files to your web root (you do not need to include the ci or docs folders)
4. Make sure the following folders are writable by the server:
   - /cache
   - /templates_c
   - /uploads
   - /modules/formulize/cache
   - /modules/formulize/custom_code
   - /modules/formuilze/export
   - /modules/formulize/temp
   - /modules/formulize/templates/screens (and all subs)
   - /modules/formulize/upload
5. Open a web browser and go to your site.
   - If you're installing fresh, go to your webroot and the installer will appear.
   - If you're upgrading, login as the webmaster, and go to the admin page for Formulize. You will be prompted to update the database.
