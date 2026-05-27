<?php

print "</tbody></table>";

if($calculationResults) {
	print "
	<table class='fz-table'>
		$calculationResults
	</table>";
}

if($noDataFound) {
	print "<br /><p>$noDataFound</p>";
}

print "</div><!-- /.fz-list__body -->";
