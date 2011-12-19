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
* @version		$Id: formcolorpicker.php 8662 2009-05-01 09:04:30Z pesianstranger $
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
class XoopsFormColorPicker extends XoopsFormText {
	
	/**
	 * Constructor
   * @param	string  $caption  Caption of the element
   * @param	string  $name     Name of the element
   * @param	string  $value    Value of the element
	 */
	function XoopsFormColorPicker($caption, $name, $value = "#FFFFFF") {
		$this->XoopsFormText( $caption, $name, 9, 7, $value );
	}
	
	/**
	 * Render the color picker
	 * @return  $string	rendered color picker HTML
	 */
	function render() {
		if (isset ( $GLOBALS ['xoTheme'] )) {
			$GLOBALS ['xoTheme']->addScript ( 'include/color-picker.js' );
		} else {
			echo "<script type=\"text/javascript\" src=\"" . ICMS_URL . "/include/color-picker.js\"></script>";
		}
		$this->setExtra ( ' style="background-color:' . $this->getValue () . ';"' );
		return parent::render() . "\n<input type='reset' value=' ... ' onclick=\"return TCP.popup('" . ICMS_URL . "/include/',document.getElementById('" . $this->getName () . "'));\">\n";
	}

	/**
	 * Returns custom validation Javascript
	 * 
	 * @return	string	Element validation Javascript
	 */
	function renderValidationJS() {
		$eltname = $this->getName ();
		$eltcaption = $this->getCaption ();
		$eltmsg = empty ( $eltcaption ) ? sprintf ( _FORM_ENTER, $eltname ) : sprintf ( _FORM_ENTER, $eltcaption );
		$eltmsg = str_replace ( '"', '\"', stripslashes ( $eltmsg ) );
		$eltmsg = strip_tags($eltmsg);
		return "if ( myform.{$eltname}.value == \"\" ) { window.alert(\"{$eltmsg}\"); myform.{$eltname}.focus(); return false; }";
	}

}

?>