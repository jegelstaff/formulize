<?php
/**
* Admin page to manage audios
*
* List, add, edit and delete audio objects
*
* @copyright	GNU General Public License (GPL)
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.3
* @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @package		profile
* @version		$Id$
*/

/**
 * Edit a Audio
 *
 * @param int $audio_id Audioid to be edited
*/
function editaudio($audio_id = 0)
{
	global $profile_audio_handler, $icmsModule, $icmsAdminTpl;

	$audioObj = $profile_audio_handler->get($audio_id);

	if (!$audioObj->isNew()){
		$icmsModule->displayAdminMenu(10, _AM_PROFILE_AUDIOS . " > " . _CO_ICMS_EDITING);
		$sform = $audioObj->getForm(_AM_PROFILE_AUDIO_EDIT, 'addaudio');
		$sform->assign($icmsAdminTpl);

	} else {
		$icmsModule->displayAdminMenu(10, _AM_PROFILE_AUDIOS . " > " . _CO_ICMS_CREATINGNEW);
		$sform = $audioObj->getForm(_AM_PROFILE_AUDIO_CREATE, 'addaudio');
		$sform->assign($icmsAdminTpl);

	}
	$icmsAdminTpl->display('db:profile_admin_audio.html');
}

include_once("admin_header.php");

$profile_audio_handler = icms_getModuleHandler('audio');
/** Use a naming convention that indicates the source of the content of the variable */
$clean_op = '';
/** Create a whitelist of valid values, be sure to use appropriate types for each value
 * Be sure to include a value for no parameter, if you have a default condition
 */
$valid_op = array ('mod','changedField','addaudio','del','view','');

if (isset($_GET['op'])) $clean_op = htmlentities($_GET['op']);
if (isset($_POST['op'])) $clean_op = htmlentities($_POST['op']);

/** Again, use a naming convention that indicates the source of the content of the variable */
$clean_audio_id = isset($_GET['audio_id']) ? (int) $_GET['audio_id'] : 0 ;

/**
 * in_array() is a native PHP function that will determine if the value of the
 * first argument is found in the array listed in the second argument. Strings
 * are case sensitive and the 3rd argument determines whether type matching is
 * required
*/
if (in_array($clean_op,$valid_op,true)){
  switch ($clean_op) {
  	case "mod":
  	case "changedField":

  		icms_cp_header();

  		editaudio($clean_audio_id);
  		break;
  	case "del":
  	    include_once ICMS_ROOT_PATH."/kernel/icmspersistablecontroller.php";
          $controller = new IcmsPersistableController($profile_audio_handler);
  		$controller->handleObjectDeletion();

  		break;
  	default:

  		icms_cp_header();

  		$icmsModule->displayAdminMenu(10, _AM_PROFILE_AUDIOS);

  		include_once ICMS_ROOT_PATH."/kernel/icmspersistabletable.php";
  		$objectTable = new IcmsPersistableTable($profile_audio_handler);
  		$objectTable->addColumn(new IcmsPersistableColumn('audio_id'));
  		$objectTable->addColumn(new IcmsPersistableColumn('uid_owner', false, false, 'getAudioSender'));
		$objectTable->addColumn(new IcmsPersistableColumn('author'));
  		$objectTable->addColumn(new IcmsPersistableColumn('title', _GLOBAL_LEFT, false, 'getAudioTitle'));
		$objectTable->addColumn(new IcmsPersistableColumn('url', 'center', 330, 'getAudioToDisplay', false, false, false));

		$objectTable->addQuickSearch(array('title', 'author'));
		$objectTable->setDefaultSort('audio_id');
		$objectTable->setDefaultOrder('DESC');

  		$icmsAdminTpl->assign('profile_audio_table', $objectTable->fetch());
  		$icmsAdminTpl->display('db:profile_admin_audio.html');
  		break;
  }
  icms_cp_footer();
}
/**
 * If you want to have a specific action taken because the user input was invalid,
 * place it at this point. Otherwise, a blank page will be displayed
 */
?>