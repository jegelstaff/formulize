<?php

// close the table opened in the open list template
print "
</table>";

// print the calculation results
// currently they are prepared in a series of two column table rows, so you must wrap them in table tags :(
if($calculationResults) {
	print "
	<table class='outer'>
		$calculationResults
	</table>";
}

if($noDataFound) {
	print "<br /><p>$noDataFound</p>";
}

// close the divs opened in the open list template
print "
</div> <!-- close list-of-entries-container -->
</div> <!-- close formulize-list-of-entries -->";