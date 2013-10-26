<?php
/**
 * Creates a form file field
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Form
 * @subpackage	Elements
 * @version		$Id: File.php 20509 2010-12-11 12:02:57Z phoenyx $
 */

defined('ICMS_ROOT_PATH')or die("ImpressCMS root path not defined");

/**
 * Create a field for uploading a file
 *
 * @category	ICMS
 * @package     Form
 * @subpackage	Elements
 *
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 */
class icms_form_elements_File extends icms_form_Element {
	/**
	 * Maximum size for an uploaded file
	 * @var	int
	 */
	private $_maxFileSize;

	/**
	 * Constructor
	 *
	 * @param	string	$caption		Caption
	 * @param	string	$name			"name" attribute
	 * @param	int		$maxfilesize	Maximum size for an uploaded file
	 */
	public function __construct($caption, $name, $maxfilesize = '4096000') {
		$this->setCaption($caption);
		$this->setName($name);
		$this->_maxFileSize = (int) ($maxfilesize);
	}

	/**
	 * Get the maximum filesize
	 *
	 * @return	int
	 */
	public function getMaxFileSize() {
		return $this->_maxFileSize;
	}

	/**
	 * prepare HTML for output
	 *
	 * @return	string	HTML
	 */
	public function render() {
		$ele_name = $this->getName();
		$ret  = "<input type='hidden' name='MAX_FILE_SIZE' value='" . $this->getMaxFileSize() . "' />";
		$ret .= "<input type='file' name='" . $ele_name . "' id='" . $ele_name . "'" . $this->getExtra() . " />";
		$ret .= "<input type='hidden' name='xoops_upload_file[]' id='xoops_upload_file[]' value='" . $ele_name . "' />";
		return $ret;
	}
}

