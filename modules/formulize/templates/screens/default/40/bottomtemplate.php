<?php

global $dara_active_year;

print "<script type='text/javascript' src='".XOOPS_URL."/libraries/overlib/overlib.js'></script>";
print "<div id='overDiv' style='position:absolute; visibility:hidden; z-index:1000;'></div>";
$defaultTable = isset($_POST['showtable']) ? strip_tags(htmlspecialchars($_POST['showtable'])) : 'list-table'; 
print "<input type='hidden' id='showtable' name='showtable' value='$defaultTable' />\n";

$instructorKeys = array();
$lecturesWithTutorials = array();
$details = array();

$days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
list($lecturesWithTutorials, $details, $instructorKeys, $drawnDays) = drawCalendar($days, $lecturesWithTutorials, $details, $instructorKeys);

foreach($days as $day) {
    if(isset($GLOBALS['dara_times'][$day])) {
        drawCalendar(array($day), $lecturesWithTutorials, $details, $instructorKeys);
    }
}

list($details, $instructorKeys) = drawList($lecturesWithTutorials, $details, $instructorKeys);

// draw all the details boxes
foreach($details as $id=>$detail) {
    if($detail['title'] AND $detail['contents']) {
		print "<div id='details-box-".str_replace("/","",$id)."' class='details-box'><div id='details-box-".$id."-contents'><a href='' boxkey='".str_replace("/","",$id)."' class='close-link'>Close</a><h2>".$detail['title']."</h2>".$detail['contents']."$saveButton</div></div>\n";
    }
}

$lecturesWithTutorials = array_unique($lecturesWithTutorials);
foreach($lecturesWithTutorials as $entry_id) {
    $instructorKeys[$entry_id] = array_unique($instructorKeys[$entry_id]);
    print "<input type='hidden' name='instructor-assignments-for-lectures[$entry_id]' value='".implode(',',$instructorKeys[$entry_id])."' />\n";
}


print "<table id='new-table'><tr><td>\n";

if(userCanAssignToProgram($_GET['program']) AND $dara_active_year) {

    
    
    print "<div class='newcourse'>\n";
    print "<h2>New Course and Curriculum Development</h2>\n";
    print "</div>\n";
    print "<div class='newcourse'>$saveButton</div>\n";
    
    print "<div id='newcourse_options'>";
    
    print "<input type='hidden' id='new' name='course_proposed_or_new' value='New' />\n";
    print "<input type='hidden' id='eight' name='course_weight' value=3 />\n"; // third option is the 0.5 FCE option, this is parsed in prepDataForWrite
    print "<input type='hidden' id='section' name='section_number' value='NCCD' />\n";
    
    print "<br><p>Course Title:<br> <input type='text' name='course_title' /><br>\n";
    print "<br>Course Number:<br> <input type='text' name='course_number' /><br>\n";

    // hard coded type to remove Thesis Advising    
    print "<br>Type: <br><input type='radio' id='course_type-1' name='course_type' value='1' onchange=\"javascript:formulizechanged=1;\" /><label for='course_type-1'>Lecture</label>
        <br /><input type='radio' id='course_type-2' name='course_type' value='2' onchange=\"javascript:formulizechanged=1;\" /><label for='course_type-2'>Practical/Studio</label>";

    print "<br>\n<br>Semester: <br>";
    displayElement("", 'ro_module_semester', 'new', 'course_semester');
    print "</p><br>\n";
    
    print $newCourseButton;
    print "</div>";
}

$yearParts = strstr($_POST['search_ro_module_year'], 'qsf_') ? explode('_', $_POST['search_ro_module_year']) : array(2=>$_POST['search_ro_module_year']); // split off annoying qsf parts, argh!
$year = $yearParts[2];
foreach($GLOBALS['dara_course'][$year] as $code=>$courseData) {
    if($courseData['type'] != 'Existing') {
       printCourse($GLOBALS['dara_course'][$year][$code]); // need to use alternative output
    }
}

print "</td></tr></table>\n";

?>

<style>

    
    #controls li {
        float: none !important;
        list-style: inside disc;
    }
    
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
	#controls .timestamp-cell {
		width: 1%;
		text-align: center;
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
    #controls .listheader {
        text-align: left;
        font-weight: bold;
        padding-bottom: 1em;

    }
    
    #controls .day-table, #week-table, #list-table, #new-table {
        display: none;
    }
    
    #update_button {
        float: left;
        padding-left: 1em;
    }
    
    .floating {
        position: fixed;
        top: 1em;
        z-index: 100;
    }

    
    #newcourse_options {
        clear: both;
        float: left;
        margin-right: 2em;
        padding-right: 2em;
        border-right: 1px solid black;
    }
    
    .newcourse {
        float: left;
        padding-right: 2em;
    }
    
    #filter_semester {
        padding-left: 1em;
        float: left;
    }
    
    #filter_rest {
        float: left;
    }

    #overDiv {
        background-color: white;
        border: 1px solid black;
        padding: 1em;
        font-size: 1em;
    }
    
    .details-box {
        display: none;
        position: absolute;
        border: 1px solid black;
        background-color: white;
        padding: 1em;
    }
    
    #controls td .even {
        background-color: #e6e6e6;
    }
    
    .numeric {
        text-align: right;
    }
    
    #list-table td {
        padding: 0.5em;
    }
    
    #tabnav {
        float: right;
    }
    
    .clearboth {
        clear: both;
    }
	.required-section {
		color: blue;			
	}
</style>

<?php

// gather all the instructor loads


?>



<script type='text/javascript'>
    
	function enableTextbox(id) {
		jQuery('#decue_'+id).attr('name','decue_'+id);
	}
	
	function updateInstructor(targetKey) {
		var newInst = '';
		jQuery("#instructor-list-"+targetKey+" option:selected").each(function() {
			optionText = jQuery(this).text();
			if(optionText != 'Choose an option') {
				if(optionText.indexOf(".")>1) {
					newInst = newInst+optionText.replace(' - avail. load: ','(')+')<br />';
				} else {
					newInst = newInst+optionText+'<br />';
				}
			}
		});
		//alert(jQuery(".instructor-link a[target='"+targetKey+"']").html());
		//alert(newInst.substring(0,(newInst.length-6)));
		if(jQuery(".instructor-link a[target='"+targetKey+"']").html() != newInst.substring(0,(newInst.length-6))) {
			if(newInst == '') {
				newInst = 'Assign an Instructor';
			} else {
				newInst = newInst+"<span style='color: red'>UNSAVED! Click 'UPDATE' To Save.</span>";
			}
			jQuery(".instructor-link a[target='"+targetKey+"']").html(newInst); //newInst.substring(0,(newInst.length-6)));	
		}
	}
	
	
    jQuery(document).ready(function() {
		
		// neuter all elements, and then selectively reenable when something is changed
        jQuery("input[name^='decue_']").each(function() {
            jQuery(this).attr('name','0509'+jQuery(this).attr('name'));
        });
		// reenable instructor selection, and hidden section fk elements
        jQuery("select[name^='de_15']").change(function() {
            var idstem = jQuery(this).attr('id').replace('de_','');
            var sectionstem = idstem.replace('134','133');
            jQuery('#decue_'+idstem).attr('name','decue_'+idstem);
            jQuery('#decue_'+sectionstem).attr('name','decue_'+sectionstem)
        });
		// reenable coordinatorship elements
		jQuery("select[name^='de_3']").change(function() {
            var idstem = jQuery(this).attr('id').replace('de_','');
            jQuery('#decue_'+idstem).attr('name','decue_'+idstem);
        });
		// reenable notes elements
		jQuery("textarea[name^='de_4']").each(function() {
			var id = "\'"+jQuery(this).attr('id').replace('de_','')+"\'";
			jQuery(this).attr('onchange','javascript:enableTextbox('+id.replace('_tarea','')+')');
		});

        var offset = jQuery('#floating_button').offset();
        jQuery(window).scroll(function () {
            var scrollTop = jQuery(window).scrollTop();
            if (offset && offset.top<scrollTop) {
              jQuery('#floating_button').addClass('floating');
            } else {
              jQuery('#floating_button').removeClass('floating');
            };
        });
        
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
    
		// make sure any copies of the coordinatorship elements are kept in sync
        jQuery("select[name^='de_']").change(function() {
            var targetname = jQuery(this).attr('name');
            jQuery("select[name='"+targetname+"']").val(jQuery(this).val());
        });

        jQuery(".close-link").click(function() {
            jQuery("#details-box-"+jQuery(this).attr('boxkey')).hide();
			var targetKey = jQuery(this).attr('boxkey');
			updateInstructor(targetKey);
            return false;
        });			
			
        jQuery(".details-link").click(function(e) {
            jQuery(".details-box").hide();
			var thisBox = "#details-box-"+jQuery(this).attr("target");
            jQuery(thisBox).css('top',e.pageY+'px');
            jQuery(thisBox).css('left',e.pageX+'px');
            var yTranslate = '-50%';
            var xTranslate = '0%';
            if (e.pageX>(window.innerWidth*0.75)) {
                xTranslate = '-75%';
            }
            jQuery(thisBox).css('transform', 'translate('+xTranslate+','+yTranslate+')');                
            jQuery(thisBox).toggle();
            return false;
        });

        jQuery(".calnavlink").click(function() {
           jQuery('#controls > table').hide();
           jQuery("#"+jQuery(this).attr('target')).show();
           jQuery("#showtable").val(jQuery(this).attr('target'));
           return false;
        });
		
		// remove the any year option
		jQuery("#search_ro_module_year option[value='']").remove();
		
        
        <?php
        if($_POST['scrollx']) {
            print "jQuery(window).scrollTop(".intval($_POST['scrollx']).");\n";
        }
        if(!isset($_POST['showtable'])) {
            $_POST['showtable'] = 'list-table';            
        }
        print "jQuery('#".strip_tags(htmlspecialchars($_POST['showtable']))."').show();\n";
		
		// setup the loads for adding to the instructor selectboxes
		global $xoopsDB;
		if(strstr($_POST['search_ro_module_year'], "_")) {
			$yearSearchParts = explode("_",$_POST['search_ro_module_year']);
			$year = $xoopsDB->escape($yearSearchParts[2]); // quick search boxes have this odd extra text in them...some legacy bug/reason?
		} else {
			$year = $xoopsDB->escape($_POST['search_ro_module_year']);
		}
		$loadSQL = "SELECT l.hr_teaching_loads_available_teaching_loa as tload, h.hr_module_name as name FROM ".$xoopsDB->prefix('formulize_hr_teaching_loads')." AS l LEFT JOIN  ".$xoopsDB->prefix('formulize_hr_module')." AS h ON l.hr_teaching_loads_instructor = h.entry_id WHERE l.hr_teaching_loads_year = '$year'";
		$res = $xoopsDB->query($loadSQL);
		print "var loads = {\n";
		while($array = $xoopsDB->fetchArray($res)) {
			if($array['tload']) {
				print "'".$array['name']."':' - avail. load: ".$array['tload']."',\n";
			} 
		}
		print "};\n";
        ?>
			
		// replace instructor names with their names plus available loads
		jQuery("select[name^='de_'] option").each(function() {
			if(typeof loads[jQuery(this).text()] != 'undefined') {
				jQuery(this).text(jQuery(this).text()+' '+loads[jQuery(this).text()]);					
			}
		});
		
    });
    
    
    
</script>