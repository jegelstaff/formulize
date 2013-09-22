<?php
/**
* Footer page included at the end of each page on user side of the mdoule
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: footer.php 20170 2010-09-19 14:01:59Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

$xoTheme->addStylesheet(BANNERS_URL . "module.css");
include_once ICMS_ROOT_PATH . "/footer.php";