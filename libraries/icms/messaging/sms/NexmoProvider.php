<?php
/**
 * Vonage (Nexmo) SMS Provider Implementation
 *
 * Implements SMS sending via Vonage/Nexmo API
 * Configuration constants (defined in trust folder):
 * - SMS_ACCOUNT_SID (your Vonage API key)
 * - SMS_AUTH_TOKEN (your Vonage API secret)
 * - SMS_FROM_NUMBER (sender ID/phone number)
 *
 * To use this provider, add to trust folder:
 * define('SMS_PROVIDER', 'Nexmo');
 * define('SMS_ACCOUNT_SID', 'your_api_key');
 * define('SMS_AUTH_TOKEN', 'your_api_secret');
 * define('SMS_FROM_NUMBER', 'YourBrand');
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 * @copyright	(c) 2024 The Formulize Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

require_once ICMS_ROOT_PATH . '/libraries/icms/messaging/sms/ProviderInterface.php';

/**
 * Vonage (Nexmo) SMS Provider
 *
 * Sends SMS messages via Vonage/Nexmo REST API
 * Note: Nexmo was rebranded to Vonage, but the API remains similar
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 */
class icms_messaging_sms_NexmoProvider implements icms_messaging_sms_ProviderInterface {

	/**
	 * Vonage API Key (stored in SMS_ACCOUNT_SID)
	 * @var string
	 */
	private $apiKey;

	/**
	 * Vonage API Secret (stored in SMS_AUTH_TOKEN)
	 * @var string
	 */
	private $apiSecret;

	/**
	 * From number or sender ID
	 * @var string
	 */
	private $fromNumber;

	/**
	 * Vonage API URL
	 * @var string
	 */
	private $apiUrl = "https://rest.nexmo.com/sms/json";

	/**
	 * Error messages
	 * @var array
	 */
	private $errors = array();

	/**
	 * Constructor
	 *
	 * Loads credentials from generic SMS_* constants
	 */
	public function __construct() {
		$this->apiKey = defined('SMS_ACCOUNT_SID') ? SMS_ACCOUNT_SID : '';
		$this->apiSecret = defined('SMS_AUTH_TOKEN') ? SMS_AUTH_TOKEN : '';
		$this->fromNumber = defined('SMS_FROM_NUMBER') ? SMS_FROM_NUMBER : '';
	}

	/**
	 * Send SMS message via Vonage/Nexmo
	 *
	 * @param string $phone Destination phone number
	 * @param string $message Message body
	 * @return string|false Returns error message on failure, false on success
	 */
	public function send($phone, $message) {
		// Validate configuration
		if (empty($this->apiKey) || empty($this->apiSecret) || empty($this->fromNumber)) {
			return "SMS not configured - missing Vonage credentials (SMS_ACCOUNT_SID, SMS_AUTH_TOKEN, SMS_FROM_NUMBER)";
		}

		// Normalize phone number
		$to = $this->normalizePhone($phone);

		// Prepare message data for Vonage API
		$data = array(
			'api_key' => $this->apiKey,
			'api_secret' => $this->apiSecret,
			'from' => $this->fromNumber,
			'to' => $to,
			'text' => $message,
		);

		// Send via cURL to Vonage REST API
		$post = http_build_query($data);
		$curl = curl_init($this->apiUrl);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

		$response = curl_exec($curl);
		$error = curl_error($curl);

		if ($error) {
			return $error;
		}

		// Parse JSON response
		$result = json_decode($response, true);
		if (isset($result['messages'][0]['status']) && $result['messages'][0]['status'] != '0') {
			// Non-zero status means error
			$errorText = isset($result['messages'][0]['error-text']) ?
			             $result['messages'][0]['error-text'] :
			             'Unknown Vonage error';
			return "Vonage error: " . $errorText;
		}

		return false; // Success
	}

	/**
	 * Normalize phone number for Vonage
	 *
	 * Strips all non-numeric characters and adds country code
	 *
	 * @param string $phone Raw phone number
	 * @return string Normalized phone number
	 */
	protected function normalizePhone($phone) {
		// Strip all non-numeric characters
		$clean = preg_replace("/[^0-9]/", '', $phone);

		// Add country code if not present (default to North America)
		if (strlen($clean) == 10) {
			$clean = "1" . $clean;
		}

		return $clean;
	}

	/**
	 * Get error messages
	 *
	 * @return array Array of error messages
	 */
	public function getErrors() {
		return $this->errors;
	}
}
