<?php

print "</tbody></table>";

if($calculationResults) {
	print "
	<table class='lyris-list-table'>
		$calculationResults
	</table>";
}

if($noDataFound) {
	print "<p class='lyris-table-empty'>$noDataFound</p>";
}

print "</div><!-- /.lyris-list__body -->";
