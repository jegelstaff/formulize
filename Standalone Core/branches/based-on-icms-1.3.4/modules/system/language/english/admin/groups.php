<?php
// $Id: groups.php 10326 2010-07-11 18:54:25Z malanciault $
//%%%%%%	Admin Module Name  AdminGroup 	%%%%%
if (!defined('_AM_DBUPDATED')) {define("_AM_DBUPDATED","Database Updated Successfully!");}

define("_AM_EDITADG","Edit Groups");
define("_AM_MODIFY","Modify");
define("_AM_DELETE","Delete");
define("_AM_CREATENEWADG","Create New Group");
define("_AM_NAME","Name");
define("_AM_DESCRIPTION","Description");
define("_AM_INDICATES","* indicates required fields");
define("_AM_SYSTEMRIGHTS","Can administrate the following System features");
define("_AM_ACTIVERIGHTS","Can administrate the following modules");
define("_AM_IFADMIN","If admin right for a module is checked, access right for the module will always be enabled.");
define("_AM_ACCESSRIGHTS","Can access the following modules");
define("_AM_UPDATEADG","Update Group");
define("_AM_MODIFYADG","Modify Group");
define("_AM_DELETEADG","Delete Group");
define("_AM_AREUSUREDEL","Are you sure you want to delete this group?");
define("_AM_YES","Yes");
define("_AM_NO","No");
define("_AM_EDITMEMBER","Edit Members of this Group");
define("_AM_MEMBERS","Members");
define("_AM_NONMEMBERS","Non-members");
define("_AM_ADDBUTTON"," add --> ");
define("_AM_DELBUTTON","<--delete");
define("_AM_UNEED2ENTER","You need to enter required info!");

// Added in RC3
define("_AM_BLOCKRIGHTS","Can see the following blocks");

define('_AM_FINDU4GROUP', 'Find users for this group');
define('_AM_GROUPSMAIN', 'Groups Main');

define('_AM_ADMINNO', 'There must be at least one user in the webmasters group');

# Adding dynamic block area/position system - TheRpLima - 2007-10-21
define("_AM_SBLEFT","Side Block - Left");
define("_AM_SBRIGHT","Side Block - Right");
define("_AM_CBLEFT","Center Block - Left");
define("_AM_CBRIGHT","Center Block - Right");
define("_AM_CBCENTER","Center Block - Center");
define("_AM_CBBOTTOMLEFT","Center Block - Bottom left");
define("_AM_CBBOTTOMRIGHT","Center Block - Bottom right");
define("_AM_CBBOTTOM","Center Block - Bottom");
#

define("_AM_EDPERM","Can use the WYSIWYG editor in the following modules");
define("_AM_DEBUG_PERM","Can see the Debug Mode in the following modules");
define("_AM_GROUPMANAGER_PERM","Can change permissions on these groups");

// Added Since 1.2
define('_MD_AM_ID', 'ID');

define("_AM_SBLEFT_ADMIN","Admin Side Block - Left");
define("_AM_SBRIGHT_ADMIN","Admin Side Block - Right");
define("_AM_CBLEFT_ADMIN","Admin Center Block - Left");
define("_AM_CBRIGHT_ADMIN","Admin Center Block - Right");
define("_AM_CBCENTER_ADMIN","Admin Center Block - Center");
define("_AM_CBBOTTOMLEFT_ADMIN","Admin Center Block - Bottom left");
define("_AM_CBBOTTOMRIGHT_ADMIN","Admin Center Block - Bottom right");
define("_AM_CBBOTTOM_ADMIN","Admin Center Block - Bottom");
?>