<?php
/**
 * icms_core_StopSpammer object
 *
 * This class is responsible for cross referencing register information with StopForumSpam.com API
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Core
 * @subpackage	StopSpammer
 * @since		1.2
 * @author		marcan <marcan@impresscms.org>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: StopSpammer.php 21913 2011-06-29 13:39:21Z blauer-fisch $
 */
/**
 * Checks usernames, emails and ip addresses against a blacklist
 *
 *
 * @category	ICMS
 * @package		Core
 *
 */
class icms_core_StopSpammer {
	private $api_url;

	/**
	 * Constructor
	 */
	public function __construct() {
		// checkin stopforumspam API
		$this->api_url = "http://www.stopforumspam.com/api?";
	}

	/**
	 * Check the StopForumSpam API for a specific field (username, email or IP)
	 *
	 * @param string $field field to check
	 * @param string $value value to validate
	 * @return true if spammer was found with passed info
	 */
	public function checkForField($field, $value) {
		$spam = false;

		return $spam; // MODIFIED BY FREEFORM SOLUTIONS for compatibility with offline installs.  SUGGESTED BY SKENOW HERE: http://www.freeformsolutions.ca/en/forum/using-formulize-no-internet-access#comment-4554

		$url = $this->api_url . $field . '=' . urlencode($value);
		if (!ini_get('allow_url_fopen')) {
			$output = '';
			$ch = curl_init();
			if (!curl_setopt($ch, CURLOPT_URL, "$url")) {
				icms_core_Debug::message($this->api_url . $field . '=' . $value);
				echo "<script> alert('" . _US_SERVER_PROBLEM_OCCURRED . "'); window.history.go(-1); </script>\n";
			}
			curl_setopt($ch, CURLOPT_URL, "$url");
			curl_setopt($ch, CURLOPT_HEADER,0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output .= curl_exec($ch);
			curl_close($ch);

			if (preg_match("#<appears>(.*)</appears>#i", $output, $out)) {
				$spam = $out[1];
			}
		} else {
			$file = fopen($url, "r");
			if (!$file) {
				icms_core_Debug::message($this->api_url . $field . '=' . $value);
				echo "<script> alert('" . _US_SERVER_PROBLEM_OCCURRED . "'); window.history.go(-1); </script>\n";
			}
			while (!feof($file)) {
				$line = fgets($file, 1024);
				if (preg_match("#<appears>(.*)</appears>#i", $line, $out)) {
					$spam = $out[1];
					break;
				}
			}
			fclose($file);
		}
		return $spam == 'yes';
	}

	/**
	 * Check the StopForumSpam API for specified username
	 *
	 * @param string $username username to check
	 * @return true if spammer was found with this username
	 */
	public function badUsername($username) {
		return $this->checkForField('username', $username);
	}

	/**
	 * Check the StopForumSpam API for specified email
	 *
	 * @param string $email email to check
	 * @return true if spammer was found with this email
	 */
	public function badEmail($email) {
		return $this->checkForField('email', $email);
	}

	/**
	 * Check the StopForumSpam API for specified IP
	 *
	 * @param string $ip ip to check
	 * @return true if spammer was found with this IP
	 */
	public function badIP($ip) {
	    // return TRUE if it's not a valid IP
	    if (!filter_var($ip, FILTER_VALIDATE_IP)) return TRUE;
	    // return FALSE if it is a valid IPv6 address - only until IPv6 can be checked without error
	    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { 
	        return FALSE;
	    }
	    return $this->checkForField('ip', $ip);
	}
}
