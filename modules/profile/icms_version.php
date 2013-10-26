<?php
/**
 * Extended User Profile
 *
 * This file holds the configuration information of this module
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		modules
 * @since		1.2
 * @author		Jan Pedersen
 * @author		Marcello Brandao <marcello.brandao@gmail.com>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: icms_version.php 22692 2011-09-18 10:43:13Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

$modversion = array(
/**  General Information  */
	'name'						=> _MI_PROFILE_NAME,
	'version'					=> 2.0,
	'description'				=> _MI_PROFILE_DESC,
	'author'					=> "phoenyx, Jan Pedersen, Marcello Brandao, Sina Asghari, Gustavo Pilla.",
	'credits'					=> "The XOOPS Project, The ImpressCMS Project, The SmartFactory, Ackbarr, Komeia, vaughan, alfred.",
	'help'						=> "",
	'license'					=> "GNU General Public License (GPL)",
	'official'					=> 0,
	'dirname'					=> basename(dirname(__FILE__)),
	'modname'					=> 'profile',

/**  Images information  */
	'iconsmall'					=> "images/icon_small.png",
	'iconbig'					=> "images/icon_big.png",
	'image'						=> "images/icon_big.png", /* for backward compatibility */

/**  Development information */
	'status_version'			=> "Final",
	'status'					=> "Final",
	'date'						=> "18 Sept 2011",
	'author_word'				=> "",
	'warning'					=> _CO_ICMS_WARNING_FINAL,

/** Contributors */
	'developer_website_url'		=> "http://www.impresscms.org",
	'developer_website_name'	=> "The ImpressCMS Project",
	'developer_email'			=> "contact@impresscms.org",

/** Administrative information */
	'hasAdmin'					=> 1,
	'adminindex'				=> "admin/user.php",
	'adminmenu'					=> "admin/menu.php",

/** Install and update informations */
	'onInstall'					=> "include/onupdate.inc.php",
	'onUpdate'					=> "include/onupdate.inc.php",

/** Search information */
	'hasSearch'					=> 1,
	'search'					=> array('file' => "include/search.inc.php", 'func' => "profile_search"),

/** Comments */
	'hasComments'				=> 1,
	'comments'					=> array('itemName' => 'uid', 'pageName' => 'index.php'),

/** Menu information */
	'hasMain'					=> 1,

/** IPF object information */
	'object_items'				=> array('audio', 'category', 'configs', 'field', 'friendship', 'pictures', 'profile', 'regstep',
								         'tribepost', 'tribes', 'tribetopic', 'tribeuser', 'videos', 'visibility', 'visitors'));

$modversion['tables'] = icms_getTablesArray($modversion['dirname'], $modversion['object_items']);

$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=168]marcan[/url] (Marc-Andr&eacute; Lanciault)";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=392]stranger[/url] (Sina Asghari)";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=69]vaughan[/url]";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=54]skenow[/url]";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=10]sato-san[/url]";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=340]nekro[/url]";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=1168]phoenyx[/url]";
$modversion['people']['testers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=53]davidl2[/url]";
$modversion['people']['testers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=10]sato-san[/url]";
$modversion['people']['translators'][] = "[url=http://community.impresscms.org/userinfo.php?uid=10]sato-san[/url]";
$modversion['people']['translators'][] = "[url=http://community.impresscms.org/userinfo.php?uid=1168]phoenyx[/url]";
$modversion['people']['documenters'][] = "[url=http://community.impresscms.org/userinfo.php?uid=372]UnderDog[/url]";
//$modversion['people']['other'][] = "";

/** Manual */
$modversion['manual']['wiki'][] = "<a href='http://wiki.impresscms.org/index.php?title=Extended_Profile/"._LANGCODE."' target='_blank'>"._LANGNAME."</a>";

$i = 1;
global $icmsConfigUser;
if (is_object(icms::$module) && icms::$module->getVar('dirname') == $modversion['dirname']) {
	if (icms::$module->config['profile_social']) {
		$modversion['sub'][$i]['name'] = _MI_PROFILE_SEARCH;
		$modversion['sub'][$i]['url'] = "search.php";
	}
	if (icms::$user) {
		$i++;
		$modversion['sub'][$i]['name'] = _MI_PROFILE_EDITACCOUNT;
		$modversion['sub'][$i]['url'] = "edituser.php";
		$i++;
		$modversion['sub'][$i]['name'] = _MI_PROFILE_CHANGEPASS;
		$modversion['sub'][$i]['url'] = "changepass.php";
		if ($icmsConfigUser['allow_chgmail']) {
			$i++;
			$modversion['sub'][$i]['name'] = _MI_PROFILE_CHANGEMAIL;
			$modversion['sub'][$i]['url'] = "changemail.php";
		}
		if ($icmsConfigUser['self_delete']) {
			$i++;
			$modversion['sub'][$i]['name'] = _MI_PROFILE_DELETEACCOUNT;
			$modversion['sub'][$i]['url'] = "edituser.php?op=delete";
		}
		if (icms::$module->config['profile_social']) {
			$i++;
			$modversion['sub'][$i]['name'] = _MI_PROFILE_MYCONFIGS;
			$modversion['sub'][$i]['url'] = "configs.php";
		}
	}
}

/** Blocks information */
$modversion['blocks'][1] = array(
	'file'        => 'blocks.php',
	'name'        => _MI_PROFILE_BLOCKS_FRIENDS,
    'description' => '', 
	'show_func'   => 'b_profile_friends_show',
	'edit_func'   => 'b_profile_friends_edit',
	'options'     => '5',
	'template'    => 'profile_block_friends.html');

$modversion['blocks'][] = array(
	'file'        => 'blocks.php',
	'name'        => _MI_PROFILE_BLOCKS_USERMENU,
    'description' => '', 
	'show_func'   => 'b_profile_usermenu_show',
	'template'    => 'profile_block_usermenu.html');

/** Templates information */
$modversion['templates'] = array(
	array('file' => 'profile_admin_audio.html', 'description' => ''),
	array('file' => 'profile_admin_category.html', 'description' => ''),
	array('file' => 'profile_admin_field.html', 'description' => ''),
	array('file' => 'profile_admin_pictures.html', 'description' => ''),
	array('file' => 'profile_admin_regstep.html', 'description' => ''),
	array('file' => 'profile_admin_tribes.html', 'description' => ''),
	array('file' => 'profile_admin_tribeuser.html', 'description' => ''),
	array('file' => 'profile_admin_videos.html', 'description' => ''),
	array('file' => 'profile_admin_visibility.html', 'description' => ''),
	array('file' => 'profile_audio.html', 'description' => ''),
	array('file' => 'profile_changemail.html', 'description' => ''),
	array('file' => 'profile_changepass.html', 'description' => ''),
	array('file' => 'profile_configs.html', 'description' => ''),
	array('file' => 'profile_footer.html', 'description' => ''),
	array('file' => 'profile_friendship.html', 'description' => ''),
	array('file' => 'profile_header.html', 'description' => ''),
	array('file' => 'profile_index.html', 'description' => ''),
	array('file' => 'profile_pictures.html', 'description' => ''),
	array('file' => 'profile_register.html', 'description' => ''),
	array('file' => 'profile_requirements.html', 'description' => ''),
	array('file' => 'profile_results.html', 'description' => ''),
	array('file' => 'profile_search.html', 'description' => ''),
	array('file' => 'profile_tribes.html', 'description' => ''),
	array('file' => 'profile_userinfo.html', 'description' => ''),
	array('file' => 'profile_videos.html', 'description' => ''));

icms_loadLanguageFile('core', 'user');

/** Preferences categories */
$modversion['configcat'][] = array(
	'nameid'		=> 'settings',
	'name'			=> '_MI_PROFILE_CAT_USER',
	'description'	=> '_MI_PROFILE_CAT_SETTINGS_DSC');

$modversion['configcat'][] = array(
	'nameid'		=> 'user',
	'name'			=> '_MI_PROFILE_CAT_USER',
	'description'	=> '_MI_PROFILE_CAT_USER_DSC');

$modversion['configcat'][] = array(
	'nameid'		=> 'settings',
	'name'			=> '_MI_PROFILE_CAT_SETTINGS',
	'description'	=> '_MI_PROFILE_CAT_SETTINGS_DSC');

$modversion['configcat'][] = array(
	'nameid'		=> 'user',
	'name'			=> '_MI_PROFILE_CAT_USER',
	'description'	=> '_MI_PROFILE_CAT_USER_DSC');

/** Preferences information */
$modversion['config'][] = array(
	'name'			=> 'profile_social',
	'title'			=> '_MI_PROFILE_PROFILE_SOCIAL',
	'description'	=> '_MI_PROFILE_PROFILE_SOCIAL_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'		=> 1);

$modversion['config'][] = array(
	'name'			=> 'profile_search',
	'title'			=> '_MI_PROFILE_PROFILE_SEARCH',
	'description'	=> '_MI_PROFILE_PROFILE_SEARCH_DSC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'		=> 1,
	'category'		=> 'settings');


$modversion['config'][] = array(
	'name'			=> 'show_empty',
	'title'			=> '_MI_PROFILE_SHOWEMPTY',
	'description'	=> '_MI_PROFILE_SHOWEMPTY_DESC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'		=> 0,
	'category'		=> 'settings');

$modversion['config'][] = array(
	'name'			=> 'index_real_name',
	'title'			=> '_MI_PROFILE_DISPNAME',
	'description'	=> '_MI_PROFILE_DISPNAME_DESC',
	'formtype'		=> 'select',
	'valuetype'		=> 'text',
	'default'		=> 'nick',
	'category'		=> 'settings',
	'options'		=> array(_US_NICKNAME		=> 'nick',
							 _US_REALNAME		=> 'real',
							 _MI_PROFILE_BOTH	=> 'both'));

$group_list = icms::handler('icms_member')->getGroupList(new icms_db_criteria_Compo(new icms_db_criteria_Item('groupid', ICMS_GROUP_ADMIN, '!=')));
foreach (array_keys($group_list) as $groupid)
	$modversion['config'][] = array(
		'name'			=> 'view_group_'.$groupid,
		'title'			=> '_MI_PROFILE_GROUP_VIEW_'.$groupid,
		'description'	=> '_MI_PROFILE_GROUP_VIEW_DSC',
		'formtype'		=> 'group_multi',
		'valuetype'		=> 'array',
		'default'		=> ICMS_GROUP_USERS,
		'category'		=> 'other');
unset($group_list);

$modversion['config'][] = array(
	'name'			=> 'rowitems',
	'title'			=> '_MI_PROFILE_ROWITEMS_TITLE',
	'description'	=> '_MI_PROFILE_ROWITEMS_DESC',
	'default'		=> 5,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'enable_pictures',
	'title'			=> '_MI_PROFILE_ENABLEPICT_TITLE',
	'description'	=> '_MI_PROFILE_ENABLEPICT_DESC',
	'default'		=> 1,
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'nb_pict',
	'title'			=> '_MI_PROFILE_NUMBPICT_TITLE',
	'description'	=> '_MI_PROFILE_NUMBPICT_DESC',
	'default'		=> 12,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'thumb_width',
	'title'			=> '_MI_PROFILE_THUMW_TITLE',
	'description'	=> '_MI_PROFILE_THUMBW_DESC',
	'default'		=> 125,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'thumb_height',
	'title'			=> '_MI_PROFILE_THUMBH_TITLE',
	'description'	=> '_MI_PROFILE_THUMBH_DESC',
	'default'		=> 175,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'resized_width',
	'title'			=> '_MI_PROFILE_RESIZEDW_TITLE',
	'description'	=> '_MI_PROFILE_RESIZEDW_DESC',
	'default'		=> 650,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'resized_height',
	'title'			=> '_MI_PROFILE_RESIZEDH_TITLE',
	'description'	=> '_MI_PROFILE_RESIZEDH_DESC',
	'default'		=> 450,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'max_original_width',
	'title'			=> '_MI_PROFILE_ORIGINALW_TITLE',
	'description'	=> '_MI_PROFILE_ORIGINALW_DESC',
	'default'		=> 2048,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'max_original_height',
	'title'			=> '_MI_PROFILE_ORIGINALH_TITLE',
	'description'	=> '_MI_PROFILE_ORIGINALH_DESC',
	'default'		=> 1600,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'maxfilesize_picture',
	'title'			=> '_MI_PROFILE_MAXFILEBYTES_PICTURE_TITLE',
	'description'	=> '_MI_PROFILE_MAXFILEBYTES_PICTURE_DESC',
	'default'		=> 512000,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'picturesperpage',
	'title'			=> '_MI_PROFILE_PICTURESPERPAGE_TITLE',
	'description'	=> '_MI_PROFILE_PICTURESPERPAGE_DESC',
	'default'		=> 6,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'physical_delete',
	'title'			=> '_MI_PROFILE_DELETEPHYSICAL_TITLE',
	'description'	=> '_MI_PROFILE_DELETEPHYSICAL_DESC',
	'default'		=> 1,
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'images_order',
	'title'			=> '_MI_PROFILE_IMGORDER_TITLE',
	'description'	=> '_MI_PROFILE_IMGORDER_DESC',
	'default'		=> 1,
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'enable_friendship',
	'title'			=> '_MI_PROFILE_ENABLEFRIENDS_TITLE',
	'description'	=> '_MI_PROFILE_ENABLEFRIENDS_DESC',
	'default'		=> 1,
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'enable_audio',
	'title'			=> '_MI_PROFILE_ENABLEAUDIO_TITLE',
	'description'	=> '_MI_PROFILE_ENABLEAUDIO_DESC',
	'default'		=> 1,
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'nb_audio',
	'title'			=> '_MI_PROFILE_NUMBAUDIO_TITLE',
	'description'	=> '_MI_PROFILE_NUMBAUDIO_DESC',
	'default'		=> 12,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'audiosperpage',
	'title'			=> '_MI_PROFILE_AUDIOSPERPAGE_TITLE',
	'description'	=> '_MI_PROFILE_AUDIOSPERPAGE_DESC',
	'default'		=> 20,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'maxfilesize_audio',
	'title'			=> '_MI_PROFILE_MAXFILEBYTES_AUDIO_TITLE',
	'description'	=> '_MI_PROFILE_MAXFILEBYTES_AUDIO_DESC',
	'default'		=> 5242880,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'enable_videos',
	'title'			=> '_MI_PROFILE_ENABLEVIDEOS_TITLE',
	'description'	=> '_MI_PROFILE_ENABLEVIDEOS_DESC',
	'default'		=> 1,
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'videosperpage',
	'title'			=> '_MI_PROFILE_VIDEOSPERPAGE_TITLE',
	'description'	=> '_MI_PROFILE_VIDEOSPERPAGE_DESC',
	'default'		=> 6,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'width_tube',
	'title'			=> '_MI_PROFILE_TUBEW_TITLE',
	'description'	=> '_MI_PROFILE_TUBEW_DESC',
	'default'		=> 450,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'height_tube',
	'title'			=> '_MI_PROFILE_TUBEH_TITLE',
	'description'	=> '_MI_PROFILE_TUBEH_DESC',
	'default'		=> 350,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'width_maintube',
	'title'			=> '_MI_PROFILE_MAINTUBEW_TITLE',
	'description'	=> '_MI_PROFILE_MAINTUBEW_DESC',
	'default'		=> 250,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'height_maintube',
	'title'			=> '_MI_PROFILE_MAINTUBEH_TITLE',
	'description'	=> '_MI_PROFILE_MAINTUBEH_DESC',
	'default'		=> 210,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'enable_tribes',
	'title'			=> '_MI_PROFILE_ENABLETRIBES_TITLE',
	'description'	=> '_MI_PROFILE_ENABLETRIBES_DESC',
	'default'		=> 1,
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'tribetopicsperpage',
	'title'			=> '_MI_PROFILE_TRIBETOPICSPERPAGE_TITLE',
	'description'	=> '_MI_PROFILE_TRIBETOPICSPERPAGE_DESC',
	'default'		=> 10,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

$modversion['config'][] = array(
	'name'			=> 'tribepostsperpage',
	'title'			=> '_MI_PROFILE_TRIBEPOSTSPERPAGE_TITLE',
	'description'	=> '_MI_PROFILE_TRIBEPOSTSPERPAGE_DESC',
	'default'		=> 10,
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int');

/** Notification information */
$modversion['hasNotification'] = 1;

$modversion['notification'] = array(
	'lookup_file'		=> 'include/notification.inc.php',
	'lookup_func'		=> 'profile_iteminfo');

$modversion['notification']['category'][] = array(
	'name'				=> 'pictures',
	'title'				=> _MI_PROFILE_PICTURE_NOTIFYTIT,
	'description'		=> _MI_PROFILE_PICTURE_NOTIFYDSC,
	'subscribe_from'	=> 'pictures.php',
	'item_name'			=> 'uid',
	'allow_bookmark'	=> 0);

$modversion['notification']['event'][] = array(
	'name'				=> 'new_picture',
	'category'			=> 'pictures',
	'title'				=> _MI_PROFILE_PICTURE_NEWPIC_NOTIFY,
	'caption'			=> _MI_PROFILE_PICTURE_NEWPIC_NOTIFYCAP,
	'description'		=> '',
	'mail_template'		=> 'notify_pictures_new_picutre',
	'mail_subject'		=> _MI_PROFILE_PICTURE_NEWPIC_NOTIFYSBJ);

$modversion['notification']['event'][] = array(
	'name'				=> 'comment',
	'category'			=> 'pictures',
    'invisible'         => true);

$modversion['notification']['category'][] = array(
	'name'				=> 'videos',
	'title'				=> _MI_PROFILE_VIDEO_NOTIFYTIT,
	'description'		=> _MI_PROFILE_VIDEO_NOTIFYDSC,
	'subscribe_from'	=> 'videos.php',
	'item_name'			=> 'uid',
	'allow_bookmark'	=> 0);

$modversion['notification']['event'][] = array(
	'name'				=> 'new_video',
	'category'			=> 'videos',
	'title'				=> _MI_PROFILE_VIDEO_NEWVIDEO_NOTIFY,
	'caption'			=> _MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYCAP,
	'description'		=> '',
	'mail_template'		=> 'notify_videos_new_video',
	'mail_subject'		=> _MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYSBJ);

$modversion['notification']['event'][] = array(
	'name'				=> 'comment',
	'category'			=> 'videos',
    'invisible'         => true);

$modversion['notification']['category'][] = array(
	'name'				=> 'audio',
	'title'				=> _MI_PROFILE_AUDIO_NOTIFYTIT,
	'description'		=> _MI_PROFILE_AUDIO_NOTIFYDSC,
	'subscribe_from'	=> 'audio.php',
	'item_name'			=> 'uid',
	'allow_bookmark'	=> 0);

$modversion['notification']['event'][] = array(
	'name'				=> 'new_audio',
	'category'			=> 'audio',
	'title'				=> _MI_PROFILE_AUDIO_NEWAUDIO_NOTIFY,
	'caption'			=> _MI_PROFILE_AUDIO_NEWAUDIO_NOTIFYCAP,
	'description'		=> '',
	'mail_template'		=> 'notify_audio_new_audio',
	'mail_subject'		=> _MI_PROFILE_AUDIO_NEWAUDIO_NOTIFYSBJ);

$modversion['notification']['event'][] = array(
	'name'				=> 'comment',
	'category'			=> 'audio',
    'invisible'         => true);

$modversion['notification']['category'][] = array(
	'name'				=> 'tribetopic',
	'title'				=> _MI_PROFILE_TRIBETOPIC_NOTIFYTIT,
	'description'		=> _MI_PROFILE_TRIBETOPIC_NOTIFYDSC,
	'subscribe_from'	=> 'tribes.php',
	'item_name'			=> 'tribes_id',
	'allow_bookmark'	=> 0);

$modversion['notification']['event'][] = array(
	'name'				=> 'new_tribetopic',
	'category'			=> 'tribetopic',
	'title'				=> _MI_PROFILE_TRIBETOPIC_NEWTRIBETOPIC_NOTIFY,
	'caption'			=> _MI_PROFILE_TRIBETOPIC_NEWTRIBETOPIC_NOTIFYCAP,
	'description'		=> '',
	'mail_template'		=> 'notify_tribetopic_new_tribetopic',
	'mail_subject'		=> _MI_PROFILE_TRIBETOPIC_NEWTRIBETOPIC_NOTIFYSBJ);

$modversion['notification']['category'][] = array(
	'name'				=> 'tribepost',
	'title'				=> _MI_PROFILE_TRIBEPOST_NOTIFYTIT,
	'description'		=> _MI_PROFILE_TRIBEPOST_NOTIFYDSC,
	'subscribe_from'	=> 'tribes.php',
	'item_name'			=> 'topic_id',
	'allow_bookmark'	=> 0);

$modversion['notification']['event'][] = array(
	'name'				=> 'new_tribepost',
	'category'			=> 'tribepost',
	'title'				=> _MI_PROFILE_TRIBEPOST_NEWTRIBEPOST_NOTIFY,
	'caption'			=> _MI_PROFILE_TRIBEPOST_NEWTRIBEPOST_NOTIFYCAP,
	'description'		=> '',
	'mail_template'		=> 'notify_tribepost_new_tribepost',
	'mail_subject'		=> _MI_PROFILE_TRIBEPOST_NEWTRIBEPOST_NOTIFYSBJ);

// create autotask to reactivate suspended users (run every 6h)
$modversion['autotasks'][] = array(
	'enabled'	=> '1',
	'repeat'	=> '0',
	'interval'	=> '360',
	'onfinish'	=> '0',
	'name'		=> _MI_PROFILE_AUTOTASK_REACTIVATE_SUSPENDED_USERS,
	'code'		=> 'include/autotasks/reactivate_suspended.php');
?>