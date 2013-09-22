<?php
/**
* Class responsible for managing visible positions for banner objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: VisibleinHandler.php 20209 2010-09-26 13:41:19Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_banners_VisibleinHandler extends icms_ipf_Handler {
	/**
	 * Constructor
	 *
	 * @param object $db database object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'visiblein', 'visiblein_id', '', '', 'banners');
	}
}