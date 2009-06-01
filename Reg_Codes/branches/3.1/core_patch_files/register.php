<?php
// $Id: register.php,v 1.15 2005/06/26 15:38:21 mithyt2 Exp $
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //

$xoopsOption['pagetype'] = 'user';

include 'mainfile.php';
$myts =& MyTextSanitizer::getInstance();

global $xoopsConfig;
$config_handler =& xoops_gethandler('config');
$xoopsConfigUser =& $config_handler->getConfigsByCat(XOOPS_CONF_USER);

include_once XOOPS_ROOT_PATH . "/modules/reg_codes/include/functions.php";
include XOOPS_ROOT_PATH . "/modules/reg_codes/language/".$xoopsConfig['language']."/main.php";

// some of the $_POST names are different now.  They use the prefix userprofile_ and pass was changed to password

// specifically get "conf" from the URL if it is present, otherwise, we're listening for the $_POST value of op
if($_GET['op'] == "conf") {
	$op = "conf";
} else {
	if ($xoopsUser) {
        	redirect_header('index.php', 4, _NOPERM);
        }
        $op = !isset($_POST['op']) ? 'register' : $myts->htmlSpecialChars($myts->stripSlashesGPC($_POST['op']));
}
$uname = isset($_POST['userprofile_uname']) ? $myts->stripSlashesGPC($_POST['userprofile_uname']) : '';
$name = isset($_POST['userprofile_name']) ? $myts->stripSlashesGPC($_POST['userprofile_name']) : '';
$email = isset($_POST['userprofile_email']) ? trim($myts->stripSlashesGPC($_POST['userprofile_email'])) : '';
$module_handler =& xoops_gethandler('module');
$regcodesModule =& $module_handler->getByDirname("reg_codes");
$regcodesConfig =& $config_handler->getConfigsByCat(0, $regcodesModule->getVar('mid'));
if ($regcodesConfig['email_as_username'] == 1)	{
	$uname = $email;
}
$url = isset($_POST['url']) ? trim($myts->stripSlashesGPC($_POST['url'])) : '';
$pass = isset($_POST['userprofile_password']) ? $myts->stripSlashesGPC($_POST['userprofile_password']) : '';
$vpass = isset($_POST['userprofile_vpass']) ? $myts->stripSlashesGPC($_POST['userprofile_vpass']) : '';
$timezone_offset = isset($_POST['userprofile_timezone_offset']) ? intval($_POST['userprofile_timezone_offset']) : $xoopsConfig['default_TZ'];
$user_viewemail = (isset($_POST['userprofile_user_viewemail']) && intval($_POST['userprofile_user_viewemail'])) ? 1 : 0;
$user_mailok = (isset($_POST['user_mailok']) && intval($_POST['user_mailok'])) ? 1 : 0;
$agree_disc = (isset($_POST['userprofile_agree_disc']) && intval($_POST['userprofile_agree_disc'])) ? 1 : 0;
$regcode = isset($_POST['userprofile_regcode']) ? trim($myts->stripSlashesGPC($_POST['userprofile_regcode'])) : trim($myts->stripSlashesGPC(urldecode($_GET['code'])));
$ckey = isset($_GET['ckey']) ? $myts->stripSlashesGPC($_GET['ckey']) : ''; 		// added nmc 2007.03.20
switch ( $op ) {
// Added processing to ensure Email Address supplied is valid 	//nmc 2007.03.20
case 'conf': // user  is confirming his/her email address after receiving email with regkey  									
	include 'header.php'; 													
	$sql = sprintf("SELECT * FROM %s WHERE reg_codes_conf_actkey = '%s'", 	
					$xoopsDB->prefix('reg_codes_confirm_user'), 			
					mysql_real_escape_string($ckey));						
	if(!$result = $xoopsDB->query($sql)) {									
		echo( sprintf(_US_REGPROCESSINVALIDCONFIRM, $xoopsConfig['adminmail'])); //= error, invalid reg code
		include 'footer.php'; 													
		break;  																
    }
	else {																		
		// should be only one record returned													
      	while($rc_conf= $xoopsDB->fetchArray($result)) 							
		{
			$sql = sprintf("SELECT reg_codes_preapproved FROM %s WHERE reg_codes_key = '%s' AND reg_codes_preapproved = '%s'", 	
						$xoopsDB->prefix('reg_codes_preapproved_users'), 			
						mysql_real_escape_string($rc_conf['reg_codes_conf_reg_code']), 
						mysql_real_escape_string($rc_conf['reg_codes_conf_email'])); 
			$result_pa = $xoopsDB->query($sql);
			$result_pa_count = $xoopsDB->getRowsNum($result_pa);
			if($result_pa_count==0) { 
				// Not pre-approved email address => go thru approval process
				$confirm_approval_groups = checkApproval($rc_conf['reg_codes_conf_reg_code']); 
				notifyAdmin($rc_conf['reg_codes_conf_name'], 						
							$rc_conf['reg_codes_conf_email'], 						
							$rc_conf['reg_codes_conf_id'], 							
							$rc_conf['reg_codes_conf_actkey'], 						
							$confirm_approval_groups) ;								
			}
			else	{
				// Account was pre-approved
				$member_handler =& xoops_gethandler('member');
				$thisuser =& $member_handler->getUser($rc_conf['reg_codes_conf_id']);
				$member_handler->activateUser($thisuser);
				redirect_header('user.php',7,_US_REGPROCESSSUCCESS . $xoopsConfig['sitename']);
			}
		
		}
		include 'footer.php'; 													
		break;  																
	}
	
case 'newuser':

	// security check -- now happens in formread.php
//	if (!$GLOBALS['xoopsSecurity']->check()) {
//	    echo implode('<br />', $GLOBALS['xoopsSecurity']->getErrors());
//		exit("Error: your session has expired.  Please click the <i>Back</i> button and then reload the page."); // added an error message
//	}

	// start the page
	include 'header.php';

	// check for validity of the user submission
	$stop = '';
	if ($xoopsConfigUser['reg_dispdsclmr'] != 0 && $xoopsConfigUser['reg_disclaimer'] != '') {
		if (empty($agree_disc)) {
			$stop .= _US_UNEEDAGREE.'<br />';
		}
	}
	$stop .= userCheck($uname, $email, $pass, $vpass, $regcode);

	$doover = false;

	if($stop) {
		$stoparray = explode("<br />", $stop);
		array_pop($stoparray);
		print "\n<script type='text/javascript'>\n";
		foreach($stoparray as $thisstop) {
			print "	alert('".str_replace("'", "\'", html_entity_decode($thisstop))."')\n";
		}
		//print "	window.location.href=\"" . XOOPS_URL . "/register.php?code=" . urlencode($_POST['userprofile_regcode']) . "&name=" . urlencode($_POST['userprofile_name']) . "&uname=" . urlencode($uname) . "&email=" . urlencode($email) . "&timezone_offset=" . urlencode($timezone_offset) . "&viewemail=" . urlencode($user_viewemail) . "\";\n";
		print "</script>\n";
//		/* Make sure that code below does not get executed when we redirect. */
//		exit;

		$doover = true;

	}


	// IF THERE WERE NO ERROR CONDITIONS, THEN PROCEED
	if(!$doover) {
		// pass these to createMember...
		//$user_viewemail
      	//$uname
      	//$name
      	//$email
      	//$pass
      	//$timezone_offset
      	$approval_groups = checkApproval($regcode);
      	list($newid, $actkey) = createMember(array('regcode'=>$regcode, 'approval'=>$approval_groups, 'user_viewemail'=>$user_viewemail, 'uname'=>$uname, 'name'=>$name, 'email'=>$email, 'pass'=>$pass, 'timezone_offset'=>$timezone_offset));
      	// assign user to correct groups based on the registration code and log them in and save the rest of their info

      	$redirect = processCode($regcode, $newid);
      	if(!$redirect) { $redirect = XOOPS_URL; }
      	if(is_array($approval_groups)) {										 	//nmc 2007.03.22
       		saveProfileInfo($newid, false, processCode($regcode, $newid, true)); 	//nmc 2007.03.22
          // false means no redirect (true on processCode means we're just gathering the groups, not assigning memberships)
          confirmUser($name, $email, $newid, $actkey, $regcode); 					//nmc 2007.03.22
    		} elseif ($approval_groups == "user") {										//nmc 2007.03.22
      		saveProfileInfo($newid, false, processCode($regcode, $newid, true)); 	//nmc 2007.03.22
    			// false means no redirect (true on processCode means we're just gathering the groups, not assigning memberships)
      		notifyAdmin($name, $email, $newid, $actkey, $approval_groups); 			//nmc 2007.03.22
      	} else {
      		loginUser($uname, $pass);						
      		saveProfileInfo($newid, $redirect);
      	}
      	break;
	} else {
	// THERE WAS AN ERROR IN WHAT WAS SUBMITTED SO LET'S TRY AGAIN!
	// conditions below very similar (identical?) to the conditions in the other op, since the behaviour is supposed to be the same

		include 'header.php';

		if(validateRegCode($regcode)) {
			// display the form (when "new" param is passed to displayform, the $groups array is spoofed based on the regcode
      	     	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
	           	// determine which form the "User Profile" is
      	     	$module_handler =& xoops_gethandler('module');
           		$formulizeModule =& $module_handler->getByDirname("formulize");
	           	$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
      	     	$fid = $formulizeConfig['profileForm'];
           		$doneArray[0] = "{NOBUTTON}";
	           	$doneArray[1] = _formulize_CREATEACT;
      	     	displayform($fid, "", "", "", $doneArray, "", "", "", "", "", "", "new");
		} else {
			if($regcode) { // a regcode was passed in but is invalid...
      			include XOOPS_ROOT_PATH . "/modules/reg_codes/language/".$xoopsConfig['language']."/modinfo.php";
      			print "\n<script type='text/javascript'>\n";
      			$error = 'ERROR: ' . _REGFORM_REGCODES_INVALID;
      			print "	alert('".str_replace("'", "\'", html_entity_decode($error))."')\n";
      			print "</script>\n";
      		}
      		include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
      		include_once XOOPS_ROOT_PATH . "/modules/reg_codes/language/".$xoopsConfig['language']."/modinfo.php";
      		$codeform = new xoopsThemeForm(_REGFORM_REGCODES_REGCODE, "register", "register.php", "post", true);
      		$reg_code_box = new XoopsFormText("", "userprofile_regcode", 13, 255, ''); 
      		$reg_code_submit = new XoopsFormButton("", "submit", _US_SUBMIT, "submit");
      		$codeform->insertBreak($reg_code_box->render() . "&nbsp;&nbsp;&nbsp;" . $reg_code_submit->render(), "head");
			if(empty($xoopsConfigUser['allow_register'])) {
	      		$codeform->insertBreak("<b>" . _REGFORM_REGCODES_HELP2a . "<a href=\"mailto:" . $xoopsConfig['adminmail'] . "\">" . _REGFORM_REGCODES_HELP2b . "</a>" . _REGFORM_REGCODES_HELP2c . "</b>", "even");
			} else {
				$codeform->insertBreak("<b>" . _REGFORM_REGCODES_HELP . "</b>", "even");
			}
      		print $codeform->render();
		}
		include 'footer.php';
	}
	
	break;
case 'register': // if we're not saving a full blown user
default:
	include 'header.php';

	// show reg code box only, then show the form (or go direct to form is code is in URL) -- APRIL 27, 2006

	// if we have a code from the URL or from the form where users can type on in, then process the code
	if(validateRegCode($regcode)) {
		// check to see if the code is an "instant account" code, and if so, make their account and redirect
		global $xoopsDB;
		$instantq = "SELECT reg_codes_instant FROM " . $xoopsDB->prefix("reg_codes") . " WHERE reg_codes_code=\"$regcode\"";
		$resinstantq = $xoopsDB->query($instantq);
		$arrayinstantq = $xoopsDB->fetchArray($resinstantq); // just get first record, there should be only one
		if($arrayinstantq['reg_codes_instant'] == 1) { // if instant account creation is on...
			$extra = substr(time() - 35, -8);
			$extra++;
			$uname = "u$extra";
			$pass = "p$extra";
			$email = "$extra@$extra.com";
			$approval_groups = checkApproval($regcode);
			list($newid, $actkey) = createMember(array('regcode'=>$regcode, 'approval'=>$approval_groups, 'user_viewemail'=>'', 'uname'=>$uname, 'name'=>'Instant Account', 'email'=>$email, 'pass'=>$pass, 'timezone_offset'=>''));
			$redirect = processCode($regcode, $newid);
			if(!$redirect) { $redirect = XOOPS_URL; }
//			if ($xoopsConfigUser['activation_type'] == 2) {
			global $xoopsConfig;
			include_once XOOPS_ROOT_PATH . "/modules/formulize/language/".$xoopsConfig['language']."/main.php";
			// note: setting approval groups on instant account codes does not make much sense, but is possible (some scenario where it makes sense may yet be devised)
	      	if(is_array($approval_groups)) { 									// nmc 2007.03.22
				confirmUser($name, $email, $newid, $actkey, $regcode); 			// nmc 2007.03.22
			} elseif ($approval_groups == "user") { 							// nmc 2007.03.22
	      		notifyAdmin($name, $email, $newid, $actkey, $approval_groups);  // nmc 2007.03.22
			} else { 															// nmc 2007.03.22
	      		loginUser($uname, $pass); 										// nmc 2007.03.22
	      	} 																	// nmc 2007.03.22
			redirect_header($redirect, 4, _formulize_ACTCREATED); 
		} else { 
			// display the form (when "new" param is passed to displayform, the $groups array is spoofed based on the regcode
             	include_once XOOPS_ROOT_PATH . "/modules/formulize/include/formdisplay.php";
             	// determine which form the "User Profile" is
             	$module_handler =& xoops_gethandler('module');
             	$formulizeModule =& $module_handler->getByDirname("formulize");
             	$formulizeConfig =& $config_handler->getConfigsByCat(0, $formulizeModule->getVar('mid'));
             	$fid = $formulizeConfig['profileForm'];
             	$doneArray[0] = "{NOBUTTON}";
             	$doneArray[1] = _formulize_CREATEACT;
             	displayform($fid, "", "", "", $doneArray, "", "", "", "", "", "", "new");
		}
	} else { // display the regcode form
		if($regcode) { // a regcode was passed in but is invalid...
			include XOOPS_ROOT_PATH . "/modules/reg_codes/language/".$xoopsConfig['language']."/modinfo.php";
			print "\n<script type='text/javascript'>\n";
			$error = 'ERROR: ' . _REGFORM_REGCODES_INVALID;
			print "	alert('".str_replace("'", "\'", html_entity_decode($error))."')\n";
			print "</script>\n";
		}
		include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";
		include_once XOOPS_ROOT_PATH . "/modules/reg_codes/language/".$xoopsConfig['language']."/modinfo.php";
		$codeform = new xoopsThemeForm(_REGFORM_REGCODES_REGCODE, "register", "register.php", "post", true);
		$reg_code_box = new XoopsFormText("", "userprofile_regcode", 13, 255, ''); 
		$reg_code_submit = new XoopsFormButton("", "submit", _US_SUBMIT, "submit");
		$codeform->insertBreak($reg_code_box->render() . "&nbsp;&nbsp;&nbsp;" . $reg_code_submit->render(), "head");
		if(empty($xoopsConfigUser['allow_register'])) {
      		$codeform->insertBreak("<b>" . _REGFORM_REGCODES_HELP2a . "<a href=\"mailto:" . $xoopsConfig['adminmail'] . "\">" . _REGFORM_REGCODES_HELP2b . "</a>" . _REGFORM_REGCODES_HELP2c . "</b>", "even");
		} else {
      		$codeform->insertBreak("<b>" . _REGFORM_REGCODES_HELP . "</b>", "even");
		}
		print $codeform->render();
	}
	include 'footer.php';
	break;
}

?>
