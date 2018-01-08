<?php

global $dara_active_year;

print "<script type='text/javascript' src='".XOOPS_URL."/libraries/overlib/overlib.js'></script>";
print "<div id='overDiv' style='position:absolute; visibility:hidden; z-index:1000;'></div>";
$defaultTable = isset($_POST['showtable']) ? strip_tags(htmlspecialchars($_POST['showtable'])) : 'week-table'; 
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

?>

<style>

	@media print
	{    
		#xo-header, #xo-footer, #week-table {
			display: none !important;
		}
		#Monday-table, #Tuesday-table, #Wednesday-table, #Thursday-table, #Friday-table, #Saturday-table, #Sunday-table {
			display: table !important;
		}
		.title-for-print {
			display: block !important;
		}
		
	}
	
    
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
            $_POST['showtable'] = 'week-table';            
        }
        print "jQuery('#".strip_tags(htmlspecialchars($_POST['showtable']))."').show();\n";
		
		
        ?>
			
				
    });
    
    
    
</script>