<?php

print "<script>
jQuery(document).ready(function() {\n";

// remove archived years from the contracts drop down
$yearData = getData('',21);
foreach($yearData as $thisYear) {
    $status = display($thisYear, 'master_year_list_status');
    if($status == 'Archived') {
		print "jQuery('#contractYear option[value=\"".display($thisYear, 'master_year_list_year')."\"]').remove();\n";
	}
}

print "});\n</script>\n";