*** SHOULD YOU USE THIS PACKAGE? ***

This is the full install of Formulize.  If you are currently using 1.5RC, then we recommend you use the 1.5RC to 1.6RC patch.  There is information and files in that package specifically related to updating the module.

*** WHAT IS FORMULIZE? ***

Formulize is a module for the XOOPS 2 portal framework that lets you create forms for your site.  The forms are accessible through a special menu block called the Form Menu.  You can specify whether users should be allowed to fill in forms multiple times or only once (ie: the difference between an activity log form which you fill in many times, once for each activity, and a conference registration form which you fill in once to register yourself for a conference).

You can view the data that your or others have entered into a form.  Depending on the default options you pick for your form, the data that is visible will either be all your own entries, or entries belonging to everyone who is in the same group(s) as you.

There are also reporting features in the module, which let you filter the entries that are displayed to you according to search terms and sorting rules that you specify.  You can also request calculations be performed on fields, such as sum totals or percentage breakdowns.  Users with admin rights on a form can use advanced reporting features to collect together information from mulitiple groups at once, and they can also publish reports to different groups of users.

Formulize is a complex module with powerful features.  With some time and effort, you can use Formulize to create virtually any kind of simple data-entry and reporting application.  If the tasks and functions you have in mind can be described in a few simple steps related to submitting data into a form and then viewing that data in certain ways through reports, then chances are you can build an application in Formulize to meet your needs, without needing to program a module yourself or know any PHP or SQL.

This latest release of Formulize includes a data extraction layer which you can use to query any form, or a collection of forms, from outside the module, or even from outside of XOOPS.  You can use this layer to present data submitted through forms in Formulize on another website, provided you can create a PHP based template that will format the data in the way you want it presented.  Details about this layer and how to use its outputs are in the file called extract_help.txt.

*** WHY IS THIS CALLED A RELEASE CANDIDATE? ***

Because an official release should be of higher quality, in our opinion.  For one thing, an official release should have complete documentation that explains how to use the module in detail.  Also, there are a few loose ends and rough spots in the module that should be addressed before we would be happy calling the module an official release.  Also, the underlying code is quite a challenge to work with, and an official release would either have comprehensive developer documentation to help you make sense of the code, or would have cleaner code.

However, the module has been used widely and we are reasonably confident that it won't chew up your data all by itself.  Although it is not a final release, we believe it to be very stable and just about completely bug free in normal operation.  But we make no guarantees.

*** INSTALLATION ***

Just copy the 'formulize' folder to your XOOPS modules directory.  Then CHMOD the export folder to 777 (global write permission).  Lastly, login to your site, and under Admin Menu->System->Modules, install Formulize 1.6.

Next, you should install the DateSelectBox patch for the XOOPS core.  The patch contains two files that need to overwrite their existing counterparts in XOOPS:

class/xoopsform/formtextdateselect.php
include/calendarjs.php

This patch gives much improved functionality for DateSelectBoxes, for all modules, not just Formulize.  Formulize should work fine without the patch installed, but it's better with it.

For details about the patch, look here:

http://sourceforge.net/tracker/?func=detail&aid=1077123&group_id=41586&atid=430842

AN IMPORTANT NOTE TO USERS WHO DO INSTALL THIS PATCH!:

With the 1.6 release of Formulize, this patch has been modified to correct a problem that the DateSelectBoxes caused, whereby the javascript and stylesheet code related to the popup calendar was drawn in above the actual start of the page.  This in turn caused the menus of your site to change appearance (it was drawn with extra padding around it).

In order to use this patch and fix this problem, you will need to make one very small change to your theme.html file, in order to accept a template variable which the patched file generates so that the javascript and stylesheet code can be inserted in the correct part of the page.  Details about how to do this are included in a comment attached to that sourceforge patch entry linked above.

*** SETUP AND CONFIGURATION ***

FORMULIZE IS NOT DESIGNED TO BE LISTED IN THE MAIN MENU!

On the System Admin->Modules page, set the Order for Formulize to '0' (to hide it).  To access the forms, you use the Form Menu block.  You will need to give access rights to that block to every group that you want to have access to the forms.  You will then also need to configure the visibility settings and location of the Form Menu block so that it appears properly on your site.

*** INFORMATION ABOUT USING THE MODULE ***

There is a lot of information about how to use the module in the Formulize area on dev.xoops.org.  In particular, we suggest you read the following threads:

http://dev.xoops.org/modules/xfmod/forum/forum.php?thread_id=1180&forum_id=745
http://dev.xoops.org/modules/xfmod/forum/forum.php?thread_id=1239&forum_id=745

There is also complete documentation on the Report Writing system available in the Docs section here:

http://dev.xoops.org/modules/xfmod/docman/?group_id=1179

For details about using the data extraction layer to access and present data from inside Formulize in another location (such as a separate website from your XOOPS website), read the extract_help.txt file.

*** FEEDBACK AND HELP ***

Please direct feedback and support questions to the trackers and forums for Formulize on dev.xoops.org.  That way, the knowledge base there grows, and you might find the answer to your question there before you even post.  

*** BUGS ***

There is a known minor bug worth mentioning:  if you create a form and then select fields to display on the View Entries page, and then add, delete or resort the fields in the form, then your selected fields to display on the View Entries page may get screwed up.  You should re-select the fields you want to display on the View Entries page after making any changes to the fields in a form.

*** CHANGELOG ***

**Version 1.6RC:
--minor changes to layout/text/graphics of admin screens
--fixed "Incorrect Index Name" SQL error on install that affected some local setups
--fixed obscure bug related to being able to update entries in single-entry/groupscope forms
--added four tables to the .sql file to accomodate the new Frameworks system
--added admin screens for the Frameworks system
--wrote include/extract.php to provide a data extraction layer that is accessible from outside the module
--fixed spelling of Required on add Text Box page
--stipped slashes from text that gets redisplayed when editing a Text Area box in a form
--removed options to add or delete menu items (due to serious problems that caused with the Form Menu)
--removed notification options on non-groupscope forms, unless the user is an admin user of the (requires you to update the module so that template change takes effect).  Non-admin users of non-groupscope forms would never ever receive any notifications so displaying the notification options to them was superfluous.
--fixed serious bug where changing captions after data has been entered would orphan the existing data in a field (it would disappear).  
--fixed core bug related to display of date select box (was causing menu to display wrong, with extra padding -- fix requires a patch to the core and a modification to the site's theme.html file, plus erase everything in the templates_c folder (to force the change to be picked up))
--added spanish translation files done for 1.5RC by Dalton.  Thanks!

**Version 1.5RC:
--initial release

=====================================================

Freeform Solutions
Toronto, Canada, March 9 2005