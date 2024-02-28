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

	function __construct() {
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
\$res = empty(\$sql) ? array() : \$xoopsDB->queryF(\$sql);
{$calculation['preCalculate']}
while(\$array = \$xoopsDB->fetchBoth(\$res)) {
  \$row = \$array;
  \$field = \$array;
{$calculation['calculate']}
}
\$totalNumberOfRecords = \$xoopsDB->getRowsNum(\$res);
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
    \$res[\$chunk] = \$xoopsDB->queryF(\$sql);
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
\$res[\$chunk] = \$xoopsDB->queryF(\$sql);
{$calculation['preCalculate']}
foreach(\$res as \$thisRes) {
  while(\$array = \$xoopsDB->fetchBoth(\$thisRes)) {
	  \$row = \$array;
	  \$field = \$array;
{$calculation['calculate']}
  }
  \$totalNumberOfRecords += \$xoopsDB->getRowsNum(\$thisRes);
}
{$calculation['postCalculate']}
EOD;

    return $code;
  }
}


class formulizeAdvancedCalculationHandler {
  var $db;
  	function __construct(&$db) {
		$this->db =& $db;
	}

  function &create() {
		return new formulizeAdvancedCalculation();
	}

  function get($id) {
    static $cachedResults = array();
    if(!isset($cachedResults[$id])) {
        global $xoopsDB;
        $newAdvCalc = null;
        $sql = 'SELECT * FROM '.$xoopsDB->prefix("formulize_advanced_calculations").' WHERE acid='.$id.';';
        if ($result = $this->db->query($sql)) {
          $resultArray = $this->db->fetchArray($result);
          $newAdvCalc = $this->create();
          $newAdvCalc->assignVars($resultArray);
        }
	$cachedResults[$id] = $newAdvCalc;
    }
    return $cachedResults[$id];
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
      print "Error: this advanced calculation could not be saved in the database.  SQL: $sql<br>".$this->db->error();
      return false;
    }

    if ($acid == 0) {
      $acid = $this->db->getInsertId();
    }
    return $acid;
	}

  function delete($acid) {
    if(is_object($acid)) {
			if(get_class($acid) != "formulizeAdvancedCalculation") {
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
	print "Error: cloning procedure SQL failed. ".$xoopsDB->error()."<br>SQL:<br>$sql";
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


  function createLog( $acid, $uid ) {
    $sql = "INSERT INTO ".$this->db->prefix("formulize_procedure_logs") . " (`proc_id`, `proc_datetime`, `proc_uid`) VALUES (".intval($acid).", NOW(), ".intval($uid).")";
    $result = $this->db->queryF($sql);

    if( !$result ){
      print "Error: this advanced calculation log could not be saved in the database.  SQL: $sql<br>".$this->db->error();
      return false;
    }

    return $this->db->getInsertId();
	}

  function createLogParam( $logid, $param, $value ) {
    $sql = "INSERT INTO ".$this->db->prefix("formulize_procedure_logs_params") . " (`proc_log_id`, `proc_log_param`, `proc_log_value`) VALUES (".intval($logid).", ".$this->db->quoteString($param).", ".$this->db->quoteString($value).")";
    $result = $this->db->queryF($sql);

    if( !$result ){
      print "Error: this advanced calculation log item could not be saved in the database.  SQL: $sql<br>".$this->db->error();
      return false;
    }

    return $this->db->getInsertId();
	}

  function buildCacheKey($acid, $localGroup=false) {
    global $xoopsUser;
    $newPost = unserialize( serialize( $_POST ) );
    unset( $newPost['XOOPS_TOKEN_REQUEST'] );
    unset( $newPost['formulize_cacheddata'] );
    if($localGroup) {
	// if we're doing a cache for a single grouped result, then strip more from POST so groups from different requests will get the same key
	unset($newPost[$acid."_groupingchoices"]);
	foreach($newPost as $thisPostedKey=>$thisPostedValue) {
	    $keyParts = explode("_", $thisPostedKey);
	    if(!is_array($keyParts) OR $keyParts[0] != $acid) {
		unset($newPost[$thisPostedKey]);
	    }
	}
    }
    //print "<pre>"; var_export( $_POST ); var_export( $newPost ); var_export( $xoopsUser->getGroups() ); print "</pre>";
    //$userGroups = $xoopsUser->getGroups();
    if ($xoopsUser) {
    $userGroups = $xoopsUser->getGroups();
    } else {
        $userGroups = array(XOOPS_GROUP_ANONYMOUS);
    }

    $key = md5( serialize( $newPost ) . serialize( $userGroups ) );
    $fileName = XOOPS_ROOT_PATH."/modules/formulize/cache/formulize_advancedCalculation_procid_".intval($acid)."_groupids_".implode("_",$userGroups)."_".$key.".php";
    return $fileName;
  }

  function getCachedResult($fileName, $debugFlag) {
    if($debugFlag) {
	return false;
    }
    // check that the filename is valid
    $namePart = str_replace(XOOPS_ROOT_PATH."/modules/formulize/cache/formulize_advancedCalculation_procid_","",$fileName);
    if(strstr($namePart,"/") OR strstr($namePart, "\\")) {
	return false;
    }
    // for now...override...webmasters always skip cache
    global $xoopsUser;
    //$groups = $xoopsUser->getGroups();
    if ($xoopsUser) {
    $groups = $xoopsUser->getGroups();
    } else {
        $groups = array(XOOPS_GROUP_ANONYMOUS);
    }
    if(in_array(XOOPS_GROUP_ADMIN, $groups)) {
	return false;
    }
    if( file_exists( $fileName ) AND !isset($_GET['formulize_bypassCachedResults']) ) {
      // cached version found
      $cachedVersion = unserialize( file_get_contents( $fileName ) );
    } else {
      $cachedVersion = false;
    }
    return $cachedVersion;
  }

  function calculate( $advCalcObject, $debugFlag=false ) {
    global $xoopsDB, $xoopsUser;
    if(!is_object($advCalcObject)) {
	$advCalcObject = $this->get($advCalcObject);
    }
    $acid = $advCalcObject->getVar('acid');

    // get formulize preferences
    $module_handler =& xoops_gethandler('module');
    $config_handler =& xoops_gethandler('config');
    $formulizeModule =& $module_handler->getByDirname("formulize");
    $formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
    $modulePrefUseCache = $formulizeConfig['useCache'];
    $modulePrefLogProcedure = $formulizeConfig['logProcedure'];

    // check to see if there is already a cached version of the request
    $fileName = "";
    $cachedVersion = false;
    if( $modulePrefUseCache ) {
      $fileName = $this->buildCacheKey($acid);
      if($cachedVersion = $this->getCachedResult($fileName, $debugFlag)) {
	// if logging is enables, don't return right away, gather up the params and log first
        if( ! $modulePrefLogProcedure ) {
          return $cachedVersion;
        }
      }
    }
    // cached version was not found, so create it

    $fromBaseQuery = $GLOBALS['formulize_queryForCalcs'];

    // get the filters and groupings information
    $filtersAndGroupings = $advCalcObject->getVar('fltr_grps');
    $filtersAndGroupingsTitles = $advCalcObject->getVar('fltr_grptitles');

    // figure out groupings
    $allGroups = array();
    $groups = array();
    $savedCheckboxValue = array();
    foreach($filtersAndGroupings as $index => $thisGrouping) {
      if($thisGrouping['is_group']) {
        $allGroups[] = $index;
        if( $thisGrouping['type']['kind'] == 3 ) {    // Checkboxes
          // if more then one is selected then do the grouping, else just do the grouping
          //print isset($_POST[$acid."_groupingchoices"][$index])." AND ".array_key_exists( $acid . "_" .$thisGrouping['handle'], $_POST )." AND ".is_array( $_POST[$acid . "_" .$thisGrouping['handle']] )." AND ".count( $_POST[$acid . "_" .$thisGrouping['handle']] );
          if( isset($_POST[$acid."_groupingchoices"][$index]) AND ( (is_array( $_POST[$acid . "_" .$thisGrouping['handle']] ) AND count((array)  $_POST[$acid . "_" .$thisGrouping['handle']] ) != 1 ) OR !is_array($_POST[$acid . "_" .$thisGrouping['handle']]) ) )  {
	    $savedGroupingFilterValue[$thisGrouping['handle']] = $_POST[$acid . "_" .$thisGrouping['handle']]; // save this value so we can use it again after
	    $groups[] = $index;
          }
        } else {
	  // if no filter value for this was specified, and it was checked off as a grouping option, then let's record it, otherwise we won't group by it
          if( $_POST[$acid . "_" .$thisGrouping['handle']] == "" AND $_POST[$acid . "_" .$thisGrouping['handle']] !== 0 AND isset($_POST[$acid."_groupingchoices"][$index])) {
	    $savedGroupingFilterValue[$thisGrouping['handle']] = $_POST[$acid . "_" .$thisGrouping['handle']]; // save this value so we can use it again after
            $groups[] = $index;
          }
        }
      }
    }

    // set a flag for age range grouping if the user has requested it
    if(isset($_POST['ocandsAgeGrouping']) AND $_POST['ocandsAgeGrouping'] == "ocandsAgeGrouping") {
	$savedGroupingFilterValue['minAge'] = $_POST[$acid . "_minAge"]; // save this value so we can use it again after
	$savedGroupingFilterValue['maxAge'] = $_POST[$acid . "_maxAge"]; // save this value so we can use it again after
	$groups[] = $_POST['ocandsAgeGrouping'];
    }


    // re-order grouping if specified by the user
    if(isset($_POST['sortedGroupings'])) {
      parse_str( $_POST['sortedGroupings'], $sortedGroupings );
      $newGroupings = array();
      foreach( $sortedGroupings['sortableGroupings'] as $value ) {
        if( is_string( $value ) && in_array( $value, $groups ) ) {
          $newGroupings[] = $value;
        } else if( in_array( $allGroups[ $value ], $groups ) ) {
          $newGroupings[] = $allGroups[ $value ];
        }
      }
      $groups = $newGroupings;
    }

    // set a flag to indicate if there is time-based grouping going on (a special feature of the OCANDS website) -- jwe Aug 18 2011
    if( isset($_POST['ocandsRangeType']) AND $_POST['ocandsRangeType'] == "multi" ) { // if multiple years with the same quarter range were requested, then set grouping to year
	$_POST['ocandsDateGrouping'] = 'year';
    }
    if(isset($_POST['ocandsDateGrouping']) AND ($_POST['ocandsDateGrouping'] == "year" OR $_POST['ocandsDateGrouping'] == "quarter"))  {
	$savedGroupingFilterValue['startDate'] = $_POST[$acid . "_startDate"]; // save this value so we can use it again after
	$savedGroupingFilterValue['endDate'] = $_POST[$acid . "_endDate"]; // save this value so we can use it again after
	$groups[] = $_POST['ocandsDateGrouping'];
    }


    // check to see if logging is enabled
    //print "<pre>POST<br>"; print_r( $_POST ); print "<br>Filters and Groupings<br>"; print_r( $filtersAndGroupings ); print "</pre>";
    if( $modulePrefLogProcedure ) {
      $logid = $this->createLog($acid, $xoopsUser->getVar('uid'));

      $prefix = $acid . "_";
      $prefixLen = strlen( $prefix );

      // process filters
      foreach($filtersAndGroupings as $index => $thisFilter) {
        if($thisFilter['is_filter']) {
          if( $thisFilter['type']['kind'] == 2 ) {
            if(isset($_POST[$prefix.$thisFilter['handle']])) {
              $options = $thisFilter['type']['options'];
              $postOptions = $_POST[$prefix.$thisFilter['handle']];
              foreach( $options as $optionKey => $optionValue ) {
                $option = $options[$optionKey];
                $value = explode( "|", $option );
                if( count((array)  $value ) == 2 ) {
                  if( $postOptions == $value[0] ) {
                    //print "<br>... " . $thisFilter['fltr_label'] . " = " . $value[1];
                    $this->createLogParam( $logid, $thisFilter['fltr_label'], $value[1] );
                  }
                } else {
                  if( $postOptions == $option ) {
                    //print "<br>... " . $thisFilter['fltr_label'] . " = " . $option;
                    $this->createLogParam( $logid, $thisFilter['fltr_label'], $option );
                  }
                }
              }
            }
          } else if( $thisFilter['type']['kind'] == 3 ) {
            if(isset($_POST[$prefix.$thisFilter['handle']])) {
              $options = $thisFilter['type']['options'];
              $postOptions = $_POST[$prefix.$thisFilter['handle']];
              foreach( $postOptions as $postKey => $postValue ) {
                $option = $options[$postKey];
                $value = explode( "|", $option );
                if( count((array)  $value ) == 2 ) {
                  //print "<br>... " . $thisFilter['fltr_label'] . "[] = " . $value[1];
                  $this->createLogParam( $logid, $thisFilter['fltr_label'] . "[]", $value[1] );
                } else {
                  //print "<br>... " . $thisFilter['fltr_label'] . "[] = " . $option;
                  $this->createLogParam( $logid, $thisFilter['fltr_label'] . "[]", $option );
                }
              }
            }
          } else {
            if(isset($_POST[$prefix.$thisFilter['handle']])) {
              //print "<br>... " . $thisFilter['fltr_label'] . " = " . $_POST[$prefix.$thisFilter['handle']];
              $this->createLogParam( $logid, $thisFilter['fltr_label'], $_POST[$prefix.$thisFilter['handle']] );
            }
          }
        }
      }

      // process groups
      foreach( $groups as $key => $value ) {
        //print "<br>... *Grouping[" . $key . "] = " . $filtersAndGroupings[$value]['grp_label'];
        $this->createLogParam( $logid, "*Grouping[" . $key . "]", $filtersAndGroupings[$value]['grp_label'] );
      }

      // process other values in POST
      foreach( $_POST as $key => $value ) {
        if( substr( $key, 0, $prefixLen ) != $prefix ) {
          //print "<br>... *$key = $value";
          $this->createLogParam( $logid, "*" . $key, $value );
        }
      }
    }
    //print "</pre>";
    //exit();


    // return the cached results
    if( $modulePrefUseCache AND $cachedVersion) {
      return $cachedVersion;
    }


    $groupCombinations = $this->groupBy( $acid, $filtersAndGroupings, $groups );

    // setup the processing environment
    $stack = array();
    $level = -1;
    if( count((array)  $groups ) > 0 ) {
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
    while( count((array)  $stack ) > 0 ) {
      // get the next item to process from the stack
      $item = null; // just to make sure there's nothing left over from the previous iteration
      $item = array_pop( $stack );

      $packedFormFilters = array();

      $doCalc = false;

      if( $hasGroups ) {
        // if the item is the root, then don't process
        if( $item[1] !== null ) {
          // set the filter
	  // $item[0] is the key from $groups array, which has the value that is the key in the filtersAndGroupings array, where this filter's handle is contained
	  // $item[1] is the value that we need to set for that filter
	  // set the proper value in $_POST so that when we package up the filters, everything works as expected

	  // check if we're grouping by Age...
	  if($groups[$item[0]] == "ocandsAgeGrouping") {
	    $_POST[$acid."_minAge"] = $this->getNextAgeRange($item[1], 'min', $savedGroupingFilterValue['minAge']);
	    $_POST[$acid."_maxAge"] = $this->getNextAgeRange($item[1], 'max', $savedGroupingFilterValue['maxAge']);
	    $activeGroupings[$groups[$item[0]]] = array('metadata'=>$groups[$item[0]],'value'=>$item[1]);

	  // if we're in a date grouping for OCANDS, then we need to do things a bit differently...
	  // in this case $item[1] will be the label for the timeframe
	  } elseif($groups[$item[0]] == "year" OR $groups[$item[0]] == "quarter") {
	    $_POST[$acid."_startDate"] = $this->convertOcandsDateLabelToDate($item[1], $groups[$item[0]], 'start', $savedGroupingFilterValue);
	    $_POST[$acid."_endDate"] = $this->convertOcandsDateLabelToDate($item[1], $groups[$item[0]], 'end', $savedGroupingFilterValue);
	    $activeGroupings[$groups[$item[0]]] = array('metadata'=>$groups[$item[0]],'value'=>$item[1]);

	  // if it's a checkbox filter, than we need to use $item[1] as the additional key in the post array, and 1 is simply the flag value
	  } elseif($filtersAndGroupings[$groups[$item[0]]]['type']['kind'] == 3) {

	    $_POST[$acid."_".$filtersAndGroupings[$groups[$item[0]]]['handle']] = array($item[1] => 1);
	    // figure out what the correct value is for the active groupings...it should be the value used in SQL, not the item[1] which will be the key position in the checkbox options array
	    $activeOption = $filtersAndGroupings[$groups[$item[0]]]['type']['options'][$item[1]];
	    if(strstr($activeOption, "|")) {
		$activeOptionParts = explode("|", $activeOption);
		$activeOption = $activeOptionParts[0];
	    }
	    $activeGroupings[$groups[$item[0]]] = array('metadata'=>$filtersAndGroupings[$groups[$item[0]]], 'value'=>$activeOption);
	    $activeGroupings[$groups[$item[0]]]['metadata']['title'] = $filtersAndGroupingsTitles[$groups[$item[0]]];
	  } else {
	    $_POST[$acid."_".$filtersAndGroupings[$groups[$item[0]]]['handle']] = $item[1];
	    $activeGroupings[$groups[$item[0]]] = array('metadata'=>$filtersAndGroupings[$groups[$item[0]]], 'value'=>$item[1]);
	    $activeGroupings[$groups[$item[0]]]['metadata']['title'] = $filtersAndGroupingsTitles[$groups[$item[0]]];
	  }


          // if the last level has been reached, then we need to calculate
          if( $item[0] == count((array)  $groups ) - 1 ) {
            $doCalc = true;
          }

          // adjust the level
          $level = $item[0];
        }

        // put the children items on the stack for processing
        if( $item[0] != count((array)  $groups ) - 1 ) {
          foreach( $item[2] as $key => & $value ) {
            array_push( $stack, array( $item[0] + 1, $key, & $value, & $item[2] ) );
          }
        }
      } else {
        $doCalc = true;
      }

      if( $doCalc ) {

	$steps = $advCalcObject->getVar('steps');
	$steptitles = $advCalcObject->getVar('steptitles');
	$user_defined_input = $advCalcObject->vars['input']['value'];
	$user_defined_output = $advCalcObject->vars['output']['value'];

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
	// check to see if there is already a cached version of this specific result (ie: local group)
    	$localFileName = "";
    	$cachedVersion = false;
	if( $modulePrefUseCache ) {
	    $localGroup = true;
	    $localFileName = $this->buildCacheKey($acid, $localGroup);
	    if($cachedVersion = $this->getCachedResult($localFileName, $debugFlag)) {
		$calculationResult = $cachedVersion[0];
		$output = $cachedVersion[1];
	    }
	}
	if(!$cachedVersion) {
	    ob_start();
	    // establish whether the timer is on or not
	    if(strstr($user_defined_input,"timerOn();")) {
		$GLOBALS['formulize_procedureTimerOn'] = true;
		$user_defined_input = str_replace("timerOn();","",$user_defined_input);
	    }

	    reportProceduresTime("Start of Procedure");
	    eval($user_defined_input);

	    reportProceduresTime("Finished processing the input instructions");

	    foreach( $steps as $stepKey => $step ) {
	      if( strpos( $step['sql'], '{foreach' ) > 0 ) {
#error_log("genForeach");
		$code = $advCalcObject->genForeach( $step );
	      } else {
#error_log("genBasic");
		$code = $advCalcObject->genBasic( $step );
	      }
#error_log("Processing step ".print_r($stepKey, true));
#$lines = explode("\n", $code);
#for ($linecounter = 186; $linecounter < 206; $linecounter++) {
#    if (isset($lines[$linecounter]))
#        error_log("line ".$linecounter.": ".$lines[$linecounter]);
#}
	      eval($code);
	      reportProceduresTime("Finished processing step '".$steptitles[$stepKey]."'", $totalNumberOfRecords);
	    }

	    eval($user_defined_output);

	    reportProceduresTime("Finished processing the output instructions");

	    $calculationResult = isset($procOutput) ? $procOutput : ""; // procOutput is a conventional name for a variable that can be set in the procedure's own code, and we'll grab it as the result if it's set.
	    $output = ob_get_clean();
	    if($localFileName) {
		file_put_contents($localFileName, serialize(array($calculationResult, $output)));
	    }
    	}

	// collect data/output from this calculation
	$calculationTextTemp = $hasGroups ? $this->captureGroupedOutput($activeGroupings, $output) : $output; // $this->captureGroupedOutput($filtersAndGroupings, $groups, $item) : ob_get_clean(); // besides any variable output, we'll grab whatever would have gone to screen, and return that as "text".  In the case of grouped results, we need to put a label before the text so we know what grouping results we're talking about.
	$calculationText = $calculationTextTemp . $calculationText; // since we do things in reverse order of how they're setup in the UI for the users, then we build the output text backwards too.

        if( $hasGroups ) {
          $item[3][$item[1]] = $calculationResult ? $calculationResult : $calculationText; // assigns by reference back to the group combinations array
        }

      } // end of $docalc

      // clean-up
      foreach($filterNames as $thisName) {
	unset(${$thisName});
      }
      $filterNames = array();
    }

    // remove any temporary tables we used to generate this procedure
    $this->destroyTables();

    if($hasGroups) {
	if(count((array) $savedGroupingFilterValue)>0) {
            $calculationResult = serialize($groupCombinations); // now that all groups have been processed, then we need to use the original groupCombinations array, where the individual results were assigned by reference, as the result that we're going to send back.
            $calculationResult = unserialize($calculationResult); // this is the stupid thing we have to do if checkbox selections are involved, since there's some deep reference involving POST, which screws up the results when we reset it to the user's original choice.  So we cannot assign the value here in a normal way, we have to munge the reference that is in place by converting the array to a string!!
	} else {
	    $calculationResult = $groupCombinations;
	}
    }

    // reset any checkbox filter values to what the user selected for them (while processing, we will have set these values to something else if there is grouping going on)
    // NEED TO DO THIS LAST BECAUSE THERE'S SOME PASS BY REFERENCE STRANGENESS GOING ON THAT AFFECTS groupCombinations! See comment above about serialize/unserialize when getting groupcombinations
    foreach($savedGroupingFilterValue as $handle=>$value) {
	$_POST[$acid . "_" .$handle] = $value;
    }

    $output = array('text'=>$calculationText, 'result'=>$calculationResult, 'groups'=>$activeGroupings);
    if($fileName) {
	file_put_contents( $fileName, serialize( $output ) );
    }

    return $output;

  }

  // this method grabs the output to screen and sticks a grouping label in front of it
  function captureGroupedOutput($activeGroupings, $output) {
    foreach($activeGroupings as $thisGrouping) {
	if(is_array($thisGrouping['metadata'])) {
	    $label = $thisGrouping['metadata']['fltr_label'];
	    $value = $this->filterTextValue($thisGrouping['metadata'], $thisGrouping['value']);
	} else { // age or date grouping...
	    $label = ucfirst($thisGrouping['metadata']);
	    $value = $thisGrouping['value'];
	}
	$groupingLabel .= " <p class='proc-grouping-label'>".$label. " &mdash; ".$value."</p>";
    }
    return "<div class='formulize-proc-text'><div class='formulize-proc-labels'>$groupingLabel</div><div class='formulize-proc-output'><blockquote>$output</blockquote></div></div>";
  }

  // this method returns the text value of a particular filter's data value, if different from the data value
  function filterTextValue($filterData, $dataValue) {
    static $cachedValues;
    $serializedFilterData = serialize($filterData);
    if(!isset($cachedValues[$serializedFilterData][$dataValue])) {
        $allOptions = $filterData["type"]["options"];
        foreach($allOptions as $thisOption) {
	    if(strstr($thisOption, "|")) {
		$optionParts = explode("|", $thisOption);
		if(trim($optionParts[0]) == trim($dataValue)) {
		    $cachedValues[$serializedFilterData][$dataValue] = $optionParts[1];
		}
	    } else {
		if($thisOption == $dataValue) {
		    $cachedValues[$serializedFilterData][$dataValue] = $thisOption;
		}
	    }
        }
    }
    return $cachedValues[$serializedFilterData][$dataValue];
  }



  function groupBy( $acid, $filtersAndGroupings, $groups, $level = 0 ) {
    $groupCombinations = array();

    $groupsCount = count((array)  $groups );

    $group = $groups[ $level ];
    if($group == "year" OR $group == "quarter") {
	$fltr_grp = "ocandsDateGrouping";
    } elseif($group == "ocandsAgeGrouping") {
	$fltr_grp = $group;
    } else {
	$fltr_grp = $filtersAndGroupings[ $group ];
    }

    //print str_repeat( ' ', $level * 2 ) . '> ' . $fltr_grp['handle'] . "\n";

    if($fltr_grp == "ocandsDateGrouping") { // always guaranteed to be the final level

	// since this is always going to be the bottom level, throw error if we're not
	if($level+1 != $groupsCount) {
	    print "Error: Ocands Date Grouping is not the bottom level of grouping!";
	} else {
	    if($_POST['ocandsRangeType'] == "multi") {
		// user has specified same range across multiple years
		$startYear = intval($_POST['ocandsDateFrom']);
		$endYear = intval($_POST['ocandsDateTo']);
		if(!$endYear) { $endYear = $startYear; }
		while($startYear <= $endYear) {
		    $groupCombinations[$startYear] = null;
		    $startYear++;
		}
	    } else {
        	    $currentDate = $_POST[$acid."_startDate"];
        	    while($currentDate < $_POST[$acid."_endDate"]) {
	    		$groupCombinations[$this->getOcandsDateLabel($currentDate, $group)] = null; // group will be year or quarter;
	    		$currentDate = $this->nextOcandsDate($currentDate, $group);
        	    }
	    }
	}

    } elseif($fltr_grp == "ocandsAgeGrouping") {
	$ageGroups = array(
	  "<1",
	  "1-5",
	  "6-12",
	  "13-15",
	  "16+"
	);
	$mins = array(0,1,6,13,16);
	$maxes = array(0.999,5.999,12.999,15.999,99);
	foreach($ageGroups as $i=>$thisAgeGroup) {
	    if($maxes[$i] < $_POST[$acid."_minAge"]) { continue; }
	    if($mins[$i] > $_POST[$acid."_maxAge"]) { continue; }
	    if( $level + 1 < $groupsCount ) {
	      $groupCombinations[$thisAgeGroup] = $this->groupBy( $acid, $filtersAndGroupings, $groups, $level + 1 );
	    } else {
	      if( $level == $groupsCount ) {
		$groupCombinations[$thisAgeGroup] = null;
	      } else {
		$groupCombinations[$thisAgeGroup] = array();
	      }
	    }
	}

    } elseif( $fltr_grp['type']['kind'] == 2 AND $_POST[ $acid."_".$fltr_grp['handle'] ] == '' AND $_POST[ $acid."_".$fltr_grp['handle'] ] !== 0 ) { // Select
      foreach( $fltr_grp['type']['options'] as $option ) {
        $value = explode( "|", $option );
        if( count((array)  $value ) == 2 ) {
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

      if(is_array( $_POST[$acid . "_" .$fltr_grp['handle']] ) AND count((array)  $_POST[$acid . "_" .$fltr_grp['handle']] ) > 1) {
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

  // this function returns the correct min or max value for an age range, when passed the official label for that age range
  function getNextAgeRange($ageRange, $minMax, $boundaryValue) {
    $keyRange = array(
	  "<1",
	  "1-5",
	  "6-12",
	  "13-15",
	  "16+"
	);
    $mins = array(0,1,6,13,16);
    $maxes = array(0.999,5.999,12.999,15.999,99);
    switch($minMax) {
	case "min":
	    $value = $mins[array_search($ageRange, $keyRange)];
	    $value = $value < $boundaryValue ? $boundaryValue : $value;
	    break;
	case "max":
	    $value = $maxes[array_search($ageRange, $keyRange)];
	    $value = $value > $boundaryValue ? $boundaryValue : $value;
	    break;
    }
    return $value;
  }

  // this function returns the correct label for a date, based on the fiscal/calendar setting for quarters if applicable
  function getOcandsDateLabel($date, $groupType) {
    $offset = $_POST['ocandsDateOffset']; // get fiscal/calendar setting
    switch($groupType) {
	case "year":
	    $year = date("Y",strtotime($date)); // return the four digit year of the date
	    if($offset == "fiscal") {
		$month = date("n",strtotime($date)); // return the number of the month in the date
		if($month <= 3) { // first calendar quarter is considered fourth quarter of previous year
		    $year--;
		}
	    }
	    return $year;
	    break;
	case "quarter":
	    $month = date("n",strtotime($date)); // return the number of the month in the date
	    $year = date("Y",strtotime($date)); // return the four digit year of the date
	    if($month > 9) {
		$quarter = 4;
	    } elseif($month > 6) {
		$quarter = 3;
	    } elseif($month > 3) {
		$quarter = 2;
	    } else {
		$quarter = 1;
	    }
	    if($offset == "fiscal") {
		$quarter--;
		if($quarter == 0) { // first calendar quarter is considered fourth quarter of previous year
		    $quarter = 4;
		    $year--;
		}
	    }
	    return "Q$quarter $year";
	    break;
    }
  }

  // this function advances to the start date of the next quarter or year
  function nextOcandsDate($date, $groupType) {
    switch($groupType) {
	case "year":
	    return date("Y-m-d", strtotime('+1 year',strtotime($date))); // add one year to the current date (as below for months, just +1 year instead)
	    break;
	case "quarter":
	    return date("Y-m-d", strtotime('+3 months',strtotime($date))); // add three months to a timestamp based on the passed in date, and then format that new timestamp as Y-m-d
	    break;
    }
  }

  // this function returns the start or end date of a time period, given the label and the groupingType
  function convertOcandsDateLabelToDate($label, $groupType, $startEnd, $savedGroupingFilterValue) {
    $offset = $_POST['ocandsDateOffset']; // get fiscal/calendar setting
    switch($groupType) {
	case "year":
	    if($_POST['ocandsRangeType'] == "multi") {
		$startYear = $this->getCalendarYearForThisQuarterDate($savedGroupingFilterValue['startDate'], $label, $offset);
		$endYear = $this->getCalendarYearForThisQuarterDate($savedGroupingFilterValue['endDate'], $label, $offset);
		$dates = array('start'=>$startYear."-".$savedGroupingFilterValue['startDate'], 'end'=>$endYear."-".$savedGroupingFilterValue['endDate']);
	    } else {
		if($offset == "fiscal") {
		    $endYear = $label+1;
		    $dates = array('start'=>$label."-04-01", 'end'=>$endYear."-03-31");
		} else {
		    $dates = array('start'=>$label."-01-01", 'end'=>$label."-12-31");
		}
	    }
	    break;
	case "quarter":
	    $quarterStarts = array(1=>'-01-01',2=>'-04-01',3=>'-07-01',4=>'-10-01');
	    $quarterEnds = array(1=>'-03-31',2=>'-06-30',3=>'-09-30',4=>'-12-31');
	    $labelParts = explode(" ",$label); // label will be "Q1 2007" for example
	    $year = $labelParts[1];
	    $quarterNumber = substr($labelParts[0],1); // get second character, ie: the number
	    if($offset == "fiscal") {
		$quarterNumber++;
		if($quarterNumber == 5) { // last quarter is in first part of next calendar year for fiscal years
		    $quarterNumber = 1;
		    $year++;
		}
	    }
	    $dates = array('start'=>$year.$quarterStarts[$quarterNumber], 'end'=>$year.$quarterEnds[$quarterNumber]);
	    break;
    }
    return $dates[$startEnd];
  }

  // this function returns the calendar year in which the passed in month-day occurs for the given year and offset (if offset is fiscal, then year will increase for Jan and March dates)
  function getCalendarYearForThisQuarterDate($yearMonth, $year, $offset) {
    if($offset == "fiscal") {
	$yearMonthParts = explode("-",$yearMonth);
	if($yearMonthParts[0]=="01" OR $yearMonthParts[0]=="03") {
	    $year++;
	}
    }
    return $year;
  }

  // this function removes temp tables created by the createProceduresTable on this pageload
  function destroyTables() {
    global $xoopsDB;
    if(isset($GLOBALS['formulize_procedures_tablenames'])) {
        $sql = "DROP TABLE `".implode("`, `",$GLOBALS['formulize_procedures_tablenames'])."`;";
        if(!$res = $xoopsDB->queryF($sql)) {
        	print "Error: could not drop the temporary tables created by this procedure.<br>".$xoopsDB->error()."<br>$sql";
        }
        unset( $GLOBALS['formulize_procedures_tablenames'] );
    }
  }

  /* When constructing the HTML, we will need to listen to GET and POST to see
     if there's a value with the same name as the form element we create, so we
     can initialize this form element with the user's previous selection. */
  function getFilter($acid, $filterHandle, $boxesAsHidden=false) {
    include_once XOOPS_ROOT_PATH.'/class/xoopsform/formelement.php'; //dependency
    include_once XOOPS_ROOT_PATH.'/class/xoopsform/formtext.php'; //dependency
    include_once XOOPS_ROOT_PATH.'/class/xoopsform/formhidden.php'; //dependency
    include_once XOOPS_ROOT_PATH.'/class/xoopsform/formtextdateselect.php';
    include_once XOOPS_ROOT_PATH.'/class/xoopsform/formselect.php';

    $hideLabel = false;


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

    $elementUnderlyingField = $form ? "element".$form : "no-underlying-element";

    if( $kind == 1 ) {
      // first param is caption, we can skip that because the front end person will embed this somewhere with a caption of their own attached
      // $elementName is the name that gets attached to the HTML element
      // 15 is the size
      // last param is the date the user chose, note it must be passed as a timestamp into the function
      $elementName = $acid . "_" . $fltr_grp["handle"];
      $dateValue = (isset($_POST[$elementName])) ? strtotime($_POST[$elementName]) : ( (isset($_GET[$elementName])) ? strtotime($_GET[$elementName]) : "" );
      if($boxesAsHidden) {
	$hideLabel = true;
	if($elementName == $acid."_minAge") {
	    $dateValue = 0;
	}
	if($elementName == $acid."_maxAge") {
	    $dateValue = 99;
	}
	$form_ele = new XoopsFormHidden($elementName, $dateValue);
      } else {
	$form_ele = new XoopsFormTextDateSelect("", $elementName, 15, $dateValue);
	$form_ele->setExtra(' class="'. $elementUnderlyingField . '" ');
      }
      $selected = date("Y-m-d", (int)$dateValue);
      // special catch for dates without a year, which is valid for OCANDS multi year groupings with only quarters specified
      if($selected == "1969-12-31" AND (strstr($elementName, "_startDate") OR strstr($elementName, "_endDate")) AND $selected != $_POST[$elementName] AND $selected != $_GET[$elementName]) {
	$selected = $_POST[$elementName];
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
        if( count((array)  $value ) == 2 ) {
          $options[$value[0]] = $value[1];
          if( $selectedValue == $value[0] ) {
            $selected = trim($value[1]);
          }
        } else {
          $options[$definedOption] = $definedOption;
          if( $selectedValue == $definedOption ) {
            $selected = trim($definedOption);
          }
        }
      }
      $form_ele->addOptionArray($options);
      $form_ele->setExtra(' class="'. $elementUnderlyingField . '" ');
    } else if( $kind == 3 ) {
      $selected = array();
      $elementName = $acid . "_" . $fltr_grp["handle"];
      $tmp_html = "";
      $index = 0;
      foreach( $fltr_grp["type"]["options"] as $definedOption ) {
        $elementArrayName = $elementName . "[" . $index . "]";
	$value = $this->_getFilterOptionsCheckboxStatus($fltr_grp, $index, $elementName);
        //print $value . "!!<br><br>";print_r($_POST);print_r($_GET);exit();
        if( $value == 1 ) {
          $checked = ' CHECKED';
        } else {
          $checked = '';
        }
        $option_value = explode( "|", $definedOption );
        if( count((array)  $option_value ) == 2 ) {
          $tmp_html .= '<input type="checkbox" id="' . $elementArrayName . '" class="'. $elementUnderlyingField . ' ' . $elementName . '" name="' . $elementArrayName . '" value="1"' . $checked . '>';
          $tmp_html .= $option_value[1] . "<br>";
          if( $value == 1 ) {
            $selected[] = trim($option_value[1]);
          }
        } else {
          $tmp_html .= '<input type="checkbox" id="' . $elementArrayName . '" class="'. $elementUnderlyingField . ' ' . $elementName . '" name="' . $elementArrayName . '" value="1"' . $checked . '>';
          $tmp_html .= $definedOption . "<br>";
          if( $value == 1 ) {
            $selected[] = trim($definedOption);
          }
        }
        $index++;
      }
      if( count((array)  $selected ) == 0 ) {
        $selected = "All";
      }
    } else if($kind == 4) { // textbox
	$elementName = $acid . "_" . $fltr_grp["handle"];
	$value = (isset($_POST[$elementName])) ? $_POST[$elementName] : ( (isset($_GET[$elementName])) ? $_GET[$elementName] : "" );
	if($boxesAsHidden) {
	    $form_ele = new XoopsFormHidden($elementName, $value);
	} else {
	    $form_ele = new XoopsFormText('', $elementName, 20, 255, $value);
	    $form_ele->setExtra(' class="'. $elementUnderlyingField . '" ');
	}
      $selected = $value;
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

    $labelText = $hideLabel ? "" : $fltr_grp["fltr_label"];

    return array( "label"=>$labelText, "html"=>$html, "selected"=>$selected );
  }

  // takes the filter and grouping option settings as input and checks them and the current status to determine if the value is on or off
  // also takes the index number of the current filter/grouping option we're evaluating
  // also takes the name of the element that we should be listening to
  function _getFilterOptionsCheckboxStatus($fltr_grp, $index, $elementName) {
    $value = 0; // default to nothing selected, unless we pick up something below...
    if(isset($_POST[$elementName][$index])) { // if selections for this filter were sent from the form...
        $value = $_POST[$elementName][$index];
    } elseif(isset($_GET[$elementName][$index])) { // or if they were set in the URL...
        $value = $_GET[$elementName][$index];
    } elseif(count((array) $_POST)==0 AND !isset($_GET[$elementName])) { // if no form submission at all and nothing set in the URL, gather defaults
        $value = in_array($index, (array)$fltr_grp["type"]["defaults"]);
    }
    return $value;
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
    // check if more than one of the values is currently selected/active, for checkboxes
    if($fltr_grp["type"]["kind"] == 3) {
      $index = 0;
      $numberChecked = 0;
      while($index <= count((array) $fltr_grp["type"]["options"]) AND $numberChecked < 2) {
	if($this->_getFilterOptionsCheckboxStatus($fltr_grp, $index, $acid . "_" . $fltr_grp["handle"])) {
	    $numberChecked++;
	}
	$index++;
      }
    }
    if( array_key_exists( $fltr_grp_index, (array)$value ) OR $numberChecked >= 2 ) {
      $checked = ' CHECKED';
    } else {
      $checked = '';
    }
    $elementUnderlyingField = $fltr_grp["form"] ? "element".$fltr_grp["form"] : "no-underlying-element";
    $html = '<input type="checkbox" id="' . $elementArrayName . '" class="'. $elementUnderlyingField . ' ' .  $elementName . '_'.$fltr_grp_index.'" name="' . $elementArrayName . '" value="' . $fltr_grp_index . '"' . $checked . '>';

    // for checkboxes, add some jquery so we can autoselect the grouping option if the user selects more than one choice
    if($fltr_grp["type"]["kind"] == 3) {
	$jQuery = "
\n\n<script type='text/javascript'>

jQuery(document).ready(function() {
    jQuery('.".$acid . "_" . $fltr_grp["handle"]."').click(function() {
	if(jQuery('.".$acid . "_" . $fltr_grp["handle"].":checked').length > 1) {
	    jQuery('.".$elementName."_".$fltr_grp_index."').prop('checked', true);
	} else {
	    jQuery('.".$elementName."_".$fltr_grp_index."').prop('checked', false);
	}
    });
});

</script>\n\n
";
	$html .= $jQuery;
    }

    return array( "label"=>$fltr_grp["grp_label"], "html"=>$html );
  }

  function getAllFilters($acid, $boxesAsHidden=false) {
    return $this->_getAllFiltersAndGroupings($acid,true,false,$boxesAsHidden);
  }

  function getAllGroupings($acid, $boxesAsHidden=false) {
    return $this->_getAllFiltersAndGroupings($acid,false,true,$boxesAsHidden);
  }

  function getAllFiltersAndGroupings($acid, $boxesAsHidden=false) {
    return $this->_getAllFiltersAndGroupings($acid,true,true,$boxesAsHidden);
  }

  function _getAllFiltersAndGroupings($acid,$filters=false,$groupings=false,$boxesAsHidden=false) {
    // load the advanced calculation (procedure)
    $acObject = $this->get($acid);
    $fltr_grps = $acObject->getVar("fltr_grps");

    // package up filters/groupings
    $_filters = array();
    $_groupings = array();
    foreach( $fltr_grps as $fltr_grp ) {
      if($filters && $fltr_grp['is_filter']) {
      	$_filters[] = $this->getFilter($acid,$fltr_grp["handle"],$boxesAsHidden);
      }
      if($groupings && $fltr_grp['is_group']) {
      	$_groupings[] = $this->getGrouping($acid,$fltr_grp["handle"]);
      }
    }

    return array( "filters"=>$_filters, "groupings"=>$_groupings );
  }

  function filterExists($acid,$name) {
    static $cachedExists = array();
    if(!isset($cachedExists[$acid][$name])) {
        $acObject = $this->get($acid);
        $fltr_grps = $acObject->getVar("fltr_grps");
        foreach($fltr_grps as $fltr_grp) {
	    if(strtoupper($fltr_grp['handle'])==strtoupper($name)) {
		$cachedExists[$acid][$name] = true;
	        return true;
	    }
	}
	$cachedExists[$acid][$name] = false;
    }
    return $cachedExists[$acid][$name];
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
		    if( count((array)  $optionValue ) == 2 ) {
			$optionValue = $optionValue[0];
		    }
		    $options[] = is_numeric($optionValue) ? $optionValue : "'".formulize_db_escape($optionValue)."'";
		}
		if(count((array) $options) > 0) {
		    $filterValue .= implode(", ",$options);
		    if($thisFilter['form'] AND is_numeric($thisFilter['form'])) {
			$filterValue .= ")";
		    }
		} else {
		    $filterValue = " 1 ";
		}
	    } else {
		if ($thisFilter['type']['kind'] ==1) { // 1 is dateboxes
		    $filterValue= date("Y-m-d", strtotime($_POST[$postName]));
		} else {
		    $filterValue .= is_numeric($_POST[$postName]) ? $_POST[$postName] : formulize_db_escape($_POST[$postName]);
		    $filterValue .= $fieldName ? "'" : ""; // close out the ' started above after we figured out the field this filter belongs to
		}
	    }
	}
	if($formId) {
              $packedFormFilters[$formId][$thisFilter['handle']] = $filterValue;
        } else {
	      $packedFormFilters[0][$thisFilter['handle']] = $filterValue;
        }
      }
    }
    /*global $xoopsUser;
    if($xoopsUser->getVar('uid')==1021) {
	print_r( $packedFormFilters );
    }*/
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
    if($averageOverThisNumber AND is_numeric($averageOverThisNumber)) {
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
	    $fieldType = "datetime NULL default NULL";
	    $indexList[] = "INDEX i_".$fieldName." ($fieldName)";
	} else {
        $fieldType = "text";
	}
	$sql .= "`$fieldName` $fieldType,";
	$fieldList[]  = $fieldName;
    }
    $sql .= implode(",", $indexList);
    $sql .= ") ENGINE=InnoDB;";
    global $xoopsDB;
   //print "$sql<br>";
    if(!$res = $xoopsDB->queryF($sql)) {
	print "Error: could not create table for the Procedure.<br>".$xoopsDB->error()."<br>$sql";
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
    if(!$res = $xoopsDB->queryF($sql)) {
	print "Error: could not insert values into the table for the Procedure.<br>".$xoopsDB->error()."<br>$sql";
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
		$thisDataMultipleCount = count((array) $values);
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

    if(!$res = $xoopsDB->queryF($sql)) {
	print "Error: could not insert values into the table for the Procedure.<br>".$xoopsDB->error()."<br>$sql";
    }

    return $tablename;
}

?>
