<?php
/**
 * Class to encrypt User Passwords.
 *
 * @category	ICMS
 * @package		Core
 * @since		1.2
 * @author		vaughan montgomery (vaughan@impresscms.org)
 * @author		ImpressCMS Project
 * @copyright	(c) 2007-2010 The ImpressCMS Project - www.impresscms.org
 * @version		SVN: $Id: Password.php 20920 2011-03-04 15:18:04Z m0nty_ $
 **/
/**
 * Password generation and validation
 *
 * @category	ICMS
 * @package		Core
 *
 */
final class icms_core_Password {

	private $pass, $salt, $mainSalt = XOOPS_DB_SALT, $uname;

	/**
	 * Constructor for the Password class
	 */
	public function __construct() {
	}

	/**
	 * Access the only instance of this class
	 * @return       object
	 * @static       $instance
	 * @staticvar    object
	 **/
	static public function getInstance() {
		static $instance;
		if (!isset($instance)) {
			$instance = new icms_core_Password();
		}
		return $instance;
	}

	// ***** Private Functions *****

	/**
	* This Private Function checks whether a users password has been expired
	* @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	* @since    1.1
	* @param    string  $uname      The username of the account to be checked
	* @return   bool     returns true if password is expired, false if password is not expired.
	*/
	private function priv_passExpired($uname) {
		if (!isset($uname) || (isset($uname) && $uname == '')) {
			redirect_header('user.php', 2, _US_SORRYNOTFOUND);
		}

		$uname = @htmlspecialchars($uname, ENT_QUOTES, _CHARSET);

		$sql = icms::$xoopsDB->query(sprintf("SELECT pass_expired FROM %s WHERE uname = %s",
			icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
		list($pass_expired) = icms::$xoopsDB->fetchRow($sql);

		if ($pass_expired == 1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * This Private Function returns the User Salt key belonging to username.
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $uname      Username to find User Salt key for.
	 * @return   string  returns the Salt key of the user.
	 */
	private function priv_getUserSalt($uname) {
		if (!isset($uname) || (isset($uname) && $uname == '')) {
			redirect_header('user.php', 2, _US_SORRYNOTFOUND);
		}

		$table = new icms_db_legacy_updater_Table('users');
		$uname = @htmlspecialchars($uname, ENT_QUOTES, _CHARSET);

		if ($table->fieldExists('loginname')) {
			$sql = icms::$xoopsDB->query(sprintf("SELECT salt FROM %s WHERE loginname = %s",
				icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
			list($salt) = icms::$xoopsDB->fetchRow($sql);
		} elseif ($table->fieldExists('login_name')) {
			$sql = icms::$xoopsDB->query(sprintf("SELECT salt FROM %s WHERE login_name = %s",
				icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
			list($salt) = icms::$xoopsDB->fetchRow($sql);
		} else {
			$sql = icms::$xoopsDB->query(sprintf("SELECT salt FROM %s WHERE uname = %s",
				icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
			list($salt) = icms::$xoopsDB->fetchRow($sql);
		}

		return $salt;
	}

    /**
    * This Private Function returns the User Encryption Type belonging to username.
    * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
    * @since    1.2.3
    * @param    string  $uname      Username to find Enc_type for.
    * @return   string  returns the Encryption type of the user.
    */
    private function priv_getUserEncType($uname) {
		if (!isset($uname) || (isset($uname) && $uname == '')) {
			redirect_header('user.php', 2, _US_SORRYNOTFOUND);
		}

		$table = new icms_db_legacy_updater_Table('users');
		$uname = @htmlspecialchars($uname, ENT_QUOTES, _CHARSET);

        if($table->fieldExists('loginname')) {
			$sql = icms::$xoopsDB->query(sprintf("SELECT enc_type FROM %s WHERE loginname = %s",
				icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
            list($enc_type) = icms::$xoopsDB->fetchRow($sql);
        } elseif($table->fieldExists('login_name')) {
			$sql = icms::$xoopsDB->query(sprintf("SELECT enc_type FROM %s WHERE login_name = %s",
				icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
            list($enc_type) = icms::$xoopsDB->fetchRow($sql);
        } else {
            $sql = icms::$xoopsDB->query(sprintf("SELECT enc_type FROM %s WHERE uname = %s",
				icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
            list($enc_type) = icms::$xoopsDB->fetchRow($sql);
        }

		return (int) $enc_type;
    }

	/**
	 * This Private Function is used to Encrypt User Passwords
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $pass       plaintext password to be encrypted
	 * @param    string  $salt       unique user salt key used in encryption process
	 * @param    int     $enc_type   encryption type to use (this is required & only used when passwords are expired)
	 * @param    int     $reset      set to 1 if we have determined that the user password has been expired
	 *                               use in conjunction only with $enc_type above.
	 * @return   Hash of users password.
	 */
	private function priv_encryptPass($pass, $salt, $enc_type, $reset) {
		global $icmsConfigUser;

		if ($reset !== 1) {
			$enc_type = (int) ($icmsConfigUser['enc_type']);
		}
		if ($enc_type == 0) {
			return md5($pass);
		} else {
			$pass = $salt . md5($pass) . $this->mainSalt;
			switch ($enc_type) {
				default:
					case '1':
						return hash('sha256', $pass);
				break;
				case '2':
					return hash('sha384', $pass);
				break;
				case '3':
					return hash('sha512', $pass);
				break;
				case '4':
					return hash('ripemd128', $pass);
				break;
				case '5':
					return hash('ripemd160', $pass);
				break;
				case '6':
					return hash('whirlpool', $pass);
				break;
				case '7':
					return hash('haval128,4', $pass);
				break;
				case '8':
					return hash('haval160,4', $pass);
				break;
				case '9':
					return hash('haval192,4', $pass);
				break;
				case '10':
					return hash('haval224,4', $pass);
				break;
				case '11':
					return hash('haval256,4', $pass);
				break;
				case '12':
					return hash('haval128,5', $pass);
				break;
				case '13':
					return hash('haval160,5', $pass);
				break;
				case '14':
					return hash('haval192,5', $pass);
				break;
				case '15':
					return hash('haval224,5', $pass);
				break;
				case '16':
					return hash('haval256,5', $pass);
				break;
			}
		}
	}

	// ***** Public Functions *****
	/**
	* This Function creates a unique random Salt Key for use with password encryptions
	* It can also be used to generate a random AlphaNumeric key sequence of any given length.
	* @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	* @since    1.1
	* @param    string  $slength    The length of the key to produce
	* @return   string  returns the generated random key.
	*/
	static public function createSalt($slength=64) {
		$salt = '';
		$base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$microtime = function_exists('microtime') ? microtime() : time();
		srand((double)$microtime * 1000000);
		for ($i=0; $i<=$slength; $i++)
		$salt.= substr($base, rand() % strlen($base), 1);
		return $salt;
	}

	/**
	 * This Public Function checks whether a users password has been expired
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $uname      The username of the account to be checked
	 * @return   bool     returns true if password is expired, false if password is not expired.
	 */
	public function passExpired($uname = '') {
		return self::priv_passExpired($uname);
	}

	/**
	 * This Public Function returns the User Salt key belonging to username.
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $uname      Username to find User Salt key for.
	 * @return   string  returns the Salt key of the user.
	 */
	public function getUserSalt($uname = '') {
		return self::priv_getUserSalt($uname);
	}

    /**
    * This Public Function returns the User Encryption Type belonging to username.
    * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
    * @since    1.1
    * @param    string  $uname      Username to find Encryption Type for.
    * @return   string  returns the Encryption Type of the user.
    */
    public function getUserEncType($uname = '')
    {
        return self::priv_getUserEncType($uname);
    }

	/**
	 * This Public Function is used to Encrypt User Passwords
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $pass       plaintext password to be encrypted
	 * @param    string  $salt       unique user salt key used in encryption process
	 * @param    int     $enc_type   encryption type to use (this is required & only used when passwords are expired)
	 * @param    int     $reset      set to 1 if we have determined that the user password has been expired
	 *                               use in conjunction only with $enc_type above.
	 * @return   Hash of users password.
	 */
	public function encryptPass($pass, $salt, $enc_type = 0, $reset = 0) {
		return self::priv_encryptPass($pass, $salt, $enc_type, $reset);
	}
}

