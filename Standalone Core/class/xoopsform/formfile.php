<?php
/**
* Creates a form file attribute
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formfile.php 9014 2009-07-19 11:07:45Z pesianstranger $
*/

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}

/**
 * 
 * 
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
/**
 * A file upload field
 * 
 * @author	Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 * 
 * @package		kernel
 * @subpackage	form
 */
class XoopsFormFile extends XoopsFormElement {

	/**
     * Maximum size for an uploaded file
	 * @var	int	
	 * @access	private
	 */
	var $_maxFileSize;

	/**
	 * Constructor
	 * 
	 * @param	string	$caption		Caption
	 * @param	string	$name			"name" attribute
	 * @param	int		$maxfilesize	Maximum size for an uploaded file
	 */
	function XoopsFormFile($caption, $name, $maxfilesize='4096000') {
		$this->setCaption($caption);
		$this->setName($name);
		$this->_maxFileSize = intval($maxfilesize);
	}

	/**
	 * Get the maximum filesize
	 * 
	 * @return	int
	 */
	function getMaxFileSize() {
		return $this->_maxFileSize;
	}

	/**
	 * prepare HTML for output
	 * 
	 * @return	string	HTML
	 */
	function render() {
    	$ele_name = $this->getName();
		return "<input type='hidden' name='MAX_FILE_SIZE' value='".$this->getMaxFileSize()."' /><input type='file' name='".$ele_name."' id='".$ele_name."'".$this->getExtra()." /><input type='hidden' name='xoops_upload_file[]' id='xoops_upload_file[]' value='".$ele_name."' />";
	}
}
?>