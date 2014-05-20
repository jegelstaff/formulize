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
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

require_once XOOPS_ROOT_PATH.'/kernel/object.php';

global $xoopsDB;

    class formulizeApplicationMenuLink extends XoopsObject {
        
        function formulizeApplicationMenuLink() {
            $this->XoopsObject();
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
        }
    }
    
    class formulizeApplicationMenuLinksHandler  {
        
        var $db;
        function formulizeApplicationMenuLinksHandler(&$db) {
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
			$sql .= ' WHERE appid = ' . $id. ' '. $groupSQL .' GROUP BY menu_id,appid,screen,rank,url,link_text ORDER BY rank';
            
            //echo $sql;
            
            if ($result = $this->db->query($sql)) { 
                
                while($resultArray = $this->db->fetchArray($result)) {			
                    $newLinks = new formulizeApplicationMenuLink();
                    $newLinks->assignVars($resultArray);
                    array_push($linksArray, $newLinks);
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
    }
        
    
class formulizeApplication extends XoopsObject {
  
  function formulizeApplication() {
    $this->XoopsObject();
    $this->initVar("appid", XOBJ_DTYPE_INT, NULL, false);
    $this->initVar("name", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
    $this->initVar("description", XOBJ_DTYPE_TXTAREA);
    $this->initVar("forms", XOBJ_DTYPE_ARRAY);
    $this->initVar("links", XOBJ_DTYPE_ARRAY);
    $this->initVar("all_links", XOBJ_DTYPE_ARRAY);  
  }
  
}

class formulizeApplicationsHandler {
  var $db;
	function formulizeApplicationsHandler(&$db) {
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
        $sql = 'SELECT * FROM '.$xoopsDB->prefix("formulize_applications").' as t1, '.$xoopsDB->prefix("formulize_application_form_link").' as t2 WHERE t1.appid = t2.appid AND t2.fid = '.$fid.' ORDER BY t1.name';
      } else {
        $sql = 'SELECT * FROM '.$xoopsDB->prefix("formulize_applications").' ORDER BY name';  
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
        $sql = 'SELECT link.fid FROM '.$xoopsDB->prefix("formulize_application_form_link").' as link, '.$xoopsDB->prefix("formulize_id").' as forms WHERE link.appid = '.$newAppId.' AND forms.id_form = link.fid ORDER BY forms.desc_form';
        $foundForms = array();
        if($formRes = $this->db->query($sql)) {
          while($formArray = $this->db->fetchArray($formRes)) {
            $foundForms[] = $formArray['fid'];
          }
        } else {
          print $xoopsDB->error();
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
    $appObject->assignVar('appid', $appid);
    
    // now assign the forms
    
    $foundForms = array();
		$checkSQL = "SELECT fid FROM ".$this->db->prefix("formulize_application_form_link"). " WHERE appid=".$appid;
		$checkRes = $this->db->query($checkSQL);
		while($checkArray = $this->db->fetchArray($checkRes)) {
			$foundForms[] = $checkArray['fid'];
		}
    // figure out what we need to insert and what we need to remove, ie: the differences
    $formsForRemoval = array_diff($foundForms, $appObject->getVar('forms'));// in foundForms, but not in the app
    $formsForInsert = array_diff($appObject->getVar('forms'), $foundForms); // in the app, but were not found

		$runRemoval = false;
		$runInsert = false;
    if(count($formsForInsert)>0) {
    	$insertStart = true;
      $insertSQL = "INSERT INTO ".$this->db->prefix("formulize_application_form_link")." (`fid`, `appid`) VALUES ";
      foreach($formsForInsert as $thisFid) {
        if(!$insertStart) { $insertSQL .= ", "; }
  			$insertSQL .= "(".$thisFid.", ".$appid.")";
  			$insertStart = false;
  			$runInsert = true;
      }
    }
    if(count($formsForRemoval)>0) {
      $removalSQL = "DELETE FROM ".$this->db->prefix("formulize_application_form_link")." WHERE ";
      $removalStart = true;
      foreach($formsForRemoval as $thisFid) {
        if(!$removalStart) { $removalSQL .= " OR "; }
        $removalSQL .= " (`appid`=$appid AND `fid`=$thisFid) ";
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
        return $links_handler->get($appid,$all); 
    }
    
    
    //modified Oct 2013 W.R.
    function insertMenuLink($appid,$menuitem){
        
        global $xoopsDB;
        
        $rank = 1;
        $rankquery = "SELECT MAX(rank) FROM `".$xoopsDB->prefix("formulize_menu_links")."` WHERE appid=".$appid.";";
        if($result = $xoopsDB->query($rankquery)) {
        	//if empty query, then rank = 1, else, rank is the next larger number
        	$max = $xoopsDB->fetchArray($result);
            $rank= $max['MAX(rank)']+1;
        }
        
        //0=menuid, 1=menuText, 2=screen, 3=url, 4=groupids, 5=default_screen
        $linkValues = explode("::",$menuitem);
        $insertsql = "INSERT INTO `".$xoopsDB->prefix("formulize_menu_links")."` VALUES (null,". $appid.",'". formulize_escape($linkValues[2])."',".$rank.",'".formulize_escape($linkValues[3])."','".formulize_escape($linkValues[1])."');";
		if(!$result = $xoopsDB->query($insertsql)) {
			exit("Error inserting Menu Item. SQL dump:\n" . $insertsql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
		}else{
			
			$menuid = $xoopsDB->getInsertId();
			if($linkValues[4] != "null" and count($linkValues[4]) > 0){
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
					if(!$result = $xoopsDB->query($permissionsql)) {
						exit("Error inserting Menu Item permissions.".$linkValues[4]." SQL dump:\n" . $permissionsql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
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
        $deletemenuitems = "DELETE FROM `".$xoopsDB->prefix("formulize_menu_links")."` WHERE appid=".$appid." AND menu_id=" .$menuitem .";";
        $deletemenupermissions = "DELETE FROM `".$xoopsDB->prefix("formulize_menu_permissions")."` WHERE menu_id=" .$menuitem .";";
        if(!$result = $xoopsDB->query($deletemenuitems)) {
            //no menu items deleted
        }else{
            $xoopsDB->query($deletemenupermissions);
        }
    }


     // modified Oct 2013 W.R.
    function updateMenuLink($appid,$menuitems){
        global $xoopsDB;       
        //0=menuid, 1=menuText, 2=screen, 3=url, 4=groupids, 5=default_screen 
        $linkValues = explode("::",$menuitems);
        $updatesql = "UPDATE `".$xoopsDB->prefix("formulize_menu_links")."` SET screen= '".formulize_escape($linkValues[2])."', url= '".formulize_escape($linkValues[3])."', link_text='".formulize_escape($linkValues[1])."' where menu_id=".formulize_escape($linkValues[0])." AND appid=".$appid.";";
        if(!$result = $xoopsDB->query($updatesql)) {
            exit("Error updating Menu Item. SQL dump:\n" . $updatesql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
        }else{
        	//delete existing permissions for this menu item
        	$deletepermissions = "DELETE FROM `".$xoopsDB->prefix("formulize_menu_permissions")."` WHERE menu_id=".formulize_escape($linkValues[0]).";";
       	 	$result = $xoopsDB->query($deletepermissions);
        
       	 	if($linkValues[4] != "null" and count($linkValues[4]) > 0){
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
                    $permissionsql = "INSERT INTO `".$xoopsDB->prefix("formulize_menu_permissions")."` VALUES (null,".formulize_escape($linkValues[0]).",". $groupid.",".$defaultScreen.")";                     
           	     if(!$result = $xoopsDB->query($permissionsql)) {
           	     	exit("Error updating Menu Item permissions.".$linkValues[4]." SQL dump:\n" . $permissionsql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
           	 		}
                    $defaultScreen = 0;
           		  }
        	 }
		}
	}
	
	// added Oct 2013 W.R.
    function updateSorting($Links){
        global $xoopsDB;
        foreach($Links as $link) {
  			$menu_id = $link->getVar('menu_id');	
  			$rank = $link->getVar('rank');           
        	$updatesql = "UPDATE `".$xoopsDB->prefix("formulize_menu_links")."` SET rank= ".$rank." where menu_id=".$menu_id.";";
        	if(!$result = $xoopsDB->query($updatesql)) {
            	exit("Error sorting Menu List. SQL dump:\n" . $updatesql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
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
            exit("Error checking default screen. SQL dump:\n" . $checksql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
        }
    }
    
    /*
     //added Oct 2013
     function defaultScreenExistsForGroup($appid, $menuid, $group_ids){
         global $xoopsDB; 
         $groups = explode(",",$group_ids);
         foreach($groups as $group_id) {  
             $checksql = "Select * FROM `".$xoopsDB->prefix("formulize_menu_links")."` as links LEFT JOIN `".$xoopsDB->prefix("formulize_menu_permissions");
             $checksql .= "` AS perms ON links.menu_id = perms.menu_id WHERE appid=".$appid." AND default_screen = 1 AND group_id=".$group_id.";";
             if($result = $xoopsDB->query($checksql)) {
                 $rows = $xoopsDB->fetchAssoc($result);
                 if ($rows==''){
                     print("no screen exist for group".$group_id);//return false;
                 }
                 else{
                     print(" screen exists for group".$group_id);//return true;
                 }
             }
             else {
                 exit("Error checking default screen. SQL dump:\n" . $checksql . "\n".$xoopsDB->error()."\nPlease contact <a href=mailto:formulize@freeformsolutions.ca>Freeform Solutions</a> for assistance.");
             }
         }
     }
     */
}



