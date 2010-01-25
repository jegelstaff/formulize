$Id$

MODULE
------
formulize (Formulize integration for Drupal-6.x)


FEATURES
--------

The Formulize module integrates with Formulize and lets you create nodes in your Drupal website that are based on screens defined in Formulize. This module also ensures synchronization of the users and groups/roles between Drupal and Formulize.

Behaviours this module supports:

* Storing data about where Formulize is, how to connect to its database, etc
* Creating new users in Formulize when new users are created in Drupal
* Updating users in Formulize when users are updated in Drupal (ie: name change, etc)
* Deleting users in Formulize when users are deleted in Drupal
* Creating new groups in Formulize when roles are created in Drupal
* Updating groups in Formulize when roles are updated in Drupal
* Deleting groups in Formulize when roles are deleted in Drupal
* Creating new groups in Formulize when groups are created in Organic Groups, including groups for each role created within a group
* Updating groups in Formulize when groups are updated in Organic Groups, including groups for each role created within a group
* Deleting groups in Formulize when groups are deleted in Organic Groups, including groups for each role created within a group.

REQUIREMENTS
------------
Drupal 6.x
MySQL >= 4.1
XOOPS
Formulize

INSTALL/CONFIG
--------------
1. Download and install XOOPS from

2. Download and install Formulize from 

2. Move this folder into your modules directory like you would any other module.

3. Set your $db_url in /path/to/drupal/sites/default/settings.php like this:
   $db_url['default']   = mysql://user:pass@host/dbname
   $db_url['formulize'] = mysql://user:pass@host/dbname

4. Enable formulize module from administer >> modules.

5. Go to 'admin/settings/formulize' and setup the full path where Formulize
   is installed. ( example: /public_html/xoops/mainfile.php )


6. The formulize module is available now.



UNINSTALL
---------
1. Disable formulize module from administer >> modules.

2. Uninstall formulize module from administer >> modules >> uninstall.


LICENSE
-------
GNU GENERAL PUBLIC LICENSE (GPL)
