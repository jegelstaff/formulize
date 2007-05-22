<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
##                Portions copyright (c) 2003 NS Tai (aka tuff)              ##
##                       <http://www.brandycoke.com/>                        ##
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
##  Author of this file: Freeform Solutions and NS Tai (aka tuff) and others ##
##  URL: http://www.brandycoke.com/                                          ##
##  Project: Formulize                                                       ##
###############################################################################
include_once ("admin_header.php");
include_once '../../../include/cp_header.php';
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

if(!isset($_POST['op'])){
	$op = isset ($_GET['op']) ? $_GET['op'] : '';
}else {
	$op = $_POST['op'];
}

if(!is_numeric($_GET['title'])) {

	if(!isset($_POST['title'])){
		$title = isset ($_GET['title']) ? $_GET['title'] : '';
	}else {
		$title = $_POST['title'];
	}

	$sql=sprintf("SELECT id_form FROM ".$xoopsDB->prefix("formulize_id")." WHERE desc_form='%s'",$title);
	$res = mysql_query ( $sql ) or die('Erreur SQL !<br>'.$requete.'<br>'.mysql_error());

	if ( $res ) {
		  while ( $row = mysql_fetch_row ( $res ) ) {
		    $id_form = $row[0];
  		}
	}
} else {
	$id_form = $_GET['title'];
	$title = $_GET['title'];
	$rtsql = "SELECT desc_form FROM " . $xoopsDB->prefix("formulize_id") . " WHERE id_form=$id_form";
	$rtres = $xoopsDB->query($rtsql);
	$rtarray = $xoopsDB->fetchArray($rtres);
	$realtitle = $rtarray['desc_form'];
}


if( $_POST['op'] != 'save' ){
	xoops_cp_header();

      // javascript for confirming conversion of elements -- added July 1 2006
      print "<script type='text/javascript'>\n";

      print "function confirmConvert() {\n";
      print " var answer = confirm('" . _AM_CONVERT_CONFIRM . "');\n";
      print " return answer;\n";
      print "}\n";

      print "</script>\n";

	echo '
	<form action="index.php?title='.$title.'" method="post">

	<table class="outer" cellspacing="1" width="98%">
	<th><center><font size=5>'._AM_FORM.trans($realtitle).'<font></center></th>
	</table>';

	echo '<table class="outer" cellspacing="1" width="98%">
	<th><center>'._AM_ELE_CREATE.'</center></th>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=text">'._AM_ELE_TEXT.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=textarea">'._AM_ELE_TAREA.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=areamodif">'._AM_ELE_MODIF.'</a></td></tr> 
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=ib">'._AM_ELE_MODIF_ONE.'</a></td></tr> 
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=select">'._AM_ELE_SELECT.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=checkbox">'._AM_ELE_CHECK.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=radio">'._AM_ELE_RADIO.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=yn">'._AM_ELE_YN.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=date">'._AM_ELE_DATE.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=sep">'._AM_ELE_SEP.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=subform">'._AM_ELE_SUBFORM.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=grid">'._AM_ELE_GRID.'</a></td></tr>
	<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=derived">'._AM_ELE_DERIVED.'</a></td></tr>';

	/*
	 * Hack by Félix<INBOX International>
	 * Adding colorpicker form element
	 */
	echo '<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=colorpick">'._AM_ELE_COLORPICK.'</a></td></tr>';
	/*
	 * End of Hack by Félix<INBOX International>
	 * Adding colorpicker form element
	 */
	// upload not yet enabled in formulize (redisplay of file info not supported, upload itself not tested)
	//<tr><td class="even"><li><a href="elements.php?title='.$title.'&op=edit&amp;ele_type=upload">'._AM_ELE_UPLOAD.'</a></td></tr>
	echo '</table>';

	echo ' <table class="outer" cellspacing="1" width="98%">
		<tr>
			<th>'._AM_ELE_CAPTION.'</th>';
			//<th>'._AM_ELE_DEFAULT.'</th>
			echo '<th>'._AM_ELE_REQ.'</th>
			<th>'._AM_ELE_ORDER.'</th>
			<th>'._AM_ELE_DISPLAY.'</th>
			<th>'._AM_ELE_PRIVATE.'</th>
			<th colspan="4">&nbsp;</th>
		</tr>
	';
	$criteria = new Criteria(1,1);
	$criteria->setSort('ele_order');
	$criteria->setOrder('ASC');
	$elements =& $formulize_mgr->getObjects($criteria,$id_form);
	$class = "odd";
	foreach( $elements as $i ){
		$id = $i->getVar('ele_id');
		$ele_value = $i->getVar('ele_value');
		$ele_value[0] = stripslashes ($ele_value[0]);
		$renderer =& new formulizeElementRenderer($i);
		$ele_value =& $renderer->constructElement('ele_value['.$id.']', true);
		$req = $i->getVar('ele_req');
		$check_req = new XoopsFormCheckBox('', 'ele_req['.$id.']', $req);
		$check_req->addOption(1, ' ');
		$priv = $i->getVar('ele_private');
		$check_priv = new XoopsFormCheckBox('', 'ele_private['.$id.']', $priv);
		$check_priv->addOption(1, ' ');
		//if( $ele_type == 'checkbox' || $ele_type == 'radio' || $ele_type == 'yn' || $ele_type == 'select' || $ele_type == 'date' || $ele_type== 'areamodif' || $ele_type == 'upload' || $ele_type == 'areamodif' || $ele_type == 'sep'){
			$check_req->setExtra('disabled="disabled"'); 
		//}
		$order = $i->getVar('ele_order');
		$text_order = new XoopsFormText('', 'ele_order['.$id.']', 4, 4, $order); // switched to 3 wide, jwe 01/06/05 -- switched to 4 wide, September 4 2006
		$display = $i->getVar('ele_display');

		// added - start - August 25 2005 - jpc
        $multiGroupDisplay = false;
		if(substr($display, 0, 1) == ",")
        {
			$multiGroupDisplay = true;
            
	        $fs_member_handler =& xoops_gethandler('member');
	        $fs_xoops_groups =& $fs_member_handler->getGroups();

	        $displayGroupList = explode(",", $display);
            
            $check_display = '';

            foreach($displayGroupList as $groupList)
            {
				if($groupList != "")
                {
		            if($check_display != '')
                    	$check_display .= "\n";

					$group_display = $fs_member_handler->getGroup($groupList);
					if(is_object($group_display)) {
						$check_display .= $group_display->getVar('name');
					} else {
						$check_display .= "???";
					}
				}                               
            }

            $check_display = '<a class=info href="" onclick="return false;" alt="' . 
            	$check_display . '" title="' . $check_display . '">' . 
                _AM_FORM_DISPLAY_MULTIPLE . '</a>';
        }
        else
        {
		// added - end - August 25 2005 - jpc

		$check_display = new XoopsFormCheckBox('', 'ele_display['.$id.']', $display);
		$check_display->addOption(1, ' ');

		// added - start - August 25 2005 - jpc
        }
		// added - end - August 25 2005 - jpc
        
		$hidden_id = new XoopsFormHidden('ele_id[]', $id);
		if(is_array($ele_value))$ele_value[0] = addslashes ($ele_value[0]);

		echo '<tr>';
		$class = $class == "even" ? "odd" : "even";
		echo '<td class="'.$class.'">'.$i->getVar('ele_caption')."</td>\n";
/*		if(is_object($ele_value)) {
			echo '<td class="'.$class.'">'.$ele_value[0]."</td>\n";
		} else {
			echo '<td class="'.$class.'">'.$ele_value->render()."</td>\n";
		}*/
		echo '<td class="'.$class.'" align="center">'.$check_req->render()."</td>\n";
		echo '<td class="'.$class.'" align="center">'.$text_order->render()."</td>\n";

		// added - start - August 25 2005 - jpc
		if($multiGroupDisplay == true)
        {
			// hidden id added to July 25 2006 so the save changes button on list of elements page works for custom display setting elements
			echo '<td class="'.$class.'" align="center">'.$check_display."</td>\n" . $hidden_id->render() . "\n";
		}
        else
        {
		// added - end - August 25 2005 - jpc

		echo '<td class="'.$class.'" align="center">'.$check_display->render().$hidden_id->render()."</td>\n";

		// added - start - August 25 2005 - jpc
		}
		// added - end - August 25 2005 - jpc


		echo '<td class="'.$class.'" align="center">'.$check_priv->render()."</td>\n";
	
                
		echo '<td class="'.$class.'" align="center"><a href="elements.php?title='.$title.'&op=edit&amp;ele_id='.$id.'">'._EDIT.'</a></td>';
		if($i->getVar('ele_type') == "text" OR $i->getVar('ele_type') == "textarea") {
			echo '<td class="'.$class.'" align="center"><a href="elements.php?title='.$title.'&op=convert&amp;ele_id='.$id.'" onclick="javascript:return confirmConvert();" title="'._AM_CONVERT_HELP.'" alt="'._AM_CONVERT_HELP.'">'._AM_CONVERT.'</a></td>';
		} else {
			echo '<td class="'.$class.'" align="center">&nbsp;</td>';
		}
		echo '<td class="'.$class.'" align="center"><a href="elements.php?title='.$title.'&op=edit&amp;ele_id='.$id.'&clone=1">'._CLONE.'</a></td>';
		echo '<td class="'.$class.'" align="center"><a href="elements.php?title='.$title.'&op=delete&amp;ele_id='.$id.'">'._DELETE.'</a></td>';
		echo '</tr>';
	}
	
	$submit = new XoopsFormButton('', 'submit', _AM_SAVE_CHANGES, 'submit');
	echo '
		<tr>
			<td class="foot" colspan="2"></td>
			<td class="foot" colspan="3" align="center">'.$submit->render().'</td>
			<td class="foot" colspan="4"></td>
		</tr>
	</table>
	';
	$hidden_op = new XoopsFormHidden('op', 'save');
	echo $hidden_op->render();
	echo '</form>';
}else{
        xoops_cp_header();
	extract($_POST);
	$error = '';
	foreach( $ele_id as $id ){
		$element =& $formulize_mgr->get($id);
// required field not yet available for all types of elements, so we don't check for it here.
//		$req = !empty($ele_req[$id]) ? 1 : 0;
//		$element->setVar('ele_req', $req);

		$private = !empty($ele_private[$id]) ? 1 : 0;
		$order = !empty($ele_order[$id]) ? intval($ele_order[$id]) : 0;
		$element->setVar('ele_private', $private);
		$element->setVar('ele_order', $order);
		if(!strstr($element->getVar('ele_display'), ",")) {
			$display = !empty($ele_display[$id]) ? 1 : 0;
			$element->setVar('ele_display', $display);
		}

		if( !$formulize_mgr->insert($element) ){
			$error .= $element->getHtmlErrors();
		}
	}
	if( empty($error) ){
		redirect_header("index.php?title=$title", 0, _AM_SAVING_CHANGES);
	}else{
		xoops_cp_header();
		echo error;
	}
}

	echo '<center><table><tr><td valign=top><center><a href="../index.php?fid='.$title.'" target="_blank">' . _AM_VIEW_FORM . ' <br><img src="../images/kfind.png"></a></center></td>';
	echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
	echo '<td valign=top><center><a href="../admin/formindex.php">' . _AM_GOTO_MAIN . ' <br><img src="../images/formulize.gif" height=35></a></center></td>';
	echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
	echo '<td valign=top><center><a href="../admin/mailindex.php?title='.$title.'">' . _AM_GOTO_PARAMS . ' <br><img src="../images/xfmail.png"></a><br>' . _AM_PARAMS_EXTRA . '</center></td>';
	echo '</tr></table></center>';

	//echo '<br><br>lien a insérer : &lt;a href&nbsp;="'.XOOPS_URL.'/modules/formulize/index.php?fid='.$title.'">'.$realtitle.'&lt;/a><br><br>';   


include 'footer.php';
xoops_cp_footer();
?>