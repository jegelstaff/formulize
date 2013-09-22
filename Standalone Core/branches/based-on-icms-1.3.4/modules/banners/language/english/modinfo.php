<?php
/**
* English language constants related to module information
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: modinfo.php 20209 2010-09-26 13:41:19Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

/** Module Info */
define("_MI_BANNERS_MD_NAME", "Banners");
define("_MI_BANNERS_MD_DESC", "ImpressCMS Banner Management System");
define("_MI_BANNERS_CLIENTS", "Clients");
define("_MI_BANNERS_BANNERS", "Banners");
define("_MI_BANNERS_POSITIONS", "Positions");

/** Module Configuration */
define("_MI_BANNERS_EMAIL_NEW_BANNER", "Notify webmaster about new banners");
define("_MI_BANNERS_EMAIL_NEW_BANNER_SUBJECT", "E-Mail Subject for new banner");
define("_MI_BANNERS_EMAIL_NEW_BANNER_SUBJECT_DEFAULT", "New banner submitted");
define("_MI_BANNERS_EMAIL_NEW_CLIENT", "Notify webmaster about new clients");
define("_MI_BANNERS_EMAIL_NEW_CLIENT_SUBJECT", "E-Mail Subject for new clients");
define("_MI_BANNERS_EMAIL_NEW_CLIENT_SUBJECT_DEFAULT", "New client");
define("_MI_BANNERS_MAXFILESIZE", "Maximum filesize for banner upload");
define("_MI_BANNERS_MAXFILESIZE_DSC", "Filesize in bytes");
define("_MI_BANNERS_CLIENT_BANNER_TYPES", "Valid banner types for clients");
define("_MI_BANNERS_CLIENT_BANNER_TYPES_DSC", "Define which type of banners can be created by clients. HTML might be removed from the list for security reasons.");