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

        /**
         * Get this link's URL, expanding an abbreviated internal reference into a full URL.
         * Users are allowed to type just "sid=12" or "fid=3" instead of the whole site URL, so anywhere
         * we need a URL that can actually be followed (an href, a redirect) we expand it here.
         * The value is deliberately unescaped, since it is a URL and not HTML text - escape at the
         * point of output if it is going into markup.
         * @return string The URL to link to, or the stored value as-is if there is nothing to expand
         */
        public function getExpandedUrl() {
            $url = $this->getVar('url', 'n');
            if(!strstr($url, '://') AND (strstr($url, 'sid=') OR strstr($url, 'fid='))) {
                $url = XOOPS_URL."/modules/formulize/index.php?".strip_tags($url);
            }
            return $url;
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

                // gather the text unescaped, since it is being assigned back onto the link object, and will be
                // escaped when it is read out of the object again by whoever is displaying it
                $menutext =	$menulink->getVar('link_text', 'n');
                $screenidname= "";
                if($menutext == ""){
                    $id = explode("=",$menulink->getVar('screen'));

                    if($menulink->getVar('screen')=="") {   //handle external url
            			$menutext = $menulink->getExpandedUrl();

            		} elseif(strpos($menulink->getVar('screen'),"fid=") !== false ){
                        $menutext = $form_handler->get($id[1])->getVar('title', 'n');
                        $screenidname = " - form ID: ".  $form_handler->get($id[1])->getVar('id_form');
                    }else{
                        $menutext = $screen_handler->get($id[1])->getVar('title', 'n');
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
		$sql = "";
    $foundApps = array();
    if($ids !== 'all') {
      // retrieve all the ids
      $queryIds = array();
      foreach($idsArray as $key=>$thisId) {
        // validate the id
        if ($thisId == 0 OR !is_numeric($thisId)) {
					continue;
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
      } elseif(!empty($queryIds)) {
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
    if ($sql AND $result = $this->db->query($sql)) {
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
    if(is_object($appid) AND get_class($appid) == "formulizeApplication") {
			$appid = $appid->getVar('appid');
		} elseif(!is_numeric($appid)) {
			return false;
		}

		// delete menu links from the db too
		$menuLinks = $this->getMenuLinksForApp($appid, 'all');
		foreach($menuLinks as $thisMenuLink) {
			$menuid = $thisMenuLink->getVar('menu_id');
			$this->deleteMenuLinkById($menuid);
		}

    global $xoopsDB;
    $sql[] = "DELETE FROM ".$xoopsDB->prefix("formulize_applications")." WHERE appid=$appid";
    $sql[] = "DELETE FROM ".$xoopsDB->prefix("formulize_application_form_link")." WHERE appid=$appid";
    foreach($sql as $thisSql) {
      if(!$xoopsDB->query($thisSql)) {
        throw new Exception("Could not delete application. SQL dump:\n" . $thisSql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
      }
    }
    return true;
  }
    function getMenuLinksForApp($appid,$all=false){
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
			throw new Exception("Could not insert Menu Item. SQL dump:\n" . $insertsql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
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
						throw new Exception("Could not insert Menu Item permissions.".$linkValues[4]." SQL dump:\n" . $permissionsql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
					}
          $defaultScreen = 0;
					if($groupid == XOOPS_GROUP_ANONYMOUS) {
						// set visibility on the menu block to include anonymous users since there is a menu item visible to them
						$gperm_handler = xoops_gethandler('groupperm');
						$formMenuBlockId = 24; // enforced by the override script in the installer
						if(count($this->getAnonRightsToFormMenuBlock()) == 0) {
							if($gperm_handler->addRight('block_read', $formMenuBlockId, XOOPS_GROUP_ANONYMOUS, 1) == false) {
								throw new Exception("Could not add anonymous group permission for menu block. ".$xoopsDB->error()."\nPlease contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.");
							}
						}
					}
				}
			}
		}
	}
    //end of insertMenuLink()

    //$screen should be something like "sid=1" or "fid=1" be careful when you use it
    //Added by Jinfu FEB 2015
    function deleteMenuLinkByScreen($screen){
	global $xoopsDB;
	$sql="Select menu_id,appid from ".$xoopsDB->prefix("formulize_menu_links")." WHERE screen= '" .$screen."';";
	//error_log($sql);
	$res=$xoopsDB->query($sql);
	while($array=$xoopsDB->fetchArray($res)){
	    $this->deleteMenuLinkById($array['menu_id']);
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
	 * Relocate a form's menu links to follow the form as it moves between applications.
	 * Only acts when the form was removed from at least one application. The form's links in the
	 * removed-from applications are deduplicated by reference (so multiple identical links collapse
	 * to one), then moved into the first added-to application and copied into any others. If the form
	 * was not added to any new application, the orphaned links are deleted.
	 * @param array $removedFromApps Application ids the form was removed from
	 * @param array $addedToApps Application ids the form was added to
	 * @param int $fid The form id whose links should follow it
	 * @return bool True on success
	 * @throws Exception if any of the underlying SQL queries fail
	 */
	function relocateMenuLinksForForm($removedFromApps, $addedToApps, $fid) {
		global $xoopsDB;
		$fid = intval($fid);
		if($fid <= 0 OR empty($removedFromApps)) {
			return true;
		}
		// appid 0 ("Forms with no app") is a valid container, so keep it - only drop negatives/non-numeric
		$addedToApps = array_values(array_filter(array_map('intval', (array) $addedToApps), function($appId) { return $appId >= 0; }));

		// 1. Identify all the form's menu links across the applications the form is leaving
		$menuIds = array();
		foreach((array) $removedFromApps as $removedApp) {
			$menuIds = array_merge($menuIds, $this->getMenuLinkIdsForForm($fid, $removedApp));
		}
		if(empty($menuIds)) {
			return true;
		}

		// 2. Deduplicate by reference: keep one survivor per distinct screen/url, mark the rest for deletion
		$survivors = array(); // referenceKey => menu_id
		$duplicates = array(); // menu_ids that duplicate a survivor's reference
		$sql = "SELECT menu_id, screen, url FROM ".$xoopsDB->prefix("formulize_menu_links")." WHERE menu_id IN (".implode(",", $menuIds).")";
		if($result = $xoopsDB->query($sql)) {
			while($row = $xoopsDB->fetchArray($result)) {
				$referenceKey = $row['screen']."|".$row['url'];
				if(isset($survivors[$referenceKey])) {
					$duplicates[] = intval($row['menu_id']);
				} else {
					$survivors[$referenceKey] = intval($row['menu_id']);
				}
			}
		}
		$survivorIds = array_values($survivors);

		// remove the duplicate links (and their permissions) so each reference survives only once
		foreach($duplicates as $duplicateMenuId) {
			$this->deleteMenuLinkById($duplicateMenuId);
		}

		// 3. If the form was not added to any new application, the survivors are now orphaned, so delete them
		if(empty($addedToApps)) {
			foreach($survivorIds as $survivorMenuId) {
				$this->deleteMenuLinkById($survivorMenuId);
			}
			return true;
		}

		// 4. Move the survivors into the first added application, then copy them into any others
		$firstApp = array_shift($addedToApps);
		$this->moveMenuLink($survivorIds, $firstApp);
		foreach($addedToApps as $additionalApp) {
			foreach($survivorIds as $survivorMenuId) {
				$this->copyMenuLink($survivorMenuId, $additionalApp);
			}
		}
		return true;
	}

	/**
	 * Get the menu_ids of the links in a given application that point to a given form.
	 * A link belongs to the form if either its screen or url column references the form directly
	 * (fid=X) or references one of the form's screens (sid=Y, resolved back to the form). This is the
	 * single place that interprets how a link's target is stored.
	 * @param int $fid The form id to match
	 * @param int $appid The application id to search within
	 * @return array An array of menu_ids belonging to the form in that application
	 */
	function getMenuLinkIdsForForm($fid, $appid) {
		global $xoopsDB;
		$fid = intval($fid);
		$appid = intval($appid);
		$menuIds = array();
		if($fid <= 0 OR $appid < 0) { // appid 0 is the "Forms with no app" container, so it is valid
			return $menuIds;
		}
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$sql = "SELECT menu_id, screen, url FROM ".$xoopsDB->prefix("formulize_menu_links")." WHERE appid = ".$appid;
		if($result = $xoopsDB->query($sql)) {
			while($row = $xoopsDB->fetchArray($result)) {
				if($this->menuLinkReferencesForm($row['screen'], $row['url'], $fid, $screen_handler)) {
					$menuIds[] = intval($row['menu_id']);
				}
			}
		}
		return $menuIds;
	}

	/**
	 * Determine whether a menu link's screen/url references a given form.
	 * @param string $screen The link's screen column value (e.g. 'fid=5' or 'sid=12')
	 * @param string $url The link's url column value (may also contain an abbreviated fid=/sid= reference)
	 * @param int $fid The form id to match
	 * @param object $screen_handler The screen handler, for resolving sid= references back to a form
	 * @return bool True if the link references the form
	 */
	function menuLinkReferencesForm($screen, $url, $fid, $screen_handler) {
		$fid = intval($fid);
		foreach(array($screen, $url) as $reference) {
			if(!$reference) {
				continue;
			}
			// direct form reference: fid=X
			if(preg_match('/fid=(\d+)/', $reference, $matches) AND intval($matches[1]) === $fid) {
				return true;
			}
			// screen reference: sid=Y, resolve the screen back to its form
			if(preg_match('/sid=(\d+)/', $reference, $matches)) {
				$screenObject = $screen_handler->get(intval($matches[1]));
				if(is_object($screenObject) AND intval($screenObject->getVar('fid')) === $fid) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Move menu links to a different application by changing their appid. The menu_id is preserved,
	 * so the associated permission rows (keyed on menu_id) travel with the links automatically.
	 * @param array|int $menuIds A menu_id or array of menu_ids to move
	 * @param int $toAppId The application id to move the links into
	 * @return bool True on success
	 * @throws Exception if the SQL query fails
	 */
	function moveMenuLink($menuIds, $toAppId) {
		global $xoopsDB;
		$toAppId = intval($toAppId);
		$menuIds = array_values(array_filter(array_map('intval', (array) $menuIds)));
		if(empty($menuIds) OR $toAppId < 0) { // appid 0 ("Forms with no app") is a valid target
			return true;
		}
		$sql = "UPDATE ".$xoopsDB->prefix("formulize_menu_links")." SET appid = ".$toAppId." WHERE menu_id IN (".implode(",", $menuIds).")";
		if(!$result = $xoopsDB->query($sql)) {
			throw new Exception("Error moving menu links. SQL dump:\n" . $sql . "\n".$xoopsDB->error());
		}
		return true;
	}

	/**
	 * Copy a menu link into a different application, duplicating the link row with a new appid and
	 * cloning its permission rows (which are keyed on the new menu_id). The default_screen flag lives
	 * inside those permission rows, so it is preserved by the clone.
	 * @param int $menuId The menu_id of the link to copy
	 * @param int $toAppId The application id to copy the link into
	 * @return int|bool The new menu_id on success, or false if the source link could not be read
	 * @throws Exception if any insert fails
	 */
	function copyMenuLink($menuId, $toAppId) {
		global $xoopsDB;
		$menuId = intval($menuId);
		$toAppId = intval($toAppId);
		if($menuId <= 0 OR $toAppId < 0) { // appid 0 ("Forms with no app") is a valid target
			return false;
		}
		// read the source link row
		$sql = "SELECT screen, url, link_text, note FROM ".$xoopsDB->prefix("formulize_menu_links")." WHERE menu_id = ".$menuId;
		if(!$result = $xoopsDB->query($sql) OR !$row = $xoopsDB->fetchArray($result)) {
			return false;
		}
		// determine the next rank in the target application
		$rank = 1;
		if($rankResult = $xoopsDB->query("SELECT MAX(`rank`) as maxrank FROM ".$xoopsDB->prefix("formulize_menu_links")." WHERE appid = ".$toAppId)) {
			$rankRow = $xoopsDB->fetchArray($rankResult);
			$rank = intval($rankRow['maxrank']) + 1;
		}
		// insert the new link row
		$insertSql = "INSERT INTO ".$xoopsDB->prefix("formulize_menu_links")." (`appid`, `screen`, `rank`, `url`, `link_text`, `note`) VALUES (";
		$insertSql .= $toAppId.", '".formulize_db_escape($row['screen'])."', ".$rank.", '".formulize_db_escape($row['url'])."', '".formulize_db_escape($row['link_text'])."', '".formulize_db_escape($row['note'])."')";
		if(!$xoopsDB->query($insertSql)) {
			throw new Exception("Error copying menu link. SQL dump:\n" . $insertSql . "\n".$xoopsDB->error());
		}
		$newMenuId = $xoopsDB->getInsertId();
		// clone the permission rows so the copy carries the same group access and default_screen flags
		if($permResult = $xoopsDB->query("SELECT group_id, default_screen FROM ".$xoopsDB->prefix("formulize_menu_permissions")." WHERE menu_id = ".$menuId)) {
			while($permRow = $xoopsDB->fetchArray($permResult)) {
				$permInsert = "INSERT INTO ".$xoopsDB->prefix("formulize_menu_permissions")." (`menu_id`, `group_id`, `default_screen`) VALUES (".intval($newMenuId).", ".intval($permRow['group_id']).", ".intval($permRow['default_screen']).")";
				if(!$xoopsDB->query($permInsert)) {
					throw new Exception("Error copying menu link permissions. SQL dump:\n" . $permInsert . "\n".$xoopsDB->error());
				}
			}
		}
		return $newMenuId;
	}

	/**
	 * Delete a menu link and its permission rows by menu_id.
	 * @param int $menuId The menu_id of the link to delete
	 * @return void
	 */
	function deleteMenuLinkById($menuId) {
		global $xoopsDB;
		$menuId = intval($menuId);
		if($menuId <= 0) {
			return;
		}
		$sql= [
			"DELETE FROM ".$xoopsDB->prefix("formulize_menu_links")." WHERE menu_id = ".$menuId,
			"DELETE FROM ".$xoopsDB->prefix("formulize_menu_permissions")." WHERE menu_id = ".$menuId
		];
		foreach($sql as $query) {
			if($xoopsDB->query($query) == false) {
				throw new Exception("Error deleting menu link. SQL dump:\n" . $query . "\n".$xoopsDB->error());
			}
		}

		// check if there are any menu items left that are visible to anonymous users. If not, set the menu block visibility to exclude anonymous users
		$sql = "SELECT COUNT(*) AS anon_count FROM ".$xoopsDB->prefix("formulize_menu_permissions")." WHERE `group_id` = ".intval(XOOPS_GROUP_ANONYMOUS);
		if($result = $xoopsDB->query($sql)) {
			$row = $xoopsDB->fetchArray($result);
			if(intval($row['anon_count']) === 0) {
				if($anonRightsToFormMenuBlock = $this->getAnonRightsToFormMenuBlock()) {
					$gperm_handler = xoops_gethandler('groupperm');
					foreach($anonRightsToFormMenuBlock as $right) {
						$gperm_handler->delete($right);
					}
				}
			}
		}
	}

	/**
	 * Get the anonymous group permission objects for the menu block, if any exist
	 * @return array An array of group permission objects for the menu block, or an empty array if none exist
	 */
	function getAnonRightsToFormMenuBlock() {
		$gperm_handler = xoops_gethandler('groupperm');
		$formMenuBlockId = 24; // enforced by the override script in the installer
		$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('gperm_itemid', $formMenuBlockId));
		$criteria->add(new icms_db_criteria_Item('gperm_modid', 1));
		$criteria->add(new icms_db_criteria_Item('gperm_name', 'block_read'));
		$criteria->add(new icms_db_criteria_Item('gperm_groupid', XOOPS_GROUP_ANONYMOUS));
		return $gperm_handler->getObjects($criteria);
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
    $url = $menulink->getExpandedUrl();
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

function resolveMenuLinkURL($menulink) {
    if($url = buildMenuLinkURL($menulink)) {
        return $url;
    }
    include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
    global $xoopsUser;
    $screen_value = $menulink->getVar("screen");
    $sid = strstr($screen_value, 'sid=') ? intval(str_replace('sid=', '', $screen_value)) : 0;
    $fid = strstr($screen_value, 'fid=') ? intval(str_replace('fid=', '', $screen_value)) : 0;
    $menuLinkScreenId = $sid;
    if(!$menuLinkScreenId && $fid) {
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        $gperm_handler = xoops_gethandler('groupperm');
        $mid = getFormulizeModId();
        $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0 => XOOPS_GROUP_ANONYMOUS);
        $menulinkFormObject = $form_handler->get($fid);
        if(!$menulinkFormObject) {
            return false;
        }
        $singleEntryMetadata = getSingle($fid, ($xoopsUser ? $xoopsUser->getVar('uid') : 0)); // returns array with flag and entry as keys
				$singleEntry = $singleEntryMetadata['flag'];
        $view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);
        $view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);
        if((!$singleEntry AND $xoopsUser) OR $view_globalscope OR ($view_groupscope AND $singleEntry != "group")) {
            $menuLinkScreenId = $menulinkFormObject->getVar('defaultlist');
        } else {
            $menuLinkScreenId = $menulinkFormObject->getVar('defaultform');
        }
    }
    if($menuLinkScreenId) {
        $screen_handler = xoops_getmodulehandler('screen', 'formulize');
        $menuLinkScreen = $screen_handler->get($menuLinkScreenId);
        if($menuLinkScreen && ($rewriteruleAddress = $menuLinkScreen->getVar('rewriteruleAddress'))) {
            return XOOPS_URL . "/" . $rewriteruleAddress;
        }
    }
    return XOOPS_URL . "/modules/formulize/index.php?" . $screen_value;
}



