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
	list($dataPoints, $dataElement, $labelElement) = gatherGraphData($fid, $frid, $filter, $labelElement, $dataElement, $operation);
	switch ($graphType) {
		case "Bar" :
			displayBarGraph($dataPoints, $labelElement, $dataElement, $graphOptions);
			break;
		case "Radar" :
			displayRadarGraph($dataPoints, $labelElement, $dataElement, $graphOptions);
			break;
		case "Line" :
			displayLineGraph($dataPoints, $labelElement, $dataElement, $graphOptions);
			break;
		default :
			echo "Sorry, the graph type \"$graphType\" is not supported at the moment!";
			break;
	}
}

/**
 * Helper method to query the database and process data for display in a graph.
 * @param $fid the id of the form where the data is coming from
 * @param $frid the id of the relation(relating fid's form to another form) $frid == fid if no relation is specified
 * @param $labelElement the field in the form to be used as label
 * @param $dataElement the field in the form to be used as data to graph
 * @param $operation the operation to be used on raw data
 */
function gatherGraphData($fid, $frid, $filter, $labelElement, $dataElement, $operation) {
	if (is_int($frid) && $frid > 0) {
		$dbData = getData($frid, $fid, $filter);
	} else {
		$dbData = getData("", $fid, $filter);
	}

	if (!is_array($dataElement)) {
		$dataElement = array($dataElement);
	}
	if (!is_array($labelElement)) {
		$labelElement = array($labelElement);
	}

	$completeDataRawValue = array();
	$completeLabelRawValue = array();
	$dataPoints = array();
	foreach ($dbData as $entry) {
		// mayor - OR array of mayors if there's more than one in the dataset, depending on the one-to-may in a relationship
		foreach($dataElement as $thisDataElement) {
			$dataRawValue = display($entry, $thisDataElement);
		}
		// city_name;
		foreach($labelElement as $thisLabelElement) {
			$labelRawValue = display($entry, $thisLabelElement);
		}
		if (!is_array($dataRawValue) && $dataRawValue) {
			$dataRawValue = array($dataRawValue);
		}
		if (!is_array($labelRawValue)) {
			$labelRawValue = array($labelRawValue);
		}
		$completeDataRawValue[] = $dataRawValue;
		$completeLabelRawValue[] = $labelRawValue;
		
		// futureworx data, would end up looking like this:
		/*
		$completeLabelRawValue[0] = 'Presentation'; // I think actually, in this case we would end up with a 5 instead of Presentation, because the value of the field is 5, and the value seems to be what they're packing up...but we want the caption...hmmm
		$completeDataRawValue[0] = 5;
		$completeLabelRawValue[1] = 'Teamwork';
		$completeDataRawValue[1] = 3;
		*/
		// this needs to be modified so that....see comment at the end...
		foreach ($labelRawValue as $thisLabelValue) {
			if ($dataPoints[$thisLabelValue]) {
				$dataPoints[$thisLabelValue] = array_merge($dataPoints[$thisLabelValue], $dataRawValue);
			} else {
				$dataPoints[$thisLabelValue] = $dataRawValue;
			}
		}
		// output would be:
		/*
		$dataPoints['presentation'] = 5;
		$dataPoints['teamwork'] = 3;
		*/
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
	
    return array($dataPoints, $dataElement, $labelElement);
}


/**
 * Helper method to draw bar graph
 * parameters have same meaning as displayGraph's parameters
 */
function displayBarGraph($dataPoints, $labelElement, $dataElement, $graphOptions) {

	$graphFileName = uniqueGraphFileName($dataPoints, $labelElement, $dataElement, $graphOptions);
	if (!file_exists(XOOPS_ROOT_PATH.$graphFileName)) {
	
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
		$chartImage = new pImage($IMAGE_WIDTH, $IMAGE_HEIGHT, $myData);
		$chartImage->drawGradientArea(0, 0, $IMAGE_WIDTH, $IMAGE_HEIGHT, DIRECTION_VERTICAL, array("StartR"=>$BACKGROUND_R, "StartG"=>$BACKGROUND_G, "StartB"=>$BACKGROUND_B, "EndR"=>$BACKGROUND_R, "EndG"=>$BACKGROUND_G, "EndB"=>$BACKGROUND_B, "Alpha"=>100));
		$chartImage->drawGradientArea(0,0,500,500,DIRECTION_HORIZONTAL,array("StartR"=>240,"StartG"=>240,"StartB"=>240,"EndR"=>180,"EndG"=>180,"EndB"=>180,"Alpha"=>30));
		$chartImage->setFontProperties(array("FontName"=>"modules/formulize/libraries/pChart/fonts/arial.ttf", "FontSize"=>8));
	
	$paddingtoLeft = $IMAGE_WIDTH * 0.15;
	$paddingtoTop = $IMAGE_HEIGHT * 0.2;
	if( $paddingtoTop > 50){
		$paddingtoTop = 50;
	}

	/* Draw the chart scale */
		$chartImage->setGraphArea($paddingtoLeft, $paddingtoTop, $IMAGE_WIDTH * 0.90, $IMAGE_HEIGHT * 0.88);
	
	if($IMAGE_ORIENTATION == "vertical"){
			$chartImage->drawScale(array("CycleBackground"=>TRUE, "DrawSubTicks"=>TRUE, "GridR"=>0, "GridG"=>0, "GridB"=>0, "GridAlpha"=>10, "Pos"=>SCALE_POS_TOPBOTTOM, "Mode"=>SCALE_MODE_ADDALL_START0, "Decimal"=>0, "MinDivHeight"=>50));
	}else{
			$chartImage->drawScale(array("CycleBackground"=>TRUE, "DrawSubTicks"=>TRUE, "GridR"=>0, "GridG"=>0, "GridB"=>0, "GridAlpha"=>10, "Mode"=>SCALE_MODE_ADDALL_START0, "Decimal"=>0, "MinDivHeight"=>50));
	}
		
	/* Turn on shadow computing */
		$chartImage->setShadow(TRUE, array("X"=>1, "Y"=>1, "R"=>0, "G"=>0, "B"=>0, "Alpha"=>10));
	
	$Palette = array("0"=>array("R"=>$BARCOLOR_R,"G"=>$BARCOLOR_G,"B"=>$BARCOLOR_B,"Alpha"=>100));
	
	for($i = 1 ; $i < $sizeMultiplier ; $i++){
		$Palette[$i] = array("R"=>$BARCOLOR_R,"G"=>$BARCOLOR_G,"B"=>$BARCOLOR_B,"Alpha"=>100);
	}
	
	$myPicture->drawBarChart(array("OverrideColors"=>$Palette));

	/* Draw the chart */
		$chartImage->drawBarChart(array("DisplayPos"=>LABEL_POS_INSIDE, "DisplayValues"=>TRUE, "Rounded"=>TRUE, "Surrounding"=>30, "OverrideColors"=>$Palette));
		// save the chart as an image
		$chartImage->render(XOOPS_ROOT_PATH.$graphFileName);
	}
	return outputChartFile($graphFileName, $graphOptions);
}

function YAxisFormat($Value) {
	if (round($Value) == $Value) {
		return round($Value);
	}
		return "";
	}

/**
 * Helper method to draw radar graph
 * parameters have same meaning as displayGraph's parameters
 */
function displayRadarGraph($dataPoints, $labelElement, $dataElement, $graphOptions) {
	include_once XOOPS_ROOT_PATH."/modules/formulize/libraries/pChart/class/pRadar.class.php";
	$graphOptions = setDefaultRadarGraphOptions($graphOptions);
	$graphFileName = uniqueGraphFileName($dataPoints, $labelElement, $dataElement, $graphOptions);
	if (!file_exists(XOOPS_ROOT_PATH.$graphFileName)) {

		// create the graph data object and add data
		$graphData = new pData();
		// if the first item in the array is not an array, then there is only one array of data points
		if (!is_array(array_slice($dataPoints, 0, 1))) {
			$graphData->addPoints($dataPoints, "DataPoints");
			$graphData->setPalette("DataPoints", $graphOptions["plotcolor"]);
		} else {
			// support multiple sets of data
			foreach ($dataPoints as $key=>$value) {
				$graphData->addPoints($value, $key);
				if (isset($graphOptions["plotcolor"][$key]))
					$graphData->setPalette($key, $graphOptions["plotcolor"][$key]);
			}
		}

		// set graph labels
		$graphData->addPoints($labelElement, "Labels");
		$graphData->setAbscissa("Labels");

		// create an image to hold the chart
		$chartImage = new pImage($graphOptions["width"], $graphOptions["height"], $graphData);
		// subtract 1 from width and height so that border can be drawn...
		//$chartImage->drawFilledRectangle(0, 0, $graphOptions["width"] - 1, $graphOptions["height"] - 1, $graphOptions["background"]);

		// set the default font
		$chartImage->setFontProperties(array("FontName"=>XOOPS_ROOT_PATH."/modules/formulize/libraries/pChart/fonts/".$graphOptions["FontName"].".ttf",
			"FontSize"=>$graphOptions["FontSize"],
			"R"=>$graphOptions["FontRGB"]["R"], "G"=>$graphOptions["FontRGB"]["G"], "B"=>$graphOptions["FontRGB"]["B"]));

		// create the pRadar object
		$theChart = new pRadar();

		// draw the chart
		$chartImage->setGraphArea(0 + $graphOptions["padding"], 0 + $graphOptions["padding"],
			$graphOptions["width"] - (2 * $graphOptions["padding"]), $graphOptions["height"] - (2 * $graphOptions["padding"]));
		$theChart->drawRadar($chartImage, $graphData, $graphOptions);

		// legend
		// For Legend parameter details, see: http://wiki.pchart.net/doc.doc.draw.legend.html
		if (isset($graphOptions["Legend"]))
			$chartImage->drawLegend($graphOptions["Legend"]["x"], $graphOptions["Legend"]["y"], $graphOptions["Legend"]);

		// title
		if (isset($graphOptions['titlefont']))
			$chartImage->setFontProperties($graphOptions['titlefont']);
		if (isset($graphOptions['title'])) {
			if (!isset($graphOptions['titleX']))
				$graphOptions['titleX'] = 0;
			if (!isset($graphOptions['titleY']))
				$graphOptions['titleY'] = 0;
	 		$chartImage->drawText($graphOptions['titleX'], $graphOptions['titleY'], $graphOptions['title']);
		}

		// save the chart as an image
		$chartImage->render(XOOPS_ROOT_PATH.$graphFileName);
	}
	return outputChartFile($graphFileName, $graphOptions);
}

/**
 * Helper method that ensures default options are set for the radar graph. Not a public API.
 */
function setDefaultRadarGraphOptions($graphOptions) {
	// set default values for graph options (if the options are not set)
	// width         : (pixels) width of the image
	// height        : (pixels) height of the image
	// padding       : (pixels) padding between each side of the chart and the image (default 0)
	// background    : (array) RGB color array
	// plotcolor     : (array) RGB color array
	// For complete Radar graph style options, see: http://wiki.pchart.net/doc.draw.radar.html

	if (!isset($graphOptions["width"]))
		$graphOptions["width"] = 600;
	if (!isset($graphOptions["height"]))
		$graphOptions["height"] = 600;
	if (!isset($graphOptions["plotcolor"]) and !isset($graphOptions["plotcolor"]["B"]))
		$graphOptions["plotcolor"] = array("R"=>150, "G"=>150, "B"=>150, "Alpha"=>50);
	if (!isset($graphOptions["FontSize"]) or $graphOptions["FontSize"] < 4)
		$graphOptions["FontSize"] = 8;
	if (!isset($graphOptions["FontName"]))
		$graphOptions["FontName"] = "arial";
	if (!isset($graphOptions["FontRGB"]) and !isset($graphOptions["FontRGB"]["B"]))
		$graphOptions["FontRGB"] = array("R"=>0, "G"=>0, "B"=>0, "Alpha"=>100);
	// padding (pixels) adds space between the edges of the image and the chart (like css padding)
	if (!isset($graphOptions["padding"]) or $graphOptions["padding"] < 0)
		$graphOptions["padding"] = 0;
	if (!isset($graphOptions["BackgroundGradient"]) and !is_array($graphOptions["BackgroundGradient"]))
		$graphOptions["BackgroundGradient"] = array("StartR"=>255, "StartG"=>255, "StartB"=>255, "StartAlpha"=>100,
			"EndR"=>207, "EndG"=>227, "EndB"=>125, "EndAlpha"=>100);
	if (!isset($graphOptions["DrawPoly"]))
		$graphOptions["DrawPoly"] = True;
	if (!isset($graphOptions["WriteValues"]))
		$graphOptions["WriteValues"] = True;
	if (!isset($graphOptions["WriteLabels"]))
		$graphOptions["WriteLabels"] = True;
	if (!isset($graphOptions["SkipLabels"]))
		$graphOptions["SkipLabels"] = 0;
	if (!isset($graphOptions["DrawAxisValues"]))
		$graphOptions["DrawAxisValues"] = True;
	if (!isset($graphOptions["ValueFontSize"]))
		$graphOptions["ValueFontSize"] = 8;
	if (!isset($graphOptions["Layout"]))
		$graphOptions["Layout"] = RADAR_LAYOUT_CIRCLE;
	if (!isset($graphOptions["LabelPos"]))
		$graphOptions["LabelPos"] = RADAR_LABELS_HORIZONTAL;

	return $graphOptions;
}


/**
 * Helper method to draw line graph
 * parameters have same meaning as displayGraph's parameters
 */
function displayLineGraph($dataPoints, $labelElement, $dataElement, $graphOptions) {
	$graphOptions = setDefaultLineGraphOptions($graphOptions);

	$graphFileName = uniqueGraphFileName($dataPoints, $labelElement, $dataElement, $graphOptions);
	if (!file_exists(XOOPS_ROOT_PATH.$graphFileName)) {
		// create the graph data object and add data
		$graphData = new pData();
		// if the first item in the array is not an array, then there is only one array of data points
		if (!is_array(array_slice($dataPoints, 0, 1))) {
			$graphData->addPoints($dataPoints, "DataPoints");
			if (isset($graphOptions["plotcolor"]))
				$graphData->setPalette("DataPoints", $graphOptions["plotcolor"]);
			if (isset($graphOptions["dashed"]))
				$graphData->setSerieTicks("DataPoints", $graphOptions["dashed"]);
			if (isset($graphOptions["thickness"]))
				$graphData->setSerieWeight("DataPoints", $graphOptions["thickness"]);
		} else {
			// support multiple sets of data
			foreach ($dataPoints as $key=>$value) {
				$graphData->addPoints($value, $key);
				if (isset($graphOptions["plotcolor"][$key]))
					$graphData->setPalette($key, $graphOptions["plotcolor"][$key]);
				if (isset($graphOptions["dashed"][$key]))
					$graphData->setSerieTicks($key, $graphOptions["dashed"][$key]);
				if (isset($graphOptions["thickness"][$key]))
					$graphData->setSerieWeight($key, $graphOptions["thickness"][$key]);
			}
		}

		// set graph labels
		$graphData->addPoints($labelElement, "Labels");
		$graphData->setAbscissa("Labels");

		// create an image to hold the chart
		$chartImage = new pImage($graphOptions["width"], $graphOptions["height"], $graphData);

		if (isset($graphOptions["pre_draw_hook"]) and function_exists($graphOptions["pre_draw_hook"])) {
			// allow drawing on the image before the chart is drawn
			$graphOptions["pre_draw_hook"]($chartImage, $graphOptions);
		}

		// subtract 1 from width and height so that border can be drawn...
		//$chartImage->drawFilledRectangle(0, 0, $graphOptions["width"] - 1, $graphOptions["height"] - 1, $graphOptions["background"]);

		// set the default font
		$chartImage->setFontProperties(array("FontName"=>XOOPS_ROOT_PATH."/modules/formulize/libraries/pChart/fonts/".$graphOptions["FontName"].".ttf",
			"FontSize"=>$graphOptions["FontSize"],
			"R"=>$graphOptions["FontRGB"]["R"], "G"=>$graphOptions["FontRGB"]["G"], "B"=>$graphOptions["FontRGB"]["B"]));

		// draw the chart
		$chartImage->setGraphArea(0 + $graphOptions["padding"], 0 + $graphOptions["padding"],
			$graphOptions["width"] - $graphOptions["padding"], $graphOptions["height"] - $graphOptions["padding"]);
	 	$chartImage->drawScale($graphOptions);
	 	if (isset($graphOptions["spline"]) and $graphOptions["spline"])
		 	$chartImage->drawSplineChart($graphOptions);
		else
	 		$chartImage->drawLineChart($graphOptions);

		// legend. For parameter details, see: http://wiki.pchart.net/doc.doc.draw.legend.html
		if (isset($graphOptions["Legend"]))
			$chartImage->drawLegend($graphOptions["Legend"]["x"], $graphOptions["Legend"]["y"], $graphOptions["Legend"]);

		// title
		if (isset($graphOptions['titlefont']))
			$chartImage->setFontProperties($graphOptions['titlefont']);
		if (isset($graphOptions['title'])) {
			if (!isset($graphOptions['titleX']))
				$graphOptions['titleX'] = 0;
			if (!isset($graphOptions['titleY']))
				$graphOptions['titleY'] = 0;
	 		$chartImage->drawText($graphOptions['titleX'], $graphOptions['titleY'], $graphOptions['title']);
		}

		if (isset($graphOptions["post_draw_hook"]) and function_exists($graphOptions["post_draw_hook"])) {
			// allow drawing on the image after the chart is drawn
			$graphOptions["post_draw_hook"]($chartImage, $graphOptions);
		}

		// save the chart as an image
		$chartImage->render(XOOPS_ROOT_PATH.$graphFileName);
	}
	return outputChartFile($graphFileName, $graphOptions);
}

/**
 * Helper method that ensures default options are set for the line graph. Not a public API.
 */
function setDefaultLineGraphOptions($graphOptions) {
	// set default values for graph options (if the options are not set)
	// width         : (pixels) width of the image
	// height        : (pixels) height of the image
	// padding       : (pixels) padding between each side of the chart and the image (default 0)
	// For complete line graph style options, see: pDraw.class.php or http://wiki.pchart.net/doc.chart.drawlinechart.html

	if (!isset($graphOptions["width"]))
		$graphOptions["width"] = 600;
	if (!isset($graphOptions["height"]))
		$graphOptions["height"] = 600;
	if (!isset($graphOptions["padding"]))
		$graphOptions["padding"] = 0;

	if (!isset($graphOptions["FontRGB"]) and !isset($graphOptions["FontRGB"]["B"]))
		$graphOptions["FontRGB"] = array("R"=>0, "G"=>0, "B"=>0, "Alpha"=>100);
	if (!isset($graphOptions["DrawSubTicks"]))
		$graphOptions["DrawSubTicks"] = false;
	if (!isset($graphOptions["DisplayValues"]))
		$graphOptions["DisplayValues"] = true;
	if (!isset($graphOptions["DisplayColor"]))
		$graphOptions["DisplayColor"] = DISPLAY_AUTO;

	return $graphOptions;
}

/**
 * Generate a unique filename for each graph
 */
function uniqueGraphFileName($dataPoints, $labelElement, $dataElement, $graphOptions) {
	// TODO: make some kind of cron job clear up or some kind of caches, update graph only when needed!
	// many charts will use the same options, so use data points and options to determine a unique filename
	return "/modules/formulize/images/graphs/".
		md5(SDATA_DB_SALT.var_export($dataPoints, true).var_export($graphOptions, true)).".png";
}

function outputChartFile($graphFileName, $graphOptions) {
	if (isset($graphOptions['return_filename']) and $graphOptions['return_filename']) {
		// simply return the filename as a string
		return $graphFileName;
	} else {
		// output an image tag
		echo "<img src='{$graphFileName}' />";
	}
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
