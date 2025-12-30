<?php
/**
 * Class for handling SMS messaging via Twilio
 *
 * Parallels icms_messaging_EmailHandler architecture
 * Provides SMS sending capability for the notification system
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 * @copyright	(c) 2024 The Formulize Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

/**
 * SMS Handler Class.
 *
 * Handles sending SMS messages via Twilio API
 * Configuration pulled from trust folder constants
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 */
class icms_messaging_SmsHandler {

	/**
	 * Twilio Account SID
	 * @var string
	 * @access private
	 */
	private $accountSid;

	/**
	 * Twilio Auth Token
	 * @var string
	 * @access private
	 */
	private $authToken;

	/**
	 * Twilio From Number
	 * @var string
	 * @access private
	 */
	private $fromNumber;

	/**
	 * Twilio API URL
	 * @var string
	 * @access private
	 */
	private $apiUrl;

	/**
	 * Error messages
	 * @var array
	 * @access private
	 */
	private $errors = array();

	/**
	 * Constructor
	 *
	 * Initialize SMS handler with credentials from trust folder
	 */
	public function __construct() {
		$this->accountSid = defined('TWILIO_ACCOUNT_SID') ? TWILIO_ACCOUNT_SID : '';
		$this->authToken = defined('TWILIO_AUTH_TOKEN') ? TWILIO_AUTH_TOKEN : '';
		$this->fromNumber = defined('TWILIO_FROM_NUMBER') ? TWILIO_FROM_NUMBER : '';

		if (!empty($this->accountSid)) {
			$this->apiUrl = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
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
		// Validate configuration
		if (empty($this->accountSid) || empty($this->authToken) || empty($this->fromNumber)) {
			return "SMS not configured - missing Twilio credentials";
		}

		// Normalize phone number
		$to = $this->normalizePhone($phone);

		// Prepare message data
		$data = array(
			'From' => $this->fromNumber,
			'To' => $to,
			'Body' => $message,
		);

		// Send via cURL (Twilio API)
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
	 *
	 * @param string $phone Raw phone number
	 * @return string Normalized phone number
	 */
	private function normalizePhone($phone) {
		// Strip all non-numeric characters
		$clean = preg_replace("/[^0-9]/", '', $phone);

		// Add +1 prefix for North America (matching existing logic)
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
