<?php
/**
* ImpressCMS Customtags
*
* @copyright	The ImpressCMS Project http://www.impresscms.org/
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		Administration
* @since		1.1
* @author		marcan <marcan@impresscms.org>
* @version		$Id: main.php 9409 2009-09-18 18:05:15Z skenow $
*/
if ( !is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin($icmsModule->mid()) ) {
    exit("Access Denied");
}

function editcustomtag($showmenu = false, $customtagid = 0, $clone=false)
{
	global $icms_customtag_handler, $icmsAdminTpl;

	xoops_cp_header();

	$customtagObj = $icms_customtag_handler->get($customtagid);

	if (!$clone && !$customtagObj->isNew()){

		$sform = $customtagObj->getForm(_CO_ICMS_CUSTOMTAG_EDIT, 'addcustomtag');

		$sform->assign($icmsAdminTpl);
		$icmsAdminTpl->assign('icms_custom_tag_title', _CO_ICMS_CUSTOMTAG_EDIT_INFO);
		$icmsAdminTpl->display('db:admin/customtag/system_adm_customtag.html');
	} else {
		$customtagObj->setVar('customtagid', 0);
		$customtagObj->setVar('tag', '');

		$sform = $customtagObj->getForm(_CO_ICMS_CUSTOMTAG_CREATE, 'addcustomtag');
		$sform->assign($icmsAdminTpl);

		$icmsAdminTpl->assign('icms_custom_tag_title', _CO_ICMS_CUSTOMTAG_CREATE_INFO);
		$icmsAdminTpl->display('db:admin/customtag/system_adm_customtag.html');
	}
}
icms_loadLanguageFile('system', 'common');

$icms_customtag_handler = xoops_getmodulehandler('customtag');

if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_POST['op']))?trim(StopXSS($_POST['op'])):((isset($_GET['op']))?trim(StopXSS($_GET['op'])):'');

switch ($op) {
	case "mod":

		$customtagid = isset($_GET['customtagid']) ? intval($_GET['customtagid']) : 0 ;

		editcustomtag(true, $customtagid);

		break;

	case "clone":

		$customtagid = isset($_GET['customtagid']) ? intval($_GET['customtagid']) : 0 ;

		editcustomtag(true, $customtagid, true);
		break;

	case "addcustomtag":
        include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
        $controller = new IcmsPersistableController($icms_customtag_handler);
		$controller->storeFromDefaultForm(_CO_ICMS_CUSTOMTAG_CREATED, _CO_ICMS_CUSTOMTAG_MODIFIED);
		break;

	case "del":
		include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
	    $controller = new IcmsPersistableController($icms_customtag_handler);
		$controller->handleObjectDeletion();

		break;

	default:

		xoops_cp_header();

		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";

		$objectTable = new IcmsPersistableTable($icms_customtag_handler);
		$objectTable->addColumn(new IcmsPersistableColumn('name', _GLOBAL_LEFT, 150, 'getCustomtagName'));
		$objectTable->addColumn(new IcmsPersistableColumn('description', _GLOBAL_LEFT));
		$objectTable->addColumn(new IcmsPersistableColumn(_CO_ICMS_CUSTOMTAGS_TAG_CODE, 'center', 200, 'getXoopsCode'));
		$objectTable->addColumn(new IcmsPersistableColumn('language', 'center', 150));

		$objectTable->addIntroButton('addcustomtag', 'admin.php?fct=customtag&amp;op=mod', _CO_ICMS_CUSTOMTAG_CREATE);

		$objectTable->addQuickSearch(array('title', 'summary', 'description'));

		$objectTable->addCustomAction('getCloneLink');

		$icmsAdminTpl->assign('icms_customtag_table', $objectTable->fetch());

		$icmsAdminTpl->assign('icms_custom_tag_explain', true);
		$icmsAdminTpl->assign('icms_custom_tag_title', _CO_ICMS_CUSTOMTAGS_DSC);

		$icmsAdminTpl->display(ICMS_ROOT_PATH . '/modules/system/templates/admin/customtag/system_adm_customtag.html');

		break;
}

xoops_cp_footer();

?>