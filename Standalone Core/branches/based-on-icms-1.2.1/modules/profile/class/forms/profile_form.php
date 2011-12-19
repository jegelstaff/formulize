<?php
/**
 * Extended User Profile
 *
 *
 * @copyright	   The ImpressCMS Project http://www.impresscms.org/
 * @license		 LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		 modules
 * @since		   1.2
 * @author		  Jan Pedersen
 * @author		  The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		 $Id$
 */

include_once ICMS_ROOT_PATH.'/class/xoopsform/themeform.php';
class ProfileForm extends XoopsThemeForm{


	function renderValidationJS( $withtags = true ) {
		$js = "";
		if ( $withtags ) {
			$js .= "\n<!-- Start Form Validation JavaScript //-->\n<script type='text/javascript'>\n<!--//\n";
		}
		$myts =& MyTextSanitizer::getInstance();
		$formname = $this->getName();
		$js .= "function xoopsFormValidate_{$formname}(myform) {";
		// First, output code to check required elements
		$elements = $this->getRequired();
		foreach ( $elements as $elt ) {
			$eltname	= $elt->getName();
			$eltcaption = trim( $elt->getCaption() );
			$eltmsg = empty($eltcaption) ? sprintf( _FORM_ENTER, $eltname ) : sprintf( _FORM_ENTER, $eltcaption );
			$eltmsg = str_replace('"', '\"', stripslashes( $eltmsg ) );
			if (strtolower(get_class($elt)) == 'xoopsformradio') {
				$js .= "var myOption = -1;";
				$js .= "for (i=myform.{$eltname}.length-1; i > -1; i--) {
					if (myform.{$eltname}[i].checked) {
						myOption = i; i = -1;
					}
				}
				if (myOption == -1) {
					window.alert(\"{$eltmsg}\"); myform.{$eltname}[0].focus(); return false; }\n";

			}elseif (strtolower(get_class($elt)) == 'smartformselect_multielement') {
				$js .= "var hasSelections = false;";
				$js .= "for(var i = 0; i < myform['{$eltname}[]'].length; i++){
					if (myform['{$eltname}[]'].options[i].selected) {
						hasSelections = true;
					}

				}
				if (hasSelections == false) {
					window.alert(\"{$eltmsg}\"); myform['{$eltname}[]'].options[0].focus(); return false; }\n";

			}elseif (strtolower(get_class($elt)) == 'xoopsformcheckbox') {
				$js .= "var hasSelections = false;";
				//sometimes, there is an implicit '[]', sometimes not
				if(strpos($eltname, '[') === false){
					$js .= "for(var i = 0; i < myform['{$eltname}[]'].length; i++){
						if (myform['{$eltname}[]'][i].checked) {
							hasSelections = true;
						}

					}
					if (hasSelections == false) {
						window.alert(\"{$eltmsg}\"); myform['{$eltname}[]'][0].focus(); return false; }\n";
				}else{
					$js .= "for(var i = 0; i < myform['{$eltname}'].length; i++){
						if (myform['{$eltname}'][i].checked) {
							hasSelections = true;
						}

					}
					if (hasSelections == false) {
						window.alert(\"{$eltmsg}\"); myform['{$eltname}'][0].focus(); return false; }\n";
				}

			}else{
				$js .= "if ( myform.{$eltname}.value == \"\" ) "
					. "{ window.alert(\"{$eltmsg}\"); myform.{$eltname}.focus(); return false; }\n";
				}
		}
		// Now, handle custom validation code
		$elements = $this->getElements( true );
		foreach ( $elements as $elt ) {
			if ( method_exists( $elt, 'renderValidationJS') && strtolower(get_class($elt)) != 'xoopsformcheckbox') {
				if ( $eltjs = $elt->renderValidationJS() ) {
					$js .= $eltjs . "\n";
				}
			}
		}
		$js .= "return true;\n}\n";
		if ( $withtags ) {
			$js .= "//--></script>\n<!-- End Form Vaidation JavaScript //-->\n";
		}
		return $js;
	}


}
?>