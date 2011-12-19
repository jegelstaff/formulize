<?php
/**
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version	$Id: banners.php 8914 2009-06-23 16:19:02Z realtherplima $
**/

/**
* Allows banner clients to login and manage their banners
* 
* Clients can view the statistics for their banners, send the statistics to their
* email address, or change the target url for the banner
*
* @package banners
**/
 
$xoopsOption['pagetype'] = "banners";
/** Including mainfile.php is required */
include 'mainfile.php';

/**
* Function to let your client login to see 
* the stats
*/
function clientlogin()
{
	global $xoopsDB, $xoopsLogger, $icmsConfig, $icmsConfigMetaFooter, $icmsConfigPlugins, $sess_handler;
	include 'header.php';
	echo "<div id='login_window'>
	<h2 class='content_title'>"._BANNERS_LOGIN_TITLE."</h2>
	<form method='post' action='banners.php' class='login_form'>
	<div class='credentials'>
	<label for='login_form-login'>"._BANNERS_LOGIN_LOGIN."</label>
	<input type='text' name='login' id='login_form-login' value='' /><br />
	<label for='login_form-password'>"._BANNERS_LOGIN_PASS."</label>
	<input type='password' name='pass' id='login_form-password' value='' /><br />
	</div>
	<div class='actions'><input type='hidden' name='op' value='Ok' /><button type='submit'>"._BANNERS_LOGIN_OK."</button></div>
	<div class='login_info'>"._BANNERS_LOGIN_INFO."</div>".
	$GLOBALS['xoopsSecurity']->getTokenHTML("BANNER_LOGIN")."
	</form></div>";
	include 'footer.php';
}

/**
* Function to display the banners stats for 
* each client
**/
function bannerstats()
{
	global $xoopsDB, $icmsConfig, $xoopsLogger, $icmsConfigMetaFooter, $icmsConfigPlugins, $sess_handler;
	if($_SESSION['banner_login'] == '' || $_SESSION['banner_pass'] == '') {redirect_header('banners.php',2);}
	$result = $xoopsDB->query(sprintf("SELECT cid, name, passwd FROM %s WHERE login=%s", $xoopsDB->prefix('bannerclient'), $xoopsDB->quoteString($_SESSION['banner_login'])));
	list($cid, $name, $passwd) = $xoopsDB->fetchRow($result);
	if($_SESSION['banner_pass'] == $passwd)
	{
		include 'header.php';
		echo "<div id='bannerstats'>
		<h4 class='content_title'>".sprintf(_BANNERS_TITLE, $name )."</h4><hr />
		<table summary=''>
		<caption>".sprintf(_BANNERS_TITLE, $name )."</caption>
		<thead><tr>
		<td>ID</td>
		<td>"._BANNERS_IMP_MADE."</td>
		<td>"._BANNERS_IMP_TOTAL."</td>
		<td>"._BANNERS_IMP_LEFT."</td>
		<td>"._BANNERS_CLICKS."</td>
		<td>"._BANNERS_PER_CLICKS."</td>
		<td>"._BANNERS_FUNCTIONS."</td></tr></thead>
		<tfoot><tr><td colspan='7'></td></tr></tfoot>";
		
		$result = $xoopsDB->query("select bid, imptotal, impmade, clicks, date from ".$xoopsDB->prefix('banner')." where cid='".intval($cid)."'");
		$i = 0;
		while(list($bid, $imptotal, $impmade, $clicks, $date) = $xoopsDB->fetchRow($result))
		{
			if($impmade == 0)
			{
				$percent = 0;
			}
			else
			{
				$percent = substr(100 * $clicks / $impmade, 0, 5);
			}
			if($imptotal == 0)
			{
				$left = _BANNERS_UNLIMITED;
			}
			else
			{
				$left = $imptotal-$impmade;
			}
			$class = ($i % 2 == 0) ? 'even' : 'odd';
			echo "<tbody><tr class='$class'>
			<td>$bid</td>
			<td>$impmade</td>
			<td>$imptotal</td>
			<td>$left</td>
			<td>$clicks</td>
			<td>$percent%</td>
			<td><a href='banners.php?op=EmailStats&amp;cid=$cid&amp;bid=$bid' title='" . _BANNERS_STATS . "'>" . _BANNERS_STATS . "</a></td></tr></tbody>";
			$i++;
		}
		echo "</table>
		<br /><br />
		<h4 class='content_title'>". _BANNERS_FOW_IN . htmlspecialchars( $icmsConfig['sitename'] ). "</h4><hr />";

		$result = $xoopsDB->query("select bid, imageurl, clickurl, htmlbanner, htmlcode from ".$xoopsDB->prefix('banner')." where cid='".intval($cid)."'");
		while(list($bid, $imageurl, $clickurl, $htmlbanner, $htmlcode) = $xoopsDB->fetchRow($result))
		{
			$numrows = $xoopsDB->getRowsNum($result);
			if($numrows>1) {echo "<br />";}
			if(!empty($htmlbanner) && !empty($htmlcode))
			{
				echo $myts->displayTarea($htmlcode);
			}
			else
			{
				if(strtolower(substr($imageurl,strrpos($imageurl,".")))==".swf")
				{
					echo '<object type="application/x-shockwave-flash" data="'.$imageurl.'" width="468" height="60">';
					echo '<param name=movie value="'.$imageurl.'" />';
					echo '<param name="quality" value="high" />';
					echo '</object>';
				}
				else
				{
					echo '<img src="'.$imageurl.'" alt="" />';
				}
			}
			echo "<br /><strong>"._BANNERS_ID.$bid."</strong><br />".sprintf(_BANNERS_SEND_STATS, 'banners.php?op=EmailStats&amp;cid='.$cid.'&amp;bid='.$bid)."<br />";
			if(!$htmlbanner)
			{
				$clickurl = htmlspecialchars($clickurl, ENT_QUOTES);
				echo sprintf(_BANNERS_POINTS, $clickurl)."<br />
				<form action='banners.php' method='post'>"._BANNERS_URL."
				<input type='text' name='url' size='50' maxlength='200' value='$clickurl' />
				<input type='hidden' name='bid' value='$bid' />
				<input type='hidden' name='cid' value='$cid' />
				<input type='submit' name='op' value='"._BANNERS_CHANGE."' />" .
				$GLOBALS['xoopsSecurity']->getTokenHTML("BANNER_EDIT")."</form>";
			}
		}

		/* Finished Banners */
		echo "<br />";
		if($result = $xoopsDB->query("select bid, impressions, clicks, datestart, dateend from ".$xoopsDB->prefix('bannerfinish')." where cid='".intval($cid)."'"))
		{
			echo "<h4 class='content_title'>".sprintf(_BANNERS_FINISHED, $name)."</h4><hr />
			<table summary=''>
			<caption>".sprintf(_BANNERS_FINISHED, $name)."</caption>
			<thead><tr>
			<td>ID</td>
			<td>"._BANNERS_IMP_MADE."</td>
			<td>"._BANNERS_CLICKS."</td>
			<td>"._BANNERS_PER_CLICKS."</td>
			<td>"._BANNERS_STARTED."</td>
			<td>"._BANNERS_ENDED."</td></tr></thead>
			<tfoot><tr><td colspan='6'></td></tr></tfoot>";
			
			$i=0;
			while(list($bid, $impressions, $clicks, $datestart, $dateend) = $xoopsDB->fetchRow($result))
			{
				$percent = substr(100 * $clicks / $impressions, 0, 5);
				$class = ($i % 2 == 0) ? 'even' : 'odd';
				echo "<tbody><tr class='$class'>
				<td>$bid</td>
				<td>$impressions</td>
				<td>$clicks</td>
				<td>$percent%</td>
				<td>".formatTimestamp($datestart)."</td>
				<td>".formatTimestamp($dateend)."</td></tr></tbody>";
			}
			echo "</table></div>";
		}
		include 'footer.php';
	}
	else
	{
		redirect_header('banners.php',2);
	}
}

/**
* Function to let the client E-mail his     
* banner Stats
* 
* @param int $cid client id
* @param int $bid banner id
*/
function EmailStats($cid, $bid)
{
	global $xoopsDB, $icmsConfig;
	if($_SESSION['banner_login'] != "" && $_SESSION['banner_pass'] != "")
	{
		$cid = intval($cid);
		$bid = intval($bid);
		if($result2 = $xoopsDB->query(sprintf("select name, email, passwd from %s where cid='%u' AND login=%s", $xoopsDB->prefix('bannerclient'), $cid, $xoopsDB->quoteString($_SESSION['banner_login']))))
		{
			list($name, $email, $passwd) = $xoopsDB->fetchRow($result2);
			if($_SESSION['banner_pass'] == $passwd)
			{
				if($email == "")
				{
					redirect_header('banners.php', 3, sprintf(_BANNERS_MAIL_ERROR, $name));
				}
				else
				{
					if($result = $xoopsDB->query("select bid, imptotal, impmade, clicks, imageurl, clickurl, date from ".$xoopsDB->prefix('banner')." where bid='".$bid."' and cid='".$cid."'"))
					{
						list($bid, $imptotal, $impmade, $clicks, $imageurl, $clickurl, $date) = $xoopsDB->fetchRow($result);
						if($impmade == 0)
						{
							$percent = 0;
						}
						else
						{
							$percent = substr(100 * $clicks / $impmade, 0, 5);
						}
						if($imptotal == 0)
						{
							$left = _BANNERS_UNLIMITED;
							$imptotal = _BANNERS_UNLIMITED;
						}
						else
						{
							$left = $imptotal-$impmade;
						}
						$fecha = date("F jS Y, h:iA.");
						$subject = sprintf(_BANNERS_MAIL_SUBJECT, $icmsConfig['sitename']);
						$message = sprintf(_BANNERS_MAIL_MESSAGE, $icmsConfig['sitename'], $name, $bid, $imageurl, $clickurl, $imptotal, $impmade, $left, $clicks, $percent, $fecha);
						$xoopsMailer =& getMailer();
						$xoopsMailer->useMail();
						$xoopsMailer->setToEmails($email);
						$xoopsMailer->setFromEmail($icmsConfig['adminmail']);
						$xoopsMailer->setFromName($icmsConfig['sitename']);
						$xoopsMailer->setSubject($subject);
						$xoopsMailer->setBody($message);
						$xoopsMailer->send();
						redirect_header('banners.php?op=Ok', 3, _BANNERS_MAIL_OK);
					}
				}
			}
		}
	}
	redirect_header('banners.php',2);
}

/**
* Function to let the client to change the  
* url for his banner     
* 
* @param int $cid client id
* @param int $bid banner id
* @param str $url new target url for the banner
*/
function change_banner_url_by_client($cid, $bid, $url)
{
	global $xoopsDB;
	if($_SESSION['banner_login'] != "" && $_SESSION['banner_pass'] != "" && $url != "")
	{
		$cid = intval($cid);
		$bid = intval($bid);
		$sql = sprintf("select passwd from %s where cid='%u' and login=%s", $xoopsDB->prefix('bannerclient'), $cid, $xoopsDB->quoteString($_SESSION['banner_login']));
		if($result = $xoopsDB->query($sql))
		{
			list($passwd) = $xoopsDB->fetchRow($result);
			if($_SESSION['banner_pass'] == $passwd)
			{
				$sql = sprintf("update %s set clickurl=%s where bid='%u' AND cid='%u'", $xoopsDB->prefix('banner'), $xoopsDB->quoteString($url), $bid, $cid);
				if($xoopsDB->query($sql)) {redirect_header('banners.php?op=Ok', 3, 'URL has been changed.');}
			}
		}
	}
	redirect_header('banners.php',2);
}

/**
 * Updates the click counter for a banner
 * 
 * @param int $bid banner id
 */   
function clickbanner($bid)
{
	global $xoopsDB;
	$bid = intval($bid);
	if($bid > 0)
	{
		if(xoops_refcheck())
		{
			if($bresult = $xoopsDB->query("select clickurl from ".$xoopsDB->prefix('banner')." where bid=$bid"))
			{
				list($clickurl) = $xoopsDB->fetchRow($bresult);
				$xoopsDB->queryF("update ".$xoopsDB->prefix('banner')." set clicks=clicks+1 where bid='".$bid."'");
				header('Location: '.$clickurl);
				exit();
			}
		}
	}
	exit();
}

$op = '';
if(!empty($_POST['op'])) {$op = $_POST['op'];}
elseif (!empty($_GET['op'])) {$op = $_GET['op'];}

$myts =& MyTextSanitizer::getInstance();
switch($op)
{
	case 'click':
		$bid = 0;
		if(!empty($_GET['bid'])) {$bid = intval($_GET['bid']);}
		clickbanner($bid);
	break;

	case 'Ok':
		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			if(!$GLOBALS['xoopsSecurity']->check(true,false,'BANNER_LOGIN'))
			{
				redirect_header('banners.php', 3, implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
			}
			$_SESSION['banner_login'] = $myts->stripslashesGPC(trim($_POST['login']));
			$_SESSION['banner_pass'] = $myts->stripslashesGPC(trim($_POST['pass']));
		}
		bannerstats();
	break;

	case _BANNERS_CHANGE:
		if(!$GLOBALS['xoopsSecurity']->check(true,false,'BANNER_EDIT'))
		{
			redirect_header('banners.php', 3, implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
		}
		$bid = $cid = 0;
		if(!empty($_POST['url'])) {$url = $myts->stripslashesGPC(trim($_POST['url']));}
		if(!empty($_POST['bid'])) {$bid = intval($_POST['bid']);}
		if(!empty($_POST['cid'])) {$cid = intval($_POST['cid']);}
		change_banner_url_by_client($cid, $bid, $url);
	break;

	case 'EmailStats':
		$bid = $cid = 0;
		if(!empty($_GET['bid'])) {$bid = intval($_GET['bid']);}
		if(!empty($_GET['cid'])) {$cid = intval($_GET['cid']);}
		EmailStats($cid, $bid);
	break;

	case 'login':
	default:
		clientlogin();
	break;
}
?>