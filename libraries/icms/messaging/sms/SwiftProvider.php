<?php
/**
 * Swift SMS Gateway Provider Implementation
 *
 * Implements SMS sending via the Swift SMS Gateway REST API (Canadian provider)
 * API reference: https://secure.smsgateway.ca/docs/index.html
 *
 * Configuration (Site → Integrations, or constants in the trust folder):
 * - SMS provider: Swift (or define('SMS_PROVIDER', 'Swift'))
 * - SMS_ACCOUNT_SID: your Swift account key (required)
 * - SMS_AUTH_TOKEN: not used by Swift, leave blank
 * - SMS_FROM_NUMBER: optional. Leave blank to send from Swift's shared number pool. Set it to one of
 *   your account's dedicated long codes to send from that number instead.
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 * @copyright	(c) 2026 The Formulize Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

require_once ICMS_ROOT_PATH . '/libraries/icms/messaging/sms/ProviderInterface.php';

/**
 * Swift SMS Gateway Provider
 *
 * Sends SMS messages via the Swift SMS Gateway REST API. Swift authenticates with a single
 * account key carried in the URL path, so there is no secret/token. Messages go out from Swift's
 * shared number pool unless a dedicated sender number is configured.
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	SMS
 */
class icms_messaging_sms_SwiftProvider implements icms_messaging_sms_ProviderInterface {

	/**
	 * Name shown in the SMS provider setting
	 */
	const LABEL = 'Swift SMS Gateway';

	/**
	 * Swift account key (stored in SMS_ACCOUNT_SID)
	 * @var string
	 */
	private $accountKey;

	/**
	 * Dedicated long code to send from (stored in SMS_FROM_NUMBER), blank for the shared pool
	 * @var string
	 */
	private $senderNumber;

	/**
	 * Swift REST API base URL
	 * @var string
	 */
	private $apiUrl = "https://secure.smsgateway.ca/services/message.svc";

	/**
	 * Error messages
	 * @var array
	 */
	private $errors = array();

	/**
	 * Constructor
	 *
	 * Loads credentials from the managed config settings, falling back to trust folder constants
	 */
	public function __construct() {
		$this->accountKey = trim(icms_messaging_SmsHandler::getSmsConfig('sms_account_sid', 'SMS_ACCOUNT_SID'));
		$this->senderNumber = trim(icms_messaging_SmsHandler::getSmsConfig('sms_from_number', 'SMS_FROM_NUMBER'));
	}

	/**
	 * Send SMS message via Swift SMS Gateway
	 *
	 * @param string $phone Destination phone number
	 * @param string $message Message body
	 * @return string|false Returns error message on failure, false on success
	 */
	public function send($phone, $message) {
		// Validate configuration
		if (empty($this->accountKey)) {
			return "SMS not configured - missing Swift account key (SMS_ACCOUNT_SID)";
		}

		$to = $this->normalizePhone($phone);
		if ($to === '') {
			return "No phone number to send the text message to";
		}

		// Shared pool: the Extended method, which reports success/failure as structured JSON.
		// Dedicated number: the ViaDedicated method, which requires the sender to be a long code on the account.
		$url = $this->apiUrl . '/' . rawurlencode($this->accountKey) . '/' . rawurlencode($to);
		$data = array('MessageBody' => $message);
		if ($this->senderNumber !== '') {
			$url .= '/ViaDedicated';
			$data['SenderNumber'] = $this->normalizePhone($this->senderNumber);
		} else {
			$url .= '/Extended';
		}

		// Send via cURL to Swift REST API
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

		$response = curl_exec($curl);
		$error = curl_error($curl);
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if ($error) {
			return $this->fail("Swift error: " . $error);
		}

		return $this->interpretResponse($response, $httpCode);
	}

	/**
	 * Work out whether Swift accepted the message
	 *
	 * The Extended method returns an object with QueuedSuccessfully and ErrorMessage. The other send
	 * methods return a plain status string, ie: "Message queued successfully" or a description of the
	 * problem such as "Cell number is invalid". Both come back JSON-encoded, but we tolerate a bare string too.
	 *
	 * @param string $response Raw response body
	 * @param int $httpCode HTTP status code
	 * @return string|false Returns error message on failure, false on success
	 */
	protected function interpretResponse($response, $httpCode) {
		$result = json_decode($response, true);

		if (is_array($result) && array_key_exists('QueuedSuccessfully', $result)) {
			if ($result['QueuedSuccessfully']) {
				return false; // Success
			}
			$errorText = !empty($result['ErrorMessage']) ? $result['ErrorMessage'] : 'Unknown Swift error';
			return $this->fail("Swift error: " . $errorText);
		}

		$status = is_string($result) ? $result : trim(strip_tags((string) $response));
		if ($httpCode < 400 && stripos($status, 'queued successfully') !== false) {
			return false; // Success
		}
		if ($status === '') {
			$status = "no response (HTTP $httpCode)";
		}
		return $this->fail("Swift error: " . substr($status, 0, 255));
	}

	/**
	 * Normalize phone number for Swift
	 *
	 * Strips all non-numeric characters and adds the North American country code to 10 digit numbers
	 * Override this method if you need different phone number formatting
	 *
	 * @param string $phone Raw phone number
	 * @return string Normalized phone number (1XXXXXXXXXX)
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
	 * Record an error and hand it back for returning from send()
	 *
	 * @param string $message Error message
	 * @return string
	 */
	private function fail($message) {
		$this->errors[] = $message;
		return $message;
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
