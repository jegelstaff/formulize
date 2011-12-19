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
* @version		$Id$
*/

if (!defined("ICMS_ROOT_PATH")) die("ICMS root path not defined");

/**  General Information  */
$modversion = array(
  'name'=> _MI_CONTENT_MD_NAME,
  'version'=> 1.0,
  'description'=> _MI_CONTENT_MD_DESC,
  'author'=> "Rodrigo P Lima aka TheRplima",
  'credits'=> "The ImpressCMS Project",
  'help'=> "",
  'license'=> "GNU General Public License (GPL)",
  'official'=> 0,
  'dirname'=> basename( dirname( __FILE__ ) ),

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
  'developer_website_url' => "http://www.rodrigoplima.com",
  'developer_website_name' => "",
  'developer_email' => "therplima@impresscms.org");

$modversion['people']['developers'][] = "[url=http://community.impresscms.org/userinfo.php?uid=106]Rodrigo P Lima aka TheRplima[/url]";
//$modversion['people']['testers'][] = "";
//$modversion['people']['translators'][] = "";
//$modversion['people']['documenters'][] = "";
//$modversion['people']['other'][] = "";

/** Manual */
$modversion['manual']['wiki'][] = "<a href='http://wiki.impresscms.org/index.php?title=content' target='_blank'>English</a>";

$modversion['warning'] = _CO_ICMS_WARNING_FINAL;

/** Administrative information */
$modversion['hasAdmin'] = 1;
$modversion['adminindex'] = "admin/index.php";
$modversion['adminmenu'] = "admin/menu.php";

/** Database information */
$modversion['object_items'][1] = 'content';

$modversion["tables"] = icms_getTablesArray($modversion['dirname'], $modversion['object_items']);

/** Install and update informations */
$modversion['onInstall'] = "include/onupdate.inc.php";
$modversion['onUpdate'] = "include/onupdate.inc.php";

/** Search information */
$modversion['hasSearch'] = 1;
$modversion['search'] = array (
  'file' => "include/search.inc.php",
  'func' => "content_search");

/** Menu information */
$modversion['hasMain'] = 1;
global $xoopsModule;
if (is_object($xoopsModule) && $xoopsModule->dirname() == 'content') {
	$content_content_handler = xoops_getModuleHandler('content', 'content');
	if ($content_content_handler->userCanSubmit()) {
		$modversion['sub'][1]['name'] = _MI_CONTENT_CONTENT_ADD;
		$modversion['sub'][1]['url'] = 'content.php?op=mod';
	}
}

/** Blocks information */
$modversion['blocks'][1] = array(
  'file' => 'content_display.php',
  'name' => _MI_CONTENT_CONTENTDISPLAY,
  'description' => _MI_CONTENT_CONTENTDISPLAYDSC,
  'show_func' => 'content_content_display_show',
  'edit_func' => 'content_content_display_edit',
  'options' => '0|1|1|1',
  'template' => 'content_content_display.html');
$modversion['blocks'][] = array(
  'file' => 'content_menu.php',
  'name' => _MI_CONTENT_CONTENTMENU,
  'description' => _MI_CONTENT_CONTENTMENUDSC,
  'show_func' => 'content_content_menu_show',
  'edit_func' => 'content_content_menu_edit',
  'options' => 'content_title|ASC|1|#59ADDB|0',
  'template' => 'content_content_menu.html');
/*
$modversion['blocks'][] = array(
  'file' => 'post_by_month.php',
  'name' => _MI_CONTENT_POSTBYMONTH,
  'description' => _MI_CONTENT_POSTBYMONTHDSC,
  'show_func' => 'content_post_by_month_show',
  'edit_func' => 'content_post_by_month_edit',
  'options' => '',
  'template' => 'content_post_by_month.html');
*/

/** Templates information */
$modversion['templates'][1] = array(
  'file' => 'content_header.html',
  'description' => 'Module Header');

$modversion['templates'][] = array( 
  'file' => 'content_footer.html',
  'description' => 'Module Footer');

$modversion['templates'][]= array(
  'file' => 'content_admin_content.html',
  'description' => 'Content Index');

$modversion['templates'][] = array(
  'file' => 'content_index.html',
  'description' => 'Content Index');

$modversion['templates'][] = array(
  'file' => 'content_single_content.html',
  'description' => 'Single content template');

$modversion['templates'][] = array(
  'file' => 'content_content.html',
  'description' => 'Content page');

$modversion['templates'][] = array(
  'file' => 'content_content_menu_structure.html',
  'description' => 'Structure used to create recursive menu.');

/** Preferences information */
// Retrieve the group user list, because the automatic group_multi config formtype does not include Anonymous group :-(
$member_handler =& xoops_getHandler('member');
$groups_array = $member_handler->getGroupList();
foreach($groups_array as $k=>$v) {
	$select_groups_options[$v] = $k;
}

$modversion['config'][1] = array(
  'name' => 'poster_groups',
  'title' => '_MI_CONTENT_CONTPAGE',
  'description' => '_MI_CONTENT_CONTPAGEDSC',
  'formtype' => 'select_pages',
  'valuetype' => 'int',
  'default' =>  '0');

$modversion['config'][] = array(
  'name' => 'poster_groups',
  'title' => '_MI_CONTENT_AUTHORGR',
  'description' => '_MI_CONTENT_AUTHORGRDSC',
  'formtype' => 'select_multi',
  'valuetype' => 'array',
  'options' => $select_groups_options,
  'default' =>  '1');

$modversion['config'][] = array(
  'name' => 'contents_limit',
  'title' => '_MI_CONTENT_LIMIT',
  'description' => '_MI_CONTENT_LIMITDSC',
  'formtype' => 'textbox',
  'valuetype' => 'text',
  'default' => 5);

$modversion['config'][] = array(
  'name' => 'show_breadcrumb',
  'title' => '_MI_CONTENT_SHOWBREADCRUMB',
  'description' => '_MI_CONTENT_SHOWBREADCRUMBDSC',
  'formtype' => 'yesno',
  'valuetype' => 'int',
  'default' => 1);

$modversion['config'][] = array(
  'name' => 'show_relateds',
  'title' => '_MI_CONTENT_SHOWRELATEDS',
  'description' => '_MI_CONTENT_SHOWRELATEDSDSC',
  'formtype' => 'yesno',
  'valuetype' => 'int',
  'default' => 1);

$modversion['config'][] = array(
  'name' => 'show_contentinfo',
  'title' => '_MI_CONTENT_SHOWINFO',
  'description' => '_MI_CONTENT_SHOWINFODSC',
  'formtype' => 'yesno',
  'valuetype' => 'int',
  'default' => 1);

/** Comments information */
$modversion['hasComments'] = 1;

$modversion['comments'] = array(
  'itemName' => 'content_id',
  'pageName' => 'content.php',
  /* Comment callback functions */
  'callbackFile' => 'include/comment.inc.php',
  'callback' => array(
    'approve' => 'content_com_approve',
    'update' => 'content_com_update')
    );

/** Notification information */
$modversion['hasNotification'] = 1;

$modversion['notification'] = array (
  'lookup_file' => 'include/notification.inc.php',
  'lookup_func' => 'content_notify_iteminfo');

$modversion['notification']['category'][1] = array (
  'name' => 'global',
  'title' => _MI_CONTENT_GLOBAL_NOTIFY,
  'description' => _MI_CONTENT_GLOBAL_NOTIFY_DSC,
  'subscribe_from' => array('index.php', 'content.php'));

$modversion['notification']['event'][1] = array(
  'name' => 'content_published',
  'category'=> 'global',
  'title'=> _MI_CONTENT_GLOBAL_CONTENT_PUBLISHED_NOTIFY,
  'caption'=> _MI_CONTENT_GLOBAL_CONTENT_PUBLISHED_NOTIFY_CAP,
  'description'=> _MI_CONTENT_GLOBAL_CONTENT_PUBLISHED_NOTIFY_DSC,
  'mail_template'=> 'global_content_published',
  'mail_subject'=> _MI_CONTENT_GLOBAL_CONTENT_PUBLISHED_NOTIFY_SBJ);

?>