<?php
/**
 * Extended User Profile
 *
 *
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          Marcello Brandao <marcello.brandao@gmail.com>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id$
 */

define("_MI_PROFILE_NUMBPICT_TITLE", "Number of Pictures");
define("_MI_PROFILE_NUMBPICT_DESC", "Number of pictures a user can have in their page. '0' to deactivate check (not recommended).");
define("_AM_PROFILE_REGSTEPS", "Registration Steps");
define("_AM_PROFILE_CATEGORYS", "Categories");
define("_AM_PROFILE_FIELDS", "Fields");
define("_MI_PROFILE_THUMW_TITLE" , "Thumb Width");
define("_MI_PROFILE_THUMBW_DESC" , "Thumbnails width in pixels<br />This means your picture thumbnail will be<br />most of this size in width<br />All proportions are maintained");
define("_MI_PROFILE_THUMBH_TITLE" , "Thumb Height");
define("_MI_PROFILE_THUMBH_DESC" , "Thumbnails Height in pixels<br />This means your picture thumbnail will be<br />most of this size in height<br />All proportions are maintained");
define("_MI_PROFILE_RESIZEDW_TITLE" , "Resized picture width");
define("_MI_PROFILE_RESIZEDW_DESC" , "Resized picture width in pixels<br />This means your picture will be<br />most of this size in width<br />All proportions are maintained<br /> The original picture if bigger than this size will <br />be resized, so it wont break your template");
define("_MI_PROFILE_RESIZEDH_TITLE" , "Resized picture height");
define("_MI_PROFILE_RESIZEDH_DESC" , "Resized picture height in pixels<br />This means your picture will be<br />most of this size in height<br />All proportions are maintained<br /> The original picture if bigger than this size will <br />be resized, so it wont break your template design");
define("_MI_PROFILE_ORIGINALW_TITLE" , "Max original picture width");
define("_MI_PROFILE_ORIGINALW_DESC" , "Maximum original picture width in pixels<br />This means the user's original picture can't exceed <br />this size in height<br /> else it won't be uploaded");
define("_MI_PROFILE_ORIGINALH_TITLE" , "Max original picture height");
define("_MI_PROFILE_ORIGINALH_DESC" , "Maximum original picture height in pixels<br />This means the user's original picture can't exceed <br />this size in height<br /> else it won't be uploaded");
define("_MI_PROFILE_PATHUPLOAD_TITLE" , "Path Uploads");
define("_MI_PROFILE_PATHUPLOAD_DESC" , "Path to the uploads directory<br />in Linux it should look like this /var/www/uploads<br />in Windows like this C:/Program Files/www");
define("_MI_PROFILE_LINKPATHUPLOAD_TITLE","Link to your uploads directory");
define("_MI_PROFILE_LINKPATHUPLOAD_DESC","This is the address of the root path to uploads <br />like http://www.yoursite.com/uploads");
define("_MI_PROFILE_MAXFILEBYTES_PICTURE_TITLE","Max size in bytes per picture");
define("_MI_PROFILE_MAXFILEBYTES_PICTURE_DESC","This is the maximum size a picture file can be<br /> You can set it in bytes like this: 512000 for 500 KB<br /> Be careful that the maximum size is also set in the php.ini file. The server is currently set to a maximum post size of <strong>".ini_get('post_max_size')."</strong> and a maximum upload filesize of <strong>".ini_get('upload_max_filesize')."</strong>.");
define("_MI_PROFILE_MAXFILEBYTES_AUDIO_TITLE","Max size in bytes per audio");
define("_MI_PROFILE_MAXFILEBYTES_AUDIO_DESC","This is the maximum size a audio file can be<br /> You can set it in bytes like this: 5242880 for 5 MB<br /> Be careful that the maximum size is also set in the php.ini file. The server is currently set to a maximum post size of <strong>".ini_get('post_max_size')."</strong> and a maximum upload filesize of <strong>".ini_get('upload_max_filesize')."</strong>.");

define("_MI_PROFILE_PICTURE_NOTIFYTIT","Album");
define("_MI_PROFILE_PICTURE_NOTIFYDSC","Notifications related to user's album");
define("_MI_PROFILE_PICTURE_NEWPIC_NOTIFY","New Picture");
define("_MI_PROFILE_PICTURE_NEWPIC_NOTIFYCAP","Tell me when this user submits a new picture");
define("_MI_PROFILE_PICTURE_NEWPOST_NOTIFYDSC","Tell me when this user submits a new picture");
define("_MI_PROFILE_PICTURE_NEWPIC_NOTIFYSBJ","{X_OWNER_NAME} has submitted a new picture to their album");
define("_MI_PROFILE_FRIENDSPERPAGE_TITLE" , "Friends per page");
define("_MI_PROFILE_FRIENDSPERPAGE_DESC" , "Set the number of friends to show per page<br />In the my Friends page");
define("_MI_PROFILE_PICTURESPERPAGE_TITLE", "Pictures per page");
define("_MI_PROFILE_PICTURESPERPAGE_DESC", "Pictures showing per page before pagination");

define("_MI_PROFILE_DELETEPHYSICAL_TITLE","Delete files from the upload folder too");
define("_MI_PROFILE_DELETEPHYSICAL_DESC","Confirming yes here, will allow the script to delete the files from the uploaded data in the database as well.<br /> Be careful about this feature, if you exclude the files from the folder and not only in the database, some people who may have linked to the image directly in another part of the site may also lose their content;<br /> at the same time if you don't exclude them, you may use to much space in the server hard disk.<br />Configure this item well for your needs.");

define("_MI_PROFILE_MODULEDESC","This module simulates a social network software like MySpace or Orkut, please login or register now.");
define("_MI_PROFILE_TUBEW_TITLE","Width of the YouTube videos");
define("_MI_PROFILE_TUBEW_DESC","The width in pixels of the YouTube video player");
define("_MI_PROFILE_TUBEH_TITLE","Height of the YouTube videos");
define("_MI_PROFILE_TUBEH_DESC","The height in pixels of the YouTube video player");

define("_MI_PROFILE_VIDEOSPERPAGE_TITLE","Videos per Page");
define("_MI_PROFILE_VIDEO_NOTIFYTIT","Videos");
define("_MI_PROFILE_VIDEO_NOTIFYDSC","Video notifications");
define("_MI_PROFILE_VIDEO_NEWVIDEO_NOTIFY","New video");
define("_MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYCAP","Notify me when a new video is submitted by this user");
define("_MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYDSC","New video notify description");
define("_MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYSBJ","{X_OWNER_NAME} has submitted a new video to their profile");

define("_MI_PROFILE_MAINTUBEW_TITLE","Main Video width");
define("_MI_PROFILE_MAINTUBEW_DESC","Width of the video, which shows in the front page of the module");
define("_MI_PROFILE_MAINTUBEH_TITLE","Main Video height");
define("_MI_PROFILE_MAINTUBEH_DESC","Height of the video, that shows in the front page of the module");

define("_MI_PROFILE_MYCONFIGS","My Settings");
define("_MI_PROFILE_TRIBESPERPAGE_TITLE","Tribes per page");
define("_MI_PROFILE_TRIBESPERPAGE_DESC","Tribes per page before pagination show up");
define("_MI_PROFILE_TRIBETOPICSPERPAGE_TITLE","Tribe Topics per page");
define("_MI_PROFILE_TRIBETOPICSPERPAGE_DESC","Tribe Topics per page before pagination show up");
define("_MI_PROFILE_TRIBEPOSTSPERPAGE_TITLE","Tribe Posts per page");
define("_MI_PROFILE_TRIBEPOSTSPERPAGE_DESC","Tribe Posts per page before pagination show up");
define("_MI_PROFILE_SEARCH","Search Members");
define("_MI_PROFILE_ENABLEPICT_TITLE","Enable pictures section");
define("_MI_PROFILE_ENABLEPICT_DESC","Enabling the pictures section for the users, will enable the pictures gallery");
define("_MI_PROFILE_ENABLEFRIENDS_TITLE","Enable friends section");
define("_MI_PROFILE_ENABLEFRIENDS_DESC","Enabling friends section for the users, will enable friends agenda");
define("_MI_PROFILE_ENABLEVIDEOS_TITLE","Enable videos section");
define("_MI_PROFILE_ENABLEVIDEOS_DESC","Enabling videos section for the users, will enable the video gallery");
define("_MI_PROFILE_ENABLETRIBES_TITLE","Enable Tribes section");
define("_MI_PROFILE_ENABLETRIBES_DESC","Enabling Tribes section for the users, will enable them to create Tribes, which group users that have similar interests");

define("_MI_PROFILE_FRIENDS","My Friends");
define("_MI_PROFILE_FRIENDS_DESC","This block shows the user friends");

define("_MI_PROFILE_IMGORDER_TITLE", "Show latest pictures first?");
define("_MI_PROFILE_IMGORDER_DESC", "");
define("_MI_PROFILE_FRIENDSHIP_NOTIFYTIT","Friendships");
define("_MI_PROFILE_FRIENDSHIP_NOTIFYDSC","Petitions of friendship");
define("_MI_PROFILE_FRIEND_NEWPETITION_NOTIFY","Petition");
define("_MI_PROFILE_FRIEND_NEWPETITION_NOTIFYCAP","Notify me when someone asks to become a friend");
define("_MI_PROFILE_FRIEND_NEWPETITION_NOTIFYDSC","Notify me when someone asks for friendship");
define("_MI_PROFILE_FRIEND_NEWPETITION_NOTIFYSBJ","Someone has just asked to be your friend");
define("_MI_PROFILE_ENABLEAUDIO_TITLE","Enable audio section");
define("_MI_PROFILE_ENABLEAUDIO_DESC","Enabling audio section for the users, will enable the audio playlist");
define("_MI_PROFILE_NUMBAUDIO_TITLE", "Number of audios");
define("_MI_PROFILE_NUMBAUDIO_DESC", "Number of audio files a user can have in their page. '0' to deactivate check (not recommended).");
define("_MI_PROFILE_AUDIOSPERPAGE_TITLE", "Number of audio files per page");
define("_PROFILE_MI_NAME", "Profile");
define("_PROFILE_MI_DESC", "Module for managing custom user profile fields");
define("_PROFILE_MI_EDITACCOUNT", "Edit Account");
define("_PROFILE_MI_CHANGEPASS", "Change Password");
define("_PROFILE_MI_CHANGEMAIL", "Change Email");
define("_PROFILE_MI_USERS", "Users");
define("_PROFILE_MI_PERMISSIONS", "Permissions");
define("_PROFILE_MI_FINDUSER", "Find Users");
define("_PROFILE_MI_AIM_TITLE", "AIM");
define("_PROFILE_MI_AIM_DESCRIPTION", "America Online Instant Messenger Client ID");
define("_PROFILE_MI_ICQ_TITLE", "ICQ");
define("_PROFILE_MI_ICQ_DESCRIPTION", "ICQ Instant Messenger ID");
define("_PROFILE_MI_YIM_TITLE", "YIM");
define("_PROFILE_MI_YIM_DESCRIPTION", "Yahoo! Instant Messenger ID");
define("_PROFILE_MI_MSN_TITLE", "MSN");
define("_PROFILE_MI_MSN_DESCRIPTION", "Microsoft Messenger ID");
define("_PROFILE_MI_FROM_TITLE", "Location");
define("_PROFILE_MI_FROM_DESCRIPTION", "");
define("_PROFILE_MI_SIG_TITLE", "Signature");
define("_PROFILE_MI_SIG_DESCRIPTION", "Here, you can write a signature that can be displayed in your forum posts, comments etc.");
define("_PROFILE_MI_VIEWEMAIL_TITLE", "Allow other users to view my email address");
define("_PROFILE_MI_VIEWEOID_TITLE", "Allow other users to view my OpenID address");
define("_PROFILE_MI_BIO_TITLE", "Extra Info");
define("_PROFILE_MI_BIO_DESCRIPTION", "");
define("_PROFILE_MI_INTEREST_TITLE", "Interests");
define("_PROFILE_MI_INTEREST_DESCRIPTION", "");
define("_PROFILE_MI_OCCUPATION_TITLE", "Occupation");
define("_PROFILE_MI_OCCUPATION_DESCRIPTION", "");
define("_PROFILE_MI_URL_TITLE", "Website");
define("_PROFILE_MI_URL_DESCRIPTION", "");
//Configuration categories
define('_PROFILE_MI_CAT_PERSONAL', 'Personal');
define('_PROFILE_MI_CAT_MESSAGING', 'Messaging');
define('_PROFILE_MI_CAT_SETTINGS1', 'Settings');
define('_PROFILE_MI_CAT_COMMUNITY', 'Community');
define('_PROFILE_MI_CAT_BASEINFO', 'Basic information');
define('_PROFILE_MI_CAT_EXTINFO', 'Complementary information');

//Configuration categories
define("_PROFILE_MI_CAT_SETTINGS", "General Settings");
define("_PROFILE_MI_CAT_SETTINGS_DSC", "");
define("_PROFILE_MI_CAT_USER", "User Settings");
define("_PROFILE_MI_CAT_USER_DSC", "");

//Configuration items
define("_PROFILE_MI_PROFILE_SEARCH", "Show latest submissions by user on user profile");
define("_PROFILE_MI_SHOWEMPTY", "Show empty fields");
define("_PROFILE_MI_SHOWEMPTY_DESC", "If set to 'no', fields without a value will not show up on user profiles");

define("_PROFILE_MI_DISPNAME", "Name to display on index page");
define("_PROFILE_MI_DISPNAME_DESC", "");
define("_PROFILE_MI_NICKNAME", "User name");
define("_PROFILE_MI_REALNAME", "Real name");
define("_PROFILE_MI_BOTH", "Both");

define("_PROFILE_MI_GROUP_VIEW_ANONYMOUS", "Anonymous users can view");
define("_PROFILE_MI_GROUP_VIEW_REGISTERED", "Registered users can view");
define("_PROFILE_MI_GROUP_VIEW_DSC", "");
define("_PROFILE_MI_PROFILE_SOCIAL", "Social profile manager");
define("_PROFILE_MI_PROFILE_SOCIAL_DESC", "Do you want to use this module as a social profile like facebook?");

define("_MI_PROFILE_VISIBILITY", "Visibility");
define("_MI_PROFILE_AUDIOS", "Audios");
define("_MI_PROFILE_TRIBES", "Tribes");
//define("_MI_PROFILE_SUSPENSIONS", "Suspensions");
define("_MI_PROFILE_PICTURES", "Pictures");
define("_MI_PROFILE_VIDEOS", "Videos");
define("_MI_PROFILE_TRIBEUSERS", "Tribeusers");
$member_handler = &xoops_gethandler('member');
$group_list = &$member_handler->getGroupList();
foreach ($group_list as $key=>$group) {
	if($key > 3){
		define("_PROFILE_MI_GROUP_VIEW_".$key, $group." users can view");
	}
}
?>