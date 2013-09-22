<?php
/**
 * Class To make printer friendly texts.
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		View
 * @subpackage	PrinterFriendly
 * @since		1.2
 * @author		ImpressCMS
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: Printerfriendly.php 11764 2012-07-01 03:10:47Z skenow $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

/**
 * Class to manage a printer friendly page
 * @category	ICMS
 * @package		View
 * @subpackage	PrinterFriendly
 * @author The IcmsFactory <www.smartfactory.ca>
 */
class icms_view_Printerfriendly {

	public $_title;
	public $_dsc;
	public $_content;
	public $_tpl;
	public $_pageTitle = FALSE;
	public $_width = 680;

	/**
	 * Constructor
	 *
	 * @param field_type bare_field_name
	 */
	public function __construct($content, $title = FALSE, $dsc = FALSE) {
		$this->_title = $title;
		$this->_dsc = $dsc;
		$this->_content = $content;
	}

	public function setPageTitle($text) {
		$this->_pageTitle = $text;
	}

	public function setWidth($width) {
		$this->_width = $width;
	}

	public function render() {
		/**
		 * @todo move the output to a template
		 * @todo make the output XHTML compliant
		 */

		$this->_tpl = new icms_view_Tpl();

		$this->_tpl->assign('icms_print_pageTitle', $this->_pageTitle ? $this->_pageTitle : $this->_title);
		$this->_tpl->assign('icms_print_title', $this->_title);
		$this->_tpl->assign('icms_print_dsc', $this->_dsc);
		$this->_tpl->assign('icms_print_content', $this->_content);
		$this->_tpl->assign('icms_print_width', $this->_width);

		$current_urls = icms_getCurrentUrls();
		$current_url = $current_urls['full'];

		$this->_tpl->assign('icms_print_currenturl', $current_url);
		$this->_tpl->assign('icms_print_url', $this->url);

		$this->_tpl->display('db:system_print.html');
	}

	/**
	 * Generates a printer friendly version of a page
	 *
	 * @param	string	$content	The HTML content of the page
	 * @param	string	$title		The title of the page
	 * @param	string	$description	The description of the page
	 * @param	string	$pagetitle
	 * @param	int		$width		The width of the page, in pixels
	 */
	static public function generate($content, $title = FALSE, $description = FALSE, $pagetitle = FALSE, $width = 680) {
		$PrintDataBuilder = new self($content, $title, $description);
		$PrintDataBuilder->setPageTitle($pagetitle);
		$PrintDataBuilder->setWidth($width);
		$PrintDataBuilder->render();
	}

}

