<?php
/**
* Form control creating a multi selectbox for an object derived from IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsformselect_multielement.php 8559 2009-04-11 11:34:22Z icmsunderdog $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

include_once (ICMS_ROOT_PATH . "/class/icmsform/elements/icmsformselectelement.php");

class IcmsFormSelect_multiElement extends IcmsFormSelectElement  {

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link IcmsPersistableObject)
	 * @param	string    $key      the form name
	 */
  function IcmsFormSelect_multiElement($object, $key) {
    $this->multiple = true;
    parent::IcmsFormSelectElement($object, $key);
  }
}

?>