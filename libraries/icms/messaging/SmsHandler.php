<?php
/**
 * Factory class for SMS messaging providers
 *
 * Provides provider-agnostic SMS sending capability
 * Automatically loads the configured provider implementation
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 * @copyright	(c) 2024 The Formulize Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 * SMS Handler Factory Class
 *
 * Creates appropriate SMS provider instance based on configuration
 * Defaults to Twilio if no provider specified
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 */
class icms_messaging_SmsHandler {

	/**
	 * SMS provider instance
	 * @var icms_messaging_sms_ProviderInterface
	 */
	private $provider;

	/**
	 * Constructor
	 *
	 * Loads the configured SMS provider or defaults to Twilio
	 */
	public function __construct() {
		// Determine which provider to use
		$providerName = defined('SMS_PROVIDER') ? SMS_PROVIDER : 'Twilio';

		// Load provider class
		$providerClass = 'icms_messaging_sms_' . $providerName . 'Provider';
		$providerFile = ICMS_ROOT_PATH . '/libraries/icms/messaging/sms/' . $providerName . 'Provider.php';

		if (file_exists($providerFile)) {
			require_once $providerFile;
			if (class_exists($providerClass)) {
				$this->provider = new $providerClass();
			} else {
				// Fallback to Twilio if specified provider class not found
				require_once ICMS_ROOT_PATH . '/libraries/icms/messaging/sms/TwilioProvider.php';
				$this->provider = new icms_messaging_sms_TwilioProvider();
			}
		} else {
			// Fallback to Twilio if specified provider file not found
			require_once ICMS_ROOT_PATH . '/libraries/icms/messaging/sms/TwilioProvider.php';
			$this->provider = new icms_messaging_sms_TwilioProvider();
		}
	}

	/**
	 * Send SMS message
	 *
	 * @param string $phone Destination phone number
	 * @param string $message Message body
	 * @return string|false Returns error message on failure, false on success
	 */
	public function send($phone, $message) {
		return $this->provider->send($phone, $message);
	}

	/**
	 * Get error messages
	 *
	 * @return array Array of error messages
	 */
	public function getErrors() {
		return $this->provider->getErrors();
	}
}
