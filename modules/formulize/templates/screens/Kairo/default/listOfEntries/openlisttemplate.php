<?php

if($downloadCalculationsURL AND $downloadCalculationsText) {
	print "
	<div id='exportlink' style='display: none;'>
		<center><p><a href='$downloadCalculationsURL' target='_blank'>$downloadCalculationsText</a></p></center>
	</div>";
}

print "
<div class='fz-list__body' id='formulize-list-of-entries'>
	<table class='fz-table fz-table--cozy'>
	<thead>";

		if($headersShown) {
			print drawHeaderRow($headers, $checkBoxesShown, $viewEntryLinksShown, $columnWidthStyle, $headingHelpAndLockShown, $lockedColumns, $numberOfInlineCustomButtons, $spacerNeeded);
		}

		if($searchesShown) {

			print "<tr>";

			if($searchHelp OR $toggleSearches) {
				print "<td class='fz-cb head' id='celladdress_1_margin'>$toggleSearches $searchHelp</td>";
			}

			foreach($columns as $columnNumber=>$elementHandle) {
				print "
					<td class='head column column$columnNumber' id='celladdress_1_$columnNumber'>
						<div class='main-cell-div' id='cellcontents_1_$columnNumber'>
							{${'quickSearch'.$searchTypes[$elementHandle].'_'.$elementHandle}}
						</div>
					</td>";
			}

			while($numberOfInlineCustomButtons > 0) {
				$numberOfInlineCustomButtons--;
				print "<td id='celladdress_1_$columnNumber' class='head'>&nbsp;</td>";
				$columnNumber++;
			}

			if($spacerNeeded) {
				print "<td class='head formulize-spacer'>&nbsp;</td>";
			}

			print "</tr>";

		}

		if($modCalcsButton AND $cancelCalcsButton AND $toggleCalcsButton) {
			print "
			<tr>
				<td class='head' colspan='$colspan'>$modCalcsButton&nbsp;&nbsp;$cancelCalcsButton&nbsp;&nbsp;$toggleCalcsButton</td>
			<tr>";
		}

print "</thead><tbody>";


// drawHeaderRow creates the HTML for the sticky column headers.
// The Open List Template MUST have a function with this name and these arguments
// if you intend to use the "repeat headers every X rows" feature.
function drawHeaderRow($headers, $checkBoxesShown, $viewEntryLinksShown, $columnWidthStyle, $headingHelpAndLockShown, $lockedColumns, $numberOfInlineCustomButtons, $spacerNeeded) {

	static $headingRowNumber = 0;
	$headingRowNumber++;

	$cells = array();

	if($checkBoxesShown OR $viewEntryLinksShown) {
		$cells[] = "<th class='fz-cb' id='celladdress_h$headingRowNumber"."_margin'></th>";
	}

	$columnNumber = 0;
	foreach($headers as $elementHandle=>$headingText) {
		$cell = "<th $columnWidthStyle class='column column$columnNumber' id='celladdress_h$headingRowNumber"."_"."$columnNumber' scope='col' aria-sort='".getAriaSort($elementHandle)."'>"
			. clickableSortLink($elementHandle, trans($headingText))
			. "</th>";
		$cells[] = $cell;
		$columnNumber++;
	}

	while($numberOfInlineCustomButtons > 0) {
		$numberOfInlineCustomButtons--;
		$cells[] = "<th id='celladdress_h$headingRowNumber"."_"."$columnNumber' class='head'></th>";
        $columnNumber++;
	}

	if($spacerNeeded) {
		$cells[] = "<th class='formulize-spacer'></th>";
	}

	return "<tr>".implode("\n", $cells)."</tr>";
}

/**
 * Generate the markup for a clickable sort link in a column header.
 * @param string $elementHandle
 * @param string $clickableContent
 * @return string
 */
function clickableSortLink($elementHandle, $clickableContent) {

	list($title, $icon) = getSortTitleAndIcon($elementHandle);

	return "<a class='fz-th-sort' href='' title='" . htmlspecialchars($title) . "' onclick='sort_data(\"$elementHandle\", event.shiftKey); return false;'>"
		. $clickableContent
		. "<span aria-hidden='true'>$icon</span>"
		. "</a>";
}

/**
 * Keep track of prior column contents; return 'same-contents-as-prior-cell' if repeated.
 * @param string $content
 * @param int $columnNumber
 * @return string
 */
function checkIfContentIsTheSameAsPrior($content, $columnNumber) {
	static $contents = array();
	$class = '';
	$cleanContents = trim(strip_tags($content));
	if(isset($contents[$columnNumber]) AND $cleanContents === $contents[$columnNumber]) {
		$class = 'same-contents-as-prior-cell';
	} else {
		$contents[$columnNumber] = $cleanContents;
	}
	return $class;
}
