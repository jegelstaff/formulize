<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

include_once XOOPS_ROOT_PATH.'/kernel/object.php';

class formulizeAPIKey extends XoopsObject {

	function __construct($uid=0, $key='',$expiry='') {
        parent::__construct();
		//initVar params: key, data_type, value, req, max, opt
		$this->initVar("uid", XOBJ_DTYPE_INT, $uid, true);
		$this->initVar("key", XOBJ_DTYPE_TXTBOX, $key, true, 255);
        $this->initVar("expiry", XOBJ_DTYPE_TXTBOX, $expiry, true, 255); // should be a date type? but we only use this for displaying to screen, so should be okay to fake it as a text value?
    }

}


class formulizeAPIKeyHandler {
	var $db;
	function __construct(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeAPIKeyHandler($db);
		}
		return $instance;
	}
	function &create($uid=0,$key='',$expiry='') {
		return new formulizeAPIKey($uid, $key, $expiry);
	}

	function get($key="all") {
		$key = preg_replace("/[^A-Za-z0-9]/", "", str_replace(" ","",$key)); // keys must be only alphanumeric characters
		static $cachedKeys = array();
		if(isset($cachedKeys[$key])) { return $cachedKeys[$key]; }
        global $xoopsDB;
		if($key!="all") {
            $sql = "SELECT uid, apikey, expiry FROM ".$xoopsDB->prefix("formulize_apikeys")." WHERE apikey = '".formulize_db_escape($key)."' AND (expiry IS NULL OR expiry > NOW())";
        } else {
            $sql = "SELECT uid, apikey, expiry FROM ".$xoopsDB->prefix("formulize_apikeys")." WHERE expiry IS NULL OR expiry > NOW()";
        }
        $res = $xoopsDB->query($sql);
        if(!$res) {
            print "Error: could not retrieve key(s) with this SQL: $sql<br>".$xoopsDB->error();
            return false;
        }
        while($row = $xoopsDB->fetchRow($res)) {
            if(!isset($cachedKeys[$row[1]])) {
                $expiry = $row[2] ? 'Expires: '.$row[2] : "";
                $cachedKeys[$row[1]] = $this->create($row[0], $row[1], $row[2]);
            }
        }
        if($key AND isset($cachedKeys[$key])) {
            return $cachedKeys[$key];
        } elseif($key=="all") {
            return $cachedKeys;
        } else {
            return false;
        }
        if(count($cachedKeys)>0) {
        return $cachedKeys;
        }
        return false;
	}
	
	function insert($uid, $expiry=0) {
        $candidateID = $this->_generateKey();
        $expiry = $expiry ? "'".date("Y-m-d H:i:s",time()+($expiry*3600))."'" : "NULL";
        global $xoopsDB;
        $sql = "INSERT INTO ".$xoopsDB->prefix("formulize_apikeys")." (uid,apikey,expiry) VALUES (".intval($uid).",'".$candidateID."',".$expiry.")";
        if(!$res = $xoopsDB->queryF($sql)) {
            print "Error: could not insert apikey with this SQL: $sql<br>".$xoopsDB->error();
            return false;
        }
    	return $xoopsDB->getInsertId();
	}

	function delete($key="all") {
        global $xoopsDB;
        $key = preg_replace("/[^A-Za-z0-9]/", "", str_replace(" ","",$key)); // keys must be only alphanumeric characters
        if($key!="all") {		
            $sql = "DELETE FROM ".$xoopsDB->prefix("formulize_apikeys")." WHERE apikey = '".formulize_db_escape($key)."' OR (expiry IS NOT NULL AND expiry < NOW())";
        } else {
            $sql = "DELETE FROM ".$xoopsDB->prefix("formulize_apikeys")." WHERE expiry IS NOT NULL AND expiry < NOW()";
        }
		if(!$xoopsDB->queryF($sql)) {
			print "Error: could not delete apikey(s) with this SQL: $sql<br>".$xoopsDB->error();
            return false;
		}
		return true;
	}

    function _generateKey() {
        $candidateID = bin2hex(openssl_random_pseudo_bytes(16));
        while($this->get($candidateID) == true) { // check that we haven't used this ID already...practically never going to happen, but still...
            $candidateID = bin2hex(openssl_random_pseudo_bytes(16));
        }
        return $candidateID;
    }
}
