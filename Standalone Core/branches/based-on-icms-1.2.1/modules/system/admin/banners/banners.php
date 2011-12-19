<?php
// $Id: banners.php 8768 2009-05-16 22:48:26Z pesianstranger $
/**
* Banner administration functions
* 
* Functions to allow adminstrators to add, edit, delete banners and clients
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	Administration
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: banners.php 8768 2009-05-16 22:48:26Z pesianstranger $
*/

if (!is_object($icmsUser) || !is_object($icmsModule) || !$icmsUser->isAdmin($icmsModule->mid()) ) {
	exit("Access Denied");
} else {

	/**
	/* Banners Administration Functions
	*/
	function BannersAdmin()
	{
		global $xoopsConfig, $icmsModule;
		$xoopsDB =& Database::getInstance();
		xoops_cp_header();
		echo '<div class="CPbigTitle" style="background-image: url('.XOOPS_URL.'/modules/system/admin/banners/images/banners_big.png)">'._MD_AM_BANS.'</div><br />';
		// Banners List
		echo "<a name='top'></a>";
		echo "<table width='100%' border='0' cellspacing='1' class='outer'><tr><td class=\"odd\">";
		echo "<div style='text-align:center'><b>"._AM_CURACTBNR."</b></div><br />
		<table width='100%' border='0'><tr>
		<td align='center'>"._AM_BANNERID."</td>
		<td align='center'>"._AM_IMPRESION."</td>
		<td align='center'>"._AM_IMPLEFT."</td>
		<td align='center'>"._AM_CLICKS."</td>
		<td align='center'>"._AM_NCLICKS."</td>
		<td align='center'>"._AM_CLINAME."</td>
		<td align='center'>"._AM_FUNCTION."</td></tr><tr align='center'>";
		$result = $xoopsDB->query("SELECT bid, cid, imptotal, impmade, clicks, date FROM ".$xoopsDB->prefix("banner")." ORDER BY bid");
		$myts =& MyTextSanitizer::getInstance();
		while(list($bid, $cid, $imptotal, $impmade, $clicks, $date) = $xoopsDB->fetchRow($result)) {
			$result2 = $xoopsDB->query("SELECT cid, name FROM ".$xoopsDB->prefix("bannerclient")." WHERE cid='".intval($cid)."'");
			list($cid, $name) = $xoopsDB->fetchRow($result2);
			$name = $myts->makeTboxData4Show($name);
			if ( $impmade == 0 ) {
				$percent = 0;
			} else {
				$percent = substr(100 * $clicks / $impmade, 0, 5);
			}
			if ( $imptotal == 0 ) {
				$left = ""._AM_UNLIMIT."";
			} else {
				$left = $imptotal-$impmade;
			}
			echo "<td align='center'>".icms_conv_nr2local($bid)."</td>
			<td align='center'>".icms_conv_nr2local($impmade)."</td>
			<td align='center'>".icms_conv_nr2local($left)."</td>
			<td align='center'>".icms_conv_nr2local($clicks)."</td>
			<td align='center'>".icms_conv_nr2local($percent)."%</td>
			<td align='center'>$name</td>
			<td align='center'><a href='admin.php?fct=banners&amp;op=BannerEdit&amp;bid=$bid'>"._AM_EDIT."</a> | <a href='admin.php?fct=banners&amp;op=BannerDelete&amp;bid=$bid&amp;ok=0'>"._AM_DELETE."</a></td><tr>";
		}
		echo "</td></tr></table>";
		echo "</td></tr></table>";
		echo "<br />";
		// Finished Banners List
		echo "<a name='top'></a>";
		echo "<table width='100%' border='0' cellspacing='1' class='outer'><tr><td class=\"odd\">";
		echo "<div style='text-align:center'><b>"._AM_FINISHBNR."</b></div><br />
		<table width='100%' border='0'><tr>
		<td align='center'>"._AM_BANNERID."</td>
		<td align='center'>"._AM_IMPD."</td>
		<td align='center'>"._AM_CLICKS."</td>
		<td align='center'>"._AM_NCLICKS."</td>
		<td align='center'>"._AM_STARTDATE."</td>
		<td align='center'>"._AM_ENDDATE."</td>
		<td align='center'>"._AM_CLINAME."</td>
		<td align='center'>"._AM_FUNCTION."</td></tr>
		<tr>";
		$result = $xoopsDB->query("SELECT bid, cid, impressions, clicks, datestart, dateend FROM ".$xoopsDB->prefix("bannerfinish")." ORDER BY bid");
		while(list($bid, $cid, $impressions, $clicks, $datestart, $dateend) = $xoopsDB->fetchRow($result)) {
			$result2 = $xoopsDB->query("SELECT cid, name FROM ".$xoopsDB->prefix("bannerclient")." WHERE cid='".intval($cid)."'");
			list($cid, $name) = $xoopsDB->fetchRow($result2);
			$name = $myts->makeTboxData4Show($name);
			$percent = substr(100 * $clicks / $impressions, 0, 5);
			echo "
			<td align='center'>".icms_conv_nr2local($bid)."</td>
			<td align='center'>".icms_conv_nr2local($impressions)."</td>
			<td align='center'>".icms_conv_nr2local($clicks)."</td>
			<td align='center'>".icms_conv_nr2local($percent)."%</td>
			<td align='center'>".formatTimestamp($datestart,"m")."</td>
			<td align='center'>".formatTimestamp($dateend,"m")."</td>
			<td align='center'>$name</td>
			<td align='center'><a href='admin.php?fct=banners&amp;op=BannerFinishDelete&amp;bid=$bid'>"._AM_DELETE."</a></td><tr>";
		}
		echo "</td></tr></table>";
		echo "</td></tr></table>";
		echo "<br />";
		// Clients List
		echo "<table width='100%' border='0' cellspacing='1' class='outer'><tr><td class=\"odd\">";
		echo "
		<div style='text-align:center'><b>"._AM_ADVCLI."</b></div><br />
		<table width='100%' border='0'><tr align='center'>
		<td align='center'>"._AM_BANNERID."</td>
		<td align='center'>"._AM_CLINAME."</td>
		<td align='center'>"._AM_ACTIVEBNR."</td>
		<td align='center'>"._AM_CONTNAME."</td>
		<td align='center'>"._AM_CONTMAIL."</td>
		<td align='center'>"._AM_FUNCTION."</td></tr><tr align='center'>";
		$result = $xoopsDB->query("SELECT cid, name, contact, email FROM ".$xoopsDB->prefix("bannerclient")." ORDER BY cid");
		while(list($cid, $name, $contact, $email) = $xoopsDB->fetchRow($result)) {
			$name = htmlspecialchars($name,ENT_QUOTES);
			$contact = htmlspecialchars($contact,ENT_QUOTES);
			$email = htmlspecialchars($email,ENT_QUOTES);
			$result2 = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("banner")." WHERE cid='".intval($cid)."'");
			list($numrows) = $xoopsDB->fetchRow($result2);
			echo "
			<td align='center'>".icms_conv_nr2local($cid)."</td>
			<td align='center'>$name</td>
			<td align='center'>".icms_conv_nr2local($numrows)."</td>
			<td align='center'>$contact</td>
			<td align='center'>$email</td>
			<td align='center'><a href='admin.php?fct=banners&amp;op=BannerClientEdit&amp;cid=$cid'>"._AM_EDIT."</a> | <a href='admin.php?fct=banners&amp;op=BannerClientDelete&amp;cid=$cid'>"._AM_DELETE."</a></td><tr>";
		}
		echo "</td></tr></table>";
		echo "</td></tr></table>";
		echo "<br />";
		// Add Banner
		$result = $xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("bannerclient"));
		list($numrows) = $xoopsDB->fetchRow($result);
		if ( $numrows > 0 ) {
			echo"<table width='100%' border='0' cellspacing='1' class='outer'><tr><td class=\"odd\">";
			echo"
			<h4>"._AM_ADDNWBNR."</h4>
			<form action='admin.php' method='post'>
			"._AM_CLINAMET."
			<select name='cid'>";
			$result = $xoopsDB->query("SELECT cid, name FROM ".$xoopsDB->prefix("bannerclient"));
			while(list($cid, $name) = $xoopsDB->fetchRow($result)) {
				$name = $myts->makeTboxData4Show($name);
				echo "<option value='$cid'>$name</option>";
			}
			echo "
			</select><br />
			"._AM_IMPPURCHT."<input type='text' name='imptotal' size='12' maxlength='11' /> ".icms_conv_nr2local(0)." = "._AM_UNLIMIT."<br />
			"._AM_IMGURLT."<input type='text' name='imageurl' size='50' maxlength='255' /><br />
			"._AM_CLICKURLT."<input type='text' name='clickurl' size='50' maxlength='255' /><br />
			"._AM_USEHTML." <input type='checkbox' name='htmlbanner' value='1' />
			<br />
			"._AM_CODEHTML."
			<br />
			<textarea name='htmlcode' rows='6' cols='60'></textarea>
			<br />
			<input type='hidden' name='fct' value='banners' />
			<input type='hidden' name='op' value='BannersAdd' />
			".$GLOBALS['xoopsSecurity']->getTokenHTML()."
			<input type='submit' value='"._AM_ADDBNR."' />
			</form>";
			echo"</td></tr></table>";
		}
		// Add Client
		echo "<br />";
		echo "<table width='100%' border='0' cellspacing='1' class='outer'><tr><td class=\"odd\">";
		echo "
		<h4>"._AM_ADDNWCLI."</h4>
		<form action='admin.php' method='post'>
		"._AM_CLINAMET."<input type='text' name='name' size='30' maxlength='60' /><br />
		"._AM_CONTNAMET."<input type='text' name='contact' size='30' maxlength='60' /><br />
		"._AM_CONTMAILT."<input type='text' name='email' size='30' maxlength='60' /><br />
		"._AM_CLILOGINT."<input type='text' name='login' size='12' maxlength='10' /><br />
		"._AM_CLIPASST."<input type='text' name='passwd' size='12' maxlength='10' /><br />
		"._AM_EXTINFO."<br /><textarea name='extrainfo' cols='60' rows='10' /></textarea><br />
		<input type='hidden' name='op' value='BannerAddClient' />
		".$GLOBALS['xoopsSecurity']->getTokenHTML()."
		<input type='hidden' name='fct' value='banners' />
		<input type='submit' value='"._AM_ADDCLI."' />
		</form>";
		echo "</td></tr></table>";
		xoops_cp_footer();
	}
	
	/**
	 * Deletes a banner
	 * 
	 * @param int $bid banner id
	 */
	function BannerDelete($bid)
	{
	  global $xoopsConfig, $icmsModule;
	  $xoopsDB =& Database::getInstance();
	  $myts =& MyTextSanitizer::getInstance();
	  xoops_cp_header();
	  $result=$xoopsDB->query("SELECT cid, imptotal, impmade, clicks, imageurl, clickurl, htmlbanner, htmlcode FROM ".$xoopsDB->prefix("banner")." where bid='".intval($bid)."'");
	  list($cid, $imptotal, $impmade, $clicks, $imageurl, $clickurl, $htmlbanner, $htmlcode) = $xoopsDB->fetchRow($result);
	  $imageurl = htmlspecialchars($imageurl, ENT_QUOTES);
	  $clickurl = htmlspecialchars($clickurl, ENT_QUOTES);
	  echo"<table width='100%' border='0' cellspacing='1' class='outer'><tr><td class=\"odd\">";
	  echo "<h4>"._AM_DELEBNR."</h4>";
	  if ($htmlbanner){
		echo $myts->displayTarea($htmlcode,1);
	  }else{
			if(strtolower(substr($imageurl,strrpos($imageurl,".")))==".swf") {
				echo '<object type="application/x-shockwave-flash" data="'.$imageurl.'" width="468" height="60">';
				echo '<param name="movie" value="'.$imageurl.'" />';
				echo '<param name="quality" value="high" />';
				echo '</object>';
			} else {
				echo '<img src="'.$imageurl.'" alt="" />';
			}
	  }
	  echo "<a href='$clickurl'>$clickurl</a><br /><br /><table width='100%' border='0'><tr align='center'><td align='center'>"._AM_BANNERID."</td><td align='center'>"._AM_IMPRESION."</td><td align='center'>"._AM_IMPLEFT."</td><td align='center'>"._AM_CLICKS."</td><td align='center'>"._AM_NCLICKS."</td><td align='center'>"._AM_CLINAME."</td></tr><tr align='center'>";
	  $result2 = $xoopsDB->query("SELECT cid, name FROM ".$xoopsDB->prefix("bannerclient")." WHERE cid='".intval($cid)."'");
	  list($cid, $name) = $xoopsDB->fetchRow($result2);
	  $name = $myts->makeTboxData4Show($name);
	  $percent = substr(100 * $clicks / $impmade, 0, 5);
	  if ( $imptotal == 0 ) {
			$left = 'unlimited';
	  } else {
			$left = $imptotal-$impmade;
	  }
	  echo "
			<td align='center'>".icms_conv_nr2local($bid)."</td>
			<td align='center'>".icms_conv_nr2local($impmade)."</td>
			<td align='center'>".icms_conv_nr2local($left)."</td>
			<td align='center'>".icms_conv_nr2local($clicks)."</td>
			<td align='center'>".icms_conv_nr2local($percent)."%</td>
			<td align='center'>$name</td>
			</tr></table><br />";
	  xoops_confirm(array('fct' => 'banners', 'op' => 'BannerDelete2', 'bid' => $bid), 'admin.php', _AM_SUREDELE);
	  echo"</td></tr></table>";
	  xoops_cp_footer();
	}
	
	/**
	 * Edit the banner
	 * @param int $bid banner id
	 */
	function BannerEdit($bid)
	{
		global $xoopsConfig, $icmsModule;
		$bid = intval($bid);
		xoops_cp_header();
		$xoopsDB =& Database::getInstance();
		$myts =& MyTextSanitizer::getInstance();
		$result=$xoopsDB->query("SELECT cid, imptotal, impmade, clicks, imageurl, clickurl, htmlbanner, htmlcode FROM ".$xoopsDB->prefix("banner")." where bid='".intval($bid)."'");
		list($cid, $imptotal, $impmade, $clicks, $imageurl, $clickurl, $htmlbanner, $htmlcode) = $xoopsDB->fetchRow($result);
		echo"<table width='100%' border='0' cellspacing='1' class='outer'><tr><td class=\"odd\">";
		echo"<h4>"._AM_EDITBNR."</h4>";
		if ($htmlbanner){
			echo $myts->displayTarea($htmlcode, 1, 0, 0, 0, 0);
		}else{
			if(strtolower(substr($imageurl,strrpos($imageurl,".")))==".swf") {
				echo '<object type="application/x-shockwave-flash" data="'.$imageurl.'" width="468" height="60">';
				echo '<param name="movie" value="'.$imageurl.'" />';
				echo '<param name="quality" value="high" />';
				echo '</object>';
			} else {
				echo '<img src="'.$imageurl.'" alt="" />';
			}
		}
		echo "<form action='admin.php' method='post'>
		"._AM_CLINAMET."
		<select name='cid'>\n";
		$result = $xoopsDB->query("SELECT cid, name FROM ".$xoopsDB->prefix("bannerclient")." where cid='".intval($cid)."'");
		list($cid, $name) = $xoopsDB->fetchRow($result);
		$name = $myts->makeTboxData4Show($name);
		echo "<option value='$cid' selected='selected'>$name</option>";
		$result = $xoopsDB->query("SELECT cid, name FROM ".$xoopsDB->prefix("bannerclient"));
		while(list($ccid, $name) = $xoopsDB->fetchRow($result)) {
			$name = $myts->makeTboxData4Show($name);
			if ( $cid != $ccid ) {
				echo "<option value='$ccid'>$name</option>";
			}
		}
		echo "</select><br />";
		if ( $imptotal == 0 ) {
			$impressions = ""._AM_UNLIMIT."";
		} else {
			$impressions = $imptotal;
		}
		echo "
		"._AM_ADDIMPT."<input type='text' name='impadded' size='12' maxlength='11' /> "._AM_PURCHT."<b>$impressions</b> "._AM_MADET."<b>".icms_conv_nr2local($impmade)."</b><br />
		"._AM_IMGURLT."<input type='text' name='imageurl' size='50' maxlength='200' value='".htmlspecialchars($imageurl, ENT_QUOTES)."' /><br />
		"._AM_CLICKURLT."<input type='text' name='clickurl' size='50' maxlength='200' value='".htmlspecialchars($clickurl, ENT_QUOTES)."' /><br />
		"._AM_USEHTML;
		if ($htmlbanner){
			echo " <input type='checkbox' name='htmlbanner' value='1' checked='checked' />";
		}else{
			echo " <input type='checkbox' name='htmlbanner' value='1' />";
		}
		echo "
		<br />
		"._AM_CODEHTML."
		<br />
		<textarea name='htmlcode' rows='6' cols='60'>".$myts->displayTarea($htmlcode, $htmlbanner, 0, 0, 0, 0)."</textarea>
		<br />
		<input type='hidden' name='bid' value='$bid' />
		<input type='hidden' name='imptotal' value='$imptotal' />
		<input type='hidden' name='fct' value='banners' />
		".$GLOBALS['xoopsSecurity']->getTokenHTML()."
		<input type='hidden' name='op' value='BannerChange' />
		<input type='submit' value='"._AM_CHGBNR."' />
		</form>";
		echo"</td></tr></table>";
		xoops_cp_footer();
	}
	
	/**
	 * Deletes a client
	 * @param int $cid client id
	 */  
	function BannerClientDelete($cid)
	{
		global $xoopsConfig, $icmsModule;
		$xoopsDB =& Database::getInstance();
		$myts =& MyTextSanitizer::getInstance();
		xoops_cp_header();
		$result = $xoopsDB->query("SELECT cid, name FROM ".$xoopsDB->prefix("bannerclient")." WHERE cid='".intval($cid)."'");
		list($cid, $name) = $xoopsDB->fetchRow($result);
		$name = $myts->makeTboxData4Show($name);
		echo "<table width='100%' border='0' cellspacing='1' class='outer'><tr><td class=\"odd\">";
		echo "<h4>"._AM_DELEADC."</h4>".sprintf(_AM_SUREDELCLI,$name)."<br /><br />";
		$result2 = $xoopsDB->query("SELECT imageurl, clickurl, htmlbanner, htmlcode FROM ".$xoopsDB->prefix("banner")." WHERE cid='".intval($cid)."'");
		$numrows = $xoopsDB->getRowsNum($result2);
		if ( $numrows == 0 ) {
			echo ""._AM_NOBNRRUN."<br /><br />";
		} else {
			echo "<font color='#ff0000'><b>"._AM_WARNING."</b></font><br />"._AM_ACTBNRRUN."<br /><br />";
		}
		while(list($imageurl, $clickurl, $htmlbanner, $htmlcode) = $xoopsDB->fetchRow($result2)) {
			$imageurl = htmlspecialchars($imageurl, ENT_QUOTES);
			$clickurl = htmlspecialchars($clickurl, ENT_QUOTES);
			$bannerobject = "";
			if ($htmlbanner){
				$bannerobject = $myts->displayTarea($htmlcode,1);
			} else {
				$bannerobject = '<div><a href="'.$clickurl.'" rel="external">';
				if(strtolower(substr($imageurl,strrpos($imageurl,".")))==".swf") {
					$bannerobject = $bannerobject;
					echo '<object type="application/x-shockwave-flash" data="'.$imageurl.'" width="468" height="60">';
					echo '<param name="movie" value="'.$imageurl.'" />';
					echo '<param name="quality" value="high" />';
					echo '</object>';
				} else {
					$bannerobject = $bannerobject.'<img src="'.$imageurl.'" alt="" />';
				}
				$bannerobject = $bannerobject.'</a></div>';
			}
			echo $bannerobject."<a href='".$clickurl."'>".$clickurl."</a><br /><br />";
		}
		xoops_confirm(array('fct' => 'banners', 'op' => 'BannerClientDelete2', 'cid' => $cid), 'admin.php', _AM_SUREDELBNR);
		echo "</td></tr></table>";
		xoops_cp_footer();
	}
	
	/**
	 * Edits a client's information
	 * @param int $cid client id
	 */
	function BannerClientEdit($cid)
	{
		global $xoopsConfig, $icmsModule;
		$xoopsDB =& Database::getInstance();
		$myts =& MyTextSanitizer::getInstance();
		xoops_cp_header();
		$result = $xoopsDB->query("SELECT name, contact, email, login, passwd, extrainfo FROM ".$xoopsDB->prefix("bannerclient")." WHERE cid='".intval($cid)."'");
		list($name, $contact, $email, $login, $passwd, $extrainfo) = $xoopsDB->fetchRow($result);
		$name = $myts->makeTboxData4Edit($name);
		$contact = $myts->makeTboxData4Edit($contact);
		$email = $myts->makeTboxData4Edit($email);
		$login = $myts->makeTboxData4Edit($login);
		$passwd = $myts->makeTboxData4Edit($passwd);
		$extrainfo = $myts->makeTareaData4Edit($extrainfo);
		echo "<table width='100%' border='0' cellspacing='1' class='outer'><tr><td class=\"odd\">";
		echo "
		<h4>"._AM_EDITADVCLI."</h4>
		<form action='admin.php' method='post'>
		"._AM_CLINAMET."<input type='text' name='name' value='$name' size='30' maxlength='60' /><br />
		"._AM_CONTNAMET."<input type='text' name='contact' value='$contact' size='30' maxlength='60' /><br />
		"._AM_CONTMAILT ."<input type='text' name='email' size='30' maxlength='60' value='$email' /><br />
		"._AM_CLILOGINT."<input type='text' name='login' size='12' maxlength='10' value='$login' /><br />
		"._AM_CLIPASST."<input type='text' name='passwd' size='12' maxlength='10' value='$passwd' /><br />
		"._AM_EXTINFO."<br /><textarea name='extrainfo' cols='60' rows='10' />$extrainfo</textarea><br />
		<input type='hidden' name='cid' value='$cid' />
		<input type='hidden' name='op' value='BannerClientChange' />
		".$GLOBALS['xoopsSecurity']->getTokenHTML()."
		<input type='hidden' name='fct' value='banners' />
		<input type='submit' value='"._AM_CHGCLI."' />";
		echo "</td></tr></table>";
		xoops_cp_footer();
	}

}

?>