<?php

include_once XOOPS_ROOT_PATH."/modules/formulize/include/elementdisplay.php";
include_once XOOPS_ROOT_PATH."/dara_helper_functions.php";

$yearParts = strstr($_POST['search_ro_module_year'], 'qsf_') ? explode('_', $_POST['search_ro_module_year']) : array(2=>$_POST['search_ro_module_year']); // split off annoying qsf parts, argh!
$year = $yearParts[2];
$yearData = getData('',21,'master_year_list_year/**/'.$year.'/**/=');
$yearStatus = display($yearData[0], 'master_year_list_status');
$activeYear = $yearStatus != 'Active' ? false : true;
$locked = $yearStatus == 'Locked' ? " <span style='color: red;'>🔒 Locked!</span>" : "";
$archived = $yearStatus == 'Archived' ? " <span style='color: red;'>Archived!</span>" : "";

// Start text
print "<div id='tabnav'>\n";

print "<h2><a href='' class='calnavlink'
target='week-table'>Week View</a>&"."nbsp;&"."nbsp;&"."nbsp;<a href='' class='calnavlink' target='list-table'>List View</a>&"."nbsp;&"."nbsp;&"."nbsp;<a href='' class='calnavlink' target='new-table'>New Course/Curriculum Dev</a></h2>\n";

print "</div>";

print "<h1>".strip_tags(htmlspecialchars($_GET['program']))." Module</h1>";
print "<div class='clearboth'></div>";
print "<hr>";

print "<div id='filter_rest'>\n";
print "<h2>Filter by year: $quickFilterro_module_year$locked$archived</h2>\n";
print "<h2>Filter by program: $quickFilterro_module_program</h2>\n";
print "<h2>Filter by building/room: $quickSearchsections_practica_room</h2>\n";
print "<h2>Filter by course code or title: ".$quickSearchro_module_full_course_title."</h2>\n";
print "<h2>Filter by instructor: ".$quickSearchinstr_assignments_instructor."</h2>\n";

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



// floating save button
print "<input type='hidden' id='scrollx' name='scrollx' value='' />\n";
print "<div id='update_button'><input type='button' id='formulize_saveButton' class='formulize_button' value='Update' onclick='jQuery(\"#scrollx\").val(jQuery(window).scrollTop());showLoading();' /></div>";
print "<div class='clearboth'></div>";
print "<hr><br>\n";


// used in the New Curriculum Development page
function printCourse($courseData, $sectionData="") {
    
	global $dara_active_year;
	
    if(!$sectionData) {
        $sectionData = $courseData['sections'];
    } 
    
    print "<div class='newcourse'>\n";
    print "<h3>{$courseData['title']}</h3>\n";
    
    foreach($sectionData as $section) {
        $entry_id = $section['entry_id'];
        print "<p>{$courseData['semester']}</p>\n";
        $section['weighting'] += isset($section['has_tutorial']) ? $section['has_tutorial'] : 0; // add any tutorial weighting
        $tutorialText = $section['has_tutorial'] ? " (includes tutorial)" : "";
        print "<p>Section weighting: ".number_format($section['weighting'],3).$tutorialText."</p>\n";
        print "<p>Instructor(s):<br>\n";
		if($dara_active_year AND userCanAssignToProgram($courseData['program'])) {
	        foreach($section['instr_assign_ids'] as $inst_assign_id) {
    	        if($inst_assign_id) {
        	        drawInstructorBox('instr_assignments_instructor',$inst_assign_id,$entry_id);
            	}
        	}
        	drawInstructorBox('instr_assignments_instructor',"new",$entry_id);
	        print "<br><p>Notes:<br>";
    	    displayElement('', 'course_components_program_director_notes', $entry_id);
        	print "</p>";
		} else {
			foreach($section['instructors'] as $instructor) {
				print $instructor.'<br>';	
			}
			print "<br><p>Notes<br>".$section['notes']."</p>";
		}
    }
    print "</div>";
    
}

// draws a list of courses, should be called after the calendars have been drawn, since we rely on the details having be gathered already!!
function drawList($lecturesWithTutorials, $details, $instructorKeys) {
    
    $headers = array(
        'Course'=>array('courseData'=>'title', 'handle'=>'sections_practica_course_code'),
        'Section'=>array('sectionData'=>'number', 'handle'=>'sections_section_number'),
        'Type'=>array('sectionData'=>'type', 'handle'=>'component_type'),
        'Semester'=>array('courseData'=>'semester', 'handle'=>'ro_module_semester'),
        'Day'=>array('sectionData'=>'days', 'handle'=>'section_times_day'),
        'Start Time'=>array('sectionData'=>'starts', 'handle'=>'section_times_start_time'),
        'End Time'=>array('sectionData'=>'ends', 'handle'=>'section_times_end_time'),
        'Room'=>array('sectionData'=>'room', 'handle'=>'sections_practica_room'),
        'Estimated<br>Enrollment'=>array('sectionData'=>'enrollment', 'handle'=>'sections_estimated_enrollment'),
        'Instructor(s)'=>array('sectionData'=>'instructors', 'handle'=>'instr_assignments_instructor'),
		'Notes'=>array('sectionData'=>'notes', 'handle'=>'course_components_program_director_notes')
	);
    print "<table id='list-table'>\n";
    $rowType = ' even';
	$rowCount = -1; // special start value for first header row
	ksort($GLOBALS['dara_sort']);
    foreach($GLOBALS['dara_sort'] as $metaData) {
		
		if($rowCount == -1 OR $rowCount == 10) {
			print "<tr>";
    		foreach($headers as $label=>$schema) {
				$headerText = isset($schema['handle']) ? clickableSortLink($schema['handle'], $label) : $label;
				print "<td class='listheader'>".$headerText."</td>\n";
    		}
			print "</tr>";
			$rowCount = 0; // reset, so we can start counting if we've done 10 rows yet or not
		}
		
        $title = $metaData[0];
		$conflictText = $metaData[2];
        $code = $metaData[3];
		$year = $metaData[4];
        $courseData = $GLOBALS['dara_course'][$year][$code];
        if($courseData['type']!='Existing') { continue; }
        $sectionNumber = $metaData[1];
        $sectionData = $courseData['sections'][$sectionNumber];
        if($sectionData['type']=='Tutorial') { continue; }
        print "<tr>";
        foreach($headers as $label=>$schema) {
            $source = key($schema);
            $value = ${$source}[$schema[$source]];
            if(is_array($value)) {
                $value = implode("<br>", $value);
            }
            if($label == 'Course') { // put the coordinator below, if there is one
                if(countNumberOfSections($courseData)>1 AND userCanAssignToProgram($courseData['program'])) {
                    $coordinator = $courseData['coordinator'] ? $courseData['coordinator'] : "Assign a Coordinator";
					$value .= "<br><a href='' target='".str_replace("/","",$year.$code.$sectionNumber)."' class='details-link' onclick='return false;'>Coordinator: $coordinator</a>";
					if(!isset($details[$year.$code.$sectionNumber])) {
						list($details, $instructorKeys) = prepDetails(array('title'=>$courseData['title'], 'code'=>$code, 'section'=>$sectionNumber, 'year'=>$year), $year.$code.$sectionNumber, $details, $instructorKeys);
					}
				} elseif(countNumberOfSections($courseData)>1) {
					$value .= "<br>Coordinator: ".$courseData['coordinator'];
				}
            }
            if($label == 'Instructor(s)') {
				
                if(!$value AND userCanAssignToProgram($courseData['program'])) { $value = "Assign an Instructor"; }
				if(userCanAssignToProgram($courseData['program'])) {
					$value = "<div class='instructor-link'><a href='' target='".str_replace("/","",$year.$code.$sectionNumber)."' class='details-link' onclick='return false;'>$value</a></div>".$conflictText;
					if(!isset($details[$year.$code.$sectionNumber])) {
						list($details, $instructorKeys) = prepDetails(array('title'=>$courseData['title'], 'code'=>$code, 'section'=>$sectionNumber, 'year'=>$year), $year.$code.$sectionNumber, $details, $instructorKeys);
					}	
				} else {
					$value = $value.$conflictText;
				}
				if($sectionData['prior_instructors']) {
					$value .= "<br><br>Prior Instructor(s):<br><ul>".$sectionData['prior_instructors']."</ul>";
				}
            }
            $numericClass = is_numeric($value) ? ' numeric' : '';
            print "<td class='listcell$rowType$numericClass'>".$value."</td>\n";
        }
        $rowType = $rowType == ' even' ? ' odd' : ' even';
		$rowCount++;
        print "</tr>";
    }
    print "</table>";
	return array($details, $instructorKeys);
}