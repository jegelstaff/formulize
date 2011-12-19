<?php
/**
* Functions related to new module.
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version		$Id: pda.php 8662 2009-05-01 09:04:30Z pesianstranger $
*/

include "mainfile.php";
header("Content-Type: text/html");

echo "<html><head><title>". htmlspecialchars($icmsConfig['sitename'])."</title>
      <meta name='HandheldFriendly' content='True' />
      <meta name='PalmComputingPlatform' content='True' />
      </head>
      <body>";

$sql = "SELECT storyid, title FROM ".$xoopsDB->prefix("stories")." WHERE published>'0' AND published<'".time()."' ORDER BY published DESC";

$result = $xoopsDB->query($sql,10,0);

if (!$result) {
    echo "An error occured";
} else {
    echo "<img src='images/logo.gif' alt='".htmlspecialchars($icmsConfig['sitename'], ENT_QUOTES)."' border='0' /><br />";
    echo "<h2>".htmlspecialchars($icmsConfig['slogan'])."</h2>";
    echo "<div>";
    while (list($storyid, $title) = $xoopsDB->fetchRow($result)) {
        echo "<a href='".ICMS_URL."/modules/news/print.php?storyid=$storyid'>".htmlspecialchars($title)."</a><br />";

    }
    echo "</div>";
}

echo "</body></html>";

?>