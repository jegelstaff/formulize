<?php
/**
* Admin ImpressCMS Block Positions
*
* List, add, edit and delete block objects
*
* @copyright	The ImpressCMS Project <http://www.impresscms.org>
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.2
* @package Administration
* @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
* @author		Rodrigo Pereira Lima (AKA TheRplima) <therplima@impresscms.org>
* @version		$Id: main.php 9409 2009-09-18 18:05:15Z skenow $
*/

if ( !is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin($icmsModule->mid()) ) {
	exit(_CT_ACCESS_DENIED);
}

function editblockposition($id = 0)
{
	global $icms_blockposition_handler, $icmsAdminTpl;

	$blockObj = $icms_blockposition_handler->get($id);

	if (!$blockObj->isNew()){
		$sform = $blockObj->getForm(_AM_SYSTEM_BLOCKSPADMIN_EDIT, 'addblockposition');
		$sform->assign($icmsAdminTpl);

	} else {
		$sform = $blockObj->getForm(_AM_SYSTEM_BLOCKSPADMIN_CREATE, 'addblockposition');
		$sform->assign($icmsAdminTpl);

	}
	$icmsAdminTpl->assign('id', $id);
	$icmsAdminTpl->assign('lang_badmin', _AM_SYSTEM_BLOCKSPADMIN_TITLE);
	$icmsAdminTpl->display('db:admin/blockspadmin/system_adm_blockspadmin.html');
}


$icms_blockposition_handler = xoops_getmodulehandler('blockspadmin');

$clean_op = '';

$valid_op = array ('mod','changedField','addblockposition', 'del', '');

if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

$clean_id = isset($_GET['id']) ? (int) $_GET['id'] : 0 ;
$clean_id = isset($_POST['id']) ? (int) $_POST['id'] : $clean_id;


if (in_array($clean_op,$valid_op,true)){

  switch ($clean_op) {
   	case "mod":
  	case "changedField":

  		icms_cp_header();

  		editblockposition($clean_id);
 		break;

  	case "addblockposition":
			include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
			$controller = new IcmsPersistableController($icms_blockposition_handler);
			$controller->storeFromDefaultForm(_AM_SYSTEM_BLOCKSPADMIN_CREATED, _AM_SYSTEM_BLOCKSPADMIN_MODIFIED);
 		break;

  	case "del":
			include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
			$controller = new IcmsPersistableController($icms_blockposition_handler);
			$controller->handleObjectDeletion();
 		break;

  	default:
  		icms_cp_header();
  		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";
  		$objectTable = new IcmsPersistableTable($icms_blockposition_handler, false);
  		$objectTable->addColumn(new IcmsPersistableColumn('pname'), 'center');
			$objectTable->addColumn(new IcmsPersistableColumn('title', false, false, 'getCustomTitle', false, false, false));
			$objectTable->addColumn(new IcmsPersistableColumn('description'));

  		$objectTable->addIntroButton('addblockposition', 'admin.php?fct=blockspadmin&amp;op=mod', _AM_SYSTEM_BLOCKSPADMIN_CREATE);
  		$objectTable->addQuickSearch(array('pname','title', 'description'));

  		$icmsAdminTpl->assign('icms_blockposition_table', $objectTable->fetch());

  		$icmsAdminTpl->assign('lang_badmin', _AM_SYSTEM_BLOCKSPADMIN_TITLE);
  		$icmsAdminTpl->assign('icms_blockposition_info', _AM_SYSTEM_BLOCKSPADMIN_INFO);

  		$icmsAdminTpl->display('db:admin/blockspadmin/system_adm_blockspadmin.html');
 		break;
  }
  icms_cp_footer();
}

?>