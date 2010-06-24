<?php
/**
* Creates a textbox form attribut
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formtext.php 9591 2009-11-22 23:43:45Z mrtheme $
*/

if(!defined('ICMS_ROOT_PATH')) {die('ImpressCMS root path not defined');}
/**
* @package     kernel
* @subpackage  form
* 
* @author	    Kazumi Ono	<onokazu@xoops.org>
* @copyright	copyright (c) 2000-2003 XOOPS.org
*/
/**
* A simple text field
* 
* @package     kernel
* @subpackage  form
* 
* @author	    Kazumi Ono	<onokazu@xoops.org>
* @copyright	copyright (c) 2000-2003 XOOPS.org
*/
class XoopsFormText extends XoopsFormElement
{
	/**
	* Size
	* @var	int 
	* @access	private
	*/
	var $_size;
	
	/**
	* Maximum length of the text
	* @var	int 
	* @access	private
	*/
	var $_maxlength;
	
	/**
	* Initial text
	* @var	string  
	* @access	private
	*/
	var $_value;
	
	/**
	* Turns off the browser autocomplete function.
	* @var 		boolean
	* @access 	public
	*/
	var $autocomplete = false;

	/**
	* Constructor
	* 
	* @param	string	$caption	Caption
	* @param	string	$name       "name" attribute
	* @param	int		$size	    Size
	* @param	int		$maxlength	Maximum length of text
	* @param	string  $value      Initial text
	*/
	function XoopsFormText($caption, $name, $size, $maxlength, $value = '', $autocomplete = false)
	{
		$this->setCaption($caption);
		$this->setName($name);
		$this->_size = intval($size);
		$this->_maxlength = intval($maxlength);
		$this->setValue($value);
		$this->autoComplete = !empty($autocomplete);
	}
	
	/**
	* Get size
	* 
	* @return	int
	*/
	function getSize() {return $this->_size;}
	
	/**
	* Get maximum text length
	* 
	* @return	int
	*/
	function getMaxlength() {return $this->_maxlength;}
	
	/**
	* Get initial content
	* 
	* @param	bool    $encode To sanitizer the text? Default value should be "true"; however we have to set "false" for backward compat
	* @return	string
	*/
	function getValue($encode = false) {return $encode ? htmlspecialchars($this->_value, ENT_QUOTES) : $this->_value;}
	
	/**
	* Set initial text value
	* 
	* @param	$value  string
	*/
	function setValue($value) {$this->_value = $value;}
	
	/**
	* Prepare HTML for output
	* 
	* @return	string  HTML
	*/
	function render()
	{
		return "<input type='text' name='".$this->getName()."' id='".$this->getName()."' size='".$this->getSize()."' maxlength='".$this->getMaxlength()."' value='".$this->getValue()."'".$this->getExtra()." />";
	}
}


?>