<?php
/**
 * All functions for DHTML text area are here.
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		XoopsForms
 * @since		XOOPS
 * @author		http://www.xoops.org The XOOPS Project
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: formdhtmltextarea.php 20020 2010-08-25 14:25:59Z malanciault $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

// Make sure you have included /include/xoopscodes.php, otherwise DHTML will not work properly!

/**
 * A textarea with xoopsish formatting and smilie buttons
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 *
 * @package	 kernel
 * @subpackage  form
 */
class XoopsFormDhtmlTextArea extends icms_form_elements_Dhtmltextarea {
	private $_deprecated;
	public function __construct($caption, $name, $value, $rows=5, $cols=50, $hiddentext="xoopsHiddenText", $options = array()) {
		parent::__construct($caption, $name, $value, $rows, $cols, $hiddentext, $options);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Dhtmltextarea', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}

?>