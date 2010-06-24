<?php
/**
* ImpressCMS Ratings
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

function editrating($showmenu = false, $ratingid = 0)
{	
	global $icms_rating_handler, $icmsAdminTpl;

	icms_cp_header();
	
	$ratingObj = $icms_rating_handler->get($ratingid);

	if (!$ratingObj->isNew()){

		$sform = $ratingObj->getForm(_CO_ICMS_RATINGS_EDIT, 'addrating');
		
		$sform->assign($icmsAdminTpl);
		$icmsAdminTpl->assign('icms_rating_title', _CO_ICMS_RATINGS_EDIT_INFO);
		$icmsAdminTpl->display('db:admin/rating/system_adm_rating.html');
	} else {
        $ratingObj->hideFieldFromForm(array('item', 'itemid', 'uid', 'date', 'rate'));
        
		if (isset($_POST['op'])) {
			$controller = new IcmsPersistableController($icms_rating_handler);
			$controller->postDataToObject($ratingObj);

			if ($_POST['op'] == 'changedField') {
				switch($_POST['changedField']) {
					case 'dirname' :
						$ratingObj->showFieldOnForm(array('item', 'itemid', 'uid', 'date', 'rate'));
					break;
				}
			}
		}


		$sform = $ratingObj->getForm(_CO_ICMS_RATINGS_CREATE, 'addrating');
		$sform->assign($icmsAdminTpl);

		$icmsAdminTpl->assign('icms_rating_title', _CO_ICMS_RATINGS_CREATE_INFO);
		$icmsAdminTpl->display('db:admin/rating/system_adm_rating.html');		
	}
}
icms_loadLanguageFile('system', 'common');

$icms_rating_handler = icms_getmodulehandler('rating');

if(!empty($_POST)) foreach($_POST as $k => $v) ${$k} = StopXSS($v);
if(!empty($_GET)) foreach($_GET as $k => $v) ${$k} = StopXSS($v);
$op = (isset($_POST['op']))?trim(StopXSS($_POST['op'])):((isset($_GET['op']))?trim(StopXSS($_GET['op'])):'');

switch ($op) {
/*	case "mod":
	case "changedField";

		$ratingid = isset($_GET['ratingid']) ? intval($_GET['ratingid']) : 0 ;

		editrating(true, $ratingid);
		
		break;

	case "clone":

		$ratingid = isset($_GET['ratingid']) ? intval($_GET['ratingid']) : 0 ;

		editrating(true, $ratingid, true);
		break;

	case "addrating":
        include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
        $controller = new IcmsPersistableController($icms_rating_handler);
		$controller->storeFromDefaultForm(_CO_ICMS_RATINGS_CREATED, _CO_ICMS_RATINGS_MODIFIED, ICMS_URL . '/modules/system/admin.php?fct=rating');
		break;
*/
	case "del":
		include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
	    $controller = new IcmsPersistableController($icms_rating_handler);		
		$controller->handleObjectDeletion();

		break;

	default:

		icms_cp_header();
		
		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";
		
		$objectTable = new IcmsPersistableTable($icms_rating_handler, false, array('delete'));
		$objectTable->addColumn(new IcmsPersistableColumn('name', _GLOBAL_LEFT, false, 'getUnameValue'));
		$objectTable->addColumn(new IcmsPersistableColumn('dirname', _GLOBAL_LEFT));
		$objectTable->addColumn(new IcmsPersistableColumn('item', _GLOBAL_LEFT, false, 'getItemValue'));
		$objectTable->addColumn(new IcmsPersistableColumn('date', 'center', 150));
		$objectTable->addColumn(new IcmsPersistableColumn('rate', 'center', 60, 'getRateValue'));
		//$objectTable->addIntroButton('addrating', 'admin.php?fct=rating&op=mod', _CO_ICMS_RATINGS_CREATE);

		//$objectTable->addQuickSearch(array('title', 'summary', 'description'));


		$icmsAdminTpl->assign('icms_rating_table', $objectTable->fetch());
		
		$icmsAdminTpl->assign('icms_rating_explain', true);
		$icmsAdminTpl->assign('icms_rating_title', _CO_ICMS_RATINGS_DSC);

		$icmsAdminTpl->display(ICMS_ROOT_PATH . '/modules/system/templates/admin/rating/system_adm_rating.html');

		break;
}

icms_cp_footer();

?>