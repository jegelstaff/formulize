<?php
/**
 * Content version infomation
 *
 * This file holds the configuration information of this module
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: icms_version.php 22685 2011-09-18 10:01:52Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

$modversion = array(
/**  General Information  */
	'name'						=> _MI_CONTENT_MD_NAME,
	'version'					=> 1.1,
	'description'				=> _MI_CONTENT_MD_DESC,
	'author'					=> "Rodrigo P Lima aka TheRplima",
	'credits'					=> "The ImpressCMS Project",
	'help'						=> "",
	'license'					=> "GNU General Public License (GPL)",
	'official'					=> 1,
	'dirname'					=> basename(dirname(__FILE__)),
	'modname'					=> "content",

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
	'adminindex'				=> "admin/index.php",
	'adminmenu'					=> "admin/menu.php",

/** Install and update informations */
	'onInstall'					=> "include/onupdate.inc.php",
	'onUpdate'					=> "include/onupdate.inc.php",

/** Search information */
	'hasSearch'					=> 1,
	'search'					=> array('file' => "include/search.inc.php", 'func' => "content_search"),

/** Comments information */
	'hasComments'				=> 1,
	'comments'					=> array('itemName' => 'content_id', 'pageName' => 'content.php',
										 'callbackFile' => 'include/comment.inc.php',
										 'callback' => array('approve' => 'content_com_approve',
															 'update' => 'content_com_update')),

/** Menu information */
	'hasMain'					=> 1,

/** Database information */
	'object_items'				=> array('content'));

$modversion["tables"] = icms_getTablesArray($modversion['dirname'], $modversion['object_items']);

$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=106]Rodrigo P Lima aka TheRplima[/url]";
$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=106]phoenyx[/url]";
$modversion['people']['translators'][] = "[url=http://community.impresscms.org/userinfo.php?uid=106]phoenyx[/url]";
//$modversion['people']['testers'][] = "";
//$modversion['people']['documenters'][] = "";
//$modversion['people']['other'][] = "";

/** Manual */
$modversion['manual']['wiki'][] = "<a href='http://wiki.impresscms.org/index.php?title=content' target='_blank'>English</a>";

if (is_object(icms::$module) && icms::$module->getVar('dirname') == 'content') {
	$content_content_handler = icms_getModuleHandler('content', basename(dirname(__FILE__)), 'content');
	if ($content_content_handler->userCanSubmit()) {
		$modversion['sub'][1]['name'] = _MI_CONTENT_CONTENT_ADD;
		$modversion['sub'][1]['url'] = 'content.php?op=mod';
	}
}

/** Blocks information */
$modversion['blocks'][] = array(
	'file'			=> 'content_display.php',
	'name'			=> _MI_CONTENT_CONTENTDISPLAY,
	'description'	=> _MI_CONTENT_CONTENTDISPLAYDSC,
	'show_func'		=> 'content_content_display_show',
	'edit_func'		=> 'content_content_display_edit',
	'options'		=> '0|1|1|1',
	'template'		=> 'content_content_display.html');
$modversion['blocks'][] = array(
	'file'			=> 'content_menu.php',
	'name'			=> _MI_CONTENT_CONTENTMENU,
	'description'	=> _MI_CONTENT_CONTENTMENUDSC,
	'show_func'		=> 'content_content_menu_show',
	'edit_func'		=> 'content_content_menu_edit',
	'options'		=> 'content_title|ASC|1|#59ADDB|0',
	'template'		=> 'content_content_menu.html');

/** Templates information */
$modversion['templates'] = array(
	array('file' => 'content_header.html', 'description' => 'Module Header'),
	array('file' => 'content_footer.html', 'description' => 'Module Footer'),
	array('file' => 'content_admin_content.html', 'description' => 'Content Index'),
	array('file' => 'content_index.html', 'description' => 'Content Index'),
	array('file' => 'content_single_content.html', 'description' => 'Single content template'),
	array('file' => 'content_content.html', 'description' => 'Content page'),
	array('file' => 'content_requirements.html', 'description' => 'Content page'),
	array('file' => 'content_content_menu_structure.html', 'description' => 'Structure used to create recursive menu.'));

/** Preferences information */

$modversion['config'][] = array(
	'name'			=> 'default_page',
	'title'			=> '_MI_CONTENT_CONTPAGE',
	'description'	=> '_MI_CONTENT_CONTPAGEDSC',
	'formtype'		=> 'select_pages',
	'valuetype'		=> 'int',
	'default'		=>  '0');

$modversion['config'][] = array(
	'name'			=> 'poster_groups',
	'title'			=> '_MI_CONTENT_AUTHORGR',
	'description'	=> '_MI_CONTENT_AUTHORGRDSC',
	'formtype'		=> 'group_multi',
	'valuetype'		=> 'array',
	'default'		=> '1');

$modversion['config'][] = array(
	'name'			=> 'contents_limit',
	'title'			=> '_MI_CONTENT_LIMIT',
	'description'	=> '_MI_CONTENT_LIMITDSC',
	'formtype'		=> 'textbox',
	'valuetype'		=> 'text',
	'default'		=> 5);

$modversion['config'][] = array(
	'name'			=> 'show_breadcrumb',
	'title'			=> '_MI_CONTENT_SHOWBREADCRUMB',
	'description'	=> '_MI_CONTENT_SHOWBREADCRUMBDSC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'		=> 1);

$modversion['config'][] = array(
	'name'			=> 'show_relateds',
	'title'			=> '_MI_CONTENT_SHOWRELATEDS',
	'description'	=> '_MI_CONTENT_SHOWRELATEDSDSC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'		=> 1);

$modversion['config'][] = array(
	'name'			=> 'show_contentinfo',
	'title'			=> '_MI_CONTENT_SHOWINFO',
	'description'	=> '_MI_CONTENT_SHOWINFODSC',
	'formtype'		=> 'yesno',
	'valuetype'		=> 'int',
	'default'		=> 1);


/** Notification information */
$modversion['hasNotification'] = 1;

$modversion['notification'] = array(
	'lookup_file'		=> 'include/notification.inc.php',
	'lookup_func'		=> 'content_notify_iteminfo');

$modversion['notification']['category'][] = array (
	'name'				=> 'global',
	'title'				=> _MI_CONTENT_GLOBAL_NOTIFY,
	'description'		=> _MI_CONTENT_GLOBAL_NOTIFY_DSC,
	'subscribe_from'	=> array('index.php', 'content.php'));

$modversion['notification']['event'][] = array(
	'name'				=> 'content_published',
	'category'			=> 'global',
	'title'				=> _MI_CONTENT_GLOBAL_CONTENT_PUBLISHED_NOTIFY,
	'caption'			=> _MI_CONTENT_GLOBAL_CONTENT_PUBLISHED_NOTIFY_CAP,
	'description'		=> _MI_CONTENT_GLOBAL_CONTENT_PUBLISHED_NOTIFY_DSC,
	'mail_template'		=> 'global_content_published',
	'mail_subject'		=> _MI_CONTENT_GLOBAL_CONTENT_PUBLISHED_NOTIFY_SBJ);