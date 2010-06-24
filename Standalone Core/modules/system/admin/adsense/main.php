<?php
/**
* ImpressCMS Adsenses
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Administration
* @since		1.2
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id$
*/

if ( !is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin($icmsModule->mid()) ) {
    exit("Access Denied");
}

function editadsense($showmenu = false, $adsenseid = 0, $clone=false)
{
	global $icms_adsense_handler, $icmsAdminTpl;

	icms_cp_header();

	$adsenseObj = $icms_adsense_handler->get($adsenseid);

	if (!$clone && !$adsenseObj->isNew()){

		$sform = $adsenseObj->getForm(_CO_ICMS_ADSENSES_EDIT, 'addadsense');

		$sform->assign($icmsAdminTpl);
		$icmsAdminTpl->assign('icms_adsense_title', _CO_ICMS_ADSENSES_EDIT_INFO);
		$icmsAdminTpl->display('db:admin/adsense/system_adm_adsense.html');
	} else {
		$adsenseObj->setVar('adsenseid', 0);
		$adsenseObj->setVar('tag', '');

		$sform = $adsenseObj->getForm(_CO_ICMS_ADSENSES_CREATE, 'addadsense');
		$sform->assign($icmsAdminTpl);

		$icmsAdminTpl->assign('icms_adsense_title', _CO_ICMS_ADSENSES_CREATE_INFO);
		$icmsAdminTpl->display('db:admin/adsense/system_adm_adsense.html');
	}
}
icms_loadLanguageFile('system', 'common');

$icms_adsense_handler = icms_getmodulehandler('adsense');

if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_POST['op']))?trim(StopXSS($_POST['op'])):((isset($_GET['op']))?trim(StopXSS($_GET['op'])):'');

switch ($op) {
	case "mod":

		$adsenseid = isset($_GET['adsenseid']) ? intval($_GET['adsenseid']) : 0 ;

		editadsense(true, $adsenseid);

		break;

	case "clone":

		$adsenseid = isset($_GET['adsenseid']) ? intval($_GET['adsenseid']) : 0 ;

		editadsense(true, $adsenseid, true);
		break;

	case "addadsense":
	    /*if(@include_once ICMS_ROOT_PATH ."/class/captcha/captcha.php") {
            $icmsCaptcha = IcmsCaptcha::instance();
            if(! $icmsCaptcha->verify() ) {
                  redirect_header('javascript:history.go(-1);', 3, $icmsCaptcha->getMessage());
                  exit;
            }
		}*/
        include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
        $controller = new IcmsPersistableController($icms_adsense_handler);
		$controller->storeFromDefaultForm(_CO_ICMS_ADSENSES_CREATED, _CO_ICMS_ADSENSES_MODIFIED);
		break;

	case "del":
		include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
	    $controller = new IcmsPersistableController($icms_adsense_handler);
		$controller->handleObjectDeletion();

		break;

	default:

		icms_cp_header();

		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";

		$objectTable = new IcmsPersistableTable($icms_adsense_handler);
		$objectTable->addColumn(new IcmsPersistableColumn('description', _GLOBAL_LEFT));
		$objectTable->addColumn(new IcmsPersistableColumn(_CO_ICMS_ADSENSE_TAG_CODE, 'center', 200, 'getXoopsCode'));
		//$objectTable->addColumn(new IcmsPersistableColumn('language', 'center', 150));

		$objectTable->addIntroButton('addadsense', 'admin.php?fct=adsense&amp;op=mod', _CO_ICMS_ADSENSES_CREATE);

		$objectTable->addQuickSearch(array('title', 'summary', 'description'));

		$objectTable->addCustomAction('getCloneLink');

		$icmsAdminTpl->assign('icms_adsense_table', $objectTable->fetch());

		$icmsAdminTpl->assign('icms_adsense_explain', true);
		$icmsAdminTpl->assign('icms_adsense_title', _CO_ICMS_ADSENSES_DSC);

		$icmsAdminTpl->display(ICMS_ROOT_PATH . '/modules/system/templates/admin/adsense/system_adm_adsense.html');

		break;
}

icms_cp_footer();

?>