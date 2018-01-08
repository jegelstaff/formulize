<?php
/**
 * Helper functions available in the ImpressCMS process
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package	core
 * @since		XOOPS
 * @author		http://www.xoops.org The XOOPS Project
 * @author		modified by marcan <marcan@impresscms.org>
 * @version	$Id: functions.php 8806 2009-05-31 22:28:54Z pesianstranger $
 */
/**
 * The header
 *
 * Implements all functions that are executed within the header of the page
 * (meta tags, header expiration, etc)
 * It will all be echoed, so no return in this function
 *
 * @param bool  $closehead  close the <head> tag
 */
function xoops_header($closehead=true) {
	global $icmsConfig, $xoopsTheme, $icmsConfigPlugins, $icmsConfigMetaFooter;
	$myts =& icms_core_Textsanitizer::getInstance();

	if(!headers_sent())
	{
		header('Content-Type:text/html; charset='._CHARSET);
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header('Cache-Control: no-store, no-cache, max-age=1, s-maxage=1, must-revalidate, post-check=0, pre-check=0');
		header("Pragma: no-cache");
	}
	echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>";
	echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'._LANGCODE.'" lang="'._LANGCODE.'">
	<head>
	<meta http-equiv="content-type" content="text/html; charset='._CHARSET.'" />
	<meta http-equiv="content-language" content="'._LANGCODE.'" />
	'.htmlspecialchars($icmsConfigMetaFooter['google_meta']).'
	<meta name="robots" content="'.htmlspecialchars($icmsConfigMetaFooter['meta_robots']).'" />
	<meta name="keywords" content="'.htmlspecialchars($icmsConfigMetaFooter['meta_keywords']).'" />
	<meta name="description" content="'.htmlspecialchars($icmsConfigMetaFooter['meta_description']).'" />
	<meta name="rating" content="'.htmlspecialchars($icmsConfigMetaFooter['meta_rating']).'" />
	<meta name="author" content="'.htmlspecialchars($icmsConfigMetaFooter['meta_author']).'" />
	<meta name="copyright" content="'.htmlspecialchars($icmsConfigMetaFooter['meta_copyright']).'" />
	<meta name="generator" content="ImpressCMS" />
	<title>'.htmlspecialchars($icmsConfig['sitename']).'</title>
	<script type="text/javascript" src="'.ICMS_URL.'/include/xoops.js"></script>
	<script type="text/javascript" src="'.ICMS_URL.'/include/linkexternal.js"></script>
	<link rel="stylesheet" type="text/css" media="all" href="' . ICMS_URL . '/icms'.(( defined('_ADM_USE_RTL') && _ADM_USE_RTL )?'_rtl':'').'.css" />';

	$style_info = '';
	if(!empty($icmsConfigPlugins['sanitizer_plugins'])){
		foreach ($icmsConfigPlugins['sanitizer_plugins'] as $key) {
			if( empty( $key ) )
			continue;
			if(file_exists(ICMS_ROOT_PATH.'/plugins/textsanitizer/'.$key.'/'.$key.'.css')){
				echo '<link rel="stylesheet" media="screen" href="'.ICMS_URL.'/plugins/textsanitizer/'.$key.'/'.$key.'.css" type="text/css" />';
			}else{
				$extension = include_once ICMS_ROOT_PATH.'/plugins/textsanitizer/'.$key.'/'.$key.'.php';
				$func = 'style_'.$key;
				if ( function_exists($func) ) {
					$style_info = $func();
					if (!empty($style_info)) {
						if(!file_exists(ICMS_ROOT_PATH.'/'.$style_info)){
							echo '<style media="screen" type="text/css">
							'.$style_info.'
							</style>';
						}else{
							echo '<link rel="stylesheet" media="screen" href="'.$style_info.'" type="text/css" />';
						}
					}
				}
			}
		}
	}

	$themecss = xoops_getcss($icmsConfig['theme_set']);
	if ($themecss) {
		echo '<link rel="stylesheet" type="text/css" media="all" href="'.$themecss.'" />';
	}
	if ($closehead) {
		echo '</head><body>';
	}
}

/**
 * The footer
 *
 * Implements all functions that are executed in the footer
 */
function xoops_footer() {
	global $icmsConfigMetaFooter;
	echo htmlspecialchars($icmsConfigMetaFooter['google_analytics']).'</body></html>';
	ob_end_flush();
}

/**
 * ImpressCMS Error Message Function
 *
 * @param string $msg	The Error Message
 * @param string $title	The Error message title
 * @param bool $render	Whether to echo (render) or return the HTML string
 * @return string $ret The entire error message in a HTML string
 * @deprecated	Use icms_coreMessage::error, instead
 * @todo 		Remove in version 1.4 -  - all occurrences have been removed from the core
 */
function icms_error_msg($msg, $title='', $render = true){
	icms_core_Debug::setDeprecated('icms_core_Message::error', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Message::error($msg, $title, $render);
}

/**
 * Backwards Compatibility Function
 *
 * @param string $msg
 * @param string $title
 * @deprecated	Use icms_coreMessage::error, instead
 * @todo		Remove in version 1.4 - all occurrences have been removed from the core
 */
function xoops_error($msg, $title=''){
	icms_core_Debug::setDeprecated('icms_coreMessage::error', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Message::error($msg, $title, true);
}

/**
 * ImpressCMS Warning Message Function
 *
 * @param string $msg	The Error Message
 * @param string $title	The Error Message title
 * @param	bool	$render	Whether to echo (render) or return the HTML string
 * @deprecated	Use icms_core_Message::warning, instead
 * @todo 		Remove in version 1.4 - all occurrences have been removed from the core
 */
function icms_warning_msg($msg, $title='', $render = false){
	icms_core_Debug::setDeprecated('icms_core_Message::warning', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Message::warning($msg, $title, $render);
}

/**
 * Backwards Compatibility Function
 * @deprecated use icms_core_Message::warning instead
 * @see icms_core_Message::warning
 * @todo Remove in version 1.4 - all occurrences have been removed from the core
 * @param string $msg
 * @param string $title
 */
function xoops_warning($msg, $title=''){
	icms_core_Debug::setDeprecated( 'icms_core_Message::warning' );
	return icms_core_Message::warning($msg, $title, true); }

/**
 * Render result message (echo, so no return string)
 * @param string $msg
 * @param string $title
 *
 * @deprecated	Use icms_core_Message:result, instead
 * @todo		Remove in version 1.4 - all occurrences have been removed from the core
 */
function xoops_result($msg, $title='')
{
	icms_core_Debug::setDeprecated('icms_core_Message::result', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Message::result($msg, $title);
}

/**
 * Generates a confirm form
 *
 * Will render (echo) the form so no return in this function
 *
 * @param array  $hiddens  Array of Hidden values
 * @param string  $action  The Form action
 * @param string  $msg  The message in the confirm form
 * @param string  $submit  The text on the submit button
 * @param bool  $addtoken  Whether or not to add a security token
 *
 * @deprecated	Use icms_core_Message::confirm, instead
 * @todo		Remove in version 1.4 - all occurrences have been removed from the core
 */
function xoops_confirm($hiddens, $action, $msg, $submit='', $addtoken = true)
{
	icms_core_Debug::setDeprecated('icms_core_Message::confirm', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Message::confirm($hiddens, $action, $msg, $submit, $addtoken);
}

/**
 * @deprecated, use {@link icms_core_Security} class instead
 * @todo Remove this in 1.4 - all occurrences have been replaced in the core
 **/
function xoops_refcheck($docheck=1) {
	icms_core_Debug::setDeprecated('icms_core_Security->checkReferer', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms::$security->checkReferer($docheck);
}

/**
 * Get the timestamp based on the user settings
 *
 * @param string  $time  String with time
 * @param string  $timeoffset  The time offset string
 * @return string  $usertimestamp  The generated user timestamp
 * @todo Move to a static class method - user
 */
function xoops_getUserTimestamp($time, $timeoffset="")
{
	global $icmsConfig;
	if($timeoffset == '')
	{
		if(icms::$user) {$timeoffset = icms::$user->getVar('timezone_offset');}
		else {$timeoffset = $icmsConfig['default_TZ'];}
	}
	$usertimestamp = (int) ($time) + ((float)($timeoffset) - $icmsConfig['server_TZ'])*3600;
	return $usertimestamp;
}

/**
 * Function to calculate server timestamp from user entered time (timestamp)
 *
 * @param string  $timestamp  String with time
 * @return string  $timestamp  The generated timestamp
 * @todo Move to a static class method - date and time
 */
function userTimeToServerTime($timestamp, $userTZ=null)
{
	global $icmsConfig;
	if(!isset($userTZ)) {$userTZ = $icmsConfig['default_TZ'];}
	$timestamp = $timestamp - (($userTZ - $icmsConfig['server_TZ']) * 3600);
	return $timestamp;
}

/**
 * Function to generate password
 *
 * @return string  $makepass  The generated password
 * d@todo Move to a static class method - password/user
 * or why not just remove and use icms_core_Password::createSalt($length); instead?
 */
function xoops_makepass() {
	icms_core_Debug::setDeprecated('icms_core_Password::createSalt(10) creates random pass of 10 characters', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Password::createSalt(10);
}

/**
 * Function to display dhtml loading image box
 * @deprecated Not found in the core
 * @todo Remove in version 1.4
 */
function OpenWaitBox()
{
	icms_core_Debug::setDeprecated('','This is very old and will be removed in version 1.4');
	echo "<div id='waitDiv' style='position:absolute;left:40%;top:50%;visibility:hidden;text-align: center;'>
	<table cellpadding='6' border='2' class='bg2'>
		<tr>
		<td align='center'><b><big>" ._FETCHING."</big></b><br /><img src='".ICMS_URL."/images/await.gif' alt='' /><br />" ._PLEASEWAIT."</td>
		</tr>
	</table>
	</div>
	<script type='text/javascript'>
	<!--//
	var DHTML = (document.getElementById || document.all || document.layers);
	function ap_getObj(name) {
		if (document.getElementById) {
			return document.getElementById(name).style;
		} else if (document.all) {
			return document.all[name].style;
		} else if (document.layers) {
			return document.layers[name];
		}
	}
	function ap_showWaitMessage(div,flag)  {
		if (!DHTML) {
			return;
		}
		var x = ap_getObj(div);
		x.visibility = (flag) ? 'visible' : 'hidden';
		if (!document.getElementById) {
			if (document.layers) {
				x.left=280/2;
			}
		}
		return true;
	}
	ap_showWaitMessage('waitDiv', 1);
	//-->
	</script>";
}

/**
 * Function to display the finish of the dhtml wait box
 *
 * @deprecated Not used anywhere in the core
 * @todo Remove in version 1.4
 */
function CloseWaitBox()
{
	icms_core_Debug::setDeprecated('', 'This is very old and will be removed in version 1.4');
	echo "<script type='text/javascript'>
	<!--//
	ap_showWaitMessage('waitDiv', 0);
	//-->
	</script>
	";
}

/**
 * Checks if email is of correct formatting
 *
 * @param string     $email      The email address
 * @param string     $antispam   Generate an email address that is protected from spammers
 * @return string    $email      The generated email address
 * @todo Move to a static class method - text validation/formatting
 * new filter can use icms_core_DataFilter::checkVar($email, 'email', $antispam, $blacklist)
 */
function checkEmail($email, $antispam = 0, $blacklist = 0)
{
	icms_core_Debug::setDeprecated('icms_core_DataFilter->checkVar - type = email, $options1 = true/false ($antispam),
		$options2 = true/false ($blacklist)', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_DataFilter::checkVar($email, 'email', $antispam, $blacklist);
}

/**
 * Format an URL
 *
 * @param string  $url  The URL to format
 * @return string  $url The generated URL
 * @todo Move to a static class method - string formatting
 */
function formatURL($url)
{
	$url = trim($url);
	if($url != '')
	{
		if((!preg_match("/^http[s]*:\/\//i", $url)) && (!preg_match("/^ftp*:\/\//i", $url)) && (!preg_match("/^ed2k*:\/\//i", $url))) {$url = 'http://'.$url;}
	}
	return $url;
}

/**
 * Function to display banners in all pages
 * @deprecated Use xoops_getbanner instead
 * @todo Remove in version 1.4 - all occurrences have been removed from the core
 */
function showbanner() {
	icms_core_Debug::setDeprecated('xoops_getbanner',sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	echo xoops_getbanner();}

/**
 * Gets banner HTML for use in templates
 *
 * @return object  $bannerobject  The generated banner HTML string
 * @todo Move to a static class method - Banner
 */
function xoops_getbanner() {
	global $icmsConfig;

	$db = icms_db_Factory::instance();
	$bresult = $db->query("SELECT * FROM ".$db->prefix('banner')." ORDER BY RAND()", 1);
	if ($db->getRowsNum($bresult) > 0) {
		list($bid, $cid, $imptotal, $impmade, $clicks, $imageurl, $clickurl, $date, $htmlbanner, $htmlcode) = $db->fetchRow($bresult);
		if ($icmsConfig['my_ip'] != $_SERVER['REMOTE_ADDR'])
			$db->queryF(sprintf("UPDATE %s SET impmade = impmade+1 WHERE bid = '%u'", $db->prefix('banner'), (int)($bid)));
		/* Check if this impression is the last one and print the banner */
		if ($imptotal == $impmade && $imptotal != 0) {
			$newid = $db->genId($db->prefix('bannerfinish').'_bid_seq');
			$sql = sprintf("INSERT INTO %s (bid, cid, impressions, clicks, datestart, dateend) VALUES ('%u', '%u', '%u', '%u', '%u', '%u')", $db->prefix('bannerfinish'), (int) ($newid), (int) ($cid), (int) ($impmade), (int) ($clicks), (int) ($date), time());
			$db->queryF($sql);
			$db->queryF(sprintf("DELETE FROM %s WHERE bid = '%u'", $db->prefix('banner'), (int)($bid)));
		}
		if ($htmlbanner) {
			$bannerobject = $htmlcode;
		} else {
			$bannerobject = '<div><a href="'.ICMS_URL.'/banners.php?op=click&amp;bid='.$bid.'" rel="external">';
			if (stristr($imageurl, '.swf')) {
				$bannerobject = $bannerobject
				.'<object type="application/x-shockwave-flash" data="'.$imageurl.'" width="468" height="60">'
				.'<param name="movie" value="'.$imageurl.'"></param>'
				.'<param name="quality" value="high"></param>'
				.'</object>';
			} else {
				$bannerobject = $bannerobject.'<img src="'.$imageurl.'" alt="" />';
			}
			$bannerobject = $bannerobject.'</a></div>';
		}
		return $bannerobject;
	}
}

/**
 * Function to redirect a user to certain pages
 *
 * @param string  $url  The URL to redirect to
 * @param int  $time  The time it takes to redirect to the URL
 * @param string  $message  The message to show while redirecting
 * @param bool  $addredirect  Add a link to the redirect URL?
 * @param string  $allowExternalLink  Allow external links
 */
function redirect_header($url, $time = 3, $message = '', $addredirect = true, $allowExternalLink = false)
{
	global $icmsConfig, $icmsConfigPersona;
	if(preg_match("/[\\0-\\31]|about:|script:/i", $url))
	{
		if(preg_match('/^\b(java)?script:([\s]*)history\.go\(-[0-9]*\)([\s]*[;]*[\s]*)$/si', $url)) {$url = ICMS_URL;}
	}
	if(!$allowExternalLink && $pos = strpos($url, '://' ))
	{
		$xoopsLocation = substr(ICMS_URL, strpos(ICMS_URL, '://') + 3);
		if(substr($url, $pos + 3, strlen($xoopsLocation)) != $xoopsLocation) {$url = ICMS_URL;}
		elseif(substr($url, $pos + 3, strlen($xoopsLocation)+1) == $xoopsLocation.'.') {$url = ICMS_URL;}
	}
	$theme = $icmsConfig['theme_set'];
	// if the user selected a theme in the theme block, let's use this theme
	if(isset($_SESSION['xoopsUserTheme']) && in_array($_SESSION['xoopsUserTheme'], $icmsConfig['theme_set_allowed'])) {$theme = $_SESSION['xoopsUserTheme'];}

	$xoopsThemeFactory = new icms_view_theme_Factory();
	$xoopsThemeFactory->allowedThemes = $icmsConfig['theme_set_allowed'];
	$xoopsThemeFactory->defaultTheme = $theme;
	$icmsTheme = $xoTheme =& $xoopsThemeFactory->createInstance(array("plugins" => array()));
	$xoopsTpl = $icmsTpl =& $xoTheme->template;

	if($icmsConfig['debug_mode'] == 2 && icms::$user->isAdmin())
	{
		$xoopsTpl->assign('time', 300);
		$xoopsTpl->assign('xoops_logdump', icms::$logger->dump());
	}
	else {$xoopsTpl->assign('time', (int) ($time));}
	if(!empty($_SERVER['REQUEST_URI']) && $addredirect && strstr($url, 'user.php'))
	{
		if(!strstr($url, '?')) {$url .= '?xoops_redirect='.urlencode($_SERVER['REQUEST_URI']);}
		else {$url .= '&amp;xoops_redirect='.urlencode($_SERVER['REQUEST_URI']);}
	}
	if(defined('SID') && SID && (!isset($_COOKIE[session_name()]) || ($icmsConfig['use_mysession'] && $icmsConfig['session_name'] != '' && !isset($_COOKIE[$icmsConfig['session_name']]))))
	{
		if(!strstr($url, '?')) {$url .= '?' . SID;}
		else {$url .= '&amp;'.SID;}
	}
	$url = preg_replace("/&amp;/i", '&', htmlspecialchars($url, ENT_QUOTES));
	$xoopsTpl->assign('url', $url);
	$message = trim($message) != '' ? $message : _TAKINGBACK;
	$xoopsTpl->assign('message', $message);
	$xoopsTpl->assign('lang_ifnotreload', sprintf(_IFNOTRELOAD, $url));
	// GIJ start
	if( ! headers_sent() && $icmsConfigPersona['use_custom_redirection']==1) {
		$_SESSION['redirect_message'] = $message ;
		header( "Location: ".preg_replace("/[&]amp;/i",'&',$url) ) ;
		exit();
	}else{
		$xoopsTpl->display('db:system_redirect.html');
		if (defined('XOOPS_CPFUNC_LOADED')) {
			icms_cp_footer();
		} else {
			include ICMS_ROOT_PATH.'/footer.php';
		}
		exit();
	}
	// GIJ end
}

/**
 * Gets environment key from the $_SERVER or $_ENV superglobal
 *
 * @param string  $key  The key to get
 * @return string  $ret  The retrieved key
 */
function xoops_getenv($key)
{
	$ret = '';
	if(array_key_exists($key, $_SERVER) && isset($_SERVER[$key]))
	{
		$ret = $_SERVER[$key];
		return $ret;
	}
	if(array_key_exists($key, $_ENV) && isset($_ENV[$key]))
	{
		$ret = $_ENV[$key];
		return $ret;
	}
	return $ret;
}

/**
 * @deprecated get the theme from the global $icmsConfig instead
 * @todo Remove in version 1.4 - all occurrences have been removed from the core
 */
function getTheme() {
	icms_core_Debug::setDeprecated( 'get the theme from the global $icmsConfig' );
	return $GLOBALS['icmsConfig']['theme_set'];}

/**
 * Function to get css file for a certain theme
 * @deprecated use xoops_getcss() instead
 * @todo Remove in version 1.4 - all instances have been removed from the core
 */
function getcss($theme = '') {
	icms_core_Debug::setDeprecated( 'xoops_getcss' );
	return xoops_getcss($theme);}

/**
 * Function to get css file for a certain themeset
 *
 * @param string  $theme  The theme set from the config
 * @return mixed  The generated theme HTML string or an empty string
 */
function xoops_getcss($theme = '')
{
	if($theme == '') {$theme = $GLOBALS['icmsConfig']['theme_set'];}
	$uagent = xoops_getenv('HTTP_USER_AGENT');
	if(stristr($uagent, 'mac')) {$str_css = 'styleMAC.css';}
	elseif(preg_match("/MSIE ([0-9]\.[0-9]{1,2})/i", $uagent)) {$str_css = 'style.css';}
	else {$str_css = 'styleNN.css';}
	if(is_dir(ICMS_THEME_PATH.'/'.$theme))
	{
		if(file_exists(ICMS_THEME_PATH.'/'.$theme.'/'.$str_css)) {return ICMS_THEME_URL.'/'.$theme.'/'.$str_css;}
		elseif(file_exists(ICMS_THEME_PATH.'/'.$theme.'/style.css')) {return ICMS_THEME_URL.'/'.$theme.'/style.css';}
	}
	if(is_dir(ICMS_THEME_PATH.'/'.$theme.'/css'))
	{
		if(file_exists(ICMS_THEME_PATH.'/'.$theme.'/css/'.$str_css)) {return ICMS_THEME_URL.'/'.$theme.'/css/'.$str_css;}
		elseif(file_exists(ICMS_THEME_PATH.'/'.$theme.'/css/style.css')) {return ICMS_THEME_URL.'/'.$theme.'/css/style.css';}
	}
	echo("<h1>The '".$theme."' theme does not exist.</h1>");
	return '';
}

/**
 * Gets Mailer object
 *
 * @return		object  localized email object handler
 * @todo 		Remove in version 1.4
 * @deprecated	Use icms_messaging_Handler, instead
 */
function &getMailer() {
	icms_core_Debug::setDeprecated('icms_messaging_Handler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return new icms_messaging_Handler();
}

/**
 * Gets the handler for a class
 *
 * @param string  $name  The name of the handler to get
 * @param bool  $optional	Is the handler optional?
 * @return		object		$inst		The instance of the object that was created
 * @todo This will not be needed when the autoload is complete
 */
function &xoops_gethandler($name, $optional = false) {
	// Lookup table: old xoops names => fully qualified refactored name
	$lookup = array(
		//"avatar"			=> "",
		//"block"				=> "icms_block",
		//"blockposition"		=> "icms_block_position",
		//"comment"			=> "",
		"config"			=> "icms_config",
		//"configcategory"	=> "",
		//"configitem"		=> "",
		//"configoption"		=> "",
		//"group"				=> "",
		//"groupperm"			=> "",
		//"image"				=> "",
		//"imagecategory"		=> "",
		//"imageset"			=> "",
		//"member"			=> "",
		//"module"			=> "",
		//"notification"		=> "",
		//"object"			=> "",
		//"online"			=> "",
		//"page"				=> "",
		//"privmessage"		=> "",
		//"session"			=> "",
		//"tplfile"			=> "",
		//"tplset"			=> "",
		//"icmspersistablecategory"	=> "",
	);
	$lower = strtolower($name);
	return icms::handler(isset($lookup[$lower]) ? $lookup[$lower] : $name);
}

/**
 * Gets rank
 *
 * @param	int  $rank_id  The Rank ID to get
 * @param 	int	 $posts		The number of posts to match for the rank
 * @return	array	$rank		The fetched rank array
 * @deprecated Use SystemUserrankHandler->getRank
 * @todo Remove in version 1.4 - all instances have been removed from the core
 */
function xoops_getrank($rank_id =0, $posts = 0) {
	icms_core_Debug::setDeprecated('SystemUserrankHandler->getRank', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_getModuleHandler("userrank", "system")->getRank($rank_id, $posts);
}

/**
 * Function maintained only for compatibility
 * @deprecated Use icms_core_DataFilter::icms_substr
 * @todo Remove in version 1.4 - all instances have been removed from the core
 *
 */
function xoops_substr($str, $start, $length, $trimmarker = '...') {
	icms_core_Debug::setDeprecated('icms_core_DataFilter::icms_substr', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_DataFilter::icms_substr($str, $start, $length, $trimmarker);
}

/**
 * Returns the portion of string specified by the start and length parameters.
 * If $trimmarker is supplied, it is appended to the return string.
 * This function works fine with multi-byte characters if mb_* functions exist on the server.
 *
 * @param	string	$str
 * @param	int	   $start
 * @param	int	   $length
 * @param	string	$trimmarker
 *
 * @return   string
 * @todo Move to a static class method - String
 */
function icms_substr($str, $start, $length, $trimmarker = '...')
{
	icms_core_Debug::setDeprecated('icms_core_DataFilter::icms_substr', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_DataFilter::icms_substr($str, $start, $length, $trimmarker);
}

// RMV-NOTIFY
// ################ Notification Helper Functions ##################
/**
 * We want to be able to delete by module, by user, or by item.
 * How do we specify this??
 *
 * @param	int  $module_id	The ID of the module to unsubscribe from
 * @return	bool	Did the unsubscribing succeed?
 * @todo Move to a static class method - Notification
 */
function xoops_notification_deletebymodule ($module_id)
{
	$notification_handler = icms::handler('icms_data_notification');
	return $notification_handler->unsubscribeByModule ($module_id);
}

/**
 * Deletes / unsubscribes by user ID
 *
 * @param	int  $user_id	The User ID to unsubscribe
 * @return	bool	Did the unsubscribing succeed?
 * @todo Move to a static class method - Notification
 */
function xoops_notification_deletebyuser ($user_id)
{
	$notification_handler =& icms::handler('icms_data_notification');
	return $notification_handler->unsubscribeByUser ($user_id);
}

/**
 * Deletes / unsubscribes by Item ID
 *
 * @param	int  $module_id	The Module ID to unsubscribe
 * @param	int  $category	The Item ID to unsubscribe
 * @param	int  $item_id	The Item ID to unsubscribe
 * @return	bool	Did the unsubscribing succeed?
 * @todo Move to a static class method - Notification
 */
function xoops_notification_deletebyitem ($module_id, $category, $item_id)
{
	$notification_handler =& icms::handler('icms_data_notification');
	return $notification_handler->unsubscribeByItem ($module_id, $category, $item_id);
}

// ################### Comment helper functions ####################
/**
 * Count the comments belonging to a certain item in a certain module
 *
 * @param	int  $module_id	The Module ID to count the comments for
 * @param	int  $item_id	The Item ID to count the comments for
 * @return	int	The number of comments
 * @todo Move to a static class method - Comment
 */
function xoops_comment_count($module_id, $item_id = null)
{
	$comment_handler = icms::handler('icms_data_comment');
	$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('com_modid', (int) ($module_id)));
	if(isset($item_id)) {$criteria->add(new icms_db_criteria_Item('com_itemid', (int) ($item_id)));}
	return $comment_handler->getCount($criteria);
}

/**
 * Delete the comments belonging to a certain item in a certain module
 *
 * @param	int  $module_id	The Module ID to delete the comments for
 * @param	int  $item_id	The Item ID to delete the comments for
 * @return	bool	Did the deleting of the comments succeed?
 * @todo Move to a static class method - Comment
 */
function xoops_comment_delete($module_id, $item_id)
{
	if( (int) ($module_id) > 0 && (int) ($item_id) > 0)
	{
		$comment_handler = icms::handler('icms_data_comment');
		$comments =& $comment_handler->getByItemId($module_id, $item_id);
		if(is_array($comments))
		{
			$count = count($comments);
			$deleted_num = array();
			for($i = 0; $i < $count; $i++)
			{
				if(false != $comment_handler->delete($comments[$i]))
				{
					// store poster ID and deleted post number into array for later use
					$poster_id = $comments[$i]->getVar('com_uid');
					if($poster_id != 0) {$deleted_num[$poster_id] = !isset($deleted_num[$poster_id]) ? 1 : ($deleted_num[$poster_id] + 1);}
				}
			}
			$member_handler = icms::handler('icms_member');
			foreach($deleted_num as $user_id => $post_num)
			{
				// update user posts
				$com_poster = $member_handler->getUser($user_id);
				if(is_object($com_poster)) {$member_handler->updateUserByField($com_poster, 'posts', $com_poster->getVar('posts') - $post_num);}
			}
			return true;
		}
	}
	return false;
}

// ################ Group Permission Helper Functions ##################
/**
 * Deletes group permissions by module and item id
 *
 * @param	int  $module_id	The Module ID to delete the permissions for
 * @param	string  $perm_name	The permission name (for the module_id and item_id to delete
 * @param	int  $item_id	The Item ID to delete the permissions for
 * @return	int	Did the deleting of the group permissions succeed?
 * @todo Move to a static class method - Groupperm
 */
function xoops_groupperm_deletebymoditem($module_id, $perm_name, $item_id = null)
{
	// do not allow system permissions to be deleted
	if( (int) ($module_id) <= 1) {return false;}
	$gperm_handler = icms::handler('icms_member_groupperm');
	return $gperm_handler->deleteByModule($module_id, $perm_name, $item_id);
}

/**
 * Converts text to UTF-8 encoded text
 *
 * @param	string	$text	The Text to convert
 * @return	string	$text	The converted text
 * @todo Move to a static class method - String
 */
function xoops_utf8_encode(&$text)
{
	if(XOOPS_USE_MULTIBYTES == 1)
	{
		if(function_exists('mb_convert_encoding')) {return mb_convert_encoding($text, 'UTF-8', 'auto');}
		return $text;
	}
	return utf8_encode($text);
}

/**
 * Converts text to UTF-8 encoded text
 * @see xoops_utf8_encode
 * @todo Move to a static class method - String
 */
function xoops_convert_encoding(&$text) {return xoops_utf8_encode($text);}

/**
 * Gets Username from UserID and creates a link to the userinfo (!) page
 *
 * @param	int	$userid	The User ID
 * @return	string	The linked username (from userID or "Anonymous")
 * @todo Move to a static class method - User
 */
function xoops_getLinkedUnameFromId($userid)
{
	$userid = (int) ($userid);
	if($userid > 0)
	{
		$member_handler = icms::handler('icms_member');
		$user =& $member_handler->getUser($userid);
		if(is_object($user))
		{
			$linkeduser = '<a href="'.ICMS_URL.'/userinfo.php?uid='.$userid.'">'.$user->getVar('uname').'</a>';
			return $linkeduser;
		}
	}
	return $GLOBALS['icmsConfig']['anonymous'];
}

/**
 * Trims certain text
 *
 * @param	string	$text	The Text to trim
 * @return	string	$text	The trimmed text
 * @todo Move to a static class method - String
 */
function xoops_trim($text)
{
	icms_core_Debug::setDeprecated('icms_core_DataFilter::icms_trim', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_DataFilter::icms_trim($text);
}

/**
 * Copy a file, or a folder and its contents
 *
 * @author	Aidan Lister <aidan@php.net>
 * @param	string	$source	The source
 * @param	string  $dest	  The destination
 * @return   bool	Returns true on success, false on failure
 * @deprecated	Use icms_core_Filesystem::copyRecursive, instead
 * @todo		Remove in version 1.4 - all occurrences have been removed from the core
 */
function icms_copyr($source, $dest)
{
	icms_core_Debug::setDeprecated('icms_core_Filesystem::copyRecursive', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Filesystem::copyRecursive($source, $dest);
}

/**
 * Safely create a folder
 *
 * @since 1.2.1
 * @copyright ImpressCMS
 *
 * @param string $target path to the folder to be created
 * @param integer $mode permissions to set on the folder. This is affected by umask in effect
 * @param string $base root location for the folder, ICMS_ROOT_PATH or ICMS_TRUST_PATH, for example
 * @param array $metachars Characters to exclude from a valid path name
 * @return boolean True if folder is created, False if it is not
 *
 * @deprecated	Use icms_core_Filesystem::mkdir, instead
 * @todo		Remove in version 1.4 - all occurrences have been removed from the core
 */
function icms_mkdir($target, $mode = 0777, $base = ICMS_ROOT_PATH, $metachars = array() ) {
	icms_core_Debug::setDeprecated('icms_core_Filesystem::mkdir', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Filesystem::mkdir($target, $mode, $base, $metachars);
}

/**
 * Change the permission of a file or folder
 *
 * @author	Newbb2 developpement team
 * @param	string	$target  target file or folder
 * @param	int		$mode	permission
 * @return   bool	Returns true on success, false on failure
 *
 * @deprecated	Use icms_core_Filesystem::chmod, instead
 * @todo		Remove in version 1.4 - all occurrences have been removed from the core
 */
function icms_chmod($target, $mode = 0777) {
	icms_core_Debug::setDeprecated('icms_core_Filesystem::chmod', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Filesystem::chmod($target, $mode);
}

/**
 * Get the icmsModule object of a specified module
 *
 * @param string $moduleName dirname of the module
 * @return object icmsModule object of the specified module
 * @todo Move to a static class method - Module
 */
function &icms_getModuleInfo($moduleName = false)
{
	static $icmsModules;
	if(isset($icmsModules[$moduleName]))
	{
		$ret =& $icmsModules[$moduleName];
		return $ret;
	}
	global $icmsModule;
	if(!$moduleName)
	{
		if(isset($icmsModule) && is_object($icmsModule))
		{
			$icmsModules[$icmsModule->getVar('dirname')] = & $icmsModule;
			return $icmsModules[$icmsModule->getVar('dirname')];
		}
	}
	if(!isset($icmsModules[$moduleName]))
	{
		if(isset($icmsModule) && is_object($icmsModule) && $icmsModule->getVar('dirname') == $moduleName) {$icmsModules[$moduleName] = & $icmsModule;}
		else
		{
			$hModule = icms::handler('icms_module');
			if($moduleName != 'icms') {$icmsModules[$moduleName] = & $hModule->getByDirname($moduleName);}
			else {$icmsModules[$moduleName] = & $hModule->getByDirname('system');}
		}
	}
	return $icmsModules[$moduleName];
}

/**
 * Get the config array of a specified module
 *
 * @param string $moduleName dirname of the module
 * @return array of configs
 * @todo Move to a static class method - Module
 */
function &icms_getModuleConfig($moduleName = false)
{
	static $icmsConfigs;
	if(isset ($icmsConfigs[$moduleName]))
	{
		$ret = & $icmsConfigs[$moduleName];
		return $ret;
	}
	global $icmsModule, $icmsModuleConfig;
	if(!$moduleName)
	{
		if(isset($icmsModule) && is_object($icmsModule))
		{
			$icmsConfigs[$icmsModule->getVar('dirname')] = & $icmsModuleConfig;
			return $icmsConfigs[$icmsModule->getVar('dirname')];
		}
	}
	// if we still did not found the icmsModule, this is because there is none
	if(!$moduleName)
	{
		$ret = false;
		return $ret;
	}
	if(isset($icmsModule) && is_object($icmsModule) && $icmsModule->getVar('dirname') == $moduleName) {$icmsConfigs[$moduleName] = & $icmsModuleConfig;}
	else
	{
		$module = & icms_getModuleInfo($moduleName);
		if(!is_object($module))
		{
			$ret = false;
			return $ret;
		}
		$hModConfig = icms::handler('icms_config');
		$icmsConfigs[$moduleName] = & $hModConfig->getConfigsByCat(0, $module->getVar('mid'));
	}
	return $icmsConfigs[$moduleName];
}

/**
 * Get a specific module config value
 *
 * @param string $key
 * @param string $moduleName
 * @param mixed $default
 * @return mixed
 * @todo Move to a static class method - Config
 */
function icms_getConfig($key, $moduleName = false, $default = 'default_is_undefined')
{
	if(!$moduleName) {$moduleName = icms_getCurrentModuleName();}
	$configs = icms_getModuleConfig($moduleName);
	if(isset($configs[$key])) {return $configs[$key];}
	else
	{
		if($default === 'default_is_undefined') {return null;}
		else {return $default;}
	}
}

/**
 * Get the dirname of the current module
 *
 * @return mixed dirname of the current module or false if no module loaded
 * @todo Move to a static class method - Module
 */
function icms_getCurrentModuleName()
{
	global $icmsModule;
	if(is_object($icmsModule)) {return $icmsModule->getVar('dirname');}
	else {return false;}
}

/**
 * Checks if a user is admin of $module
 *
 * @param mixed	Module to check or false if no module is passed
 * @return bool : true if user is admin
 * @todo Move to a static class method - User
 */
function icms_userIsAdmin($module = false)
{
	static $icms_isAdmin;
	if(!$module)
	{
		global $icmsModule;
		$module = $icmsModule->getVar('dirname');
	}
	if(isset ($icms_isAdmin[$module])) {return $icms_isAdmin[$module];}
	if(!icms::$user)
	{
		$icms_isAdmin[$module] = false;
		return $icms_isAdmin[$module];
	}
	$icms_isAdmin[$module] = false;
	$icmsModule = icms_getModuleInfo($module);
	if(!is_object($icmsModule)) {return false;}
	$module_id = $icmsModule->getVar('mid');
	$icms_isAdmin[$module] = icms::$user->isAdmin($module_id);
	return $icms_isAdmin[$module];
}

/**
 * Load a module language file
 *
 * If $module = core, file will be loaded from ICMS_ROOT_PATH/language/
 *
 * @param string $module dirname of the module
 * @param string $file name of the file without ".php"
 * @param bool $admin is this for a core admin side feature ?
 *
 */
function icms_loadLanguageFile($module, $file, $admin=false)
{
	global $icmsConfig;
	if($module == 'core') {$languagePath = ICMS_ROOT_PATH.'/language/';}
	else {$languagePath = ICMS_ROOT_PATH.'/modules/'.$module.'/language/';}
	$extraPath = $admin ? 'admin/' : '';
	$filename = $languagePath.$icmsConfig['language'].'/'.$extraPath.$file.'.php';
	if(!file_exists($filename)) {$filename = $languagePath.'english/'.$extraPath.$file.'.php';}
	if(file_exists($filename)) {include_once $filename ;}
}

/**
 * @author pillepop2003 at yahoo dot de
 *
 * Use this snippet to extract any float out of a string. You can choose how a single dot is treated with the (bool) 'single_dot_as_decimal' directive.
 * This function should be able to cover almost all floats that appear in an european environment.
 *
 * @param string $str	String to get float value from
 * @param mixed	$set	Array of settings of False if no settings were passed
 * @param mixed	Float value or 0 if no match was found in the string
 * @todo Move to a static class method - String/Data
 */
function icms_getfloat($str, $set=FALSE)
{
	if(preg_match("/([0-9\.,-]+)/", $str, $match))
	{
		// Found number in $str, so set $str that number
		$str = $match[0];
		if(strstr($str, ','))
		{
			// A comma exists, that makes it easy, cos we assume it separates the decimal part.
			$str = str_replace('.', '', $str); // Erase thousand seps
			$str = str_replace(',', '.', $str); // Convert , to . for (float) command
			return (float)($str);
		}
		else
		{
			// No comma exists, so we have to decide, how a single dot shall be treated
			if(preg_match("/^[0-9\-]*[\.]{1}[0-9-]+$/", $str) == TRUE && $set['single_dot_as_decimal'] == TRUE) {return (float)($str);}
			else
			{
				$str = str_replace('.', '', $str);	// Erase thousand seps
				return (float)($str);
			}
		}
	}
	else {return 0;}
}

/**
 * Use this snippet to extract any currency out of a string
 *
 * @param string $var	String to get currency value from
 * @param mixed	$currencyObj	Currency object or false if no object was passed
 * @return string	$ret The returned value
 * @todo Move to a static class method - Currency
 */
function icms_currency($var, $currencyObj=false)
{
	$ret = icms_getfloat($var,  array('single_dot_as_decimal'=> TRUE));
	$ret = round($ret, 2);
	// make sure we have at least .00 in the $var
	$decimal_section_original = strstr($ret, '.');
	$decimal_section = $decimal_section_original;
	if($decimal_section)
	{
		if(strlen($decimal_section) == 1) {$decimal_section = '.00';}
		elseif(strlen($decimal_section) == 2) {$decimal_section = $decimal_section . '0';}
		$ret = str_replace($decimal_section_original, $decimal_section, $ret);
	}
	else {$ret = $ret . '.00';}
	if($currencyObj) {$ret = $ret.' '.$currencyObj->getCode();}
	return $ret;
}

/**
 * Use this snippet to extract any currency out of a string
 *
 * @see icms_currency
 * @deprecated Use icms_currency
 * @todo remove in version 1.4
 */
function icms_float($var) {return icms_currency($var);}

/**
 * Strip text from unwanted text (purify)
 *
 * @param string $text	String to purify
 * @param mixed	$keyword	The keyword string or false if none was passed
 * @return string	$text The purified text
 * @todo Remove this and use HTML Purifier
 */
function icms_purifyText($text, $keyword = false) {
	$text = str_replace('&nbsp;', ' ', $text);
	$text = str_replace('<br />', ' ', $text);
	$text = str_replace('<br/>', ' ', $text);
	$text = str_replace('<br', ' ', $text);
	$text = strip_tags($text);
	$text = html_entity_decode($text);
	$text = icms_core_DataFilter::undoHtmlSpecialChars($text);
	$text = str_replace(')', ' ', $text);
	$text = str_replace('(', ' ', $text);
	$text = str_replace(':', ' ', $text);
	$text = str_replace('&euro', ' euro ', $text);
	$text = str_replace('&hellip', '...', $text);
	$text = str_replace('&rsquo', ' ', $text);
	$text = str_replace('!', ' ', $text);
	$text = str_replace('?', ' ', $text);
	$text = str_replace('"', ' ', $text);
	$text = str_replace('-', ' ', $text);
	$text = str_replace('\n', ' ', $text);
	$text = str_replace('&#8213;', ' ', $text);

	if($keyword) {
		$text = str_replace('.', ' ', $text);
		$text = str_replace(',', ' ', $text);
		$text = str_replace('\'', ' ', $text);
	}
	$text = str_replace(';', ' ', $text);

	return $text;
}

/**
 * Converts HTML to text equivalents
 *
 * @param string $document	The document string to convert
 * @return string	$text The converted text
 * @todo Remove this and use the proper data filter and HTML Purifier
 */
function icms_html2text($document)
{
	// PHP Manual:: function preg_replace
	// $document should contain an HTML document.
	// This will remove HTML tags, javascript sections
	// and white space. It will also convert some
	// common HTML entities to their text equivalent.
	// Credits : newbb2
	$search = array ("'<script[^>]*?>.*?</script>'si",  // Strip out javascript
	"'<img.*?/>'si",	   // Strip out img tags
	"'<[\/\!]*?[^<>]*?>'si",		  // Strip out HTML tags
	"'([\r\n])[\s]+'",				// Strip out white space
	"'&(quot|#34);'i",				// Replace HTML entities
	"'&(amp|#38);'i",
	"'&(lt|#60);'i",
	"'&(gt|#62);'i",
	"'&(nbsp|#160);'i",
	"'&(iexcl|#161);'i",
	"'&(cent|#162);'i",
	"'&(pound|#163);'i",
	"'&(copy|#169);'i",
	"'&#(\d+);'e");					// evaluate as php

	$replace = array ("",
	"",
	"",
	"\\1",
	"\"",
	"&",
	"<",
	">",
	" ",
	chr(161),
	chr(162),
	chr(163),
	chr(169),
	"chr(\\1)");

	$text = preg_replace($search, $replace, $document);
	return $text;

}

/**
 * Function to keeps the code clean while removing unwanted attributes and tags.
 * This function was got from http://www.php.net/manual/en/function.strip-tags.php#81553
 *
 * @var $sSource - string - text to remove the tags
 * @var $aAllowedTags - array - tags that dont will be striped
 * @var $aDisabledAttributes - array - attributes not allowed, will be removed from the text
 *
 * @return string
 * @todo Remove this and replace with the proper data filter and HTML Purifier
 */
function icms_cleanTags($sSource, $aAllowedTags = array('<h1>','<b>','<u>','<a>','<ul>','<li>'), $aDisabledAttributes = array('onabort', 'onblur', 'onchange', 'onclick', 'ondblclick', 'onerror', 'onfocus', 'onkeydown', 'onkeyup', 'onload', 'onmousedown', 'onmousemove', 'onmouseover', 'onmouseup', 'onreset', 'onresize', 'onselect', 'onsubmit', 'onunload'))
{
	if(empty($aDisabledAttributes)) return strip_tags($sSource, implode('', $aAllowedTags));
	return preg_replace('/<(.*?)>/ie', "'<' . preg_replace(array('/javascript:[^\"\']*/i', '/(".implode('|', $aDisabledAttributes).")[ \\t\\n]*=[ \\t\\n]*[\"\'][^\"\']*[\"\']/i', '/\s+/'), array('', '', ' '), stripslashes('\\1')) . '>'", strip_tags($sSource, implode('', $aAllowedTags)));
}

/**
 * Store a cookie
 *
 * @param string $name name of the cookie
 * @param string $value value of the cookie
 * @param int $time duration of the cookie
 */
function icms_setCookieVar($name, $value, $time = 0)
{
	if($time == 0) {$time = time() + 3600 * 24 * 365;}
	setcookie($name, $value, $time, '/');
}

/**
 * Get a cookie value
 *
 * @param string $name name of the cookie
 * @param string $default value to return if cookie not found
 *
 * @return string value of the cookie or default value
 */
function icms_getCookieVar($name, $default = '')
{
	$name = str_replace('.', '_', $name);
	if((isset($_COOKIE[$name])) && ($_COOKIE[$name] > '')) {return $_COOKIE[$name];}
	else {return $default;}
}

/**
 * Get URL of the page before the form to be able to redirect their after the form has been posted
 *
 * @return string url before form
 */
function icms_get_page_before_form()
{
	global $impresscms;
	return isset($_POST['icms_page_before_form']) ? $_POST['icms_page_before_form'] : $impresscms->urls['previouspage'];
}

/**
 * Get URL of the page before the form to be able to redirect their after the form has been posted
 *
 * @param	array	$matches	Array of matches to sanitize
 * @return mixed The sanitized tag or empty string
 * @todo Move to a static class method - Customtag
 */
function icms_sanitizeCustomtags_callback($matches) {
	$icms_customtag_handler = icms_getModuleHandler("customtag", "system");
	$icms_customTagsObj = $icms_customtag_handler->getCustomtagsByName();
	if (!isset($icms_customTagsObj[$matches[1]])) return "";
	return $icms_customTagsObj[$matches[1]]->renderWithPhp();
}

/**
 * Get URL of the page before the form to be able to redirect their after the form has been posted
 *
 * @param	array	$matches	Array of matches to sanitize
 * @return mixed The sanitized tag or empty string
 * @todo Move to a static class method - Adsense
 */
function icms_sanitizeAdsenses_callback($matches) {
	$icms_adsense_handler = icms_getModuleHandler("adsense", "system");
	$icms_adsensesObj = $icms_adsense_handler->getAdsensesByTag();
	if (!isset($icms_adsensesObj[$matches[1]])) return '';
	return $icms_adsensesObj[$matches[1]]->render();
}

/**
 * @deprecated Use icms_member_user_Handler instead
 * @todo Remove this function in version 1.4
 */
function icms_getLinkedUnameFromId($userid, $name = FALSE, $users = array (), $withContact = FALSE) {
	icms_core_Debug::setDeprecated('icms_member_user_Handler::getUserLink', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_member_user_Handler::getUserLink($userid, $name, $users, $withContact);
}

/**
 * Get an array of the table used in a module
 *
 * @param string $moduleName name of the module
 * @param $items array of items managed by the module
 * @return array of tables used in the module
 * @todo Move to a static class method - Module
 */
function icms_getTablesArray($moduleName, $items)
{
	$ret = array();
	if (is_array($items))
	foreach($items as $item) {$ret[] = $moduleName.'_'.$item;}
	return $ret;
}

/**
 * Function to create a navigation menu in content pages.
 * This function was based on the function that do the same in mastop publish module
 *
 * @param integer $id
 * @param string $separador
 * @param string $style
 * @return string
 */
function showNav($id = null, $separador = '/', $style="style='font-weight:bold'")
{
	$url = ICMS_URL.'/content.php';
	if($id == false) {return false;}
	else
	{
		if($id > 0)
		{
			/**
			 * @todo this handler doesn't even exist since 1.2. check if the function is still required
			 */
			$content_handler =& xoops_gethandler('content');
			$cont = $content_handler->get($id);
			if($cont->getVar('content_id') > 0)
			{
				$seo = $content_handler->makeLink($cont);
				$ret = "<a href='".$url."?page=".$seo."'>".$cont->getVar('content_title')."</a>";
				if($cont->getVar('content_supid') == 0) {return "<a href='".ICMS_URL."'>"._CT_NAV."</a> $separador ".$ret;}
				elseif($cont->getVar('content_supid') > 0) {$ret = showNav($cont->getVar('content_supid'), $separador)." $separador ".$ret;}
			}
		}
		else {return false;}
	}
	return $ret;
}

/**
 * Searches text for unwanted tags and removes them
 *
 * @param string $text	String to purify
 * @return string	$text The purified text
 * @todo Remove and replace with the proper data filter and HTML Purifier
 */
function StopXSS($text)
{
	if(!is_array($text))
	{
		$text = preg_replace("/\(\)/si", "", $text);
		$text = strip_tags($text);
		$text = str_replace(array("\"",">","<","\\"), "", $text);
	}
	else
	{
		foreach($text as $k=>$t)
		{
			if (is_array($t)) {
				StopXSS($t);
			} else {
				$t = preg_replace("/\(\)/si", "", $t);
				$t = strip_tags($t);
				$t = str_replace(array("\"",">","<","\\"), "", $t);
				$text[$k] = $t;
			}
		}
	}
	return $text;
}

/**
 * Purifies the CSS that is put in the content (pages) system
 *
 * @param string $text	String to purify
 * @return string	$text The purified text
 * @todo Remove and replace with the proper data filter and HTML Purifier
 */
function icms_sanitizeContentCss($text)
{
	if(preg_match_all('/(.*?)\{(.*?)\}/ie',$text,$css))
	{
		$css = $css[0];
		$perm = $not_perm = array();
		foreach($css as $k=>$v)
		{
			if(!preg_match('/^\#impress_content(.*?)/ie',$v)) {$css[$k] = '#impress_content '.icms_cleanTags(trim($v),array())."\r\n";}
			else {$css[$k] = icms_cleanTags(trim($v),array())."\r\n";}
		}
		$text = implode($css);
	}
	return $text;
}

/**
 * Function to get the base domain name from a URL.
 * credit for this function should goto Phosphorus and Lime, it is released under LGPL.
 *
 * @param string $url the URL to be stripped.
 * @return string
 * @todo Move to a static class method - HTTP or URI
 */
function icms_get_base_domain($url)
{
	$debug = 0;
	$base_domain = '';

	// generic tlds (source: http://en.wikipedia.org/wiki/Generic_top-level_domain)
	$G_TLD = array(
	'biz','com','edu','gov','info','int','mil','name','net','org','aero','asia','cat','coop','jobs','mobi','museum','pro','tel','travel',
	'arpa','root','berlin','bzh','cym','gal','geo','kid','kids','lat','mail','nyc','post','sco','web','xxx',
	'nato', 'example','invalid','localhost','test','bitnet','csnet','ip','local','onion','uucp','co');

	// country tlds (source: http://en.wikipedia.org/wiki/Country_code_top-level_domain)
	$C_TLD = array(
	// active
	'ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','ax','az',
	'ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bw','by','bz',
	'ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz',
	'de','dj','dk','dm','do','dz','ec','ee','eg','er','es','et','eu','fi','fj','fk','fm','fo',
	'fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw',
	'gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir','is','it','je',
	'jm','jo','jp','ke','kg','kh','ki','km','kn','kr','kw','ky','kz','la','lb','lc','li','lk',
	'lr','ls','lt','lu','lv','ly','ma','mc','md','mg','mh','mk','ml','mm','mn','mo','mp','mq',
	'mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np',
	'nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pn','pr','ps','pt','pw','py','qa',
	're','ro','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sk','sl','sm','sn','sr','st',
	'sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tr','tt','tv','tw',
	'tz','ua','ug','uk','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yu',
	'za','zm','zw',
	// inactive
	'eh','kp','me','rs','um','bv','gb','pm','sj','so','yt','su','tp','bu','cs','dd','zr');

	// get domain
	if(!$full_domain = icms_get_url_domain($url)) {return $base_domain;}

	// break up domain, reverse
	$DOMAIN = explode('.', $full_domain);
	if($debug) print_r($DOMAIN);
	$DOMAIN = array_reverse($DOMAIN);
	if($debug) print_r($DOMAIN);

	// first check for ip address
	if(count($DOMAIN) == 4 && is_numeric($DOMAIN[0]) && is_numeric($DOMAIN[3])) {return $full_domain;}

	// if only 2 domain parts, that must be our domain
	if(count($DOMAIN) <= 2) return $full_domain;

	/*
	 finally, with 3+ domain parts: obviously D0 is tld now,
	 if D0 = ctld and D1 = gtld, we might have something like com.uk so,
	 if D0 = ctld && D1 = gtld && D2 != 'www', domain = D2.D1.D0 else if D0 = ctld && D1 = gtld && D2 == 'www',
	 domain = D1.D0 else domain = D1.D0 - these rules are simplified below.
	 */
	if(in_array($DOMAIN[0], $C_TLD) && in_array($DOMAIN[1], $G_TLD) && $DOMAIN[2] != 'www')
	{
		$full_domain = $DOMAIN[2].'.'.$DOMAIN[1].'.'.$DOMAIN[0];
	}
	else
	{
		$full_domain = $DOMAIN[1].'.'.$DOMAIN[0];
	}
	// did we succeed?
	return $full_domain;
}

/**
 * Function to get the domain from a URL.
 * credit for this function should goto Phosphorus and Lime, it is released under LGPL.
 *
 * @param string $url the URL to be stripped.
 * @return string
 * @todo Move to a static class method - HTTP or URI
 */
function icms_get_url_domain($url)
{
	$domain = '';
	$_URL = parse_url($url);

	if(!empty($_URL) || !empty($_URL['host'])) {$domain = $_URL['host'];}
	return $domain;
}

/**
 * Function to wordwrap given text.
 *
 * @param string $str 	The text to be wrapped.
 * @param string $width The column width - text will be wrapped when longer than $width.
 * @param string $break The line is broken using the optional break parameter.
 *			can be '/n' or '<br />'
 * @param string $cut 	If cut is set to TRUE, the string is always wrapped at the specified width.
 *			So if you have a word that is larger than the given width, it is broken apart..
 * @return string
 * @todo Move to a static class method - String
 */
function icms_wordwrap($str, $width, $break = '/n', $cut = false)
{
	if(strtolower(_CHARSET) !== 'utf-8')
	{
		$str = wordwrap($str, $width, $break, $cut);
		return $str;
	}
	else
	{
		$splitedArray = array();
		$lines = explode("\n", $str);
		foreach($lines as $line)
		{
			$lineLength = strlen($line);
			if($lineLength > $width)
			{
				$words = explode("\040", $line);
				$lineByWords = '';
				$addNewLine = true;
				foreach($words as $word)
				{
					$lineByWordsLength = strlen($lineByWords);
					$tmpLine = $lineByWords.((strlen($lineByWords) !== 0) ? ' ' : '').$word;
					$tmplineByWordsLength = strlen($tmpLine);
					if($tmplineByWordsLength > $width && $lineByWordsLength <= $width && $lineByWordsLength !== 0)
					{
						$splitedArray[] = $lineByWords;
						$lineByWords = '';
					}
					$newLineByWords = $lineByWords.((strlen($lineByWords) !== 0) ? ' ' : '').$word;
					$newLineByWordsLength = strlen($newLineByWords);
					if($cut && $newLineByWordsLength > $width)
					{
						for($i = 0; $i < $newLineByWordsLength; $i = $i + $width) {$splitedArray[] = mb_substr($newLineByWords, $i, $width);}
						$addNewLine = false;
					}
					else	{$lineByWords = $newLineByWords;}
				}
				if($addNewLine) {$splitedArray[] = $lineByWords;}
			}
			else	{$splitedArray[] = $line;}
		}
		return implode($break, $splitedArray);
	}
}

/**
 * Function to reverse given text with utf-8 character sets
 *
 * credit for this function should goto lwc courtesy of php.net.
 *
 * @param string $str		The text to be reversed.
 * @param string $reverse	true will reverse everything including numbers, false will reverse text only but numbers will be left intact.
 *				example: when true: impresscms 2008 > 8002 smcsserpmi, false: impresscms 2008 > 2008 smcsserpmi
 * @return string
 */
function icms_utf8_strrev($str, $reverse = false)
{
	icms_core_Debug::setDeprecated('icms_core_DataFilter::utf8_strrev', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_DataFilter::utf8_strrev($str, $reverse);
}

/**
 * Function to get a query from DB
 *
 * @param object $db	Reference to the database object
 * @param string	$table	The table to get the value from
 * @param string	$field	The table to get the value from
 * @param string	$condition	The where condition (where clause) to use
 * @return	mixed
 * @todo Move to a static class method - database
 */
function getDbValue(&$db, $table, $field, $condition = '')
{
	$table = $db->prefix( $table );
	$sql = "SELECT `$field` FROM `$table`";
	if($condition) {$sql .= " WHERE $condition";}
	$result = $db->query($sql);
	if($result)
	{
		$row = $db->fetchRow($result);
		if($row) {return $row[0];}
	}
	return false;
}

/**
 * Function to escape $value makes safe for DB Queries.
 *
 * @param string $quotes - true/false - determines whether to add quotes to the value or not.
 * @param string $value - $variable that is being escaped for query.
 * @return string
 * @todo Move to a static class method - Database or Filter
 */
function icms_escapeValue($value, $quotes = true)
{
	if(is_string($value))
	{
		if(get_magic_quotes_gpc) {$value = stripslashes($value);}
		$value = icms::$xoopsDB->escape($value);
		if($quotes) {$value = '"'.$value.'"';}
	}
	elseif($value === null) {$value = 'NULL';}
	elseif(is_bool($value)) {$value = $value ? 1 : 0;}
	elseif(is_numeric($value)) {$value = (int) ($value);}
	elseif(is_int($value)) {$value = (int) ($value);}
	elseif(!is_numeric($value))
	{
		$value = icms::$xoopsDB->escape($value);
		if($quotes) {$value = '"'.$value.'"';}
	}
	return $value;
}

/**
 * Get a number value in other languages
 *
 * @param int $string Content to be transported into another language
 * @return string inout with replaced numeric values
 *
 * Example: In Persian we use, (۱, ۲, ۳, ۴, ۵, ۶, ۷, ۸, ۹, ۰) instead of (1, 2, 3, 4, 5, 6, 7, 8, 9, 0)
 * Now in a module and we are showing amount of reads, the output in Persian must be ۱۲ (which represents 12).
 * To developers, please use this function when you are having a numeric output, as this is counted as a string in php so you should use %s.
 * Like:
 * $views = sprintf ( 'Viewed: %s Times.', icms_conv_nr2local($string) );
 * @todo Move to a static class method - String or Localization
 */
function icms_conv_nr2local($string)
{
	$basecheck = defined('_USE_LOCAL_NUM') && _USE_LOCAL_NUM;
	if ( $basecheck ){
		$string = str_replace(
		array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
		array(_LCL_NUM0, _LCL_NUM1, _LCL_NUM2, _LCL_NUM3, _LCL_NUM4, _LCL_NUM5, _LCL_NUM6, _LCL_NUM7, _LCL_NUM8, _LCL_NUM9), $string);
	}
	return $string;
}

/**
 * Get a number value in other languages and transform it to English
 *
 * This function is exactly the opposite of icms_conv_nr2local();
 * Please view the notes there for more information.
 * @todo Move to a static class method - String or Localization
 */
function icms_conv_local2nr($string)
{
	$basecheck = defined('_USE_LOCAL_NUM') && _USE_LOCAL_NUM;
	if ( $basecheck ){
		$string = str_replace(
		array(_LCL_NUM0, _LCL_NUM1, _LCL_NUM2, _LCL_NUM3, _LCL_NUM4, _LCL_NUM5, _LCL_NUM6, _LCL_NUM7, _LCL_NUM8, _LCL_NUM9),
		array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
		$string);
	}
	return $string;
}

/**
 * Get month name by its ID
 *
 * @param int $month_id ID of the month
 * @return string month name
 * @todo Move to a static class method - Date or Calendar
 */
function Icms_getMonthNameById($month_id) {
	global $icmsConfig;
	icms_loadLanguageFile('core', 'calendar');
	$month_id = icms_conv_local2nr($month_id);
	if( $icmsConfig['use_ext_date'] == true && defined ('_CALENDAR_TYPE') && _CALENDAR_TYPE == "jalali"){
		switch($month_id) {
			case 1:
				return _CAL_FARVARDIN;
				break;
			case 2:
				return _CAL_ORDIBEHESHT;
				break;
			case 3:
				return _CAL_KHORDAD;
				break;
			case 4:
				return _CAL_TIR;
				break;
			case 5:
				return _CAL_MORDAD;
				break;
			case 6:
				return _CAL_SHAHRIVAR;
				break;
			case 7:
				return _CAL_MEHR;
				break;
			case 8:
				return _CAL_ABAN;
				break;
			case 9:
				return _CAL_AZAR;
				break;
			case 10:
				return _CAL_DEY;
				break;
			case 11:
				return _CAL_BAHMAN;
				break;
			case 12:
				return _CAL_ESFAND;
				break;
		}
	}else{
		switch($month_id) {
			case 1:
				return _CAL_JANUARY;
				break;
			case 2:
				return _CAL_FEBRUARY;
				break;
			case 3:
				return _CAL_MARCH;
				break;
			case 4:
				return _CAL_APRIL;
				break;
			case 5:
				return _CAL_MAY;
				break;
			case 6:
				return _CAL_JUNE;
				break;
			case 7:
				return _CAL_JULY;
				break;
			case 8:
				return _CAL_AUGUST;
				break;
			case 9:
				return _CAL_SEPTEMBER;
				break;
			case 10:
				return _CAL_OCTOBER;
				break;
			case 11:
				return _CAL_NOVEMBER;
				break;
			case 12:
				return _CAL_DECEMBER;
				break;
		}
	}
}

/**
 * This function is to convert date() function outputs into local values
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @since		1.2
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @param int $type	The type of date string?
 * @param string $maket	The date string type
 * @return mixed The converted date string
 * @todo Move to a static class method - Date or Calendar
 */
function ext_date($time)
{
	icms_loadLanguageFile('core', 'calendar');
	/*		$string = str_replace(
		array(_CAL_AM, _CAL_PM, _CAL_AM_LONG, _CAL_PM_LONG, _CAL_SAT, _CAL_SUN, _CAL_MON, _CAL_TUE, _CAL_WED, _CAL_THU, _CAL_FRI, _CAL_SATURDAY, _CAL_SUNDAY, _CAL_MONDAY, _CAL_TUESDAY, _CAL_WEDNESDAY, _CAL_THURSDAY, _CAL_FRIDAY),
		array('Am', 'Pm', 'AM', 'PM', 'Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
		$string);
		*/
	$trans = array( 'am'	=> _CAL_AM,
					'pm'	=> _CAL_PM,
					'AM'	=> _CAL_AM_CAPS,
					'PM'	=> _CAL_PM_CAPS,
					'Monday'	=> _CAL_MONDAY,
					'Tuesday'   => _CAL_TUESDAY,
					'Wednesday' => _CAL_WEDNESDAY,
					'Thursday'  => _CAL_THURSDAY,
					'Friday'	=> _CAL_FRIDAY,
					'Saturday'  => _CAL_SATURDAY,
					'Sunday'	=> _CAL_SUNDAY,
					'Mon'		=> _CAL_MON,
					'Tue'	   => _CAL_TUE,
					'Wed'		 => _CAL_WED,
					'Thu'		  => _CAL_THU,
					'Fri'		=> _CAL_FRI,
					'Sat'		  => _CAL_SAT,
					'Sun'		=> _CAL_SUN,
					'Januari'	=> _CAL_JANUARY,
					'Februari'	=> _CAL_FEBRUARY,
					'March'		=> _CAL_MARCH,
					'April'		=> _CAL_APRIL,
					'May'		=> _CAL_MAY,
					'June'		=> _CAL_JUNE,
					'July'		=> _CAL_JULY,
					'August'	=> _CAL_AUGUST,
					'September' => _CAL_SEPTEMBER,
					'October'	=> _CAL_OCTOBER,
					'November'	=> _CAL_NOVEMBER,
					'December'	=> _CAL_DECEMBER,
					'Jan'		=> _CAL_JAN,
					'Feb'		=> _CAL_FEB,
					'Mar'		=> _CAL_MAR,
					'Apr'		=> _CAL_APR,
					'May'		=> _CAL_MAY,
					'Jun'		=> _CAL_JUN,
					'Jul'		=> _CAL_JUL,
					'Aug'		=> _CAL_AUG,
					'Sep'		 => _CAL_SEP,
					'Oct'		=> _CAL_OCT,
					'Nov'		=> _CAL_NOV,
					'Dec'		=> _CAL_DEC );

	$timestamp = strtr( $time, $trans );
	return $timestamp;
}

/**
 * Function to display formatted times in user timezone
 *
 * @param string  $time  String with time
 * @param string  $format  The time format based on PHP function format parameters
 * @param string  $timeoffset  The time offset string
 * @return string  $usertimestamp  The generated user timestamp
 * @todo Move to a static class method - Date or Calendar
 */
function formatTimestamp($time, $format = "l", $timeoffset = null)
{
	global $icmsConfig;

	$format_copy = $format;
	$format = strtolower($format);

	if ($format == "rss" || $format == "r"){
		$TIME_ZONE = "";
		if (!empty($GLOBALS['icmsConfig']['server_TZ'])){
			$server_TZ = abs( (int) ($GLOBALS['icmsConfig']['server_TZ'] * 3600.0));
			$prefix = ($GLOBALS['icmsConfig']['server_TZ'] < 0) ?  " -" : " +";
			$TIME_ZONE = $prefix.date("Hi", $server_TZ);
		}
		$date = gmdate("D, d M Y H:i:s", (int) ($time)) . $TIME_ZONE;
		return $date;
	}

	if ( ($format == "elapse" || $format == "e") && $time < time() ) {
		$elapse = time() - $time;
		if ( $days = floor( $elapse / (24 * 3600) ) ) {
			$num = $days > 1 ? sprintf(_DAYS, $days) : _DAY;
		} elseif ( $hours = floor( ( $elapse % (24 * 3600) ) / 3600 ) ) {
			$num = $hours > 1 ? sprintf(_HOURS, $hours) : _HOUR;
		} elseif ( $minutes = floor( ( $elapse % 3600 ) / 60 ) ) {
			$num = $minutes > 1 ? sprintf(_MINUTES, $minutes) : _MINUTE;
		} else {
			$seconds = $elapse % 60;
			$num = $seconds > 1 ? sprintf(_SECONDS, $seconds) : _SECOND;
		}
		$ret = sprintf(_ELAPSE, icms_conv_nr2local($num));
		return $ret;
	}

	// disable user timezone calculation and use default timezone,
	// for cache consideration
	if ($timeoffset === null) {
		$timeoffset = ($icmsConfig['default_TZ'] == '') ? '0.0' : $icmsConfig['default_TZ'];
	}

	$usertimestamp = xoops_getUserTimestamp($time, $timeoffset);

	switch ($format) {
		case 'daynumber':
			$datestring = 'd';
			break;
		case 'D':
			$datestring = 'D';
			break;
		case 'F':
			$datestring = 'F';
			break;
		case 'hs':
			$datestring = 'h';
			break;
		case 'H':
			$datestring = 'H';
			break;
		case 'gg':
			$datestring = 'g';
			break;
		case 'G':
			$datestring = 'G';
			break;
		case 'i':
			$datestring = 'i';
			break;
		case 'j':
			$datestring = 'j';
			break;
		case 'l':
			$datestring = _DATESTRING;
			break;
		case 'm':
			$datestring = _MEDIUMDATESTRING;
			break;
		case 'monthnr':
			$datestring = 'm';
			break;
		case 'mysql':
			$datestring = 'Y-m-d H:i:s';
			break;
		case 'month':
			$datestring = 'M';
			break;
		case 'n':
			$datestring = 'n';
			break;
		case 's':
			$datestring = _SHORTDATESTRING;
			break;
		case 'seconds':
			$datestring = 's';
			break;
		case 'suffix':
			$datestring = 'S';
			break;
		case 't':
			$datestring = 't';
			break;
		case 'w':
			$datestring = 'w';
			break;
		case 'shortyear':
			$datestring = 'y';
			break;
		case 'Y':
			$datestring = 'Y';
			break;
		case 'c':
		case 'custom':
			static $current_timestamp, $today_timestamp, $monthy_timestamp;
			if (!isset($current_timestamp)) {
				$current_timestamp = xoops_getUserTimestamp(time(), $timeoffset);
			}
			if (!isset($today_timestamp)) {
				$today_timestamp = mktime(0, 0, 0, date("m", $current_timestamp), date("d", $current_timestamp), date("Y", $current_timestamp));
			}

			if ( abs($elapse_today = $usertimestamp - $today_timestamp) < 24*60*60) {
				$datestring = ($elapse_today > 0) ? _TODAY : _YESTERDAY;
			} else {
				if (!isset($monthy_timestamp)) {
					$monthy_timestamp[0] = mktime(0, 0, 0, 0, 0, date("Y", $current_timestamp));
					$monthy_timestamp[1] = mktime(0, 0, 0, 0, 0, date("Y", $current_timestamp) + 1);
				}
				if ($usertimestamp >= $monthy_timestamp[0] && $usertimestamp < $monthy_timestamp[1]) {
					$datestring = _MONTHDAY;
				} else{
					$datestring = _YEARMONTHDAY;
				}
			}
			break;

		default:
			if ($format != '') {
				$datestring = $format_copy;
			} else {
				$datestring = _DATESTRING;
			}
			break;
	}

	$basecheck = $icmsConfig['use_ext_date'] == true && defined ('_CALENDAR_TYPE') && $format != 'mysql';
	if($basecheck && file_exists(ICMS_ROOT_PATH.'/language/'.$icmsConfig['language'].'/local.date.php'))
	{
		include_once ICMS_ROOT_PATH.'/language/'.$icmsConfig['language'].'/local.date.php';
		return ucfirst(local_date($datestring,$usertimestamp));
	}elseif ($basecheck && _CALENDAR_TYPE != "jalali" && $icmsConfig['language'] != 'english'){
		return ucfirst(icms_conv_nr2local(ext_date(date($datestring,$usertimestamp))));
	}elseif ($basecheck && _CALENDAR_TYPE == "jalali"){
		include_once 'jalali.php';
		return ucfirst(icms_conv_nr2local(jdate($datestring,$usertimestamp)));
	}else{
		return ucfirst(date($datestring,$usertimestamp));
	}
}

/**
 * Gets module handler instance
 *
 * @param string $name	The name of the module
 * @param string $module_dir	The module directory where to get the module class
 * @param string $module_basename	The basename of the module
 * @param bool $optional	Is the module optional? Is it bad when the module cannot be loaded?
 * @return object The module handler instance
 */
function &icms_getModuleHandler($name = null, $module_dir = null, $module_basename = null, $optional = false) {
	static $handlers;
	// if $module_dir is not specified
	if (!isset($module_dir)) {
		//if a module is loaded
		if (isset($GLOBALS['icmsModule']) && is_object($GLOBALS['icmsModule'])) {
			$module_dir = $GLOBALS['icmsModule']->getVar('dirname');
		} else {
			trigger_error(_CORE_NOMODULE, E_USER_ERROR);
		}
	} else {
		$module_dir = trim($module_dir);
	}
	$module_basename = isset($module_basename) ? trim($module_basename) : $module_dir;
	$name = (!isset($name)) ? $module_dir : trim($name);
	if (!isset($handlers[$module_dir][$name])) {
		$class = 'mod_' . $module_dir . '_' . ucfirst($name) . 'Handler';
		if (class_exists($class)) {
			$handlers[$module_dir][$name] = new $class(icms::$xoopsDB);
		} else {
			if($module_dir != 'system') {
				$hnd_file = ICMS_ROOT_PATH . "/modules/{$module_dir}/class/{$name}.php";
			} else {
				$hnd_file = ICMS_ROOT_PATH . "/modules/{$module_dir}/admin/{$name}/class/{$name}.php";
			}
			if (file_exists($hnd_file)) {include_once $hnd_file;}
			$class = ucfirst(strtolower($module_basename)) . ucfirst($name) . 'Handler';
			if (class_exists($class)) {
				$handlers[$module_dir][$name] = new $class(icms::$xoopsDB);
			}
		}
	}
	if (!isset($handlers[$module_dir][$name]) && !$optional) {
		trigger_error(sprintf(_CORE_MODULEHANDLER_NOTAVAILABLE, $module_dir, $name), E_USER_ERROR);
	}
	if (isset($handlers[$module_dir][$name])) {return $handlers[$module_dir][$name];}
	$inst = false;
	return $inst;
}

/**
 * Gets module handler
 * For Backward Compatibility.
 *
 * @param	string  $name  The name of the module
 * @param	string	$module_dir		The module directory where to get the module class
 * @return	object  $inst	The reference to the generated object
 * @deprecated Use icms_getmodulehandler instead
 * @todo Remove this function in version 1.4
 */
function &xoops_getmodulehandler($name = null, $module_dir = null, $optional = false)
{
	icms_core_Debug::setDeprecated('icms_getModuleHandler', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_getModuleHandler($name, $module_dir, $module_dir, $optional);
}

/**
 * Get URL of previous page
 *
 * @param string $default default page if previous page is not found
 * @return string previous page URL
 * @todo Move to a static class method - HTTP or URI
 */
function icms_getPreviousPage($default=false) {
	global $impresscms;
	if (isset($impresscms->urls['previouspage'])) {
		return $impresscms->urls['previouspage'];
	} elseif($default) {
		return $default;
	} else {
		return ICMS_URL;
	}
}

/**
 * Get module admin link
 *
 * @param string $moduleName dirname of the moodule
 * @return string URL of the admin side of the module
 * @todo Move to a static class method - HTTP or URI
 */
function icms_getModuleAdminLink($moduleName=false) {
	global $icmsModule;
	if (!$moduleName && (isset ($icmsModule) && is_object($icmsModule))) {
		$moduleName = $icmsModule->getVar('dirname');
	}
	$ret = '';
	if ($moduleName) {
		$ret = "<a href='" . ICMS_URL . "/modules/$moduleName/admin/index.php'>" . _CO_ICMS_ADMIN_PAGE . "</a>";
	}
	return $ret;
}

/**
 * Finds the width and height of an image (can also be a flash file)
 *
 * @credit phppp
 *
 * @var string $url path of the image file
 * @var string $width reference to the width
 * @var string $height reference to the height
 * @return bool false if impossible to find dimension
 * @todo Move to a static class method - Image
 */
function icms_getImageSize($url, & $width, & $height) {
	if (empty ($width) || empty ($height)) {
		if (!$dimension = @ getimagesize($url)) {
			return false;
		}
		if (!empty ($width)) {
			$height = $dimension[1] * $width / $dimension[0];
		}
		elseif (!empty ($height)) {
			$width = $dimension[0] * $height / $dimension[1];
		} else {
			list ($width, $height) = array (
			$dimension[0],
			$dimension[1]
			);
		}
		return true;
	} else {
		return true;
	}
}

/**
 * Gets all types of urls in one array
 *
 * @return array The array of urls
 * @todo Move to a static class method - HTTP or URI
 */
function icms_getCurrentUrls() {
	$urls = array();
	$http = ((strpos(ICMS_URL, "https://")) === false) ? ("http://") : ("https://");
	$phpself = $_SERVER['PHP_SELF'];
	$httphost = $_SERVER['HTTP_HOST'];
	$querystring = $_SERVER['QUERY_STRING'];
	if ($querystring != '') {
		$querystring = '?' . $querystring;
	}
	$currenturl = $http . $httphost . $phpself . $querystring;
	$urls = array ();
	$urls['http'] = $http;
	$urls['httphost'] = $httphost;
	$urls['phpself'] = $phpself;
	$urls['querystring'] = $querystring;
	$urls['full_phpself'] = $http . $httphost . $phpself;
	$urls['full'] = $currenturl;
	$urls['isHomePage'] = (ICMS_URL . "/index.php") == ($http . $httphost . $phpself);
	return $urls;
}

/**
 * Deletes a file
 *
 * @param string $dirname path of the file
 * @return	The unlinked dirname
 * @deprecated	Use icms_core_Filesystem::deleteFile
 * @todo		Remove in version 1.4 - all instances have been removed from the core
 */
function icms_deleteFile($dirname) {
	icms_core_Debug::setDeprecated('icms_core_Filesystem::deleteFile', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Filesystem::deleteFile($dirname);
}

/**
 * Resizes an image to maxheight and maxwidth
 *
 * @param string $src	The image file to resize
 * @param string $maxWidth	The maximum width to resize the image to
 * @param string $maxHeight	The maximum height to resize the image to
 * @return array The resized image array
 * @todo Move to a static class method - Image
 */
function icms_imageResize($src, $maxWidth, $maxHeight) {
	$width = '';
	$height = '';
	$type = '';
	$attr = '';
	if (file_exists($src)) {
		list ($width, $height, $type, $attr) = getimagesize($src);
		if ($width > $maxWidth) {
			$originalWidth = $width;
			$width = $maxWidth;
			$height = $width * $height / $originalWidth;
		}
		if ($height > $maxHeight) {
			$originalHeight = $height;
			$height = $maxHeight;
			$width = $height * $width / $originalHeight;
		}
		$attr = " width='$width' height='$height'";
	}
	return array (
	$width,
	$height,
	$type,
	$attr
	);
}

/**
 * Generates the module name with either a link or not
 *
 * @param bool $withLink	Generate the modulename with in an anchor link?
 * @param bool $forBreadCrumb	Is the module name for the breadcrumbs?
 * @param mixed $moduleName	The passed modulename or false if no modulename was passed
 * @return array
 * @todo Move to a static class method - Module
 */
function icms_getModuleName($withLink = true, $forBreadCrumb = false, $moduleName = false) {
	if (!$moduleName) {
		global $icmsModule;
		$moduleName = $icmsModule->getVar('dirname');
	}
	$icmsModule = icms_getModuleInfo($moduleName);
	$icmsModuleConfig = icms_getModuleConfig($moduleName);
	if (!isset ($icmsModule)) {
		return '';
	}

	if (!$withLink) {
		return $icmsModule->getVar('name');
	} else {
		$seoMode = icms_getModuleModeSEO($moduleName);
		if ($seoMode == 'rewrite') {
			$seoModuleName = icms_getModuleNameForSEO($moduleName);
			$ret = ICMS_URL . '/' . $seoModuleName . '/';
		} elseif ($seoMode == 'pathinfo') {
			$ret = ICMS_URL . '/modules/' . $moduleName . '/seo.php/' . $seoModuleName . '/';
		} else {
			$ret = ICMS_URL . '/modules/' . $moduleName . '/';
		}
		return '<a href="' . $ret . '">' . $icmsModule->getVar('name') . '</a>';
	}
}

/**
 * Converts size to human readable text
 *
 * @param int $size	The size to convert
 * @return string The converted size
 * @todo Move to a static class method - String
 */
function icms_convert_size($size){
	if ($size >= 1073741824){
		$ret = round(((($size/1024)/1024)/1024),1).' '._CORE_GIGABYTES_SHORTEN;
	}elseif($size >= 1048576 && $size < 1073741824){
		$ret = round((($size/1024)/1024),1).' '._CORE_MEGABYTES_SHORTEN;
	}elseif($size >= 1024 && $size < 1048576){
		$ret = round(($size/1024),1).' '._CORE_KILOBYTES_SHORTEN;
	}else{
		$ret = ($size).' '._CORE_BYTES;
	}
	return icms_conv_nr2local($ret);
}

/**
 * Generates a random string
 *
 * @param int $numchar	How many characters should the string consist of?
 * @return string The generated random string
 * @todo Move to a static class method -
 */
function icms_random_str($numchar){
	$letras = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,x,w,y,z,1,2,3,4,5,6,7,8,9,0";
	$array = explode(",", $letras);
	shuffle($array);
	$senha = implode($array, "");
	return substr($senha, 0, $numchar);
}

/**
 * Generates a random string
 *
 * @param int $currentoption	What current admin option are we in?
 * @param string $breadcrumb	The breadcrumb if it is passed, otherwise empty string
 * @todo Move to a static class method - module
 */
function icms_adminMenu($currentoption = 0, $breadcrumb = '') {
	global $icmsModule;
	$icmsModule->displayAdminMenu( $currentoption, $icmsModule -> name() . ' | ' . $breadcrumb );
}

/**
 * Loads common language file
 */
function icms_loadCommonLanguageFile() {
	icms_loadLanguageFile('system', 'common');
}

/**
 * Gets current page
 *
 * @return string The URL of the current page
 * @todo Move to a static class method - HTTP or URI
 */
function icms_getCurrentPage() {
	$urls = icms_getCurrentUrls();
	return $urls['full'];
}

/**
 * Gets module name in SEO format
 *
 * @param mixed $moduleName	Modulename if it is passed, otherwise false
 * @return string The modulename in SEO format
 * @todo Move to a static class method - Module
 */
function icms_getModuleNameForSEO($moduleName = false) {
	$icmsModule = & icms_getModuleInfo($moduleName);
	$icmsModuleConfig = & icms_getModuleConfig($moduleName);
	if (isset ($icmsModuleConfig['seo_module_name'])) {
		return $icmsModuleConfig['seo_module_name'];
	}
	$ret = icms_getModuleName(false, false, $moduleName);
	return (strtolower($ret));
}

/**
 * Determines if the module is in SEO mode
 *
 * @param mixed $moduleName	Modulename if it is passed, otherwise false
 * @return bool Is the module in SEO format?
 * @todo Move to a static class method - Module
 */
function icms_getModuleModeSEO($moduleName = false) {
	$icmsModule = & icms_getModuleInfo($moduleName);
	$icmsModuleConfig = & icms_getModuleConfig($moduleName);
	return isset ($icmsModuleConfig['seo_mode']) ? $icmsModuleConfig['seo_mode'] : false;
}

/**
 * Gets the include ID if the module is in SEO format (otherwise nothing)
 *
 * @param mixed $moduleName	Modulename if it is passed, otherwise false
 * @return mixed The module include ID otherwise nothing
 * @todo Move to a static class method - Module
 */
function icms_getModuleIncludeIdSEO($moduleName = false) {
	$icmsModule = & icms_getModuleInfo($moduleName);
	$icmsModuleConfig = & icms_getModuleConfig($moduleName);
	return !empty ($icmsModuleConfig['seo_inc_id']);
}

/**
 * Gets environment key from the $_SERVER or $_ENV superglobal
 *
 * @param string  $key  The key to get
 * @return string  $ret  The retrieved key
 * @todo Move to a static class method - HTTP
 */
function icms_getenv($key) {
	$ret = '';
	$ret = isset ($_SERVER[$key]) ? $_SERVER[$key] : (isset ($_ENV[$key]) ? $_ENV[$key] : '');
	return $ret;
}

/**
 * Gets the status of a module to see if it's active or not.
 *
 * @param string $module_name  The module's name to get
 * @param bool True if module exists and is active, otherwise false
 * @todo Move to a static class method - Module
 */
function icms_get_module_status($module_name){
	$module_handler = icms::handler('icms_module');
	$this_module = $module_handler->getByDirname($module_name);
	if($this_module && $this_module->getVar('isactive')){
		return true;
	}
	return false;
}

/**
 * Wrap a long term or word
 *
 * @author	<admin@jcink.com>
 * @param	string	$string	The string
 * @param	string  $width	  The length
 * @return   bool	Returns a long term, in several small parts with the length of $width
 * @todo Move to a static class method - String
 */
function one_wordwrap($string,$width=false){
	$width = $width ? $width : '15';
	$new_string = '';
	$s=explode(" ", $string);
	foreach ($s as $k=>$v) {
		$cnt=strlen($v);
		if($cnt>$width) $v=icms_wordwrap($v, $width, ' ', true);
		$new_string.="$v ";
	}
	return $new_string;
}

/**
 * Removes the content of a folder.
 *
 * @author	Steve Kenow (aka skenow) <skenow@impresscms.org>
 * @author	modified by Vaughan <vaughan@impresscms.org>
 * @author	modified by Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @param	string	$path	The folder path to cleaned. Must be an array like: array('templates_c' => ICMS_COMPILE_PATH . "/");
 * @param	bool  $remove_admin_cache	  True to remove admin cache, if required.
 * @deprecated	Use icms_core_Filesystem::cleanFolders
 * @todo Move to static class Filesystem
 */
function icms_clean_folders($dir, $remove_admin_cache=false) {
	icms_core_Debug::setDeprecated('icms_core_Filesystem::cleanFolders', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Filesystem::cleanFolders($dir, $remove_admin_cache);
}

/**
 * Clean up all the writeable folders
 * @param bool
 * @deprecated	User icms_core_Filesystem::cleanFolders
 * @todo		Remove in versison 1.4 - all instances have been removed from the core
 */
function icms_cleaning_write_folders() {
	icms_core_Debug::setDeprecated('icms_core_Filesystem::cleanFolders', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Filesystem::cleanFolders(array('templates_c' => ICMS_COMPILE_PATH . "/", 'cache' => ICMS_CACHE_PATH . "/"));
}

/**
 * Recursively delete a directory
 *
 * @param string $dir Directory name
 * @param bool $deleteRootToo Delete specified top-level directory as well
 * @deprecated	Use icms_core_Filesystem::deleteRecursive
 * @todo		Remove in version 1.4 - all instances have been removed from the core
 */
function icms_unlinkRecursive($dir, $deleteRootToo=true){
	icms_core_Debug::setDeprecated('icms_core_Filesystem::deleteRecursive', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Filesystem::deleteRecursive($dir, $deleteRootToo);
}

/**
 * Adds required jQuery files to header for Password meter.
 * @todo Move to a static class method - Password
 */
function icms_PasswordMeter(){
	global $xoTheme, $icmsConfigUser;
	$xoTheme->addScript(ICMS_URL.'/libraries/jquery/jquery.js', array('type' => 'text/javascript'));
	$xoTheme->addScript(ICMS_URL.'/libraries/jquery/password_strength_plugin.js', array('type' => 'text/javascript'));
	$xoTheme->addScript('', array('type' => ''), '
				$(document).ready( function() {
					$.fn.shortPass = "'._CORE_PASSLEVEL1.'";
					$.fn.badPass = "'._CORE_PASSLEVEL2.'";
					$.fn.goodPass = "'._CORE_PASSLEVEL3.'";
					$.fn.strongPass = "'._CORE_PASSLEVEL4.'";
					$.fn.samePassword = "'._CORE_UNAMEPASS_IDENTIC.'";
					$.fn.resultStyle = "";
				$(".password_adv").passStrength({
					minPass: '.$icmsConfigUser['minpass'].',
					strongnessPass: '.$icmsConfigUser['pass_level'].',
					shortPass: 		"top_shortPass",
					badPass:		"top_badPass",
					goodPass:		"top_goodPass",
					strongPass:		"top_strongPass",
					baseStyle:		"top_testresult",
					userid:			"#uname",
					messageloc:		0
				});
			});
');
}

/**
 * Build criteria automatically from an array of key=>value
 *
 * @param array $criterias array of fieldname=>value criteria
 * @return object (@link icms_db_criteria_Compo) the icms_db_criteria_Compo object
 * @todo Move to a static class method - Criteria
 */
function icms_buildCriteria($criterias) {
	$criteria = new icms_db_criteria_Compo();
	foreach($criterias as $k=>$v) {
		$criteria->add(new icms_db_criteria_Item($k, $v));
	}
	return $criteria;
}

/**
 * Build a breadcrumb
 *
 * @param array $items of the breadcrumb to be displayed
 * @return str HTML code of the breadcrumb to be inserted in another template
 * @todo Move to a static class method - Breadcrumb
 */
function icms_getBreadcrumb($items) {
	$icmsBreadcrumb = new icms_view_Breadcrumb($items);
	return $icmsBreadcrumb->render(TRUE);
}
/**
 * Build a template assignement
 *
 * @param array $items to build the smarty to be used in templates
 * @return smarty value for each item
 * @todo Move to a static class method - Template
 */
function icms_makeSmarty($items) {
	global $icmsTpl;
	if (!isset($icmsTpl) || !is_array($items))return false;
	foreach ($items as $item => $value){
		$icmsTpl->assign($item, $value);
	}
	return true;
}

/**
 * Copy a file, or a folder and its contents from a website to your host
 *
 * @author	Sina Asghari <stranger@impresscms.org>
 * @author	nensa at zeec dot biz
 * @param	string	$src	The source
 * @param	string  $dest	  The destination
 * @return   bool	Returns stream_copy_to_stream($src, $dest) on success, false on failure
 * @deprecated	Use icms_core_Filesystem::copyStream
 * @todo		Remove in version 1.4 - all instances have been removed from the core
 */
function icms_stream_copy($src, $dest)
{
	icms_core_Debug::setDeprecated('icms_core_Filesystem::copyStream', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_core_Filesystem::copyStream($src, $dest);
}
/**
 * Is a module being installed, updated or uninstalled
 * Used for setting module configuration default values or options
 *
 * The function should be in functions.admin.php, however it requires extra inclusion in xoops_version.php if so
 *
 * @param	string	$dirname	dirname of current module
 * @return	bool
 * @todo Move to a static class method - Module
 */
function icms_moduleAction($dirname = 'system')
{
	global $icmsModule;
	$ret = @(
	// action module 'system'
	!empty($icmsModule) && 'system' == $icmsModule->getVar('dirname', 'n')
	&&
	// current dirname
	($dirname == $_POST['dirname'] || $dirname == $_POST['module'])
	&&
	// current op
	('update_ok' == $_POST['op'] || 'install_ok' == $_POST['op'] || 'uninstall_ok' == $_POST['op'])
	&&
	// current action
		'modulesadmin' == $_POST['fct']
	);
	return $ret;
}

/**
 * Get localized string if it is defined
 *
 * @param	string	$name	string to be localized
 */
if (!function_exists("mod_constant")) {
	function mod_constant($name)
	{
		global $icmsModule;
		if (!empty($GLOBALS["VAR_PREFIXU"]) && @defined($GLOBALS["VAR_PREFIXU"]."_".strtoupper($name))) {
			return CONSTANT($GLOBALS["VAR_PREFIXU"]."_".strtoupper($name));
		} elseif (!empty($icmsModule) && @defined(strtoupper($icmsModule->getVar("dirname", "n")."_".$name))) {
			return CONSTANT(strtoupper($icmsModule->getVar("dirname", "n")."_".$name));
		} elseif (defined(strtoupper($name))) {
			return CONSTANT(strtoupper($name));
		} else {
			return str_replace("_", " ", strtolower($name));
		}
	}
}
/**
 *
 * Enter description here ...
 * @param unknown_type $id
 * @param unknown_type $title
 * @param unknown_type $dsc
 * @todo Move to a static class method
 */
function icms_collapsableBar($id = '', $title = '', $dsc = '') {
	global $icmsModule;
	echo "<h3 style=\"color: #2F5376; font-weight: bold; font-size: 14px; margin: 6px 0 0 0; \"><a href='javascript:;' onclick=\"togglecollapse('" . $id . "'); toggleIcon('" . $id . "_icon')\";>";
	echo "<img id='" . $id . "_icon' src=" . ICMS_URL . "/images/close12.gif alt='' /></a>&nbsp;" . $title . "</h3>";
	echo "<div id='" . $id . "'>";
	if ($dsc != '') {
		echo "<span style=\"color: #567; margin: 3px 0 12px 0; font-size: small; display: block; \">" . $dsc . "</span>";
	}
}

/**
 *
 * Enter description here ...
 * @param $id
 * @param $title
 * @param $dsc
 * @todo Move to a static class method
 */
function icms_ajaxCollapsableBar($id = '', $title = '', $dsc = '') {
	global $icmsModule;
	$onClick = "ajaxtogglecollapse('$id')";
	//$onClick = "togglecollapse('$id'); toggleIcon('" . $id . "_icon')";
	echo '<h3 style="border: 1px solid; color: #2F5376; font-weight: bold; font-size: 14px; margin: 6px 0 0 0; " onclick="' . $onClick . '">';
	echo "<img id='" . $id . "_icon' src=" . ICMS_URL . "/images/close12.gif alt='' /></a>&nbsp;" . $title . "</h3>";
	echo "<div id='" . $id . "'>";
	if ($dsc != '') {
		echo "<span style=\"color: #567; margin: 3px 0 12px 0; font-size: small; display: block; \">" . $dsc . "</span>";
	}
}
/**
 * Ajax testing......
 */
/*
 function icms_collapsableBar($id = '', $title = '', $dsc='')
 {

 global $icmsModule;
 //echo "<h3 style=\"color: #2F5376; font-weight: bold; font-size: 14px; margin: 6px 0 0 0; \"><a href='javascript:;' onclick=\"toggle('" . $id . "'); toggleIcon('" . $id . "_icon')\";>";

 ?>
 <h3 class="icms_collapsable_title"><a href="javascript:Effect.Combo('<? echo $id ?>');"><? echo $title ?></a></h3>
 <?

 echo "<img id='" . $id . "_icon' src=" . ICMS_URL . "/images/close12.gif alt='' /></a>&nbsp;" . $title . "</h3>";
 echo "<div id='" . $id . "'>";
 if ($dsc != '') {
 echo "<span style=\"color: #567; margin: 3px 0 12px 0; font-size: small; display: block; \">" . $dsc . "</span>";
 }
 }
 */
/**
 *
 * Enter description here ...
 * @param unknown_type $name
 * @todo Move to a static class method
 */
function icms_openclose_collapsable($name) {
	$urls = icms_getCurrentUrls();
	$path = $urls['phpself'];
	$cookie_name = $path . '_icms_collaps_' . $name;
	$cookie_name = str_replace('.', '_', $cookie_name);
	$cookie = icms_getCookieVar($cookie_name, '');
	if ($cookie == 'none') {
		echo '
				<script type="text/javascript"><!--
				togglecollapse("' . $name . '"); toggleIcon("' . $name . '_icon");
					//-->
				</script>
				';
	}
	/*	if ($cookie == 'none') {
	 echo '
	 <script type="text/javascript"><!--
	 hideElement("' . $name . '");
	 //-->
	 </script>
	 ';
		}
		*/
}
/**
 * @todo Move to a static class method
 * Enter description here ...
 * @param unknown_type $name
 */
function icms_close_collapsable($name) {
	echo "</div>";
	icms_openclose_collapsable($name);
	echo "<br />";
}
/**
 * Enter description here ...
 * @param $content
 * @param $title
 * @param $description
 * @param $pagetitle
 * @param $width
 * @deprecated	Use icms_view_Printerfriendly::generate, instead
 * @todo		Remove in versions 1.4
 */
function icms_MakePrinterFriendly($content, $title=false, $description=false, $pagetitle=false, $width=680) {
	icms_core_Debug::setDeprecated('icms_view_Printerfriendly::generate', sprintf(_CORE_REMOVE_IN_VERSION, '1.4'));
	return icms_view_Printerfriendly::generate($content, $title, $description, $pagetitle, $width);
}
/**
 * @todo Move to a static class method - user
 * Enter description here ...
 * @param $email
 */
function icms_getUnameFromUserEmail($email = '')
{
	$db = icms_db_Factory::instance();
	if($email !== '')
	{
		$sql = $db->query("SELECT uname, email FROM ".$db->prefix('users')." WHERE email = '".@htmlspecialchars($email,
		ENT_QUOTES, _CHARSET)."'");
		list($uname, $email) = $db->fetchRow($sql);
	}
	else
	{
		redirect_header('user.php',2,_US_SORRYNOTFOUND);
	}
	return $uname;
}

/**
 * Check if the module currently uses WYSIWYG and decied wether to do_br or not
 *
 * @return bool true | false
 * @todo Move to a static class method - text area?
 */
function icms_need_do_br($moduleName=false) {
	global $icmsConfig, $icmsModule;

	if (!$moduleName) {
		global $icmsModule;
		$theModule = $icmsModule;
		$moduleName = $theModule->getVar('dirname');
	} else {
		$theModule = icms_getModuleInfo($moduleName);
	}

	$groups = icms::$user->getGroups();

	$editor_default = $icmsConfig['editor_default'];
	$gperm_handler = icms::handler('icms_member_groupperm');
	if (file_exists(ICMS_EDITOR_PATH . "/" . $editor_default . "/xoops_version.php") && $gperm_handler->checkRight('use_wysiwygeditor', $theModule->getVar('mid'), $groups)) {
		return false;
	} else {
		return true;
	}
}
?>