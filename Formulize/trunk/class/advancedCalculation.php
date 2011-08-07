<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2007 Freeform Solutions                  ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

require_once XOOPS_ROOT_PATH.'/kernel/object.php';
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';

class formulizeAdvancedCalculation extends xoopsObject {

	function formulizeAdvancedCalculation() {
		$this->initVar('acid', XOBJ_DTYPE_INT, '', true);
		$this->initVar('fid', XOBJ_DTYPE_INT, '', true);
		$this->initVar('name', XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this->initVar('description', XOBJ_DTYPE_TXTBOX);
		$this->initVar('input', XOBJ_DTYPE_TXTBOX);
		$this->initVar('output', XOBJ_DTYPE_TXTBOX);
		$this->initVar('steps', XOBJ_DTYPE_ARRAY);
		$this->initVar('steptitles', XOBJ_DTYPE_ARRAY);
		$this->initVar('fltr_grps', XOBJ_DTYPE_ARRAY);
		$this->initVar('fltr_grptitles', XOBJ_DTYPE_ARRAY);
	}


  function genBasic( $calculation ) {
    $code = <<<EOD
\$totalNumberOfRecords = 0;
\$sql = "{$calculation['sql']}";
\$res = \$xoopsDB->query(\$sql);
{$calculation['preCalculate']}
while(\$array = \$xoopsDB->fetchBoth(\$res)) {
  \$row = \$array;
  \$field = \$array;
{$calculation['calculate']}
}
\$totalNumberOfRecords = mysql_numrows(\$res);
{$calculation['postCalculate']}
EOD;

    return $code;
  }

  function genForeach( $calculation ) {
    $sqlbase = $calculation['sql'];

    $openStart = strpos( $sqlbase, '{foreach' );
    $openEnd = strpos( $sqlbase, '}', $openStart );

    $closeStart = strpos( $sqlbase, '{/foreach}', $openEnd );

    $foreachSqlPre = substr( $sqlbase, 0, $openStart );
    $foreachSqlItem = substr( $sqlbase, $openEnd + 1, ( $closeStart - $openEnd - 1 ) );
    //$foreachSqlPost = substr( $sqlbase, $closeStart + 10 );

    //print $foreachSqlPre . '<hr>' . $foreachSqlItem . '<hr>' . $foreachSqlPost . '<hr>';

    $foreachExpression = substr( $sqlbase, $openStart + 8, ( $openEnd - $openStart - 8 ) );
    $delimitPos = strpos( $foreachExpression, ';' );
    $foreachCriteria = substr( $foreachExpression, 0, $delimitPos );
    $foreachMatch = substr( $foreachExpression, $delimitPos + 1 );

    //print $foreachExpression . '<hr>' . $foreachCriteria . '<hr>' . $foreachMatch . '<hr>';

    $code = <<<EOD
\$totalNumberOfRecords = 0;
\$sqlBase = "{$foreachSqlPre}";
\$sql = \$sqlBase . "(";
\$start = true;
\$chunk = 0;
\$res = array();
foreach({$foreachCriteria}) {
  if(strlen(\$sql) > 500000) {
		\$sql .= ")";
    \$res[\$chunk] = \$xoopsDB->query(\$sql);
    \$chunk++;
    \$start = true;
    \$sql = \$sqlBase  . "(";
  }
  if(!\$start) {
    \$sql .= " {$foreachMatch} ";
  }
  \$sql .= " {$foreachSqlItem} ";
  \$start = false;
}
\$sql .= ")";
\$res[\$chunk] = \$xoopsDB->query(\$sql);
{$calculation['preCalculate']}
foreach(\$res as \$thisRes) {
  while(\$array = \$xoopsDB->fetchBoth(\$thisRes)) {
	  \$row = \$array;
	  \$field = \$array;
{$calculation['calculate']}
  }
  $totalNumberOfRecords += mysql_numrows(\$thisRes);
}
{$calculation['postCalculate']}
EOD;

    return $code;
  }
}


class formulizeAdvancedCalculationHandler {
  var $db;
	function formulizeAdvancedCalculationHandler(&$db) {
		$this->db =& $db;
	}
  
  function &create() {
		return new formulizeAdvancedCalculation();
	}
  
  function get($id) {
    global $xoopsDB;
    $newAdvCalc = null;
    $sql = 'SELECT * FROM '.$xoopsDB->prefix("formulize_advanced_calculations").' WHERE acid='.$id.';';
    if ($result = $this->db->query($sql)) {
      $resultArray = $this->db->fetchArray($result);
      $newAdvCalc = $this->create();
      $newAdvCalc->assignVars($resultArray);
    }
    return $newAdvCalc;
	}
  
  function insert(&$advCalcObject, $force=false) {
		if( get_class($advCalcObject) != 'formulizeAdvancedCalculation'){
        return false;
    }
    if( !$advCalcObject->isDirty() ){
        return true;
    }
    if( !$advCalcObject->cleanVars() ){
        return false;
    }
    foreach( $advCalcObject->cleanVars as $k=>$v ){
      ${$k} = $v;
    }
    if($advCalcObject->isNew() || empty($acid)) {
      $sql = "INSERT INTO ".$this->db->prefix("formulize_advanced_calculations") . " (`fid`, `name`, `description`, `input`, `output`, `steps`, `steptitles`, `fltr_grps`, `fltr_grptitles`) VALUES (".$fid.", ".$this->db->quoteString($name).", ".$this->db->quoteString($description).", ".$this->db->quoteString($input).", ".$this->db->quoteString($output).", ".$this->db->quoteString($steps).", ".$this->db->quoteString($steptitles).", ".$this->db->quoteString($fltr_grps).", ".$this->db->quoteString($fltr_grptitles).")";
    } else {
      $sql = "UPDATE ".$this->db->prefix("formulize_advanced_calculations") . " SET `fid` = ".$fid.", `name` = ".$this->db->quoteString($name).", `description` = ".$this->db->quoteString($description).", `input` = ".$this->db->quoteString($input).", `output` = ".$this->db->quoteString($output).", `steps` = ".$this->db->quoteString($steps).", `steptitles` = ".$this->db->quoteString($steptitles).", `fltr_grps` = ".$this->db->quoteString($fltr_grps).", `fltr_grptitles` = ".$this->db->quoteString($fltr_grptitles)." WHERE acid = ".intval($acid);
    }
    
    if( false != $force ){
        $result = $this->db->queryF($sql);
    }else{
        $result = $this->db->query($sql);
    }

    if( !$result ){
      print "Error: this advanced calculation could not be saved in the database.  SQL: $sql<br>".mysql_error();
      return false;
    }

    if ($acid == 0) {
      $acid = $this->db->getInsertId();
    }
    return $acid;
	}
  
  function delete($acid) {
    if(is_object($acid)) {
			if(!get_class("formulizeAdvancedCalculation")) {
				return false;
			}
			$acid = $acid->getVar('acid');
		} elseif(!is_numeric($acid)) {
			return false;
		}
    global $xoopsDB;
    $isError = false;
    $sql = "DELETE FROM ".$xoopsDB->prefix("formulize_advanced_calculations")." WHERE acid=$acid";
    if(!$xoopsDB->query($sql)) {
      print "Error: could not complete the deletion of application ".$acid;
      $isError = true;
    }
    return $isError ? false : true;
  } 
  
  function cloneProcedure($acid) {
    global $xoopsDB;
    $sql = "INSERT INTO ".$xoopsDB->prefix("formulize_advanced_calculations")." (fid, name, description, input, output, steps, steptitles, fltr_grps, fltr_grptitles ) SELECT fid, CONCAT(name,' - copy'), description, input, output, steps, steptitles, fltr_grps, fltr_grptitles FROM ".$xoopsDB->prefix("formulize_advanced_calculations")." WHERE acid=".intval($acid);
    if(!$res = $xoopsDB->queryF($sql)) {
	print "Error: cloning procedure SQL failed. ".mysql_error()."<br>SQL:<br>$sql";
	return false;
    } else {
	return true;
    }
  }


  function getList($fid) {
    global $xoopsDB;
    $sql = "SELECT acid, name, description FROM ".$xoopsDB->prefix("formulize_advanced_calculations")." WHERE fid=$fid";
    $result = $this->db->query($sql);
    if(!$result) {
      print "Error: could not complete getting a list of advanced calculations".$fid;
      return null;
    }
    $list = array();
		while($row = $this->db->fetchArray($result)) {
      $list[] = $row;
    }
    return $list;
  }


  function calculate( $advCalcObject ) {
    global $xoopsDB;
    if(!is_object($advCalcObject)) {
	$advCalcObject = $this->get($advCalcObject);
    }
    $acid = $advCalcObject->getVar('acid');

    $fromBaseQuery = $GLOBALS['formulize_queryForCalcs'];

    // construct output return value structure
    $calcOutput = array( "html" => null,
      "procedure-raw" => null, "procedure-html" => null,
      "grouping-raw" => null, "grouping-raw" => null );

    //print "<pre>POST<br>"; print_r( $_POST ); print "<br>Filters and Groupings<br>"; print_r( $filtersAndGroupings ); print "</pre>"; exit();

    // get the filters and groupings information
    $filtersAndGroupings = $advCalcObject->getVar('fltr_grps');

    // figure out groupings
    $groups = array();
    foreach($filtersAndGroupings as $index => $thisGrouping) {
      if($thisGrouping['is_group']) {
        if( $thisGrouping['type']['kind'] == 3 ) {    // Checkboxes
          // if more then one is selected then do the grouping, else just do the grouping
          //print isset($_POST[$acid."_groupingchoices"][$index])." AND ".array_key_exists( $acid . "_" .$thisGrouping['handle'], $_POST )." AND ".is_array( $_POST[$acid . "_" .$thisGrouping['handle']] )." AND ".count( $_POST[$acid . "_" .$thisGrouping['handle']] );
          if( isset($_POST[$acid."_groupingchoices"][$index]) AND ( (is_array( $_POST[$acid . "_" .$thisGrouping['handle']] ) AND count( $_POST[$acid . "_" .$thisGrouping['handle']] ) != 1 ) OR !is_array($_POST[$acid . "_" .$thisGrouping['handle']]) ) )  {
            $groups[] = $index;
          }
        } else {
	        // if no filter value for this was specified, and it was checked off as a grouping option, then let's record it, otherwise we won't group by it
          if( $_POST[$acid . "_" .$thisGrouping['handle']] == "" AND $_POST[$acid . "_" .$thisGrouping['handle']] !== 0 AND isset($_POST[$acid."_groupingchoices"][$index])) {
            $groups[] = $index;
          }
        }
      }
    }
    $groupCombinations = $this->groupBy( $acid, $filtersAndGroupings, $groups );

    // setup the processing environment
    $stack = array();
    $level = -1;
    if( count( $groups ) > 0 ) {
      $hasGroups = true;
      array_push( $stack, array( -1, null, & $groupCombinations, null ) );
    } else {
      // there are no groups to process, so prime the processor with an entry
      $hasGroups = false;
      array_push( $stack, array( -1, null, null, null ) );
    }

    $calculationText = "";
    $calculationResult = "";
    $activeGroupings = array(); // will contain the metadata for the filter/grouping option, plus the "value" which is the value we're filtering on.  In the case of checkbox filters, because they are set differently based on the item position in the options array, the "value" may be different from the value pulled off the stack.

    // process the stack
    while( count( $stack ) > 0 ) {
      // get the next item to process from the stack
      $item = array_pop( $stack );

      $packedFormFilters = array();

      $doCalc = false;

      if( $hasGroups ) {
        // if the item is the root, then don't process
        if( $item[1] != null ) {
          // set the filter
	  // $item[0] is the key from $groups array, which has the value that is the key in the filtersAndGroupings array, where this filter's handle is contained
	  // $item[1] is the value that we need to set for that filter
	  // set the proper value in $_POST so that when we package up the filters, everything works as expected
	  
	  // if it's a checkbox filter, than we need to use $item[1] as the additional key in the post array, and 1 is simply the flag value
	  
	  if($filtersAndGroupings[$groups[$item[0]]]['type']['kind'] == 3) {
	    $_POST[$acid."_".$filtersAndGroupings[$groups[$item[0]]]['handle']] = array($item[1] => 1);
	    // figure out what the correct value is for the active groupings...it should be the value used in SQL, not the item[1] which will be the key position in the checkbox options array
	    $activeOption = $filtersAndGroupings[$groups[$item[0]]]['type']['options'][$item[1]];
	    if(strstr($activeOption, "|")) {
		$activeOptionParts = explode("|", $activeOption);
		$activeOption = $activeOptionParts[0];
	    } 
	    $activeGroupings[$groups[$item[0]]] = array('metadata'=>$filtersAndGroupings[$groups[$item[0]]], 'value'=>$activeOption);
	  } else {
	    $_POST[$acid."_".$filtersAndGroupings[$groups[$item[0]]]['handle']] = $item[1];
	    $activeGroupings[$groups[$item[0]]] = array('metadata'=>$filtersAndGroupings[$groups[$item[0]]], 'value'=>$item[1]);
	  }
	  
	  
	  

          // if the last level has been reached, then we need to calculate
          if( $item[0] == count( $groups ) - 1 ) {
            //print "{$item[0]}:{$item[1]}: calculate\n";
            $doCalc = true;
            //$item[3][$item[1]] = 1234;
          }

          // adjust the level
          $level = $item[0];
        }

        // put the children items on the stack for processing
        if( $item[0] != count( $groupCombinations ) - 1 ) {
          foreach( $item[2] as $key => & $value ) {
            array_push( $stack, array( $item[0] + 1, $key, & $value, & $item[2] ) );
          }
        }
      } else {
        $doCalc = true;
      }

      if( $doCalc ) {
        ob_start();
        $steps = $advCalcObject->getVar('steps');
        $steptitles = $advCalcObject->getVar('steptitles');
        $input = $advCalcObject->vars['input']['value'];
        $output = $advCalcObject->vars['output']['value'];

	// setup the filters
	$packedFormFilters = $this->setFilterVariables($filtersAndGroupings, $acid);
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$filterNames = array();
	foreach($packedFormFilters as $formId=>$formFilters) {
	    if($formId) { // form id 0 in packedFormFilters is the non-form filters, such as startDate, etc
        	$formObject = $form_handler->get($formId);
        	//$GLOBALS['filters_'.$formObject->getVar('form_handle')] = " (".implode(" AND ",$formFilters).") "; // set the packaged up filter, ie: $filters_7, $filters_Opening
		${'filters_'.$formObject->getVar('form_handle')} = " (".implode(" AND ",$formFilters).") "; // set the packaged up filter, ie: $filters_7, $filters_Opening
		$filterNames[] = "filters_".$formObject->getVar('form_handle');
	    }
	    foreach($formFilters as $filterHandle=>$filterValue) {
	        //$GLOBALS[$filterHandle] = $filterValue; // set individual filters, ie: $sexFilter
		${$filterHandle} = $filterValue; // set individual filters, ie: $sexFilter
		$filterNames[] = $filterHandle;
	    }
	}

        // establish whether the timer is on or not
        if(strstr($input,"timerOn();")) {
	    $GLOBALS['formulize_procedureTimerOn'] = true;
	    $input = str_replace("timerOn();","",$input);
        }

        reportProceduresTime("Start of Procedure");    
        eval($input);
        
        reportProceduresTime("Finished processing the input instructions");

        foreach( $steps as $stepKey => $step ) {
          if( strpos( $step['sql'], '{foreach' ) > 0 ) {
            $code = $advCalcObject->genForeach( $step );
          } else {
            $code = $advCalcObject->genBasic( $step );
          }
          eval($code);
          reportProceduresTime("Finished processing step '".$steptitles[$stepKey]."'", $totalNumberOfRecords);  
        }

        eval($output);
        
        reportProceduresTime("Finished processing the output instructions");

	// collect data/output from this calculation
	$calculationResult = isset($procOutput) ? $procOutput : ""; // procOutput is a conventional name for a variable that can be set in the procedure's own code, and we'll grab it as the result if it's set.
	$calculationTextTemp = $hasGroups ? $this->captureGroupedOutput($activeGroupings) : ob_get_clean(); // $this->captureGroupedOutput($filtersAndGroupings, $groups, $item) : ob_get_clean(); // besides any variable output, we'll grab whatever would have gone to screen, and return that as "text".  In the case of grouped results, we need to put a label before the text so we know what grouping results we're talking about.
	$calculationText = $calculationTextTemp . $calculationText; // since we do things in reverse order of how they're setup in the UI for the users, then we build the output text backwards too.

        if( $hasGroups ) {
          $item[3][$item[1]] = $calculationResult ? $calculationResult : $calculationText; // assigns by reference back to the group combinations array
        }
	
      }

      // clean-up
      $item = null;
      foreach($filterNames as $thisName) {
	unset(${$thisName});
      }
      $filterNames = array();
    }

    // remove any temporary tables we used to generate this procedure
    $this->destroyTables();

    if($hasGroups) {
	$calculationResult = $groupCombinations; // now that all groups have been processed, then we need to use the original groupCombinations array, where the individual results were assigned by reference, as the result that we're going to send back.
    }

    return array('text'=>$calculationText, 'result'=>$calculationResult);
    
  }

  // this method grabs the output to screen and sticks a grouping label in front of it
  function captureGroupedOutput($activeGroupings) {
    foreach($activeGroupings as $thisGrouping) {
	$groupingLabel .= " <p class='proc-grouping-label'>".$thisGrouping['metadata']['fltr_label'] . " &mdash; ".$this->filterTextValue($thisGrouping['metadata'], $thisGrouping['value'])."</p>";
    }
    $output = ob_get_clean();
    return "<div class='formulize-proc-text'><div class='formulize-proc-labels'>$groupingLabel</div><div class='formulize-proc-output'><blockquote>$output</blockquote></div></div>";
  }

  // this method returns the text value of a particular filter's data value, if different from the data value
  function filterTextValue($filterData, $dataValue) {
    $allOptions = $filterData["type"]["options"];
    foreach($allOptions as $thisOption) {
	if(strstr($thisOption, "|")) {
	    $optionParts = explode("|", $thisOption);
	    if(trim($optionParts[0]) == trim($dataValue)) {
		return $optionParts[1];
	    }
	} else {
	    if($thisOption == $dataValue) {
		return $thisOption;
	    }
	}
    }
    return $dataValue;
  }
  
  

  function groupBy( $acid, $filtersAndGroupings, $groups, $level = 0 ) {
    $groupCombinations = array();

    $groupsCount = count( $groups );

    $group = $groups[ $level ];
    $fltr_grp = $filtersAndGroupings[ $group ];

    //print str_repeat( ' ', $level * 2 ) . '> ' . $fltr_grp['handle'] . "\n";

    if( $fltr_grp['type']['kind'] == 2 AND $_POST[ $acid."_".$fltr_grp['handle'] ] == '' AND $_POST[ $acid."_".$fltr_grp['handle'] ] !== 0 ) { // Select
      foreach( $fltr_grp['type']['options'] as $option ) {
        $value = explode( "|", $option );
        if( count( $value ) == 2 ) {
          $key = $value[0];
        } else {
          $key = $option;
        }

        //print str_repeat( ' ', $level * 2 ) . $key . ':' . $option . "\n";

        if( $level + 1 < $groupsCount ) {
          $groupCombinations[$key] = $this->groupBy( $acid, $filtersAndGroupings, $groups, $level + 1 );
        } else {
          if( $level == $groupsCount ) {
            $groupCombinations[$key] = null;
          } else {
            $groupCombinations[$key] = array();
          }
        }
      }
    } else if( $fltr_grp['type']['kind'] == 3) { // Checkboxes
	
	/*array_key_exists( $acid . "_" .$fltr_grp['handle'], $_POST )
	AND */

      if(is_array( $_POST[$acid . "_" .$fltr_grp['handle']] ) AND count( $_POST[$acid . "_" .$fltr_grp['handle']] ) > 1) {
	$selected_grps = $_POST[$acid . "_" .$fltr_grp['handle']];
      } else {
	$selected_grps = false;
      }
      $index = 0;
      foreach( $fltr_grp['type']['options'] as $thisKey=>$option ) {
        if( !$selected_grps OR array_key_exists( $index, $selected_grps ) ) {
          $key = $thisKey;

          //print str_repeat( ' ', $level * 2 ) . $key . ':' . $option . "\n";

          if( $level + 1 < $groupsCount ) {
            $groupCombinations[$key] = $this->groupBy( $acid, $filtersAndGroupings, $groups, $level + 1 );
          } else {
            if( $level == $groupsCount ) {
              $groupCombinations[$key] = null;
            } else {
              $groupCombinations[$key] = array();
            }
          }
        }
        $index++;
      }
    }

    return $groupCombinations;
  }

  // this function removes temp tables created by the createProceduresTable on this pageload
  function destroyTables() {
    global $xoopsDB;
    if(isset($GLOBALS['formulize_procedures_tablenames'])) {
        $sql = "DROP TABLE `".implode("`, `",$GLOBALS['formulize_procedures_tablenames'])."`;";
        if(!$res = $xoopsDB->query($sql)) {
        	print "Error: could not drop the temporary tables created by this procedure.<br>".mysql_error()."<br>$sql";
        }
    }
  }

  /* When constructing the HTML, we will need to listen to GET and POST to see
     if there's a value with the same name as the form element we create, so we
     can initialize this form element with the user's previous selection. */
  function getFilter($acid, $filterHandle, $datesAsHidden=false) {
    include_once XOOPS_ROOT_PATH.'/class/xoopsform/formelement.php'; //dependency
    include_once XOOPS_ROOT_PATH.'/class/xoopsform/formtext.php'; //dependency
    include_once XOOPS_ROOT_PATH.'/class/xoopsform/formtextdateselect.php';
    include_once XOOPS_ROOT_PATH.'/class/xoopsform/formselect.php';

    // load the advanced calculation (procedure)
    $acObject = $this->get($acid);
    $fltr_grps = $acObject->getVar("fltr_grps");

    // look for the handle
    $fltr_grp_index = -1;
    foreach( $fltr_grps as $index => $fltr_grp ) {
      if( $filterHandle == $fltr_grp["handle"] ) {
        $fltr_grp_index = $index;
        break;
      }
    }
    if( $fltr_grp_index == -1 ) {
      print "Error: handle not found";
      return;
    }

    $fltr_grp = $fltr_grps[ $fltr_grp_index ];

    $kind = $fltr_grp["type"]["kind"];
    $form = $fltr_grp["form"];

    if( $kind == 1 ) {
      // first param is caption, we can skip that because the front end person will embed this somewhere with a caption of their own attached
      // $elementName is the name that gets attached to the HTML element
      // 15 is the size
      // last param is the date the user chose, note it must be passed as a timestamp into the function
      $elementName = $acid . "_" . $fltr_grp["handle"];
      $dateValue = (isset($_POST[$elementName])) ? strtotime($_POST[$elementName]) : ( (isset($_GET[$elementName])) ? strtotime($_GET[$elementName]) : "" );
      if($datesAsHidden) {
	$form_ele = new XoopsFormHidden($elementName, $dateValue);
      } else {
	$form_ele = new XoopsFormTextDateSelect("", $elementName, 15, $dateValue);
      }
    } else if( $kind == 2 ) {
      // $selectedValue is the value in the option list that should be selected by default...
      //   if $selectedValue is "" then the first item will be selected (ie: no selection)
      // 1 is the size (number of rows)
      // 0 is the flag for if multiple selections are allowed
      $elementName = $acid . "_" . $fltr_grp["handle"];
      $selectedValue = (isset($_POST[$elementName])) ? $_POST[$elementName] : ( (isset($_GET[$elementName])) ? $_GET[$elementName] : "" );
      $form_ele = new XoopsFormSelect("", $elementName, $selectedValue, 1, 0);

      // 1. first item in the list should be "Choose an option..." with a value of "" (ie: empty)
      // 2. support for "pipe" syntax that is valid elsewhere in Formulize when admins are specifying options for selectboxes.
      //   if in the admin UI the user has said the options are this:
      //     one, two, three
      //   then we build an option array to put into the element, that looks like this:
      //     one=>one, two=>two, three=>three
      //   BUT, If the user typed the options into the admin UI like this:
      //     1|one, 2|two, 3|three
      //       then we build the array:
      //         1=>one, 2=>two, 3=>three
      $definedOptions = $fltr_grp["type"]["options"];
      $options = array();
      $options[""] = "Choose an option...";
      foreach( $definedOptions as $definedOption ) {
        $value = explode( "|", $definedOption );
        if( count( $value ) == 2 ) {
          $options[$value[0]] = $value[1];
        } else {
          $options[$definedOption] = $definedOption;
        }
      }
      $form_ele->addOptionArray($options);
    } else if( $kind == 3 ) {
      $elementName = $acid . "_" . $fltr_grp["handle"];
      $tmp_html = "";
      $index = 0;
      foreach( $fltr_grp["type"]["options"] as $definedOption ) {
        $elementArrayName = $elementName . "[" . $index . "]";
        $value = (isset($_POST[$elementName][$index])) ? $_POST[$elementName][$index] : ( (isset($_GET[$elementName][$index])) ? $_GET[$elementName][$index] : 0 );
        //print $value . "!!<br><br>";print_r($_POST);print_r($_GET);exit();
        if( $value == 1 ) {
          $checked = ' CHECKED';
        } else {
          $checked = '';
        }
        $option_value = explode( "|", $definedOption );
        if( count( $option_value ) == 2 ) {
          $tmp_html .= '<input type="checkbox" id="' . $elementArrayName . '" name="' . $elementArrayName . '" value="1"' . $checked . '>';
          $tmp_html .= $option_value[1] . "<br>";
        } else {
          $tmp_html .= '<input type="checkbox" id="' . $elementArrayName . '" name="' . $elementArrayName . '" value="1"' . $checked . '>';
          $tmp_html .= $definedOption . "<br>";
        }
        $index++;
      }
    }

    if( $form_ele ) {
      $html = $form_ele->render();
    } else {
      if( $tmp_html ) {
        $html = $tmp_html;
      } else {
        $html = "";
      }
    }

    return array( "label"=>$fltr_grp["fltr_label"], "html"=>$html );
  }

  function getGrouping($acid, $filterHandle) {
    // load the advanced calculation (procedure)
    $acObject = $this->get($acid);
    $fltr_grps = $acObject->getVar("fltr_grps");

    // look for the handle
    $fltr_grp_index = -1;
    foreach( $fltr_grps as $index => $fltr_grp ) {
      if( $filterHandle == $fltr_grp["handle"] ) {
        $fltr_grp_index = $index;
        break;
      }
    }
    if( $fltr_grp_index == -1 ) {
      print "Error: handle not found";
      return;
    }

    $fltr_grp = $fltr_grps[ $fltr_grp_index ];

    $elementName = $acid . "_groupingchoices";
    $elementArrayName = $elementName . "[" . $fltr_grp_index . "]";
    $value = (isset($_POST[$elementName])) ? $_POST[$elementName] : ( (isset($_GET[$elementName])) ? $_GET[$elementName] : 0 );
    if( array_key_exists( $fltr_grp_index, $value ) ) {
      $checked = ' CHECKED';
    } else {
      $checked = '';
    }
    $html = '<input type="checkbox" id="' . $elementArrayName . '" name="' . $elementArrayName . '" value="' . $fltr_grp_index . '"' . $checked . '>';

    return array( "label"=>$fltr_grp["grp_label"], "html"=>$html );
  }

  function getAllFilters($acid, $datesAsHidden=false) {
    return $this->_getAllFiltersAndGroupings($acid,true,false,$datesAsHidden);
  }

  function getAllGroupings($acid, $datesAsHidden=false) {
    return $this->_getAllFiltersAndGroupings($acid,false,true,$datesAsHidden);
  }

  function getAllFiltersAndGroupings($acid, $datesAsHidden=false) {
    return $this->_getAllFiltersAndGroupings($acid,true,true,$datesAsHidden);
  }

  function _getAllFiltersAndGroupings($acid,$filters=false,$groupings=false,$datesAsHidden=false) {
    // load the advanced calculation (procedure)
    $acObject = $this->get($acid);
    $fltr_grps = $acObject->getVar("fltr_grps");

    // package up filters/groupings
    $_filters = array();
    $_groupings = array();
    foreach( $fltr_grps as $fltr_grp ) {
      if($filters && $fltr_grp['is_filter']) {
      	$_filters[] = $this->getFilter($acid,$fltr_grp["handle"],$datesAsHidden);
      }
      if($groupings && $fltr_grp['is_group']) {
      	$_groupings[] = $this->getGrouping($acid,$fltr_grp["handle"]);
      }
    }

    return array( "filters"=>$_filters, "groupings"=>$_groupings );
  }
  
  function setFilterVariables($filtersAndGroupings, $acid) {
  // need to construct user defined filter variables so they can be picked up in the evals below as required
    // 1. check this procedure to see what the filter options are
    // 2. grab required user info to build the filter option, from POST (since the user's defined choices will be in POST)
    // 3. make variables as text ready for use in SQL, including right table aliases, etc

    $packedFormFilters = array();
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    foreach($filtersAndGroupings as $thisFilter) {
      if($thisFilter['is_filter']) {
        $fieldName = "";
        $formId = "";
        $filterValue = $thisFilter['form_alias'] ? $thisFilter['form_alias']."." : "";
	$postName = $acid."_".$thisFilter['handle'];
	if($thisFilter['form'] AND is_numeric($thisFilter['form'])) {
	    $elementObject = $element_handler->get($thisFilter['form']);
	    $formId = $elementObject->getVar('id_form');
	    if(is_object($elementObject)) {
	        $fieldName = $elementObject->getVar('ele_handle');
	        if($thisFilter['type']['kind'] == 3) {
		    $filterValue .= "`".$fieldName."` IN (";
		} else {
		    $filterValue .= "`".$fieldName."` = '";
		}
	    }
	}
	if(isset($_POST[$postName]) AND $_POST[$postName] == '' AND $_POST[$postName] !== 0) {
	    $filterValue = " 1 ";
	} else {
	    if($thisFilter['type']['kind'] == 3) { // is a checkbox filter with possibly multiple selections
		$options = array();
		foreach($_POST[$postName] as $index=>$flag) {
		    $optionValue = explode( "|", $thisFilter["type"]["options"][$index] );
		    if( count( $optionValue ) == 2 ) {
			$optionValue = $optionValue[0];
		    }
		    $options[] = is_numeric($optionValue) ? $optionValue : "'".mysql_real_escape_string($optionValue)."'";
		}
		if(count($options) > 0) {
		    $filterValue .= implode(", ",$options).")";
		} else {
		    $filterValue = " 1 ";
		}
	    } else {
		$filterValue .= is_numeric($_POST[$postName]) ? $_POST[$postName] : mysql_real_escape_string($_POST[$postName]);
		$filterValue .= $fieldName ? "'" : ""; // close out the ' started above after we figured out the field this filter belongs to
	    }
	}
	if($formId) {
              $packedFormFilters[$formId][$thisFilter['handle']] = $filterValue;
        } else {
	      $packedFormFilters[0][$thisFilter['handle']] = $filterValue;
        }
      }
    }
    //print_r( $packedFormFilters ); exit();
    return $packedFormFilters;
  }
  
}

// THIS FUNCTION RETURNS THE NECESSARY SQL, INSIDE ' AND ( ) ' TO 
function groupScopeFilter($handle, $alias="") {
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $formObject = $form_handler->getByHandle($handle);
    $scopeFilter = "";
    if(is_object($formObject)) {
	global $xoopsUser, $xoopsDB;
	$fid = $formObject->getVar('id_form');
	$alias = $alias ? $alias."." : $xoopsDB->prefix($handle).".";
	$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
	$regUsersKey = array_search(2, $groups);
	if($regUsersKey !== false) { // if registered users is found in the group list, then remove it
	    unset($groups[$regUsersKey]);
	}
        $scopeFilter = " EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("formulize_entry_owner_groups")." AS scope WHERE (scope.entry_id=".$alias."entry_id AND scope.fid=".intval($fid).") AND (scope.groupid = ".implode(" OR scope.groupid = ", $groups).")) ";
    }
    return $scopeFilter; 
}

//This function displays the processing time since the last time it was called, with a label for the current unit that was just completed
//Optionally, a number can be passed representing the number of event/items/actions/loop iterations that have happened since the last time this was called, which will cause an average time to be displayed as well as elapsed time
function reportProceduresTime($label, $averageOverThisNumber=0) {
    if(!isset($GLOBALS['formulize_procedureTimerOn'])) { return; }
    static $time; 
    static $totalTime;
    if(!$time) {
	$time = round(microtime(true),8);
	$currentTime = $time;
	$elapsedTime = 0;
	$totalTime = 0;
    } else {
	$currentTime = round(microtime(true),8);
	$elapsedTime = round($currentTime - $time,8);
	$totalTime += $elapsedTime;
	$time = $currentTime;
    }
    if($averageOverThisNumber) {
	$averageTime = round($elapsedTime / $averageOverThisNumber,8);;
    } else {
	$averageTime = 0;
    }
    print "<br>** $label<br>";
    print "$elapsedTime -- elapsed time since last report<br>";
    if($averageTime) {
	print "$averageTime -- average time for each of $averageOverThisNumber operations since last report<br>";
    }
    print "$totalTime -- total time since start<br>";
   
}

//This function takes an array and makes a table in the database for it
//Each item in the array has a series of second level keys which are the field names, followed by a value for that field, or an array of multiple values
// ie:
// $array[$id1]['field1']=$value1;
// $array[$id1]['field2']=array($value2, value3);
// $array[$id2]['field1']=$valuex;
// $array[$id2]['field2']=array($valuey, valuez);

// Results in a table like this:
// field1|field2
// $value1|$value2
// $value1|$value3
// $valuex|$valuey
// $valuex|$valuez

// this function can only support one field that has an array of values.  All other fields must be atomic, single values.

function createProceduresTable($array, $permTableName = "") {
    $tablename = $permTableName ? $permTableName."_".str_replace(".","_",microtime(true)) : "procedures_table_".str_replace(".","_",microtime(true));
    $sql = "CREATE TABLE `$tablename` (";
    $indexList = array();
    $fieldList = array();
    foreach($array[key($array)] as $fieldName=>$values) { // loop through the first element to see all the fields we're dealing with
	if(!is_array($values)) {
	    $values = array($values);
	}
	if(is_numeric($values[0])) {
	    $fieldType = "bigint(20) default '0'";
	    $indexList[] = "INDEX i_".$fieldName." ($fieldName)";
	} elseif(strtotime($values[0])) {
	    $fieldType = "date NULL default NULL";
	    $indexList[] = "INDEX i_".$fieldName." ($fieldName)";
	} else {
	    $fieldType = "text NULL default NULL";	    
	}
	$sql .= "`$fieldName` $fieldType,";
	$fieldList[]  = $fieldName;
    }
    $sql .= implode(",", $indexList);
    $sql .= ") TYPE=MyISAM;";
    global $xoopsDB;
   //print "$sql<br>";
    if(!$res = $xoopsDB->query($sql)) {
	print "Error: could not create table for the Procedure.<br>".mysql_error()."<br>$sql";
    } elseif(!$permTableName) {
	$GLOBALS['formulize_procedures_tablenames'][] = $tablename;
    }
    $sqlbase = "INSERT INTO $tablename (`".implode("`, `",$fieldList)."`) VALUES ";
    $sql = $sqlbase;
    $start = true;
    foreach($array as $fieldData) {
  if(strlen($sql) > 50000) {
    // need to do the interim query here because of the length of the query
   //print "$sql<br>";
    if(!$res = $xoopsDB->query($sql)) {
	print "Error: could not insert values into the table for the Procedure.<br>".mysql_error()."<br>$sql";
    }
    $start = true;
    $sql = $sqlbase;
  }
	$sql .= $start ? "" : ", ";
	$values = array();
	$thisDataMultipleCount = 0;
	$thisDataMultipleField = "";
	foreach($fieldList as $fieldName) {
	    if(is_array($fieldData[$fieldName])) {
		$index = 0;
		foreach($fieldData[$fieldName] as $thisValue) {
		    $values[$index][$fieldName] = $thisValue;
		    $index++;
		}
		$thisDataMultipleCount = count($values);
		$thisDataMultipleField = $fieldName;
	    } else {
		$values[0][$fieldName] = $fieldData[$fieldName];
	    }
	}
	if($thisDataMultipleCount > 1) {
	    foreach($fieldList as $fieldName) {
		if($fieldName == $thisDataMultipleField) { continue; }
		$originalValue = $values[0][$fieldName];
		for($i=1;$i<$thisDataMultipleCount;$i++) {
		    $values[$i][$fieldName] = $originalValue;
		}
	    }
	}
	
	$recordStart = true;
	foreach($values as $record=>$data) {
	    $sql .= $recordStart ? "" : ", ";
	    $sql .= "(";
	    $fieldStart = true;
	    foreach($fieldList as $fieldName) {
		$sql .= $fieldStart ? "" : ", ";
		$sql .= "'".$data[$fieldName]."'";
		$fieldStart = false;
	    }
	    $sql .= ")";
	    $recordStart = false;
	}
	$start = false;
    }

    if(!$res = $xoopsDB->query($sql)) {
	print "Error: could not insert values into the table for the Procedure.<br>".mysql_error()."<br>$sql";
    }
    
    return $tablename;
}

?>
