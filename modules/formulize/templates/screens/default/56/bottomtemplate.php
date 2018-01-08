<?php

global $adsListRows, $assignListRows;

ksort($adsListRows);
ksort($assignListRows);

if(count($assignListRows)>0) {
	print "<div id='tabnav' style='float: right;'>\n";
	print "<h2><a href='' class='navlink' target='ads-list'>Make Ads</a>&"."nbsp;&"."nbsp;&"."nbsp;<a href='' class='navlink' target='assign-list'>Assign Sessionals</a></h2>\n";
	print "</div>";
}

print "<h1>Course Sessional Management Module</h1>";

print "<p><b>Filter by term:</b> ".$quickFilterro_module_semester."</p><br><p><b>Filter by Program:</b> ".$quickSearchro_module_program."&"."nbsp;&"."nbsp;&"."nbsp;&"."nbsp;&"."nbsp;<span id='ads-list-button'>$makeAdsButton ";

include_once XOOPS_ROOT_PATH."/file_generation_code.php";
daraShowContractLinks(4, 7, "AD");
print "</span></p>";

include_once XOOPS_ROOT_PATH."/dara_helper_functions.php";
daraShowYearFilter($quickFilterro_module_year);

print "<hr>";

print "<table class='outer' id='ads-list' style='display: none;'>";
print "<tr><td class='head'></td><td class='head'>Course</td><td class='head'>Description</td><td class='head'>Duties</td><td class='head'>Qualifications</td></tr>";

foreach($adsListRows as $row) {
	print $row;
}

print "</table>";

if(count(assignListRows)>0) {

	print "<table class='outer' id='assign-list' style='display: none;'>";
	print "<tr><td class='head'>Course</td><td class='head'>Section</td><td class='head'>Times</td><td class='head'>Room</td><td class='head'>Instructor</td><td class='head'>Notes</td></tr>";
	
	foreach($assignListRows as $row) {
		print $row;
	}
	
	print "</table>";
}

print $saveButton;

$defaultPage = isset($_POST['showpage']) ? strip_tags(htmlspecialchars($_POST['showpage'])) : 'ads-list'; 
print "<input type='hidden' id='showpage' name='showpage' value='$defaultPage' />\n";


?>

<style>
	#controls td .even {
        background-color: #e6e6e6;
    }
</style>

<script type='text/javascript'>
 
    jQuery(document).ready(function() {
		
		jQuery(".navlink").click(function() {
			if(jQuery(this).attr('target') == 'ads-list') {
				jQuery('#ads-list-button').show();
			} else {
				jQuery('#ads-list-button').hide();
			}
           jQuery('#controls > table').hide();
           jQuery("#"+jQuery(this).attr('target')).show();
           jQuery("#showpage").val(jQuery(this).attr('target'));
           return false;
        });
		
		<?php
		if(!isset($_POST['showpage'])) {
            $_POST['showpage'] = 'ads-list';            
        }
		if($_POST['showpage'] != 'ads-list') {
			print "jQuery('#ads-list-button').hide();\n";
		}
        print "jQuery('#".strip_tags(htmlspecialchars($_POST['showpage']))."').show();\n";
		?>
		
	});
    
    
    
</script>