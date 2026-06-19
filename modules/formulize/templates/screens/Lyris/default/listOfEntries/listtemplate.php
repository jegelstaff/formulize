<?php

print "<tr class='entry-row' aria-selected='false'>";

	if($viewEntryLink OR $selectionCheckbox) {
		print "
			<td class='fz-cb'>
				$selectionCheckbox $viewEntryLink
			</td>";
	} elseif($searchHelp OR $toggleSearches) {
		print "
			<td class='fz-cb'></td>";
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
