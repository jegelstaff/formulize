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

function displayGraph($type, $data, $dataElements=array(), $yElements=array(), $xAxisType='time', $timeElement = null, $timeUnit='day', $timeFormat='M j', $timeUnitCount=1, $labels=null, $minValue=null, $maxValue=null, $showTooltips = 'all', $smoothedLine = false, $showCursor = true) {

	$jsTimeFormat = convertPHPTimeFormatToJSTimeFormat($timeFormat);
	$xAxisStart = 0;

	switch (strtolower($type)) {
		case 'line':
			$lineType = $smoothedLine ? 'SmoothedXLineSeries' : 'LineSeries';
			$x = 1;
			$dataSet = array();
			$xAxisType = ($xAxisType == 'time' AND $timeElement) ? 'time' : 'ordinal';
			$nextExpectedTime = null;
			$nextActualTime = null;
			$firstTime = null;
			$lastTime = null;
			foreach($data as $i=>$dataPoint) {
				$time = $xAxisType == 'time' ? display($dataPoint, $timeElement) : null;
				$millisecondTimestamp = 0;
				$readableTime = null;
				if($time) {
					$millisecondTimestamp = strtotime($time)*1000;
					$readableTime = date($timeFormat, strtotime($time));
					// if this point follows a gap, treat it as a bullet
					$showBullet = ($nextExpectedTime AND $readableTime != $nextExpectedTime) ? 'showBullets: true, ' : '';
					$nextExpectedTime = date($timeFormat, strtotime($time." +$timeUnitCount $timeUnit"));
					$nextActualTime = isset($data[$i+1]) ? date($timeFormat, strtotime(display($data[$i+1], $timeElement))) : '';
					// if this point is the start of a consecutive series, don't treat it as a bullet
					if($showBullet AND $nextExpectedTime == $nextActualTime) {
						$showBullet = '';
					}
					$firstTime = !$firstTime ? $millisecondTimestamp : $firstTime;
					$lastTime = $millisecondTimestamp;
				}
				$dataValues = array();
				foreach($dataElements as $dataElement) {
					$dataValues[] = "$dataElement: ".display($dataPoint, $dataElement);
				}
				$dataSet[] = "{x: $x, ".$showBullet.implode(", ", $dataValues).", time: '$readableTime', millisecondTimestamp: $millisecondTimestamp}";
				$x++;
			}
			$dataSet = "[".implode(',', $dataSet)."]";

			// set the zoom level, applied to xAxis and scollbar
			if($xAxisType == 'time') {
				$millisecondDuration = $lastTime - $firstTime;
				switch($timeUnit) {
					case "millisecond": // default to 100 milliseconds (10th of a second)
						$xAxisStart = 1 - (100 / $millisecondDuration);
						break;
					case "second": // default to 10 seconds
						$xAxisStart = 1 - (10 / ($millisecondDuration / 1000));
						break;
					case "minute": // default to 10 minutes
						$xAxisStart = 1 - (10 / ($millisecondDuration / 1000 / 60));
						break;
					case "hour": // default to 10 hours
						$xAxisStart = 1 - (10 / ($millisecondDuration / 1000 / 60 / 60));
						break;
					case "day": // default to 14 days
						$xAxisStart = 1 - (14 / ($millisecondDuration / 1000 / 60 / 60 / 24));
						break;
					case "week": // default to 12 weeks
						$xAxisStart = 1 - (12 / ($millisecondDuration / 1000 / 60 / 60 / 24 / 7));
						break;
					case "month": // default to 12 months
						$xAxisStart = 1 - (12 / ($millisecondDuration / 1000 / 60 / 60 / 24 / 30));
						break;
					case "year": // default to 10 years
						$xAxisStart = 1 - (10 / ($millisecondDuration / 1000 / 60 / 60 / 24 / 365));
						break;
				}
			} else {
				// default to 30 items
				$xAxisStart = 1 - (30 / count($data));
			}
			$xAxisStart = $xAxisStart > 1 ? 1 : $xAxisStart;
			$xAxisStart = $xAxisStart < 0 ? 0 : $xAxisStart;

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

			$showTooltips = $showTooltips === 'all' ? '' : ($showTooltips ? "maxTooltipDistance: 0," : false);

			?>

			<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/locales/en_CA.js"></script>
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
					<?php if($showTooltips) { print $showTooltips; } ?>
					paddingLeft: 0
				}));

				<?php
				if($showCursor) { ?>
				// Add cursor
				// https://www.amcharts.com/docs/v5/charts/xy-chart/cursor/
				var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {
					behavior: "none"
				}));
				cursor.lineY.set("visible", false);
				<?php
				} ?>

				// Create axes
				// https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
				var xAxis = chart.xAxes.push(
					<?php
					switch($xAxisType) {
					case 'time': ?>
					am5xy.DateAxis.new(root, {
						start: <?php print $xAxisStart; ?>,
						baseInterval: {
							timeUnit: "<?php print $timeUnit; ?>",
							count: <?php print $timeUnitCount; ?>
						},
						<?php if($showTooltips !== false) { print "
						tooltipDateFormat: \"$jsTimeFormat\",
						tooltip: am5.Tooltip.new(root, {}),
						"; } ?>
						renderer: am5xy.AxisRendererX.new(root, {})

					})
					<?php
					break;
					case 'ordinal':
					default: ?>
					am5xy.ValueAxis.new(root, {
						renderer: am5xy.AxisRendererX.new(root, {}),
						<?php if($showTooltips !== false) { print "
						tooltip: am5.Tooltip.new(root, {})
						"; } ?>
					})
					<?php
					} ?>
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
				var series = chart.series.push(am5xy.<?php print $lineType; ?>.new(root, {
					name: "<?php print $title; ?>",
					connect: false,
					xAxis: xAxis,
					yAxis: yAxis,
					valueYField: "<?php print $yElement['data']; ?>",
					valueXField: "<?php print $xAxisType == 'time' ? 'millisecondTimestamp' : 'x'; ?>",
					legendValueText: "<?php print str_replace("\n", '\n', $yElement['labelText']); ?>",
					<?php if($showTooltips !== false) { print "
					tooltip: am5.Tooltip.new(root, {
						labelText: \"".str_replace("\n", '\n', $yElement['labelText'])."\"
					})
					"; } ?>
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

				series.bullets.push(function(root, series, dataItem) {
					if (dataItem.dataContext.showBullets == true) {
						return am5.Bullet.new(root, {
							sprite: am5.Circle.new(root, {
								radius: 4,
								fill: series.get("fill")
							})
						});
					}
				});

				series.data.setAll(data);
				series.appear(1000);

				<?php
			}
			?>

			// Add scrollbar
			// https://www.amcharts.com/docs/v5/charts/xy-chart/scrollbars/
			chart.set("scrollbarX", am5.Scrollbar.new(root, {
				start: <?php print $xAxisStart; ?>,
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
				});
				yAxis.onPrivate("max", (max) => {
   				rangeDataItem.set("endValue", max * 2);
				});

				series.filledRanges.push(series.createAxisRange(rangeDataItem));
				series.filledRanges[series.filledRanges.length-1].fills.template.setAll({
					fillOpacity: fillOpacity,
					visible: true
				});

				fillOpacity = fillOpacity + 0.1;

			}

			<?php print $drawLines; ?>

			<?php if(userHasMobileClient() == false) { ?>
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

			<?php } // end of if the user has a mobile client ?>

			chart.appear(1000, 100);

			}); // end am5.ready()
			</script>

			<!-- HTML -->
			<div id="chartdiv"></div>

			<?php
			break;

		case 'radar':

			?>

			<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/locales/en_CA.js"></script>
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

			// Create root element
			// https://www.amcharts.com/docs/v5/getting-started/#Root_element
			var root = am5.Root.new("chartdiv");


			// Set themes
			// https://www.amcharts.com/docs/v5/concepts/themes/
			root.setThemes([
				am5themes_Animated.new(root)
			]);


			// Create chart
			// https://www.amcharts.com/docs/v5/charts/radar-chart/
			var chart = root.container.children.push(am5radar.RadarChart.new(root, {
				panX: false,
				panY: false,
				wheelX: false,
				wheelY: false,
			}));

			// Add cursor
			// https://www.amcharts.com/docs/v5/charts/radar-chart/#Cursor
			var cursor = chart.set("cursor", am5radar.RadarCursor.new(root, {
				behavior: "zoomX"
			}));
			cursor.lineY.set("visible", false);

			// Create axes and their renderers
			// https://www.amcharts.com/docs/v5/charts/radar-chart/#Adding_axes
			var xRenderer = am5radar.AxisRendererCircular.new(root, {});
			xRenderer.labels.template.setAll({
				radius: 10
			});

			var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
				maxDeviation: 0,
				categoryField: "country",
				renderer: xRenderer,
				tooltip: am5.Tooltip.new(root, {})
			}));

			var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
				renderer: am5radar.AxisRendererRadial.new(root, {})
			}));


			// Create series
			// https://www.amcharts.com/docs/v5/charts/radar-chart/#Adding_series
			var series = chart.series.push(am5radar.RadarLineSeries.new(root, {
				name: "Series",
				xAxis: xAxis,
				yAxis: yAxis,
				valueYField: "litres",
				categoryXField: "country",
				tooltip:am5.Tooltip.new(root, {
					labelText:"{valueY}"
				})
			}));

			series.strokes.template.setAll({
				strokeWidth: 2
			});

			series.bullets.push(function () {
				return am5.Bullet.new(root, {
					sprite: am5.Circle.new(root, {
						radius: 5,
						fill: series.get("fill")
					})
				});
			});


			// Set data
			// https://www.amcharts.com/docs/v5/charts/radar-chart/#Setting_data
			var data = [{
				"country": "Lithuania",
				"litres": 501
			}, {
				"country": "Czechia",
				"litres": 301
			}, {
				"country": "Ireland",
				"litres": 266
			}, {
				"country": "Germany",
				"litres": 165
			}, {
				"country": "Australia",
				"litres": 139
			}, {
				"country": "Austria",
				"litres": 336
			}, {
				"country": "UK",
				"litres": 290
			}, {
				"country": "Belgium",
				"litres": 325
			}, {
				"country": "The Netherlands",
				"litres": 40
			}];
			series.data.setAll(data);
			xAxis.data.setAll(data);


			// Animate chart and series in
			// https://www.amcharts.com/docs/v5/concepts/animations/#Initial_animation
			series.appear(1000);
			chart.appear(1000, 100);

			}); // end am5.ready()
			</script>

			<!-- HTML -->
			<div id="chartdiv"></div>


			<?php
			break;

		case "pie":
			?>
			<!-- Styles -->
			<style>
			#chartdiv {
				width: 100%;
				height: 500px;
			}
			</style>

			<!-- Resources -->
			<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

			<!-- Chart code -->
			<script>
			am5.ready(function() {

			// Create root element
			// https://www.amcharts.com/docs/v5/getting-started/#Root_element
			var root = am5.Root.new("chartdiv");

			// Set themes
			// https://www.amcharts.com/docs/v5/concepts/themes/
			root.setThemes([
				am5themes_Animated.new(root)
			]);

			// Create chart
			// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/
			var chart = root.container.children.push(
				am5percent.PieChart.new(root, {
					endAngle: 270
				})
			);

			// Create series
			// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Series
			var series = chart.series.push(
				am5percent.PieSeries.new(root, {
					valueField: "value",
					categoryField: "category",
					endAngle: 270
				})
			);

			series.labels.template.setAll({
				text: "{category}"
			});

			series.slices.template.setAll({
				tooltipText: "{valuePercentTotal.formatNumber('0.00')}% (n={value})"
			});

			series.states.create("hidden", {
				endAngle: -90
			});

			// Set data
			// https://www.amcharts.com/docs/v5/charts/percent-charts/pie-chart/#Setting_data
			series.data.setAll([<?php

			foreach($data as $i=>$dataPoint) {
				print $i ? ", " : "";
				print "{
				category: ".$dataPoint['category'].",
				value: ".$dataPoint['value']."
			}";
			}

			?>]);

			series.appear(1000, 100);

			}); // end am5.ready()
			</script>

			<!-- HTML -->
			<div id="chartdiv"></div>

			<?php
			break;

		case "bar":
			?>
			<!-- Styles -->
			<style>
			#chartdiv {
				width: 100%;
				height: 500px;
			}
			</style>

			<!-- Resources -->
			<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
			<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

			<!-- Chart code -->
			<script>
			am5.ready(function() {

			// Create root element
			// https://www.amcharts.com/docs/v5/getting-started/#Root_element
			var root = am5.Root.new("chartdiv");

			// Set themes
			// https://www.amcharts.com/docs/v5/concepts/themes/
			root.setThemes([
				am5themes_Animated.new(root)
			]);

			// Create chart
			// https://www.amcharts.com/docs/v5/charts/xy-chart/
			var chart = root.container.children.push(am5xy.XYChart.new(root, {
				/*panX: true,
				panY: true,
				wheelX: "panX",
				wheelY: "zoomX",
				pinchZoomX: true,
				paddingLeft:0,
				paddingRight:1*/
				panX: false,
				panY: false,
				wheelX: "none",
				wheelY: "none",
				paddingBottom: 50,
				paddingTop: 40,
				paddingLeft:0,
				paddingRight:0
			}));

			// Add cursor
			// https://www.amcharts.com/docs/v5/charts/xy-chart/cursor/
			var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
			cursor.lineY.set("visible", false);


			// Create axes
			// https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
			var xRenderer = am5xy.AxisRendererX.new(root, {
				minGridDistance: 30,
				minorGridEnabled: true
			});

			xRenderer.grid.template.setAll({
				location: 1
			})

			var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
				maxDeviation: 0.3,
				categoryField: "category",
				renderer: xRenderer,
			}));

			xAxis.get("renderer").labels.template.adapters.add("text", function(text, target) {
				return ''
			});

			var yRenderer = am5xy.AxisRendererY.new(root, {
				strokeOpacity: 0.1
			})

			var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
				maxDeviation: 0.3,
				min: 0,
				renderer: yRenderer
			}));

			// Create series
			// https://www.amcharts.com/docs/v5/charts/xy-chart/series/
			var series = chart.series.push(
				am5xy.ColumnSeries.new(root, {
					name: "Series 1",
					xAxis: xAxis,
					yAxis: yAxis,
					valueYField: "value",
					maskBullets: false,
					sequencedInterpolation: true,
					calculateAggregates: true,
					categoryXField: "category",
					tooltip: am5.Tooltip.new(root, {
						labelText: "{category}: {value}"
					})
				})
			);

			series.columns.template.setAll({ cornerRadiusTL: 5, cornerRadiusTR: 5, strokeOpacity: 0 });
			series.columns.template.adapters.add("fill", function (fill, target) {
				return chart.get("colors").getIndex(series.columns.indexOf(target));
			});

			series.columns.template.adapters.add("stroke", function (stroke, target) {
				return chart.get("colors").getIndex(series.columns.indexOf(target));
			});

			// Set data
			var data = [<?php

			foreach($data as $i=>$dataPoint) {
				print $i ? ", " : "";
				print "{
				category: ".$dataPoint['category'].",
				value: ".$dataPoint['value'].",
				icon: { src: \"".$dataPoint['icon']."\" }
			}";
			}

			?>];

			/* bullet animations */
			var currentlyHovered;

			series.columns.template.events.on("pointerover", function (e) {
				handleHover(e.target.dataItem);
			});

			series.columns.template.events.on("pointerout", function (e) {
				handleOut();
			});

			function handleHover(dataItem) {
				if (dataItem && currentlyHovered != dataItem) {
					handleOut();
					currentlyHovered = dataItem;
					var bullet = dataItem.bullets[0];
					bullet.animate({
						key: "locationY",
						to: 1,
						duration: 1200,
						easing: am5.ease.out(am5.ease.cubic)
					});
				}
			}

			function handleOut() {
				if (currentlyHovered) {
					var bullet = currentlyHovered.bullets[0];
					bullet.animate({
						key: "locationY",
						to: 0,
						duration: 1200,
						easing: am5.ease.out(am5.ease.cubic)
					});
				}
			}

			var circleTemplate = am5.Template.new({});

			series.bullets.push(function (root, series, dataItem) {
				var bulletContainer = am5.Container.new(root, {});

				// only containers can be masked, so we add image to another container
				var imageContainer = bulletContainer.children.push(
					am5.Container.new(root, {})
				);

				var image = imageContainer.children.push(
					am5.Picture.new(root, {
						templateField: "icon",
						centerX: am5.p50,
						centerY: -4,
						cornerRadius: 5,
						width: 100,
					})
				);

				return am5.Bullet.new(root, {
					locationY: 0,
					sprite: bulletContainer
				});
			});


			xAxis.data.setAll(data);
			series.data.setAll(data);

			/* bullet animations */
			var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
			cursor.lineX.set("visible", false);
			cursor.lineY.set("visible", false);

			cursor.events.on("cursormoved", function () {
				var dataItem = series.get("tooltip").dataItem;
				if (dataItem) {
					handleHover(dataItem);
				} else {
					handleOut();
				}
			});

			// Make stuff animate on load
			// https://www.amcharts.com/docs/v5/concepts/animations/
			series.appear(1000);
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
