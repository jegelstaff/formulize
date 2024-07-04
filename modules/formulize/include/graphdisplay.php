<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2024 Formulize                           ##
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
##  Author of this file: Formulize      						 		 	 ##
##  Project: Formulize                                                       ##
###############################################################################

include_once '../../../mainfile.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/common.php';

function displayGraph($type, $data, $dataElements, $xElements, $yElements, $labels=null, $timeUnit='day', $timeFormat='M j', $timeUnitCount=1, $minValue=null, $maxValue=null, $showAllTooltips = true) {

	$jsTimeFormat = convertPHPTimeFormatToJSTimeFormat($timeFormat);

	switch (strtolower($type)) {
		case 'line':
			$x = 1;
			$dataValues = array();
			$dataSet = array();
			foreach($data as $dataPoint) {
				$time = display($dataPoint, $xElements);
				$millisecondTimestamp = strtotime($time)*1000;
				$readableTime = date($timeFormat, strtotime($time));
				foreach($dataElements as $dataElement) {
					$dataValues[] = "$dataElement: ".display($dataPoint, $dataElement);
				}
				$dataSet[] = "{x: $x, ".implode(", ", $dataValues).", time: '$readableTime', millisecondTimestamp: $millisecondTimestamp}";
				$x++;
			}
			$dataSet = "[".implode(',', $dataSet)."]";

			$minMaxYAxes = array();
			if($minValue !== null) {
				$minMaxYAxes[] = "min: $minValue";
			}
			if($maxValue !== null) {
				$minMaxYAxes[] = "max: $maxValue";
			}
			$minMaxYAxes = implode(",", $minMaxYAxes);
			$minMaxYAxes .= $minMaxYAxes ? ",\n" : "";

			if($labels) {
				$labels = is_array($labels) ? $labels : array($labels);
			}
			$drawLines = array();
			if(is_array($labels)) {
				foreach($labels as $label) {
					$start = $label['value'] ? $label['value'] : 0;
					$label = $label['label'] ? $label['label'] : '';
					$drawLines[] = "drawLine(".$start.", \"".$label."\");";
				}
			}
			$drawLines = implode("\n", $drawLines);

			$showAllTooltips = $showAllTooltips ? '' : "maxTooltipDistance: 0,";

			?>

			<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/locales/de_DE.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/geodata/germanyLow.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/fonts/notosans-sc.js"></script>

			<style>
			#chartdiv {
			width: 100%;
			height: 500px;
			}
			</style>

			<!-- Chart code -->
			<script>
			am5.ready(function() {

				// https://www.amcharts.com/docs/v5/getting-started/#Root_element
				var root = am5.Root.new("chartdiv");
				root.utc = true;

				// Set themes
				// https://www.amcharts.com/docs/v5/concepts/themes/
				root.setThemes([
					am5themes_Animated.new(root)
				]);

				// Create chart
				// https://www.amcharts.com/docs/v5/charts/xy-chart/
				var chart = root.container.children.push(am5xy.XYChart.new(root, {
					panX: true,
					panY: true,
					wheelX: "panX",
					wheelY: "zoomX",
					pinchZoomX: true,
					<?php print $showAllTooltips; ?>
					paddingLeft: 0
				}));

				// Add cursor
				// https://www.amcharts.com/docs/v5/charts/xy-chart/cursor/
				var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {
					behavior: "none"
				}));
				cursor.lineY.set("visible", false);

				// Create axes
				// https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
				var xAxis = chart.xAxes.push(
					am5xy.DateAxis.new(root, {
						baseInterval: {
							timeUnit: "<?php print $timeUnit; ?>",
							count: <?php print $timeUnitCount; ?>
						},
						tooltipDateFormat: "<?php print $jsTimeFormat; ?>",
						renderer: am5xy.AxisRendererX.new(root, {}),
						tooltip: am5.Tooltip.new(root, {})
					})
				);


				var yAxis = chart.yAxes.push(
					am5xy.ValueAxis.new(root, {
						<?php print $minMaxYAxes; ?>
						renderer: am5xy.AxisRendererY.new(root, {})
					})
				);

				// Set data
			data = <?php print $dataSet; ?>;

			<?php

			$yElements = is_array($yElements) ? $yElements : array($yElements);

			foreach($yElements as $title=>$yElement) {

				?>

				var fillOpacity = 0.2;

				// Add series
				// https://www.amcharts.com/docs/v5/charts/xy-chart/series/
				var series = chart.series.push(am5xy.LineSeries.new(root, {
					name: "<?php print $title; ?>",
					xAxis: xAxis,
					yAxis: yAxis,
					valueYField: "<?php print $yElement['data']; ?>",
					valueXField: "millisecondTimestamp",
					legendValueText: "<?php print str_replace("\n", '\n', $yElement['labelText']); ?>",
					tooltip: am5.Tooltip.new(root, {
						labelText: "<?php print str_replace("\n", '\n', $yElement['labelText']); ?>"
					})
				}));

				series.strokes.template.setAll({
					strokeWidth: 2
				});

				series.filledRanges = [];

				<?php if(!empty($yElement['highlightAbove'])) {
					$highlightAbove = is_array($yElement['highlightAbove']) ? $yElement['highlightAbove'] : array($yElement['highlightAbove']);
					foreach($highlightAbove as $thisHighlightAbove) {
						print "highlightData(series, $thisHighlightAbove);\n";
					}
				} ?>

				series.data.setAll(data);
				series.appear(1000);

				<?php
			}
			?>

			// Add scrollbar
			// https://www.amcharts.com/docs/v5/charts/xy-chart/scrollbars/
			chart.set("scrollbarX", am5.Scrollbar.new(root, {
				orientation: "horizontal"
			}));

			function drawLine(value, label) {
				var seriesRangeDataItem = yAxis.makeDataItem({ value: value, endValue: value });
				var seriesRange = series.createAxisRange(seriesRangeDataItem);
				seriesRangeDataItem.get("grid").setAll({
					strokeOpacity: 1,
					visible: true,
					stroke: am5.color(0x000000),
					strokeDasharray: [2, 2]
				});
				seriesRangeDataItem.get("label").setAll({
					location:0,
					visible:true,
					text: label,
					inside:true,
					centerX:0,
					centerY:am5.p100,
				});
			}

			function highlightData(series, value) {

				var rangeDataItem = yAxis.makeDataItem({
					value: value,
					endValue: value * 10000000
				});

				series.filledRanges.push(series.createAxisRange(rangeDataItem));
				series.filledRanges[series.filledRanges.length-1].fills.template.setAll({
					fillOpacity: fillOpacity,
					visible: true
				});

				fillOpacity = fillOpacity + 0.1;

			}

			<?php print $drawLines; ?>

			// Add legend
			// https://www.amcharts.com/docs/v5/charts/xy-chart/legend-xy-series/
			var legend = chart.rightAxesContainer.children.push(am5.Legend.new(root, {
				width: 200,
				height: am5.percent(100),
				paddingLeft: 15,
				clickTarget: "none"
			}));

			// When legend item container is hovered, dim all the series except the hovered one
			legend.itemContainers.template.events.on("pointerover", function(e) {
				var itemContainer = e.target;

				// As series list is data of a legend, dataContext is series
				var series = itemContainer.dataItem.dataContext;

				chart.series.each(function(chartSeries) {
					if (chartSeries != series) {
						chartSeries.strokes.template.setAll({
							strokeOpacity: 0.15,
							stroke: am5.color(0x000000)
						});
						chartSeries.filledRanges.forEach((range) => {
							range.fills.template.setAll({
								visible: false,
							})
						});
					}
				})
			})

			// When legend item container is unhovered, make all series as they are
			legend.itemContainers.template.events.on("pointerout", function(e) {
				var itemContainer = e.target;
				var series = itemContainer.dataItem.dataContext;

				chart.series.each(function(chartSeries) {
					chartSeries.strokes.template.setAll({
						strokeOpacity: 1,
						stroke: chartSeries.get("fill")
					});
					chartSeries.filledRanges.forEach((range) => {
						range.fills.template.setAll({
							visible: true
						})
					});
				});
			})

			legend.itemContainers.template.set("width", am5.p100);
			legend.valueLabels.template.setAll({
				width: am5.p100,
				textAlign: "right"
			});

			// It's is important to set legend data after all the events are set on template, otherwise events won't be copied
			legend.data.setAll(chart.series.values);

			chart.appear(1000, 100);

			}); // end am5.ready()
			</script>

			<!-- HTML -->
			<div id="chartdiv"></div>

			<?php
			break;
		}
}


function convertPHPTimeFormatToJSTimeFormat($formatString) {
	$replacements = array(
		'M' => 'MMM',
		'j' => 'd',
		// needs to be completed!!!
	);
	foreach($replacements as $php=>$js) {
		$formatString = str_replace($php, $js, $formatString);
	}
	return $formatString;
}
