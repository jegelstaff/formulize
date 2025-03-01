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
   - /logs
   - /templates_c
   - /uploads
   - /modules/formulize/cache
   - /modules/formulize/code
   - /modules/formuilze/export
   - /modules/formuilze/queue
   - /modules/formulize/temp
   - /modules/formulize/templates/screens (and all subs)
   - /modules/formulize/upload
5. Open a web browser and go to your site.
   - _If you're installing fresh_, go to your webroot and the installer will appear.
   - _If you're upgrading_, login as the webmaster, and...
       - go to the admin page for Formulize. You will be prompted to update the database. 
       - click on the 'Modules' heading in the menu across the top of the admin page (the heading, not the Formulize link in the menu). The module list will appear. Click the circular arrows on the right side of the Formulize row. This will prompt you to update Formulize, which handles changes in templates and configuration settings.
