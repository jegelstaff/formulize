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
 * @version         $Id$
 */
die('Sorry, this feature is not active yet.');
include 'header.php';
$op = isset($_REQUEST['op']) ? trim($_REQUEST['op']) : '';
$profile_smartuser_handler =& icms_getmodulehandler( 'smartuser', basename(  dirname(  dirname( __FILE__ ) ) ), 'profile' );
$hidden_fields_form = array('last_login', 'posts', 'notify_method', 'notify_mode', 'uorder', 'umode', 'theme', 'user_mailok', 'attachsig', 'user_viewemail', 'user_regdate', 'timezone_offset', 'openid', 'user_viewoid');
$hidden_fields_results = array('notify_method', 'notify_mode', 'uorder', 'umode', 'theme', 'user_mailok', 'attachsig', 'user_viewemail', 'timezone_offset', 'user_sig', 'user_regdate', 'last_login', 'openid', 'user_viewoid');
switch ($op) {
	case "post" :
		include_once ICMS_ROOT_PATH."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/class/smartuser.php";
 		$fields =& $profile_smartuser_handler->getFields();
		$criteria = new CriteriaCompo();
		if($_REQUEST['uname'] != ''){
			$criteria->add(new Criteria('uname', '%'.$_REQUEST['uname'].'%', 'LIKE'));
		}
		if($_REQUEST['email'] != ''){
			$criteria->add(new Criteria('email', '%'.$_REQUEST['email'].'%', 'LIKE'));
		}
		foreach($fields as $key =>$field){
    		if(isset($_REQUEST[$key]) && $_REQUEST[$key] != ''){
    			$criteria->add(new Criteria($key, '%'.$_REQUEST[$key].'%', 'LIKE'));
    		}
    	}

		//xoops_cp_header();
		xoops_cp_header();
		icms_adminMenu(6, _AM_SPROFILE_FINDUSER);

		echo "<a href='finduser.php'>"._AM_SPROFILE_BACK_TO_FORM."</a><br/>";
		include_once ICMS_KERNEL_PATH."icmspersistabletable.php";

		$objectTable = new IcmsPersistableTable($profile_smartuser_handler, $criteria, array());
		$objectTable->addWithSelectedActions(array('export_sel'=>_CO_ICMS_EXPORT));
		$objectTable->setTableId('profile_users');

		$custom_fields = $profile_smartuser_handler->getFields();
		$objectTable->addColumn(new IcmsPersistableColumn('uname', 'center', 100, 'getUserLink',  false,_AM_SPROFILE_UNAME));
		$objectTable->addColumn(new IcmsPersistableColumn('email', 'center', 100, 'getUserEail',  false,_AM_SPROFILE_EMAIL));

		foreach($custom_fields as $key => $custom_field){
			if(!in_array($key, $hidden_fields_results)){
				$objectTable->addColumn(new IcmsPersistableColumn($key, 'center', 100, false, false, $custom_field->getVar('field_title')));
			}
		}

		$objectTable->addActionButton('export', _SUBMIT, _AM_SPROFILE_EXPORT_ALL);

		$objectTable->render();
		unset($criteria);

		break;

	case 'with_selected_actions':
		//Not working for now
		if($_POST["selected_action"] == 'delete_sel'){

			if ($_POST['confirm']) {
				if($smartshop_transaction_handler->batchDelete(explode('|', intval($_POST['ids'])))){
					redirect_header("transaction.php", 2, _AM_SSHOP_TRANSDELETED);
					exit();
				}else{
					redirect_header("transaction.php", 2, _AM_SSHOP_TRANSDELETE_ERROR);
					exit();
				}
			} else {
				xoops_cp_header();
				icms_adminMenu(2, _AM_SSHOP_TRANSACTIONS);

				// no confirm: show deletion condition
				xoops_confirm(array('op' => 'with_selected_actions', 'selected_action'=>'delete_sel', 'ids' => implode('|', intval($_POST['selected_smartobjects'])), 'confirm' => 1), 'transaction.php', _AM_SSSHOP_DELETETHOSETRANS . " <br />'" .implode(', ', $_POST['selected_smartobjects']). "'. <br /> <br />", _AM_SSHOP_DELETE);
			}


		break;


		//end not working

		}elseif($_POST["selected_action"] == 'export_sel'){
				$criteria = new CriteriaCompo();
				$criteria->add(new Criteria('uid', '(' . implode(', ', $_POST['selected_smartobjects']) . ')', 'IN'));

				include_once(ICMS_KERNEL_PATH.'icmspersistableexport.php');
				$custom_fields = $profile_smartuser_handler->getFields();

				$fields = array();
				foreach($custom_fields as $key => $custom_field){
					if($custom_field->getVar('exportable') == '1'){
						$fields[] = $key;
					}
				}
				$smartObjectExport = new IcmsPersistableExport($profile_smartuser_handler, $criteria, $fields);
				$smartObjectExport->render(time().'_transactions.csv');
				exit;
			break;
		}

	case 'export':

		$criteria = new CriteriaCompo();

		include_once(ICMS_KERNEL_PATH.'icmspersistableexport.php');
		$custom_fields = $profile_smartuser_handler->getFields();

		$fields = array();
		foreach($custom_fields as $key => $custom_field){
			if($custom_field->getVar('exportable')){
				$fields[] = $key;
			}
		}

		foreach($custom_fields as $key =>$field){
    		if(isset($_REQUEST[$key]) && $_REQUEST[$key] != ''){
    			$criteria->add(new Criteria($key, '%'.$_REQUEST[$key].'%', 'LIKE'));
    		}
    	}
		$smartObjectExport = new IcmsPersistableExport($profile_smartuser_handler, $criteria, $fields);
		$smartObjectExport->render(time().'_transactions.csv');
		exit;
	break;


	case 'form':
	default:
		include_once ICMS_ROOT_PATH."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/class/smartuser.php";
		include_once ICMS_ROOT_PATH . '/class/xoopsformloader.php';
		//xoops_cp_header();
		xoops_cp_header();
		icms_adminMenu(6, _AM_SPROFILE_FINDUSER);

 		$custom_fields = $profile_smartuser_handler->getFields();

		$fields = array();
		foreach($custom_fields as $key => $custom_field){
			if($custom_field->getVar('exportable')){
				$fields[] = $key;
			}
		}
		$sform = new XoopsThemeForm(_AM_SPROFILE_FINDUSER, "op", xoops_getenv('PHP_SELF'), 'get');
		$uname_elt = new XoopsFormText(sprintf(_AM_SPROFILE_FINDUSER_CRIT, _AM_SPROFILE_UNAME), 'uname', 50, 255, '');
	    $sform->addElement($uname_elt);
	    $email_elt = new XoopsFormText(sprintf(_AM_SPROFILE_FINDUSER_CRIT, _AM_SPROFILE_EMAIL), 'email', 50, 255, '');
	    $sform->addElement($email_elt);
		foreach($custom_fields as $key =>$field){
    		if(!in_array($key, $hidden_fields_form)){
	    		$elt = new XoopsFormText(sprintf(_AM_SPROFILE_FINDUSER_CRIT, $field->getVar('field_title')), $key, 50, 255, '');
	    		$sform->addElement($elt);
	    		unset($elt);
    		}
    	}
    	$button_tray = new XoopsFormElementTray('', '');
		$hidden = new XoopsFormHidden('op', 'post');
		$button_tray->addElement($hidden);

    	$butt_find = new XoopsFormButton('', '', _SUBMIT, 'submit');
		//$butt_find->setExtra('onclick="this.form.elements.op.value=\'post\'"');
		$button_tray->addElement($butt_find);

		$butt_cancel = new XoopsFormButton('', '', _CANCEL, 'button');
		$butt_cancel->setExtra('onclick="history.go(-1)"');
		$button_tray->addElement($butt_cancel);

		$sform->addElement($button_tray);


		$sform->display();
		break;
}



xoops_cp_footer();
?>
