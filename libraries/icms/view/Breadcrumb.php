<?php
/**
 * Navigation breadcrumbs
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		View
 * @subpackage	Breadcrumb
 * @author		marcan <marcan@impresscms.org>
 * @version		SVN: $Id: Breadcrumb.php 20449 2010-12-02 00:25:05Z skenow $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Breadcrumb
 *
 * Managing page breadcrumb
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		View
 * @subpackage	Breadcrumb
 * @author		marcan <marcan@impresscms.org>
 */
class icms_view_Breadcrumb {

	private $_tpl;
	private $items;

	/**
	 * Constructor
	 * @param array $items An array of items for the breadcrumb
	 */
	public function __construct($items) {
		$this->items = $items;
	}

	/**
	 * Adds the breadcrumb items to the template
	 * @param boolean $fetchOnly Whether to display the breadcrumbs, or not
	 */
	public function render($fetchOnly = FALSE) {

		$this->_tpl = new icms_view_Tpl();
		$this->_tpl->assign('icms_breadcrumb_items', $this->items);

		if ($fetchOnly) {
			return $this->_tpl->fetch('db:system_breadcrumb.html');
		} else {
			$this->_tpl->display('db:system_breadcrumb.html');
		}
	}
}
