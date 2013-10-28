<?php
/**
 * Form control creating an advanced file upload element
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		ipf
 * @subpackage	form
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: File.php 20302 2010-10-16 17:31:12Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

class icms_ipf_form_elements_File extends icms_form_elements_File {
	private $_object;
	private $_key;

	/**
	 * Constructor
	 * @param	object    $object   reference to targetobject (@link icms_ipf_Object)
	 * @param	string    $key      the form name
	 */
	public function __construct($object, $key) {
		$this->_object = $object;
		$this->_key = $key;
		parent::__construct($object->vars[$key]['form_caption'], $key, isset($object->vars[$key]['form_maxfilesize']) ? $object->vars[$key]['form_maxfilesize'] : 0);
		$this->setExtra(" size=50");
	}

	/**
	 * prepare HTML for output
	 *
	 * @return	string	$ret  the constructed HTML
	 */
	public function render() {
		$ret = '';
		if ($this->_object->getVar($this->_key) != '') {
			$ret .=	"<div>" . _CO_ICMS_CURRENT_FILE
				. "<a href='" . $this->_object->getUploadDir() . $this->_object->getVar($this->_key)
				. "' target='_blank' >". $this->_object->getVar($this->_key) . "</a></div>" ;
		}

		$ret .= "<div><input type='hidden' name='MAX_FILE_SIZE' value='" . $this->getMaxFileSize() . "' />
		        <input type='file' name='" . $this->getName() . "' id='" . $this->getName() . "'" . $this->getExtra() . " />
		        <input type='hidden' name='icms_upload_file[]' id='icms_upload_file[]' value='" . $this->getName() . "' /></div>";

		return $ret;
	}
}