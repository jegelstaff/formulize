<?php
/**
* Functions to extend PHPMailer to email the users
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	MultiMailer
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: xoopsmultimailer.php 9781 2010-01-26 19:17:43Z malanciault $
*/

if (!defined("ICMS_ROOT_PATH")) {
    die("ImpressCMS root path not defined");
}
/**
 * @package		class
 * @subpackage	mail
 *
 * @filesource
 *
 * @author		Jochen Büînagel	<jb@buennagel.com>
 * @copyright	copyright (c) 2000-2003 The XOOPS Project (http://www.xoops.org)
 *
 * @version		$Revision: 1083 $ - $Date: 2007-10-16 12:42:51 -0400 (mar., 16 oct. 2007) $
 */

/**
 * load the base class
 */
require_once(ICMS_LIBRARIES_PATH.'/phpmailer/class.phpmailer.php');

/**
 * Mailer Class.
 *
 * At the moment, this does nothing but send email through PHP's "mail()" function,
 * but it has the abiltiy to do much more.
 *
 * If you have problems sending mail with "mail()", you can edit the member variables
 * to suit your setting. Later this will be possible through the admin panel.
 *
 * @todo		Make a page in the admin panel for setting mailer preferences.
 *
 * @package		class
 * @subpackage	mail
 *
 * @author		Jochen Buennagel	<job@buennagel.com>
 * @copyright	(c) 2000-2003 The Xoops Project - www.xoops.org
 * @version		$Revision: 1083 $ - changed by $Author$ on $Date: 2007-10-16 12:42:51 -0400 (mar., 16 oct. 2007) $
 */
class XoopsMultiMailer extends PHPMailer {

	/**
	 * "from" address
	 * @var 	string
	 * @access	private
	 */
	var $From 		= "";

	/**
	 * "from" name
	 * @var 	string
	 * @access	private
	 */
	var $FromName 	= "";

	// can be "smtp", "sendmail", or "mail"
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
	var $Mailer		= "mail";

	/**
	 * set if $Mailer is "sendmail"
	 *
	 * Only used if {@link $Mailer} is set to "sendmail".
	 * Contains the full path to your sendmail program or replacement.
	 * @var 	string
	 * @access	private
	 */
	var $Sendmail = "/usr/sbin/sendmail";

	/**
	 * SMTP Host.
	 *
	 * Only used if {@link $Mailer} is set to "smtp"
	 * @var 	string
	 * @access	private
	 */
	var $Host		= "";

	/**
   * Sets connection prefix.
   * Options are "", "ssl" or "tls"
   * @var string
   */
  var $SMTPSecure = "";

	/**
	 * Does your SMTP host require SMTPAuth authentication?
	 * @var 	boolean
	 * @access	private
	 */
	var $SMTPAuth	= FALSE;

	/**
	 * Username for authentication with your SMTP host.
	 *
	 * Only used if {@link $Mailer} is "smtp" and {@link $SMTPAuth} is TRUE
	 * @var 	string
	 * @access	private
	 */
	var $Username	= "";

	/**
	 * Password for SMTPAuth.
	 *
	 * Only used if {@link $Mailer} is "smtp" and {@link $SMTPAuth} is TRUE
	 * @var 	string
	 * @access	private
	 */
	var $Password	= "";

	/**
	 * Sets default SMTP Port to use?
	 * @var 	boolean
	 * @access	private
	 */
	var $Port	= 25;

	/**
	 * Constuctor
	 *
	 * @access public
	 * @return void
	 *
	 * @global	$icmsConfig
	 */
	function XoopsMultiMailer(){
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
			// TODO: change value type of xoopsConfig "smtphost" from array to text
			$this->Host = implode(';',$icmsConfigMailer['smtphost']);
			$this->Username = $icmsConfigMailer['smtpuser'];
			$this->Password = $icmsConfigMailer['smtppass'];
			$this->Port = $icmsConfigMailer['smtpauthport'];
		} else {
			$this->Mailer = $icmsConfigMailer['mailmethod'];
			$this->SMTPAuth = false;
			$this->Sendmail = $icmsConfigMailer['sendmailpath'];
			$this->Host = implode(';',$icmsConfigMailer['smtphost']);
		}
		$this->CharSet = strtolower( _CHARSET );
		$this->SetLanguage( 'en', ICMS_LIBRARIES_PATH . "/phpmailer/language/" );
		$this->PluginDir = ICMS_LIBRARIES_PATH."/phpmailer/";
	}



	/**
   * Formats an address correctly. This overrides the default addr_format method which does not seem to encode $FromName correctly
   * @access private
   * @param string    $addr the email address to be formatted
   * @return string   the formatted string (address)
   */
  function AddrFormat($addr) {
    if(empty($addr[1]))
        $formatted = $addr[0];
    else
        $formatted = sprintf('%s <%s>', '=?'.$this->CharSet.'?B?'.base64_encode($addr[1]).'?=', $addr[0]);

    return $formatted;
  }
}


?>