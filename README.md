Formulize
=========

* Data entry, collection, management, workflows and reporting.
* On the web, in your CMS, and on your mobile device.
* Docs and other materials (incl. video tutorials) here: https://www.formulize.org
* To setup a local dev environment, follow the steps here: https://www.formulize.org/developers/development_environment/

To get the latest, just download the source code of the latest release, or the latest commit if you want the cutting edge, which is almost always fully functional.

To install fresh:
1) upload files to your web server
2) create MySQL/mariaDB database and username/pw (optional, installer might be able to do it for you depending on permissions on your server)
3) go to the root of the website in your browser and follow the installation prompts

To update existing install:
1) backup files and DB,
2) overwrite the files with these ones (except for mainfile.php and the /install folder),
3) login and go to the admin side, click on the Modules menu heading at the top of the page, and then click the circle arrows to update the Formulize "module",
4) go to the main Formulize admin page (Modules -> Formulize) and run the database update if prompted (or go to .../modules/formulize/admin/ui.php?op=patchDB)

If you have issues, please contact info@formulize.org
