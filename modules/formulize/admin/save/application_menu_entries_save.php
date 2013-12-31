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
    
    // this file handles saving of submissions from the application_settings page of the new admin UI
    
    // if we aren't coming from what appears to be save.php, then return nothing
    if(!isset($processedValues)) {
        return;
    }
    
    $application_handler = xoops_getmodulehandler('applications', 'formulize');
    $appid = $_POST['formulize_admin_key'];
    $setOfMenuItems = $_POST['menu_items'];
    
    // added Oct 2013 W.R.
    if($_POST['deletemenuitem']) {
  		$menuitem = $_POST['deletemenuitem'];
		$application_handler->deleteMenuLink($appid, $menuitem);  
		$_POST['reload_settings'] = 1;
  	}
    
    $menuLinkAdded = false;
    foreach($setOfMenuItems as $menuitems) {
    
    if(strlen($menuitems) > 0){
    	$linkValues = explode("::",$menuitems);
	    if($linkValues[2] != 'url') {
		$linkValues[3] = '';
		$menuitems = implode("::", $linkValues);
	    }
        if($linkValues[0] == "null"){
        	$application_handler->insertMenuLink($appid, $menuitems);
		    $_POST['reload_settings'] = 1;
		    $menuLinkAdded = true;
        }
        else{ //if($linkValues[0] == menu_id)
        	$application_handler->updateMenuLink($appid, $menuitems);
        }
    }
    }
    
    // Sort update of menu links
    // added Oct 2013 W.R.

		if($_POST['menuorder']) {
            
			// retrieve all the links that belong to this application
			$Links = $application_handler->getMenuLinksForApp($appid, all);
            
			// get the new order of the links...
	$newOrder = explode("drawer-".intval($_POST['tabnumber'])."[]=", str_replace("&", "", $_POST['menuorder']));
			unset($newOrder[0]);
	if($menuLinkAdded) {
	    $newOrder[] = count($newOrder); // assign the current count, to a new key (so the key and value will be the same, to represent the most recent menu entry)
	}
			// newOrder will have keys corresponding to the new order, and values corresponding to the old order
			if(count($Links) != count($newOrder)) {
				print "Error: the number of links being saved did not match the number of links already in the database";
				return;
			}
            
			// modify links
			$oldOrderNumber = 0;
			$needReload = 0;
			foreach($Links as $link) {
				$menu_id = $link->getVar('menu_id');	
				$newOrderNumber = array_search(($oldOrderNumber),$newOrder);
				$link->assignVar('rank',$newOrderNumber);	
				if($oldOrderNumber != $newOrderNumber) {		
					$_POST['reload_settings'] = 1;
				}		
				$oldOrderNumber++;
			}
            
			// presist changes
			$application_handler->updateSorting($Links);
		}
    
    // if the form name was changed, then force a reload of the page...reload will be the application id
    if(isset($_POST['reload_settings']) AND $_POST['reload_settings'] == 1) {
        print "/* eval */ reloadWithScrollPosition();";
    }