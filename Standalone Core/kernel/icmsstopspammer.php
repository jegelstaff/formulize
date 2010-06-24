<?php
/**
* IcmsStopSpammer object
*
* This class is responsible for cross referencing register information with StopForumSpam.com API
*
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	IcmsPersistableObject
* @since	1.2
* @author		marcan <marcan@impresscms.org>
* @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id
*/
class IcmsStopSpammer {
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
	function checkForField($field, $value) {
		$spam = false;

		$url = $this->api_url . $field . '=' . $value;
		if (!ini_get('allow_url_fopen')) {
			$output = '';
			$ch=curl_init();
			if (!curl_setopt($ch, CURLOPT_URL, "$url")) {
				icms_debug($this->api_url . $field . '=' . $value);
				echo "<script> alert('" . _US_SERVER_PROBLEM_OCCURRED . "'); window.history.go(-1); </script>\n";
			}
			curl_setopt($ch, CURLOPT_URL, "$url");
			curl_setopt($ch, CURLOPT_HEADER,0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output .=curl_exec($ch);
			curl_close($ch);

			if (eregi("<appears>(.*)</appears>", $output, $out)) {
				$spam = $out[1];
			}
		} else {
			$file = fopen($url, "r");
			if (!$file) {
				icms_debug($this->api_url . $field . '=' . $value);
				echo "<script> alert('" . _US_SERVER_PROBLEM_OCCURRED . "'); window.history.go(-1); </script>\n";
			}
			while (!feof($file)) {
				$line = fgets($file, 1024);
				if (eregi("<appears>(.*)</appears>", $line, $out)) {
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
	function badUsername($username) {
		return $this->checkForField('username', $username);
	}

	/**
	 * Check the StopForumSpam API for specified email
	 *
	 * @param string $email email to check
	 * @return true if spammer was found with this email
	 */
	function badEmail($email) {
		return $this->checkForField('email', $email);
	}

	/**
	 * Check the StopForumSpam API for specified IP
	 *
	 * @param string $ip ip to check
	 * @return true if spammer was found with this IP
	 */
	function badIP($ip) {
		return $this->checkForField('ip', $ip);
	}
}

?>