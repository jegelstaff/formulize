<?php
/**
* banners version infomation
*
* This file holds the configuration information of this module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: icms_version.php 24563 2012-10-09 21:39:32Z skenow $
*/

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

/**  General Information  */
$modversion = array(
  'name'					=> _MI_BANNERS_MD_NAME,
  'version'					=> 1.1,
  'description'				=> _MI_BANNERS_MD_DESC,
  'author'					=> "Phoenyx, QM-B, Skenow",
  'credits'					=> "The ImpressCMS Project",
  'help'					=> "",
  'license'					=> "GNU General Public License (GPL)",
  'official'				=> 1,
  'dirname'					=> basename(dirname(__FILE__)),
  'modname'					=> 'banners',

/**  Images information  */
  'iconsmall'				=> "images/icon_small.png",
  'iconbig'					=> "images/icon_big.png",
  'image'					=> "images/icon_big.png", /* for backward compatibility */

/**  Development information */
  'status_version'			=> "Final",
  'status'					=> "Final",
  'date'					=> "9 October 2012",
  'author_word'				=> "ImpressCMS 1.3+ Only",
  'warning'					=> _CO_ICMS_WARNING_FINAL,

/** Menu information */
  'hasMain'					=> 1,

/** Administrative information */
  'hasAdmin'				=> 1,
  'adminindex'				=> "admin/index.php",
  'adminmenu'				=> "admin/menu.php",

/** Install and update informations */
  'onInstall'				=> "include/onupdate.inc.php",
  'onUpdate'				=> "include/onupdate.inc.php",
  'onUninstall'				=> "include/onupdate.inc.php",

/** Contributors */
  'developer_website_url'	=> "http://www.impresscms.de",
  'developer_website_name'	=> "ImpressCMS Germany",
  'developer_email'			=> "phoenyx@impresscms.de");

$modversion['people']['developers'][]		= "[url=http://community.impresscms.org/userinfo.php?uid=1168]Phoenyx[/url]";
//$modversion['people']['testers'][]		= "";
//$modversion['people']['translators'][]	= "";
//$modversion['people']['documenters'][]	= "";
//$modversion['people']['other'][]			= "";

/** Manual */
//$modversion['manual']['wiki'][]			= "<a href='http://wiki.impresscms.org/index.php?title=banners' target='_blank'>English</a>";

/** Database information */
$modversion['object_items'] = array('banner', 'client', 'position', 'positionlink', 'visiblein');
$modversion['tables'] = icms_getTablesArray($modversion['dirname'], $modversion['object_items']);

/** Menu */
$banners_client_handler = icms_getModuleHandler('client', $modversion['dirname'], 'banners', TRUE);
if (is_object($banners_client_handler) && $banners_client_handler->getUserClientId(TRUE) !== FALSE) {
	$modversion['sub'][0]['name'] = _SUBMIT;
	$modversion['sub'][0]['url'] = "banner.php?op=mod";
}

/** Templates information */
$modversion['templates'][1] = array(
	'file'        => 'banners_admin_client.html',
	'description' => _MI_BANNERS_CLIENTS.' (Admin)');

$modversion['templates'][] = array(
	'file'        => 'banners_admin_banner.html',
	'description' => _MI_BANNERS_BANNERS.' (Admin)');

$modversion['templates'][] = array(
	'file'        => 'banners_admin_position.html',
	'description' => _MI_BANNERS_POSITIONS.' (Admin)');

$modversion['templates'][] = array(
	'file'        => 'banners_banner.html',
	'description' => _MI_BANNERS_BANNERS);

$modversion['templates'][] = array(
	'file'        => 'banners_client.html',
	'description' => _MI_BANNERS_CLIENTS);

/** Configuration Items */
$modversion['config'][] = array(
	'name'			=> 'email_new_banner',
	'title'			=> '_MI_BANNERS_EMAIL_NEW_BANNER',
	'description'	=> '',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'		=> 1);

$modversion['config'][] = array(
	'name'			=> 'email_new_banner_subject',
	'title'			=> '_MI_BANNERS_EMAIL_NEW_BANNER_SUBJECT',
	'description'	=> '',
	'formtype'		=> 'textbox',
	'valuetype'		=> 'text',
	'default'		=> _MI_BANNERS_EMAIL_NEW_BANNER_SUBJECT_DEFAULT);

$modversion['config'][] = array(
	'name'			=> 'email_new_client',
	'title'			=> '_MI_BANNERS_EMAIL_NEW_CLIENT',
	'description'	=> '',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'		=> 1);

$modversion['config'][] = array(
	'name'			=> 'email_new_client_subject',
	'title'			=> '_MI_BANNERS_EMAIL_NEW_CLIENT_SUBJECT',
	'description'	=> '',
	'formtype'		=> 'textbox',
	'valuetype'		=> 'text',
	'default'		=> _MI_BANNERS_EMAIL_NEW_CLIENT_SUBJECT_DEFAULT);

$modversion['config'][] = array(
	'name'			=> 'maxfilesize',
	'title'			=> '_MI_BANNERS_MAXFILESIZE',
	'description'	=> '_MI_BANNERS_MAXFILESIZE_DSC',
	'formtype'		=> 'textbox',
	'valuetype'		=> 'int',
	'default'		=> 102400);

icms_loadLanguageFile($modversion['dirname'], 'common');
$modversion['config'][] = array(
	'name'			=> 'client_banner_types',
	'title'			=> '_MI_BANNERS_CLIENT_BANNER_TYPES',
	'description'	=> '_MI_BANNERS_CLIENT_BANNER_TYPES_DSC',
	'formtype'		=> 'select_multi',
	'valuetype'		=> 'array',
	'default'		=> array(1),
	'options'		=> array(_CO_BANNERS_BANNER_TYPE_IMAGE => 1,
					         _CO_BANNERS_BANNER_TYPE_HTML  => 2,
					         _CO_BANNERS_BANNER_TYPE_FLASH => 3));