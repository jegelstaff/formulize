<?php
/**
 * Class for handling email, extending PHPMailer to email the users
 *
 * @category	ICMS
 * @package		Messaging
 * @subpackage	Email
 * @copyright	(c) 2007-2008 The ImpressCMS Project - www.impresscms.org
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @version		SVN: $Id: EmailHandler.php 22529 2011-09-02 19:55:40Z phoenyx $
 */

/**
 * load the base class
 */
require_once ICMS_LIBRARIES_PATH . '/phpmailer/class.phpmailer.php';

/**
 * Mailer Class.
 *
 * @category	ICMS
 * @package		Core
 * @subpackage	Mail
 */
class icms_messaging_EmailHandler extends PHPMailer {

	/**
	 * "from" address
	 * @var 	string
	 * @access	private
	 */
	public $From 		= "";

	/**
	 * "from" name
	 * @var 	string
	 * @access	private
	 */
	public $FromName 	= "";

	/**
	 * Method to be used when sending the mail.
	 *
	 * This can be:
	 * <li>mail (standard PHP function "mail()") (default)
	 * <li>smtp	(send through any SMTP server, SMTPAuth is supported.
	 * You must set {@link $Host}, for SMTPAuth also {@link $SMTPAuth},
	 * {@link $Username}, and {@link $Password}.)
	 * <li>sendmail (manually set the path to your sendmail program
	 * to something different than "mail()" uses in {@link $Sendmail})
	 *
	 * @var 	string
	 * @access	private
	 */
	public $Mailer		= "mail";

	/**
	 * set if $Mailer is "sendmail"
	 *
	 * Only used if {@link $Mailer} is set to "sendmail".
	 * Contains the full path to your sendmail program or replacement.
	 * @var 	string
	 * @access	private
	 */
	public $Sendmail = "/usr/sbin/sendmail";

	/**
	 * SMTP Host.
	 *
	 * Only used if {@link $Mailer} is set to "smtp"
	 * @var 	string
	 * @access	private
	 */
	public $Host		= "";

	/**
	 * Sets connection prefix.
	 * Options are "", "ssl" or "tls"
	 * @var string
	 */
	public $SMTPSecure = "";

	/**
	 * Does your SMTP host require SMTPAuth authentication?
	 * @var 	boolean
	 * @access	private
	 */
	public $SMTPAuth	= FALSE;

	/**
	 * Username for authentication with your SMTP host.
	 *
	 * Only used if {@link $Mailer} is "smtp" and {@link $SMTPAuth} is TRUE
	 * @var 	string
	 * @access	private
	 */
	public $Username	= "";

	/**
	 * Password for SMTPAuth.
	 *
	 * Only used if {@link $Mailer} is "smtp" and {@link $SMTPAuth} is TRUE
	 * @var 	string
	 * @access	private
	 */
	public $Password	= "";

	/**
	 * Sets default SMTP Port to use?
	 * @var 	boolean
	 * @access	private
	 */
	public $Port	= 25;

	/**
	 * Constuctor
	 *
	 * @access public
	 * @return void
	 *
	 * @global	$icmsConfig
	 */
	public function __construct() {
		global $icmsConfig, $icmsConfigMailer;
		$this->From = $icmsConfigMailer['from'];
		if ($this->From == '') {
			$this->From = $icmsConfig['adminmail'];
		}
		$this->Sender = $this->From;

		if ($icmsConfigMailer["mailmethod"] == "smtpauth") {
			$this->Mailer = "smtp";
			$this->SMTPAuth = true;
			$this->SMTPSecure = $icmsConfigMailer['smtpsecure'];
			// TODO: change value type of icmsConfigMailer "smtphost" from array to text
			$this->Host = implode(';',$icmsConfigMailer['smtphost']);
			$this->Username = $icmsConfigMailer['smtpuser'];
			$this->Password = $icmsConfigMailer['smtppass'];
			$this->Port = $icmsConfigMailer['smtpauthport'];
		} else {
			$this->Mailer = $icmsConfigMailer['mailmethod'];
			$this->SMTPAuth = false;
			$this->Sendmail = $icmsConfigMailer['sendmailpath'];
			$this->Host = implode(';', $icmsConfigMailer['smtphost']);
		}
		$this->CharSet = strtolower(_CHARSET);
		$this->SetLanguage('en', ICMS_LIBRARIES_PATH . "/phpmailer/language/");
		$this->PluginDir = ICMS_LIBRARIES_PATH . "/phpmailer/";
	}

	/**
	 * Formats an address correctly. This overrides the default addr_format method which does not seem to encode $FromName correctly
	 * @access private
	 * @param string    $addr the email address to be formatted
	 * @return string   the formatted string (address)
	 */
	public function AddrFormat($addr) {
		if (empty($addr[1])) {
			$formatted = $addr[0];
		} else {
			$formatted = sprintf('%s <%s>', '=?'. $this->CharSet . '?B?' . base64_encode($addr[1]) . '?=', $addr[0]);
		}
		return $formatted;
	}

	// to be overidden by lang specific mail class, if needed
	public function encodeFromName($text) {
		return $text;
	}

	// to be overidden by lang specific mail class, if needed
	public function encodeSubject($text) {
		return $text;
	}

	// to be overidden by lang specific mail class, if needed
	public function encodeBody(&$text) {
		return $text;
	}

}
