<?php
/**
 * Admin page to manage contents
 *
 * List, add, edit and delete content objects
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: content.php 21967 2011-07-04 18:41:00Z mcdonald3072 $
 */

/**
 * Edit a Content
 *
 * @param int $content_id Contentid to be edited
 */
function editcontent($content_id = 0, $clone = false, $content_pid = false) {
	global $content_content_handler, $icmsAdminTpl;

	$contentObj = $content_content_handler->get($content_id);

	if (!$clone && !$contentObj->isNew()) {
		$contentObj->hideFieldFromForm(array('content_published_date', 'content_updated_date', 'content_makesymlink'));
		$contentObj->setVar('content_updated_date', date(_DATESTRING));
		icms::$module->displayAdminMenu(0, _AM_CONTENT_CONTENTS . " > " . _CO_ICMS_EDITING);
		$sform = $contentObj->getForm(_AM_CONTENT_CONTENT_EDIT, 'addcontent');
		$sform->assign($icmsAdminTpl);
	} elseif (!$contentObj->isNew() && $clone) {
		$contentObj->hideFieldFromForm(array('content_published_date', 'content_updated_date'));
		$contentObj->setVar('content_id', 0);
		$contentObj->setNew();
		icms::$module->displayAdminMenu(0, _AM_CONTENT_CONTENTS . " > " . _AM_CONTENT_CONTENT_CLONE);
		$sform = $contentObj->getForm(_AM_CONTENT_CONTENT_CLONE, 'addcontent');
		$sform->assign($icmsAdminTpl);
	} else {
		$contentObj->hideFieldFromForm(array('content_published_date', 'content_updated_date'));
		$contentObj->setVar('content_published_date', date(_DATESTRING));
		if ($content_pid) {
			$contentObj->setVar('content_pid', $content_pid);
		}
		icms::$module->displayAdminMenu(0, _AM_CONTENT_CONTENTS . " > " . _CO_ICMS_CREATINGNEW);
		$sform = $contentObj->getForm(_AM_CONTENT_CONTENT_CREATE, 'addcontent');
		$sform->assign($icmsAdminTpl);
	}
	$icmsAdminTpl->display('db:content_admin_content.html');
}

include_once "admin_header.php";

$content_content_handler = icms_getModuleHandler('content', basename(dirname(dirname(__FILE__))), "content");
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array('mod', 'changedField', 'addcontent', 'del', 'clone', 'view', '');

if (isset($_GET ['op']))
$clean_op = htmlentities($_GET ['op']);
if (isset($_POST ['op']))
$clean_op = htmlentities($_POST ['op']);

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_content_id = isset($_GET ['content_id']) ?(int)$_GET ['content_id'] : 0;
$clean_content_id = isset($_POST ['content_id']) ?(int)$_POST ['content_id'] : $clean_content_id;
$clean_content_pid = isset($_GET ['content_pid']) ?(int)$_GET ['content_pid'] : 0;
$clean_content_pid = isset($_POST ['content_pid']) ?(int)$_POST ['content_pid'] : $clean_content_pid;

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
 */
if (in_array($clean_op, $valid_op, true)) {
	switch($clean_op) {
		case "clone" :
			icms_cp_header();
			editcontent($clean_content_id, true);
			break;

		case "mod" :
			icms_cp_header();
			editcontent($clean_content_id, false, $clean_content_pid);
			break;

		case "addcontent" :
			$controller = new icms_ipf_Controller($content_content_handler);
			$controller->storeFromDefaultForm(_AM_CONTENT_CONTENT_CREATED, _AM_CONTENT_CONTENT_MODIFIED);

			break;

		case "del" :
			$controller = new icms_ipf_Controller($content_content_handler);
			$controller->handleObjectDeletion();

			break;

		case "view" :
			$contentObj = $content_content_handler->get($clean_content_id);

			icms_cp_header();

			icms::$module->displayAdminMenu(0, _AM_CONTENT_CONTENTS . " > " . _PREVIEW .' > '. $contentObj->getVar('content_title'));

			$icmsAdminTpl->assign('content_content_singleview', $contentObj->displaySingleObject(true, false, array('edit','delete')));
			$icmsAdminTpl->display('db:content_admin_content.html');

			break;

		case "changedField" :
			foreach ($_POST['mod_content_Content_objects'] as $k=>$v){
				$changed = false;
				$obj = $content_content_handler->get($v);
				if ($obj->getVar('content_status','e') != $_POST['content_status'][$k]){
					$obj->setVar('content_status', (int)$_POST['content_status'][$k]);
					$changed = true;
				}
				if ($obj->getVar('content_visibility','e') != $_POST['content_visibility'][$k]){
					$obj->setVar('content_visibility', (int)$_POST['content_visibility'][$k]);
					$changed = true;
				}
				if ($changed){
					$content_content_handler->insert($obj);
				}
			}
			redirect_header('content.php', 2, _AM_CONTENT_CONTENT_MODIFIED);

			break;

		default :

			icms_cp_header();

			icms::$module->displayAdminMenu(0, _AM_CONTENT_CONTENTS);

			$criteria = new icms_db_criteria_Compo();
			$criteria->add(new icms_db_criteria_Item('content_pid', $clean_content_pid));

			$objectTable = new icms_ipf_view_Table($content_content_handler, $criteria);
			$objectTable->addColumn(new icms_ipf_view_Column('content_title', false, false, 'getPreviewItemLink'));
			$objectTable->addColumn(new icms_ipf_view_Column('content_subs', 'center', 100));
			$objectTable->addColumn(new icms_ipf_view_Column('counter', 'center', 100));
			$objectTable->addColumn(new icms_ipf_view_Column('content_status', 'center', 150, 'getContent_statusControl'));
			$objectTable->addColumn(new icms_ipf_view_Column('content_visibility', 'center', 150, 'getContent_visibleControl'));

			$objectTable->addColumn(new icms_ipf_view_Column('content_published_date', 'center', 150));

			$objectTable->addActionButton('changedField', false, _SUBMIT);
			$objectTable->addCustomAction('getViewItemLink');
			$objectTable->addCustomAction('getCloneItemLink');

			$objectTable->addIntroButton('addcontent', 'content.php?op=mod'.($clean_content_pid ? '&amp;content_pid=' . $clean_content_pid : ''), _AM_CONTENT_CONTENT_CREATE);

			$objectTable->addQuickSearch(array('content_title', 'content_body'));

			$objectTable->addFilter('content_status', 'getContent_statusArray');
			$objectTable->addFilter('content_uid', 'getPostersArray');
			$objectTable->addFilter('content_pid', 'getContentList');
			$objectTable->addFilter('content_visibility', 'getContent_visibleArray');
			$objectTable->addFilter('content_tags', 'getContent_tagsArray');

			$objectTable->addHeader('<p style="margin-bottom: 10px;">' . $content_content_handler->getBreadcrumbForPid($clean_content_pid) . '</p>');

			$icmsAdminTpl->assign('content_content_table', $objectTable->fetch());

			$icmsAdminTpl->display('db:content_admin_content.html');
			break;
	}
	icms_cp_footer();
} else {
	redirect_header(ICMS_URL, 3, _NOPERM);
}