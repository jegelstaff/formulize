<?php
/**
* Manage of online users
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: online.php 9520 2009-11-11 14:32:52Z pesianstranger $
*/
/**
 * @package     kernel
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */

/**
 * A handler for "Who is Online?" information
 * 
 * @package     kernel
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsOnlineHandler
{

    /**
     * Database connection
     * 
     * @var	object
     * @access	private
     */
    var $db;

    /**
     * Constructor
     * 
     * @param	object  &$db    {@link XoopsHandlerFactory} 
     */
    function XoopsOnlineHandler(&$db)
    {
        $this->db =& $db;
    }

    /**
     * Inserts online information into the database
     * 
     * @param	int     $uid    UID of the active user
     * @param	string  $uname  Username
     * @param	string  $timestamp
     * @param	string  $module Current module
     * @param	string  $ip     User's IP adress
     * 
     * @return	bool    TRUE on success
     */
    function write($uid, $uname, $time, $module, $ip)
  	{
  		$uid = intval($uid);
  		if ($uid > 0) {
  			$sql = "SELECT COUNT(*) FROM ".$this->db->prefix('online')." WHERE online_uid='".$uid."'";
  		} else {
  			$sql = "SELECT COUNT(*) FROM ".$this->db->prefix('online')." WHERE online_uid='".$uid."' AND online_ip='".$ip."'";
  		}
  		list($count) = $this->db->fetchRow($this->db->queryF($sql));
          if ( $count > 0 ) {
              $sql = "UPDATE ".$this->db->prefix('online')." SET online_updated='".$time."', online_module = '".$module."' WHERE online_uid = '".$uid."'";
              if ($uid == 0) {
                  $sql .= " AND online_ip='".$ip."'";
              }
          } else {
      			$sql = sprintf("INSERT INTO %s (online_uid, online_uname, online_updated, online_ip, online_module) VALUES ('%u', %s, '%u', %s, '%u')", $this->db->prefix('online'), $uid, $this->db->quoteString($uname), intval($time), $this->db->quoteString($ip), intval($module));
          }
      		if (!$this->db->queryF($sql)) {
              return false;
          }
  		return true;
    }





    /**
     * Delete online information for a user
     * 
     * @param	int $uid    UID
     * 
     * @return	bool    TRUE on success
     */
    function destroy($uid)
    {
    		$sql = sprintf("DELETE FROM %s WHERE online_uid = '%u'", $this->db->prefix('online'), intval($uid));
        if (!$result = $this->db->queryF($sql)) {
            return false;
        }
        return true;
    }




    /**
     * Garbage Collection
     * 
     * Delete all online information that has not been updated for a certain time
     * 
     * @param	int $expire Expiration time in seconds
     */
    function gc($expire)
    {
		$sql = sprintf("DELETE FROM %s WHERE online_updated < '%u'", $this->db->prefix('online'), time() - intval($expire));
        $this->db->queryF($sql);
    }


    /**
     * Get an array of online information
     * 
     * @param	object  $criteria   {@link CriteriaElement} 
     * @return	array   Array of associative arrays of online information
     */
    function getAll($criteria = null)
    {
        $ret = array();
        $limit = $start = 0;
        $sql = 'SELECT * FROM '.$this->db->prefix('online');
        if (is_object($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere();
            $limit = $criteria->getLimit();
            $start = $criteria->getStart();
        }
        $result = $this->db->query($sql, $limit, $start);
        if (!$result) {
            return false;
        }
        while ($myrow = $this->db->fetchArray($result)) {
            $ret[] = $myrow;
            unset($myrow);
        }
        return $ret;
    }

    /**
     * Count the number of online users
     * 
     * @param	object  $criteria   {@link CriteriaElement} 
     */
    function getCount($criteria = null)
    {
        $sql = 'SELECT COUNT(*) FROM '.$this->db->prefix('online');
        if (is_object($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' '.$criteria->renderWhere();
        }
        if (!$result = $this->db->query($sql)) {
            return false;
        }
        list($ret) = $this->db->fetchRow($result);
        return $ret;
    }
}

?>