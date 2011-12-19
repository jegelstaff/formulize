<?php
/**
* Form control creating an image upload element for an object derived from IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsformlanguageelement.php 8559 2009-04-11 11:34:22Z icmsunderdog $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class IcmsFormLanguageElement extends XoopsFormSelectLang {

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link IcmsPersistableObject)
	 * @param	string    $key      the form name
	 */
  function IcmsFormLanguageElement($object, $key) {

    $var = $object->vars[$key];
    $control = $object->controls[$key];
    $all = isset($control['all']) ? true : false;

    $this->XoopsFormSelectLang($var['form_caption'], $key, $object->getVar($key, 'e'));
    if ($all) {
    	$this->addOption('all', _ALL);
    }
  }
}

?>