<?php

print "</tbody></table>";

if($calculationResults) {
	print "
	<table class='lyris-list-table'>
		$calculationResults
	</table>";
}

if($noDataFound) {
	print "<div class='fz-empty lyris-list__empty'><p class='fz-empty__title'>$noDataFound</p></div>";
}

print "</div><!-- /.lyris-list__body -->";
