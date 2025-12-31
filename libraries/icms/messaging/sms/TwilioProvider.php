<?php
/**
 * Twilio SMS Provider Implementation
 *
 * Implements SMS sending via Twilio API
 * Configuration constants (defined in trust folder):
 * - SMS_ACCOUNT_SID or TWILIO_ACCOUNT_SID
 * - SMS_AUTH_TOKEN or TWILIO_AUTH_TOKEN
 * - SMS_FROM_NUMBER or TWILIO_FROM_NUMBER
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
 * Twilio SMS Provider
 *
 * Sends SMS messages via Twilio REST API
 * Supports both generic SMS_* and TWILIO_* constant naming
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 */
class icms_messaging_sms_TwilioProvider implements icms_messaging_sms_ProviderInterface {

	/**
	 * Twilio Account SID
	 * @var string
	 */
	private $accountSid;

	/**
	 * Twilio Auth Token
	 * @var string
	 */
	private $authToken;

	/**
	 * Twilio From Number
	 * @var string
	 */
	private $fromNumber;

	/**
	 * Twilio API URL
	 * @var string
	 */
	private $apiUrl;

	/**
	 * Error messages
	 * @var array
	 */
	private $errors = array();

	/**
	 * Constructor
	 *
	 * Loads credentials from trust folder constants
	 * Supports both SMS_* (generic) and TWILIO_* (provider-specific) naming
	 */
	public function __construct() {
		// Try generic SMS_* constants first, fall back to TWILIO_* for backward compatibility
		$this->accountSid = defined('SMS_ACCOUNT_SID') ? SMS_ACCOUNT_SID : '';

		$this->authToken = defined('SMS_AUTH_TOKEN') ? SMS_AUTH_TOKEN : '';

		$this->fromNumber = defined('SMS_FROM_NUMBER') ? SMS_FROM_NUMBER : '';

		if (!empty($this->accountSid)) {
			$this->apiUrl = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
		}
	}

	/**
	 * Send SMS message via Twilio
	 *
	 * @param string $phone Destination phone number
	 * @param string $message Message body
	 * @return string|false Returns error message on failure, false on success
	 */
	public function send($phone, $message) {
		// Validate configuration
		if (empty($this->accountSid) || empty($this->authToken) || empty($this->fromNumber)) {
			return "SMS not configured - missing credentials (SMS_ACCOUNT_SID, SMS_AUTH_TOKEN, SMS_FROM_NUMBER)";
		}

		// Normalize phone number to E.164 format
		$to = $this->normalizePhone($phone);

		// Prepare message data for Twilio API
		$data = array(
			'From' => $this->fromNumber,
			'To' => $to,
			'Body' => $message,
		);

		// Send via cURL to Twilio REST API
		$post = http_build_query($data);
		$curl = curl_init($this->apiUrl);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, "{$this->accountSid}:{$this->authToken}");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post);

		$response = curl_exec($curl);
		$error = curl_error($curl);

		// Return error or false (success)
		return $error ? $error : false;
	}

	/**
	 * Normalize phone number for Twilio E.164 format
	 *
	 * Strips all non-numeric characters and adds +1 prefix for North America
	 * Override this method if you need different phone number formatting
	 *
	 * @param string $phone Raw phone number
	 * @return string Normalized phone number (+1XXXXXXXXXX)
	 */
	protected function normalizePhone($phone) {
		// Strip all non-numeric characters
		$clean = preg_replace("/[^0-9]/", '', $phone);

		// Add +1 prefix for North America
		// TODO: Make country code configurable for international use
		return "+1" . $clean;
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
