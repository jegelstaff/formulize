<?php
/**
 * English language constants commonly used in the module
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: common.php 20051 2010-08-28 16:30:42Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

// content
define("_CO_CONTENT_CONTENT_CONTENT_PID", "Parent Page");
define("_CO_CONTENT_CONTENT_CONTENT_PID_DSC", " ");
define("_CO_CONTENT_CONTENT_CONTENT_UID", "Poster");
define("_CO_CONTENT_CONTENT_CONTENT_UID_DSC", " ");
define("_CO_CONTENT_CONTENT_CONTENT_TITLE", "Title");
define("_CO_CONTENT_CONTENT_CONTENT_TITLE_DSC", " ");
define("_CO_CONTENT_CONTENT_CONTENT_BODY", "Content Body");
define("_CO_CONTENT_CONTENT_CONTENT_BODY_DSC", " ");
define("_CO_CONTENT_CONTENT_CONTENT_CSS", "Custom CSS");
define("_CO_CONTENT_CONTENT_CONTENT_CSS_DSC", 'If you want to personalize the visual of the page you can define here some css styles for this purpose. <br />Click <a href="javascript:openWithSelfMain(\''.ICMS_URL.'/modules/content/images/content-help.png\', \'content_help\', 1000, 600);">here</a> to see the css classes and Ids avaliable.<br />Recommended only for advanced users.');
define("_CO_CONTENT_CONTENT_CONTENT_TAGS", "Tags");
define("_CO_CONTENT_CONTENT_CONTENT_TAGS_DSC", "Separate the tags with '<font color=red>,</font>'");
define("_CO_CONTENT_CONTENT_CONTENT_VISIBILITY", "Show link in");
define("_CO_CONTENT_CONTENT_CONTENT_VISIBILITY_DSC", " ");
define("_CO_CONTENT_CONTENT_CONTENT_PUBLISHED_DATE", "Published Date");
define("_CO_CONTENT_CONTENT_CONTENT_PUBLISHED_DATE_DSC", " ");
define("_CO_CONTENT_CONTENT_CONTENT_UPDATED_DATE", "Updated Date");
define("_CO_CONTENT_CONTENT_CONTENT_UPDATED_DATE_DSC", " ");
define("_CO_CONTENT_CONTENT_CONTENT_WEIGHT", "Weight");
define("_CO_CONTENT_CONTENT_CONTENT_WEIGHT_DSC", " ");
define("_CO_CONTENT_CONTENT_CONTENT_STATUS", "Status");
define("_CO_CONTENT_CONTENT_CONTENT_STATUS_DSC", " ");
define("_CO_CONTENT_CONTENT_CONTENT_MAKESYMLINK", "Create Symlink?");
define("_CO_CONTENT_CONTENT_CONTENT_MAKESYMLINK_DSC", "Set to <b>YES</b> to create automaticaly a symlink for this content page.");
define("_CO_CONTENT_CONTENT_READ", "View Permission");
define("_CO_CONTENT_CONTENT_READ_DSC", "Select which groups will have view permission for this content page. This means that a user belonging to one of these groups will be able to view the content page when it is activated in the site.");
define("_CO_CONTENT_CONTENT_CONTENT_SUBS", "Related Pages");
define("_CO_CONTENT_CONTENT_CONTENT_SUBS_DSC", "");
define("_CO_CONTENT_CONTENT_CONTENT_CANCOMMENT", "Can comment ?");
define("_CO_CONTENT_CONTENT_CONTENT_CANCOMMENT_DSC", "");
define("_CO_CONTENT_CONTENT_CONTENT_SHOWSUBS", "Show Related Pages");
define("_CO_CONTENT_CONTENT_CONTENT_SHOWSUBS_DSC", "If the <b>\"Show Related Pages\"</b> in the preferences of this module is set to <b>\"YES\"</b> then you can override this config and enable or disable the display of the Related Pages of this Page.");
define("_CO_CONTENT_CONTENT_INFO", "Published by %s on %s. (%u reads)");
define("_CO_CONTENT_CONTENT_FROM_USER", "All contents of %s");
define("_CO_CONTENT_CONTENT_COMMENTS_INFO", "%d comments");
define("_CO_CONTENT_CONTENT_NO_COMMENT", "No comment");

//Status
define("_CO_CONTENT_CONTENT_STATUS_PUBLISHED", "Published");
define("_CO_CONTENT_CONTENT_STATUS_PENDING", "Pending review");
define("_CO_CONTENT_CONTENT_STATUS_DRAFT", "Draft");
define("_CO_CONTENT_CONTENT_STATUS_PRIVATE", "Private");
define("_CO_CONTENT_CONTENT_STATUS_EXPIRED", "Expired");

//Visibility
define("_CO_CONTENT_CONTENT_VISIBLE_MENUOLNY", "Only in Menu");
define("_CO_CONTENT_CONTENT_VISIBLE_SUBSONLY", "Only in Related Pages");
define("_CO_CONTENT_CONTENT_VISIBLE_MENUSUBS", "Menu and Related Pages");
define("_CO_CONTENT_CONTENT_VISIBLE_DONTSHOW", "Don't show link");