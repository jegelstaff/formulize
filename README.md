Formulize
=========

Data management and reporting on the web, in your CMS, and on your mobile device.

Docs and other materials (incl. video tutorials) here: http://www.formulize.org

To get the latest, just download the source code of the latest release, or the latest commit if you want the cutting edge, which is almost always fully functional.

To install fresh:
1) upload files to your web server
2) create MySQL/mariaDB database and username/pw (optional, installer might be able to do it for you depending on permissions on your server)
3) go to the root of the website in your browser and follow the installation prompts

To update existing install:
1) backup files and DB, 
2) overwrite the files with these ones (except for mainfile.php and the /install folder), 
3) login and go to the admin side, run the database update when prompted (or go to .../modules/formulize/admin/ui.php?op=patchDB)
4) click on the Modules menu heading at the top of the page on the admin side and then click the circle arrows to update the Formulize "module"

If you have issues, please contact info@formulize.org

Community Summit happening in Toronto in October? Get vaccinated. Details to follow.

[![Build Status](https://travis-ci.org/jegelstaff/formulize.png)](https://travis-ci.org/jegelstaff/formulize)
