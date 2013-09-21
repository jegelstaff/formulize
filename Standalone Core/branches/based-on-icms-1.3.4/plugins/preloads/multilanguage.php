<?php
/**
 * Defines the Multilanguage tag handler and attaches it to the event handler during preload
 * 
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		:ibraries
 * @since		1.3
 * @version		$Id: multilanguage.php 11194 2011-04-20 23:49:33Z skenow $
 */

/**
 * Handles selection and display of multilanguage texts
 * 
 * @category	ICMS
 * @package		Libraries
 */
class icms_MultilanguageEventHandler {

	/**
	 * Attaches the multilanguage handler to the event handler
	 * 
	 */
	static public function setup() {
		icms_Event::attach('icms', 'loadService-session', array(__CLASS__, 'initMultilang'));
	}

	/**
	 * Initializes the multilanguage process
	 * 
	 */
	static public function initMultilang() {
		global $icmsConfigMultilang, $icmsConfig;
		if ($icmsConfigMultilang['ml_enable']) {
			require_once ICMS_INCLUDE_PATH . '/im_multilanguage.php' ;
			$easiestml_langs = explode(',', $icmsConfigMultilang['ml_tags']);

			$easiestml_langpaths = icms_core_Filesystem::getDirList(ICMS_ROOT_PATH . "/language/");
			$langs = array_combine($easiestml_langs, explode(',', $icmsConfigMultilang['ml_names']));

			if ($icmsConfigMultilang['ml_autoselect_enabled']
				&& isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
				&& $_SERVER['HTTP_ACCEPT_LANGUAGE'] != ""
			) {
				$autolang = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);
				if (in_array($autolang, $easiestml_langs)) {
					$icmsConfig['language'] = $langs[$autolang];
				}
			}

			if (isset($_GET['lang']) && isset($_COOKIE['lang'])) {
				if (in_array($_GET['lang'], $easiestml_langs)) {
					$icmsConfig['language'] = $langs[$_GET['lang']];
					if (isset($_SESSION['UserLanguage'])) {
						$_SESSION['UserLanguage'] = $langs[$_GET['lang']];
					}
				}
			} elseif (isset($_COOKIE['lang']) && isset($_SESSION['UserLanguage'])) {
				if ($_COOKIE['lang'] != $_SESSION['UserLanguage']) {
					if (in_array($_SESSION['UserLanguage'], $langs)) {
						$icmsConfig['language'] = $_SESSION['UserLanguage'];
					}
				} else {
					if (in_array($_COOKIE['lang'], $easiestml_langs)) {
						$icmsConfig['language'] = $langs[$_COOKIE['lang']];
					}
				}
			} elseif (isset($_COOKIE['lang'])) {
				if (in_array($_COOKIE['lang'], $easiestml_langs)) {
					$icmsConfig['language'] = $langs[$_COOKIE['lang']];
					if (isset( $_SESSION['UserLanguage'] )) {
						$_SESSION['UserLanguage'] = $langs[$_GET['lang']];
					}
				}
			} elseif (isset($_GET['lang'])) {
				if (in_array($_GET['lang'], $easiestml_langs)) {
					$icmsConfig['language'] = $langs[$_GET['lang']];
				}
			}
		}
	}
}

icms_MultilanguageEventHandler::setup();

