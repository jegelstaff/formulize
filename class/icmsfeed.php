<?php
/**
 *
 * Module RSS Feed Class
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package	    core
 * @since		1.1
 * @deprecated	Use icms_feeds_Rss, instead
 * @todo		Remove in version 1.4
 * @author		Ignacio Segura, "Nachenko"
 * @version		$Id: icmsfeed.php 20369 2010-11-15 20:26:46Z skenow $
 */

defined('ICMS_ROOT_PATH') or exit();

class IcmsFeed extends icms_feeds_Rss {

	private $_deprecated;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_feeds_Rss', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}
