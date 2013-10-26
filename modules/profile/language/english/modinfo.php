<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		modules
 * @since		1.2
 * @author		Jan Pedersen
 * @author		Marcello Brandao <marcello.brandao@gmail.com>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: modinfo.php 22413 2011-08-27 10:21:21Z phoenyx $
 */

define("_MI_PROFILE_NUMBPICT_TITLE", "Number of pictures");
define("_MI_PROFILE_NUMBPICT_DESC", "Number of pictures a user can have in their album. '0' to deactivate check (not recommended).");
define("_AM_PROFILE_REGSTEPS", "Registration steps");
define("_AM_PROFILE_CATEGORYS", "Categories");
define("_AM_PROFILE_FIELDS", "Fields");
define("_MI_PROFILE_THUMW_TITLE" , "Thumb width");
define("_MI_PROFILE_THUMBW_DESC" , "Thumbnails width in pixels<br />This means your picture thumbnail will be<br />most of this size in width<br />All proportions are maintained");
define("_MI_PROFILE_THUMBH_TITLE" , "Thumb Height");
define("_MI_PROFILE_THUMBH_DESC" , "Thumbnails Height in pixels<br />This means your picture thumbnail will be<br />most of this size in height<br />All proportions are maintained");
define("_MI_PROFILE_RESIZEDW_TITLE" , "Resized picture width");
define("_MI_PROFILE_RESIZEDW_DESC" , "Resized picture width in pixels.<br />The original picture if bigger than this size will <br />be resized, so it wont break your template");
define("_MI_PROFILE_RESIZEDH_TITLE" , "Resized picture height");
define("_MI_PROFILE_RESIZEDH_DESC" , "Resized picture height in pixels<br />The original picture if bigger than this size will <br />be resized, so it wont break your template design");
define("_MI_PROFILE_ORIGINALW_TITLE" , "Max original picture width");
define("_MI_PROFILE_ORIGINALW_DESC" , "Maximum original picture width in pixels<br />This means the user's original picture can't exceed <br />this size in height<br /> else it won't be uploaded");
define("_MI_PROFILE_ORIGINALH_TITLE" , "Max original picture height");
define("_MI_PROFILE_ORIGINALH_DESC" , "Maximum original picture height in pixels<br />This means the user's original picture can't exceed <br />this size in height<br /> else it won't be uploaded");
define("_MI_PROFILE_MAXFILEBYTES_PICTURE_TITLE", "Max size in bytes per picture");
define("_MI_PROFILE_MAXFILEBYTES_PICTURE_DESC", "This is the maximum size a picture file can be<br /> You can set it in bytes like this: 512000 for 500 KB<br /> Be careful that the maximum size is also set in the php.ini file. The server is currently set to a maximum post size of <strong>".ini_get('post_max_size')."</strong> and a maximum upload filesize of <strong>".ini_get('upload_max_filesize')."</strong>.");
define("_MI_PROFILE_MAXFILEBYTES_AUDIO_TITLE", "Max size in bytes per audio");
define("_MI_PROFILE_MAXFILEBYTES_AUDIO_DESC", "This is the maximum size a audio file can be<br /> You can set it in bytes like this: 5242880 for 5 MB<br /> Be careful that the maximum size is also set in the php.ini file. The server is currently set to a maximum post size of <strong>".ini_get('post_max_size')."</strong> and a maximum upload filesize of <strong>".ini_get('upload_max_filesize')."</strong>.");
define("_MI_PROFILE_PICTURESPERPAGE_TITLE", "Pictures per page");
define("_MI_PROFILE_PICTURESPERPAGE_DESC", "Pictures showing per page before pagination");
define("_MI_PROFILE_VIDEOSPERPAGE_TITLE", "Videos per Page");
define("_MI_PROFILE_DELETEPHYSICAL_TITLE", "Delete uploaded files");
define("_MI_PROFILE_DELETEPHYSICAL_DESC", "If set to 'yes', direct links to this file will be broken as well. Configure with care!");
define("_MI_PROFILE_MODULEDESC", "This module simulates a social network software like MySpace or Orkut, please login or register now.");
define("_MI_PROFILE_TUBEW_TITLE", "Width of the YouTube videos");
define("_MI_PROFILE_TUBEW_DESC", "The width in pixels of the YouTube video player");
define("_MI_PROFILE_TUBEH_TITLE", "Height of the YouTube videos");
define("_MI_PROFILE_TUBEH_DESC", "The height in pixels of the YouTube video player");
define("_MI_PROFILE_PICTURE_NOTIFYTIT", "Pictures");
define("_MI_PROFILE_PICTURE_NOTIFYDSC", "Notifications related to user's pictures");
define("_MI_PROFILE_PICTURE_NEWPIC_NOTIFY", "New picture");
define("_MI_PROFILE_PICTURE_NEWPIC_NOTIFYCAP", "Notify me when a new picture is submitted by this user");
define("_MI_PROFILE_PICTURE_NEWPIC_NOTIFYSBJ", "{PICTURE_OWNER} has submitted a new picture");
define("_MI_PROFILE_VIDEO_NOTIFYTIT", "Videos");
define("_MI_PROFILE_VIDEO_NOTIFYDSC", "Video notifications");
define("_MI_PROFILE_VIDEO_NEWVIDEO_NOTIFY", "New video");
define("_MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYCAP", "Notify me when a new video is submitted by this user");
define("_MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYSBJ", "{VIDEO_OWNER} has submitted a new video to their profile");
define("_MI_PROFILE_AUDIO_NOTIFYTIT", "Audio");
define("_MI_PROFILE_AUDIO_NOTIFYDSC", "Audio notifications");
define("_MI_PROFILE_AUDIO_NEWAUDIO_NOTIFY", "New audio");
define("_MI_PROFILE_AUDIO_NEWAUDIO_NOTIFYCAP", "Notify me when a new audio file is submitted by this user");
define("_MI_PROFILE_AUDIO_NEWAUDIO_NOTIFYSBJ", "{AUDIO_OWNER} has submitted a new audio file to their profile");
define("_MI_PROFILE_TRIBETOPIC_NOTIFYTIT", "Groups");
define("_MI_PROFILE_TRIBETOPIC_NOTIFYDSC", "Group discussion (topic) notifications");
define("_MI_PROFILE_TRIBETOPIC_NEWTRIBETOPIC_NOTIFY", "New topic");
define("_MI_PROFILE_TRIBETOPIC_NEWTRIBETOPIC_NOTIFYCAP", "Notify me when a new topic is submitted for this group");
define("_MI_PROFILE_TRIBETOPIC_NEWTRIBETOPIC_NOTIFYSBJ", "New topic {TRIBETOPIC_TITLE} in {TRIBE_TITLE}");
define("_MI_PROFILE_TRIBEPOST_NOTIFYTIT", "Groups");
define("_MI_PROFILE_TRIBEPOST_NOTIFYDSC", "Group discussion (reply) notifications");
define("_MI_PROFILE_TRIBEPOST_NEWTRIBEPOST_NOTIFY", "New post");
define("_MI_PROFILE_TRIBEPOST_NEWTRIBEPOST_NOTIFYCAP", "Notify me when a new post is submitted for this topic");
define("_MI_PROFILE_TRIBEPOST_NEWTRIBEPOST_NOTIFYSBJ", "New post in {TRIBETOPIC_TITLE}");
define("_MI_PROFILE_MAINTUBEW_TITLE", "Main video width");
define("_MI_PROFILE_MAINTUBEW_DESC", "Width of the video, which shows in the front page of the module");
define("_MI_PROFILE_MAINTUBEH_TITLE", "Main video height");
define("_MI_PROFILE_MAINTUBEH_DESC", "Height of the video, that shows in the front page of the module");
define("_MI_PROFILE_MYCONFIGS", "Settings");
define("_MI_PROFILE_TRIBETOPICSPERPAGE_TITLE", "Topics per page");
define("_MI_PROFILE_TRIBETOPICSPERPAGE_DESC"," Topics per page before pagination show up");
define("_MI_PROFILE_TRIBEPOSTSPERPAGE_TITLE", "Posts per page");
define("_MI_PROFILE_TRIBEPOSTSPERPAGE_DESC", "Posts per page before pagination show up");
define("_MI_PROFILE_ROWITEMS_TITLE", "Thumbnails per row");
define("_MI_PROFILE_ROWITEMS_DESC", "How many pictures, groups and group members per row in details view");
define("_MI_PROFILE_SEARCH", "Search Members");
define("_MI_PROFILE_ENABLEPICT_TITLE", "Enable pictures section");
define("_MI_PROFILE_ENABLEPICT_DESC", "");
define("_MI_PROFILE_ENABLEFRIENDS_TITLE", "Enable friends section");
define("_MI_PROFILE_ENABLEFRIENDS_DESC", "");
define("_MI_PROFILE_ENABLEVIDEOS_TITLE", "Enable videos section");
define("_MI_PROFILE_ENABLEVIDEOS_DESC", "");
define("_MI_PROFILE_ENABLETRIBES_TITLE", "Enable groups section");
define("_MI_PROFILE_ENABLETRIBES_DESC", "");
define("_MI_PROFILE_BLOCKS_FRIENDS", "My friends");
define("_MI_PROFILE_BLOCKS_USERMENU", "User Menu");
define("_MI_PROFILE_IMGORDER_TITLE", "Show latest picture first?");
define("_MI_PROFILE_IMGORDER_DESC", "");
define("_MI_PROFILE_ENABLEAUDIO_TITLE", "Enable audio section");
define("_MI_PROFILE_ENABLEAUDIO_DESC", "");
define("_MI_PROFILE_NUMBAUDIO_TITLE", "Number of audio files");
define("_MI_PROFILE_NUMBAUDIO_DESC", "Number of audio files a user can have in their page. '0' to deactivate check (not recommended).");
define("_MI_PROFILE_AUDIOSPERPAGE_TITLE", "Number of audio files per page");
define("_MI_PROFILE_NAME", "Profile");
define("_MI_PROFILE_DESC", "Module for managing custom user profile fields");
define("_MI_PROFILE_EDITACCOUNT", "Edit account");
define("_MI_PROFILE_CHANGEPASS", "Change password");
define("_MI_PROFILE_CHANGEMAIL", "Change email");
define("_MI_PROFILE_DELETEACCOUNT", "Delete account");
define("_MI_PROFILE_USERS", "Users");
define("_MI_PROFILE_PERMISSIONS", "Permissions");
define("_MI_PROFILE_FINDUSER", "Find users");

//Configuration categories
define("_MI_PROFILE_CAT_PERSONAL", "Personal");
define("_MI_PROFILE_CAT_MESSAGING", "Messaging");
define("_MI_PROFILE_CAT_SETTINGS1", "Settings");
define("_MI_PROFILE_CAT_COMMUNITY", "Community");
define("_MI_PROFILE_CAT_BASEINFO", "Basic information");
define("_MI_PROFILE_CAT_EXTINFO", "Complementary information");
define("_MI_PROFILE_CAT_SETTINGS", "General Settings");
define("_MI_PROFILE_CAT_SETTINGS_DSC", "");
define("_MI_PROFILE_CAT_USER", "User settings");
define("_MI_PROFILE_CAT_USER_DSC", "");

//Configuration items
define("_MI_PROFILE_PROFILE_SEARCH", "Show latest submissions by user on user profile");
define("_MI_PROFILE_SHOWEMPTY", "Show empty fields");
define("_MI_PROFILE_SHOWEMPTY_DESC", "If set to 'no', fields without a value will not show up on user profiles");

define("_MI_PROFILE_DISPNAME", "Name to display on index page");
define("_MI_PROFILE_DISPNAME_DESC", "");
define("_MI_PROFILE_BOTH", "Both");

define("_MI_PROFILE_GROUP_VIEW_DSC", "Annonymous users are showing up in this list, however, selecting them will not have any effect.");
define("_MI_PROFILE_PROFILE_SOCIAL", "Social profile manager");
define("_MI_PROFILE_PROFILE_SOCIAL_DESC", "Do you want to use this module as a social profile?");

define("_MI_PROFILE_VISIBILITY", "Visibility");
define("_MI_PROFILE_AUDIOS", "Audios");
define("_MI_PROFILE_TRIBES", "Groups");
define("_MI_PROFILE_PICTURES", "Pictures");
define("_MI_PROFILE_VIDEOS", "Videos");
define("_MI_PROFILE_TRIBEUSERS", "Memberships");

$group_list = icms::handler('icms_member')->getGroupList();
foreach ($group_list as $key => $group) if ($key > 1) define("_MI_PROFILE_GROUP_VIEW_".$key, $group." can view");
unset($group_list);
define("_MI_PROFILE_AUTOTASK_REACTIVATE_SUSPENDED_USERS", "Reactivate suspended users");
?>