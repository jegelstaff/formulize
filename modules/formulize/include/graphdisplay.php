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

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

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
	// getting data from DB
	if (is_int($frid) && $frid > 0) {
		$dbData = getData($frid, $fid);
	} else {
		$dbData = getData("", $fid);
	}

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
	renderGraph($myPicture, $fid, $frid, $labelElement, $dataElement, $operation, $graphOptions);
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
function renderGraph($myPicture, $fid, $frid, $labelElement, $dataElement, $operation, $graphOptions) {	// establish text and code for buttons, whether a screen is in effect or not
	$screenButtonText = array();
	$screenButtonText['modifyScreenLink'] = ($edit_form AND $screen) ? _formulize_DE_MODIFYSCREEN : "";
	$screenButtonText['changeColsButton'] = _formulize_DE_CHANGECOLS;
	$screenButtonText['calcButton'] = _formulize_DE_CALCS;
	$screenButtonText['advCalcButton'] = _formulize_DE_ADVCALCS;
	$screenButtonText['advSearchButton'] = _formulize_DE_ADVSEARCH;
	$screenButtonText['exportButton'] = _formulize_DE_EXPORT;
	$screenButtonText['exportCalcsButton'] = _formulize_DE_EXPORT_CALCS;
	$screenButtonText['importButton'] = _formulize_DE_IMPORTDATA;
	$screenButtonText['notifButton'] = _formulize_DE_NOTBUTTON;
	$screenButtonText['cloneButton'] = _formulize_DE_CLONESEL;
	$screenButtonText['deleteButton'] = _formulize_DE_DELETESEL;
	$screenButtonText['selectAllButton'] = _formulize_DE_SELALL;
	$screenButtonText['clearSelectButton'] = _formulize_DE_CLEARALL;
	$screenButtonText['resetViewButton'] = _formulize_DE_RESETVIEW;
	$screenButtonText['saveViewButton'] = _formulize_DE_SAVE;
	$screenButtonText['deleteViewButton'] = _formulize_DE_DELETE;
	$screenButtonText['currentViewList'] = _formulize_DE_CURRENT_VIEW;
	$screenButtonText['saveButton'] = _formulize_SAVE;
	$screenButtonText['addButton'] = $singleMulti[0]['singleentry'] == "" ? _formulize_DE_ADDENTRY : _formulize_DE_UPDATEENTRY;
	$screenButtonText['addMultiButton'] = _formulize_DE_ADD_MULTIPLE_ENTRY;
	$screenButtonText['addProxyButton'] = _formulize_DE_PROXYENTRY;
	if($screen) {
		if($add_own_entry) {
			$screenButtonText['addButton'] = $screen->getVar('useaddupdate');
			$screenButtonText['addMultiButton'] = $screen->getVar('useaddmultiple');
		} else {
			$screenButtonText['addButton'] = "";
			$screenButtonText['addMultiButton'] = "";
		}
		if($proxy) {
			$screenButtonText['addProxyButton'] = $screen->getVar('useaddproxy');
		} else {
			$screenButtonText['addProxyButton'] = "";
		}
		$screenButtonText['exportButton'] = $screen->getVar('useexport');
		$screenButtonText['importButton'] = $screen->getVar('useimport');
		$screenButtonText['notifButton'] = $screen->getVar('usenotifications');
		$screenButtonText['currentViewList'] = $screen->getVar('usecurrentviewlist');
		$screenButtonText['saveButton'] = $screen->getVar('desavetext');
		$screenButtonText['changeColsButton'] = $screen->getVar('usechangecols');
		$screenButtonText['calcButton'] = $screen->getVar('usecalcs');
		$screenButtonText['advCalcButton'] = $screen->getVar('useadvcalcs');
		$screenButtonText['advSearchButton'] = $screen->getVar('useadvsearch');
		$screenButtonText['exportCalcsButton'] = $screen->getVar('useexportcalcs');
		// only include clone and delete if the checkboxes are in effect (2 means do not use checkboxes)
		if($screen->getVar('usecheckboxes') != 2) {
			$screenButtonText['cloneButton'] = $screen->getVar('useclone');
            if ($user_can_delete and !$settings['lockcontrols']) {
				$screenButtonText['deleteButton'] = $screen->getVar('usedelete');
			} else {
				$screenButtonText['deleteButton'] = "";
			}
			$screenButtonText['selectAllButton'] = $screen->getVar('useselectall');
			$screenButtonText['clearSelectButton'] = $screen->getVar('useclearall');
		} else {
			$screenButtonText['cloneButton'] = "";
			$screenButtonText['deleteButton'] = "";
			$screenButtonText['selectAllButton'] = "";
			$screenButtonText['clearSelectButton'] = "";
		}
		// only include the reset, save, deleteview buttons if the current view list is in effect
		if($screen->getVar('usecurrentviewlist')) {
			$screenButtonText['resetViewButton'] = $screen->getVar('usereset');
			$screenButtonText['saveViewButton'] = $screen->getVar('usesave');
			$screenButtonText['deleteViewButton'] = $screen->getVar('usedeleteview');
		} else {
			$screenButtonText['resetViewButton'] = "";
			$screenButtonText['saveViewButton'] = "";
			$screenButtonText['deleteViewButton'] = "";
		}
	} 
	if($delete_other_reports = $gperm_handler->checkRight("delete_other_reports", $fid, $groups, $mid)) { $pubstart = 10000; }
	if($screenButtonText['saveButton']) { $screenButtonText['goButton'] = $screenButtonText['saveButton']; } // want this button accessible by two names, essentially, since it serves two purposes semantically/logically
	$onActionButtonCounter = 0;
	$atLeastOneActionButton = false;
	foreach($screenButtonText as $scrButton=>$scrText) {
    formulize_benchmark("before creating button: ".$scrButton);
		$buttonCodeArray[$scrButton] = formulize_screenLOEButton($scrButton, $scrText, $settings, $fid, $frid, $colhandles, $flatcols, $pubstart, $loadOnlyView, $calc_cols, $calc_calcs, $calc_blanks, $calc_grouping, $singleMulti[0]['singleentry'], $lastloaded, $currentview, $endstandard, $pickgroups, $viewoptions, $loadviewname, $advcalc_acid, $screen);
    formulize_graphScreenLOEButton($scrButton, $scrText, $fid, $frid, $flatcols, $pubstart, $loadOnlyView, $doNotForceSingle, $lastloaded, $currentview, $endstandard, $pickgroups, $viewoptions, $loadviewname, $screen)
    formulize_benchmark("button done");
		if($buttonCodeArray[$scrButton] AND $onActionButtonCounter < 14) { // first 14 items in the array should be the action buttons only
			$atLeastOneActionButton = true;
		}
		$onActionButtonCounter++;
	}

	// TODO: make some kind of cron job clear up or some kind of caches, update graph only when needed!
	$graphRelativePathPrefix = "modules/formulize/images/graphs/";
    // Uses md5 hash of the data points and graph options to shorten filename and handle non alphanumeric chars
	$graphRelativePath = $graphRelativePathPrefix . md5(SDATA_DB_SALT.var_export($dataPoints, true).var_export($graphOptions, true)).".png";
	$myPicture -> render(XOOPS_ROOT_PATH . "/" . $graphRelativePath);
	echo "<img src='" . XOOPS_URL . "/$graphRelativePath' />";
  
  
	$useXhr = false;
	if($screen) {
		if($screen->getVar('dedisplay')) {
			$useXhr = true;
		}
	}

  
  interfaceJavascript($fid, $frid, $currentview, $useWorking, $useXhr, $settings['lockedColumns']); // must be called after form is drawn, so that the javascript which clears ventry can operate correctly (clearing is necessary to avoid displaying the form after clicking the Back button on the form and then clicking a button or doing an operation that causes a posting of the controls form).
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

// THIS FUNCTION GENERATES HTML FOR ANY BUTTONS THAT ARE REQUESTED
function formulize_graphScreenLOEButton($button, $buttonText, $fid, $frid, $flatcols, $pubstart, $loadOnlyView, $doNotForceSingle, $lastloaded, $currentview, $endstandard, $pickgroups, $viewoptions, $loadviewname, $screen) {
  static $importExportCleanupDone = false;
	if($buttonText) {
		$buttonText = trans($buttonText);
		switch ($button) {
			case "modifyScreenLink":
				return "<a href=" . XOOPS_URL . "/modules/formulize/admin/ui.php?page=screen&sid=".$screen->getVar('sid').">" . $buttonText . "</a>";
				break;
			case "addButton":
				$addNewParam = $doNotForceSingle ? "" : "'single'"; // force the addNew behaviour to single entry unless this button is being used on a single entry form, in which case we don't need to force anything
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=addentry value='" . $buttonText . "' onclick=\"javascript:addNew($addNewParam);\"></input>";
				break;
			case "addMultiButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=addentry value='" . $buttonText . "' onclick=\"javascript:addNew();\"></input>";
				break;
			case "resetViewButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=resetviewbutton value='" . $buttonText . "' onclick=\"javascript:showLoadingReset();\"></input>";
				break;
			case "saveViewButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=save value='" . $buttonText . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/save.php?fid=$fid&frid=$frid&lastloaded=$lastloaded&cols=$flatcols&currentview=$currentview&loadonlyview=$loadOnlyView');\"></input>";
				break;
			case "deleteViewButton":
				return "<input type=button class=\"formulize_button\" id=\"formulize_$button\" name=delete value='" . $buttonText . "' onclick=\"javascript:delete_view(this.form, '$pubstart', '$endstandard');\"></input>";
				break;
			case "currentViewList":
				$currentViewList = "<b>" . $buttonText . "</b><br><SELECT style=\"width: 350px;\" name=currentview id=currentview size=1 onchange=\"javascript:change_view(this.form, '$pickgroups', '$endstandard');\">\n";
				$currentViewList .= $viewoptions;
				$currentViewList .= "\n</SELECT>\n";
				if(!$loadviewname AND strstr($currentview, ",") AND !$loadOnlyView) { // if we're on a genuine pick-groups view (not a loaded view)...and the load-only-view override is not in place (which eliminates other viewing options besides the loaded view)
					$currentViewList .= "<br><input type=button name=pickdiffgroup value='" . _formulize_DE_PICKDIFFGROUP . "' onclick=\"javascript:showPop('" . XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview');\"></input>";		
				}
				return $currentViewList;
				break;
		}
	} elseif($button == "currentViewList") { // must always set a currentview value in POST even if the list is not visible
		return "<input type=hidden name=currentview value='$currentview'></input>\n";
	} else {
		return false;
	}
}



// this function includes the javascript necessary make the interface operate properly
// note the mandatory clearing of the ventry value upon loading of the page.  Necessary to make the back button work right (otherwise ventry setting is retained from the previous loading of the page and the form is displayed after the next submission of the controls form)
function interfaceJavascript($fid, $frid, $currentview, $useWorking, $useXhr, $lockedColumns) {
?>
<script type='text/javascript'>

if (typeof jQuery == 'undefined') { 
	var head = document.getElementsByTagName('head')[0];
	script = document.createElement('script');
	script.id = 'jQuery';
	script.type = 'text/javascript';
	script.src = '<?php print XOOPS_URL; ?>/modules/formulize/libraries/jquery/jquery-1.4.2.min.js';
	head.appendChild(script);
}

<?php
if($useXhr) {
	print " initialize_formulize_xhr();\n";
	drawXhrJavascript();
	print "</script>";
	print "<script type=\"text/javascript\" src=\"".XOOPS_URL."/modules/formulize/libraries/jquery/jquery-1.4.2.min.js\"></script>\n";
	print "<script type='text/javascript'>";
	print "var elementStates = new Array();";
	print "var savingNow = \"\";";
	print "var elementActive = \"\";";
?>
function renderElement(handle,element_id,entryId,fid,check) {
	if(elementStates[handle] == undefined) {
		elementStates[handle] = new Array();
	}
	if(elementStates[handle][entryId] == undefined) {
		if(elementActive) {
			// this is a bit cheap...we should be able to track multiple elements open at once.  But there seem to be race condition issues in the asynchronous requests that we have to track down.  This UI restriction isn't too bad though.
			alert("You need to close the form element that is open first, before you can edit this one.");
			return false;
		}
		elementActive = true;
		elementStates[handle][entryId] = jQuery("#deDiv_"+handle+"_"+entryId).html();
		var formulize_xhr_params = [];
		formulize_xhr_params[0] = handle;
		formulize_xhr_params[1] = element_id;
		formulize_xhr_params[2] = entryId;
		formulize_xhr_params[3] = fid;
		formulize_xhr_send('get_element_html',formulize_xhr_params);
	} else {
		if(check && savingNow == "") {
			savingNow = true;
			jQuery("#deDiv_"+handle+"_"+entryId).fadeTo("fast",0.33);
			if(jQuery("[name='de_"+fid+"_"+entryId+"_"+element_id+"[]']").length > 0) { 
			  nameToUse = "[name='de_"+fid+"_"+entryId+"_"+element_id+"[]']";
			} else {
			  nameToUse = "[name='de_"+fid+"_"+entryId+"_"+element_id+"']";
			}
			jQuery.post("<?php print XOOPS_URL; ?>/modules/formulize/include/readelements.php", jQuery(nameToUse+",[name='decue_"+fid+"_"+entryId+"_"+element_id+"']").serialize(), function(data) {
				if(data) {
				   alert(data);	
				} else {
					// need to get the current value, and then prep it, and then format it
					var formulize_xhr_params = [];
					formulize_xhr_params[0] = handle;
					formulize_xhr_params[1] = element_id;
					formulize_xhr_params[2] = entryId;
					formulize_xhr_params[3] = fid;
					formulize_xhr_send('get_element_value',formulize_xhr_params);
				}
			});
		} else if(check) {
			// do nothing...only allow one saving operation at a time
		} else {
			jQuery("#deDiv_"+handle+"_"+entryId).html(elementStates[handle][entryId]);
			elementStates[handle].splice(entryId, 1);
			elementActive = "";
		}
	}
}

function renderElementHtml(elementHtml,params) {
	handle = params[0];
	element_id = params[1];
	entryId = params[2];
	fid = params[3];
	jQuery("#deDiv_"+handle+"_"+entryId).html(elementHtml+"<br /><a href=\"\" onclick=\"javascript:renderElement('"+handle+"', "+element_id+", "+entryId+", "+fid+",1);return false;\"><img src=\"<?php print XOOPS_URL; ?>/modules/formulize/images/check.gif\" /></a>&nbsp;&nbsp;&nbsp;<a href=\"\" onclick=\"javascript:renderElement('"+handle+"', "+element_id+", "+entryId+", "+fid+");return false;\"><img src=\"<?php print XOOPS_URL; ?>/modules/formulize/images/x-wide.gif\" /></a>");
}

function renderElementNewValue(elementValue,params) {
	handle = params[0];
	element_id = params[1];
	entryId = params[2];
	fid = params[3];
	jQuery("#deDiv_"+handle+"_"+entryId).fadeTo("fast",1);
	jQuery("#deDiv_"+handle+"_"+entryId).html(elementValue);
	elementStates[handle].splice(entryId, 1);
	savingNow = "";
	elementActive = "";
}

<?php	
}
?>


window.document.controls.ventry.value = '';
window.document.controls.loadreport.value = '';

function warnLock() {
	alert('<?php print _formulize_DE_WARNLOCK; ?>');
	return false;
}

function clearSearchHelp(formObj, defaultHelp) {
	if(formObj.firstbox.value == defaultHelp) {
		formObj.firstbox.value = "";
	}
}

function showPop(url) {

	window.document.controls.ventry.value = '';
	if (window.formulize_popup == null) {
		formulize_popup = window.open(url,'formulize_popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=550,screenX=0,screenY=0,top=0,left=0');
      } else {
		if (window.formulize_popup.closed) {
			formulize_popup = window.open(url,'formulize_popup','toolbar=no,scrollbars=yes,resizable=yes,width=800,height=550,screenX=0,screenY=0,top=0,left=0');
            } else {
			window.formulize_popup.location = url;              
		}
	}
	window.formulize_popup.focus();

}


function confirmDel() {
	var answer = confirm ('<?php print _formulize_DE_CONFIRMDEL; ?>');
	if (answer) {
		window.document.controls.delconfirmed.value = 1;
		window.document.controls.ventry.value = '';
		showLoading();
	} else {
		return false;
	}
}

function confirmClone() {
	var clonenumber = prompt("<?php print _formulize_DE_CLONE_PROMPT; ?>", "1");
	if(eval(clonenumber) > 0) {
		window.document.controls.cloneconfirmed.value = clonenumber;
		window.document.controls.ventry.value = '';
		window.document.controls.forcequery.value = 1;
		showLoading();
	} else {
		return false;
	}
}


function sort_data(col) {
	if(window.document.controls.sort.value == col) {
		var ord = window.document.controls.order.value;
		if(ord == 'SORT_DESC') {
			window.document.controls.order.value = 'SORT_ASC';
		} else {
			window.document.controls.order.value = 'SORT_DESC';
		}
	} else {
		window.document.controls.order.value = 'SORT_ASC';
	}
	window.document.controls.sort.value = col;
	window.document.controls.ventry.value = '';
	showLoading();
}


function runExport(type) {
	window.document.controls.xport.value = type;
	window.document.controls.ventry.value = '';
	showLoading();

}

function showExport() {
	window.document.getElementById('exportlink').style.display = 'block';
}

//Select All and Clear All new JQuery Function Instead the Javascript 
function selectAll(check) {
   $('.formulize_selection_checkbox').each(function(){
      $('.formulize_selection_checkbox').attr('checked', true);
   });
}

function unselectAll(uncheck) {
   $('.formulize_selection_checkbox').each(function(){
      $('.formulize_selection_checkbox').attr('checked', false);
   });
}
/* ---------------------------------------
   The selectall and clearall functions are based on a function by
   Vincent Puglia, GrassBlade Software
   site:   http://members.aol.com/grassblad
   
   NOTE: MUST RETROFIT THIS SO IN ADDITION TO CHECKING TYPE, WE ARE CHECKING FOR 'delete_' in the name, so we can have other checkbox elements in the screen templates!
------------------------------------------- */
/*
function selectAll(formObj) 
{
   for (var i=0;i < formObj.length;i++) 
   {
      fldObj = formObj.elements[i];
      if (fldObj.type == 'checkbox')
      { 
         fldObj.checked = true; 
      }
   }
}

function clearAll(formObj)
{
   for (var i=0;i < formObj.length;i++) 
   {
      fldObj = formObj.elements[i];
      if (fldObj.type == 'checkbox')
      { 
         fldObj.checked = false; 
      }
   }
}
*/
function delete_view(formObj, pubstart, endstandard) {

	for (var i=0; i < formObj.currentview.options.length; i++) {
		if (formObj.currentview.options[i].selected) {
			if( i > endstandard && i < pubstart && formObj.currentview.options[i].value != "") {
				var answer = confirm ('<?php print _formulize_DE_CONF_DELVIEW; ?>');
				if (answer) {
					window.document.controls.delview.value = 1;
					window.document.controls.ventry.value = '';
					showLoading();
				} else {
					return false;
				}
			} else {
				if(formObj.currentview.options[i].value != "") {
					alert('<?php print _formulize_DE_DELETE_ALERT; ?>');
				}
				return false;
			}
		}
	}

}

function change_view(formObj, pickgroups, endstandard) {
	for (var i=0; i < formObj.currentview.options.length; i++) {
		if (formObj.currentview.options[i].selected) {
			if(i == pickgroups && pickgroups != 0) {
				<?php print "showPop('" . XOOPS_URL . "/modules/formulize/include/changescope.php?fid=$fid&frid=$frid&scope=$currentview');"; ?>				
				return false;
			} else {
				if ( formObj.currentview.options[i].value == "" ) {
					return false;
				} else {
					window.document.controls.loadreport.value = 1;
					if(i <= endstandard && window.document.controls.lockcontrols.value == 1) {
						window.document.controls.resetview.value = 1;
						window.document.controls.curviewid.value = "";
					}
					window.document.controls.lockcontrols.value = 0;
					window.document.controls.ventry.value = '';
					showLoading();
				}
			}
		}
	}
}

function addNew(flag) {
	if(flag=='proxy') {
		window.document.controls.ventry.value = 'proxy';
	} else if(flag=='single') {
		window.document.controls.ventry.value = 'single';
	} else {
		window.document.controls.ventry.value = 'addnew';
	}
	window.document.controls.submit();
}

function goDetails(viewentry, screen) {
	window.document.controls.ventry.value = viewentry;
	if(screen>0) {
		window.document.controls.overridescreen.value = screen;
	}
	window.document.controls.submit();
}

function cancelCalcs() {
	window.document.controls.calc_cols.value = '';
	window.document.controls.calc_calcs.value = '';
	window.document.controls.calc_blanks.value = '';
	window.document.controls.calc_grouping.value = '';
	window.document.controls.hlist.value = 0;
	window.document.controls.hcalc.value = 1;
	window.document.controls.ventry.value = '';
	showLoading();
}

function customButtonProcess(caid, entries) {
	window.document.controls.caid.value = caid;
	window.document.controls.caentries.value = entries;
	showLoading();
}


function hideList() {
	window.document.controls.hlist.value = 1;
	window.document.controls.hcalc.value = 0;
	window.document.controls.ventry.value = '';
	showLoading();
}

function showList() {
	window.document.controls.hlist.value = 0;
	window.document.controls.hcalc.value = 1;
	window.document.controls.ventry.value = '';
	showLoading();
}

function killSearch() {
	window.document.controls.asearch.value = '';
	window.document.controls.ventry.value = '';
	showLoading();
}

function forceQ() {
	window.document.controls.forcequery.value = 1;
	showLoading();
}

function showLoading() {
	window.document.controls.formulize_scrollx.value = jQuery(window).scrollTop();
	window.document.controls.formulize_scrolly.value = jQuery(window).scrollLeft();
	<?php
		if($useWorking) {
			print "window.document.getElementById('listofentries').style.opacity = 0.5;\n";
			print "window.document.getElementById('workingmessage').style.display = 'block';\n";
			print "window.scrollTo(0,0);\n";
		}
	?>
	window.document.controls.ventry.value = '';
	window.document.controls.submit();
}

function showLoadingReset() {
	<?php
		if($useWorking) {
			print "window.document.getElementById('listofentries').style.opacity = 0.5;\n";
			print "window.document.getElementById('workingmessage').style.display = 'block';\n";
			print "window.scrollTo(0,0);\n";
		}
	?>
	window.document.resetviewform.submit();
}

function pageJump(page) {
	window.document.controls.formulize_LOEPageStart.value = page;
	showLoading();
}

function getPaddingNumber(element,paddingType) {
	var value = element.css(paddingType).replace(/[A-Za-z$-]/g, "");;
	return value;
}

var floatingContents = new Array();

function toggleColumnInFloat(column) {
	jQuery('.column'+column).map(function () {
		var columnAddress = jQuery(this).attr('id').split('_');
		var row = columnAddress[1];
		if(floatingContents[column] == true) {
			jQuery('#floatingcelladdress_'+row+' #cellcontents_'+row+'_'+column).remove();
			jQuery('#celladdress_'+row+'_'+column).css('display', 'table-cell');
			jQuery(this).removeClass('now-scrolling');
		} else {
			jQuery('#floatingcelladdress_'+row).append(jQuery(this).html());
			var paddingTop = getPaddingNumber(jQuery(this),'padding-top');
			var paddingBottom = getPaddingNumber(jQuery(this),'padding-bottom');
			var paddingLeft = getPaddingNumber(jQuery(this),'padding-left');
			var paddingRight = getPaddingNumber(jQuery(this),'padding-right');
			jQuery('#floatingcelladdress_'+row+' #cellcontents_'+row+'_'+column).css('width', (parseInt(jQuery(this).width())+parseInt(paddingLeft)+parseInt(paddingRight)));
			jQuery('#floatingcelladdress_'+row+' #cellcontents_'+row+'_'+column).css('height', (parseInt(jQuery(this).height())+parseInt(paddingTop)+parseInt(paddingBottom)));
			jQuery(this).addClass('now-scrolling');
		}
	});
	if(floatingContents[column] == true) {
		floatingContents[column] = false;
		jQuery("#lockcolumn_"+column).empty().append('[ ]');
	} else {
		floatingContents[column] = true;
	}
}

function setScrollDisplay(element) {
	if(element.scrollLeft() > 0) {
		var maxWidth = 0;
		jQuery(".now-scrolling").css('display', 'none');
		jQuery(".floating-column").css('display', 'table-cell');
	} else {
		jQuery(".floating-column").css('display', 'none');
		jQuery(".now-scrolling").css('display', 'table-cell');
	}
}

jQuery(window).load(function() {
	jQuery('.lockcolumn').live("click", function() {
		var lockData = jQuery(this).attr('id').split('_');
		var column = lockData[1];
		if(floatingContents[column] == true) {
			jQuery(this).empty();
			jQuery(this).append('[ ]');
			var curColumnsArray = jQuery('#formulize_lockedColumns').val().split(',');
			var curColumnsHTML = '';
			for (var i=0; i < curColumnsArray.length; i++) {
				if(curColumnsArray[i] != column) {
					if(curColumnsHTML != '') {
						curColumnsHTML = curColumnsHTML+',';
					}
					curColumnsHTML = curColumnsHTML+curColumnsArray[i];
				}
			}
			jQuery('#formulize_lockedColumns').val(curColumnsHTML);
		} else {
			jQuery(this).empty();
			jQuery(this).append('[X]');
			var curColumnsHTML = jQuery('#formulize_lockedColumns').val();
			jQuery('#formulize_lockedColumns').val(curColumnsHTML+','+column);
		}
		toggleColumnInFloat(column);
		return false;
	});
	
	jQuery(window).scrollTop(<?php print intval($_POST['formulize_scrollx']); ?>);
	jQuery(window).scrollLeft(<?php print intval($_POST['formulize_scrolly']); ?>);

<?php

foreach($lockedColumns as $thisColumn) {
	if(is_numeric($thisColumn)) {
		print "toggleColumnInFloat(".intval($thisColumn).");\n";
	}
}


?>
	
	jQuery('#resbox').scroll(function () {
		setScrollDisplay(jQuery('#resbox'));
	});
	jQuery(window).scroll(function () {
		setScrollDisplay(jQuery(window));
	});

	var saveButtonOffset = jQuery('#floating-list-of-entries-save-button').offset();
	saveButtonOffset.left = 15;
	floatSaveButton(saveButtonOffset);
	jQuery(window).scroll(function () {
		floatSaveButton(saveButtonOffset);
	});
	
});

function floatSaveButton(saveButtonOffset) {
      var scrollBottom = jQuery(window).height() - jQuery(window).scrollTop();
      if (saveButtonOffset && (saveButtonOffset.top > scrollBottom || jQuery(window).width() < jQuery(document).width())) {
	jQuery('#floating-list-of-entries-save-button').addClass('save_button_fixed');
	jQuery('#floating-list-of-entries-save-button').addClass('ui-corner-all');
	if(saveButtonOffset.top <= scrollBottom) {
		jQuery('#floating-list-of-entries-save-button').css('bottom', jQuery(window).height() - saveButtonOffset.top - jQuery('#floating-list-of-entries-save-button').height());
	}
	if(jQuery(window).scrollLeft() < saveButtonOffset.left) {
		newSaveButtonOffset = saveButtonOffset.left - jQuery(window).scrollLeft();
	} else if(jQuery(window).scrollLeft() > 0){
		newSaveButtonOffset = 0;
	} else {
		newSaveButtonOffset = saveButtonOffset.left;
	}
	jQuery('#floating-list-of-entries-save-button').css('left', newSaveButtonOffset);
      } else if(saveButtonOffset) {
	jQuery('#floating-list-of-entries-save-button').removeClass('save_button_fixed');
	jQuery('#floating-list-of-entries-save-button').removeClass('ui-corner-all');
      };
}

jQuery(window).scroll(function () {
        jQuery('.floating-column').css('margin-top', ((window.pageYOffset)*-1));
});

</script>
<?php
}
?>