<?php
/**
*
* Class To make printer friendly texts.
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		1.2
* @author		ImpressCMS
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id$
*/

if (!defined("ICMS_ROOT_PATH")) {
die("ICMS root path not defined");
}

/**
* Class to manage a printer friendly page
* @author The IcmsFactory <www.smartfactory.ca>
*/

class IcmsPrinterFriendly
{
	public $_title;
	public $_dsc;
	public $_content;
	public $_tpl;
	public $_pageTitle = false;
	public $_width = 680;

	function IcmsPrinterFriendly($content, $title=false, $dsc=false)
	{
		$this->_title = $title;
		$this->_dsc = $dsc;
		$this->_content = $content;
	}

	function setPageTitle($text) {
		$this->_pageTitle = $text;
	}

	function setWidth($width) {
		$this->_width = $width;
	}

	function render()
	{
		/**
		 * @todo move the output to a template
		 * @todo make the output XHTML compliant
		 */

		include_once ICMS_ROOT_PATH . '/class/template.php';

		$this->_tpl =& new XoopsTpl();

		$this->_tpl->assign('icms_print_pageTitle', $this->_pageTitle ? $this->_pageTitle : $this->_title);
		$this->_tpl->assign('icms_print_title', $this->_title);
		$this->_tpl->assign('icms_print_dsc', $this->_dsc);
		$this->_tpl->assign('icms_print_content', $this->_content);
		$this->_tpl->assign('icms_print_width', $this->_width);

		$current_urls = smart_getCurrentUrls();
		$current_url = $current_urls['full'];

		$this->_tpl->assign('icms_print_currenturl', $current_url);
		$this->_tpl->assign('icms_print_url', $this->url);

		$this->_tpl->display( 'db:system_print.html' );
	}

}

?>