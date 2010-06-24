<?php
/**
* ImpressCMS Mimetypes
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

function editmimetype($showmenu = false, $mimetypeid = 0, $clone=false)
{
	global $icms_mimetype_handler, $icmsAdminTpl;

	xoops_cp_header();

	$mimetypeObj = $icms_mimetype_handler->get($mimetypeid);

	if (!$clone && !$mimetypeObj->isNew()){

		$sform = $mimetypeObj->getForm(_CO_ICMS_MIMETYPE_EDIT, 'addmimetype');

		$sform->assign($icmsAdminTpl);
		$icmsAdminTpl->assign('icms_mimetype_title', _CO_ICMS_MIMETYPE_EDIT_INFO);
		$icmsAdminTpl->display('db:admin/mimetype/system_adm_mimetype.html');
	} else {
		$mimetypeObj->setVar('mimetypeid', 0);
		$mimetypeObj->setVar('extension', '');

		$sform = $mimetypeObj->getForm(_CO_ICMS_MIMETYPE_CREATE, 'addmimetype');
		$sform->assign($icmsAdminTpl);

		$icmsAdminTpl->assign('icms_mimetype_title', _CO_ICMS_MIMETYPE_CREATE_INFO);
		$icmsAdminTpl->display('db:admin/mimetype/system_adm_mimetype.html');
	}
}
icms_loadLanguageFile('system', 'common');

$icms_mimetype_handler = xoops_getmodulehandler('mimetype');

if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_POST['op']))?trim(StopXSS($_POST['op'])):((isset($_GET['op']))?trim(StopXSS($_GET['op'])):'');

switch ($op) {
	case "mod":

		$mimetypeid = isset($_GET['mimetypeid']) ? intval($_GET['mimetypeid']) : 0 ;

		editmimetype(true, $mimetypeid);

		break;

	/*case "clone":

		$mimetypeid = isset($_GET['mimetypeid']) ? intval($_GET['mimetypeid']) : 0 ;

		editmimetype(true, $mimetypeid, true);
		break;*/

	case "addmimetype":
        include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
        $controller = new IcmsPersistableController($icms_mimetype_handler);
		$controller->storeFromDefaultForm(_CO_ICMS_MIMETYPE_CREATED, _CO_ICMS_MIMETYPE_MODIFIED);
		break;

	case "del":
		include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
	    $controller = new IcmsPersistableController($icms_mimetype_handler);
		$controller->handleObjectDeletion();

		break;

	default:

		xoops_cp_header();

		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";

		$objectTable = new IcmsPersistableTable($icms_mimetype_handler);
		$objectTable->addColumn(new IcmsPersistableColumn('name', _GLOBAL_LEFT, 150));
		$objectTable->addColumn(new IcmsPersistableColumn('extension', _GLOBAL_LEFT, 150));
		$objectTable->addColumn(new IcmsPersistableColumn('types', _GLOBAL_LEFT));

		$objectTable->addIntroButton('addmimetype', 'admin.php?fct=mimetype&amp;op=mod', _CO_ICMS_MIMETYPE_CREATE);

		$objectTable->addQuickSearch(array('name', 'extension', 'types'));

		$icmsAdminTpl->assign('icms_mimetype_table', $objectTable->fetch());

		$icmsAdminTpl->assign('icms_mimetype_explain', true);
		$icmsAdminTpl->assign('icms_mimetype_title', _CO_ICMS_MIMETYPES_DSC);

		$icmsAdminTpl->display(ICMS_ROOT_PATH . '/modules/system/templates/admin/mimetype/system_adm_mimetype.html');

		break;
}

xoops_cp_footer();

?>