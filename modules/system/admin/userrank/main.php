<?php
/**
 * ImpressCMS User Ranks.
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		System
 * @subpackage	Users
 * @since		1.2
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: main.php 21385 2011-03-30 14:27:08Z m0nty_ $
 */

if (!is_object(icms::$user) || !is_object($icmsModule) || !icms::$user->isAdmin($icmsModule->getVar("mid"))) {
	exit("Access Denied");
}

/**
 * Logic and rendering for editing user ranks
 * 
 * @param bool	$showmenu	Unnecessary? Not in any other location
 * @param int	$rank_id	Unique ID for the rank entry
 * @param bool	$clone		Are you cloning an existing rank?
 */
function edituserrank($showmenu = FALSE, $rank_id = 0, $clone = FALSE) {
	global $icms_userrank_handler, $icmsAdminTpl;

	icms_cp_header();
	$userrankObj = $icms_userrank_handler->get($rank_id);

	if (!$clone && !$userrankObj->isNew()) {
		$sform = $userrankObj->getForm(_CO_ICMS_USERRANKS_EDIT, "adduserrank");
		$sform->assign($icmsAdminTpl);
		$icmsAdminTpl->assign("icms_userrank_title", _CO_ICMS_USERRANKS_EDIT_INFO);
		$icmsAdminTpl->display("db:admin/userrank/system_adm_userrank.html");
	} else {
		$userrankObj->setVar("rank_id", 0);
		$sform = $userrankObj->getForm(_CO_ICMS_USERRANKS_CREATE, "adduserrank");
		$sform->assign($icmsAdminTpl);
		$icmsAdminTpl->assign("icms_userrank_title", _CO_ICMS_USERRANKS_CREATE_INFO);
		$icmsAdminTpl->display("db:admin/userrank/system_adm_userrank.html");
	}
}

$icms_userrank_handler = icms_getModuleHandler("userrank", "system");

if (!empty($_POST)) foreach ($_POST as $k => $v) ${$k} = StopXSS($v);
if (!empty($_GET)) foreach ($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_POST['op'])) ? trim(filter_input(INPUT_POST, 'op')) : ((isset($_GET['op'])) ? trim(filter_input(INPUT_GET, 'op')) : '');

switch ($op) {
	case "mod" :
		$rank_id = isset($_GET["rank_id"]) ? (int) $_GET["rank_id"] : 0;
		edituserrank(TRUE, $rank_id);
		break;

	case "clone" :
		$rank_id = isset($_GET["rank_id"]) ? (int) $_GET["rank_id"] : 0;
		edituserrank(TRUE, $rank_id, TRUE);
		break;

	case "adduserrank" :
		$controller = new icms_ipf_Controller($icms_userrank_handler);
		$controller->storeFromDefaultForm(_CO_ICMS_USERRANKS_CREATED, _CO_ICMS_USERRANKS_MODIFIED);
		break;

	case "del" :
		$controller = new icms_ipf_Controller($icms_userrank_handler);
		$controller->handleObjectDeletion();
		break;

	default:
		icms_cp_header();
		$objectTable = new icms_ipf_view_Table($icms_userrank_handler);
		$objectTable->addColumn(new icms_ipf_view_Column("rank_title", _GLOBAL_LEFT, FALSE, "getRankTitle"));
		$objectTable->addColumn(new icms_ipf_view_Column("rank_min"));
		$objectTable->addColumn(new icms_ipf_view_Column("rank_max"));
		$objectTable->addColumn(new icms_ipf_view_Column("rank_image", "center", 200, "getRankPicture", FALSE, FALSE, FALSE));
		$objectTable->addIntroButton("adduserrank", "admin.php?fct=userrank&amp;op=mod", _CO_ICMS_USERRANKS_CREATE);
		$objectTable->addQuickSearch(array("rank_title"));
		$objectTable->addCustomAction("getCloneLink");
		$icmsAdminTpl->assign("icms_userrank_table", $objectTable->fetch());
		$icmsAdminTpl->assign("icms_userrank_explain", TRUE);
		$icmsAdminTpl->assign("icms_userrank_title", _CO_ICMS_USERRANKS_DSC);
		$icmsAdminTpl->display(ICMS_MODULES_PATH . "/system/templates/admin/userrank/system_adm_userrank.html");
		break;
}

icms_cp_footer();