<?php

include_once XOOPS_ROOT_PATH."/file_generation_code.php";


?>

<style>
	
	#schedule {
		margin-left: 2em;
		float: left;
		padding: 0.5em;
		border: 1px solid black;
	}
	#addButton {
		float: left;	
	}
	#filter_semester, #update_button {
        padding-left: 2em;
        float: left;
    }
    
    #filter_rest {
        float: left;
    }
	
</style>

<?php

/*print "<div id='tabnav'>\n";
print "<h2><a href='' class='calnavlink'
target='week-table'>Week View</a>&"."nbsp;&"."nbsp;&"."nbsp;<a href='' class='calnavlink' target='list-table'>List View</a></h2>\n";
print "</div>";*/

print "<div style='float: right;'>$currentViewList</div>";
print "<h1>".$screen->getVar('title')."</h1>";
print "<div id='addButton'>$addButton</div>";
print "<div style='clear: both;'></div>";
$semSearch = isset($_POST['search_ro_module_semester']) ? $_POST['search_ro_module_semester'] : '';
print "<input type=hidden name=search_ro_module_semester value='$semSearch'>";
print "<div id='filter_rest'>\n";
$gradSelect = (isset($_POST['search_ro_module_grad_undergrad']) AND $_POST['search_ro_module_grad_undergrad']=='=Graduate') ? 'selected' : '';
$underGradSelect = (isset($_POST['search_ro_module_grad_undergrad']) AND $_POST['search_ro_module_grad_undergrad']=='=Undergraduate') ? 'selected' : '';
print "<h2>Filter by grad/undergrad: <select name=search_ro_module_grad_undergrad onchange='showLoading();'><option value=''>Any</option><option value='=Graduate' $gradSelect>Graduate</option><option value='=Undergraduate' $underGradSelect>Undergraduate</option></select></h2>";
print "<h2>Filter by course code/title: $quickSearchro_module_full_course_title</h2>";
print "<h2>Filter by program: $quickFilterro_module_program</h2>";
print "<h2>Filter by teaching method: $quickFilterro_module_lecture_studio</h2>";
print "<h2>Filter by 'offered' status: $quickFilterro_module_course_active</h2>";
print "<h2>Filter by 'confirmation' status: $quickFilterro_module_confirmation_status</h2>";

print "</div>";

print "<div id='filter_semester'>";

print "<h2>Filter for courses in these semesters:</h2>\n";
$element_handler = xoops_getmodulehandler('elements', 'formulize');
$semesterElement = $element_handler->get('ro_module_semester');
$semesters = array_keys($semesterElement->getVar('ele_value'));
$allChecked = (!isset($_POST['search_ro_module_semester']) OR $_POST['search_ro_module_semester'] == '') ? 'checked' : '';
print "<input type='checkbox' id='all-sem' class='allsem' name='sem' value='' $allChecked> <label for='all-sem'>All</label><br>\n";     
foreach($semesters as $sem) {
    $checked = (isset($_POST['search_ro_module_semester']) AND strstr($_POST['search_ro_module_semester'], $sem)) ? 'checked' : '';
    print "<input type='checkbox' id='$sem' class='sem' name='sem' value='$sem' $checked> <label for='$sem'>$sem</label><br>\n";     
}
print "</div>";

print "<div id='update_button'><input type='button' id='formulize_saveButton' class='formulize_button' value='Update' onclick='jQuery(\"#scrollx\").val(jQuery(window).scrollTop());showLoading();' /></div>";

print "<div id='schedule'>$scheduleButton";
daraShowContractLinks(3, 1, "RO");

if(strstr($_POST['search_ro_module_year'], 'qsf_')) {
    $yearParts = explode('_', $_POST['search_ro_module_year']); // split off annoying qsf parts, argh!
}elseif(substr($_POST['search_ro_module_year'],0,1)=="!") {
    $yearParts = array(2=>substr($_POST['search_ro_module_year'], 1, -1)); // remove ! !
}else{
    $yearParts = array(2=>$_POST['search_ro_module_year']); // normal, like coming back from a form submission or something
}

$availableLocks = getData('', 22, 'lock_dates_year/**/'.$yearParts[2].'/**/=');

print "<br>Compare with lock date: <select name='compareDate'><option value=''>None</option>";
foreach($availableLocks as $i=>$thisLock) {
	$lockEntryIds = internalRecordIds($thisLock, 22);
	print "<option value=".$lockEntryIds[0].">".display($thisLock, 'lock_dates_date')."</option>";
}
print "</select>";

print "<br>Show Coordinators? <input type='radio' name='showCoords' value='Yes' checked> Yes&"."nbsp;&"."nbsp;&"."nbsp;<input type='radio' name='showCoords' value=''> No
<br>Show Tentative Instr.? <input type='radio' name='showTentInst' value='Yes'> Yes&"."nbsp;&"."nbsp;&"."nbsp;<input type='radio' name='showTentInst' value='' checked> No</div>";


print "<div style='clear: both;'></div>";

include_once XOOPS_ROOT_PATH."/dara_helper_functions.php";
$locked = daraShowYearFilter($quickFilterro_module_year);





?>

<script>
	
	jQuery('.typeradio').change(function() {
		jQuery('input[name=search_ro_module_grad_undergrad]').val(jQuery(this).val());
		showLoading();
	});
	jQuery('.termradio').change(function() {
		jQuery('input[name=search_ro_module_semester]').val(jQuery(this).val());
		showLoading();
	});

	jQuery(document).ready(function() {
		
		jQuery('.sem').click(function() {
            var currentSearch = jQuery("[name='search_ro_module_semester']").val();
            var clickedTerm = "OR="+jQuery(this).val()+"//";
            if(jQuery(this).attr('checked')) {
                jQuery("[name='search_ro_module_semester']").val(currentSearch+clickedTerm);
				jQuery('.allsem').removeAttr('checked');
            } else {
                jQuery("[name='search_ro_module_semester']").val(currentSearch.replace(clickedTerm,''));
            }
        });
		
		jQuery('.allsem').click(function() {
			if(jQuery(this).attr('checked')) {
				jQuery("[name='search_ro_module_semester']").val('');
				jQuery('.sem').each(function() {
					jQuery(this).removeAttr('checked');
				});
			} 
		});
		
		jQuery(".calnavlink").click(function() {
           jQuery('#controls > table').hide();
		   jQuery('#list-table').hide();
           jQuery("#"+jQuery(this).attr('target')).show();
           jQuery("#showtable").val(jQuery(this).attr('target'));
           return false;
        });
		
		<?php
        if(!isset($_POST['showtable'])) {
            $_POST['showtable'] = 'list-table';            
        }
        print "jQuery('#".strip_tags(htmlspecialchars($_POST['showtable']))."').show();\n";
		?>
		
	});
	
	
	
</script>

<?php
print "<div id='list-table'>";