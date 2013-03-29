<?php

class icms_AutologinEventHandler {

	static public function setup() {
		icms_Event::attach('icms_core_Session', 'sessionStart', array(__CLASS__, 'onSessionStart'));
		icms_Event::attach('icms_core_Session', 'sessionClose', array(__CLASS__, 'onSessionClose'));
	}
	static public function onSessionStart() {
		// Autologin if correct cookie present.
		if (empty($_SESSION['xoopsUserId']) && isset($_COOKIE['autologin_uname']) && isset($_COOKIE['autologin_pass'])) {
			self::sessionAutologin($_COOKIE['autologin_uname'], $_COOKIE['autologin_pass'], $_POST);
		}
	}
	static public function onSessionClose() {
		// autologin hack GIJ (clear autologin cookies)
		$icms_cookie_path = defined('ICMS_COOKIE_PATH') ? ICMS_COOKIE_PATH
				: preg_replace('?http://[^/]+(/.*)$?', '$1', ICMS_URL);
		if ($icms_cookie_path == ICMS_URL) {
			$icms_cookie_path = '/';
		}
		setcookie('autologin_uname', '', time() - 3600, $icms_cookie_path, '', 0, 0);
		setcookie('autologin_pass', '', time() - 3600, $icms_cookie_path, '', 0, 0);
	}

	static public function sessionAutologin($autologinName, $autologinPass, $post) {
		// autologin V2 GIJ
		if (!empty($post)) {
			$_SESSION['AUTOLOGIN_POST'] = $post;
			$_SESSION['AUTOLOGIN_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
			redirect_header(ICMS_URL . '/session_confirm.php', 0, '&nbsp;');
		} elseif (!empty($_SERVER['QUERY_STRING']) && substr($_SERVER['SCRIPT_NAME'], -19) != 'session_confirm.php') {
			$_SESSION['AUTOLOGIN_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
			redirect_header(ICMS_URL . '/session_confirm.php', 0, '&nbsp;');
		}
		// end of autologin V2

		// redirect to ICMS_URL/ when query string exists (anti-CSRF) V1 code
		/* if (! empty($_SERVER['QUERY_STRING'])) {
		redirect_header(ICMS_URL . '/' , 0 , 'Now, logging in automatically') ;
		exit ;
		}*/

		$myts = icms_core_Textsanitizer::getInstance();
		$uname = $myts->stripSlashesGPC($autologinName);
		$pass = $myts->stripSlashesGPC($autologinPass);
		if (empty($uname) || is_numeric($pass)) {
			$user = false ;
		} else {
			// V3
			$uname4sql = addslashes($uname);
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('uname', $uname4sql));
			$user_handler = icms::handler('icms_member_user');
			$users = $user_handler->getObjects($criteria, false);
			if (empty($users) || count($users) != 1) {
				$user = false ;
			} else {
				// V3.1 begin
				$user = $users[0] ;
				$old_limit = time() - (defined('ICMS_AUTOLOGIN_LIFETIME') ? ICMS_AUTOLOGIN_LIFETIME : 604800);
				list($old_Ynj, $old_encpass) = explode(':', $pass);
				if (strtotime($old_Ynj) < $old_limit || md5($user->getVar('pass') .
						ICMS_DB_PASS . ICMS_DB_PREFIX . $old_Ynj) != $old_encpass)
				{
					$user = false;
				}
				// V3.1 end
			}
			unset($users);
		}
		$icms_cookie_path = defined('ICMS_COOKIE_PATH') ? ICMS_COOKIE_PATH
			: preg_replace('?http://[^/]+(/.*)$?', "$1", ICMS_URL);
		if ($icms_cookie_path == ICMS_URL) {
			$icms_cookie_path = '/';
		}
		if (false != $user && $user->getVar('level') > 0) {
			// update time of last login
			$user->setVar('last_login', time());
			if (!icms::handler('icms_member')->insertUser($user, true)) {
			}
			//$_SESSION = array();
			$_SESSION['xoopsUserId'] = $user->getVar('uid');
			$_SESSION['xoopsUserGroups'] = $user->getGroups();

			global $icmsConfig;
			$user_theme = $user->getVar('theme');
			$user_language = $user->getVar('language');
			if (in_array($user_theme, $icmsConfig['theme_set_allowed'])) {
				$_SESSION['xoopsUserTheme'] = $user_theme;
			}
			$_SESSION['UserLanguage'] = $user_language;

			// update autologin cookies
			// we need to secure cookie when using SSL
			$secure = substr(ICMS_URL, 0, 5) == 'https' ? 1 : 0;
			// 1 week default
			$expire = time()
					+ (defined('ICMS_AUTOLOGIN_LIFETIME') ? ICMS_AUTOLOGIN_LIFETIME : 604800);
			setcookie('autologin_uname', $uname, $expire, $icms_cookie_path, '', $secure, 1);
			// V3.1
			$Ynj = date('Y-n-j');
			setcookie(
				'autologin_pass', $Ynj . ':' . md5($user->getVar('pass') . ICMS_DB_PASS . ICMS_DB_PREFIX . $Ynj),
				$expire, $icms_cookie_path, '', $secure, 1
			);
		} else {
			setcookie('autologin_uname', '', time() - 3600, $icms_cookie_path, '', 0, 0);
			setcookie('autologin_pass', '', time() - 3600, $icms_cookie_path, '', 0, 0);
		}
	}

}

icms_AutologinEventHandler::setup();

