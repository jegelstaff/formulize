<?php

// if the user requested to download calculations, draw the link for downloading calculations
if($downloadCalculationsURL AND $downloadCalculationsText) {
	print "
	<div id='exportlink' style='display: none;'>
		<center><p><a href='$downloadCalculationsURL' target='_blank'>$downloadCalculationsText</a></p></center>
	</div>";
}

// scrollbox always on in Anari
$scrollBoxClassOnOff = 'scrollbox';

// start the main table of entries
print "
<div class='$scrollBoxClassOnOff' id='formulize-list-of-entries'>
	<div class='list-of-entries-container'>
		<table class='outer'>
			";

		// draw the row of headers
		if($headersShown) {
			print drawHeaderRow($headers, $checkBoxesShown, $viewEntryLinksShown, $columnWidthStyle, $headingHelpAndLockShown, $lockedColumns, $numberOfInlineCustomButtons, $spacerNeeded);
		}

		// draw the row of search boxes
		if($searchesShown) {

            print "<tr>";

            // draw in the search help text if necessary, in the first column where the selection checkboxes and view entry links would be
            if($searchHelp OR $toggleSearches) {
                print "<td class='head' id='celladdress_1_margin'>$toggleSearches $searchHelp</td>";
            }

            // draw in a cell for the locked columns feature
            print "<td class='head floating-column' id='floatingcelladdress_1'></td>";

            // draw cells for all the search boxes
            // examples of search box variables: $quickSearchBox_quantity, $quickSearchFilter_ordertype
            foreach($columns as $columnNumber=>$elementHandle) {
                print "
                    <td $columnWidthStyle class='head column column$columnNumber' id='celladdress_1_$columnNumber'>
                        <div class='main-cell-div' id='cellcontents_1_$columnNumber'>
                            {${'quickSearch'.$searchTypes[$elementHandle].'_'.$elementHandle}}
                        </div>
                    </td>";
            }

            // add extra cells for each of the inline custom buttons, if any
            while($numberOfInlineCustomButtons > 0) {
                $numberOfInlineCustomButtons--;
                print "<td id='celladdress_1_$columnNumber' class=head>&nbsp;</td>";
                $columnNumber++;
            }

            // add a spacer column if necessary
            if($spacerNeeded) {
                print "<td class='head formulize-spacer'>&nbsp;</td>";
            }

            print "</tr>";

		}

		// show the buttons for interacting with calculations, if necessary
		if($modCalcsButton AND $cancelCalcsButton AND $toggleCalcsButton) {
			print "
			<tr>
				<td class=head colspan='$colspan'>$modCalcsButton&nbsp;&nbsp;$cancelCalcsButton&nbsp;&nbsp;$toggleCalcsButton</td>
			<tr>";
		}


// drawHeaderRow creates the HTML for displaying the headers at the top of a list of entries
// the Open List Template MUST have a function with this name and these arguments, if you intend to use the "repeat headers every X rows" feature
// $headers is an array of all the heading texts. The keys are the element handles.
// $checkBoxesShown is a boolean that indicates if the selection checkboxes are being shown to the user
// $viewEntryLinksShown is a boolean that indicates if the links to view an entry are being shown to the user
// $columnWidthStyle is a snippet of inline styling that sets a width for the column, if the user has set one
// $headingHelpAndLockShown is a boolean where that indicates if the the help and lock icons beside each header are being shown to the user
// $lockedColumns is an array of all columns that are currently locked by the user. The values are numbers indicating which columns are locked. Columns are numbered from 0 on the far left.
// $numberOfInlineCustomButtons is a number indicating if the number of inline custom buttons, so we can add columns to account for them
// $spacerNeeded is a boolean that indicates if we need to add something to take up space on the right side. Used so the browser will respect specific widths the user might have specified for columns
function drawHeaderRow($headers, $checkBoxesShown, $viewEntryLinksShown, $columnWidthStyle, $headingHelpAndLockShown, $lockedColumns, $numberOfInlineCustomButtons, $spacerNeeded) {

	// keep a persistent counter for which heading row we're drawing
	// since there can be more than one heading row per page, if headings are being repeated
	static $headingRowNumber = 0;
	$headingRowNumber++;

	// make an array of the cells we are going to draw, then draw them in a row
	$cells = array();

	// draw in a cell for the column with the selection checkboxes and view entry links
	if($checkBoxesShown OR $viewEntryLinksShown) {
		$cells[] = "<td class='head formulize-controls-head' id='celladdress_h$headingRowNumber"."_"."margin'>&nbsp;</td>";
	}

	// draw in a cell for the locked columns feature
	$cells[] = "<td class='head floating-column' id='floatingcelladdress_h$headingRowNumber'></td>";

	// draw the cells for each heading
	$columnNumber = 0;
	foreach($headers as $elementHandle=>$headingText) {
		$cell = "
				<th $columnWidthStyle class='head column column $columnNumber' id='celladdress_h$headingRowNumber"."_"."$columnNumber' scope='col' aria-sort='".getAriaSort($elementHandle)."'>
					<div class='main-cell-div' id='cellcontents_h$headingRowNumber"."_"."$columnNumber'>";
						if($headingHelpAndLockShown) {
							// NEEDS DEBUGGING - display of lock column doesn't work smoothly, icon doesn't show up when it should either?
							$lockColumnClass = in_array($columnNumber, $lockedColumns) ? 'lockcolumn heading-locked' : 'lockcolumn heading-unlocked';
							$cell .= "<a href='' id='lockcolumn_$columnNumber' class='$lockColumnClass' title='"._formulize_DE_FREEZECOLUMN."'></a>\n";
							$cell .= "<a href='' class='header-info-link' onclick='javascript:showPop(\"".XOOPS_URL."/modules/formulize/include/moreinfo.php?col=$elementHandle\");return false;' title='"._formulize_DE_MOREINFO."'></a>\n";
						}
						$cell .= clickableSortLink($elementHandle, printSmart(trans($headingText))); // create the clickable sort text, with icon if applicable
					$cell .= "
					</div>
				</th>";
		$cells[] = $cell;
		$columnNumber++;
	}

	// add extra cells for each of the inline custom buttons, if any
	while($numberOfInlineCustomButtons > 0) {
		$numberOfInlineCustomButtons--;
		$cells[] = "<td id='celladdress_h$headingRowNumber"."_"."$columnNumber' class=head>&nbsp;</td>";
        $columnNumber++;
	}

	// add a spacer column if necessary
	if($spacerNeeded) {
		$cells[] = "<td class='head formulize-spacer'>&nbsp;</td>";
	}

	// draw the cells in a row
    $row = "<tr>".implode("\n", $cells)."</tr>";

	return $row;
}

// clickableSortLink creates the HTML for the links users can click to control the sort order of a list of entries.
// $elementHandle is the element that we are creating a link for
// $clickableContent is the text or markup that will be displayed to the user and will be clickable
function clickableSortLink($elementHandle, $clickableContent) {

	// get current sorting element and order
	$sort = $_POST['sort'];
	$order = $_POST['order'];

	// setup containers for the clickable item
	$clickableSortLink = "
		<div style='padding-right:20px;'>
			<a style='display:flex;' href='' alt='"._formulize_DE_SORTTHISCOL."' title='"._formulize_DE_SORTTHISCOL."' onclick='javascript:sort_data(\"$elementHandle\");return false;'>
				<div>$clickableContent</div>
				<div style='min-width:15px; padding-left:5px;' aria-hidden='true'>";

					// if the element is the current sorting element, add an icon to show this
					if($elementHandle == $sort) {
						$iconClass = $order == "SORT_DESC" ? "fas fa-sort-amount-down" : "fas fa-sort-amount-up";
						$clickableSortLink .= "<i class='$iconClass'></i>";
					}

				// close the markup
				$clickableSortLink .= "
				</div>
			</a>
		</div>";

	return $clickableSortLink;
}

/**
 * Gets the aria sort value for the current element handle
 * @param string elementHandle - handle of the element being checked
 * @return string Returns the aria sort value
 */
function getAriaSort($elementHandle) {
	$sort = $_POST['sort'];
	$order = $_POST['order'];

	if ($sort == $elementHandle) {
		if ($order == 'SORT_ASC') {
			return 'ascending';
		}
		if ($order == 'SORT_DESC') {
			return 'descending';
		}
	}
	return 'none';
}

/**
 * Keep track of prior column contents and compare to what is passed in.
 * Return the class name 'same-contents-as-prior-cell' if appropriate.
 * @param string content - the cell contents
 * @param int columnNumber - the number of the column, starting from zero
 * @return string An empty string if content is different, or a css class name if content is the same
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
