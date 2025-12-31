<?php
/**
 * Interface for SMS provider implementations
 *
 * Defines the contract that all SMS providers must implement
 * Allows easy addition of new SMS services without modifying core code
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 * @copyright	(c) 2024 The Formulize Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 * SMS Provider Interface
 *
 * All SMS provider implementations must implement this interface
 * Ensures consistent API across different SMS services
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 */
interface icms_messaging_sms_ProviderInterface {

	/**
	 * Send SMS message
	 *
	 * @param string $phone Destination phone number
	 * @param string $message Message body
	 * @return string|false Returns error message on failure, false on success
	 */
	public function send($phone, $message);

	/**
	 * Get error messages
	 *
	 * @return array Array of error messages
	 */
	public function getErrors();
}
