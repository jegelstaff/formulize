<?php

print "<tr class='entry-row' aria-selected='false'>";

	// the margin column is there only when the list has checkboxes or view entry links, the same as in the header and filter rows (openlisttemplate.php), so the columns line up
	// a row can still have nothing in it, such as an entry the user can't delete when there are no view entry links
	if($checkBoxesShown OR $viewEntryLinksShown) {
		print "
			<td class='lyris-cb'>
				$selectionCheckbox $viewEntryLink
			</td>";
	}

	foreach($columnContents as $columnNumber=>$columnContent) {
		$sameClass = checkIfContentIsTheSameAsPrior($columnContent, $columnNumber);
		print "
			<td $columnWidthStyle class='column column$columnNumber $class $sameClass' id='celladdress_$rowNumber"."_"."$columnNumber'>
				$columnContent
			</td>";
	}

	foreach($customButtons as $customButton) {
		print "
			<td $columnWidthStyle class='$class'>
				<center>$customButton</center>
			</td>";
	}

	if($spacerNeeded) {
		print "<td class='formulize-spacer'>&nbsp;</td>";
	}

print "</tr>";
