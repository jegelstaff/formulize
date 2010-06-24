<?php
/**
* Creates a form passwordfield attribute
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formpassword.php 9340 2009-09-06 11:40:35Z pesianstranger $
*/

if(!defined('ICMS_ROOT_PATH')) {die('ImpressCMS root path not defined');}

/**
* @package 	kernel
* @subpackage 	form
*
* @author 	Kazumi Ono 	<onokazu@xoops.org>
* @copyright 	copyright (c) 2000-2003 XOOPS.org
*/
/**
* A password field
*
* @author 	Kazumi Ono	<onokazu@xoops.org>
* @copyright 	copyright (c) 2000-2003 XOOPS.org
*
* @package 	kernel
* @subpackage 	form
*/
class XoopsFormPassword extends XoopsFormElement
{
	/**
	* Size of the field.
	* @var 		int
	* @access 	private
	*/
	var $_size;

	/**
	* Maximum length of the text
	* @var 		int
	* @access 	private
	*/
	var $_maxlength;
	
	/**
	* Initial content of the field.
	* @var 		string
	* @access 	private
	*/
	var $_value;
	
	/**
	* Turns off the browser autocomplete function.
	* @var 		boolean
	* @access 	public
	*/
	var $autocomplete = false;
	
	/**
	* Initial content of the field.
	* @var 		string
	* @access 	private
	*/
	var $_classname;
	
	/**
	* Constructor
	*
	* @param	string	$caption	Caption
	* @param	string	$name		"name" attribute
	* @param	int		$size		Size of the field
	* @param	int		$maxlength	Maximum length of the text
	* @param	int		$value		Initial value of the field. 
	* 							<b>Warning:</b> this is readable in cleartext in the page's source!
	*/
	function XoopsFormPassword($caption, $name, $size, $maxlength, $value = '', $autocomplete = false, $classname = '')
	{
		$this->setCaption($caption);
		$this->setName($name);
		$this->_size = intval($size);
		$this->_maxlength = intval($maxlength);
		$this->setValue($value);
		$this->autoComplete = !empty($autocomplete);
		$this->setClassName($classname);
	}
	
	/**
	* Get the field size
	*
	* @return	int
	*/
	function getSize() {return $this->_size;}
	
	/**
	* Get the max length
	*
	* @return	int
	*/
	function getMaxlength() {return $this->_maxlength;}
	
	/**
	* Get the "value" attribute
	*
	* @param	bool    $encode To sanitizer the text?
	* @return	string
	*/
	function getValue($encode = false) {return $encode ? htmlspecialchars($this->_value, ENT_QUOTES) : $this->_value;}
	
	/**
	* Set the initial value
	* 
	* @param	$value	string
	*/
	function setValue($value) {$this->_value = $value;}
	
	/**
	* Set the initial value
	* 
	* @param	$value	string
	*/
	function setClassName($classname) {$this->_classname = $classname;}
	
	/**
	* Get the "class" attribute
	*
	* @param	bool    $encode To sanitizer the text?
	* @return	string
	*/
	function getClassName($encode = false) {return $encode ? htmlspecialchars($this->_classname, ENT_QUOTES) : $this->_classname;}
	
	/**
	* Prepare HTML for output
	*
	* @return	string	HTML
	*/
	function render()
	{
        global $icmsConfigUser;
        if($icmsConfigUser['pass_level'] > 20 ){icms_PasswordMeter();}
		$ele_name = $this->getName();
		return "<input class='".$this->getClassName()."' type='password' name='".$ele_name."' id='".$ele_name."' size='".$this->getSize()."' maxlength='".$this->getMaxlength()."' value='".$this->getValue()."'".$this->getExtra()." ".($this->autoComplete ? "" : "autocomplete='off' ")."/>";
	}
}
?>