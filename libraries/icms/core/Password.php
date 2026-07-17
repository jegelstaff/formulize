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
	 * The adaptive algorithm and work factor for all new password hashes.
	 * bcrypt is pinned explicitly (NOT PASSWORD_DEFAULT) because it is a PHP core
	 * commodity guaranteed present on every build - PASSWORD_DEFAULT is documented
	 * to change algorithm across PHP versions (e.g. to Argon2, which requires a
	 * non-guaranteed build option). Pinning also keeps hashPassword() and
	 * passwordNeedsUpgrade() in lockstep so upgrade-on-login never thrashes.
	 * Bump BCRYPT_COST to raise strength; existing accounts re-hash on next login.
	 */
	const BCRYPT_ALGO = PASSWORD_BCRYPT;
	const BCRYPT_COST = 12;

	/**
	 * A fixed, valid cost-12 bcrypt hash that matches no real password. Used only
	 * by wasteTimeVerifying() to spend comparable CPU time on the "no such user"
	 * login branch, so response timing does not reveal whether a username exists
	 * (see icms_member_Handler::loginUser() ). Its cost must track BCRYPT_COST so
	 * the dummy verify takes the same time as a real one.
	 */
	const DUMMY_HASH = '$2y$12$28P6Nb76Qoc7I5dnANYyt.YijpFCmeaZPKR9/tL23ilgrviF7HI3.';

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
        // August 29 2016
        // to properly handle apostrophes in username, turned off htmlspecialchars conversion, and stripped slashes from the passed in username. Quotestring method below handles the necessary magic to escaping and security.
		//$uname = @htmlspecialchars($uname, ENT_QUOTES, _CHARSET);
        $uname = stripslashes($uname);

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
		$max = strlen($base) - 1;
		// random_int() is cryptographically secure (PHP 7+); replaces the previous srand()/rand()
		for ($i = 0; $i < $slength; $i++) {
			$salt .= substr($base, random_int(0, $max), 1);
		}
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

	/**
	 * Hash a plaintext password with the modern adaptive algorithm (bcrypt, pinned
	 * via BCRYPT_ALGO/BCRYPT_COST). Use this for every NEW password write — registration,
	 * password reset, admin-set, change-password, SSO/EAU provisioning. Unlike
	 * encryptPass(), the result is self-describing: it carries its own algorithm id,
	 * cost factor and random salt, so the separate `salt`/`enc_type` columns are not
	 * consulted when verifying it later.
	 *
	 * The plaintext is peppered first (see applyPepper()), so the stored bcrypt hash
	 * is worthless to an attacker who exfiltrates only the database and not the
	 * out-of-DB pepper — preserving the protection the legacy scheme had via $mainSalt.
	 * @copyright (c) 2026 Formulize security hardening (audit item A4)
	 * @param     string  $pass   plaintext password to hash
	 * @return    string  adaptive password hash (bcrypt = 60 chars; fits pass varchar(255))
	 */
	public function hashPassword($pass) {
		return password_hash($this->applyPepper($pass), self::BCRYPT_ALGO, array('cost' => self::BCRYPT_COST));
	}

	/**
	 * Verify a plaintext password against the account's stored hash, transparently
	 * supporting BOTH the modern adaptive hashes written by hashPassword() and the
	 * legacy fast-hash values (md5 / salted sha* / ripemd / haval) written by the
	 * old encryptPass() path.
	 *
	 * Adaptive hashes are self-contained, so $salt is ignored for them; it is only
	 * used to reproduce a legacy hash for comparison. The modern branch peppers the
	 * plaintext exactly as hashPassword() did when writing it. The legacy branch
	 * mirrors the exact computation the old login used (encryptPass() applies the
	 * pepper via $mainSalt internally and forces the global enc_type when $reset !== 1),
	 * so it is behaviour-preserving for not-yet-migrated accounts — and must NOT be
	 * peppered again here or it would double-apply.
	 * @copyright (c) 2026 Formulize security hardening (audit item A4)
	 * @param     string  $pass        plaintext password as entered at login
	 * @param     string  $storedHash  the hash currently stored for the account
	 * @param     string  $salt        the account's stored salt (legacy hashes only)
	 * @return    bool    true if the password matches
	 */
	public function verifyPassword($pass, $storedHash, $salt = '') {
		if (self::isAdaptiveHash($storedHash)) {
			return password_verify($this->applyPepper($pass), $storedHash);
		}
		$legacy = $this->encryptPass($pass, $salt);
		return hash_equals((string) $storedHash, (string) $legacy);
	}

	/**
	 * Whether a stored hash should be transparently re-hashed with the modern
	 * algorithm on the next successful login: true for every legacy fast hash, and
	 * also true for an adaptive hash whose parameters (e.g. cost) no longer match
	 * the current PASSWORD_DEFAULT.
	 * @copyright (c) 2026 Formulize security hardening (audit item A4)
	 * @param     string  $storedHash  the hash currently stored for the account
	 * @return    bool
	 */
	public function passwordNeedsUpgrade($storedHash) {
		if (!self::isAdaptiveHash($storedHash)) {
			return true;
		}
		return password_needs_rehash($storedHash, self::BCRYPT_ALGO, array('cost' => self::BCRYPT_COST));
	}

	/**
	 * Spend roughly the same CPU time as a real adaptive verify, without any
	 * account, and return false. Called on the "no such user" branch of loginUser()
	 * so response timing does not distinguish an unknown username from a wrong
	 * password. DUMMY_HASH is a valid bcrypt hash that matches no password.
	 * @copyright (c) 2026 Formulize security hardening (audit item A4)
	 * @param     string  $pass  the submitted plaintext (never matches)
	 * @return    bool           always false
	 */
	public function wasteTimeVerifying($pass) {
		password_verify((string) $pass, self::DUMMY_HASH);
		return false;
	}

	/**
	 * Is $hash one of PHP's adaptive password_hash() outputs (bcrypt/Argon2)?
	 * password_get_info() reports a known algo for those, and an empty algo
	 * (0 on PHP 7, null on PHP 8) for the legacy hex fast-hashes.
	 * @param  string  $hash
	 * @return bool
	 */
	private static function isAdaptiveHash($hash) {
		if (!is_string($hash) || $hash === '') {
			return false;
		}
		$info = password_get_info($hash);
		return !empty($info['algo']);
	}

	/**
	 * Apply the site pepper to a plaintext password before it is handed to bcrypt.
	 * The pepper is XOOPS_DB_SALT (this->mainSalt) — a secret stored in the trust
	 * folder OUTSIDE the database — so a database-only leak yields bcrypt hashes
	 * that cannot be brute-forced at all without also stealing the pepper. This is
	 * the same protection the legacy encryptPass() scheme provided by mixing
	 * $mainSalt into its input, carried forward into the bcrypt path.
	 *
	 * HMAC-SHA256 (not plain concatenation) is used because it is the correct
	 * construction for keying a value with a secret, and its fixed 64-char hex
	 * output is a side benefit: it is always < bcrypt's 72-byte input limit and
	 * contains no null byte, so neither bcrypt truncation footgun can ever bite,
	 * regardless of the real password's length or bytes.
	 *
	 * Like the legacy $mainSalt before it, XOOPS_DB_SALT must never change once a
	 * site is live (doing so invalidates every stored password) — the install
	 * already documents exactly this constraint.
	 * @copyright (c) 2026 Formulize security hardening (audit item A4)
	 * @param     string  $pass  plaintext password
	 * @return    string  64-char hex HMAC of the password keyed with the site pepper
	 */
	private function applyPepper($pass) {
		return hash_hmac('sha256', (string) $pass, (string) $this->mainSalt);
	}
}

