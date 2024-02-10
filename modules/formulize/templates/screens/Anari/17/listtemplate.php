<?php

print "
<tr class='entry-row'>";
					
	// draw in the cell for the selection checkbox and view entry link if applicable
	if($viewEntryLink OR $selectionCheckbox) {
		print "
			<td class='head $class formulize-controls'>
				$selectionCheckbox $viewEntryLink
			</td>";
	} elseif($searchHelp OR $toggleSearches) {
        print "
            <td class='head $class formulize-controls'></td>";
    }
	
	// draw in a cell for the locked columns feature
	print "<td $columnWidthStyle class='$class floating-column' id='floatingcelladdress_$rowNumber'></td>";
						
	// draw in all the cells for the contents of this row
	foreach($columnContents as $columnNumber=>$columnContent) {
		$cellClass = $class." column column".$columnNumber;
		print "
			<td $columnWidthStyle class='$cellClass' id='celladdress_$rowNumber"."_"."$columnNumber'>
				".viewEntryLink('View').$columnContent."
			</td>";
	}
				
	// draw in a cell with each custom button
	foreach($customButtons as $customButton) {
		print "
			<td $columnWidthStyle class=$class>
				<center>$customButton</center>
			</td>";
	}
	
	// add a spacer column if necessary
	if($spacerNeeded) {
		print "<td class='$class formulize-spacer'>&nbsp;</td>";
	}						
					
print "
</tr>";