<?php
/**
 * Class handling RSS feeds, using SimplePie class
 *
 * SimplePie is a very fast and easy-to-use class, written in PHP, that puts the ?simple? back into ?really simple syndication?.
 * Flexible enough to suit beginners and veterans alike, SimplePie is focused on speed, ease of use, compatibility and
 * standards compliance.
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Feeds
 * @subpackage	RSS
 * @since		1.2
 * @author		malanciault <marcan@impresscms.org)
 * @version		$Id: Simplerss.php 20370 2010-11-15 20:54:33Z skenow $
 */

include_once ICMS_LIBRARIES_PATH . '/simplepie/simplepie.inc' ;
include_once ICMS_LIBRARIES_PATH . '/simplepie/idn/idna_convert.class.php' ;

class icms_feeds_Simplerss extends SimplePie {

	/**
	 * The IcmsSimpleRss class contains feed level data and options
	 *
	 * There are two ways that you can create a new IcmsSimpleRss object. The first
	 * is by passing a feed URL as a parameter to the IcmsSimpleRss constructor
	 * (as well as optionally setting the cache expiry - The cache location is automatically set
	 * as ICMS_CACHE_PATH). This will initialise the whole feed with all of the default settings, and you
	 * can begin accessing methods and properties immediately.
	 *
	 * The second way is to create the IcmsSimpleRss object with no parameters
	 * at all. This will enable you to set configuration options. After setting
	 * them, you must initialise the feed using $feed->init(). At that point the
	 * object's methods and properties will be available to you.
	 *
	 * @access public
	 * @param string $feed_url This is the URL you want to parse.
	 * @param int $cache_duration This is the number of seconds that you want to store the cache file for.
	 */
	public function __construct($feed_url = null, $cache_duration = null) {
		// Other objects, instances created here so we can set options on them
		$this->sanitize = new SimplePie_Sanitize;

		$this->set_cache_location(ICMS_CACHE_PATH);

		if ($cache_duration !== null) {
			$this->set_cache_duration($cache_duration);
		}

		// Only init the script if we're passed a feed URL
		if ($feed_url !== null) {
			$this->set_feed_url($feed_url);
			$this->init();
		}
	}
}

