<?php
/**
 * ImpressCMS Adsenses
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Administration
 * @since		1.2
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: main.php 20747 2011-02-03 02:22:45Z skenow $
 */

if (!is_object(icms::$user) || !is_object(icms::$module) || !icms::$user->isAdmin(icms::$module->getVar('mid'))) {
	exit("Access Denied");
}

/**
 * Edit AdSense entries
 * 
 * @param $showmenu		This parameter is not used (why is it here?)
 * @param $adsenseid	Unique identifier of the AdSense unit
 * @param $clone		Is this cloning an existing AdSense unit?
 */
function editadsense($showmenu = FALSE, $adsenseid = 0, $clone = FALSE) {
	global $icms_adsense_handler, $icmsAdminTpl;

	icms_cp_header();
	$adsenseObj = $icms_adsense_handler->get($adsenseid);

	if (!$clone && !$adsenseObj->isNew()) {
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

$icms_adsense_handler = icms_getModuleHandler("adsense", "system");

if (!empty($_POST)) foreach ($_POST as $k => $v) ${$k} = StopXSS($v);
if (!empty($_GET)) foreach ($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_POST['op'])) 
	? trim(StopXSS($_POST['op'])) 
	: ((isset($_GET['op'])) 
		? trim(StopXSS($_GET['op'])) 
		: '');

switch ($op) {
	case "mod":
		$adsenseid = isset($_GET['adsenseid']) ? (int) $_GET['adsenseid'] : 0 ;
		editadsense(TRUE, $adsenseid);
		break;

	case "clone":
		$adsenseid = isset($_GET['adsenseid']) ? (int) $_GET['adsenseid'] : 0 ;
		editadsense(TRUE, $adsenseid, TRUE);
		break;

	case "addadsense":
		$controller = new icms_ipf_Controller($icms_adsense_handler);
		$controller->storeFromDefaultForm(_CO_ICMS_ADSENSES_CREATED, _CO_ICMS_ADSENSES_MODIFIED);
		break;

	case "del":
		$controller = new icms_ipf_Controller($icms_adsense_handler);
		$controller->handleObjectDeletion();
		break;

	default:
		icms_cp_header();
		$objectTable = new icms_ipf_view_Table($icms_adsense_handler);
		$objectTable->addColumn(new icms_ipf_view_Column('description', _GLOBAL_LEFT));
		$objectTable->addColumn(new icms_ipf_view_Column(_CO_ICMS_ADSENSE_TAG_CODE, 'center', 200, 'getXoopsCode'));
		$objectTable->addIntroButton('addadsense', 'admin.php?fct=adsense&amp;op=mod', _CO_ICMS_ADSENSES_CREATE);
		$objectTable->addQuickSearch(array('title', 'summary', 'description'));
		$objectTable->addCustomAction('getCloneLink');
		$icmsAdminTpl->assign('icms_adsense_table', $objectTable->fetch());
		$icmsAdminTpl->assign('icms_adsense_explain', TRUE);
		$icmsAdminTpl->assign('icms_adsense_title', _CO_ICMS_ADSENSES_DSC);
		$icmsAdminTpl->display(ICMS_ROOT_PATH . '/modules/system/templates/admin/adsense/system_adm_adsense.html');
		break;
}

icms_cp_footer();
