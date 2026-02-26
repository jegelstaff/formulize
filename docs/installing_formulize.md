---
layout: default
permalink: deploying_a_website/installing_formulize/
title: Using the Formulize Installer
---

# Using the Formulize Installer

If you're setting up a Formulize site for the first time, you will need to follow some steps and use the installer.

1. Create a database on your server, note the name, and the username and password used to access the database. Make sure the database user has full rights to the database, including the ability to ALTER tables, etc.

2. Create a "trust" folder on the website, preferrably outside the web root (but some server configurations can't read a folder outside the web root). This folder will be used by Formulize to store the database name, and the username and password for accessing the database.

3. Get the Formulize files onto your server and into the webroot folder. Make sure the [folders that need to be writable](../writable_folders) by the server, are writable. Also, make sure that ```mainfile.php``` in the root of the website, is writable by the server.

4. In a web browser, browse to the folder where you placed the Formulize files. ie: ```https://mysite.com``` if you put the Formulize files in the root of your website, or ```https://mysite.com/formulize/``` if you put the Formulize files in a subfolder named _formulize_, for example.

	The Formulize installer will appear. Click the arrow in the bottom right to continue.

	![Installer Page 1](../../images/installer1.png)

5. The installer will check if your server meets the requirements for Formulize. If it does, click the arrow in the bottom right to continue.

	![Installer Page 2](../../images/installer2.png)

6. The installer will automatically fill in the URL and physical path for you. You need to specify the _trust path_ yourself. This should be the full path to the folder you created in step 2 above. Ideally, this folder should be outside the web root.

	Once you've put in the correct _trust path_, click the arrow in the bottom right to continue.

	![Installer Page 3](../../images/installer3.png)

7. Next, the installer will ask for your database details. Enter the username and password for your database, that you created in step 1 above. Also, if the database is not on the same server with the web server, then change _localhost_ to whatever it should be.

	__If you are installing in Docker, use _mariadb_ as the _Server hostname_, NOT _localhost_!__

	Once you are done, click the arrow in the bottom right to continue.

	![Installer Page 4](../../images/installer4.png)

8. On the next page, enter the name of the database you created in step 1 above. Leave everything else as is. Once you are done, click the arrow in the bottom right to continue.

	![Installer Page 5](../../images/installer5.png)

9. On the next page, the installer shows the system configuration details it is about to save in ```mainfile.php``` and in the _trust path_. Click the arrow in the bottom right to continue.

	![Installer Page 6](../../images/installer6.png)

10. The installer will create a series of tables in the database. Click the arrow in the bottom right to continue.

	![Installer Page 7](../../images/installer7.png)

11. The installer shows the tables it created. Click the arrow in the bottom right to continue.

	![Installer Page 8](../../images/installer8.png)

12. You can now specify the details for the initial webmaster user of your Formulize system. Once you have done this, click the arrow in the bottom right to continue.

	![Installer Page 9](../../images/installer9.png)

13. The installer will next add the initial configuration data, including your webmaster user, into the database. Click the arrow in the bottom right to continue.

	![Installer Page 10](../../images/installer10.png)

14. The installer shows what data it saved in the database. Click the arrow in the bottom right to continue.

	![Installer Page 11](../../images/installer11.png)

15. Lastly, the installer enables "modules" in the system, including Formulize. __DO NOT__ change the selected modules. Just click the arrow in the bottom right to continue.

	![Installer Page 12](../../images/installer12.png)

16. The results page after enabling the modules, is very long. Scroll to the bottom, and click the arrow in the bottom right to continue.

	![Installer Page 13](../../images/installer13.png)

17. On the last page of the installer, click the house icon in the lower right to go to the front page of your new Formulize system!

	![Installer Page 14](../../images/installer14.png)

18. Welcome to Formulize! Enter the webmaster username and password you created in step 12, and start creating forms and applications!

	![Installer Page 15](../../images/installer15.png)

19. Now that everything is installed, set 400 permission (read-only, owner-only) on the ```mainfile.php``` and the "trust" folder and its contents. If for whatever reason the web server user is not the owner of those files, make the web server the owner of the files.
