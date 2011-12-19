<?php
/**
 * Extended User Profile
 *
 * This file holds the configuration information of this module
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license	LICENSE.txt
 * @license	GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package	modules
 * @since	1.2
 * @author	Jan Pedersen
 * @author	Marcello Brandao <marcello.brandao@gmail.com>
 * @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id$
 */

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

/**  General Information  */
$modversion = array(
	'name'=> _PROFILE_MI_NAME,
	'version'=> 1.3,
	'description'=> _PROFILE_MI_DESC,
	'author'=> "Jan Pedersen, Marcello Brandao, Sina Asghari, Gustavo Pilla.",
	'credits'=> "The XOOPS Project, The ImpressCMS Project, The SmartFactory, Ackbarr, Komeia, vaughan, alfred.",
	'help'=> "",
	'license'=> "GNU General Public License (GPL)",
	'official'=> 0,
	'dirname'=> basename( dirname( __FILE__ ) ),
	'modname' => 'profile',

/**  Images information  */
	'iconsmall'=> "images/icon_small.png",
	'iconbig'=> "images/icon_big.png",
	'image'=> "images/icon_big.png", /* for backward compatibility */

/**  Development information */
	'status_version'=> "Final",
	'status'=> "Final",
	'date'=> "",
	'author_word'=> "",

/** Contributors */
	'developer_website_url' => "http://www.impresscms.org",
	'developer_website_name' => "ImpressCMS Core & Module developpers",
	'developer_email' => "contact@impresscms.org");
/**
 *
 * Method to be implemented by IPF as soon as module is working based on IPF!
 *
 */

$i = 0;

$i++;
$modversion['object_items'][$i] = 'pictures';
$i++;
$modversion['object_items'][$i] = 'friendship';
$i++;
$modversion['object_items'][$i] = 'visitors';
$i++;
$modversion['object_items'][$i] = 'videos';
$i++;
$modversion['object_items'][$i] = 'tribes';
$i++;
$modversion['object_items'][$i] = 'tribeuser';
$i++;
$modversion['object_items'][$i] = 'tribetopic';
$i++;
$modversion['object_items'][$i] = 'tribepost';
$i++;
$modversion['object_items'][$i] = 'configs';
$i++;
$modversion['object_items'][$i] = 'audio';
$i++;
$modversion['object_items'][$i] = 'category';
$i++;
$modversion['object_items'][$i] = 'profile';
$i++;
$modversion['object_items'][$i] = 'field';
$i++;
$modversion['object_items'][$i] = 'visibility';
$i++;
$modversion['object_items'][$i] = 'regstep';
$modversion["tables"] = icms_getTablesArray($modversion['dirname'], $modversion['object_items']);

$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=168]marcan[/url] (Marc-Andr&eacute; Lanciault)";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=392]stranger[/url] (Sina Asghari)";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=69]vaughan[/url]";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=54]skenow[/url]";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=10]sato-san[/url]";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=340]nekro[/url]";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=1168]Phoenyx[/url]";
$modversion['people']['testers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=53]davidl2[/url]";
$modversion['people']['testers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=392]stranger[/url] (Sina Asghari)";
$modversion['people']['testers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=10]sato-san[/url]";
$modversion['people']['translators'][] = "";
$modversion['people']['documenters'][] = "[url=http://community.impresscms.org/userinfo.php?uid=372]UnderDog[/url]";
//$modversion['people']['other'][] = "";

/** Manual */
$modversion['manual']['wiki'][] = "<a href='http://wiki.impresscms.org/index.php?title=Extended_Profile/"._LANGCODE."' target='_blank'>"._LANGNAME."</a>";

$modversion['warning'] = _CO_ICMS_WARNING_FINAL;

/** Administrative information */
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/user.php";
$modversion['adminmenu'] = "admin/menu.php";

/** Install and update informations */
$modversion['onInstall'] = "include/onupdate.inc.php";
$modversion['onUpdate'] = "include/onupdate.inc.php";

/** Search information */
$modversion['hasSearch'] = 0;
$modversion['search'] = array (
  'file' => "include/search.inc.php",
  'func' => "profile_search");

/** Menu information */
$modversion['hasMain'] = 1;

$i = 1;
global $icmsModule, $icmsModuleConfig, $icmsUser;
if (is_object($icmsModule) && $icmsModule->dirname() == $modversion['dirname']) {
	$modversion['sub'][$i]['name'] = _MI_PROFILE_SEARCH;
	$modversion['sub'][$i]['url'] = "search.php";
	if ($icmsUser) {
		$i++;
		$modversion['sub'][$i]['name'] = _PROFILE_MI_EDITACCOUNT;
		$modversion['sub'][$i]['url'] = "edituser.php";
		$i++;
		$modversion['sub'][$i]['name'] = _PROFILE_MI_CHANGEPASS;
		$modversion['sub'][$i]['url'] = "changepass.php";
		if (isset($icmsModuleConfig) && isset($icmsModuleConfig['allow_chgmail']) && $icmsModuleConfig['allow_chgmail'] == 1) {
			$i++;
			$modversion['sub'][$i]['name'] = _PROFILE_MI_CHANGEMAIL;
			$modversion['sub'][$i]['url'] = "changemail.php";
		}
		if($icmsModuleConfig['profile_social']==1){
			$i++;
			$modversion['sub'][$i]['name'] = _MI_PROFILE_MYCONFIGS;
			$modversion['sub'][$i]['url'] = "configs.php";
		}
	}
}

/** Blocks information */
$modversion['blocks'][1] = array(
  'file' => 'blocks.php',
  'name' => _MI_PROFILE_FRIENDS,
  'description' => _MI_PROFILE_FRIENDS_DESC,
  'show_func' => 'b_profile_friends_show',
  'edit_func' => 'b_profile_friends_edit',
  'options' => '5',
  'template' => 'profile_block_friends.html');

/** Templates information */
$modversion['templates'][] = array(
  'file' => 'profile_index.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_audio.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_header.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_tribes.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_configs.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_footer.html',
  'description' => '');
$modversion['templates'][] = array(
  'file' => 'profile_search.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_results.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_friendship.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_notifications.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_videos.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_profileform.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_userinfo.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_admin_visibility.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_register.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_register.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_changepass.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_report.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_admin_field.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_admin_regstep.html',
  'description' => '');

$modversion['templates'][] = array(
  'file' => 'profile_admin_category.html',
  'description' => '');

$modversion['templates'][]= array(
  'file' => 'profile_admin_tribes.html',
  'description' => 'tribes Admin Index');

$modversion['templates'][]= array(
  'file' => 'profile_admin_audio.html',
  'description' => 'audio Admin Index');

$modversion['templates'][]= array(
  'file' => 'profile_tribes.html',
  'description' => 'tribes Index');

$modversion['templates'][]= array(
  'file' => 'profile_admin_pictures.html',
  'description' => 'pictures Admin Index');

$modversion['templates'][]= array(
  'file' => 'profile_pictures.html',
  'description' => 'pictures Index');

$modversion['templates'][]= array(
  'file' => 'profile_admin_videos.html',
  'description' => 'videos Admin Index');

$modversion['templates'][]= array(
  'file' => 'profile_admin_tribeuser.html',
  'description' => 'tribeuser Admin Index');

/** Preferences categories */
$modversion['configcat'][1] = array(
  'nameid' => 'settings',
  'name' => '_PROFILE_MI_CAT_USER',
  'description' => '_PROFILE_MI_CAT_SETTINGS_DSC');

$modversion['configcat'][] = array(
  'nameid' => 'user',
  'name' => '_PROFILE_MI_CAT_USER',
  'description' => '_PROFILE_MI_CAT_USER_DSC');

// Config categories
$modversion['configcat'][1]['nameid'] = 'settings';
$modversion['configcat'][1]['name'] = '_PROFILE_MI_CAT_SETTINGS';
$modversion['configcat'][1]['description'] = '_PROFILE_MI_CAT_SETTINGS_DSC';

$modversion['configcat'][2]['nameid'] = 'user';
$modversion['configcat'][2]['name'] = '_PROFILE_MI_CAT_USER';
$modversion['configcat'][2]['description'] = '_PROFILE_MI_CAT_USER_DSC';
/** Preferences information */

$i = 1;
$modversion['config'][$i]['name'] = 'profile_social';
$modversion['config'][$i]['title'] = '_PROFILE_MI_PROFILE_SOCIAL';
$modversion['config'][$i]['description'] = '_PROFILE_MI_PROFILE_SOCIAL_DESC';
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$modversion['config'][$i]['default'] = 1;

$i++;
$modversion['config'][$i]['name'] = 'profile_search';
$modversion['config'][$i]['title'] = '_PROFILE_MI_PROFILE_SEARCH';
$modversion['config'][$i]['description'] = '_PROFILE_MI_PROFILE_SEARCH_DSC';
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$modversion['config'][$i]['default'] = 1;
$modversion['config'][$i]['category'] = 'settings';

$i++;
$modversion['config'][$i]['name'] = 'show_empty';
$modversion['config'][$i]['title'] = '_PROFILE_MI_SHOWEMPTY';
$modversion['config'][$i]['description'] = '_PROFILE_MI_SHOWEMPTY_DESC';
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$modversion['config'][$i]['default'] = 0;
$modversion['config'][$i]['category'] = 'settings';

//real name disp
$i++;
$modversion['config'][$i]['name'] = 'index_real_name';
$modversion['config'][$i]['title'] = '_PROFILE_MI_DISPNAME';
$modversion['config'][$i]['description'] = '_PROFILE_MI_DISPNAME_DESC';
$modversion['config'][$i]['formtype'] = 'select';
$modversion['config'][$i]['valuetype'] = 'text';
$modversion['config'][$i]['default'] = 'nick';
$modversion['config'][$i]['category'] = 'settings';
$modversion['config'][$i]['options'] = array(_PROFILE_MI_NICKNAME  => 'nick',
										_PROFILE_MI_REALNAME  => 'real',
										_PROFILE_MI_BOTH  => 'both');

$member_handler = &xoops_gethandler('member');
$criteria = new CriteriaCompo();
$criteria->add(new Criteria('groupid', ICMS_GROUP_ANONYMOUS, '!='));
$group_list = &$member_handler->getGroupList($criteria);
foreach ($group_list as $key=>$group) $groups[$group] = $key;

$i++;
$modversion['config'][$i]['name'] = 'view_group_anonymous';
$modversion['config'][$i]['title'] = '_PROFILE_MI_GROUP_VIEW_ANONYMOUS';
$modversion['config'][$i]['description'] = '_PROFILE_MI_GROUP_VIEW_DSC';
$modversion['config'][$i]['formtype'] = 'select_multi';
$modversion['config'][$i]['valuetype'] = 'array';
$modversion['config'][$i]['options'] = $groups;
$modversion['config'][$i]['default'] = $groups;
$modversion['config'][$i]['category'] = 'other';

$i++;
$modversion['config'][$i]['name'] = 'view_group_registered';
$modversion['config'][$i]['title'] = '_PROFILE_MI_GROUP_VIEW_REGISTERED';
$modversion['config'][$i]['description'] = '_PROFILE_MI_GROUP_VIEW_DSC';
$modversion['config'][$i]['formtype'] = 'select_multi';
$modversion['config'][$i]['valuetype'] = 'array';
$modversion['config'][$i]['options'] = $groups;
$modversion['config'][$i]['default'] = $groups;
$modversion['config'][$i]['category'] = 'other';

foreach ($groups as $groupid) {
	if($groupid > 3){
		$i++;
		$modversion['config'][$i]['name'] = 'view_group_'.$groupid;
		$modversion['config'][$i]['title'] = '_PROFILE_MI_GROUP_VIEW_'.$groupid;
		$modversion['config'][$i]['description'] = '_PROFILE_MI_GROUP_VIEW_DSC';
		$modversion['config'][$i]['formtype'] = 'select_multi';
		$modversion['config'][$i]['valuetype'] = 'array';
		$modversion['config'][$i]['options'] = $groups;
		$modversion['config'][$i]['default'] = $groups;
		$modversion['config'][$i]['category'] = 'other';
	}
}
$i++;
$modversion['config'][$i]['name'] = 'enable_pictures';
$modversion['config'][$i]['title'] = '_MI_PROFILE_ENABLEPICT_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_ENABLEPICT_DESC';
$modversion['config'][$i]['default'] = 1;
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'nb_pict';
$modversion['config'][$i]['title'] = '_MI_PROFILE_NUMBPICT_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_NUMBPICT_DESC';
$modversion['config'][$i]['default'] = 12;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'thumb_width';
$modversion['config'][$i]['title'] = '_MI_PROFILE_THUMW_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_THUMBW_DESC';
$modversion['config'][$i]['default'] = 125;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'thumb_height';
$modversion['config'][$i]['title'] = '_MI_PROFILE_THUMBH_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_THUMBH_DESC';
$modversion['config'][$i]['default'] = 175;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'resized_width';
$modversion['config'][$i]['title'] = '_MI_PROFILE_RESIZEDW_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_RESIZEDW_DESC';
$modversion['config'][$i]['default'] = 650;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'resized_height';
$modversion['config'][$i]['title'] = '_MI_PROFILE_RESIZEDH_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_RESIZEDH_DESC';
$modversion['config'][$i]['default'] = 450;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'max_original_width';
$modversion['config'][$i]['title'] = '_MI_PROFILE_ORIGINALW_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_ORIGINALW_DESC';
$modversion['config'][$i]['default'] = 2048;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'max_original_height';
$modversion['config'][$i]['title'] = '_MI_PROFILE_ORIGINALH_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_ORIGINALH_DESC';
$modversion['config'][$i]['default'] = 1600;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'maxfilesize_picture';
$modversion['config'][$i]['title'] = '_MI_PROFILE_MAXFILEBYTES_PICTURE_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_MAXFILEBYTES_PICTURE_DESC';
$modversion['config'][$i]['default'] = 512000;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'picturesperpage';
$modversion['config'][$i]['title'] = '_MI_PROFILE_PICTURESPERPAGE_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_PICTURESPERPAGE_DESC';
$modversion['config'][$i]['default'] = 6;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'physical_delete';
$modversion['config'][$i]['title'] = '_MI_PROFILE_DELETEPHYSICAL_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_DELETEPHYSICAL_DESC';
$modversion['config'][$i]['default'] = 1;
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'images_order';
$modversion['config'][$i]['title'] = '_MI_PROFILE_IMGORDER_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_IMGORDER_DESC';
$modversion['config'][$i]['default'] = 1;
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'enable_friendship';
$modversion['config'][$i]['title'] = '_MI_PROFILE_ENABLEFRIENDS_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_ENABLEFRIENDS_DESC';
$modversion['config'][$i]['default'] = 1;
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'friendsperpage';
$modversion['config'][$i]['title'] = '_MI_PROFILE_FRIENDSPERPAGE_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_FRIENDSPERPAGE_DESC';
$modversion['config'][$i]['default'] = 12;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'enable_audio';
$modversion['config'][$i]['title'] = '_MI_PROFILE_ENABLEAUDIO_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_ENABLEAUDIO_DESC';
$modversion['config'][$i]['default'] = 1;
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'nb_audio';
$modversion['config'][$i]['title'] = '_MI_PROFILE_NUMBAUDIO_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_NUMBAUDIO_DESC';
$modversion['config'][$i]['default'] = 12;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'audiosperpage';
$modversion['config'][$i]['title'] = '_MI_PROFILE_AUDIOSPERPAGE_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_AUDIOSPERPAGE_DESC';
$modversion['config'][$i]['default'] = 20;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'maxfilesize_audio';
$modversion['config'][$i]['title'] = '_MI_PROFILE_MAXFILEBYTES_AUDIO_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_MAXFILEBYTES_AUDIO_DESC';
$modversion['config'][$i]['default'] = 5242880;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'enable_videos';
$modversion['config'][$i]['title'] = '_MI_PROFILE_ENABLEVIDEOS_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_ENABLEVIDEOS_DESC';
$modversion['config'][$i]['default'] = 1;
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'videosperpage';
$modversion['config'][$i]['title'] = '_MI_PROFILE_VIDEOSPERPAGE_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_VIDEOSPERPAGE_DESC';
$modversion['config'][$i]['default'] = 6;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;


$modversion['config'][$i]['name'] = 'width_tube';
$modversion['config'][$i]['title'] = '_MI_PROFILE_TUBEW_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_TUBEW_DESC';
$modversion['config'][$i]['default'] = 450;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'height_tube';
$modversion['config'][$i]['title'] = '_MI_PROFILE_TUBEH_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_TUBEH_DESC';
$modversion['config'][$i]['default'] = 350;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'width_maintube';
$modversion['config'][$i]['title'] = '_MI_PROFILE_MAINTUBEW_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_MAINTUBEW_DESC';
$modversion['config'][$i]['default'] = 250;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'height_maintube';
$modversion['config'][$i]['title'] = '_MI_PROFILE_MAINTUBEH_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_MAINTUBEH_DESC';
$modversion['config'][$i]['default'] = 210;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'enable_tribes';
$modversion['config'][$i]['title'] = '_MI_PROFILE_ENABLETRIBES_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_ENABLETRIBES_DESC';
$modversion['config'][$i]['default'] = 1;
$modversion['config'][$i]['formtype'] = 'yesno';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'tribesperpage';
$modversion['config'][$i]['title'] = '_MI_PROFILE_TRIBESPERPAGE_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_TRIBESPERPAGE_DESC';
$modversion['config'][$i]['default'] = 6;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'tribetopicsperpage';
$modversion['config'][$i]['title'] = '_MI_PROFILE_TRIBETOPICSPERPAGE_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_TRIBETOPICSPERPAGE_DESC';
$modversion['config'][$i]['default'] = 10;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';
$i++;
$modversion['config'][$i]['name'] = 'tribepostsperpage';
$modversion['config'][$i]['title'] = '_MI_PROFILE_TRIBEPOSTSPERPAGE_TITLE';
$modversion['config'][$i]['description'] = '_MI_PROFILE_TRIBEPOSTSPERPAGE_DESC';
$modversion['config'][$i]['default'] = 10;
$modversion['config'][$i]['formtype'] = 'textbox';
$modversion['config'][$i]['valuetype'] = 'int';

// Comments
$modversion['hasComments'] = 1;
$modversion['comments']['itemName'] = 'uid';
$modversion['comments']['pageName'] = 'index.php';

/** Notification information */
$modversion['hasNotification'] = 1;

$modversion['notification'] = array (
  'lookup_file' => 'include/notification.inc.php',
  'lookup_func' => 'profile_iteminfo');

$modversion['notification']['category'][1] = array (
  'name' => 'picture',
  'title' => _MI_PROFILE_PICTURE_NOTIFYTIT,
  'description' => _MI_PROFILE_PICTURE_NOTIFYDSC,
  'subscribe_from' => 'pictures.php',
  'item_name' => 'uid',
  'allow_bookmark' => 1 );

$modversion['notification']['event'][1] = array(
  'name' => 'new_picture',
  'category'=> 'picture',
  'title'=> _MI_PROFILE_PICTURE_NEWPIC_NOTIFY,
  'caption'=> _MI_PROFILE_PICTURE_NEWPIC_NOTIFYCAP,
  'description'=> _MI_PROFILE_PICTURE_NEWPOST_NOTIFYDSC,
  'mail_template'=> 'picture_newpic_notify',
  'mail_subject'=> _MI_PROFILE_PICTURE_NEWPIC_NOTIFYSBJ);

$modversion['notification']['category'][2] = array (
  'name' => 'videos',
  'title' => _MI_PROFILE_VIDEO_NOTIFYTIT,
  'description' => _MI_PROFILE_VIDEO_NOTIFYDSC,
  'subscribe_from' => 'videos.php',
  'item_name' => 'uid',
  'allow_bookmark' => 1 );

$modversion['notification']['event'][2] = array(
  'name' => 'new_video',
  'category'=> 'videos',
  'title'=> _MI_PROFILE_VIDEO_NEWVIDEO_NOTIFY,
  'caption'=> _MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYCAP,
  'description'=> _MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYDSC,
  'mail_template'=> 'video_newvideo_notify',
  'mail_subject'=> _MI_PROFILE_VIDEO_NEWVIDEO_NOTIFYSBJ);

/*
$modversion['notification']['category'][4] = array (
  'name' => 'friendship',
  'title' => _MI_PROFILE_FRIENDSHIP_NOTIFYTIT,
  'description' => _MI_PROFILE_FRIENDSHIP_NOTIFYDSC,
  'subscribe_from' => 'friends.php',
  'item_name' => 'uid',
  'allow_bookmark' => 0 );

$modversion['notification']['event'][4] = array(
  'name' => 'new_friendship',
  'category'=> 'friendship',
  'title'=> _MI_PROFILE_FRIEND_NEWPETITION_NOTIFY,
  'caption'=> _MI_PROFILE_FRIEND_NEWPETITION_NOTIFYCAP,
  'description'=> _MI_PROFILE_FRIEND_NEWPETITION_NOTIFYDSC,
  'mail_template'=> 'friendship_newpetition_notify',
  'mail_subject'=> _MI_PROFILE_FRIEND_NEWPETITION_NOTIFYSBJ);
*/
?>
