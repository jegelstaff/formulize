<?php
/**
* Session Management
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: session.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/
/*
  Based on SecureSession class
  Written by Vagharshak Tozalakyan <vagh@armdex.com>
  Released under GNU Public License
*/
/**
* Handler for a session
* @package     kernel
* @author	    Kazumi Ono	<onokazu@xoops.org>
* @copyright	copyright (c) 2000-2003 XOOPS.org
*/
class XoopsSessionHandler
{
	/**
	* Database connection
	* @var	object
	* @access	private
	*/
	var $db;

	/**
	* Security checking level
	* Possible value: 
	*	0 - no check;
	*	1 - check browser characteristics (HTTP_USER_AGENT/HTTP_ACCEPT_LANGUAGE), to be implemented in the future now;
	*	2 - check browser and IP A.B;
	*	3 - check browser and IP A.B.C, recommended;
	*	4 - check browser and IP A.B.C.D;
	* @var	int
	* @access	public
	*/
	var $securityLevel = 3;
    
	/**
	* Enable regenerate_id
	* @var	bool
	* @access	public
	*/
	var $enableRegenerateId = false;
    
	/**
	* Constructor
	* @param object $db reference to the {@link XoopsDatabase} object
	* 
	*/
	function XoopsSessionHandler(&$db)
     {
          $this->db =& $db;
     }

	/**
	* Open a session
	* @param	string  $save_path
	* @param	string  $session_name
	* @return	bool
	*/
	function open($save_path, $session_name)
     {
          return true;
     }

	/**
	* Close a session
	* @return	bool
	*/
	function close()
	{
		$this->gc_force();
		return true;
	}

	/**
	* Read a session from the database
	* @param	string  &sess_id    ID of the session
	* @return	array   Session data
	*/
	function read($sess_id)
	{
		$sql = sprintf('SELECT sess_data, sess_ip FROM %s WHERE sess_id = %s', $this->db->prefix('session'), $this->db->quoteString($sess_id));
		if(false != $result = $this->db->query($sql))
		{
			if(list($sess_data, $sess_ip) = $this->db->fetchRow($result))
			{
				if($this->securityLevel > 1)
				{
					$pos = strpos($sess_ip, ".", $this->securityLevel - 1);
					if(strncmp($sess_ip, $_SERVER['REMOTE_ADDR'], $pos)) {$sess_data = '';}
				}
				return $sess_data;
			}
		}
		return '';
	}

	/**
	* Inserts a session into the database
	* @param   string  $sess_id
	* @param   string  $sess_data
	* @return  bool    
	**/
	function write($sess_id, $sess_data)
	{
		$sess_id = $this->db->quoteString($sess_id);
		$sql = sprintf("UPDATE %s SET sess_updated = '%u', sess_data = %s WHERE sess_id = %s", $this->db->prefix('session'), time(), $this->db->quoteString($sess_data), $sess_id);
		$this->db->queryF($sql);
		if(!$this->db->getAffectedRows())
		{
			$sql = sprintf("INSERT INTO %s (sess_id, sess_updated, sess_ip, sess_data) VALUES (%s, '%u', %s, %s)", $this->db->prefix('session'), $sess_id, time(), $this->db->quoteString($_SERVER['REMOTE_ADDR']), $this->db->quoteString($sess_data));
			return $this->db->queryF($sql);
		}
		return true;
	}

	/**
	* Destroy a session
	* @param   string  $sess_id
	* @return  bool
	**/
	function destroy($sess_id)
	{
		$sql = sprintf('DELETE FROM %s WHERE sess_id = %s', $this->db->prefix('session'), $this->db->quoteString($sess_id));
		if(!$result = $this->db->queryF($sql))
          {
               return false;
          }
		return true;
	}

	/**
	* Garbage Collector
	* @param   int $expire Time in seconds until a session expires
	* @return  bool
	**/
	function gc($expire)
	{
		if(empty($expire))
          {
               return true;
          }
		$mintime = time() - intval($expire);
		$sql = sprintf("DELETE FROM %s WHERE sess_updated < '%u'", $this->db->prefix('session'), $mintime);
		return $this->db->queryF($sql);
	}

	/**
	* Force gc for situations where gc is registered but not executed
	**/
	function gc_force()
	{
		if(rand(1, 100) < 11)
		{
			$expiration = empty($GLOBALS['xoopsConfig']['session_expire']) ? @ini_get('session.gc_maxlifetime') : $GLOBALS['xoopsConfig']['session_expire'] * 60;
			$this->gc($expiration);
		}
	}

	/**
	* Update the current session id with a newly generated one
	* To be refactored 
	* @param   bool $delete_old_session
	* @return  bool
	**/
	function icms_sessionRegenerateId($regenerate = false)
	{
		$old_session_id = session_id();
		if($regenerate)
		{
			$success = session_regenerate_id(true);
//			$this->destroy($old_session_id);
		}
		else
          {
               $success = session_regenerate_id();
          }
		// Force updating cookie for session cookie is not issued correctly in some IE versions or not automatically issued prior to PHP 4.3.3 for all browsers 
		if($success)
          {
               $this->update_cookie();
          }
		return $success;
	}

	/**
	* Update cookie status for current session
	* To be refactored 
	* FIXME: how about $xoopsConfig['use_ssl'] is enabled?
	* @param   string  $sess_id    session ID
	* @param   int     $expire     Time in seconds until a session expires
	* @return  bool
	**/
	function update_cookie($sess_id = null, $expire = null)
	{
		global $icmsConfig;
          $secure = substr(ICMS_URL, 0, 5) == 'https' ? 1 : 0; // we need to secure cookie when using SSL
		$session_name = ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') ? $icmsConfig['session_name'] : session_name();
		$session_expire = !is_null($expire) ? intval($expire) : ( ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '') ? $icmsConfig['session_expire'] * 60 : ini_get('session.cookie_lifetime') );
		$session_id = empty($sess_id) ? session_id() : $sess_id;
		setcookie($session_name, $session_id, $session_expire ? time() + $session_expire : 0, '/',  '', $secure, 0);
	}

	// Call this when init session.
	function icms_sessionOpen($regenerate = false)
	{
		$_SESSION['icms_fprint'] = $this->icms_sessionFingerprint();
		if($regenerate)
          {
               $this->icms_sessionRegenerateId(true);
          }
	}
	
	// Call this to check session.
	function icms_sessionCheck()
	{
//		$this->icms_sessionRegenerateId();
		return (isset($_SESSION['icms_fprint']) && $_SESSION['icms_fprint'] == $this->icms_sessionFingerprint());
	}

	// Internal function. Returns sha256 from fingerprint.
	function icms_sessionFingerprint()
	{
		$securityLevel = $this->securityLevel;
		$fingerprint = XOOPS_DB_SALT;
		if($securityLevel >= 1)
          {
               $fingerprint .= $_SERVER['HTTP_USER_AGENT'];
          }
		if($securityLevel >= 2)
		{
			$num_blocks = abs(intval($securityLevel));
			if($num_blocks > 4)
               {
                    $num_blocks = 4;
               }
			$blocks = explode('.', $_SERVER['REMOTE_ADDR']);
			for($i = 0; $i < $num_blocks; $i++)
               {
                    $fingerprint .= $blocks[$i].'.';
               }
		}
		return hash('sha256',$fingerprint);
	}
}
?>