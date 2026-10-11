<?php

print "</tbody></table>";

// The calculation results are table rows, two cells each: what was calculated, and its results.
// They get a table of their own, not lyris-list-table, whose rules for the entries (one line
// to a cell, headings that stay put) would reach the tables of results inside the cells.
if($calculationResults) {
	print "
	<table class='lyris-calc-table'>
		$calculationResults
	</table>";
}

if($noDataFound) {
	print "<div class='fz-empty lyris-list__empty'><p class='fz-empty__title'>$noDataFound</p></div>";
}

print "</div><!-- /.lyris-list__body -->";
