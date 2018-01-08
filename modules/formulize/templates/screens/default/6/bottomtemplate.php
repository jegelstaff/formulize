<?php

print "</div>";//closing list-table div

$filter = array('ro_module_existing_or_new_course/**/Existing/**/=');
$ORfilter = "";
if(isset($_POST['search_ro_module_grad_undergrad']) AND $_POST['search_ro_module_grad_undergrad']) {
	$filter[] = 'ro_module_grad_undergrad/**/'.substr($_POST['search_ro_module_grad_undergrad'],1).'/**/=';
}
if(isset($_POST['search_ro_module_full_course_title']) AND $_POST['search_ro_module_full_course_title']) {
	$filter[] = 'ro_module_full_course_title/**/'.$_POST['search_ro_module_full_course_title'].'/**/LIKE';
}
if(isset($_POST['search_ro_module_program']) AND $_POST['search_ro_module_program']) {
	$programSearch = explode("_", $_POST['search_ro_module_program']); // qsf parts
	$filter[] = 'ro_module_program/**/'.$programSearch[2].'/**/=';
}
if(isset($_POST['search_ro_module_lecture_studio']) AND $_POST['search_ro_module_lecture_studio']) {
	$lectureStudio = explode("_", $_POST['search_ro_module_lecture_studio']);
	$filter[] = 'ro_module_lecture_studio/**/'.$lectureStudio[2].'/**/=';
}
if(isset($_POST['search_ro_module_year']) AND $_POST['search_ro_module_year']) {
	if(strstr($_POST['search_ro_module_year'], 'qsf_')) {
 		$yearParts = explode('_', $_POST['search_ro_module_year']); // split off annoying qsf parts, argh!
	}elseif(substr($_POST['search_ro_module_year'],0,1)=="!") {
    	$yearParts = array(2=>substr($_POST['search_ro_module_year'], 1, -1)); // remove ! !
	}
	$filter[] = 'ro_module_year/**/'.$yearParts[2].'/**/=';
}
$filter = implode("][",$filter);
if(isset($_POST['search_ro_module_semester']) AND $_POST['search_ro_module_semester']) {
	$ORfilter = str_replace("//", "/**/=][",trim(str_replace("OR=","ro_module_semester/**/",$_POST['search_ro_module_semester']),"/"));
}

//$weekViewData = getData(4,4,array(array('AND',$filter), array('OR',$ORfilter)));
/*foreach($weekViewData as $thisWeekViewDatum) {
	$entry_ids = internalRecordIds($thisWeekViewDatum, 4);
	$entry_id = $entry_ids[0];
	readSection($thisWeekViewDatum, $entry_id);	
	}*/

print "<script type='text/javascript' src='".XOOPS_URL."/libraries/overlib/overlib.js'></script>";
print "<div id='overDiv' style='position:absolute; visibility:hidden; z-index:1000;'></div>";
$defaultTable = isset($_POST['showtable']) ? strip_tags(htmlspecialchars($_POST['showtable'])) : 'list-table'; 
print "<input type='hidden' id='showtable' name='showtable' value='$defaultTable' />\n";

$instructorKeys = array();
$lecturesWithTutorials = array();
$details = array();

//$days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
//drawCalendar($days);

/*foreach($days as $day) {
    if(isset($GLOBALS['dara_times'][$day])) {
		drawCalendar(array($day));
    }
	}*/

?>

<style>
	#controls .tableheader {
        width: <?php print intval(100/count($drawnDays)); ?>%;
    }
    #controls .sectionblock {
        float: left;
        padding: 1em;
    }
    #controls .daytimeblock-cell {
        border: 1px solid black;        
    }
    #controls .timeslotdivider-cell {
		border: 0px solid black;
    }
    #controls .dayborder {
        border-left: 1px solid black;
    }
    #week {
        clear: both;
        display: table;
        border-spacing: 1em 0;
    }

    #controls .conflict {
        color: red;
    }
    
    #controls .tableheader, #controls .dayheader {
        text-align: center;
        font-weight: bold;
        padding-bottom: 1em;
    }
	#overDiv {
        background-color: white;
        border: 1px solid black;
        padding: 1em;
        font-size: 1em;
    }
	#controls .day-table, #week-table, #list-table, #new-table {
        display: none;
    }
	#tabnav {
        float: right;
    }
</style>
<?php
//end