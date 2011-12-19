<?php
/**
 * ImpressCMS User Ranks.
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Administration
 * @since		1.2
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: main.php 9409 2009-09-18 18:05:15Z skenow $
 */

if ( !is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin($icmsModule->mid()) ) {
    exit("Access Denied");
}

$icms_userrank_handler = icms_getmodulehandler('userrank');
function edituserrank($showmenu = false, $rank_id = 0, $clone=false)
{
    global $icms_userrank_handler, $icmsAdminTpl;

    icms_cp_header();

    $userrankObj = $icms_userrank_handler->get($rank_id);

    if (!$clone && !$userrankObj->isNew()){

        $sform = $userrankObj->getForm(_CO_ICMS_USERRANKS_EDIT, 'adduserrank');

        $sform->assign($icmsAdminTpl);
        $icmsAdminTpl->assign('icms_userrank_title', _CO_ICMS_USERRANKS_EDIT_INFO);
        $icmsAdminTpl->display('db:admin/userrank/system_adm_userrank.html');
    } else {
        $userrankObj->setVar('rank_id', 0);

        $sform = $userrankObj->getForm(_CO_ICMS_USERRANKS_CREATE, 'adduserrank');
        $sform->assign($icmsAdminTpl);

        $icmsAdminTpl->assign('icms_userrank_title', _CO_ICMS_USERRANKS_CREATE_INFO);
        $icmsAdminTpl->display('db:admin/userrank/system_adm_userrank.html');
    }
}
icms_loadLanguageFile('system', 'common');

if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_POST['op'])) ? trim(StopXSS($_POST['op'])) : ((isset($_GET['op'])) ? trim(StopXSS($_GET['op'])) : '');

switch ($op) {
    case 'mod' :

        $rank_id = isset($_GET['rank_id']) ? intval($_GET['rank_id']) : 0 ;

        edituserrank(true, $rank_id);

        break;

    case 'clone' :

        $rank_id = isset($_GET['rank_id']) ? intval($_GET['rank_id']) : 0 ;

        edituserrank(true, $rank_id, true);
        break;

    case 'adduserrank' :
        include_once ICMS_ROOT_PATH . '/kernel/icmspersistablecontroller.php' ;
        $controller = new IcmsPersistableController($icms_userrank_handler);
        $controller->storeFromDefaultForm(_CO_ICMS_USERRANKS_CREATED, _CO_ICMS_USERRANKS_MODIFIED);
        break;

    case 'del' :
        include_once ICMS_ROOT_PATH . '/kernel/icmspersistablecontroller.php' ;
        $controller = new IcmsPersistableController($icms_userrank_handler);
        $controller->handleObjectDeletion();

        break;

    default:

        icms_cp_header();

        include_once ICMS_ROOT_PATH . '/kernel/icmspersistabletable.php' ;

        $objectTable = new IcmsPersistableTable($icms_userrank_handler);
        $objectTable->addColumn(new IcmsPersistableColumn('rank_title', _GLOBAL_LEFT, false, 'getUserrankName'));
        $objectTable->addColumn(new IcmsPersistableColumn('rank_min', _GLOBAL_LEFT));
        $objectTable->addColumn(new IcmsPersistableColumn('rank_max', _GLOBAL_LEFT));
        $objectTable->addColumn(new IcmsPersistableColumn('rank_image', 'center', 200, 'getRankPicture', false, false, false));

        $objectTable->addIntroButton('adduserrank', 'admin.php?fct=userrank&amp;op=mod', _CO_ICMS_USERRANKS_CREATE);

        $objectTable->addQuickSearch( array('rank_title') );

        $objectTable->addCustomAction('getCloneLink');

        $icmsAdminTpl->assign('icms_userrank_table', $objectTable->fetch());

        $icmsAdminTpl->assign('icms_userrank_explain', true);
        $icmsAdminTpl->assign('icms_userrank_title', _CO_ICMS_USERRANKS_DSC);

        $icmsAdminTpl->display(ICMS_ROOT_PATH . '/modules/system/templates/admin/userrank/system_adm_userrank.html');

        break;
}

icms_cp_footer();

?>