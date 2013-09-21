<?php
/**
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		XOOPS
 * @author		http://www.xoops.org The XOOPS Project
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: session_confirm.php 11072 2011-03-14 15:52:14Z m0nty_ $
 */

include "mainfile.php";

// security check
if (!isset($_SESSION['AUTOLOGIN_REQUEST_URI'])) exit;

// get URI
$url = $_SESSION['AUTOLOGIN_REQUEST_URI'];
unset($_SESSION['AUTOLOGIN_REQUEST_URI']);
if (preg_match('/javascript:/si', $url)) exit; // black list of url
$url4disp = preg_replace("/&amp;/i", '&', htmlspecialchars($url, ENT_QUOTES));

if (isset($_SESSION['AUTOLOGIN_POST'])) {
	// posting confirmation
	$old_post = $_SESSION['AUTOLOGIN_POST'];
	unset($_SESSION['AUTOLOGIN_POST']);

	$hidden_str = '' ;
	foreach ($old_post as $k => $v) {
		$hidden_str .= "\t".'      <input type="hidden" name="'.htmlspecialchars($k,ENT_QUOTES).'" value="'.htmlspecialchars($v,ENT_QUOTES).'" />'."\n" ;
	}

	echo '<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset='._CHARSET.'" />
		<title>'.$icmsConfig['sitename'].'</title>
		</head>
		<body>
		<div style="text-align:center; background-color: #EBEBEB; border-top: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; font-weight : bold;">
		  <h4>'._RETRYPOST.'</h4>
		  <form action="'.$url4disp.'" method="POST">
		  '.$hidden_str.'
			<input type="submit" name="timeout_repost" value="'._SUBMIT.'" />
		  </form>
		</div>
		</body>
		</html>
	' ;
	exit ;
} else {
	// just redirecting
	$time = 1 ;
	// $message = empty( $message ) ? _TAKINGBACK : $message ;
	$message = _TAKINGBACK ;

	echo '<html>
		<head>
		<meta http-equiv="Content-Type" content="text/html; charset='._CHARSET.'" />
		<meta http-equiv="Refresh" content="'.$time.'; url='.$url4disp.'" />
		<title>'.$icmsConfig['sitename'].'</title>
		</head>
		<body>
		<div style="text-align:center; background-color: #EBEBEB; border-top: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; font-weight : bold;">
		  <h4>'.$message.'</h4>
		  <p>'.sprintf(_IFNOTRELOAD, $url4disp).'</p>
		</div>
		</body>
		</html>
	' ;
	exit ;
}