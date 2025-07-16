---
layout: default
permalink: installing_formulize/
title: Using the Formulize Installer
---

1. If you are installing Formulize for the first time, make sure you do include the /install/ folder when you deploy the files to the website. You can exclude all [the other files and folders listed above](#deploying-formulize).
2. <a name="writable-folders"></a>
3. Create a database on your server, note the name, and the username and password used to access the database.
4. Create a "trust" folder on the website, preferrably outside the web root (but some server configurations can't read a folder outside the web root)
5. In a web browser, browse to the root folder where you placed the files. The Formulize installer will appear and you can follow the steps to setup Formulize.
6. When prompted, type in the path to the "trust" folder. You will need to have already created this folder manually yourself.
7. When prompted for the database information, fill that in based on the database you created in step 3.
8. When prompted, create the username and password for the initial webmaster user.
9. When at the "install modules" step, select all the modules including Formulize.
10. When you reach the last page, click on the Home icon in the lower right to go to the homepage of your website and login with the username and password for the webmaster user, that you created in step 8.
