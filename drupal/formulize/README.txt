$Id$

MODULE
------
formulize (Formulize integration for Drupal-6.x)


FEATURES
--------

The Formulize module integrates with the Formulize data management system and lets you create nodes in your Drupal website that are based on screens defined in Formulize. This module also ensures synchronization of the users and groups/roles between Drupal and Formulize.

Behaviours this module supports:

* Storing data about where Formulize is, how to connect to its database, etc
* Displaying data management screens from Formulize, inside Drupal
* Automatically synchronizing users with Drupal
* With the Formulize Organic Groups Synchronization module, automatically synchronizing Drupal organic groups with Formulize groups
* With the Formulize Roles Synchronization module, automatically synchronizing Drupal roles with Formulize groups


REQUIREMENTS
------------
Drupal 6.x
MySQL >= 4.1
Formulize (standalone version)


INSTALL/CONFIG
--------------
1. Download and install Formulize from http://www.freeformsolutions.ca/formulize

2. Copy the Formulize drupal module folder into your modules directory like you would any other module.

3. Enable formulize module from administer >> modules.

4. Go to 'admin/settings/formulize' and setup the full path to the "mainfile.php" file where Formulize
   is installed. ( example: /public_html/formulize/mainfile.php )

5. In the admin area, go to the Synchronize page and copy all your Drupal users into Formulize

6. Create screens in Formulize to control how you want forms and data to be displayed.  Then create Formulize nodes in Drupal that display those screens.


UNINSTALL
---------
1. Disable formulize module from administer >> modules.

2. Uninstall formulize module from administer >> modules >> uninstall.


LICENSE
-------
GNU GENERAL PUBLIC LICENSE (GPL)
