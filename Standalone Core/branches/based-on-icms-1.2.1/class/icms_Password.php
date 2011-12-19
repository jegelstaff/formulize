<?php
/**
* Class to encrypt User Passwords.
* @package      kernel
* @subpackage   core
* @since        1.2
* @author       vaughan montgomery (vaughan@impresscms.org)
* @author       ImpressCMS Project
* @copyright    (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
**/
final class icms_Password
{
    private $pass;
    private $salt;
    private $mainSalt = XOOPS_DB_SALT;
    private $uname;

    function __construct()
    {
    }

    /**
    * Access the only instance of this class
    * @return       object
    * @static       $purify_instance
    * @staticvar    object
    **/
    public static function getInstance()
    {
        static $instance;
        if(!isset($instance))
        {
            $instance = new icms_Password();
        }
        return $instance;
    }

    // ***** Private Functions *****
    /**
    * This Private Function checks whether a users password has been expired
    * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
    * @since    1.1
    * @param    string  $uname      The username of the account to be checked
    * @return   int     returns 1 if password is expired, 0 if password is not expired.
    */
    private function icms_passwordExpired($uname)
    {
        $db = Database::getInstance();
        if($uname !== '')
        {
            $sql = $db->query("SELECT uname, pass_expired FROM ".$db->prefix('users')." WHERE
                uname = '".@htmlspecialchars($uname, ENT_QUOTES, _CHARSET)."'");
            list($uname, $pass_expired) = $db->fetchRow($sql);
        }
        else
        {
            redirect_header('user.php',2,_US_SORRYNOTFOUND);
        }
        return $pass_expired;
    }

    /**
    * This Private Function returns the User Salt key belonging to username.
    * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
    * @since    1.1
    * @param    string  $uname      Username to find User Salt key for.
    * @return   string  returns the Salt key of the user.
    */
    private function icms_getUserSalt($uname)
    {
        $db = Database::getInstance();
        if($uname !== '')
        {
            include_once ICMS_ROOT_PATH.'/class/database/databaseupdater.php';
            $table = new IcmsDatabasetable('users');
            if($table->fieldExists('loginname'))
            {
                $sql = $db->query("SELECT loginname, salt FROM ".$db->prefix('users')." WHERE
                    loginname = '".@htmlspecialchars($uname, ENT_QUOTES, _CHARSET)."'");
                list($loginname, $salt) = $db->fetchRow($sql);
            }
            elseif($table->fieldExists('login_name'))
            {
                $sql = $db->query("SELECT login_name, salt FROM ".$db->prefix('users')." WHERE
                    login_name = '".@htmlspecialchars($uname, ENT_QUOTES, _CHARSET)."'");
                list($login_name, $salt) = $db->fetchRow($sql);
            }
            else
            {
                $sql = $db->query("SELECT uname, salt FROM ".$db->prefix('users')." WHERE
                    uname = '".@htmlspecialchars($uname, ENT_QUOTES, _CHARSET)."'");
                list($uname, $salt) = $db->fetchRow($sql);
            }
        }
        else
        {
            redirect_header('user.php',2,_US_SORRYNOTFOUND);
        }
        return $salt;
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
    * @return   string  returns the final encrypted hash of users password.
    */
    private function icms_encryptPassword($pass, $salt, $enc_type, $reset)
    {
        global $icmsConfigUser;

        if($reset == 0)
        {
            $enc_type = intval($icmsConfigUser['enc_type']);
        }
        if($enc_type == 0)
        {
            $pass_hash = md5($pass);
        }
        elseif($enc_type == 1)
        {
            $pass_hash = hash('sha256', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 2)
        {
            $pass_hash = hash('sha384', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 3)
        {
            $pass_hash = hash('sha512', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 4)
        {
            $pass_hash = hash('ripemd128', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 5)
        {
            $pass_hash = hash('ripemd160', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 6)
        {
            $pass_hash = hash('whirlpool', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 7)
        {
            $pass_hash = hash('haval128,4', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 8)
        {
            $pass_hash = hash('haval160,4', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 9)
        {
            $pass_hash = hash('haval192,4', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 10)
        {
            $pass_hash = hash('haval224,4', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 11)
        {
            $pass_hash = hash('haval256,4', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 12)
        {
            $pass_hash = hash('haval128,5', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 13)
        {
            $pass_hash = hash('haval160,5', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 14)
        {
            $pass_hash = hash('haval192,5', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 15)
        {
            $pass_hash = hash('haval224,5', $salt.md5($pass).$this->mainSalt);
        }
        elseif($enc_type == 16)
        {
            $pass_hash = hash('haval256,5', $salt.md5($pass).$this->mainSalt);
        }
        unset($mainSalt, $pass);
        return $pass_hash;
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
    public function icms_createSalt($slength=64)
    {
        $salt = '';
        $base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $microtime = function_exists('microtime') ? microtime() : time();
        srand((double)$microtime * 1000000);
        for($i=0; $i<=$slength; $i++)
            $salt.= substr($base, rand() % strlen($base), 1);
        return $salt;
    }

    /**
    * This Public Function checks whether a users password has been expired
    * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
    * @since    1.1
    * @param    string  $uname      The username of the account to be checked
    * @return   int     returns 1 if password is expired, 0 if password is not expired.
    */
    public function icms_passExpired($uname = '')
    {
        return $this->icms_passwordExpired($uname);
    }

    /**
    * This Public Function returns the User Salt key belonging to username.
    * @copyright (c) 2007-2008 The ImpressCMS Project - www.impresscms.org
    * @since    1.1
    * @param    string  $uname      Username to find User Salt key for.
    * @return   string  returns the Salt key of the user.
    */
    final public function icms_getUserSaltFromUname($uname = '')
    {
        return $this->icms_getUserSalt($uname);
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
    * @return   string  returns the final encrypted hash of users password.
    */
    final public function icms_encryptPass($pass, $salt, $enc_type = 0, $reset = 0)
    {
        return $this->icms_encryptPassword($pass, $salt, $enc_type, $reset);
    }
}
?>