<?php
/**
 * Class to encrypt User Passwords.
 *
 * @category	ICMS
 * @package	Core
 * @since		1.2
 * @author		vaughan montgomery (vaughan@impresscms.org)
 * @author		ImpressCMS Project
 * @copyright	(c) 2007-2010 The ImpressCMS Project - www.impresscms.org
 * @version	SVN: $Id: Password.php 12111 2012-11-09 02:11:04Z skenow $
 */
/**
 * Password generation and validation
 *
 * @category	ICMS
 * @package	Core
 * @subpackage	Password
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
	 */
	static public function getInstance() {
		static $instance;

		if (!isset($instance)) {
			$instance = new icms_core_Password();
		}

		return $instance;
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
		mt_srand((double)$microtime * 1000000);
		for ($i=0; $i<=$slength; $i++)
		$salt.= substr($base, mt_rand(0, $slength) % strlen($base), 1);

		return $salt;
	}

	/**
	 * This Function creates a unique Crypto Generated Key for use with password encryptions
	 * This functions falls back to standard function createSalt() if PHP < 5.3
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.3.3
	 * @param    string  $slength    The length of the key to produce
	 * @return   string  returns the generated random key.
	 */
	public function createCryptoKey($slength = 64) {
		if (function_exists('openssl_random_pseudo_bytes')) {
			$key = openssl_random_pseudo_bytes($slength, $strong);
			if ($strong === TRUE) {
				return $key;
			} else {
				return self::createCryptoKey($slength);
			}
		} else {
			return self::createSalt($slength);
		}
	}

	/**
	 * This Public Function checks whether a users password has been expired
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $uname      The username of the account to be checked
	 * @return   bool     returns true if password is expired, false if password is not expired.
	 */
	public function passExpired($uname = '') {
		if (!isset($uname) || (isset($uname) && $uname == '')) {
			redirect_header('user.php', 2, _US_SORRYNOTFOUND);
		}

		return self::_passExpired($uname);
	}

	/**
	 * This Public Function returns the User Salt key belonging to username.
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $uname      Username to find User Salt key for.
	 * @return   string  returns the Salt key of the user.
	 *
	 * To be removed in future versions
	 */
	public function getUserSalt($uname = '') {
		if (!isset($uname) || (isset($uname) && $uname == '')) {
			redirect_header('user.php', 2, _US_SORRYNOTFOUND);
		}

		return self::_getUserSalt($uname);
	}

	/**
	 * This Public Function returns the User Encryption Type belonging to username.
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $uname      Username to find Encryption Type for.
	 * @return   string  returns the Encryption Type of the user.
	 *
	 * to be removed in future versions
	 */
	public function getUserEncType($uname = '') {
		if (!isset($uname) || (isset($uname) && $uname == '')) {
			redirect_header('user.php', 2, _US_SORRYNOTFOUND);
		}

		return self::_getUserEncType($uname);
	}

	/**
	 * This Public Function is used to Encrypt User Passwords
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $pass       plaintext password to be encrypted
	 * @return   Hash of users password.
	 */
	public function encryptPass($pass) {
		global $icmsConfigUser;

		$salt = self::createSalt();
		$iterations = 5000;
		$enc_type = (isset($icmsConfigUser['enc_type']) ? (int) $icmsConfigUser['enc_type'] : 23);

		return self::_encryptPassword($pass, $salt, $enc_type, $iterations);
	}

	/**
	 * This Public Function verifies if the users password is correct.
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.3.3
	 * @param    string  $uname      Username to verify.
	 * @param    string  $pass       Password to verify.
	 * @return   mixed      returns Hash if correct, returns false if incorrect.
	 */
	public function verifyPass($pass = '', $uname = '') {
		if (!isset($pass) || !isset($uname)) {
			return false;
		}

		return self::_verifyPassword($pass, $uname);
	}

	// ***** Private Functions *****

	/**
	 * This Private Function checks whether a users password has been expired
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $uname      The username of the account to be checked
	 * @return   bool     returns true if password is expired, false if password is not expired.
	 */
	private function _passExpired($uname) {
		$uname = @htmlspecialchars($uname, ENT_QUOTES, _CHARSET);
		$table = new icms_db_legacy_updater_Table('users');

		if ($table->fieldExists('loginname')) {
			$sql = icms::$xoopsDB->query(sprintf("SELECT pass_expired FROM %s WHERE loginname = %s",
			icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
			list($pass_expired) = icms::$xoopsDB->fetchRow($sql);
		} elseif ($table->fieldExists('login_name')) {
			$sql = icms::$xoopsDB->query(sprintf("SELECT pass_expired FROM %s WHERE login_name = %s",
			icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
			list($pass_expired) = icms::$xoopsDB->fetchRow($sql);
		} else {
			$sql = icms::$xoopsDB->query(sprintf("SELECT pass_expired FROM %s WHERE uname = %s",
			icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
			list($pass_expired) = icms::$xoopsDB->fetchRow($sql);
		}

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
	 *
	 * To be removed in future versions
	 */
	private function _getUserSalt($uname) {
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
	 *
	 * To be removed in future versions
	 */
	private function _getUserEncType($uname) {
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
	 * This Private Function returns the User Password Hash belonging to username.
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.3.3
	 * @param    string  $uname      Username to find hash for.
	 * @return   string  returns the Password hash of the user.
	 */
	private function _getUserHash($uname) {
		if (!isset($uname) || (isset($uname) && $uname == '')) {
			redirect_header('user.php', 2, _US_SORRYNOTFOUND);
		}

		$table = new icms_db_legacy_updater_Table('users');
		$uname = @htmlspecialchars($uname, ENT_QUOTES, _CHARSET);

		if($table->fieldExists('loginname')) {
			$sql = icms::$xoopsDB->query(sprintf("SELECT pass FROM %s WHERE loginname = %s",
			icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
			list($pass) = icms::$xoopsDB->fetchRow($sql);
		} elseif($table->fieldExists('login_name')) {
			$sql = icms::$xoopsDB->query(sprintf("SELECT pass FROM %s WHERE login_name = %s",
			icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
			list($pass) = icms::$xoopsDB->fetchRow($sql);
		} else {
			$sql = icms::$xoopsDB->query(sprintf("SELECT pass FROM %s WHERE uname = %s",
			icms::$xoopsDB->prefix('users'), icms::$xoopsDB->quoteString($uname)));
			list($pass) = icms::$xoopsDB->fetchRow($sql);
		}

		return $pass;
	}

	/**
	 * This Private Function is used to Encrypt User Passwords
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.1
	 * @param    string  $pass       plaintext password to be encrypted
	 * @param    string  $salt       unique user salt key used in encryption process
	 * @param    int     $enc_type   encryption type to use (this is required & only used when passwords are expired)
	 * @return   Hash of users password.
	 *
	 * To be removed in future versions, use _encryptPassword() instead
	 */
	private function _encryptPass($pass, $salt, $enc_type) {
		if ($enc_type == 0) {
			return md5($pass);
		} else {
			$pass = $salt . md5($pass) . $this->mainSalt;

			$type = array();
			$type['encType'] = array(
				1 => 'sha256',
				2 => 'sha384',
				3 => 'sha512',
				4 => 'ripemd128',
				5 => 'ripemd160',
				6 => 'whirlpool',
				7 => 'haval128,4',
				8 => 'haval160,4',
				9 => 'haval192,4',
				10 => 'haval224,4',
				11 => 'haval256,4',
				12 => 'haval128,5',
				13 => 'haval160,5',
				14 => 'haval192,5',
				15 => 'haval224,5',
				16 => 'haval256,5',
			);

			return hash($type['encType'][$enc_type], $pass);
		}
	}

	/**
	 * This Private Function is used to Encrypt User Passwords
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.3.3
	 * @param    string  $pass          plaintext password to be encrypted
	 * @param    string  $salt          unique user salt key used in encryption process
	 * @param    int     $enc_type      encryption type to use.
	 * @param    int     $iterations    Number of times to rehash(stretch).
	 * @return   Hash of users password.
	 */
	private function _encryptPassword($pass, $salt, $enc_type, $iterations) {
		if ($enc_type == 20) {
			return '$' . $enc_type . '$20$' . md5($pass); // this should never be used. should be removed???
		} else {
			$hash = '$' . $enc_type . '$' . $iterations . '$' . $salt . '-' . self::_rehash(
				self::_rehash($salt, $iterations) .
				self::_rehash($pass, $iterations) .
				self::_rehash($this->mainSalt, $iterations),
				$iterations, $enc_type);

			return $hash;
		}
	}

	/**
	 * This Private Function rehashes (stretches) the Password Hash
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.3.3
	 * @param    string     $hash           hash to be re-hashed (stretched)
	 * @param    int        $iterations     Number of times to re-hash
	 * @param    int        $enc_type       encryption type to use
	 * @return   Hash of users password.
	 */
	private function _rehash($hash, $iterations, $enc_type = 21) {
		$type['encType'] = array(
			21 => 'sha256',
			22 => 'sha384',
			23 => 'sha512',
			24 => 'ripemd128',
			25 => 'ripemd160',
			26 => 'whirlpool',
			27 => 'haval128,4',
			28 => 'haval160,4',
			29 => 'haval192,4',
			30 => 'haval224,4',
			31 => 'haval256,4',
			32 => 'haval128,5',
			33 => 'haval160,5',
			34 => 'haval192,5',
			35 => 'haval224,5',
			36 => 'haval256,5',
			37 => 'ripemd256',
			38 => 'ripemd320',
			39 => 'snefru256',
			40 => 'gost'
		);

		for ($i = 0; $i < $iterations; ++$i) {
			$hashed = hash($type['encType'][$enc_type], $hash . $hash);
		}

		return $hashed;
	}

	/**
	 * This Private Function verifies if the password is correct
	 * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
	 * @since    1.3.3
	 * @param    string     $pass       Password to be verified
	 * @param    string     $uname      Username of password to be verified
	 * @return   mixed      returns password HASH if correct, returns false if incorrect
	 */
	private function _verifyPassword($pass, $uname) {
		$userSalt = self::_getUserSalt($uname); // to be deprecated in future versions
		$userHash = self::_getUserHash($uname);

		if(preg_match_all("/(\\$)(\\d+)(\\$)(\\d+)(\\$)((?:[a-z0-9_]*))(-)((?:[a-z0-9_]*))/is", $userHash, $matches)) {
			$encType = (int) $matches[2][0];
			$iterations = (int) $matches[4][0];
			$userSalt = $matches[6][0];

			if (self::_encryptPassword($pass, $userSalt, $encType, $iterations) == $userHash) {
				return $userHash;
			}
		} else { // to be removed in future versions
			$encType = self::_getUserEncType($uname);

			if (self::_encryptPass($pass, $userSalt, $encType) == $userHash) {
				return $userHash;
			}
		}

		return false;
	}
}
