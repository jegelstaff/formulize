This is a RELEASE CANDIDATE, and should NOT be used on a production site.  Although we believe this is a strong release candidate, it is not an official release.

The intent of this release is to give those interested in the module a chance to see it and play with it, and to see what feedback we get in terms of bugs, feature requests, etc.

***Installation***

Just copy the 'formulize' folder to your XOOPS modules directory.  Then CHMOD the export folder to 777.  Lastly, login to your site, and under Admin Menu->System->Modules, install Formulize 1.5.

Next, you should install the DateSelectBox patch for the XOOPS core.  The patch contains two files that need to overwrite their existing counterparts in XOOPS:

class/xoopsform/formtextdateselect.php
include/calendarjs.php

This patch gives much improved functionality for DateSelectBoxes, for all modules, not just Formulize.  Formulize should work fine without the patch installed, but it's better with it.

For details about the patch, look here:

http://sourceforge.net/tracker/?func=detail&aid=1077123&group_id=41586&atid=430842

***Setup***

Set the Order for Formulize to '0' (to hide it).  To access the forms, you use the Form Menu block.  

***Notes***

Complete docs will accompany the final release.  This is essentially a "sneak-peek".

***Bugs***

Do not change the caption for a form element after data has been submitted.  Due to an architectural problem that we should have fixed in the beginning, but didn't have time to, changing the name of a form element will result in all existing data entered in that element being made inaccessible!

***Feedback***

Please direct feedback to the trackers and forums for Formulize on dev.xoops.org.  Thanks.

--Julian
