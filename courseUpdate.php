<?php

include "mainfile.php";

$sql = "SELECT entry_id FROM ia5ba870e_formulize_ro_module WHERE ro_module_year = '2019/2020' ORDER BY entry_id";
$res = $xoopsDB->query($sql);
$lastCourseId = intval($_GET['id']);
while($row = $xoopsDB->fetchRow($res)) {
    if($lastCourseId>=$row[0]) { continue; } // skip courses until we get the one we haven't done before
    print "<html><h1>Processing ".$row[0]."</h1>";
    $GLOBALS['formulize_doNotCacheDataSet'] = true;
    $courseData = getData(1,3,$row[0]); // get this course's data
    $courseData = $courseData[0];
    $courseTitle = display($courseData, 'ro_module_full_course_title');
    print "<p>$courseTitle</p>";
    $sectNumbers = display($courseData, 'sections_section_number');
    $oCoordWeight = display($courseData, 'ro_module_coordinatorship_weighting');
    $oSectWeights = display($courseData, 'teaching_weighting');
    print "<p>Updating derived values...</p>";
    $GLOBALS['formulize_doNotCacheDataSet'] = true;
    formulize_updateDerivedValues($row[0],3,1); // update the derived values
    print "<p>Checking for updates to weightings...</p>";
    $courseData = getData(1,3,$row[0]); // get this course's data again
    $courseData = $courseData[0];
    $nCoordWeight = display($courseData, 'ro_module_coordinatorship_weighting');
    $nSectWeights = display($courseData, 'teaching_weighting');
    if(($nCoordWeight != $oCoordWeight AND display($courseData, 'ro_module_course_coordinator')) OR $oSectWeights != $nSectWeights) {
        print "<p>Weightings changed!</p>";
        print "<p>Coord: $oCoordWeight -> $nCoordWeight</p>";
        print "<p>Coordinator is: ".display($courseData, 'ro_module_course_coordinator')."</p>";
        print "<p>Sections:</p>";
        foreach($sectNumbers as $i=>$number) {
            print "<p>$number: ".$oSectWeights[$i]." -> ".$nSectWeights[$i]."</p>";
        }
        print "</html>";
    } else {
        ?>
            <script type='text/javascript'>
                window.location = 'https://dara.daniels.utoronto.ca/courseUpdate.php?id=<?php print $row[0]; ?>';
            </script>
        </html>
        <?php
    }
    break;
}