<?php

/**
* Admin ImpressCMS Blocks
*
* List, add, edit and delete block objects
*
* @copyright	The ImpressCMS Project <http://www.impresscms.org>
* @license		GNU General Public License (GPL) <http://www.gnu.org/licenses/old-licenses/gpl-2.0.html>
* @since		ImpressCMS 1.2
* @package Administration
* @version		$Id: main.php 9517 2009-11-09 18:48:53Z Phoenyx $
* @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
*/

if (!is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin($icmsModule->mid())) {
	exit ("Access Denied");
}

/**
 * Edit a block
 *
 * @param int $bid ID of block to be edited
 * @param bool $clone Set to 'true' if the block is being cloned
 */
function editblock($bid = 0, $clone = false) {
	global $icms_block_handler, $icmsAdminTpl;

	$blockObj = $icms_block_handler->get($bid);

	if (!$blockObj->isNew() && $blockObj->getVar('edit_func') != '') $blockObj->showFieldOnForm('options');
	if (!$clone && !$blockObj->isNew()) {	
		$sform = $blockObj->getForm(_AM_SYSTEM_BLOCKSADMIN_EDIT, 'addblock');
		$sform->assign($icmsAdminTpl);
	} else {
		if ($clone) {
			if ($blockObj->getVar('block_type') != 'C') {
				$blockObj->setVar('block_type', 'K');
				$blockObj->hideFieldFromForm('content');
				$blockObj->hideFieldFromForm('c_type');
			}
			$blockObj->setVar('bid', '0');
			$blockObj->setNew();
		} else {
			$blockObj->setVar('block_type', 'C');
		}
		$sform = $blockObj->getForm(_AM_SYSTEM_BLOCKSADMIN_CREATE, 'addblock');
		$sform->assign($icmsAdminTpl);
	}
	$icmsAdminTpl->assign('bid', $bid);
	$icmsAdminTpl->display('db:admin/blocksadmin/system_adm_blocksadmin.html');
}

$icms_block_handler = xoops_getmodulehandler('blocksadmin');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array (
	'mod',
	'changedField',
	'addblock',
	'del',
	'clone',
	'up',
	'down',
	'visible',
	'change_blocks',
	''
);

if (isset ($_GET['op']))
	$clean_op = htmlentities($_GET['op']);
if (isset ($_POST['op']))
	$clean_op = htmlentities($_POST['op']);

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_bid = isset ($_GET['bid']) ? ( int ) $_GET['bid'] : 0;
$clean_bid = isset ($_POST['bid']) ? ( int ) $_POST['bid'] : $clean_bid;

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
 */
if (in_array($clean_op, $valid_op, true)) {
	switch ($clean_op) {
		case 'visible' :
			$icms_block_handler->changeVisible($bid);
			$rtn = '/modules/system/admin.php?fct=blocksadmin';
			if (isset ($_GET['sortsel']))
				$rtn .= '&amp;sortsel=' . $_GET['sortsel'] . '&amp;ordersel=' . $_GET['ordersel'] . '&amp;limitsel=' . $_GET['limitsel'] . '&amp;startbid=' . $_GET['startbid'];
			if (isset ($_GET['rtn']))
				redirect_header(ICMS_URL . base64_decode($_GET['rtn']));
			else
				redirect_header(ICMS_URL . $rtn);
			break;

		case "up" :
			$icms_block_handler->upWeight($bid);
			$rtn = '/modules/system/admin.php?fct=blocksadmin';
			if (isset ($_GET['sortsel']))
				$rtn .= '&amp;sortsel=' . $_GET['sortsel'] . '&amp;ordersel=' . $_GET['ordersel'] . '&amp;limitsel=' . $_GET['limitsel'] . '&amp;startbid=' . $_GET['startbid'];
			if (isset ($_GET['rtn']))
				redirect_header(ICMS_URL . base64_decode($_GET['rtn']));
			else
				redirect_header(ICMS_URL . $rtn);
			break;

		case "down" :
			$icms_block_handler->downWeight($bid);
			$rtn = '/modules/system/admin.php?fct=blocksadmin';
			if (isset ($_GET['sortsel']))
				$rtn .= '&amp;sortsel=' . $_GET['sortsel'] . '&amp;ordersel=' . $_GET['ordersel'] . '&amp;limitsel=' . $_GET['limitsel'] . '&amp;startbid=' . $_GET['startbid'];
			if (isset ($_GET['rtn']))
				redirect_header(ICMS_URL . base64_decode($_GET['rtn']));
			else
				redirect_header(ICMS_URL . $rtn);
			break;

		case "clone" :
			xoops_cp_header();
			editblock($clean_bid, true);
			break;

		case "mod" :
		case "changedField" :
			icms_cp_header();
			editblock($clean_bid);
			break;

		case "addblock" :
			include_once ICMS_ROOT_PATH . "/kernel/icmspersistablecontroller.php";
			$controller = new IcmsPersistableController($icms_block_handler);
			$controller->storeFromDefaultForm(_AM_SYSTEM_BLOCKSADMIN_CREATED, _AM_SYSTEM_BLOCKSADMIN_MODIFIED);
			break;

		case "del" :
			include_once ICMS_ROOT_PATH . "/kernel/icmspersistablecontroller.php";
			$controller = new IcmsPersistableController($icms_block_handler);
			$controller->handleObjectDeletion();

			break;

		case "change_blocks" :
			foreach ($_POST['SystemBlocksadmin_objects'] as $k => $v) {
				$changed = false;
				$obj = $icms_block_handler->get($v);

				if ($obj->getVar('side', 'e') != $_POST['block_side'][$k]) {
					$obj->setVar('side', intval($_POST['block_side'][$k]));
					$changed = true;
				}
				if ($obj->getVar('weight', 'e') != $_POST['block_weight'][$k]) {
					$obj->setVar('weight', intval($_POST['block_weight'][$k]));
					$changed = true;
				}
				if ($changed) {
					$icms_block_handler->insert($obj);
				}
			}

			$rtn = '/modules/system/admin.php?fct=blocksadmin';
			if (isset ($_GET['sortsel']))
				$rtn .= '&amp;sortsel=' . $_GET['sortsel'] . '&amp;ordersel=' . $_GET['ordersel'] . '&amp;limitsel=' . $_GET['limitsel'] . '&amp;startbid=' . $_GET['startbid'];
			if (isset ($_GET['rtn']))
				redirect_header(ICMS_URL . base64_decode($_GET['rtn']), 2, _AM_SYSTEM_BLOCKSADMIN_MODIFIED);
			else
				redirect_header(ICMS_URL . $rtn, 2, _AM_SYSTEM_BLOCKSADMIN_MODIFIED);

			break;

		default :

			icms_cp_header();
			include_once ICMS_ROOT_PATH . "/kernel/icmspersistabletable.php";
			$objectTable = new IcmsPersistableTable($icms_block_handler);
			$objectTable->addColumn(new IcmsPersistableColumn('visible', 'center'));
			$objectTable->addColumn(new IcmsPersistableColumn('name'));
			$objectTable->addColumn(new IcmsPersistableColumn('title', _GLOBAL_LEFT, false, 'getAdminViewItemLink'));
			$objectTable->addColumn(new IcmsPersistableColumn('mid'));
			$objectTable->addColumn(new IcmsPersistableColumn('side', 'center', false, 'getSideControl'));
			$objectTable->addColumn(new IcmsPersistableColumn('weight', 'center', false, 'getWeightControl'));

			$objectTable->addIntroButton('addpost', 'admin.php?fct=blocksadmin&amp;op=mod', _AM_SYSTEM_BLOCKSADMIN_CREATE);
			$objectTable->addQuickSearch(array (
				'title',
				'name'
			));

			$objectTable->addFilter('mid', 'getModulesArray');
			$objectTable->addFilter('visible', 'getVisibleStatusArray');
			$objectTable->addFilter('side', 'getBlockPositionArray');

			$objectTable->addCustomAction('getBlankLink');
			$objectTable->addCustomAction('getUpActionLink');
			$objectTable->addCustomAction('getDownActionLink');
			$objectTable->addCustomAction('getCloneActionLink');

			$objectTable->addActionButton('change_blocks', false, _SUBMIT);

			$icmsAdminTpl->assign('icms_block_table', $objectTable->fetch());

			$icmsAdminTpl->display('db:admin/blocksadmin/system_adm_blocksadmin.html');
			break;
	}
	xoops_cp_footer();
}
/**
 * If you want to have a specific action taken because the user input was invalid,
 * place it at this point. Otherwise, a blank page will be displayed
 */
?>