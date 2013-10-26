<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		LICENSE.txt
 * @license		GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package		modules
 * @since		1.4
 * @version		$Id: search.inc.php 20562 2010-12-19 18:26:36Z phoenyx $
 */

/** Protection against inclusion outside the site */
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

/**
 * Return search results and show images on userinfo page
 *
 * @param array $queryarray the terms to look
 * @param text $andor the conector between the terms to be looked
 * @param int $limit The number of maximum results
 * @param int $offset from wich register start
 * @param int $userid from which user to look
 * @return array $ret with all results
 */
function profile_search($queryarray, $andor, $limit, $offset, $userid) {
	global $icmsConfigUser;

	$ret = array();
	$i = 0;
	$dirname = basename(dirname(dirname(__FILE__)));

	// check if anonymous users can access profiles
	if (!is_object(icms::$user) && !$icmsConfigUser['allow_annon_view_prof']) return $ret;

	// check if tribes are activated in module configuration
	$module = icms::handler('icms_module')->getByDirname($dirname, TRUE);
	if (!$module->config['enable_tribes']) return $ret;

	$profile_tribes_handler = icms_getModuleHandler('tribes', basename(dirname(dirname(__FILE__))), 'profile');
	
	$criteria = new icms_db_criteria_Compo();
	// if those two lines are uncommented, "all search results" isn't showing in the search results
	//if ($offset) $criteria->setStart($offset);
	//if ($limit) $criteria->setLimit((int)$limit);
	$criteria->setSort('title');
	if ($userid) $criteria->add(new icms_db_criteria_Item('uid_owner', $userid));
	if (is_array($queryarray) && count($queryarray) > 0)
		foreach ($queryarray as $query) {
			$criteria_query = new icms_db_criteria_Compo();
			$criteria_query->add(new icms_db_criteria_Item('title', '%'.$query.'%', 'LIKE'));
			$criteria_query->add(new icms_db_criteria_Item('tribe_desc', '%'.$query.'%', 'LIKE'), 'OR');
			$criteria->add($criteria_query, $andor);
			unset($criteria_query);
		}

	$tribes = $profile_tribes_handler->getObjects($criteria, false, false);
	foreach ($tribes as $tribe) {
		$ret[$i++] = array(
			"image" => 'images/tribes.gif',
			"link"  => $tribe['itemUrl'],
			"title" => $tribe['title'],
			"time"  => strtotime($tribe['creation_time']),
			"uid"   => $tribe['uid_owner']
		);
	}

	return $ret;
}
?>