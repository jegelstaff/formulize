<?php
/**
 * Generating an RSS feed
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Rodrigo P Lima aka TheRplima <therplima@impresscms.org>
 * @package		content
 * @version		$Id: rss.php 20430 2010-11-21 12:42:09Z phoenyx $
 */

include_once "header.php";
include_once ICMS_ROOT_PATH . "/header.php";

/** To come soon in imBuilding...

$clean_post_uid = isset($_GET['uid']) ? (int)$_GET['uid'] : false;

$content_feed = new icms_feed_Rss();

$content_feed->title = $icmsConfig['sitename'] . ' - ' . icms::$module->name();
$content_feed->url = ICMS_URL;
$content_feed->description = $icmsConfig['slogan'];
$content_feed->language = _LANGCODE;
$content_feed->charset = _CHARSET;
$content_feed->category = icms::$module->name();

$content_post_handler = icms_getModuleHandler('post');
//ContentPostHandler::getPosts($start = 0, $limit = 0, $post_uid = false, $year = false, $month = false
$postsArray = $content_post_handler->getPosts(0, 10, $clean_post_uid);

foreach($postsArray as $postArray) {
$content_feed->feeds[] = array (
'title' => $postArray['post_title'],
'link' => str_replace('&', '&amp;', $postArray['itemUrl']),
'description' => htmlspecialchars(str_replace('&', '&amp;', $postArray['post_lead']), ENT_QUOTES),
'pubdate' => $postArray['post_published_date_int'],
'guid' => str_replace('&', '&amp;', $postArray['itemUrl']),
);
}

$content_feed->render();
*/