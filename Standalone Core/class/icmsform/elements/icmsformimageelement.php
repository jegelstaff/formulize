<?php
/**
* Form control creating an hidden field for an object derived from IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsformimageelement.php 9614 2009-11-28 14:00:41Z Phoenyx $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class IcmsFormImageElement extends XoopsFormElementTray {

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link IcmsPersistableObject)
	 * @param	string    $key      the form name
	 */
  function IcmsFormImageElement($object, $key) {
  	$var = $object->vars[$key];
  	$control = $object->getControl($key);

    $object_imageurl = $object->getImageDir();
    $this->XoopsFormElementTray( $var['form_caption'], ' ' );

    if (isset($objectArray['image'])){
        $objectArray['image'] = str_replace('{ICMS_URL}', ICMS_URL, $objectArray['image']);
    }

    if($object->getVar($key,'e') != '' && (substr($object->getVar($key,'e'), 0, 4) == 'http' || substr($object->getVar($key,'e'), 0, 11) == '{ICMS_URL}')){
    	$this->addElement( new XoopsFormLabel( '', "<img src='" . str_replace('{ICMS_URL}', ICMS_URL, $object->getVar($key,'e')) . "' alt='' /><br/><br/>" ) );
    }elseif($object->getVar($key,'e') != ''){
    	$this->addElement( new XoopsFormLabel( '', "<img src='" . $object_imageurl . $object->getVar($key,'e') . "' alt='' /><br/><br/>" ) );
   	}

    include_once ICMS_ROOT_PATH."/class/icmsform/elements/icmsformfileuploadelement.php";
    $this->addElement(new IcmsFormFileUploadElement($object, $key));

    if (!isset($control['nourl']) || !$control['nourl']) {
        include_once ICMS_ROOT_PATH."/class/icmsform/elements/icmsformtextelement.php";
		$this->addElement(new XoopsFormLabel( '<div style="padding-top: 8px; font-size: 80%;">'._CO_ICMS_URL_FILE_DSC.'</div>', ''));
        $this->addElement(new XoopsFormLabel( '', '<br />' . _CO_ICMS_URL_FILE));
        $this->addElement(new XoopsFormText('', 'url_'.$key, 50, 500));
    }
    if (!$object->isNew()) {
    	include_once ICMS_ROOT_PATH."/class/icmsform/elements/icmsformcheckelement.php";
        $this->addElement(new XoopsFormLabel( '', '<br /><br />'));
        $delete_check = new IcmsFormCheckElement('', 'delete_'.$key);
        $delete_check->addOption(1, '<span style="color:red;">'._CO_ICMS_DELETE.'</span>');
        $this->addElement($delete_check);
    }
  }
}
?>