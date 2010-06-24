<?php
/**
* Form control creating an element to link and URL to an object derived from IcmsPersistableObject
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsformurllinkelement.php 8559 2009-04-11 11:34:22Z icmsunderdog $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class IcmsFormUrlLinkElement extends XoopsFormElementTray {


	/**
	 * Constructor
	 * @param	object    $form_caption   the caption of the form
	 * @param	string    $key            the key
	 * @param	object    $object         reference to targetobject (@todo, which object will be passed here?)
	 */
  function IcmsFormUrlLinkElement($form_caption, $key, $object) {
    $this->XoopsFormElementTray($form_caption, '&nbsp;' );

    $this->addElement( new XoopsFormLabel( '', '<br/>'._CO_ICMS_URLLINK_URL));
    $this->addElement(new IcmsFormTextElement($object, 'url_'.$key));

    $this->addElement( new XoopsFormLabel( '', '<br/>'._CO_ICMS_CAPTION));
    $this->addElement(new IcmsFormTextElement($object, 'caption_'.$key));

    $this->addElement( new XoopsFormLabel( '', '<br/>'._CO_ICMS_DESC.'<br/>'));
    $this->addElement(new XoopsFormTextArea('', 'desc_'.$key, $object->getVar('description')));

    $this->addElement( new XoopsFormLabel( '', '<br/>'._CO_ICMS_URLLINK_TARGET));
    $targ_val = $object->getVar('target');
    $targetRadio = new XoopsFormRadio('', 'target_'.$key, $targ_val!= '' ? $targ_val : '_blank');
    $control = $object->getControl('target');
    $targetRadio->addOptionArray($control['options']);

    $this->addElement($targetRadio);
  }
}

?>