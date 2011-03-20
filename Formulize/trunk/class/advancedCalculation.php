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
	}


  function genBasic( $calculation ) {
    $code = <<<EOD
\$sql = "{$calculation['sql']}";
\$res = \$xoopsDB->query(\$sql);
{$calculation['preCalculate']}
while(\$array = \$xoopsDB->fetchBoth(\$res)) {
  \$row = \$array;
  \$field = \$array;
{$calculation['calculate']}
}
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
      $sql = "INSERT INTO ".$this->db->prefix("formulize_advanced_calculations") . " (`fid`, `name`, `description`, `input`, `output`, `steps`, `steptitles`) VALUES (".$fid.", ".$this->db->quoteString($name).", ".$this->db->quoteString($description).", ".$this->db->quoteString($input).", ".$this->db->quoteString($output).", ".$this->db->quoteString($steps).", ".$this->db->quoteString($steptitles).")";
    } else {
      $sql = "UPDATE ".$this->db->prefix("formulize_advanced_calculations") . " SET `fid` = ".$fid.", `name` = ".$this->db->quoteString($name).", `description` = ".$this->db->quoteString($description).", `input` = ".$this->db->quoteString($input).", `output` = ".$this->db->quoteString($output).", `steps` = ".$this->db->quoteString($steps).", `steptitles` = ".$this->db->quoteString($steptitles)." WHERE acid = ".intval($acid);
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
    $fromBaseQuery = $GLOBALS['formulize_queryForCalcs'];

    global $xoopsDB;
    ob_start();

    $steps = $advCalcObject->getVar('steps');
    $steptitles = $advCalcObject->getVar('steptitles');
    $input = $advCalcObject->vars['input']['value'];
    $output = $advCalcObject->vars['output']['value'];

    //formulize_includeEval( $input );
    eval($input);

    foreach( $steps as $stepKey => $step ) {
      if( strpos( $step['sql'], '{foreach' ) > 0 ) {
        $code = $advCalcObject->genForeach( $step );
      } else {
        $code = $advCalcObject->genBasic( $step );
      }
      //formulize_includeEval( $code );
      eval($code);
    }

    //formulize_includeEval( $output, true, array("\$xoopsDB")); // second param forces all the passed code to be evaluated now, third param is all the global variables that should be available in this scope
    eval($output);

    $ob_calc = ob_get_clean();

    // remove any temporary tables we used to generate this procedure
    $this->destroyTables();
		
    return $ob_calc;
  }
  
  // this function removes temp tables created by the createProceduresTable on this pageload
  function destroyTables() {
    global $xoopsDB;
    $sql = "DROP TABLE `".implode("`, `",$GLOBALS['formulize_procedures_tablenames'])."`;";
    if(!$res = $xoopsDB->query($sql)) {
	print "Error: could not drop the temporary tables created by this procedure.<br>".mysql_error()."<br>$sql";
    }
  }
  
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

function createProceduresTable($array) {
    $tablename = "procedures_table_".str_replace(".","_",microtime(true));
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
    if(!$res = $xoopsDB->query($sql)) {
	print "Error: could not create table for the Procedure.<br>".mysql_error()."<br>$sql";
    } else {
	$GLOBALS['formulize_procedures_tablenames'][] = $tablename;
    }
    $sql = "INSERT INTO $tablename (`".implode("`, `",$fieldList)."`) VALUES ";
    $start = true;
    foreach($array as $fieldData) {
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
