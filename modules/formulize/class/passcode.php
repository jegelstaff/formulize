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

class formulizePassCode extends XoopsObject {

	function __construct($passCode,$screen,$expiry='',$notes='') {
        parent::__construct();
		//initVar params: key, data_type, value, req, max, opt
		$this->initVar("passcode", XOBJ_DTYPE_TXTBOX, $passCode, true, 255);
		$this->initVar("screen", XOBJ_DTYPE_INT, $screen, true);
        $this->initVar("notes", XOBJ_DTYPE_TXTBOX, $notes, true, 255);
        $this->initVar("expiry", XOBJ_DTYPE_TXTBOX, $expiry, true, 255); // should be a date type? but we only use this for displaying to screen, so should be okay to fake it as a text value?
    }

}


class formulizePassCodeHandler {
	var $db;
	function __construct(&$db) {
		$this->db =& $db;
	}
	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizePassCodeHandler($db);
		}
		return $instance;
	}
	function &create($passCode='',$screen,$expiry='') {
        if(!$screen OR !is_numeric($screen)) {
            return false;
        }
        if(!$passCode) {
            $passCode = $this->generatePassCode();
        }
		return new formulizePassCode($passCode, $screen, $expiry);
	}

    function generatePasscode() {
        
        if(!function_exists('bcadd')) {
            return '';
        }
        
        require XOOPS_ROOT_PATH.'/modules/formulize/libraries/GenPhrase/Loader.php';
        $loader = new GenPhrase\Loader();
        $loader->register();
        $gen = new GenPhrase\Password();
        // Generate a passphrase using english words and (at least) 50 bits of entropy.
        return $gen->generate();
    }
    
    function validatePasscode($code, $sid) {
        global $xoopsDB;
        $code = formulize_db_escape($code);
        $sid = intval($sid);
        $date = date('Y-m-d');
        self::cleanupExpiredPasscodes();
        $sql = 'SELECT * FROM '.$xoopsDB->prefix('formulize_passcodes').' WHERE passcode = "'.$code.'" AND screen = '.$sid.' AND (expiry > "'.$date.'" OR expiry IS NULL)';
        $res = $xoopsDB->query($sql);
        if($xoopsDB->getRowsNum($res)) {
            $_SESSION['formulize_passCode_'.$sid] = $code;
            return true;
        } else {
            if(isset($_SESSION['formulize_passCode_'.$sid])) { unset($_SESSION['formulize_passCode_'.$sid]); }
            return false;
        }
    }
    
    function cleanupExpiredPasscodes() {
        global $xoopsDB;
        $date = date('Y-m-d');
        $sql = 'DELETE FROM '.$xoopsDB->prefix('formulize_passcodes').' WHERE expiry <= "'.$date.'" AND expiry IS NOT NULL';
        $xoopsDB->queryF($sql);
    }
    
	function getOtherScreenPasscodes($sid) {
        return self::getPasscodes($sid, '!=');
    }
    
    function getThisScreenPasscodes($sid) {
        return self::getPasscodes($sid, '=');
    }
    
    // returns an array of passcodes matching what is passed. In each array/passcode, the fields are keyed as - passcode, notes, expiry, id
    function getPasscode($passcode) {
        return self::getPasscodes(0,"",$passcode);
    }
    
    function getPasscodes($sid=0, $op="",$passcode="") {
        global $xoopsDB;
        $screenWhere = '';
        $passcodeWhere = '';
        if($sid) {
            if(!$op OR ($op != '!=' AND $op != '=')) {
                return array();
            }
            $screenWhere = ' AND screen '.$op.' '.intval($sid);
        }
        if($passcode) {
            $passcodeWhere = ' AND passcode = "'.formulize_db_escape($passcode).'"';
        }
        $date = date('Y-m-d');
        self::cleanupExpiredPasscodes();
        $sql = 'SELECT distinct(passcode) as passcode, notes, expiry, passcode_id as id FROM '.$xoopsDB->prefix('formulize_passcodes').' WHERE (expiry > "'.$date.'" OR expiry IS NULL) '.$screenWhere.$passcodeWhere.' ORDER BY passcode_id ASC';
        $passcodes = array();
        if($res = $xoopsDB->query($sql)) {
            while($array = $xoopsDB->fetchArray($res)) {
                $passcodes[] = $array;
            }
        }
        return $passcodes;
	}
	
    function copyPasscodeToScreen($id, $sid) {
        global $xoopsDB;
        $id = intval($id);
        $sid = intval($sid);
        $sql = 'SELECT passcode, notes, expiry FROM '.$xoopsDB->prefix('formulize_passcodes').' WHERE passcode_id = '.$id;        
        $res = $xoopsDB->query($sql);
        $data = $xoopsDB->fetchArray($res);
        return self::insert($data['passcode'], $data['notes'], $sid, $data['expiry']);
    }
    
	function insert($code, $notes, $sid, $expiry='') {
        global $xoopsDB;
        $sid = intval($sid);
        $code = formulize_db_escape($code);
        $notes = formulize_db_escape($notes);
        $expiry = $expiry ? '"'.date('Y-m-d', strtotime($expiry)).'"' : 'NULL';
        $sql = 'INSERT INTO '.$xoopsDB->prefix('formulize_passcodes').' (passcode, notes, screen, expiry) VALUES ("'.$code.'", "'.$notes.'", '.$sid.', '.$expiry.')';
        if(!$res = $xoopsDB->queryF($sql)) {
            print "Error: could not insert passcode with this SQL: $sql<br>".$xoopsDB->error();
            return false;
        }
        self::createPasscodeElement($sid);
        return true;
	}
    
    function updatePasscode($oldCode, $newCode, $sid) {
        global $xoopsDB;
        $sid = intval($sid);
        $oldCode = formulize_db_escape($oldCode);
        $newCode = formulize_db_escape($newCode);
        $sql = 'UPDATE '.$xoopsDB->prefix('formulize_passcodes').' SET passcode = "'.$newCode.'" WHERE passcode = "'.$oldCode.'" AND screen = '.$sid;
        if(!$res = $xoopsDB->queryF($sql)) {
            print "Error: could not update passcode with this SQL: $sql<br>".$xoopsDB->error();
            return false;
        }
        return true;
    }
    

    // add a system level passcode element to the form visible to registered users, if one does not exist already
    function createPasscodeElement($sid) {
        $sid = intval($sid);
        $screen_handler = xoops_getmodulehandler('screen', 'formulize');
        $element_handler = xoops_getmodulehandler('elements', 'formulize');
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $screenObject = $screen_handler->get($sid);
        $fid = $screenObject->getVar('fid');
        $formObject = $form_handler->get($fid);
        $elementTypes = $formObject->getVar('elementTypes');
        if(in_array('anonPasscode',$elementTypes)) {
            return true;
        }
        // otherwise, add a passcode element to the form
        $element_handler = xoops_getmodulehandler('elements', 'formulize');
        $element = $element_handler->create();
        $element->setVar('id_form', $fid);
        $element->setVar('ele_type', 'anonPasscode');
        $element->setVar('ele_display', ','.XOOPS_GROUP_USERS.',');
        $element->setVar('ele_disabled', 1);
        $element->setVar('ele_req', 0);
        $element->setVar('ele_encrypt', 0);
        $element->setVar('ele_handle', 'anon_passcode_'.$fid);
        $element->setVar('ele_caption', 'Anonymous User Passcode');
        $element->setVar('ele_forcehidden', 1);
        $elementId = $element_handler->insert($element);
        // pass id so element object gets recreated inside other method, and then it will pick up all the properties from the custom class
        if(!$insertResult = $form_handler->insertElementField($elementId, $element->overrideDataType)) {
			exit("Error: could not add the new element to the data table in the database.");
		}
    }
    
    function updateExpiry($id, $expiry) {
        global $xoopsDB;
        $id = intval($id);
        $expiry = $expiry ? '"'.date('Y-m-d', strtotime($expiry)).'"' : 'NULL';
        $sql = 'UPDATE '.$xoopsDB->prefix('formulize_passcodes').' SET expiry = '.$expiry.' WHERE passcode_id = '.$id;
        if(!$res = $xoopsDB->queryF($sql)) {
            print "Error: could not update passcode with this SQL: $sql<br>".$xoopsDB->error();
            return false;
        }
        return true;
    }
    
	function delete($id) {
        global $xoopsDB;
        $id = intval($id);
        $sql = 'DELETE FROM '.$xoopsDB->prefix('formulize_passcodes').' WHERE passcode_id = '.$id;
		if(!$xoopsDB->queryF($sql)) {
			print "Error: could not delete passcode with this SQL: $sql<br>".$xoopsDB->error();
            return false;
		}
		return true;
	}
    
}
