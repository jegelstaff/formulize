<?php
/**
 * Class to introduce timepicker
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package	XoopsForms
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id: formtextdateselect.php 21522 2011-04-14 23:57:13Z skenow $
 **/
if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

/**
 * A text field with calendar popup
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * @deprecated	use icms_form_elements_Date
 * @todo		Remove in version 1.4
 */

class XoopsFormTextDateSelect extends icms_form_elements_Date
{

	private $_deprecated;
	/**
	 * Constructor
	 */
	function XoopsFormTextDateSelect($caption, $name, $size = 15, $value= 0)
	{
		//$value = !is_numeric($value) ? time() : (int) ($value); // ALTERED BY FREEFORM SOLUTIONS SO THAT THE LITERAL VALUE PASSED IN IS SENT TO THE PARENT CLASS
		parent::__construct($caption, $name, $size, $value);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Date', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}