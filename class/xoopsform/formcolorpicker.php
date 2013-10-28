<?php
/**
 * XoopsFormColorPicker component class file
 *
 * This class provides a textfield with a color picker popup. This color picker
 * comes from Tigra project (http://www.softcomplex.com/products/tigra_color_picker/).
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @license      http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author       Zoullou <webmaster@zoullou.org>
 * @since        Xoops 2.0.15
 * @version		$Id: formcolorpicker.php 20020 2010-08-25 14:25:59Z malanciault $
 * @package 		XoopsForms
 * @subpackage 	ColorPicker
 */

if (! defined ( 'ICMS_ROOT_PATH' )) {
	die ( "ImpressCMS root path not defined" );
}

/**
 * @package     kernel
 * @subpackage  form
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
/**
 * Color Picker
 *
 * @package     kernel
 * @subpackage  form
 *
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormColorPicker extends icms_form_elements_Colorpicker {
	private $_deprecated;
	public function __construct($caption, $name, $value = "#FFFFFF") {
		parent::__construct($caption, $name, $value);
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_form_elements_Colorpicker', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
?>