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

require_once XOOPS_ROOT_PATH . '/kernel/object.php';
require_once XOOPS_ROOT_PATH . '/modules/formulize/class/screen.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';

class formulizeGraphScreen extends formulizeScreen {
	function formulizeGraphScreen() {
		$this -> formulizeScreen();
		$this -> initVar("donedest", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this -> initVar("savebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this -> initVar("alldonebuttontext", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
		$this -> initVar('displayheading', XOBJ_DTYPE_INT);
		$this -> initVar('reloadblank', XOBJ_DTYPE_INT);
	}

}

class formulizeGraphScreenHandler extends formulizeScreenHandler {
	var $db;

	function formulizeGraphScreenHandler(&$db) {
		$this -> db = &$db;
	}

	function & getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeGraphScreenHandler($db);
		}
		return $instance;
	}

	function & create() {
		return new formulizeGraphScreen();
	}

	function insert($screen) {
		$update = ($screen -> getVar('sid') == 0) ? false : true;
		if (!$sid = parent::insert($screen)) {// write the basic info to the db, handle cleaning vars and all that jazz.  Object passed by reference, so updates will have affected it in the other method.
			return false;
		}
		$screen -> assignVar('sid', $sid);
		// standard flags used by xoopsobject class
		$screen -> setVar('dohtml', 0);
		$screen -> setVar('doxcode', 0);
		$screen -> setVar('dosmiley', 0);
		$screen -> setVar('doimage', 0);
		$screen -> setVar('dobr', 0);
		// note: conditions is not written to the DB yet, since we're not gathering that info from the UI
		if (!$update) {
			$sql = sprintf("INSERT INTO %s (sid, donedest, savebuttontext, alldonebuttontext, displayheading, reloadblank) VALUES (%u, %s, %s, %s, %u, %u)", $this -> db -> prefix('formulize_screen_graph'), $screen -> getVar('sid'), $this -> db -> quoteString($screen -> getVar('donedest')), $this -> db -> quoteString($screen -> getVar('savebuttontext')), $this -> db -> quoteString($screen -> getVar('alldonebuttontext')), $screen -> getVar('displayheading'), $screen -> getVar('reloadblank'));
		} else {
			$sql = sprintf("UPDATE %s SET donedest = %s, savebuttontext = %s, alldonebuttontext = %s, displayheading = %u, reloadblank = %u WHERE sid = %u", $this -> db -> prefix('formulize_screen_graph'), $this -> db -> quoteString($screen -> getVar('donedest')), $this -> db -> quoteString($screen -> getVar('savebuttontext')), $this -> db -> quoteString($screen -> getVar('alldonebuttontext')), $screen -> getVar('displayheading'), $screen -> getVar('reloadblank'), $screen -> getVar('sid'));
		}
		$result = $this -> db -> query($sql);
		if (!$result) {
			return false;
		}
		return $sid;
	}

	// 	THIS METHOD MIGHT BE MOVED UP A LEVEL TO THE PARENT CLASS
	function get($sid) {
		$sid = intval($sid);
		if ($sid > 0) {
			$sql = 'SELECT * FROM ' . $this -> db -> prefix('formulize_screen') . ' AS t1, ' . $this -> db -> prefix('formulize_screen_graph') . ' AS t2 WHERE t1.sid=' . $sid . ' AND t1.sid=t2.sid';
			if (!$result = $this -> db -> query($sql)) {
				return false;
			}
			$numrows = $this -> db -> getRowsNum($result);
			if ($numrows == 1) {
				$screen = new formulizeGraphScreen();
				$screen -> assignVars($this -> db -> fetchArray($result));
				return $screen;
			}
		}
		return false;
	}

	// THIS METHOD HANDLES ALL THE LOGIC ABOUT HOW TO ACTUALLY DISPLAY THIS TYPE OF SCREEN
	// $screen is a screen object
	function render($screen, $entry, $settings = "") {// $settings is used internally to pass list of entries settings back and forth to editing screens
		if (!is_array($settings)) {
			$settings = "";
		}
		
		$fid = $screen -> getVar("fid");
		$frid = $screen -> getVar("frid");
		$formtitle = getFormtitle($screen -> getVar("fid"));
		$colHeading_elemntId_array = get_coldheading_elemntId_array($screen);

		
		print $displayheading;

		print "<div id='graphform'><table class=outer><tbody><tr><th class=head align=center colspan=2>" . $formtitle . " </th>";
		print "<tr><td class=even colspan=2>description</td></tr>";
		print "<form name=graphoptions>
					<tr>
						<td class=head>Select graph type:</td>
						<td class=even>
							<input type=radio name=graphtype value=Bar checked=checked>Bar
						</td>
					</tr>
					<tr>
						<td class=head>Select data to be drawn:</td>
						<td class=even>
							<select name=datatype onchange=selecttype()>
								<option value=none></option>
								<option value=display>display</option>
								<option value=sum>sum</option>
								<option value=count>count</option>
								<option value=count-unique>count-unique</option>
							</select>
						</td>
					</tr>
					
					<tr id='labelelement' style='display:none'>
						<td class=head>Select the field in the form to be used as label:</td>
						<td class=even>
							<select name=labelelement>";
								foreach($colHeading_elemntId_array as $item => $value){
									print "<option value=".$value[1]." >".$value[0]."</option>";
								}
								
					 print "</select>
						</td>
					</tr>
					
					<tr id=dataelement style='display:none'>
						<td class=head>Select the field in the form to be used as data to graph:</td>
						<td class=even>
							<select name=dataelement>";
								foreach($colHeading_elemntId_array as $item => $value){
									print "<option value=".$value[1]." >".$value[0]."</option>";
								}
							print "</select>
						</td>
					</tr>
					
					<tr id='countelement' style='display:none'>
						<td class=head>Select the field in the form to be counted:</td>
						<td class=even>
							<select name=countelement>";
								
								foreach($colHeading_elemntId_array as $item => $value){
									print "<option value=".$value[1]." >".$value[0]."</option>";
								}
								
					 print "</select>
						</td>
					</tr>
					
					<tr id='sumelement' style='display:none'>
						<td class=head>Select the field in the form to be sumed:</td>
						<td class=even>
							<select name=sumelement>";
								
								foreach($colHeading_elemntId_array as $item => $value){
									print "<option value=".$value[1]." >".$value[0]."</option>";
								}
								
					 print "</select>
						</td>
					</tr>
					
					<tr>
						<td class=head >Graph orientation:</td>
						<td class=even>
							<input type=radio name=orientation value=horizontal>horizontal
							<input type=radio name=orientation value=vertical checked=checked>vertical
						</td>
					</tr>
					
					<tr>
						<td class=head >Graph resolution:</td>
						<td class=even>
							Width : <input type=text name=graphwidth > 
							Height: <input type=text name=graphheight >(in pixel)
						</td>
					</tr>
					
					<tr id=backgroundcolor>
						<td class=head >Select background color:</td>
						<td class=even>
							R: <input type=text name=backgroundcolorr class=colorr value=141>  
							G:<input type=text name=backgounrcolorg class=colorg value=189> 
							B: <input type=text name=backgroundcolorb class=colorb value=225>
							<img src='".XOOPS_URL."/modules/formulize/libraries/colorpicker/images/select.png' id=backgroundcolor width=20 height=20>
							
						</td>
					</tr>
					
					<tr id=bargroundcolor>
						<td class=head >Select bar color:</td>
						<td class=even>
							R: <input type=text name=barcolorr class=colorr value=143>  
							G:<input type=text name=barcolorg class=colorg value=190> 
							B: <input type=text name=barcolorb class=colorb value=88>
							<img src='".XOOPS_URL."/modules/formulize/libraries/colorpicker/images/select.png' id=barcolor width=20 height=20>
							
						</td>
					</tr>
					
					<tr><td colspan=2 align=center class=head><input type=button value='Save and draw' onclick=drawgraph()></td></tr>
		
			   </form>";
		print "<tr><td id=graph colspan=2></td><tr></tbody></table></div>";
		
		print "
		        <link rel='stylesheet' media='screen' type='text/css' href='".XOOPS_URL."/modules/formulize/libraries/colorpicker/css/colorpicker.css' />
				<script type='text/javascript' src='".XOOPS_URL."/modules/formulize/libraries/colorpicker/js/colorpicker.js'></script>
				<script>
					function selecttype(){
						var typeselected = document.graphoptions.datatype.value;
						var label = document.getElementById('labelelement');
						var data = document.getElementById('dataelement');
						var count = document.getElementById('countelement');
						var sum = document.getElementById('sumelement');
						if(typeselected == 'none'){
							label.style.display = 'none';
							data.style.display = 'none';
							count.style.display = 'none';
							sum.style.display = 'none';
						}else if (typeselected == 'display'){
							label.style.display = 'table-row';
							data.style.display = 'table-row';
							count.style.display = 'none';
							sum.style.display = 'none';
						} else if ( typeselected == 'count'){
							count.style.display = 'table-row';
							label.style.display = 'none';
							data.style.display = 'none';
							sum.style.display = 'none';
						} else if (typeselected == 'count-unique'){
							count.style.display = 'table-row';
							label.style.display = 'none';
							data.style.display = 'none';
							sum.style.display = 'none';
						} else if (typeselected == 'sum'){
							label.style.display = 'table-row';
							sum.style.display = 'table-row';
							count.style.display = 'none';
							data.style.display = 'none';
						}
					}



					$('#backgroundcolor, #barcolor').ColorPicker({
						onSubmit: function(hsb, hex, rgb, el) {
							var r = rgb['r'];
							var g = rgb['g'];
							var b = rgb['b'];
							
							$(el).parent().find('.colorr').val(r);
							$(el).parent().find('.colorg').val(g);
							$(el).parent().find('.colorb').val(b);
							$(el).ColorPickerHide();
						},
						
					})
					.bind('keyup', function(){
						$(this).ColorPickerSetColor(this.value);
					});



					// MODIFICATION
					function drawgraph(){
						var graphType = document.graphoptions.graphtype.value;
						var operation = document.graphoptions.datatype.value;
						var fid = ".$fid.";
						var frid = ".$frid.";
						var labelelement = document.graphoptions.labelelement.value;
						var dataelement = document.graphoptions.dataelement.value;
						// console.log(labelelement);
						// displayGraph(Bar, 1, 0, 1, 1, count);
						//alert(fid + ' ' + frid + ' ' + graphType + ' ' + operation + ' ' + labelelement + ' ' + dataelement);
						// console.log(fid + ' ' + frid + ' ' + graphType + ' ' + operation + ' ' + labelelement + ' ' + dataelement);
						jQuery.post('" . XOOPS_URL . "/modules/formulize/include/saveanddraw.php',"
									. "{ graphType : graphType, fid : fid, frid : frid, labelElement : labelelement, dataElement : dataelement, operation : operation}"
									. ", function(data) {
							if(data){
								// alert(data);
								var graphframe = document.getElementById('graph');
								graphframe.innerHTML = data;
							} else {
								alert('ERROR with graph screen, please contact system admin.');
							}
        				});
					}
					// END OF MY MODIFICATION
			   </script>";

		//include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
		//displayForm($formframe, $entry, $mainform, $donedest, array(0=>$alldonebuttontext, 1=>$savebuttontext), $settings, $displayheading, "", $overrideMulti);
	}

}


function get_coldheading_elemntId_array($screen) {
	$fid = $screen -> getVar("fid");
	$frid = $screen -> getVar("frid");
	$colList = getAllColList($fid, $frid);
	$elementHandler = xoops_getmodulehandler('elements', 'formulize');
	$rtn = array();
	foreach($colList as $formInfo){
		foreach($formInfo as $eleInfo){
			$ele_id = $eleInfo["ele_id"];
			$ele_handle = $eleInfo["ele_handle"];
			$elementObject = $elementHandler->get($ele_id);
			$ele_colheading = $labelElement = $elementObject->getVar('ele_colhead') ? $elementObject->getVar('ele_colhead') : printSmart($elementObject->getVar('ele_caption'));
			if(!array_key_exists($ele_colheading, $testArray)){
				$testArray[$ele_colheading] = $ele_handle;
				array_push($rtn, array($ele_colheading, $ele_handle));
			}
		}
	}
	return $rtn;
}
?>
