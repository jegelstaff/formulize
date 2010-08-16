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


  function calculate() {
    //echo 'Calculating...<br>';

    $steps = $this->getVar('steps');
    //$input = $this->getVar('input');  // formats the text for html output (html_entities?)
    $input = $this->vars['input']['value'];
    //$output = $this->getVar('output');  // formats the text for html output (html_entities?)
    $output = $this->vars['output']['value'];

    // copy parameter values into the context
    //$context = new stdClass();
    /*foreach( $_POST as $key => $value ) {
      if( ! ( $key == 'calculate' ) ) {
        //$context->$key = $value;
        ${$key} = $value;
      }
    }*/
    //print_r( $context );

    //print( $input );
    eval( $input );

    foreach( $steps as $step ) {
      if( strpos( $step['sql'], '{foreach' ) > 0 ) {
        //echo $step['sql'] . "<br>\n" . $this->genForeach( $step ) . "<br><br>";
        $code = $this->genForeach( $step );
      } else {
        //echo $step['sql'] . "<br>\n" . $this->genBasic( $step ) . "<br><br>";
        $code = $this->genBasic( $step );
      }
      //print( $code );
      //eval( 'print_r( $context );' . $code );
      eval( $code );
    }

    //print( $output );
    eval( $output );
  }

  function genBasic( $calculation ) {
    $code = <<<EOD
\$sql = "{$calculation['sql']}";
\$res = mysql_query(\$sql);
{$calculation['preCalculate']}
while(\$row = mysql_fetch_row(\$res)) {
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
\$sql = \$sqlBase;
\$start = true;
\$chunk = 0;
foreach({$foreachCriteria}) {
  if(strlen(\$sql) > 500000) {
    \$res[\$chunk] = mysql_query(\$sql);
    \$chunk++;
    \$start = true;
    \$sql = \$sqlbase;
  }
  if(!\$start) {
    \$sql .= " {$foreachMatch} ";
  }
  \$sql .= " {$foreachSqlItem} ";
  \$start = false;
}
\$res[\$chunk] = mysql_query(\$sql);
{$calculation['preCalculate']}
foreach(\$res as \$thisRes) {
  while(\$row = mysql_fetch_row(\$thisRes)) {
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
}
?>
