<?php
/**
* IcmsAddTo class to easily add content to social networking/bookmarking site
*
* @credit http://addtobookmarks.com/, James Morris and the XoopsInfo team
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	1.2
* @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version	$Id: icmsaddto.php 8706 2009-05-03 10:11:14Z icmsunderdog $
*/


if (!defined("ICMS_ROOT_PATH")) {
    die("ICMS root path not defined");
}

class IcmsAddTo {

	public $_layout;
	public $_method;

	/**
	 * Constructor of IcmsAddTo
	 *
	 * @param int $layout 0=Horizontal 1 row, 1=Horizontal 2 rows, 2=Vertical with icons, 3=Vertical no icons
	 * @param int $method 0=directpage, 1=popup
	 */
	function IcmsAddTo($layout=0, $method=1) {
		$layout = intval($layout);
		if ($layout < 0 || $layout > 3) {
			$layout = 0;
		}
		$this->_layout = $layout;

		$method = intval($method);
		if ($method < 0 || $method > 1) {
			$method = 1;
		}
		$this->_method = $method;
	}

	/**
	* Render the social bookmark block
	* 
	* @param bool  $fetchonly  only fetch the information and don't display it yet
	* 
	* @return string  the rendered social bookmark block or none if displayed immediately
	*/
	function render($fetchOnly=false)
	{
		global $xoTheme, $xoopsTpl;

		$xoTheme->addStylesheet(ICMS_URL . '/modules/system/templates/system_addto'.(( defined("_ADM_USE_RTL") && _ADM_USE_RTL )?'_rtl':'').'.css');

		$xoopsTpl->assign('icms_addto_method', $this->_method);
		$xoopsTpl->assign('icms_addto_layout', $this->_layout);
		
		if(file_exists(ICMS_URL . '/language/'.$GLOBALS['icmsConfig']['language'].'/addto')){
		$xoopsTpl->assign('icms_addto_url', ICMS_URL . '/language/'.$GLOBALS['icmsConfig']['language'].'/addto/');
		}else{
		$xoopsTpl->assign('icms_addto_url', ICMS_URL . '/language/english/addto/');
		}

		if ($fetchOnly) {
			return $xoopsTpl->fetch('db:system_block_socialbookmark.html' );
		} else {
			$xoopsTpl->display( 'db:system_block_socialbookmark.html' );
		}
	}

	/**
	* Renders the social bookmarks for a block
	* 
	* @return array  The rendered block array
	*/
	function renderForBlock()
	{
		global $xoTheme;

		$xoTheme->addStylesheet(ICMS_URL . '/modules/system/templates/system_addto'.(( defined("_ADM_USE_RTL") && _ADM_USE_RTL )?'_rtl':'').'.css');

		$block = array();
		$block['icms_addto_method'] = $this->_method;
		$block['icms_addto_layout'] = $this->_layout;
		if(file_exists(ICMS_URL . '/language/'.$GLOBALS['icmsConfig']['language'].'/addto')){
		$block['icms_addto_url'] = ICMS_URL . '/language/'.$GLOBALS['icmsConfig']['language'].'/addto/';
		}else{
		$block['icms_addto_url'] = ICMS_URL . '/language/english/addto/';
		}

		return $block;
	}
}
?>