<?php
// $Id: global.php 20900 2011-02-27 02:18:47Z skenow $
//%%%%%%	File Name mainfile.php 	%%%%%
define('_PLEASEWAIT','Please Wait');
define('_FETCHING','Loading...');
define('_TAKINGBACK','Taking you back to where you were....');
define('_LOGOUT','Logout');
define('_SUBJECT','Subject');
define('_MESSAGEICON','Message Icon');
define('_COMMENTS','Comments');
define('_POSTANON','Post Anonymously');
define('_DISABLESMILEY','Disable smiley');
define('_DISABLEHTML','Disable html');
define('_PREVIEW','Preview');

define('_GO','Go!');
define('_NESTED','Nested');
define('_NOCOMMENTS','No Comments');
define('_FLAT','Flat');
define('_THREADED','Threaded');
define('_OLDESTFIRST','Oldest First');
define('_NEWESTFIRST','Newest First');
define('_MORE','more...');
define('_IFNOTRELOAD','If the page does not automatically reload, please click <a href="%s">here</a>');
define('_WARNINSTALL2','WARNING: Directory %s exists on your server. <br />Please remove this directory for security reasons.');
define('_WARNINWRITEABLE','WARNING: File %s is writeable by the server. <br />Please change the permission of this file for security reasons.<br /> in Unix (444), in Win32 (read-only)');
define('_WARNINNOTWRITEABLE','WARNING: File %s is not writeable by the server. <br />Please change the permission of this file for functionality reasons.<br /> in Unix (777), in Win32 (writeable)');

// Error messages issued by icms_core_Object::cleanVars()
define( '_XOBJ_ERR_REQUIRED', '%s is required' );
define( '_XOBJ_ERR_SHORTERTHAN', '%s must be shorter than %d characters.' );

//%%%%%%	File Name themeuserpost.php 	%%%%%
define('_PROFILE','Profile');
define('_POSTEDBY','Posted by');
define('_VISITWEBSITE','Visit Website');
define('_SENDPMTO','Send Private Message to %s');
define('_SENDEMAILTO','Send Email to %s');
define('_ADD','Add');
define('_REPLY','Reply');
define('_DATE','Date');   // Posted date

//%%%%%%	File Name admin_functions.php 	%%%%%
define('_MAIN','Main');
define('_MANUAL','Manual');
define('_INFO','Info');
define('_CPHOME','Control Panel Home');
define('_YOURHOME','Home Page');

//%%%%%%	File Name misc.php (who's-online popup)	%%%%%
define('_WHOSONLINE','Who\'s Online');
define('_GUESTS', 'Guests');
define('_MEMBERS', 'Members');
define('_ONLINEPHRASE','<b>%s</b> user(s) are online');
define('_ONLINEPHRASEX','<b>%s</b> user(s) are browsing <b>%s</b>');
define('_CLOSE','Close');  // Close window

//%%%%%%	File Name module.textsanitizer.php 	%%%%%
define('_QUOTEC','Quote:');

//%%%%%%	File Name admin.php 	%%%%%
define("_NOPERM","Sorry, you don't have permission to access this area.");

//%%%%%		Common Phrases		%%%%%
define("_NO","No");
define("_YES","Yes");
define("_EDIT","Edit");
define("_DELETE","Delete");
define("_SUBMIT","Submit");
define("_MODULENOEXIST","Selected module does not exist!");
define("_ALIGN","Align");
define("_LEFT","Left");
define("_CENTER","Center");
define("_RIGHT","Right");
define("_FORM_ENTER", "Please enter %s");
// %s represents file name
define("_MUSTWABLE","File %s must be writable by the server!");
// Module info
define('_PREFERENCES', 'Preferences');
define("_VERSION", "Version");
define("_DESCRIPTION", "Description");
define("_ERRORS", "Errors");
define("_NONE", "None");
define('_ON','on');
define('_READS','reads');
define('_SEARCH','Search');
define('_ALL', 'All');
define('_TITLE', 'Title');
define('_OPTIONS', 'Options');
define('_QUOTE', 'Quote');
define('_HIDDENC', 'Hidden Content:');
define('_HIDDENTEXT', 'This content is hidden for anonymous users, please <a href="'.ICMS_URL.'/register.php" title="Registeration at ' . htmlspecialchars ( $icmsConfig ['sitename'], ENT_QUOTES ) . '">register</a> to be able to see it.');
define('_LIST', 'List');
define('_LOGIN','User Login');
define('_USERNAME','Username: ');
define('_PASSWORD','Password: ');
define("_SELECT","Select");
define("_IMAGE","Image");
define("_SEND","Send");
define("_CANCEL","Cancel");
define("_ASCENDING","Ascending order");
define("_DESCENDING","Descending order");
define('_BACK', 'Back');
define('_NOTITLE', 'No title');

/* Image manager */
define('_IMGMANAGER','Image Manager');
define('_NUMIMAGES', '%s images');
define('_ADDIMAGE','Add Image File');
define('_IMAGENAME','Name:');
define('_IMGMAXSIZE','Max size allowed (bytes):');
define('_IMGMAXWIDTH','Max width allowed (pixels):');
define('_IMGMAXHEIGHT','Max height allowed (pixels):');
define('_IMAGECAT','Category:');
define('_IMAGEFILE','Image file:');
define('_IMGWEIGHT','Order:');
define('_IMGDISPLAY','Display this image?');
define('_IMAGEMIME','MIME type:');
define('_FAILFETCHIMG', 'Could not get uploaded file %s');
define('_FAILSAVEIMG', 'Failed storing image %s into the database');
define('_NOCACHE', 'No Cache');
define('_CLONE', 'Clone');
define('_INVISIBLE', 'Invisible');

//%%%%%	File Name class/xoopsform/formmatchoption.php 	%%%%%
define("_STARTSWITH", "Starts with");
define("_ENDSWITH", "Ends with");
define("_MATCHES", "Matches");
define("_CONTAINS", "Contains");

//%%%%%%	File Name commentform.php 	%%%%%
define("_REGISTER","Register");

//%%%%%%	File Name xoopscodes.php 	%%%%%
define("_SIZE","SIZE");  // font size
define("_FONT","FONT");  // font family
define("_COLOR","COLOR");  // font color
define("_EXAMPLE","SAMPLE");
define("_ENTERURL","Enter the URL of the link you want to add:");
define("_ENTERWEBTITLE","Enter the web site title:");
define("_ENTERIMGURL","Enter the URL of the image you want to add.");
define("_ENTERIMGPOS","Now, enter the position of the image.");
define("_IMGPOSRORL","'R' or 'r' for right, 'L' or 'l' for left, 'C' or 'c' for center, or leave it blank.");
define("_ERRORIMGPOS","ERROR! Enter the position of the image.");
define("_ENTEREMAIL","Enter the email address you want to add.");
define("_ENTERCODE","Enter the codes that you want to add.");
define("_ENTERQUOTE","Enter the text that you want to be quoted.");
define("_ENTERHIDDEN","Enter the text that you want to be hidden for anonymous users.");
define("_ENTERTEXTBOX","Please input text into the textbox.");

//%%%%%		TIME FORMAT SETTINGS   %%%%%
define('_SECOND', '1 second');
define('_SECONDS', '%s seconds');
define('_MINUTE', '1 minute');
define('_MINUTES', '%s minutes');
define('_HOUR', '1 hour');
define('_HOURS', '%s hours');
define('_DAY', '1 day');
define('_DAYS', '%s days');
define('_WEEK', '1 week');
define('_MONTH', '1 month');

define("_DATESTRING","Y/n/j G:i:s");
define("_MEDIUMDATESTRING","g:ia, F j Y");
define("_SHORTDATESTRING","Y-m-d"); // ALTERED BY FREEFORM SOLUTIONS FOR BACKWARDS COMPATIBILITY WITH PREVIOUS DATE FORMATS
/*
 The following characters are recognized in the format string:
 a - "am" or "pm"
 A - "AM" or "PM"
 d - day of the month, 2 digits with leading zeros; i.e. "01" to "31"
 D - day of the week, textual, 3 letters; i.e. "Fri"
 F - month, textual, long; i.e. "January"
 h - hour, 12-hour format; i.e. "01" to "12"
 H - hour, 24-hour format; i.e. "00" to "23"
 g - hour, 12-hour format without leading zeros; i.e. "1" to "12"
 G - hour, 24-hour format without leading zeros; i.e. "0" to "23"
 i - minutes; i.e. "00" to "59"
 j - day of the month without leading zeros; i.e. "1" to "31"
 l (lowercase 'L') - day of the week, textual, long; i.e. "Friday"
 L - boolean for whether it is a leap year; i.e. "0" or "1"
 m - month; i.e. "01" to "12"
 n - month without leading zeros; i.e. "1" to "12"
 M - month, textual, 3 letters; i.e. "Jan"
 s - seconds; i.e. "00" to "59"
 S - English ordinal suffix, textual, 2 characters; i.e. "th", "nd"
 t - number of days in the given month; i.e. "28" to "31"
 T - Timezone setting of this machine; i.e. "MDT"
 U - seconds since the epoch
 w - day of the week, numeric, i.e. "0" (Sunday) to "6" (Saturday)
 Y - year, 4 digits; i.e. "1999"
 y - year, 2 digits; i.e. "99"
 z - day of the year; i.e. "0" to "365"
 Z - timezone offset in seconds (i.e. "-43200" to "43200")
 */

//%%%%%		LANGUAGE SPECIFIC SETTINGS   %%%%%
define('_CHARSET', 'utf-8');
define('_LANGCODE', 'en');

// change 0 to 1 if this language is a multi-bytes language
define("XOOPS_USE_MULTIBYTES", "0");
// change 0 to 1 if this language is a RTL (right to left) language
define("_ADM_USE_RTL","0");

define('_MODULES','Modules');
define('_SYSTEM','System');
define('_IMPRESSCMS_NEWS','News');
define('_ABOUT','The ImpressCMS Project');
define('_IMPRESSCMS_HOME','Project Home');
define('_IMPRESSCMS_COMMUNITY','Community');
define('_IMPRESSCMS_ADDONS','Addons');
define('_IMPRESSCMS_WIKI','Wiki');
define('_IMPRESSCMS_BLOG','Blog');
define('_IMPRESSCMS_DONATE','Donate!');
define("_IMPRESSCMS_Support","Support the project !");
define('_IMPRESSCMS_SOURCEFORGE','SourceForge Project');
define('_IMPRESSCMS_ADMIN','Administration of');
/** The default separator used in icms_view_Tree::getNicePathFromId */
define('_BRDCRMB_SEP','&nbsp;:&nbsp;');
//Content Manager
define('_CT_NAV','Home');
define('_CT_RELATEDS','Related pages');
//Security image (captcha)
define("_SECURITYIMAGE_GETCODE","Enter the security code");
define("_QUERIES", "Queries");
define("_BLOCKS", "Blocks");
define("_EXTRA", "Extra");
define("_TIMERS", "Timers");
define("_CACHED", "Cached");
define("_REGENERATES", "Regenerates every %s seconds");
define("_TOTAL", "Total :");
define("_ERR_NR", "Error number:");
define("_ERR_MSG", "Error message:");
define("_NOTICE", "Notice");
define("_WARNING", "Warning");
define("_STRICT", "Strict");
define("_ERROR", "Error(s)");
define("_TOOKXLONG", " took %s seconds to load.");
define("_BLOCK", "Block(s)");
define("_WARNINGUPDATESYSTEM","Congratulations, you have just successfully upgraded your site to the latest version of ImpressCMS!<br />Therefor to finish the upgrade process you'll need to click here and update your system module.<br />Click here to process the upgrade.");

// This shows local support site in ImpressCMS menu, (if selected language is not English)
define('_IMPRESSCMS_LOCAL_SUPPORT','http://www.impresscms.org'); //add the local support site's URL
define('_IMPRESSCMS_LOCAL_SUPPORT_TITLE','Local support site');
define("_ALLEFTCON","Enter the text to be aligned on the Left side.");
define("_ALCENTERCON","Enter the text to be aligned on the Center side.");
define("_ALRIGHTCON","Enter the text to be aligned on the Right side.");
define( "_TRUST_PATH_HELP", "Warning: System failed in reaching Trust path.<br />The trust path is a folder where ImpressCMS and its modules will store some sensible code and information for more security.<br />It is recommended that this folder be outside of the web root, making it not accessible by a browser.<br /><a target='_blank' href='http://wiki.impresscms.org/index.php?title=Trust_Path'>Click here to learn more about the Trust path and how to Create it.</a>" );
define( "_PROTECTOR_NOT_FOUND", "Warning: System is unable to find if Protector is installed or active in your site.<br />We highly recommend you to install or activate Protector to improve your site's security.<br />We also have to thank GIJOE for this very good module.<br /><a target='_blank' href='http://wiki.impresscms.org/index.php?title=Protector'>Click here to learn more about Protector.</a><br /><a target='_blank' href='http://xoops.peak.ne.jp/modules/mydownloads/singlefile.php?lid=105&cid=1'>Click here to download the latest version of Protector.</a>" );

define('_MODABOUT_ABOUT', 'About');
// if you have troubles with this font on your language or it is not working, download tcpdf from: http://www.tecnick.com/public/code/cp_dpage.php?aiocp_dp=tcpdf and add the required font in libraries/tcpdf/fonts then write down the font name here. system will then load this font for your language.
define('_PDF_LOCAL_FONT', '');
define('_CALENDAR_TYPE',''); // this value is for the local calendar used in this system, if you're not sure about this leave this value as it is!
define('_CALENDAR','Calendar');
define('_RETRYPOST','Sorry, a time-out occured. Would you like to post again ?'); // autologin hack GIJ

############# added since 1.2 #############
define('_QSEARCH','Quick Search');
define('_PREV','Prev');
define('_NEXT','Next');
define('_LCL_NUM0','0');
define('_LCL_NUM1','1');
define('_LCL_NUM2','2');
define('_LCL_NUM3','3');
define('_LCL_NUM4','4');
define('_LCL_NUM5','5');
define('_LCL_NUM6','6');
define('_LCL_NUM7','7');
define('_LCL_NUM8','8');
define('_LCL_NUM9','9');
// change 0 to 1 if your language has a different numbering than latin`s alphabet
define("_USE_LOCAL_NUM","0");
define("_ICMS_DBUPDATED","Database Updated Successfully!");
define('_MD_AM_DBUPDATED',_ICMS_DBUPDATED);

define('_TOGGLETINY','Toggle Editor');
define("_ENTERHTMLCODE","Enter the HTML codes that you want to add.");
define("_ENTERPHPCODE","Enter the PHP codes that you want to add.");
define("_ENTERCSSCODE","Enter the CSS codes that you want to add.");
define("_ENTERJSCODE","Enter the JavaScript codes that you want to add.");
define("_ENTERWIKICODE","Enter the wiki term that you want to add.");
define("_ENTERLANGCONTENT","Enter the text that you want to be in %s.");
define('_LANGNAME', 'English');
define('_ENTERYOUTUBEURL', 'Enter YouTube url:');
define('_ENTERHEIGHT', 'Enter frame\'s height');
define('_ENTERWIDTH', 'Enter frame\'s width');
define('_ENTERMEDIAURL', 'Enter media url:');
// !!IMPORTANT!! insert '\' before any char among reserved chars: "a", "A", "B", "c", "d", "D", "F", "g", "G", "h", "H", "i", "I", "j", "l", "L", "m", "M", "n", "O", "r", "s", "S", "t", "T", "U", "w", "W", "Y", "y", "z", "Z"
// insert double '\' before 't', 'r', 'n'
define("_TODAY", "\T\o\d\a\y G:i");
define("_YESTERDAY", "\Y\e\s\\t\e\\r\d\a\y G:i");
define("_MONTHDAY", "n/j G:i");
define("_YEARMONTHDAY", "Y/n/j G:i");
define("_ELAPSE", "%s ago");
define('_VISIBLE', 'Visible');
define('_UP', 'Up');
define('_DOWN', 'Down');
define('_CONFIGURE', 'Configure');

// Added in 1.2.2
define('_CSSTIDY_VULN', 'WARNING: File %s exists on your server. <br />Please remove this file manually');
define('_FILE_DELETED', 'File %s was deleted successfully');

// added in 1.3
define('_CHECKALL', 'Check all');
define('_COPYRIGHT', 'Copyright');
define("_LONGDATESTRING", "F jS Y, h:iA");
define('_AUTHOR', 'Author');
define("_CREDITS", "Credits");
define("_LICENSE", "License");
define("_LOCAL_FOOTER",'<a href=\"http://www.freeformsolutions.ca/formulize/\" rel=\"external\" target=\"_blank\">Formulize Standalone Version</a> &copy; 2004-' . date('Y', time()) . ' &mdash; <a href=\"http://www.impresscms.org/\" rel=\"external\">The ImpressCMS Project</a> &copy; 2007-' . date('Y', time()));

// ADDED BY FREEFORM SOLUTIONS FOR THE DATE DEFAULT CHANGES IN FORMULIZE STANDALONE
define("_DATE_DEFAULT", "YYYY-mm-dd");
define("_CAL_MONDAYFIRST", "false");