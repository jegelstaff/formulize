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
 * @version		$Id: icmsprinterfriendly.php 20371 2010-11-15 22:06:37Z skenow $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

/**
 * Class to manage a printer friendly page
 * @category	ICMS
 * @package		View
 * @subpackage	PrinterFriendly
 * @author The IcmsFactory <www.smartfactory.ca>
 * @deprecated	Use icms_view_Printerfriendly, instead
 * @todo		Remove in version 1.4
 */
class IcmsPrinterFriendly extends icms_view_Printerfriendly {

	private $_deprecated;
	/**
	 * Constructor
	 *
	 * @param field_type bare_field_name
	 */
	public function __construct($content, $title=false, $dsc=false) {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_view_Printerfriendly', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}

}

