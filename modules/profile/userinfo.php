<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		modules
 * @since		1.2
 * @author		Jan Pedersen
 * @author		The SmartFactory <www.smartfactory.ca>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: userinfo.php 21139 2011-03-20 20:58:11Z m0nty_ $
 */

include '../../mainfile.php';

$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : '';

if ($uid <= 0) {
	if (is_object(icms::$user)) {
		$uid = icms::$user->getVar('uid');
	} else {
		header('location: '.ICMS_URL);
		exit();
	}
}

if (icms::$module->config['profile_social'] == 1) {
	header('location: '.ICMS_URL.'/modules/'.basename(dirname(__FILE__)).'/index.php?uid='.$uid);
	exit();
}

$groups = is_object(icms::$user) ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);
if (!$icmsConfigUser['allow_annon_view_prof'] && !is_object(icms::$user)) redirect_header(ICMS_URL.'/user.php', 3, _NOPERM);

if (is_object(icms::$user) && $uid == icms::$user->getVar('uid')) {
    //disable cache
    $icmsConfig['module_cache'][icms::$module->getVar('mid')] = 0;
    $xoopsOption['template_main'] = 'profile_userinfo.html';
    include ICMS_ROOT_PATH.'/header.php';

    $thisUser = icms::$user;
} else {
    $thisUser = icms::handler('icms_member')->getUser($uid);
    if (!is_object($thisUser) || (!$thisUser->isActive() && (!icms::$user || !icms::$user->isAdmin()))) redirect_header(ICMS_URL."/modules/".basename(dirname(__FILE__)), 3, _MD_PROFILE_SELECTNG);

	//disable cache
    if (icms::$user->isAdmin(icms::$module->getVar('mid'))) $icmsConfig['module_cache'][icms::$module->getVar('mid')] = 0;
    $xoopsOption['template_main'] = 'profile_userinfo.html';
    include ICMS_ROOT_PATH.'/header.php';
	if (!$thisUser->isActive() && icms::$user && icms::$user->isAdmin()) $icmsTpl->assign('deleted', _MD_PROFILE_DELETED);
}

// adding profile stylesheet
$xoTheme->addStylesheet(ICMS_URL.'/modules/'.basename(dirname(__FILE__)).'/assets/css/profile'.(@_ADM_USE_RTL == 1 ? '_rtl':'').'.css');

// Dynamic User Profiles
$field_handler = icms_getModuleHandler('field', basename(dirname(__FILE__)), 'profile');
$categories = $field_handler->getProfileFields($thisUser);
$icmsTpl->assign('categories', $categories);
$icmsTpl->assign('break', count($categories) / 2 + 1);
unset($categories, $field_handler);

if (icms::$module->config['profile_search']) {
    $criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('hassearch', 1));
    $criteria->add(new icms_db_criteria_Item('isactive', 1));
    $modules = icms::handler('icms_module')->getObjects($criteria, true);
    $mids = array_keys($modules);

    $allowed_mids = icms::handler('icms_member_groupperm')->getItemIds('module_read', $groups);
    if (count($mids) > 0 && count($allowed_mids) > 0) {
        foreach ($mids as $mid) {
            if ( in_array($mid, $allowed_mids)) {
                $results = $modules[$mid]->search('', '', 5, 0, $thisUser->getVar('uid'));
                $count = count($results);
                if (is_array($results) && $count > 0) {
                    for ($i = 0; $i < $count; $i++) {
                        if (isset($results[$i]['image']) && $results[$i]['image'] != '') {
                            $results[$i]['image'] = ICMS_URL.'/modules/'.$modules[$mid]->getVar('dirname').'/'.$results[$i]['image'];
                        } else {
                            $results[$i]['image'] = ICMS_URL.'/images/icons/posticon2.gif';
                        }
                        if (!preg_match("/^http[s]*:\/\//i", $results[$i]['link'])) {
                            $results[$i]['link'] = ICMS_URL."/modules/".$modules[$mid]->getVar('dirname')."/".$results[$i]['link'];
                        }
                        $results[$i]['title'] = icms_core_DataFilter::htmlSpecialChars($results[$i]['title']);
                        $results[$i]['time'] = $results[$i]['time'] ? formatTimestamp($results[$i]['time']) : '';
                    }
                    if ($count == 5) {
                        $showall_link = '<a href="'.ICMS_URL.'/search.php?action=showallbyuser&amp;mid='.$mid.'&amp;uid='.$thisUser->getVar('uid').'">'._MD_PROFILE_SHOWALL.'</a>';
                    } else {
                        $showall_link = '';
                    }
                    $icmsTpl->append('modules', array('name' => $modules[$mid]->getVar('name'), 'results' => $results, 'showall_link' => $showall_link));
                }
                unset($modules[$mid]);
            }
        }
    }
}

if (icms::$module->config['index_real_name'] == 'real' && trim($thisUser->getVar('name'))) {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('name')) : _GUESTS;
} elseif (icms::$module->config['index_real_name'] == 'both' && trim($thisUser->getVar('name'))) {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('name')).' ('.trim($thisUser->getVar('uname')).')' : _GUESTS;
} else {
	$owner_name = is_object($thisUser) ? trim($thisUser->getVar('uname')) : _GUESTS;
}
$icmsTpl->assign('profile_module_home', sprintf(_MD_PROFILE_PAGETITLE, $owner_name));

include 'footer.php';