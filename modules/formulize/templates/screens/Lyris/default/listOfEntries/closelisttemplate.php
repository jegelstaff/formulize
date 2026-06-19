<?php

print "</tbody></table>";

if($calculationResults) {
	print "
	<table class='fz-table'>
		$calculationResults
	</table>";
}

if($noDataFound) {
	print "<p class='fz-table-empty'>$noDataFound</p>";
}

print "</div><!-- /.fz-list__body -->";
