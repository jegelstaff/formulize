<?php
/**
 * ImpressCMS Bootstrap event handler
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		libraries
 * @since		1.3
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id:$
 */

class icms_BootstrapEventHandler {
	static public function setup() {
		icms_Event::attach('icms', 'loadService', array(__CLASS__, 'loadService'));
		icms_Event::attach('icms', 'finishcoreboot', array(__CLASS__, 'finishCoreBoot'));
		icms_Event::attach('icms', '*', array(__CLASS__, 'backwardCompatibility'));
	}

	/**
	 * Called after the kernel initializes a service in icms::loadService
	 * @return	void
	 */
	static public function loadService($params, $event) {
		switch ($params['name']) {
			case "logger":
				$params['service']->startTime('ICMS');
				$params['service']->startTime('ICMS Boot');
				break;
		}
	}

	static public function finishCoreBoot() {
		icms::$logger->stopTime('ICMS Boot');
		icms::$logger->startTime('Module init');
	}

	/**
	 * Create variables necessary for XOOPS / ICMS < 1.4 BC
	 * @param array $params
	 * @param icms_Event $event
	 */
	static public function backwardCompatibility($params, $event) {
		if ($event->name == 'startcoreboot') {
			$GLOBALS['xoops'] = $GLOBALS['impresscms'] = new icms_core_Kernel();
			$GLOBALS['icmsPreloadHandler'] = icms::$preload;
			// @todo-icms Check if all this is still needed (looks like PHP4 and IIS5 stuff)
			if (!isset($_SERVER['PATH_TRANSLATED']) && isset($_SERVER['SCRIPT_FILENAME'])) {
				$_SERVER['PATH_TRANSLATED'] =& $_SERVER['SCRIPT_FILENAME'];	 // For Apache CGI
			} elseif (isset($_SERVER['PATH_TRANSLATED']) && !isset($_SERVER['SCRIPT_FILENAME'])) {
				$_SERVER['SCRIPT_FILENAME'] =& $_SERVER['PATH_TRANSLATED'];	 // For IIS/2K now I think :-(
			}
			if (empty($_SERVER['REQUEST_URI'])) {
				// Not defined by IIS
				// Under some configs, IIS makes SCRIPT_NAME point to php.exe :-(
				if (!($_SERVER['REQUEST_URI'] = @$_SERVER['PHP_SELF'])) {
					$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
				}
				if (isset($_SERVER['QUERY_STRING'])) {
					$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
				}
			}
			$GLOBALS['xoopsRequestUri'] = $_SERVER['REQUEST_URI'];
		} elseif ($event->name == 'loadService') {
			switch ($params['name']) {
				case "security":
					$GLOBALS['xoopsSecurity'] = $GLOBALS['icmsSecurity'] = $params['service'];
					break;
				case "logger":
					$GLOBALS['xoopsLogger'] = $GLOBALS['xoopsErrorHandler'] = $params['service'];
					break;
				case "xoopsDB":
					$GLOBALS['xoopsDB'] = $params['service'];
					break;
				case "config":
					$GLOBALS['config_handler'] = $params['service'];
					$GLOBALS['icmsConfig']['xoops_url'] = ICMS_URL;
					$GLOBALS['icmsConfig']['root_path'] = ICMS_ROOT_PATH . "/";
					break;
				case "session":
					$GLOBALS['member_handler'] = icms::handler('icms_member');
					$GLOBALS['sess_handler'] = $params['service'];
					$GLOBALS['xoopsUser'] = $GLOBALS['icmsUser'] = icms::$user;
					if (icms::$user && icms::$user->isAdmin()) {
						$GLOBALS['xoopsUserIsAdmin'] = $GLOBALS['icmsUserIsAdmin'] = TRUE;
					} else {
						$GLOBALS['xoopsUserIsAdmin'] = $GLOBALS['icmsUserIsAdmin'] = FALSE;
					}
					$_SESSION['xoopsUserGroups'] = icms::$user ? icms::$user->getGroups() : array(ICMS_GROUP_ANONYMOUS);
					break;
				case "module":
					$GLOBALS['icmsModule'] = $GLOBALS['xoopsModule'] = icms::$module;
					if (icms::$user) {
						$GLOBALS['xoopsUserIsAdmin'] = $GLOBALS['icmsUserIsAdmin'] =
							icms::$user->isAdmin(icms::$module ? icms::$module->getVar('mid') : 1);
					}
					if (icms::$module) {
						$GLOBALS['icmsModuleConfig'] = $GLOBALS['xoopsModuleConfig'] = icms::$module->config;
					}
					break;
			}
		}

	}
}

icms_BootstrapEventHandler::setup();