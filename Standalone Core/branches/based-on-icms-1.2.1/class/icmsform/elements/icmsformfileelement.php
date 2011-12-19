<?php
/**
* Form control creating an advanced file upload element
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		IcmsPersistableObject
* @since		  1.1
* @author		  marcan <marcan@impresscms.org>
* @version		$Id: icmsformfileelement.php 8559 2009-04-11 11:34:22Z icmsunderdog $
*/

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

class IcmsFormFileElement extends XoopsFormFile {
	var $object;
	var $key;


	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link IcmsPersistableObject)
	 * @param	string    $key      the form name
	 */
  function IcmsFormFileElement($object, $key) {
    $this->object = $object;
   	$this->key = $key;
    $this->XoopsFormFile($object->vars[$key]['form_caption'], $key, isset($object->vars[$key]['form_maxfilesize']) ? $object->vars[$key]['form_maxfilesize'] : 0);
    $this->setExtra(" size=50");
  }


	/**
	 * prepare HTML for output
	 *
	 * @return	string	$ret  the constructed HTML
	 */
	function render(){
		$ret = '';
		if($this->object->getVar($this->key) != '' ){
   		$ret .=	"<div>"._CO_ICMS_CURRENT_FILE."<a href='" . $this->object->getUploadDir().$this->object->getVar($this->key) . "' target='_blank' >". $this->object->getVar($this->key)."</a></div>" ;
    }

		$ret .= "<div><input type='hidden' name='MAX_FILE_SIZE' value='".$this->getMaxFileSize()."' />
		        <input type='file' name='".$this->getName()."' id='".$this->getName()."'".$this->getExtra()." />
		        <input type='hidden' name='icms_upload_file[]' id='icms_upload_file[]' value='".$this->getName()."' /></div>";

  	return $ret;
	}
}

?>