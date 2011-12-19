<?php
/**
 * Extended User Profile
 *
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          The SmartFactory <www.smartfactory.ca>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id: finduser.php 20113 2010-09-08 19:12:39Z phoenyx $
 */

//die('Sorry, this feature is not active yet.');
include "admin_header.php";
$op = isset($_REQUEST['op']) ? trim($_REQUEST['op']) : '';
$profile_smartuser_handler = icms_getModuleHandler('smartuser', basename(dirname(dirname(__FILE__))), 'profile');
$hidden_fields_form = array('last_login', 'posts', 'notify_method', 'notify_mode', 'uorder', 'umode', 'theme', 'user_mailok', 'attachsig', 'user_viewemail', 'user_regdate', 'timezone_offset', 'openid', 'user_viewoid');
$hidden_fields_results = array('notify_method', 'notify_mode', 'uorder', 'umode', 'theme', 'user_mailok', 'attachsig', 'user_viewemail', 'timezone_offset', 'user_sig', 'user_regdate', 'last_login', 'openid', 'user_viewoid');
switch ($op) {
	case "post" :
		include_once ICMS_ROOT_PATH."/modules/".PROFILE_DIRNAME."/class/smartuser.php";
 		$fields = $profile_smartuser_handler->getFields();
		$criteria = new icms_db_criteria_Compo();
		if ($_REQUEST['uname'] != '') $criteria->add(new icms_db_criteria_Item('uname', '%'.$_REQUEST['uname'].'%', 'LIKE'));
		if ($_REQUEST['email'] != '') $criteria->add(new icms_db_criteria_Item('email', '%'.$_REQUEST['email'].'%', 'LIKE'));

		foreach ($fields as $key =>$field){
    		if (isset($_REQUEST[$key]) && $_REQUEST[$key] != '') $criteria->add(new icms_db_criteria_Item($key, '%'.$_REQUEST[$key].'%', 'LIKE'));
    	}

		icms_cp_header();
		icms_adminMenu(11, _AM_SPROFILE_FINDUSER);

		$objectTable = new icms_ipf_view_Table($profile_smartuser_handler, $criteria, array());
		$objectTable->addWithSelectedActions(array('export_sel' => _CO_ICMS_EXPORT));
		$objectTable->setTableId('profile_users');
		$objectTable->addColumn(new icms_ipf_view_Column('uname', 'center', 100, 'getUserLink',  false,_AM_SPROFILE_UNAME));
		$objectTable->addColumn(new icms_ipf_view_Column('email', 'center', 100, 'getUserEail',  false,_AM_SPROFILE_EMAIL));

		foreach ($fields as $key => $custom_field){
			if (!in_array($key, $hidden_fields_results)){
				$objectTable->addColumn(new icms_ipf_view_Column($key, 'center', 100, false, false, $custom_field->getVar('field_title')));
			}
		}

		$objectTable->addActionButton('export', _SUBMIT, _AM_SPROFILE_EXPORT_ALL);
		$objectTable->renderD();
		unset($criteria);

		break;
	case 'with_selected_actions':
		if ($_POST["selected_action"] == 'export_sel') {
			$criteria = new icms_db_criteria_Compo();
			$criteria->add(new icms_db_criteria_Item('uid', '('.implode(', ', $_POST['selected_icms_persistableobjects']).')', 'IN'));

			$fields = $profile_smartuser_handler->getFields();

			$export_fields = array();
			foreach ($custom_fields as $key => $custom_field){
				if ($custom_field->getVar('exportable') == '1') $export_fields[] = $key;
			}
			$icms_export = new icms_ipf_export_Handler($profile_smartuser_handler, $criteria, $export_fields);
			$icms_export->render(time().'_users.csv');
			exit;
		}
		break;
	case 'form':
	default:
		include_once ICMS_ROOT_PATH."/modules/".PROFILE_DIRNAME."/class/smartuser.php";
		icms_cp_header();
		icms_adminMenu(11, _AM_SPROFILE_FINDUSER);

 		$custom_fields = $profile_smartuser_handler->getFields();

		$sform = new icms_form_Theme(_AM_SPROFILE_FINDUSER, "op", xoops_getenv('PHP_SELF'), 'post');
		$uname_elt = new icms_form_elements_Text(sprintf(_AM_SPROFILE_FINDUSER_CRIT, _AM_SPROFILE_UNAME), 'uname', 50, 255, '');
	    $sform->addElement($uname_elt);
	    $email_elt = new icms_form_elements_Text(sprintf(_AM_SPROFILE_FINDUSER_CRIT, _AM_SPROFILE_EMAIL), 'email', 50, 255, '');
	    $sform->addElement($email_elt);
		foreach($custom_fields as $key =>$field){
    		if(!in_array($key, $hidden_fields_form)){
	    		$elt = new icms_form_elements_Text(sprintf(_AM_SPROFILE_FINDUSER_CRIT, $field->getVar('field_title')), $key, 50, 255, '');
	    		$sform->addElement($elt);
	    		unset($elt);
    		}
    	}
    	$button_tray = new icms_form_elements_Tray('', '');
		$hidden = new icms_form_elements_Hidden('op', 'post');
		$button_tray->addElement($hidden);
    	$butt_find = new icms_form_elements_Button('', '', _SUBMIT, 'submit');
		$button_tray->addElement($butt_find);
		$butt_cancel = new icms_form_elements_Button('', '', _CANCEL, 'button');
		$butt_cancel->setExtra('onclick="history.go(-1)"');
		$button_tray->addElement($butt_cancel);
		$sform->addElement($button_tray);
		$sform->display();
		break;
}

icms_cp_footer();
?>