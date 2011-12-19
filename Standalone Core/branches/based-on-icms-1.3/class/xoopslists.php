<?php
/**
 * Handles all list functions within ImpressCMS
 *
 * @copyright	The XOOPS Project <http://www.xoops.org/>
 * @copyright	XOOPS_copyrights.txt
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license		LICENSE.txt
 * @package		misc
 * @since		XOOPS
 * @author		The XOOPS Project Community <http://www.xoops.org>
 * @author		modified by UnderDog <underdog@impresscms.org>
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @version		$Id: xoopslists.php 22162 2011-08-03 21:00:35Z fiammy $
 */

if (defined("ICMS_LISTS_INCLUDED") ) exit();
/** Make sure this file is only included once */
define("ICMS_LISTS_INCLUDED",1);

/**
 * Handles all list functions within ImpressCMS
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org/>
 * @license		LICENSE.txt
 * @package		misc
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 */
class IcmsLists {

	/**
	 * Gets list of name of directories inside a directory
	 *
	 * @param   string	$dirname	name of the directory to scan
	 * @return  array	 $list	   list of directories in the directory
	 * @deprecated	Use icms_core_Filesystem::getDirList, instead
	 * @todo		Remove in version 1.4 - all instances have been removed from the core
	 */
	static public function getDirListAsArray( $dirname ) {
		icms_core_Debug::setDeprecated('icms_core_Filesystem::getDirList', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_Filesystem::getDirList($dirname);
	}

	/**
	 * Gets list of all files in a directory
	 *
	 * @param   string	$dirname	name of the directory to scan for files
	 * @param   string	$prefix	 prefix to put in front of the file
	 * @return  array	 $filelist   list of files in the directory
	 * @deprecated	Use icms_core_Filesystem::getFileList
	 * @todo		Remove in version 1.4 - all instances have been removed from the core
	 */
	static public function getFileListAsArray($dirname, $prefix="") {
		icms_core_Debug::setDeprecated('icms_core_Filesystem::getFileList', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_Filesystem::getFileList($dirname, $prefix);
	}

	/**
	 * Gets list of image file names in a directory
	 * @see	icms_core_Filesystem::getFileList
	 *
	 * @param   string	$dirname	name of the directory to scan for image files
	 * @param   string	$prefix	 prefix to put in front of the image file
	 * @return  array	 $filelist   list of files in the directory
	 * @deprecated	Use icms_core_Filesystem::getFileList, instead
	 * @todo		Remove in version 1.4 - all occurrences have been removed in the core
	 */
	static public function getImgListAsArray($dirname, $prefix="") {
		icms_core_Debug::setDeprecated('icms_core_Filesystem::getFileList', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_Filesystem::getFileList($dirname, $prefix, array('gif', 'jpg', 'png'));
	}

	/**
	 * Gets list of font file names in a directory
	 *
	 * @param   string	$dirname	name of the directory to scan for font files
	 * @param   string	$prefix	 prefix to put in front of the font file
	 * @return  array	$filelist   list of font files in the directory
	 * @deprecated	Use icms_core_Filesystem::getFileList, instead
	 * @todo		Remove in version 1.4 - all occurrences have been removed from the core
	 */
	static public function getFontListAsArray($dirname, $prefix="") {
		icms_core_Debug::setDeprecated('icms_core_Filesystem::getFileList', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_Filesystem::getFileList($dirname, $prefix, array('ttf'));
	}

	/**
	 * Gets list of php file names in a directory
	 * @see	icms_core_Filesystem::getFileList
	 *
	 * @param   string	$dirname	name of the directory to scan for PHP files
	 * @param   string	$prefix	 prefix to put in front of the PHP file
	 * @return  array	 $filelist   list of PHP files in the directory
	 * @deprecated	Use icms_core_Filesystem::getFileList
	 * @todo		Remove in version 1.4 - all occurrences have been removed from the core
	 */
	static public function getPhpListAsArray($dirname, $prefix="") {
		icms_core_Debug::setDeprecated('icms_core_Filesystem::getFileList', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		$filelist = icms_core_Filesystem::getFileList($dirname, $prefix, array('php'));
		return str_replace('.php', '', $filelist);
	}

	/**
	 * Gets list of html file names in a certain directory
	 * @see	icms_core_Filesystem::getFileList
	 *
	 * @param   string	$dirname	name of the directory to scan for HTML files
	 * @param   string	$prefix	 prefix to put in front of the HTML file
	 * @return  array	 $filelist   list of HTML files in the directory
	 * @deprecated	Use icms_core_Filesystem::getFileList
	 * @todo		Remove in version 1.4 - no occurrences in the core
	 */
	static public function getHtmlListAsArray($dirname, $prefix="") {
		icms_core_Debug::setDeprecated('icms_core_Filesystem::getFileList', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_Filesystem::getFileList($dirname, $prefix, array('htm', 'html', 'xhtml'));
	}

	/**
	 * @deprecated use icms_form_elements_select_Timezone::getTimeZoneList(), instead
	 * 
	 * @todo	Remove in 1.4
	 */
	static public function getTimeZoneList() {
		icms_core_Debug::setDeprecated('icms_form_elements_select_Timezone::getTimeZoneList()', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_form_elements_select_Timezone::getTimeZoneList();
	}

	/**
	 * Gets list of administration themes folder from themes directory, excluding any directories that do not have theme_admin.html
	 * @deprecated	Use icms_view_theme_Factory::getAdminThemesList()
	 * @todo	Remove in 1.4
	 * @return	array
	 */
	static public function getAdminThemesList(){
	    icms_core_Debug::setDeprecated('icms_module_Handler::getAvailable', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_view_theme_Factory::getAdminThemesList();
	}

	/**
	 * Gets list of themes folder from themes directory, excluding any directories that do not have theme.html
	 * @deprecated	Use icms_view_theme_Factory::getThemesList()
	 * @todo	Remove in 1.4
	 * @return	array
	 */
	static public function getThemesList(){
	    icms_core_Debug::setDeprecated('icms_module_Handler::getAvailable', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_view_theme_Factory::getThemesList();
	}

	/**
	 * Gets a list of module folders from the modules directory
	 * @todo	Remove in version 1.4
	 * @deprecated	Use icms_module_Handler::getAvailable, instead
	 */
	static public function getModulesList() {
	    icms_core_Debug::setDeprecated('icms_module_Handler::getAvailable', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_module_Handler::getAvailable();
	}

	/**
	 * Gets a list of active module folders from database
	 *
	 * @see	icms_module_Handler::getActive
	 * @deprecated	Use icms_module_Handler::getActive, instead
	 * @todo		Remove in version 1.4
	 */
	static public function getActiveModulesList() {
		icms_core_Debug::setDeprecated('icms_module_Handler::getActive', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_module_Handler::getActive();
	}

	/**
	 * Gets list of avatar file names in a certain directory
	 * if directory is not specified, default directory will be searched
	 *
	 * @deprecated	Use icms_data_avatar_Handler::getListFromDir instead
	 * @todo	Remove in version 1.4 - no occurrences in the core
	 *
	 * @param   string	$avatar_dir name of the directory to scan for files
	 * @return  array	 $avatars	list of avatars in the directory
	 */
	static public function getAvatarsList($avatar_dir="") {
		icms_core_Debug::setDeprecated('icms_data_avatar_Handler::getListFromDir', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_data_avatar_Handler::getListFromDir($avatar_dir);
	}

	/**
	 * Gets list of all avatar image files inside default avatars directory
	 *
	 * @deprecated	use icms_data_avatar_Handler::getAllFromDir instead
	 * @todo	Remove in version 1.4 - no occurrences in the core
	 *
	 * @return  mixed	 $avatars|false  list of avatar files in the directory or false if no avatars
	 */
	static public function getAllAvatarsList() {
		icms_core_Debug::setDeprecated('icms_data_avatar_Handler::getAllFromDir', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_data_avatar_Handler::getAllFromDir();
	}

	/**
	 * Gets list of subject icon image file names in a certain directory
	 * @deprecated	Use icms_core_Filesystem::getFileList(ICMS_ROOT_PATH . "/images/subject/", '', array('gif', 'jpg', 'png'));
	 * @todo		Remove in 1.4
	 *
	 * If directory is not specified, default directory will be searched.
	 *
	 * @param   string	$sub_dir	name of the directory to scan for files
	 * @return  array	 $subjects   list of subject files in the directory
	 */
	static public function getSubjectsList($sub_dir="") {
		icms_core_Debug::setDeprecated('icms_core_Filesystem::getFileList(ICMS_ROOT_PATH . "/images/subject/" , array("gif", "jpg", "png"))', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		$subjects = array();
		if ($sub_dir != "") {
			$subjects = icms_core_Filesystem::getFileList(ICMS_ROOT_PATH . "/images/subject/" . $sub_dir, $sub_dir . "/", array('gif', 'jpg', 'png'));
		} else {
			$subjects = icms_core_Filesystem::getFileList(ICMS_ROOT_PATH . "/images/subject/", '', array('gif', 'jpg', 'png'));
		}
		return $subjects;
	}

	/**
	 * Gets list of language folders inside default language directory - can't find anywhere in the core
	 * @deprecated	Use icms_core_Filesystem::getDirList, instead
	 * @todo		Remove in 1.4
	 * @return  array	 $lang_list   list of language files in the directory
	 */
	static public function getLangList() {
		icms_core_Debug::setDeprecated('icms_core_Filesystem::getDirList{ICMS_ROOT_PATH . "/language/")', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_core_Filesystem::getDirList(ICMS_ROOT_PATH . "/language/");
	}

	/**
	 * Gets list of editors folders inside editors directory
	 * @deprecated	Use icms_core_Filesystem::getDirList or icms_plugins_EditorHandler::getList
	 * @todo		Remove in 1.4
	 *
	 * @param	string	 $type			type of editor
	 * @return  array	 $editor_list   list of files in the directory
	 */
	static public function getEditorsList($type='') {
		icms_core_Debug::setDeprecated('icms_plugins_EditorHandler::getListByType', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_plugins_EditorHandler::getListByType($type);
	}

	/**
	 * Gets list of enabled editors folders inside editors directory
	 * @todo	Remove in 1.4, this isn't used anywhere in the core and we don't really need a function just to return a global value
	 * @deprecated
	 * @return array
	 */
	static public function getEnabledEditorsList() {
		icms_core_Debug::setDeprecated('', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		global $icmsConfig;
		return $icmsConfig['editor_enabled'];
	}

	/**
	 * Gets list of countries
	 * @deprecated	Use icms_form_elements_select_Country::getCountryList(), instead
	 * @todo		Remove in version 1.4
	 *
	 * @return  array	 $country_list   list of countries
	 */
	static public function getCountryList() {
		icms_core_Debug::setDeprecated('icms_form_elements_select_Country::getCountryList()', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_form_elements_select_Country::getCountryList();	
	}

	/**
	 * Gets list HTML tags - not used anywhere in the core
	 *
	 * @return  array	 $html_list
	 */
	static public function getHtmlList() {
		$html_list = array (
			"a" => "&lt;a&gt;",
			"abbr" => "&lt;abbr&gt;",
			"acronym" => "&lt;acronym&gt;",
			"address" => "&lt;address&gt;",
			"b" => "&lt;b&gt;",
			"bdo" => "&lt;bdo&gt;",
			"big" => "&lt;big&gt;",
			"blockquote" => "&lt;blockquote&gt;",
			"caption" => "&lt;caption&gt;",
			"cite" => "&lt;cite&gt;",
			"code" => "&lt;code&gt;",
			"col" => "&lt;col&gt;",
			"colgroup" => "&lt;colgroup&gt;",
			"dd" => "&lt;dd&gt;",
			"del" => "&lt;del&gt;",
			"dfn" => "&lt;dfn&gt;",
			"div" => "&lt;div&gt;",
			"dl" => "&lt;dl&gt;",
			"dt" => "&lt;dt&gt;",
			"em" => "&lt;em&gt;",
			"font" => "&lt;font&gt;",
			"h1" => "&lt;h1&gt;",
			"h2" => "&lt;h2&gt;",
			"h3" => "&lt;h3&gt;",
			"h4" => "&lt;h4&gt;",
			"h5" => "&lt;h5&gt;",
			"h6" => "&lt;h6&gt;",
			"hr" => "&lt;hr&gt;",
			"i" => "&lt;i&gt;",
			"img" => "&lt;img&gt;",
			"ins" => "&lt;ins&gt;",
			"kbd" => "&lt;kbd&gt;",
			"li" => "&lt;li&gt;",
			"map" => "&lt;map&gt;",
			"object" => "&lt;object&gt;",
			"ol" => "&lt;ol&gt;",
			"samp" => "&lt;samp&gt;",
			"small" => "&lt;small&gt;",
			"strong" => "&lt;strong&gt;",
			"sub" => "&lt;sub&gt;",
			"sup" => "&lt;sup&gt;",
			"table" => "&lt;table&gt;",
			"tbody" => "&lt;tbody&gt;",
			"td" => "&lt;td&gt;",
			"tfoot" => "&lt;tfoot&gt;",
			"th" => "&lt;th&gt;",
			"thead" => "&lt;thead&gt;",
			"tr" => "&lt;tr&gt;",
			"tt" => "&lt;tt&gt;",
			"ul" => "&lt;ul&gt;",
			"var" => "&lt;var&gt;"
			);
		asort($html_list);
		reset($html_list);
		return $html_list;
	}

	/**
	 * Gets list of all user ranks in the database
	 * @deprecated	Use SystemUserrankHandler->getList
	 * @todo		Remove in version 1.4
	 *
	 * @return  array	 $ret   list of user ranks
	 */
	static public function getUserRankList() {
		icms_core_Debug::setDeprecated('SystemUserrankHandler->getList', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
		return icms_getModuleHandler("userrank", "system")->getList(icms_buildCriteria(array("rank_special" => 1)));
	}
}

/**
 * XoopsLists
 *
 * @copyright	The XOOPS Project <http://www.xoops.org/>
 * @copyright	XOOPS_copyrights.txt
 * @license		LICENSE.txt
 * @since		XOOPS
 * @author		The XOOPS Project Community <http://www.xoops.org>
 *
 * @deprecated
 */
class XoopsLists extends IcmsLists { /* For Backwards Compatibility */ }
