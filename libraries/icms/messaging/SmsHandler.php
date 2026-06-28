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
		// Determine which provider to use: config setting first, then the legacy
		// trust-folder constant, then the Twilio default.
		$providerName = self::getSmsConfig('sms_provider', 'SMS_PROVIDER');
		if ($providerName === '') {
			$providerName = 'Twilio';
		}

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

	/**
	 * Resolve an SMS setting: prefer the managed config item (mailer category),
	 * fall back to the legacy trust-folder constant, then to an empty string.
	 * Lets sites keep using trust constants while allowing the admin UI to take over.
	 *
	 * @param string $configName   The conf_name of the setting (e.g. 'sms_account_sid')
	 * @param string $constantName The legacy trust constant to fall back to (e.g. 'SMS_ACCOUNT_SID')
	 * @return string
	 */
	public static function getSmsConfig($configName, $constantName = '') {
		static $mailerConfig = null;
		if ($mailerConfig === null) {
			$mailerConfig = array();
			if (class_exists('icms')) {
				$config_handler = icms::handler('icms_config');
				if (is_object($config_handler)) {
					$cat = defined('ICMS_CONF_MAILER') ? ICMS_CONF_MAILER : 6;
					$fetched = $config_handler->getConfigsByCat($cat, 0);
					if (is_array($fetched)) {
						$mailerConfig = $fetched;
					}
				}
			}
		}
		if (isset($mailerConfig[$configName]) && $mailerConfig[$configName] !== '') {
			return $mailerConfig[$configName];
		}
		if ($constantName !== '' && defined($constantName)) {
			return constant($constantName);
		}
		return '';
	}
}
