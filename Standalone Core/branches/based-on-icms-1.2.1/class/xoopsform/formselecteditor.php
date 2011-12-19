<?php
/**
* Creates a form attribute which is able to select an editor
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	XoopsForms
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: formselecteditor.php 8912 2009-06-23 13:16:21Z realtherplima $
*/
/**
 * base class
 */

/**
 * A select box with available editors
 * 
 * @package     kernel
 * @subpackage  form
 * 
 * @author	    phppp (D.J.)
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsFormSelectEditor extends XoopsFormElementTray
{	
	/**
	 * Constructor
	 * 
	 * @param	object	$form	the form calling the editor selection	
	 * @param	string	$name	editor name
	 * @param	string	$value	Pre-selected text value
   * @param	bool	$noHtml  dohtml disabled
	 */
	function XoopsFormSelectEditor(&$form, $name="editor", $value=null, $noHtml=false)
	{
		global $icmsConfig;
		
		if (empty($value)){
			$value = $icmsConfig['editor_default'];
		}
		
		$this->XoopsFormElementTray(_SELECT);
		$edtlist = XoopsLists::getEditorsList();
		$option_select = new XoopsFormSelect("", $name, $value);
		$querys = preg_replace('/editor=(.*?)&/','',$_SERVER['QUERY_STRING']);
		$extra = 'onchange="if(this.options[this.selectedIndex].value.length > 0 ){
				window.location = \'?editor=\'+this.options[this.selectedIndex].value+\'&'.$querys.'\';
			}"';
		$option_select->setExtra($extra);
		$option_select->addOptionArray($edtlist);
		
		$this->addElement($option_select);
	}
}
?>