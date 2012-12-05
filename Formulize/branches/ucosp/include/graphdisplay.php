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
include_once XOOPS_ROOT_PATH."/modules/formulize/include/functions.php";

/**
 * IMPORTANT: Implemented User Cases:
 * 1. bar graph "count":
 * 		a. counting # of male and females in a class:
 * 			displayGraph("Bar", form_id_of_class, "", gender, gender, "count", $graphOptions);
 * 		b. counting # of mayors for a city
 * 			displayGraph("Bar", form_id_of_class, relation_id, city, mayor, "count", $graphOptions);
 * 2. bar graph "count-unique":
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
	$myPicture -> setFontProperties(array("FontName" => XOOPS_ROOT_PATH ."/modules/formulize/libraries/pChart/fonts/arial.ttf", "FontSize" => 8));
	
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
function renderGraph($myPicture, $fid, $frid, $labelElement, $dataElement, $operation, $graphOptions) {
	// TODO: make some kind of cron job clear up or some kind of caches, update graph only when needed!
	$grapRelativePathPrefix = "modules/formulize/images/graphs/";
	$graphRelativePath = $grapRelativePathPrefix . "_" . $fid . "_" . "_" . $frid . "_" . $labelElement . "_" . $dataElement . "_" . "$operation" . "_" . preg_replace('/[^\w\d]/', "", print_r($graphOptions, true)) . ".png";
	$graphRelativePath = preg_replace('/\s/', '_', $graphRelativePath);
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
?>