<?php
/**
 * Handles all message functions within ImpressCMS
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @version	$Id: xoopsmailer.php 20312 2010-11-03 03:08:54Z skenow $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

icms_loadLanguageFile('core', 'mail');

/**
 * Class for sending messages.
 *
 * @deprecated	use icms_messaging_Handler instead.
 *
 */
class XoopsMailer extends icms_messaging_Handler {

	private $_deprecated;

	public function __construct() {
		parent::__construct();
		$this->_deprecated = icms_core_Debug::setDeprecated('icms_messaging_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	}
}
