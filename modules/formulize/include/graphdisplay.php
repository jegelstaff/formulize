<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2012 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions						 		 	 ##
##  Project: Formulize                                                       ##
###############################################################################

include_once XOOPS_ROOT_PATH . "/modules/formulize/libraries/pChart/class/pData.class.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/libraries/pChart/class/pDraw.class.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/libraries/pChart/class/pImage.class.php";

global $xoopsConfig;
// load the formulize language constants if they haven't been loaded already
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
} else {
    include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";
include_once XOOPS_ROOT_PATH . "/modules/formulize/class/usersGroupsPerms.php";
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

/**
 * IMPORTANT: Implemented User Cases:
 * 1. bar graph "count":
 * 		a. counting # of male and females in a class:
 * 			displayGraph("Bar", form_id_of_class, "", gender, gender, "count", $graphOptions);
 * 		b. counting # of mayors for a city
 * 			displayGraph("Bar", form_id_of_class, relation_id, city, mayor, "count", $graphOptions);
 * 2. bar graph "unique-count":
 * 		same as above, but with a slight twist:
 * 		a. in gender example, since there are only two distinct gender, then output would be bar with length 1 and 1
 * 		b. in mayors example, the mayors with same name would only be counted once
 * 3. bar graph "display":
 * 		a. display country with it's population
 * 			displayGraph("Bar", form_id_of_class, "", country, population, "display", $graphOptions);
 * 4. bar graph "sum":
 * 		a. display country with it's poplution
 * 			displayGraph("Bar", country_form, province_relation, country, province_population, "sum", $graphOptions);
 */
 
 
 /**
  * Instruction of the parameter "graphOptions"
  * "graphOptions" should be an array of key value pairs. Here is a list of valid keys:
  * 1. width:
  * 	Set the width of the bar graph in px.
  * 2. height:
  * 	Set the height of the bar graph in px.
  * 3. orientation:
  * 	Set the graph orientation. The value for this key should be either "horizontal" or "vertical".
  * 4. backgroundcolor:
  * 	Set the background color of the bar graph. The value for this key should be another array of key value pair, contain the three values of 
  * 	R, G and B. For example, "backgroundcolor" => array("R" => 0, "G" => 0 , "B" => 0) indicates setting the background color of the graph to black.
  * 5. barcolor:
  * 	Set the color for the bars. The value for this key should be another array of key value pair, contain the three values of 
  * 	R, G and B.
  * 
  */
 
/**
 * Main entrance of displayGraph API
 * @param $graphType type of the graph to be displayed with input data
 * @param $fid the id of the form where the data is coming from
 * @param $frid the id of the relation(relating fid's form to another form) $frid == fid if no relation is specified
 * @param $labelElement the field in the form to be used as label
 * @param $dataElement the field in the form to be used as data to graph
 * @param $operation the operation to be used to draw graphs
 * @param $graphOptions the graph parameters passed in by user!
 */
function displayGraph($graphType, $fid, $frid, $labelElement, $dataElement, $operation, $graphOptions) {
	switch ($graphType) {
		case "Bar" :
			displayBarGraph($fid, $frid, $labelElement, $dataElement, $operation, $graphOptions);
			break;
		default :
			echo "Sorry, the graph type \"$graphType\" is not supported at the moment!";
			break;
	}
}

/**
 * Helper method to draw bar graph
 * parameters have same meaning as displayGraph's parameters
 */
function displayBarGraph($fid, $frid, $labelElement, $dataElement, $operation, $graphOptions) {

    // Code from entriesdisplay to get the proper data based on selected views

    // Set up initial vars
    global $xoopsUser;

    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    $mid = getFormulizeModId();
    $gperm_handler =& xoops_gethandler('groupperm');
    $member_handler =& xoops_gethandler('member');
    $view_globalscope = $gperm_handler->checkRight("view_globalscope", $fid, $groups, $mid);
    $view_groupscope = $gperm_handler->checkRight("view_groupscope", $fid, $groups, $mid);
    $uid = $xoopsUser ? $xoopsUser->getVar('uid') : "0";
    $groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
    print $graphOptions['defaultview'];
    print $_POST['loadreport'];

    // One part of the loadReport system, the most pertinent one
    // Other branches such as legacy support is not included because the lack of way to test
    if(is_numeric($graphOptions['defaultview'])) { // saved or published view
        print "in here";
        print $graphOptions['defaultview'];
        // As "p" is removed in defaultview and needed by formulize_gatherDataSet
        $settings['loadedview'] = "p" . $graphOptions['defaultview'];

        // kill the quicksearches??
        // Not quite sure what this does probably deletes all the search terms
        // put in entriesdisplay textfield if empty to be replaced from db
        foreach($_POST as $k=>$v) {
            if(substr($k, 0, 7) == "search_" AND $v != "") {
                unset($_POST[$k]);
            }
        }
        // Not all vars are needed because it's based on entriesdisplay need
        // Right now the needed ones are:
        // currentview, asearch, sort, order
        list($_POST['currentview'], $_POST['oldcols'], $_POST['asearch'], $_POST['calc_cols'], $_POST['calc_calcs'], $_POST['calc_blanks'], $_POST['calc_grouping'], $_POST['sort'], $_POST['order'], $_POST['hlist'], $_POST['hcalc'], $_POST['lockcontrols'], $quicksearches) = loadReport($graphOptions['defaultview'], $fid, $frid);
    }

    if ($_POST['currentview'] == "") {
        // when loadReport isn't run (mine, group, all views)
        $currentView = $graphOptions['defaultview'];
    } else {
        $currentView = $_POST['currentview'];
    }

    // Set up the advanced search based on the text filter inputted in entriesdisplay
    // right now there is no filter field here
    $settings['asearch'] = $_POST['asearch'];
    if($_POST['asearch']) {
        $as_array = explode("/,%^&2", $_POST['asearch']);
        foreach($as_array as $k=>$one_as) {
            $settings['as_' . $k] = $one_as;
        }
    }

    //get all submitted search text
    foreach($_POST as $k=>$v) {
        if(substr($k, 0, 7) == "search_" AND $v != "") {
            $thiscol = substr($k, 7);
            $searches[$thiscol] = $v;
            $temp_key = "search_" . $thiscol;
            $settings[$temp_key] = $v;
        }
    }

    list($scope, $currentView) = buildScope($currentView, $member_handler, $gperm_handler, $uid, $groups, $fid, $mid, true);
    $dbData = formulize_gatherDataSet($settings, $searches, strip_tags($_POST['sort']), strip_tags($_POST['order']), $frid, $fid, $scope, intval($_POST['forcequery']));

    // End of code from entriesdisplay

	foreach ($dbData as $entry) {
		// mayor - OR array of mayors if there's more than one in the dataset, depending on the one-to-may in a relationship
		$dataRawValue = display($entry, $dataElement);
		// city_name;
		$labelRawValue = display($entry, $labelElement);
		if (!is_array($dataRawValue) && $dataRawValue) {
			$dataRawValue = array($dataRawValue);
		}
		if (!is_array($labelRawValue)) {
			$labelRawValue = array($labelRawValue);
		}
		foreach ($labelRawValue as $thisLabelValue) {
			if ($dataPoints[$thisLabelValue]) {
				$dataPoints[$thisLabelValue] = array_merge($dataPoints[$thisLabelValue], $dataRawValue);
			} else {
				$dataPoints[$thisLabelValue] = $dataRawValue;
			}
		}
	}

	// Oct 29 Update for column heading for graphs:
	$elementHandler = xoops_getmodulehandler('elements', 'formulize');
	$elementObject = $elementHandler->get($labelElement);
	$labelElement = $elementObject->getVar('ele_colhead') ? $elementObject->getVar('ele_colhead') : printSmart($elementObject->getVar('ele_caption'));
	$elementObject = $elementHandler->get($dataElement);
	$dataElement = $elementObject->getVar('ele_colhead') ? $elementObject->getVar('ele_colhead') : printSmart($elementObject->getVar('ele_caption'));
	// end of Update
	switch($operation) {
		case "count" :
			// count the values in each label of the array
			foreach(array_keys($dataPoints) as $key){
				if(!empty($dataPoints[$key])){
					$dataPoints[$key] = count($dataPoints[$key]);
				} else {
					$dataPoints[$key] = 0;
				}
			}
			if($labelElement == $dataElement){
				$dataElement = "count of " . $labelElement;
			} else {
				$dataElement = "count of " . $dataElement;
			}
			break;
		case "sum" :
		case "display":
			// TODO: Check this!
			foreach ($dataPoints as $thisLabel => $theseValues) {
				$dataPoints[$thisLabel] = array_sum($theseValues);
			}
			$dataElement = (($operation == "display") ? "number of " : "sum of ") . $dataElement;
			break;
		case "count-unique" :
			foreach ($dataPoints as $thisLabel => $theseValues) {
				$dataPoints[$thisLabel] = count(array_unique($theseValues));
			}
			if($dataElement == $labelElement){
				$dataElement = "count of unique " . $labelElement;
			} else {
				$dataElement = "count of unique " . $dataElement;
			}
			break;
		default :
			echo "Sorry, the operation \"$operation\" for Bar graph is not supported at the moment!";
			return;
	}
	
	// print("dataElement: ".$dataElement." ");
	// print("labelElement: ".$labelElement." ");

	
	// process the graph options
	// these defaults will be used, unless overwritten by values from the $graphOptions array
	$sizeMultiplier = sizeof(array_keys($dataPoints));
	$BAR_THICKNESS = 40;
	$IMAGE_WIDTH = 600;
	$IMAGE_DEFAULT_WIDTH = $IMAGE_WIDTH;
	
	if( $sizeMultiplier > 1){
		$IMAGE_HEIGHT = $BAR_THICKNESS * $sizeMultiplier/0.5;
	}else{
		$IMAGE_HEIGHT = $BAR_THICKNESS * 4;	
	}
	
	$IMAGE_DEFAULT_HEIGHT = $IMAGE_HEIGHT;
	$IMAGE_ORIENTATION = "vertical";
	$BACKGROUND_R = 141;
	$BACKGROUND_G = 189;
	$BACKGROUND_B = 225;
	$BARCOLOR_R = 143;
	$BARCOLOR_G = 190;
	$BARCOLOR_B = 88;

    unset($graphOptions['defaultview']);

	if (sizeof($graphOptions) > 0) {
		foreach ($graphOptions as $graphoption => $value) {

			switch($graphoption) {
				case "width" :
					$IMAGE_WIDTH = $value;
					break;
				case "height" :
					$IMAGE_HEIGHT = $value;
					break;
				case "orientation" :
					$IMAGE_ORIENTATION = $value;
					if($IMAGE_ORIENTATION == "horizontal"){
						if($IMAGE_HEIGHT == $IMAGE_DEFAULT_HEIGHT ){
							$IMAGE_HEIGHT = 500;
						}else{
							if($IMAGE_WIDTH == $IMAGE_DEFAULT_WIDTH){
								$IMAGE_WIDTH = $BAR_THICKNESS * $sizeMultiplier / 0.5;
							}
						}
					}
					break;
				case "backgroundcolor" :
					// print_r($value);
					foreach ($value as $RGB => $colorvalue) {
						switch($RGB) {
							case "R" :
								$BACKGROUND_R = $colorvalue;
								break;
							case "G" :
								$BACKGROUND_G = $colorvalue;
								break;
							case "B" :
								$BACKGROUND_B = $colorvalue;
								break;
							default :
								echo "Please follow the correct format of backgroundcolor.";
								break;
						}
					}

					break;
				case "barcolor" :
					// print_r($value);
					foreach ($value as $RGB => $colorvalue) {
						switch($RGB) {
							case "R" :
								$BARCOLOR_R = $colorvalue;
								break;
							case "G" :
								$BARCOLOR_G = $colorvalue;
								break;
							case "B" :
								$BARCOLOR_B = $colorvalue;
								break;
							default :
								echo "Please follow the correct format of backgroundcolor.";
								break;
						}
					}

					break;
				default :
					echo "Sorry, the graph option \"$graphoption\" for Bar graph is not supported at the moment!<br>";
					break;
			}
		}
	}
	
	// reset width/height of the image in case the label is too long
	if( (strlen($labelElement)*4.5 >= $IMAGE_HEIGHT) AND $IMAGE_ORIENTATION == "vertical"){
		if( $IMAGE_HEIGHT == $IMAGE_DEFAULT_HEIGHT ){
			$IMAGE_HEIGHT = strlen($labelElement)*5;
		}else{
			$labelElement = substr($labelElement, 0, $IMAGE_HEIGHT/4.5-3)."...";
		}
	}elseif((strlen($dataElement)*4.5 >= $IMAGE_HEIGHT) AND $IMAGE_ORIENTATION == "horizontal"){
		if( $IMAGE_HEIGHT == $IMAGE_DEFAULT_HEIGHT ){
			$IMAGE_HEIGHT = strlen($dataElement)*5;
		}else{
			$dataElement = substr($dataElement, 0, $IMAGE_HEIGHT/4.5-3)."...";
		}
	}elseif((strlen($labelElement)*4.5 >= $IMAGE_WIDTH) AND $IMAGE_ORIENTATION == "horizontal"){
		if( $IMAGE_WIDTH == $IMAGE_DEFAULT_WIDTH){
			$IMAGE_WIDTH = strlen($labelElement)*5;
		}else{
			$labelElement = substr($labelElement, 0, $IMAGE_HEIGHT/4.5-3)."...";
		}
	}elseif((strlen($dataElement)*4.5 >= $IMAGE_WIDTH) AND $IMAGE_ORIENTATION == "vertical"){
		if( $IMAGE_WIDTH == $IMAGE_DEFAULT_WIDTH){
			$IMAGE_WIDTH = strlen($dataElement)*5;
		}else{
			$dataElement = substr($dataElement, 0, $IMAGE_HEIGHT/4.5-3)."...";
		}
	}




	// Code straightly copied from pChart documentation to draw the graph
	$myData = new pData();
	$myData -> addPoints(array_values($dataPoints), $dataElement);
	$myData -> setAxisName(0, $dataElement);
	$myData -> addPoints(array_keys($dataPoints), $labelElement);
	$myData -> setSerieDescription($labelElement, $labelElement);
	$myData -> setAbscissa($labelElement);
	$myData -> setAbscissaName($labelElement);
	// $myData -> setAxisDisplay(0, AXIS_FORMAT_CUSTOM, "YAxisFormat");

	/* Create the pChart object */
	$myPicture = new pImage($IMAGE_WIDTH, $IMAGE_HEIGHT, $myData);
	$myPicture -> drawGradientArea(0, 0, $IMAGE_WIDTH, $IMAGE_HEIGHT, DIRECTION_VERTICAL, array("StartR" => $BACKGROUND_R, "StartG" => $BACKGROUND_G, "StartB" => $BACKGROUND_B, "EndR" => $BACKGROUND_R, "EndG" => $BACKGROUND_G, "EndB" => $BACKGROUND_B, "Alpha" => 100));
	$myPicture->drawGradientArea(0,0,500,500,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>30));
	$myPicture -> setFontProperties(array("FontName" => XOOPS_ROOT_PATH . "/modules/formulize/libraries/pChart/fonts/arial.ttf", "FontSize" => 8));

	$paddingtoLeft = $IMAGE_WIDTH * 0.15;
	$paddingtoTop = $IMAGE_HEIGHT * 0.2;
	if( $paddingtoTop > 50){
		$paddingtoTop = 50;
	}

	/* Draw the chart scale */
	$myPicture -> setGraphArea($paddingtoLeft, $paddingtoTop, $IMAGE_WIDTH * 0.90, $IMAGE_HEIGHT * 0.88);

	if($IMAGE_ORIENTATION == "vertical"){
		$myPicture -> drawScale(array("CycleBackground" => TRUE, "DrawSubTicks" => TRUE, "GridR" => 0, "GridG" => 0, "GridB" => 0, "GridAlpha" => 10, "Pos" => SCALE_POS_TOPBOTTOM, "Mode" => SCALE_MODE_ADDALL_START0, "Decimal" => 0, "MinDivHeight" => 50));
	}else{
		$myPicture -> drawScale(array("CycleBackground" => TRUE, "DrawSubTicks" => TRUE, "GridR" => 0, "GridG" => 0, "GridB" => 0, "GridAlpha" => 10, "Mode" => SCALE_MODE_ADDALL_START0, "Decimal" => 0, "MinDivHeight" => 50));
	}

	/* Turn on shadow computing */
	$myPicture -> setShadow(TRUE, array("X" => 1, "Y" => 1, "R" => 0, "G" => 0, "B" => 0, "Alpha" => 10));

	$Palette = array("0"=>array("R"=>$BARCOLOR_R,"G"=>$BARCOLOR_G,"B"=>$BARCOLOR_B,"Alpha"=>100));

	for($i = 1 ; $i < $sizeMultiplier ; $i++){
		$Palette[$i] = array("R"=>$BARCOLOR_R,"G"=>$BARCOLOR_G,"B"=>$BARCOLOR_B,"Alpha"=>100);
	}

	// print_r($Palette);

	$myPicture->drawBarChart(array("OverrideColors"=>$Palette));

	/* Draw the chart */
	$myPicture -> drawBarChart(array("DisplayPos" => LABEL_POS_INSIDE, "DisplayValues" => TRUE, "Rounded" => TRUE, "Surrounding" => 30, "OverrideColors"=>$Palette));
	renderGraph($myPicture, $fid, $frid, $labelElement, $dataElement, $operation, $graphOptions, $dataPoints);
	return;
}

function YAxisFormat($Value) {
	if (round($Value) == $Value) {
		return round($Value);
	} else {
		return "";
	}
	// return $Value;
}

/**
 * Save the graph to the local file system and render the graph
 */
function renderGraph($myPicture, $fid, $frid, $labelElement, $dataElement, $operation, $graphOptions, $dataPoints) {
	// TODO: make some kind of cron job clear up or some kind of caches, update graph only when needed!
	$graphRelativePathPrefix = "modules/formulize/images/graphs/";
    // Uses md5 hash of the data points and graph options to shorten filename and handle non alphanumeric chars
	$graphRelativePath = $graphRelativePathPrefix . md5(SDATA_DB_SALT.var_export($dataPoints, true).var_export($graphOptions, true)).".png";
	$myPicture -> render(XOOPS_ROOT_PATH . "/" . $graphRelativePath);
	echo "<img src='" . XOOPS_URL . "/$graphRelativePath' />";
	return;
}

function initializeZeros($keys) {
	foreach ($keys as $key) {
		$rtn[$key] = 0;
	}
	return $rtn;
}

// This function is for testing purpose only
function echoBR() {
	echo "<br \>";
}

// Functions from entriesdisplay, trimmed to useful bits

function formulize_gatherDataSet($settings=array(), $searches, $sort="", $order="", $frid, $fid, $scope, $forcequery = 0) {
    if (!is_array($searches))
        $searches = array();

    // Order of operations for the requested advanced search options
    // 1. unpack the settings necessary for the search
    // 2. loop through the data and store the results, unsetting $data as we go, and then reassigning the found array to $data at the end

    // example of as $settings:
    /*	global $xoopsUser;
        if($xoopsUser->getVar('uid') == 'j') {
        $settings['as_1'] = "[field]545[/field]";
        $settings['as_2'] = "==";
        $settings['as_3'] = "Ontario";
        $settings['as_4'] = "AND";
        $settings['as_5'] = "(";
        $settings['as_6'] = "[field]557[/field]";
        $settings['as_7'] = "==";
        $settings['as_8'] = "visiting classrooms";
        $settings['as_9'] = "OR";
        $settings['as_10'] = "[field]557[/field]";
        $settings['as_11'] = "==";
        $settings['as_12'] = "advocacy";
        $settings['as_13'] = ")";
        } // end of xoopsuser check
    */
//	545 prov
//	556 are you still interested, yes/no
//	570 where vol with LTS (university name)
//	557 which of following areas... (multi)

    $query_string = "";
    if($settings['as_0']) {
        // build the query string
        // string looks like this:
        //if([query here]) {
        //	$query_result = 1;
        //}

        $query_string .= "if(";
        $firstTermNot = false;
        for($i=0;$settings['as_' . $i];$i++) {
            // save query for writing later
            $wq['as_' . $i] = $settings['as_' . $i];
            if(substr($settings['as_' . $i], 0, 7) == "[field]" AND substr($settings['as_' . $i], -8) == "[/field]") { // a field has been found, next two should be part of the query
                $fieldLen = strlen($settings['as_' . $i]);
                $field = substr($settings['as_' . $i], 7, $fieldLen-15); // 15 is the length of [field][/field]
                $field = calcHandle($field, $fid);
                $query_string .= "evalAdvSearch(\$entry, \"$field\", \"";
                $i++;
                $wq['as_' . $i] = $settings['as_' . $i];
                $query_string .= $settings['as_' . $i] . "\", \"";
                $i++;
                $wq['as_' . $i] = $settings['as_' . $i];
                $query_string .= $settings['as_' . $i] . "\")";
            } else {
                if($i==0 AND $settings['as_'.$i] == "NOT") {
                    $firstTermNot = true; // must flag initial negations and handle differently
                    continue;
                }
                if($firstTermNot == true AND $i==1 AND $settings['as_'.$i] != "(") {
                    $firstTermNot = false; // only actually preserve the full negation if the second term is a parenthesis
                    $query_string .= " NOT ";
                }
                $query_string .= " " . $settings['as_' . $i] . " ";
            }
        }

        if($firstTermNot) { // if we are looking for the negative of the entire query...
            $query_string .= ") { \$query_result=0; } else { \$query_result=1; }";
        } else {
            $query_string .= ") { \$query_result=1; } else { \$query_result=0; }";
        }
    }
    // build the filter out of the searches array
    $start = 1;
    $filter = "";
    $ORstart = 1;
    $ORfilter = "";
    $individualORSearches = array();
    global $xoopsUser;
    foreach($searches as $key=>$master_one_search) { // $key is handles for frameworks, and ele_handles for non-frameworks.

        // convert "between 2001-01-01 and 2002-02-02" to a normal date filter with two dates
        $count = preg_match("/^[bB][eE][tT][wW][eE][eE][nN] ([\d]{1,4}[-][\d]{1,2}[-][\d]{1,4}) [aA][nN][dD] ([\d]{1,4}[-][\d]{1,2}[-][\d]{1,4})\$/", $master_one_search, $matches);
        if ($count > 0) {
            $master_one_search = ">={$matches[1]}//<={$matches[2]}";
        }

        // split search based on new split string
        $searchArray = explode("//", $master_one_search);

        foreach($searchArray as $one_search) {

            $addToItsOwnORFilter = false; // used for trapping the {BLANK} keywords into their own space so they don't interfere with each other, or other filters

            // remove the qsf_ parts to make the quickfilter searches work
            if(substr($one_search, 0, 4)=="qsf_") {
                $qsfparts = explode("_", $one_search);
                // need to determine if the key is a multi selection element or not.  If it is, then this should not be a straight equals!
                $one_search = "=".$qsfparts[2];
            }

            // strip out any starting and ending ! that indicate that the column should not be stripped
            if(substr($one_search, 0, 1) == "!" AND substr($one_search, -1) == "!") {
                $one_search = substr($one_search, 1, -1);
            }

            // look for OR indicators...if all caps OR is at the front, then that means that this search is to put put into a separate set of OR filters that gets appended as a set to the main set of AND filters
            $addToORFilter = false; // flag to indicate if we need to apply the current search term to a set of "OR'd" terms
            if(substr($one_search, 0, 2) == "OR" AND strlen($one_search) > 2) {
                $addToORFilter = true;
                $one_search = substr($one_search, 2);
            }


            // look for operators
            $operators = array(0=>"=", 1=>">", 2=>"<", 3=>"!");
            $operator = "";
            if(in_array(substr($one_search, 0, 1), $operators)) {
                // operator found, check to see if it's <= or >= and set start point for term accordingly
                $startpoint = (substr($one_search, 0, 2) == ">=" OR substr($one_search, 0, 2) == "<=" OR substr($one_search, 0, 2) == "!=" OR substr($one_search, 0, 2) == "<>") ? 2 : 1;
                $operator = substr($one_search, 0, $startpoint);
                if($operator == "!") { $operator = "NOT LIKE"; }
                $one_search = substr($one_search, $startpoint);

            }

            // look for blank search terms and convert them to {BLANK} so they are handled properly
            if($one_search === "") {
                $one_search = "{BLANK}";
            }

            // look for { } and transform special terms into what they should be for the filter
            if(substr($one_search, 0, 1) == "{" AND substr($one_search, -1) == "}") {
                $searchgetkey = substr($one_search, 1, -1);

                if (ereg_replace("[^A-Z]","", $searchgetkey) == "TODAY") {
                    $number = ereg_replace("[^0-9+-]","", $searchgetkey);
                    $one_search = date("Y-m-d",mktime(0, 0, 0, date("m") , date("d")+$number, date("Y")));
                } elseif($searchgetkey == "USER") {
                    if($xoopsUser) {
                        $one_search = $xoopsUser->getVar('name');
                        if(!$one_search) { $one_search = $xoopsUser->getVar('uname'); }
                    } else {
                        $one_search = 0;
                    }
                } elseif($searchgetkey == "USERNAME") {
                    if($xoopsUser) {
                        $one_search = $xoopsUser->getVar('uname');
                    } else {
                        $one_search = "";
                    }
                } elseif($searchgetkey == "BLANK") { // special case, we need to construct a special OR here that will look for "" OR IS NULL
                    if($operator == "!=" OR $operator == "NOT LIKE") {
                        $blankOp1 = "!=";
                        $blankOp2 = " IS NOT NULL ";
                    } else {
                        $addToItsOwnORFilter = $addToORFilter ? false : true; // if this is not going into an OR filter already because the user asked for it to, then let's
                        $blankOp1 = "=";
                        $blankOp2 = " IS NULL ";
                    }
                    $one_search = "/**/$blankOp1][$key/**//**/$blankOp2";
                    $operator = ""; // don't use an operator, we've specially constructed the one_search string to have all the info we need
                } elseif($searchgetkey == "PERGROUPFILTER") {
                    $one_search = $searchgetkey;
                    $operator = "";
                } elseif(isset($_POST[$searchgetkey]) OR isset($_GET[$searchgetkey])) {
                    $one_search = $_POST[$searchgetkey] ? htmlspecialchars(strip_tags($_POST[$searchgetkey])) : "";
                    $one_search = (!$one_search AND $_GET[$searchgetkey]) ? htmlspecialchars(strip_tags($_GET[$searchgetkey])) : $one_search;
                    if(!$one_search) {
                        continue;
                    }
                } elseif($searchgetkey) { // we were supposed to find something above, but did not, so there is a user defined search term, which has no value, ergo disregard this search term
                    continue;
                } else {
                    $one_search = "";
                    $operator = "";
                }
            }

            // do additional search for {USERNAME} or {USER} in case they are embedded in another string
            if($xoopsUser) {
                $one_search = str_replace("{USER}", $xoopsUser->getVar('name'), $one_search);
                $one_search = str_replace("{USERNAME}", $xoopsUser->getVar('uname'), $one_search);
            }


            if($operator) {
                $one_search = $one_search . "/**/" . $operator;
            }
            if($addToItsOwnORFilter) {
                $individualORSearches[] = $key ."/**/$one_search";
            } elseif($addToORFilter) {
                if(!$ORstart) { $ORfilter .= "]["; }
                $ORfilter .= $key . "/**/$one_search"; // . formulize_escape($one_search); // mysql_real_escape_string no longer necessary here since the extraction layer does the necessary dirty work for us
                $ORstart = 0;
            } else {
                if(!$start) { $filter .= "]["; }
                $filter .= $key . "/**/$one_search"; // . formulize_escape($one_search); // mysql_real_escape_string no longer necessary here since the extraction layer does the necessary dirty work for us
                $start = 0;
            }

        }
    }
    //print $filter;
    // if there's a set of options that have been OR'd, then we need to construction a more complex filter

    if($ORfilter OR count($individualORSearches)>0) {
        $filterIndex = 0;
        $arrayFilter[$filterIndex][0] = "and";
        $arrayFilter[$filterIndex][1] = $filter;
        if($ORfilter) {
            $filterIndex++;
            $arrayFilter[$filterIndex][0] = "or";
            $arrayFilter[$filterIndex][1] = $ORfilter;
        }
        if(count($individualORSearches)>0) {
            foreach($individualORSearches as $thisORfilter) {
                $filterIndex++;
                $arrayFilter[$filterIndex][0] = "or";
                $arrayFilter[$filterIndex][1] = $thisORfilter;
            }
        }
        $filter = $arrayFilter;
        $filterToCompare = serialize($filter);
    } else {
        $filterToCompare = $filter;
    }

    $data = getData($frid, $fid, $filter, "AND", $scope, 0, 0, $sort, $order, $forcequery);
    if($query_string AND is_array($data)) { $data = formulize_runAdvancedSearch($query_string, $data); } // must do advanced search after caching the data, so the advanced search results are not contained in the cached data.  Otherwise, we would have to rerun the base extraction every time we wanted to change just the advanced search query.  This way, advanced search changes can just hit the cache, and not the db.
    return $data;
}

// THIS FUNCTION LOADS A SAVED VIEW
// fid and frid are only used if a report is being asked for by name
function loadReport($id, $fid, $frid) {
    global $xoopsDB;
    if(is_numeric($id)) {
        $thisview = q("SELECT * FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_id='$id'");
    } else {
        if($frid) {
            $formframe = intval($frid);
            $mainform = intval($fid);
        } else {
            $formframe = intval($fid);
            $mainform = "''";
        }
        $thisview = q("SELECT * FROM " . $xoopsDB->prefix("formulize_saved_views") . " WHERE sv_name='".formulize_escape($id)."' AND sv_formframe = $formframe AND sv_mainform = $mainform");
    }
    if(!isset($thisview[0]['sv_currentview'])) {
        print "Error: could not load the specified saved view: '".strip_tags(htmlspecialchars($id))."'";
        return false;
    }
    $to_return[0] = $thisview[0]['sv_currentview'];
    $to_return[1] = $thisview[0]['sv_oldcols'];
    $to_return[2] = $thisview[0]['sv_asearch'];
    $to_return[3] = $thisview[0]['sv_calc_cols'];
    $to_return[4] = $thisview[0]['sv_calc_calcs'];
    $to_return[5] = $thisview[0]['sv_calc_blanks'];
    $to_return[6] = $thisview[0]['sv_calc_grouping'];
    $to_return[7] = $thisview[0]['sv_sort'];
    $to_return[8] = $thisview[0]['sv_order'];
    $to_return[9] = $thisview[0]['sv_hidelist'];
    $to_return[10] = $thisview[0]['sv_hidecalc'];
    $to_return[11] = $thisview[0]['sv_lockcontrols'];
    $to_return[12] = $thisview[0]['sv_quicksearches'];
    return $to_return;
}

?>