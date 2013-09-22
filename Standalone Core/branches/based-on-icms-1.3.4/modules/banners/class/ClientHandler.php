<?php
/**
* Class responsible for managing banners client objects
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: ClientHandler.php 20431 2010-11-21 12:40:45Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_banners_ClientHandler extends icms_ipf_Handler {
	private $_clients;

	/**
	 * Constructor
	 *
	 * @param object $db database object
	 */
	public function __construct(&$db) {
		parent::__construct($db, 'client', 'client_id', 'last_name', 'company', 'banners');
	}

	/**
	 * create and return client array for value list
	 *
	 * @return array clients
	 */
	public function getClientArray() {
		if (!count($this->_clients)) {
			$criteria = new icms_db_criteria_Compo();
			$criteria->setSort('last_name');
			$criteria->setOrder('ASC');
			$clients = $this->getObjects($criteria);
			foreach ($clients as $client) {
				$this->_clients[$client->getVar('client_id')] = $client->getFullClientName();
			}
		}
		return $this->_clients;
	}

	/**
	 * generate value list for active filter
	 *
	 * @return array
	 */
	public function getActiveArray() {
		return array(1 => _YES, 0 => _NO);
	}

	/**
	 * get client id for current user
	 *
	 * @param bool $active only return client if client is active
	 * @return mixed false or numeric (client id)
	 */
	public function getUserClientId($active = FALSE) {
		if (!is_object(icms::$user)) return FALSE;
		$clients = $this->getObjects(new icms_db_criteria_Compo(new icms_db_criteria_Item('uid', icms::$user->getVar('uid'))));
		if (count($clients) != 1) return FALSE;
		if ($active == TRUE && $clients[0]->getVar('active') == FALSE) return FALSE;
		return $clients[0]->getVar('client_id');
	}
}