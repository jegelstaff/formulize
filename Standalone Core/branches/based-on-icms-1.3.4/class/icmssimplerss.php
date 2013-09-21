<?php
/**
 * Class handling RSS feeds, using SimplePie class
 *
 * SimplePie is a very fast and easy-to-use class, written in PHP, that puts the ?simple? back into ?really simple syndication?.
 * Flexible enough to suit beginners and veterans alike, SimplePie is focused on speed, ease of use, compatibility and
 * standards compliance.
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2
 * @author		malanciault <marcan@impresscms.org)
 * @deprecated	use icms_feeds_Simplerss, instead
 * @todo		Remove in version 1.4
 * @version		$Id: icmssimplerss.php 10762 2010-11-15 20:26:46Z skenow $
 */


class IcmsSimpleRss extends icms_feeds_Simplerss {

	private $_deprecated;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_feeds_Simplerss', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}