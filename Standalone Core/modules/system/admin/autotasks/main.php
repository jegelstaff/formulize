<?php
/**
 * ImpressCMS AUTOTASKSs
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2 alpha 2
 * @author		MekDrop <mekdrop@gmail.com>
 */
if ( !is_object($xoopsUser) || !is_object($xoopsModule) || !$xoopsUser->isAdmin($xoopsModule->mid()) ) {
    exit("Access Denied");
}

function editautotasks($showmenu = false, $autotasksid = 0, $clone=false)
{
	global $icms_autotasks_handler, $icmsAdminTpl;

	xoops_cp_header();

	$autotasksObj = $icms_autotasks_handler->get($autotasksid);

	if (!$clone && !$autotasksObj->isNew()){

		$sform = $autotasksObj->getForm(_CO_ICMS_AUTOTASKS_EDIT, 'addautotasks');
		$sform->assign($icmsAdminTpl);
		$icmsAdminTpl->display('db:admin/autotasks/system_adm_autotasks.html');
	} else {
		$autotasksObj->setVar('sat_id', 0);

		$sform = $autotasksObj->getForm(_CO_ICMS_AUTOTASKS_CREATE, 'addautotasks');
		$sform->assign($icmsAdminTpl);
		$icmsAdminTpl->display('db:admin/autotasks/system_adm_autotasks.html');
	}

}
icms_loadLanguageFile('system', 'common');

$icms_autotasks_handler = xoops_getmodulehandler('autotasks', 'system');

$op = '';

if (isset($_GET['op'])) $op = $_GET['op'];
if (isset($_POST['op'])) $op = $_POST['op'];

switch ($op) {
	case "mod":

		$autotasksid = isset($_GET['sat_id']) ? intval($_GET['sat_id']) : 0 ;

		editautotasks(true, $autotasksid);

		break;

	case "clone":

		$autotasksid = isset($_GET['sat_id']) ? intval($_GET['sat_id']) : 0 ;

		editautotasks(true, $autotasksid, true);
		break;

	case "addautotasks":
        include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
        $controller = new IcmsPersistableController($icms_autotasks_handler);
		$controller->storeFromDefaultForm(_CO_ICMS_AUTOTASKS_CREATED, _CO_ICMS_AUTOTASKS_MODIFIED, ICMS_URL . '/modules/system/admin.php?fct=autotasks');
		break;

	case "del":
		include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
	    $controller = new IcmsPersistableController($icms_autotasks_handler);
		$controller->handleObjectDeletion();

		break;

	default:

		xoops_cp_header();

		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";

		$objectTable = new IcmsPersistableTable($icms_autotasks_handler, false, array('edit'));
		$objectTable->addColumn(new IcmsPersistableColumn('sat_name', 'left', false, 'getNameForDisplay'));
		$objectTable->addColumn(new IcmsPersistableColumn('sat_repeat', 'center', 80, 'getRepeatForDisplay'));
		$objectTable->addColumn(new IcmsPersistableColumn('sat_interval', 'center', 80, 'getIntervalForDisplay'));
		$objectTable->addColumn(new IcmsPersistableColumn('sat_enabled', 'center', 80, 'getEnableForDisplay'));
		$objectTable->addColumn(new IcmsPersistableColumn('sat_onfinish', 'center', 120, 'getOnFinishForDisplay'));
		$objectTable->addColumn(new IcmsPersistableColumn('sat_type', 'center', 120, 'getTypeForDisplay'));
		$objectTable->addColumn(new IcmsPersistableColumn('sat_lastruntime', 'center', 180, 'getLastRunTimeForDisplay'));

		$objectTable->addIntroButton('addautotasks', 'admin.php?fct=autotasks&amp;op=mod', _CO_ICMS_AUTOTASKS_CREATE);

		$objectTable->addQuickSearch(array('title', 'summary', 'description'));

		$objectTable->addCustomAction('getDeleteButtonForDisplay');

		$icmsAdminTpl->assign('icms_autotasks_table', $objectTable->fetch());

		$icmsAdminTpl->display('db:admin/autotasks/system_adm_autotasks.html');

		//echo $objectTable->fetch();

		break;
}

xoops_cp_footer();

?>