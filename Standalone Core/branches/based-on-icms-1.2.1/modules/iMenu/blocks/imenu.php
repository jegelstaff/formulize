<?php
// begin - Nov 6, 2005 - jpc - Freeform Solutions

if(file_exists(XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php")) {
	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
}

// end - Nov 6, 2005 - jpc - Freeform Solutions


function b_imenu_show($options) {
        global $xoopsDB,$xoopsUser;
        $block = array();
	$group = $xoopsUser ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);		

	// Modified by Freeform Solutions May 19 2005 (Revenge of the XOOPS)
	// 1. Get all Parents
	// 2. Get current page location
	// 3. Find out if current page location contains the URL of any specified menu item
	// 4. if so, flag to expand that parent to show its children
	// 5. draw parents, plus expanded element if necessary

	$parentId = "";

	$parents = $xoopsDB->query("SELECT id, groups, link, title, target FROM ".$xoopsDB->prefix("imenu")." WHERE hide=0 AND parent='0' ORDER BY weight ASC");

	// figure out if we're supposed to send all subs or just the "current" one
	// added January 30 2007 -- jwe
	$module_handler =& xoops_gethandler('module');
	$config_handler =& xoops_gethandler('config');
	$iMenuModule =& $module_handler->getByDirname("iMenu");
    	$iMenuConfig =& $config_handler->getConfigsByCat(0, $iMenuModule->getVar('mid'));
     	$send_all_subs = $iMenuConfig['send_all_subs'];

     	
      	// get URL 
      	$url_parts = parse_url(XOOPS_URL);
      	$currentURL = $url_parts['scheme'] . "://" . $url_parts['host'] . htmlSpecialChars(strip_tags($_SERVER['REQUEST_URI'])); // request_uri not being output to screen, but security precautions always welcome
      	
      	// get all menu links
      	$menuLinksQ = $xoopsDB->query("SELECT id, groups, link, parent FROM " . $xoopsDB->prefix("imenu"));
      	$breakready = 0;
		$saveNoSubParent = "";
      	while($menuLinks = $xoopsDB->fetchArray($menuLinksQ)) {
      		if($breakready) { // if we matched on a top level entry last time through...
      			$areSubsQ = $xoopsDB->query("SELECT id FROM " . $xoopsDB->prefix("imenu") . " WHERE parent='$foundId'");
      			$areSubsRes = $xoopsDB->fetchArray($areSubsQ);
     			if($areSubsRes['id']) { // if a sub was found for the matched top level entry...
      				break; // stop looking for matches
      			} else { // ignore the top level entry we found and keep looking...
					$saveNoSubParent = $foundId;
      				unset($foundId);
      				unset($parentId);
	     				$breakready = 0;
      			}
      		}

      		$groups = explode(" ",$menuLinks['groups']);
      		//if(count(array_intersect($group,$groups)) > 0) { // if this is a link the user is allowed to see
          if (checkiMenuPerms($group, $menuLinks['groups'], $menuLinks['link'])) {
      			//print $currentURL . "<br>";
      			//print $menuLinks['link'] . "<br><br>";


      			// begin - Nov 6, 2005 - jpc - Freeform Solutions

									//print strstr($menuLinks['link'], "XOOPS_URL");

                  // Is this an executable link?
									list($execute, $executedLink) = executeLink($menuLinks['link']);
									if($execute) {
										eval("\$menuLinks['link'] = " . $executedLink . ";");
									} elseif($executedLink) { // we might have just modified the link if it was missing the http part
										$menuLinks['link'] = $executedLink;
									}
      			// end - Nov 6, 2005 - jpc - Freeform Solutions
      			
      			if(strstr($currentURL, htmlSpecialChars(strip_tags(trim($menuLinks['link']))))) { // if this link is subsumed by the current URL (must do corresponding corrections to the syntax as we do when getting the current URL, so matches can be found)
      				$foundId = $menuLinks['id'];
      				$parentId = $menuLinks['parent'];
				if($parentId != 0) {
      					break; // exit while loop if we've got a match on a sub
      				} else { // else, we've got a match on a top level entry, which may in fact have no subs, so we only break if it turns out there is a sub.  If no subs, then we keep looking. 2007.03.16 -- jwe & nmc
      					$breakready = 1;
      				}
      			}
      		}
      	}

      	// find parent of match
      	if(!isset($parentId) OR $parentId == 0) {
						$parentId = isset($foundId) ? $foundId : $saveNoSubParent; // if the match was on a parent, then make that link's own id into the flag to be used below. // If the only valid match was on a parent with no subs, then we use that value instead.
      	}

     	while($myrow = $xoopsDB->fetchArray($parents) ) {
			$active = $myrow['id'] == $parentId ? true:false; //determine if this is the active link - added 2007.03.16  -- jwe & nmc
			//-- nmc 2007.03.28 -- begin
			// Need to determine which links are allowed before deciding on the $firstsub and $lastsub => check groups before calling drawlink & removed $group from call
			unset($menuItemGroups);
			$menuItemGroups = explode(" ",$myrow['groups']);
			//if (count(array_intersect($group,$menuItemGroups)) > 0) {
      if (checkiMenuPerms($group, $myrow['groups'], $myrow['link'])) {
				$block = drawLink($active, $myrow, $block, 0, $options); 
	     		if($active OR $send_all_subs) { // amended 2007.03.16  -- jwe & nmc
     			//print "SELECT id, groups, link, title, target FROM ".$xoopsDB->prefix("imenu")." WHERE hide=0 AND parent='" . $myrow['id'] . "' ORDER BY weight ASC<br>";
     		      $result = $xoopsDB->query("SELECT id, groups, link, title, target FROM ".$xoopsDB->prefix("imenu")." WHERE hide=0 AND parent='" . $myrow['id'] . "' ORDER BY weight ASC");
			$numSubs = $xoopsDB->getRowsNum($result);
					unset($subMenuItemArray);											
					$subMenuItemArray = array();					
					for($ptrSubMenu=1;$ptrSubMenu<=$numSubs;$ptrSubMenu++) { 							
		     			$subMenuItem = $xoopsDB->fetchArray($result);					
						unset($subMenuItemGroups);										
						$subMenuItemGroups = explode(" ",$subMenuItem['groups']);
            if (checkiMenuPerms($group, $subMenuItem['groups'], $subMenuItem['link'])) {
						//if (count(array_intersect($group,$subMenuItemGroups)) > 0) {	
							$subMenuItemArray[] = $subMenuItem ;							
						}															
					}																
					$numAllowedSubs = count($subMenuItemArray) - 1;						
					for($gs=0;$gs<=$numAllowedSubs;$gs++) {							
						$lastSub =  $gs == $numAllowedSubs ? true : false;		
						$firstSub = $gs == 0 ? true : false;						
						$block = drawLink(false, $subMenuItemArray[$gs], $block, 1, $options, $lastSub, $firstSub);	
					}															
					// nmc 2007.03.28 -- end
     			}
     		}
     	}
	return $block;
}

// this function checks whether a user has permission for the link
// checks to see if it's a pageworks page or formulize form, and if so, determines your permission from those modules
// HOWEVER...iMenu's permissions are checked first, so you can override someone's ability to see a link that they have permission for, in situations where you are constructing a menu very carefully.
// $group is the user's groups
// $groups is the groups the link is allowed for, according to iMenu
// $link is the raw unparsed/processed/executed text of the link (assumption is that only XOOPS_URL is used as a PHP building block, and page ID or form ID is not derived somehow in PHP)
function checkiMenuPerms($group, $groups, $link) {
  
  
  
  // remove the close \?\> in case it's there, since it will throw off the text parsers
  $link = trim(ltrim(rtrim($link, "?>"), "<?"));
  $link = rtrim($link, "\"");
  $link = rtrim($link, "'");
  
  $groups = trim($groups) == "" ? "" : explode(" ", $groups);
  
  if(is_array($groups)) {
    if(count(array_intersect($group,$groups)) > 0) {
      return true;
    } else {
      return false;
    }
  } elseif(strstr(strtolower($link), strtolower("/modules/formulize"))) {
      if($fidpos = strpos($link, "fid=")) {
        $nextand = strpos($link, "&", $fidpos);
        $length = $nextand ? $nextand - ($fidpos+4) : strlen($link) - ($fidpos+4);
        $fid = substr($link, $fidpos+4, $length);
        $sid = 0;
      } elseif($sidpos = strpos($link, "sid=")) {
				$nextand = strpos($link, "&", $sidpos);
				$length = $nextand ? $nextand - ($sidpos+4) : strlen($link) - ($sidpos+4);
				$sid = substr($link, $sidpos+4, $length);
        $screen_handler =& xoops_getmodulehandler('screen', 'formulize');
        $screen = $screen_handler->get($sid); // first get basic screen object to determine type
        $fid = $screen->getVar('fid');
      }
      $gperm_handler =& xoops_gethandler('groupperm');
      include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
      $perm = $gperm_handler->checkRight("view_form", $fid, $group, getFormulizeModId()); // note 'group' is the array of all groups the user belongs to.  iMenu was written by someone not contemplating large overlapping group memberships, I think.
      return $perm;
  } elseif(strstr(strtolower($link), strtolower("/modules/pageworks"))) {
    if($pagepos = strpos($link, "page=")) {
      $nextand = strpos($link, "&", $pagepos);
      $length = $nextand ? $nextand - ($pagepos+5) : strlen($link) - ($pagepos+5);
      $page = substr($link, $fidpos+5, $length);
     }
     $gperm_handler =& xoops_gethandler('groupperm');
     include_once XOOPS_ROOT_PATH . "/modules/pageworks/include/functions.php";
     return $gperm_handler->checkRight('view', $page, $group, getPageworksModId());
  } 
}

// added $active as parameter 2007.03.16  -- jwe & nmc
// removed $group as parameter 2007.03.28 -- nmc
function drawLink($active, $myrow, $block, $sub="0", $options, $lastSub=false, $firstSub=false) {

		$myts =& MyTextSanitizer::getInstance();

		if(method_exists($myts, 'formatForML')) {
			$title = $myts->formatForML($myrow["title"]);
		} else {
			$title = $myrow['title'];
		}
		if ( !XOOPS_USE_MULTIBYTES ) {
			if (strlen($title) >= $options[0]) {
				$title = $myts->makeTboxData4Show(substr($title,0,($options[0]-1)))."...";
			}
		} else {
			$title = $myts->makeTboxData4Show($title);
		}


			$imenu['title'] = $title;
			$imenu['target'] = $myrow['target'];
			$imenu['link'] = $myrow['link'];
            

			// begin - Nov 6, 2005 - jpc - Freeform Solutions

            // Is this an executable link?
            list($execute, $executedLink) = executeLink($imenu['link']);
						if($execute) {
							eval("\$imenu['link'] = " . $executedLink . ";");
						} elseif($executedLink) { // we might have just modified the link if it was missing the http part
							$imenu['link'] = $executedLink;
						}          
			// end - Nov 6, 2005 - jpc - Freeform Solutions
			
            
            else if (eregi("^\[([a-z0-9]+)\]$", $myrow['link'], $moduledir)) {
				$module_handler = & xoops_gethandler( 'module' );
				$module =& $module_handler->getByDirname($moduledir[1]);
				if ( is_object( $module ) && $module->getVar( 'isactive' ) ) {
					$imenu['link'] = XOOPS_URL."/modules/".$moduledir[1];
				}
			}

			// check to see if this is a link to the inbox, and if so, change the title to include the number of new messages and change colour appropriately
			if($imenu['link'] ==  XOOPS_URL . "/viewpmsg.php") {
				global $xoopsUser;
				$criteria = new CriteriaCompo(new Criteria('read_msg', 0));
				$criteria->add(new Criteria('to_userid', $xoopsUser->getVar('uid')));
				$pm_handler =& xoops_gethandler('privmessage');
				$imenu['messages'] = $pm_handler->getCount($criteria);
			}


			$imenu['sub'] = $sub;
			$imenu['lastsub'] = $lastSub;	
			$imenu['firstsub'] = $firstSub;
			$imenu['active'] = $active; // added 2007.03.16  -- jwe & nmc
	        $block['contents'][] = $imenu;
		
        return $block;
}

function b_imenu_edit($options) {
	$form = _IM_IMENU_CHARS."&nbsp;<input type='text' name='options[]' value='".$options[0]."' />&nbsp;"._IM_IMENU_LENGTH."";
	return $form;
}

function executeLink($link) {
		$phptag = substr($link, 0, 5);
		$phptag = $phptag === "<?php" ? $phptag : substr($link, 0, 2);
		if(($phptag === "<?" OR $phptag === "<?php") OR strstr($link, "XOOPS_URL")) {
			if($phptag === "<?") {
				$endPos = strlen($link) - 4;
				$executableLink = substr($link, 2, $endPos);
			} elseif($phptag === "<?php") {
				$endPos = strlen($link) - 7;
				$executableLink = substr($link, 5, $endPos);
			} else {
				$executableLink = $link;
			}
			$executeIt = true;
		} elseif(!strstr($link, "http://")) {
			$executeIt = false;
			$executableLink = XOOPS_URL . $link;
		} else {
			$executeIt = false;
			$executableLink = "";
		} 
		return array(0=>$executeIt, 1=>$executableLink);
}
		
		
?>