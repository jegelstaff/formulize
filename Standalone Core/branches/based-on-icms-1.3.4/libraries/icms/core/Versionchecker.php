<?php
/**
 * Class used to determine if the core, or modules, need to be updated
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * IcmsVersionChecker
 *
 * Class used to check if the ImpressCMS install is up to date
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Core
 * @subpackage	VersionChecker
 * @since		1.0
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: Versionchecker.php 11603 2012-02-26 08:45:50Z fiammy $
 */
class icms_core_Versionchecker {

	/*
	 * errors
	 * @public $errors array
	 */
	public $errors = array();

	/*
	 * URL of the XML containing version information
	 * @public $version_xml string
	 */
	public $version_xml = "http://www.impresscms.org/impresscms_version_branch13.xml";

	/*
	 * Time before fetching the $version_xml again and store it in $cache_version_xml
	 * @public $cache_time integer
	 * @todo set this to a day at least or make it configurable in System Admin > Preferences
	 */
	public $cache_time=1;

	/*
	 * Name of the latest version
	 * @public $latest_version_name string
	 */
	public $latest_version_name;

	/*
	 * Name of installed version
	 * @private $installed_version_name string
	 */
	public $installed_version_name;

	/*
	 * Number of the latest build
	 * @public $latest_build integer
	 */
	public $latest_build;

	/*
	 * Status of the latest build
	 *
	 * 1  = Alpha
	 * 2  = Beta
	 * 3  = RC
	 * 10 = Final
	 *
	 * @public $latest_status integer
	 */
	public $latest_status;

	/*
	 * URL of the latest release
	 * @public $latest_url string
	 */
	public $latest_url;

	/*
	 * Changelog of the latest release
	 * @public $latest_changelog string
	 */
	public $latest_changelog;

	/**
	 * Constructor
	 *
	 * @return	void
	 *
	 */
	public function __construct() {
		$this->installed_version_name = ICMS_VERSION_NAME;
	}

	/**
	 * Access the only instance of this class
	 *
	 * @static
	 * @staticvar object
	 *
	 * @return	object
	 *
	 */
	static public function &getInstance() {
		static $instance;
		if (!isset($instance)) {
			$instance = new self();
		}
		return $instance;
	}

	/**
	 * Check for a newer version of ImpressCMS
	 *
	 * @return	TRUE if there is an update, FALSE if no update OR errors occured
	 *
	 */
	public function check() {

		// Create a new instance of the SimplePie object
		$feed = new icms_feeds_Simplerss();
		$feed->set_feed_url($this->version_xml);
		$feed->set_cache_duration(0);
		$feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
		$feed->init();
		$feed->handle_content_type();

		if (!$feed->error) {
			$versionInfo['title'] = $feed->get_title();
			$versionInfo['link'] = $feed->get_link();
			$versionInfo['image_url'] = $feed->get_image_url();
			$versionInfo['image_title'] = $feed->get_image_title();
			$versionInfo['image_link'] = $feed->get_image_link();
			$feed_item = $feed->get_item(0);
			$versionInfo['description'] = $feed_item->get_description();
			$versionInfo['permalink'] = $feed_item->get_permalink();
			$versionInfo['title'] = $feed_item->get_title();
			$versionInfo['content'] = $feed_item->get_content();
			$guidArray = $feed_item->get_item_tags('', 'guid');
			$versionInfo['guid'] = $guidArray[0]['data'];
		} else {
			$this->errors[] = _AM_VERSION_CHECK_RSSDATA_EMPTY;
			return false;
		}
		$this->latest_version_name = $versionInfo['title'];
		$this->latest_changelog = $versionInfo['description'];
		$build_info = explode('|', $versionInfo['guid']);
		$this->latest_build = $build_info[0];
		$this->latest_status = $build_info[1];

		if ($this->latest_build > ICMS_VERSION_BUILD) {
			// There is an update available
			$this->latest_url = $versionInfo['link'];
			return true;
		}
		return false;
	}

	/**
	 * Gets all the error messages
	 *
	 * @param	$ashtml	bool	return as html?
	 * @return	mixed
	 */
	public function getErrors($ashtml=true) {
		if (!$ashtml) {
			return $this->errors;
		} else {
			$ret = '';
			if (count($this->errors) > 0) {
				foreach ($this->errors as $error) {
					$ret .= $error.'<br />';
				}
			}
			return $ret;
		}
	}
}
