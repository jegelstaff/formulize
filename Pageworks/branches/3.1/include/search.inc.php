<?php
// ------------------------------------------------------------------------- 
//	pageworks
//		Copyright 2004, Freeform Solutions
// 		
// ------------------------------------------------------------------------- 
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

// ENTIRE SEARCH SYSTEM HAS NO KNOWLEDGE OF GROUP SCOPE ACCESS CONTROL!
// DESIGNED INITIALLY FOR BLANKET DISPLAY OF ALL DATA TO PUBLIC


function getTitle($id_req, $pf_search_title) {

	global $xoopsDB;

	// 1. return value in formulize_form where id_req equals passed param, AND
	// 2. ele_caption formatted for form table (` to ') matches ele_id of pf_search_title (which is the fe_id from the formulize_framework_elements table)
	// because of the stupid `/' BS, we have to split this into two queries.
	// -----
	// 1. get caption from form table based on pf_search_title

	$sql = "SELECT ele_caption FROM " . 
		$xoopsDB->prefix("formulize") . ", " .
		$xoopsDB->prefix("formulize_framework_elements") . ", " .
		$xoopsDB->prefix("pageworks_frameworks") . 
		" WHERE " . 
			$xoopsDB->prefix("pageworks_frameworks") . ".pf_search_title=" .
			$xoopsDB->prefix("formulize_framework_elements") . ".fe_id AND " .
			$xoopsDB->prefix("formulize_framework_elements") . ".fe_element_id=" .
			$xoopsDB->prefix("formulize") . ".ele_id AND " .
			$xoopsDB->prefix("pageworks_frameworks") . ".pf_search_title='" . $pf_search_title . "'";
	$res = $xoopsDB->query($sql);
	if($res) {
		$array = $xoopsDB->fetchArray($res);
		$form_cap = $array['ele_caption'];
	} else {
		return "No title found for this page";
	}

	// 2. convert the caption to formulize_form format
	$form_cap = str_replace ("&#039;", "`", $form_cap);
	$form_cap = str_replace ("&quot;", "`", $form_cap);
	$form_cap = str_replace ("'", "`", $form_cap);

	// 3. get the value of this caption for the id_req passed
	
	$sql = "SELECT ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req='" . $id_req . "' AND ele_caption=\"" . $form_cap . "\"";

	$res = $xoopsDB->query($sql);
	if($res) {
		$array = $xoopsDB->fetchArray($res);
		return $array['ele_value'];
	} else {
		return "No value found for this title";
	}
}
// RETURNS THE RESULTS OF AN SQL STATEMENT
// returns a multidimensioned array where the first index is the row of the result and the second index is the field name in that row
/*function go($query) {
	//print "$query"; // debug code
	$res = mysql_query($query);
	while ($array = mysql_fetch_array($res)) {
		$result[] = $array;
	}
	return $result;
}*/


// This function returns the caption, formatted for formulize_form, based on the handle for the element
function getFFCaptionForPageworksSearch($handle, $frid, $fid) {
	global $xoopsDB;
	define(DBPRE, $xoopsDB->prefix('') . "_");
	//print "SELECT fe_element_id FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '$frid' AND fe_form_id = '$fid' AND fe_handle = '$handle'";
	$elementId = go("SELECT fe_element_id FROM " . DBPRE . "formulize_framework_elements WHERE fe_frame_id = '$frid' AND fe_form_id = '$fid' AND fe_handle = '$handle'");
	//print "SELECT ele_caption FROM " . DBPRE . "formulize WHERE ele_id = '" . $elementId[0]['fe_element_id'] . "'";
	$caption = go("SELECT ele_caption FROM " . DBPRE . "formulize WHERE ele_id = '" . $elementId[0]['fe_element_id'] . "'"); 
	$ffcaption = eregi_replace ("&#039;", "`", $caption[0]['ele_caption']);
	$ffcaption = eregi_replace ("&quot;", "`", $ffcaption);
	return $ffcaption;
}

function pageworks_search($queryarray, $andor, $limit, $offset, $userid) {

	global $xoopsDB, $myts, $xoopsUser;

	// search for results

	//1. figure out what pageworks the user has access to
	//2. figure out what forms are 1 level removed from the main form in the frameworks used on those pages
	//3. save the page ids, and corresponding form ids, and search titles
	//4. search in formulize_form for those form_ids and the search terms in ele_value
	//5. record hits by id_req
	//6. add hits to the saved info above (so hits are associated with the pages and search titles, etc)
	//7. generate results based on this array

	// GET THE MODULE ID
	$res4 = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='pageworks'");
	if ($res4) {
		while ($row = mysql_fetch_row($res4))
			$module_id = $row[0];
	}

	$groups = $xoopsUser ? $xoopsUser->getGroups() : XOOPS_GROUP_ANONYMOUS;
	$gperm_handler = &xoops_gethandler('groupperm');
	$allowedPages = $gperm_handler->getItemIds("view", $groups, $module_id);

	if(count($allowedPages)>0) { // IF condition to prevent results for users who have access to zero pages

	array_unique($allowedPages);

	// generate sql filter based on $allowedPages
	$start = 1;
	foreach($allowedPages as $apage) {
		if($start) {
			$sql_filter = "(" . $xoopsDB->prefix("pageworks_frameworks") . ".pf_page_id=$apage";
			$start = 0;	
		} else {
			$sql_filter .= " OR " . $xoopsDB->prefix("pageworks_frameworks") . ".pf_page_id=$apage";
		}
		$search_profile[$apage];
	}
	$sql_filter .= ")";

	// get form_ids of all main forms and one-level-away-linked forms in the frameworks on that page, and check that page is searchable
	$sql = "SELECT fl_form1_id, fl_form2_id, pf_page_id, pf_search_title, pf_filters, pf_framework FROM " . 
		$xoopsDB->prefix("formulize_framework_links") . ", " . 
		$xoopsDB->prefix("pageworks_frameworks") . ", " .
		$xoopsDB->prefix("pageworks_pages") . 
		" WHERE " . $sql_filter . " AND (" . // look for the allowed pages
			$xoopsDB->prefix("pageworks_frameworks") . ".pf_framework=" . 			// join to links table
			$xoopsDB->prefix("formulize_framework_links") . ".fl_frame_id AND (" .		
			$xoopsDB->prefix("pageworks_frameworks") . ".pf_mainform=" . 			// mainform is present in the link set
			$xoopsDB->prefix("formulize_framework_links") . ".fl_form1_id OR " .
			$xoopsDB->prefix("pageworks_frameworks") . ".pf_mainform=" . 
			$xoopsDB->prefix("formulize_framework_links") . ".fl_form2_id)) AND (" .
			$xoopsDB->prefix("pageworks_pages") . ".page_searchable=1 AND " .			// page is searchable
			$xoopsDB->prefix("pageworks_pages") . ".page_id=" . 
			$xoopsDB->prefix("pageworks_frameworks") . ".pf_page_id)";
	
	if($res=$xoopsDB->query($sql)) {
		$start = 1;
		while($array = $xoopsDB->fetchArray($res)) {
			//print_r($array);
			//print "<br>";
			$form_ids[$array['fl_form1_id']][] = $array['pf_page_id'];
			$form_ids[$array['fl_form2_id']][] = $array['pf_page_id'];
			$page_frameworks[$array['pf_page_id']][] = $array['pf_framework'];
			$search_titles[$array['pf_page_id']][] = $array['pf_search_title'];
			$page_filters[$array['pf_page_id']][] = $array['pf_filters'];
			if($start) {
				$search_filter = "(id_form=" . $array['fl_form1_id'] . " OR id_form=" . $array['fl_form2_id'];
				$start = 0;
			} else {
				$search_filter .= " OR id_form=" . $array['fl_form1_id'] . " OR id_form=" . $array['fl_form2_id'];
			}
		}		
		$search_filter .= ")";
	} else {
		exit("Could not find forms to search corresponding to the pages you can view");
	}
	

	// search formulize_form with search filter
	// NOTE - LINKED FIELDS ARE NOT SEARCHED PROPERLY, SINCE THE LINK CODE IS IN ELE_VALUE AND NOT THE ACTUAL VALUE.

	$start=1;
	if($andor != "exact") {
		foreach($queryarray as $aterm) {
			$aterm = addslashes($aterm);
			$term_list[] = $aterm;
			if($start) {
				$search_terms = "(ele_value LIKE \"%$aterm%\"";
				$start=0;
			} else {
				$search_terms .= " OR ele_value LIKE \"%$aterm%\""; // note, always use OR -- if AND is used, then we need to check in the results to see if all search terms were matched for a given id_req in order to validate an AND query
			}
		}
		$search_terms .= ")";
	} else { // exact match asked for so treat all search terms as a phrase
		$search_terms = "ele_value LIKE \"%";
		foreach($queryarray as $aterm) {
			$aterm = addslashes($aterm);
			if($start) {
				$search_terms .= $aterm;
				$term_list[0] = $aterm; // term_list likely not necessary with exact matches
				$start=0;
			} else {
				$search_terms .= " " . $aterm;
				$term_list[0] .= " " . $aterm;
			}
		}
		$search_terms .= "%\"";
	}
	

	$sql = "SELECT id_req, id_form, ele_value FROM " . $xoopsDB->prefix("formulize_form") . " WHERE $search_filter AND $search_terms ORDER BY id_req";
	//print "$sql";
	if($res = $xoopsDB->query($sql)) {
		$prev_id = "start";
		$skip = 0;
		while($array = $xoopsDB->fetchArray($res)) {
			// Compile results of search so that:
			// 1. we store compiled results in array with one entry per id_req
			// 2. we count the occurences of each search term within one id_req to determine relevance (term_list array compiled above)
			// 3. we require all search terms to be present for AND queries
			// 4. we associate the valid page_ids for each id_req with that result (based on id_form and form_ids array created above) -- must ensure that page filters are used to exclude hits that would not be part of that page.

			//print "PREV: $prev_id<BR>";
			//print "CURR: " . $array['id_req'] . "<BR><BR>";

			if($array['id_req'] != $prev_id OR $prev_id=="start") { // initialize a new results compilation, start by checking this ID against filter for framework for this page

				// must do checking to see if previous id_req met AND search criteria, otherwise throw it out
				if($prev_id != "start") {
					$foundForAnd = 0;
					$totalcount = 0;
					foreach($term_list as $aterm) {
						if($term_counts[$prev_id][$aterm] > 0) { 
							$foundForAnd++; 
							$totalcount += $term_counts[$prev_id][$aterm];
						}
					}
					if($foundForAnd != count($term_list) AND $andor == "AND") { 
						unset($results[$prev_id]); 
					} else {
						$results[$prev_id]['count'] = $totalcount;
					}
				}			
				// 1. use id_form to check for page_id
				// 2. use page_id to get filter
				// 3. query id_req, id_form for a match on filter
				// 4. if no match, set skip flag that gets reevaluated with each new id_req
				$skip = 0;
				//print_r($form_ids);
				foreach($form_ids[$array['id_form']] as $key=>$page_id) {
					//if(!in_array($page_id, $results[$array['id_req']]['page'])) { // check to avoid duplicate results, could happen when one form appears more than once in the same framework (multiple links make up framework)

					//print_r($array);
					//print "<br>";

					//print_r($page_filters);
					//print "Filter: " . $page_filters[$page_id][0] . "<br>";

					// $filters = explode("][", $page_filters[$page_id]); // assuming ONE filter for now, logic gets insane for more than one
					// foreach($filters as $afilter) {
					if($page_filters[$page_id][0]=="") { // assume only one filter in use!
						//print "NO FILTER!<BR>";
						$results[$array['id_req']]['id_req'] = $array['id_req']; // we lose the key after multisort below, so we need this info in the array as a value
						$results[$array['id_req']]['page'][] = $page_id;
						$results[$array['id_req']]['title'][] = getTitle($array['id_req'], $search_titles[$page_id][0]); // assume only one framework per page, therefore only one search title
					} else {
						$filterparts = explode("/**/", $page_filters[$page_id][0]); // assume only one filter in use!
						$capforfilter = getFFCaptionForPageworksSearch($filterparts[0], $page_frameworks[$page_id][0], $array['id_form']); // assume only one framework per page for now too
						$capforfilter = addslashes($capforfilter);
						$valueforfilter = addslashes($filterparts[1]);
						$sql="SELECT id_req FROM " . $xoopsDB->prefix("formulize_form") . " WHERE id_req=" . $array['id_req'] . " AND id_form=" . $array['id_form'] . " AND (ele_caption = \"$capforfilter\" AND ele_value LIKE \"%$valueforfilter%\")";
						//print $sql;
						//print "<br>";
						$res1=$xoopsDB->query($sql);
						if($row = $xoopsDB->fetchRow($res1)) { // if something is found...
							//print "FILTER!<br>";
							$results[$array['id_req']]['id_req'] = $array['id_req']; // we lose the key after multisort below, so we need this info in the array as a value
							$results[$array['id_req']]['page'][] = $page_id;
							$results[$array['id_req']]['title'][] = getTitle($array['id_req'], $search_titles[$page_id][0]); // assume only one framework per page, therefore only one search title
						}
						//print "RESULTS: ";
						//print_r($results);
						//print "<br><br>";
					}
					//} // end duplicate check -- needed to avoid duplicate results in LTS contact list (one form appears twice in framework), but not helping in CHR.
				}
				if(count($results[$array['id_req']]['page'])==0) { // if this entry is not a match for any filters on any pages, then skip processing this result
					$skip = 1;
					$prev_id = $array['id_req'];
				}
			}

			//print "SKIP: $skip<br>";

			if($skip==0) { // do not process if the filter check failed
				// put in counting of individual term matches here	
				foreach($term_list as $aterm) {
					$haystack = strtolower($array['ele_value']);
					$haystack = stripslashes($haystack);
					$needle = strtolower($aterm);
					$hitcount = substr_count($haystack, $needle);
					/*print "COUNT RESULT:<br>";
					print "Term: $needle<br>";
					print "Value: $haystack<br>";
					print "Count: $hitcount<br><br>";*/
					$term_counts[$array['id_req']][$aterm] += $hitcount; // add hitcount to the counts for this term
				}
				$prev_id = $array['id_req'];
			}


	// DEBUG:
	/*print "Result " . $resid . ": ";
	print_r($array);
	print "<br>";
	$resid++;*/
		}
		
	}else {
		//exit("Main search query failed, most likely because of a problem with the underlying forms being searched, or the composition of the search terms.");
	}
	$foundForAnd = 0;
	$totalcount = 0;
	include_once XOOPS_ROOT_PATH . "/modules/pageworks/include/functions.php";
	$udt = getUserDateTime(); // logging added July 8 2005
	foreach($term_list as $aterm) {
		if($term_counts[$prev_id][$aterm] > 0) { 
			$foundForAnd++; 
			$totalcount += $term_counts[$prev_id][$aterm];
		}
		// log the use of these search terms... added July 8 2005
		$log_term = strtolower($aterm);
		writeLogEntry($log_term, $udt['uid'], $udt['date'], $udt['time']);
	}
	if($foundForAnd != count($term_list) AND $andor == "AND") { 
		unset($results[$prev_id]); 
	} else {
		$results[$prev_id]['count'] = $totalcount;
	}



	// need to sort results array by count.

	foreach($results as $id_req=>$data) {
		$sortCount[] = $data['count'];
	}
	array_multisort($sortCount, SORT_DESC, $results);

	// DEBUG
	//print_r($results);

	// parse hits for display...
	// need to pay attention to limit and offset after compiling complete results, in order to return only the correct results
	$indexer = 0;

	foreach($results as $result) {
		foreach($result['page'] as $id=>$page_id) {
			$title = $result['title'][$id];
			$title = stripslashes($title);
			if($title) { // exclude results with no title -- currently it is not possible to grab titles from linked forms.  ie: search office list and people list, get hits for office name in both because people are linked to office, but the office search title is searched for, not the people search title (since the hit did not occur in people)
     			
     			$id_req = $result['id_req'];

     			if($result['count']>1) {
     				$hitstring = "hits";
     			} else {
     				$hitstring = "hit";
     			}

     			// added July 7 2005: add leading zeros to the page id, used to support the sub entry features of the modified iMenu module
     			if($page_id<10) {
     				$page_id = "00" . $page_id;
     			} elseif($page_id<100) {
     				$page_id = "0" . $page_id;
     			}

     			$hits[$indexer]['image'] = "images/pw_search.gif";
     			$hits[$indexer]['link'] = "index.php?page=" . $page_id . "&id=" . $id_req;
     			$hits[$indexer]['title'] = strip_tags(html_entity_decode($title . " (" . $result['count'] . " " . $hitstring . ")"));
     			$indexer++;
			} // end of if title
		}
	}

	} // end of IF condition that limits searches to users who can view at least one page

	return $hits;

}


?>