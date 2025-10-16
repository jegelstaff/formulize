<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2010 Freeform Solutions                  ##
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
##  URL: http://www.formulize.org                           ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH.'/kernel/object.php';

global $xoopsDB;

    class formulizeApplicationMenuLink extends XoopsObject {

        function __construct() {
            parent::__construct();
            $this->initVar("menu_id", XOBJ_DTYPE_INT, NULL, false);
            $this->initVar("appid", XOBJ_DTYPE_INT, NULL, false);
            $this->initVar("screen", XOBJ_DTYPE_TXTBOX, NULL, false,11);
            $this->initVar("rank", XOBJ_DTYPE_INT, NULL, false);
            $this->initVar("url", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
            $this->initVar("link_text", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
            $this->initVar("name", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
            $this->initVar("text", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
            $this->initVar("permissions", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
            $this->initVar("default_screen", XOBJ_DTYPE_TXTBOX, NULL, false, 255); //added oct 2013
	    $this->initVar("note",XOBJ_DTYPE_TXTBOX,null,false,255);
        }

        // override the parent's getVar, since we want to allow for abbreviated URLs in the UI, to avoid users having to type the current site URL into the system
        function getVar($var, $raw=false) {
            $value = parent::getVar($var, $raw);
            if($var == 'url') {
                if(!strstr($value,'://') AND (strstr($value, 'sid=') OR strstr($value, 'fid=')) AND !$raw) {
                    $value = XOOPS_URL."/modules/formulize/index.php?".htmlspecialchars(strip_tags($value));
                }
            }
            return $value;
        }

    }

	#[AllowDynamicProperties]
  class formulizeApplicationMenuLinksHandler {

        var $db;
        function __construct(&$db) {
            $this->db =& $db;
        }

        function get($id, $all = false) {
            global $xoopsDB;
            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $screen_handler = xoops_getmodulehandler('screen', 'formulize');
            $linksArray = array();

            global $xoopsUser;
            $groupSQL = "";

            $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0 => XOOPS_GROUP_ANONYMOUS);
            if(!$all){
                foreach($groups as $group) {
                    if(strlen($groupSQL) == 0){
                        $groupSQL .= " AND ( perm.group_id=". $group . " ";
                    }else{
                        $groupSQL .= " OR perm.group_id=". $group . " ";
                    }
                }
                $groupSQL .= ")";
            }

            $sql = 'SELECT links.*, group_concat(group_id separator \',\') as permissions FROM '.$xoopsDB->prefix("formulize_menu_links").' as links ';
			$sql .= ' LEFT JOIN '.$xoopsDB->prefix("formulize_menu_permissions").' as perm ON links.menu_id = perm.menu_id ';
			$sql .= ' WHERE appid = ' . $id. ' '. $groupSQL .' GROUP BY menu_id,appid,screen,`rank`,url,link_text ORDER BY `rank`';

            if ($result = $this->db->query($sql)) {

                while($resultArray = $this->db->fetchArray($result)) {
                    $link = new formulizeApplicationMenuLink();
                    $link->assignVars($resultArray);
                    $linksArray[$link->getVar('menu_id')] = $link;
                }
            }

            foreach($linksArray as $menulink) {

                // added Oct 2013
                // groups that use the link as a default screen
                $menuid = $menulink->getVar('menu_id');

                $sql = 'SELECT group_concat(group_id separator \',\') as default_screen FROM '.$xoopsDB->prefix("formulize_menu_permissions");
				$sql .= ' WHERE menu_id = ' . $menuid. ' AND default_screen = 1' ;

				if ($result = $this->db->query($sql)) {
               		$resultArray = $this->db->fetchArray($result);
                	$menulink->assignVar('default_screen',$resultArray['default_screen']);
            	}

                $menutext =	$menulink->getVar('link_text');
                $screenidname= "";
                if($menutext == ""){
                    $id = explode("=",$menulink->getVar('screen'));

                    if($menulink->getVar('screen')=="") {   //handle external url
            			$menutext = $menulink->getVar('url');

            		} elseif(strpos($menulink->getVar('screen'),"fid=") !== false ){
                        $menutext = $form_handler->get($id[1])->getVar('title');
                        $screenidname = " - form ID: ".  $form_handler->get($id[1])->getVar('id_form');
                    }else{
                        $menutext = $screen_handler->get($id[1])->getVar('title');
                        $screenidname = " - screen ID: ".$screen_handler->get($id[1])->getVar('sid');
                    }
                }

                $menulink->assignVar('text',$menutext);
                $menulink->assignVar('name',$menutext.$screenidname);
            }

            return $linksArray;
        }

		/**
		 * Returns an array of the fid,sid,url representing the default menu link for the current user
		 * @return array An array containing the form id and screen id and url of any default menu link found
		 */
		static function getDefaultScreenForUser() {

			global $xoopsUser, $xoopsDB;
			static $cachedResults = array();
			$fid = null;
			$sid = null;
			$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
			$cacheKey = serialize($groups);
			if(!isset($cachedResults[$cacheKey])) {
				$groupSQL = "";
				foreach($groups as $group) {
					if(strlen($groupSQL) == 0){
							$groupSQL .= " AND ( perm.group_id=". $group . " ";
					}else{
							$groupSQL .= " OR perm.group_id=". $group . " ";
					}
				}
				$groupSQL .= ")";
				$sql = 'SELECT links.screen, links.url FROM '.$xoopsDB->prefix("formulize_menu_links").' AS links ';
				$sql .= ' LEFT JOIN '.$xoopsDB->prefix("formulize_menu_permissions").' AS perm ON links.menu_id = perm.menu_id ';
				$sql .= ' WHERE  default_screen = 1'. $groupSQL . 'ORDER BY links.rank LIMIT 0,1';
				$res = $xoopsDB->query ( $sql ) or die('SQL Error !<br />'.$sql.'<br />'.$xoopsDB->error());
				$url = '';
				$fid = 0;
				$sid = 0;
				if($row = $xoopsDB->fetchArray ( $res )) {
					if($row['url']) {
						if(substr($row['url'],0,1)=='/') {
								$url = XOOPS_URL . $row['url'];
						} elseif(!strstr($row['url'],'://')) {
								$url = 'http://' . $row['url'];
						} else {
								$url = $row['url'];
						}
					}
					$screenID = $row['screen'];
					if ( strpos($screenID,"fid=") !== false){
						$fid = substr($screenID, strpos($screenID,"=")+1 );
					} else {
						$sid = substr($screenID, strpos($screenID,"=")+1 );
					}
				}
				$cachedResults[$cacheKey] = array($fid,$sid,$url);
			}
			return $cachedResults[$cacheKey];
		}
  }

class formulizeApplication extends XoopsObject {

  private $_forms = null;

  function __construct() {
    parent::__construct();
    $this->initVar("appid", XOBJ_DTYPE_INT, NULL, false);
    $this->initVar("name", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
    $this->initVar("description", XOBJ_DTYPE_TXTAREA);
    $this->initVar("forms", XOBJ_DTYPE_ARRAY, serialize(array()), false);
    $this->initVar("links", XOBJ_DTYPE_ARRAY);
    $this->initVar("all_links", XOBJ_DTYPE_ARRAY);
    $this->initVar("custom_code", XOBJ_DTYPE_TXTBOX, NULL, false); //added jan 2015
  }

    function forms() {
        if (null == $this->_forms) {
            $form_handler = xoops_getmodulehandler('forms', 'formulize');
            $this->_forms = $form_handler->getFormsByApplication($this->appid);
        }
        return $this->_forms;
    }

		function getVar($key, $format = 's') {
			if($key == 'custom_code' AND $appid = $this->getVar('appid')) {
				$filename=XOOPS_ROOT_PATH."/modules/formulize/code/application_custom_code_".$appid.".php";
				$contents = '';
				if(file_exists($filename)) {
					$contents = file_get_contents($filename);
				}
				return $contents;
			}
			return parent::getVar($key, $format);
		}
}

#[AllowDynamicProperties]
class formulizeApplicationsHandler {
  var $db;
	function __construct(&$db) {
		$this->db =& $db;
	}

  function &create() {
		return new formulizeApplication();
	}

  function get($ids, $fid=0) { // takes a single ID or an array of ids, or the keyword 'all', Plus, if $fid is greater than 0, limit applications retrieved to ones where that form is involved
    static $cachedApps = array();
    global $xoopsDB;
    $idsArray = array();
    if(is_array($ids)) {
      $idsArray = $ids;
    } else {
      $idsArray[0] = $ids;
    }
    $foundApps = array();
    if($ids !== 'all') {
      // retrieve all the ids
      $queryIds = array();
      foreach($idsArray as $key=>$thisId) {
        // validate the id
        if ($thisId == 0 OR !is_numeric($thisId)) {
          $cachedApps[$thisId] = false;
          $foundApps[$key] = false;
        } else {
          if(isset($cachedApps[$thisId])) { // retrive the id from the cache if possible
            if($fid > 0 AND in_array($fid, $cachedApps[$thisId]->getVar('forms'))) {
              $foundApps[$key] = $cachedApps[$thisId];
              continue;
            }
          }
          // didn't find it in the cache, so mark it for retrieval
          $queryIds[] = $thisId;
        }
      }
      if($fid > 0) {
        $sql = 'SELECT * FROM '.$xoopsDB->prefix("formulize_applications").' as t1, '.$xoopsDB->prefix("formulize_application_form_link").' as t2 WHERE t1.appid IN ('.implode(",",$queryIds).') AND t1.appid = t2.appid AND t2.fid = '.$fid.' ORDER BY t1.name';
      } else {
        $sql = 'SELECT * FROM '.$xoopsDB->prefix("formulize_applications").' WHERE appid IN ('.implode(",",$queryIds).') ORDER BY name';
      }
    } else {
      if(isset($cachedApps['all'])) {
        if($fid > 0) {
          $matchingCachedApps = array();
          foreach($cachedApps['all'] as $thisCachedApp) {
            if(in_array($fid, $thisCachedApp->getVar('forms'))) {
              $matchingCachedApps[] = $thisCachedApp;
            }
          }
          return $matchingCachedApps;
        } else {
          return $cachedApps['all'];
        }
      }
      if($fid > 0) {
        $sql = 'SELECT * FROM '.$xoopsDB->prefix("formulize_applications").' as t1, '.$xoopsDB->prefix("formulize_application_form_link").' as t2 WHERE t1.appid = t2.appid AND t2.fid = '.$fid.' AND t2.appid > 0 ORDER BY t1.name';
      } else {
        $sql = 'SELECT * FROM '.$xoopsDB->prefix("formulize_applications").' WHERE appid > 0 ORDER BY name';
      }
    }

    $links_handler = xoops_getmodulehandler('ApplicationMenuLinks', 'formulize'); // JAKEADDED

    // query the DB for the ids we're supposed to
    if ($result = $this->db->query($sql)) {
      while($resultArray = $this->db->fetchArray($result)) {
        $newApp = new formulizeApplication();
        $newApp->assignVars($resultArray);
        $newAppId = $newApp->getVar('appid');
        $menulinks = $links_handler->get($newAppId); // JAKEADDED
        $newApp->assignVar('links', serialize($menulinks)); // JAKE ADDED
        $menulinks = $links_handler->get($newAppId,true); // JAKEADDED
        $newApp->assignVar('all_links', serialize($menulinks));
        // add in the forms
        $sql = 'SELECT link.fid, forms.form_title FROM '.$xoopsDB->prefix("formulize_application_form_link").' as link, '.$xoopsDB->prefix("formulize_id").' as forms WHERE link.appid = '.$newAppId.' AND forms.id_form = link.fid ORDER BY forms.form_title';
        $foundForms = array();
				$sortArray = array();
        if($formRes = $this->db->query($sql)) {
          while($formArray = $this->db->fetchArray($formRes)) {
            $foundForms[] = $formArray['fid'];
						$sortArray[] = trans($formArray['form_title']);
          }
					array_multisort($sortArray, SORT_NATURAL, $foundForms);
        }
        $newApp->assignVar('forms', serialize($foundForms)); // need to serialize arrays when assigning to array properties in the xoops object class
        $cachedApps[$newAppId] = $newApp;
        if($ids === 'all') {
          $foundApps[] = $newApp;
        } else {
          $foundApps[array_search($newAppId,$idsArray)] = $newApp;
        }
      }
    }
    // fill in any holes in the $foundApps array if for some reason any items were missed
    if($ids === 'all') {
      $cachedApps['all'] = $foundApps;
      return $foundApps;
    } else {
      foreach($idsArray as $key=>$thisId) {
        if(!isset($foundApps[array_search($thisId,$idsArray)])) { // if we don't already have a value for this id, then mark it as false, but don't cache it because we don't know why the query failed...another query for the same id might succeed
          $foundApps[array_search($thisId,$idsArray)] = false;
        }
      }
    }
    if(is_array($ids)) {
      return $foundApps;
    } else {
      return $foundApps[0];
    }
	}

  function getAllApplications() {
    return $this->get('all');
  }

  function getApplicationsByForm($fid) {
    return $this->get('all',$fid);
  }

  function insert(&$appObject, $force=false) {
    if( get_class($appObject) != 'formulizeApplication'){
        return false;
    }
    if( !$appObject->isDirty() ){
        return true;
    }
    if( !$appObject->cleanVars() ){
        return false;
    }
    foreach( $appObject->cleanVars as $k=>$v ){
      ${$k} = $v;
    }
    if($appObject->isNew() || empty($appid)) {
        $sql = "INSERT INTO ".$this->db->prefix("formulize_applications") . " (`name`, `description`) VALUES (".$this->db->quoteString($name).", ".$this->db->quoteString($description).")";
    } else {
        $sql = "UPDATE ".$this->db->prefix("formulize_applications") . " SET `name` = ".$this->db->quoteString($name).", `description` = ".$this->db->quoteString($description)." WHERE appid = ".intval($appid);
    }

    if( false != $force ){
        $result = $this->db->queryF($sql);
    }else{
        $result = $this->db->query($sql);
    }

    if( !$result ){
      print "Error: this application could not be saved in the database.  SQL: $sql<br>".$xoopsDB->error();
      return false;
    }
    if( empty($appid) ){
      $appid = $this->db->getInsertId();
    }
    $appObject->assignVar('appid', intval($appid));

    // now assign the forms

    $foundForms = array();
		$checkSQL = "SELECT fid FROM ".$this->db->prefix("formulize_application_form_link"). " WHERE appid=".intval($appid);
		$checkRes = $this->db->query($checkSQL);
		while($checkArray = $this->db->fetchArray($checkRes)) {
			$foundForms[] = $checkArray['fid'];
		}
    // figure out what we need to insert and what we need to remove, ie: the differences
    $formsForRemoval = array_diff($foundForms, $appObject->getVar('forms'));// in foundForms, but not in the app
    $formsForInsert = array_diff($appObject->getVar('forms'), $foundForms); // in the app, but were not found

		$runRemoval = false;
		$runInsert = false;
    if(count((array) $formsForInsert)>0) {
    	$insertStart = true;
      $insertSQL = "INSERT INTO ".$this->db->prefix("formulize_application_form_link")." (`fid`, `appid`) VALUES ";
      foreach($formsForInsert as $thisFid) {
        if(!$insertStart) { $insertSQL .= ", "; }
  			$insertSQL .= "(".intval($thisFid).", ".intval($appid).")";
  			$insertStart = false;
  			$runInsert = true;
      }
    }
    if(count((array) $formsForRemoval)>0) {
      $removalSQL = "DELETE FROM ".$this->db->prefix("formulize_application_form_link")." WHERE ";
      $removalStart = true;
      foreach($formsForRemoval as $thisFid) {
        if(!$removalStart) { $removalSQL .= " OR "; }
        $removalSQL .= " (`appid`=".intval($appid)." AND `fid`=".intval($thisFid).") ";
        $removalStart = false;
        $runRemoval = true;
      }
    }

		if($runInsert) {
      if($force) {
        $result = $this->db->queryF($insertSQL);
      } else {
        $result = $this->db->query($insertSQL);
      }
		}
		if($runRemoval) {
      if($force) {
        $result2 = $this->db->queryF($removalSQL);
      } else {
			  $result2 = $this->db->query($removalSQL);
			}
		}
    if(($insertSQL AND !$result) OR ($removalSQL AND !$result2)) {
      print "Error: this application could not be saved in the database.  SQL: $removalSQL<br>$insertSQL<br>";
			return false;
		} else {
      return $appid;
    }
	}

  function delete($appid) {
    if(is_object($appid)) {
			if(!get_class("formulizeApplication")) {
				return false;
			}
			$appid = $appid->getVar('appid');
		} elseif(!is_numeric($appid)) {
			return false;
		}
    global $xoopsDB;
    $isError = false;
    $sql[] = "DELETE FROM ".$xoopsDB->prefix("formulize_applications")." WHERE appid=$appid";
    $sql[] = "DELETE FROM ".$xoopsDB->prefix("formulize_application_form_link")." WHERE appid=$appid";
    foreach($sql as $thisSql) {
      if(!$xoopsDB->query($thisSql)) {
        print "Error: could not complete the deletion of application ".$appid;
        $isError = true;
      }
    }
    return $isError ? false : true;
  }
    function getMenuLinksForApp($appid,$all=false){
        global $xoopsDB;
        $links_handler = xoops_getmodulehandler('ApplicationMenuLinks', 'formulize');
        if(!is_array($appid)) {
            $appid = array($appid);
        }
        $foundLinks = array();
        foreach($appid as $aid) {
            if($links = $links_handler->get($aid,$all)) {
                $foundLinks = array_merge($foundLinks, $links);
            }
        }
        return $foundLinks;
    }


    //modified Oct 2013 W.R.
    function insertMenuLink($appid,$menuitem, $force=false){

        global $xoopsDB;

        $rank = 1;
        $rankquery = "SELECT MAX(`rank`) FROM `".$xoopsDB->prefix("formulize_menu_links")."` WHERE appid=".intval($appid).";";
        if($result = $xoopsDB->query($rankquery)) {
	    //if empty query, then rank = 1, else, rank is the next larger number
            $max = $xoopsDB->fetchArray($result);
            $rank= $max['MAX(`rank`)']+1;
        }

        //0=menuid, 1=menuText, 2=screen, 3=url, 4=groupids, 5=default_screen  6=note
        $linkValues = explode("::",$menuitem);
//	error_log("link values ".print_r($linkValues));
        $insertsql = "INSERT INTO `".$xoopsDB->prefix("formulize_menu_links")."` VALUES (null,". intval($appid).",'". formulize_db_escape($linkValues[2])."',".$rank.",'".formulize_db_escape($linkValues[3])."','".formulize_db_escape($linkValues[1])."','". formulize_db_escape($linkValues[6])."');";
				if($force) {
					$result = $xoopsDB->queryF($insertsql);
				} else {
					$result = $xoopsDB->query($insertsql);
				}
		if(!$result) {
			exit("Error inserting Menu Item. SQL dump:\n" . $insertsql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
		}else{

			$menuid = $xoopsDB->getInsertId();
			if($linkValues[4] != "null" and count((array) $linkValues[4]) > 0){
                $groupsThatCanView = explode(",",$linkValues[4]);
				$groupsThatCanView = array_map(array($xoopsDB, 'escape'), $groupsThatCanView);
                $groupsWithDefaultPage = explode(",",$linkValues[5]);
				$groupsWithDefaultPage = array_map(array($xoopsDB, 'escape'), $groupsWithDefaultPage);
				$defaultScreen = 0;
				foreach($groupsThatCanView as $groupid) {
                    //check for default screen
					if (in_array($groupid, $groupsWithDefaultPage)){
						$defaultScreen = 1;
					}
					$permissionsql = "INSERT INTO `".$xoopsDB->prefix("formulize_menu_permissions")."` VALUES (null,".$menuid.",". $groupid.", ".$defaultScreen.")";
					if($force) {
						$result = $xoopsDB->queryF($permissionsql);
					} else {
						$result = $xoopsDB->query($permissionsql);
					}
					if(!$result) {
						exit("Error inserting Menu Item permissions.".$linkValues[4]." SQL dump:\n" . $permissionsql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
					}
                    $defaultScreen = 0;
				}
			}
		}
	}
    //end of insertMenuLink()


    // modified Oct 2013 W.R.
    function deleteMenuLink($appid,$menuitem ){
        global $xoopsDB;
        $deletemenuitems = "DELETE FROM `".$xoopsDB->prefix("formulize_menu_links")."` WHERE appid=".intval($appid)." AND menu_id=" .$menuitem .";";
        $deletemenupermissions = "DELETE FROM `".$xoopsDB->prefix("formulize_menu_permissions")."` WHERE menu_id=" .$menuitem .";";
        if(!$result = $xoopsDB->query($deletemenuitems)) {
            echo("Delete menu link $menuitem failed.");
        }else{
            $xoopsDB->query($deletemenupermissions);
        }
    }
    //$screen should be something like "sid=1" or "fid=1" be careful when you use it
    //Added by Jinfu FEB 2015
    function deleteMenuLinkByScreen($screen){
	global $xoopsDB;
	$sql="Select menu_id,appid from ".$xoopsDB->prefix("formulize_menu_links")." WHERE screen= '" .$screen."';";
	//error_log($sql);
	$res=$xoopsDB->query($sql);
	while($array=$xoopsDB->fetchArray($res)){
	    $this->deleteMenuLink($array['appid'],$array['menu_id']);
	    //error_log("ajslkjalkdjlas: ".$array['appid']." ".$array['menu_id']."\n");
	}
    }

     // modified Oct 2013 W.R.
    function updateMenuLink($appid,$menuitems){
        global $xoopsDB;
        //0=menuid, 1=menuText, 2=screen, 3=url, 4=groupids, 5=default_screen 6=note
        $linkValues = explode("::",$menuitems);
	//error_log("link values ".print_r($linkValues));
        $updatesql = "UPDATE `".$xoopsDB->prefix("formulize_menu_links").
	"` SET screen= '".formulize_db_escape($linkValues[2])."', url= '".formulize_db_escape($linkValues[3])."', link_text='".formulize_db_escape($linkValues[1])."',note='".formulize_db_escape($linkValues[6])."' where menu_id=".intval($linkValues[0])." AND appid=".intval($appid).";";
        if(!$result = $xoopsDB->query($updatesql)) {
            exit("Error updating Menu Item. SQL dump:\n" . $updatesql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
        }else{
        	//delete existing permissions for this menu item
        	$deletepermissions = "DELETE FROM `".$xoopsDB->prefix("formulize_menu_permissions")."` WHERE menu_id=".intval($linkValues[0]).";";
       	 	$result = $xoopsDB->query($deletepermissions);

       	 	if($linkValues[4] != "null" and count((array) $linkValues[4]) > 0){
                $groupsThatCanView = explode(",",$linkValues[4]);
				$groupsThatCanView = array_map(array($xoopsDB, 'escape'), $groupsThatCanView);
                $groupsWithDefaultPage = explode(",",$linkValues[5]);
				$groupsWithDefaultPage = array_map(array($xoopsDB, 'escape'), $groupsWithDefaultPage);
                $defaultScreen = 0;
        		foreach($groupsThatCanView as $groupid) {
                    //check for default screen
                    if (in_array($groupid, $groupsWithDefaultPage)){
                        $defaultScreen = 1;
                    }
                    $permissionsql = "INSERT INTO `".$xoopsDB->prefix("formulize_menu_permissions")."` VALUES (null,".intval($linkValues[0]).",". $groupid.",".$defaultScreen.")";
           	     if(!$result = $xoopsDB->query($permissionsql)) {
           	     	exit("Error updating Menu Item permissions.".$linkValues[4]." SQL dump:\n" . $permissionsql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
           	 		}
                    $defaultScreen = 0;
           		  }
        	 }
		}
	}

	/**
	 * Move all menu links associated with a form from one application to another
	 * @param int fromAppId The application id to move the links from
	 * @param int toAppId The application id to move the links to
	 * @param int fid The form id of the form whose links should be moved
	 * @return bool True on success
	 * @throws Exception if the SQL query fails
	 */
	function moveMenuLinksBetweenApplications($fromAppId, $toAppId, $fid) {
		global $xoopsDB;
		$fromAppId = intval($fromAppId);
		$toAppId = intval($toAppId);
		if($fromAppId > 0 AND $toAppId > 0 AND $fid > 0) {
			$screen_handler = xoops_getmodulehandler('screen', 'formulize');
			$screensOfForm = $screen_handler->getScreensForForm($fid);
			$screenIds = array_keys($screensOfForm);
			$screenIdsSQL = "";
			foreach($screenIds as $thisScreenId) {
				if($screenIdsSQL != "") { $screenIdsSQL .= " OR "; }
				$screenIdsSQL .= " screen = 'sid=".intval($thisScreenId)."' ";
			}
			$sql = "UPDATE `".$xoopsDB->prefix("formulize_menu_links")."` SET appid = ".$toAppId." WHERE appid = ".$fromAppId." AND (screen = 'fid=".intval($fid)."' $screenIdsSQL);";
			if(!$result = $xoopsDB->query($sql)) {
				throw new Exception("Error moving menu links between applications. SQL dump:\n" . $sql . "\n".$xoopsDB->error());
			}
		}
		return true;
	}

	// added Oct 2013 W.R.
    function updateSorting($Links){
        global $xoopsDB;
        foreach($Links as $link) {
  			$menu_id = $link->getVar('menu_id');
  			$rank = $link->getVar('rank');
        	$updatesql = "UPDATE `".$xoopsDB->prefix("formulize_menu_links")."` SET `rank`= ".$rank." where menu_id=".$menu_id.";";
        	if(!$result = $xoopsDB->query($updatesql)) {
            	exit("Error sorting Menu List. SQL dump:\n" . $updatesql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
        	}
        }
	}

    //added Oct 2013
    function getGroupsWithDefaultScreen(){
        global $xoopsDB;

        $sql = 'SELECT group_concat( DISTINCT(group_id) separator \',\') as default_screen FROM '.$xoopsDB->prefix("formulize_menu_permissions");
        $sql .= ' WHERE default_screen = 1' ;

        if ($result = $xoopsDB->query($sql)) {
            $resultArray = $xoopsDB->fetchArray($result);
            return $resultArray['default_screen'];
        }
        else {
            exit("Error checking default screen. SQL dump:\n" . $checksql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
        }
    }

	/**
	 * Get the admin UI metadata necessary to display the form listings for the application
	 * @param int aid The application id of the application we're working with. Zero for 'forms with no application'.
	 * @return array formsInApp An array of the metadata necessary for the form listings
	 */
	function getFormMetadataForAdminUI($aid) {
		global $xoopsUser;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$gperm_handler = xoops_gethandler('groupperm');
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
		$aid = intval($aid);
		$formsInApp = array();
		$adminLayoutTopAndLeftForForms = $this->getAdminLayoutTopAndLeftForForms($aid);
		$formObjects = $form_handler->getFormsByApplication($aid);
		if(is_array($formObjects)) {
			foreach($formObjects as $thisFormObject) {
				if (!$gperm_handler->checkRight("edit_form", $thisFormObject->getVar('id_form'), $xoopsUser->getGroups(), getFormulizeModId())) {
						continue;
				}
				$formsInApp[$thisFormObject->getVar('id_form')]['name'] = $thisFormObject->getVar('title');
				$formsInApp[$thisFormObject->getVar('id_form')]['fid'] = $thisFormObject->getVar('id_form'); // forms tab uses fid
				$hasDelete = $gperm_handler->checkRight("delete_form", $thisFormObject->getVar('id_form'), $xoopsUser->getGroups(), getFormulizeModId());
				$formsInApp[$thisFormObject->getVar('id_form')]['hasdelete'] = $hasDelete;
				// get the default screens for each form too
				$defaultFormScreen = $thisFormObject->getVar('defaultform');
				$defaultListScreen = $thisFormObject->getVar('defaultlist');
				$defaultFormObject = $screen_handler->get($defaultFormScreen);
				if (is_object($defaultFormObject)) {
						$defaultFormName = $defaultFormObject->getVar('title');
				}
				$defaultListObject = $screen_handler->get($defaultListScreen);
				if (is_object($defaultListObject)) {
						$defaultListName = $defaultListObject->getVar('title');
				}
				$formLinks = $framework_handler->formatFrameworksAsRelationships(array($framework_handler->get(-1)), $thisFormObject->getVar('id_form'));
				$formsInApp[$thisFormObject->getVar('id_form')]['form'] = $thisFormObject;
				$formsInApp[$thisFormObject->getVar('id_form')]['defaultformscreenid'] = $defaultFormScreen;
				$formsInApp[$thisFormObject->getVar('id_form')]['defaultlistscreenid'] = $defaultListScreen;
				$formsInApp[$thisFormObject->getVar('id_form')]['defaultformscreenname'] = $defaultFormName;
				$formsInApp[$thisFormObject->getVar('id_form')]['defaultlistscreenname'] = $defaultListName;
				$formsInApp[$thisFormObject->getVar('id_form')]['lockedform'] = $thisFormObject->getVar('lockedform');
				$formsInApp[$thisFormObject->getVar('id_form')]['istableform'] = $thisFormObject->getVar('tableform');
				$formsInApp[$thisFormObject->getVar('id_form')]['top'] = (isset($adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['top']) AND $adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['top']) ? $adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['top']: '0px';
				$formsInApp[$thisFormObject->getVar('id_form')]['left'] = (isset($adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['left']) AND $adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['left']) ? $adminLayoutTopAndLeftForForms[$thisFormObject->getVar('id_form')]['left']: '0px';
				$formsInApp[$thisFormObject->getVar('id_form')]['links'] = $formLinks[0]['content']['links'];
			}
		}
		return $formsInApp;
	}

  /**
   * Fetch the top and left values for the admin layout of the forms in the given application
   * @param int aid The application id of the application we're working with. Zero for 'forms with no application'.
   * @return array Returns a multidimensional array, first level key is the form id, second level has two keys, top and left, for the top and left css values (ie: 345.677px). Or an empty array if the application id is not valid.
   */
  function getAdminLayoutTopAndLeftForForms($aid) {
    global $xoopsDB;
    $positions = array();
    $aid = intval($aid);
    if($aid) {
      $sql = 'SELECT `fid`, `top`, `left` FROM '.$xoopsDB->prefix('formulize_application_form_link').' WHERE appid = '.$aid;
      if($res = $xoopsDB->query($sql)) {
        while($row = $xoopsDB->fetchRow($res)) {
          $positions[$row[0]]['top'] = $row[1];
          $positions[$row[0]]['left'] = $row[2];
        }
      }
    }
    return $positions;
  }

  /**
   * Set the top and left values for forms based on what was passed back through the admin UI, which will include the aid
   * @return boolean True or false depending on the result of the query
   */
  function setAdminLayoutTopAndLeftForForms() {
		global $xoopsDB;
		$adminLayoutTopAndLeftForForms = array();
		$positions = array(); // will be a multidimensional array of the positions for the forms. Top level key is the appid, second is form id, third level keys are top and left, for the top and left css values (ie: 345.677px)
		foreach($_POST['formTop'] as $appidDotFid=>$topValue) {
			$leftValue = $_POST['formLeft'][$appidDotFid];
			list($aid, $fid) = explode('.',$appidDotFid);
			$aid = intval($aid);
			$fid = intval($fid);
			$adminLayoutTopAndLeftForForms[$aid] = $this->getAdminLayoutTopAndLeftForForms($aid);
			if($topValue != '0px' AND $leftValue != '0px' AND ($adminLayoutTopAndLeftForForms[$aid][$fid]['top'] != $topValue OR $adminLayoutTopAndLeftForForms[$aid][$fid]['left'] != $leftValue)) {
				$positions[$aid][$fid]['top'] = $topValue;
				$positions[$aid][$fid]['left'] = $leftValue;
			}
		}
		foreach($positions as $aid=>$formPositions) {
			foreach($formPositions as $fid=>$topAndLeft) {
				$sql = 'UPDATE '.$xoopsDB->prefix('formulize_application_form_link')." SET `top` = '".$topAndLeft['top']."', `left` = '".$topAndLeft['left']."' WHERE appid = $aid AND fid = $fid";
				if(!$res = $xoopsDB->query($sql)) {
					return false;
				}
			}
		}
		return true;
	}

}

function buildMenuLinkURL($menulink) {
    $url = $menulink->getVar("url");
    if(strlen($url) > 0){
        if(substr($url, 0, 1)=="/") {
            $url = XOOPS_URL.$url;
        } else {
            $pos = strpos($url,"://");
            if($pos === false){
                $url = "http://".$url;
            }
        }
    }
    return $url;
}



