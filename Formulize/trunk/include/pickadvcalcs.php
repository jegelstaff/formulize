<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################


require_once "../../../mainfile.php";

global $xoopsConfig, $xoopsDB, $xoopsUser;


// load the formulize language constants if they haven't been loaded already
if( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
	include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
} else {
	include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
}

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';


// Set some required variables
$mid = getFormulizeModId();
$fid = "";
if( !$fid = $_GET['fid'] ) {
	$fid = intval($_POST['fid']);
}
$frid = "";
if( !$frid = $_GET['frid'] ) {
	$frid = intval($_POST['frid']);	
}

$gperm_handler = &xoops_gethandler('groupperm');
$member_handler = &xoops_gethandler('member');
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

if( !$scheck = security_check( $fid, "", $uid, "", $groups, $mid, $gperm_handler ) ) {
	print "<p>" . _NO_PERM . "</p>";
	exit;
}


// build advanced calculation list
$advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
$list = $advanced_calculation_handler->getList($fid);
?>
<html>
  <head>
    <title><?php print _formulize_DE_PICKCALCS; ?></title>

    <link rel="stylesheet" type="text/css" media="screen" href="<?php print XOOPS_URL ?>/xoops.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="<?php print xoops_getcss() ?>" />

    <script>
    function selectAdvCalc( acid ) {
      window.opener.document.controls.advcalc_acid.value = acid;
      window.opener.document.controls.hlist.value = 1;
      window.opener.document.controls.hcalc.value = 0;
      window.opener.showLoading();
      window.self.close();
    }
    </script>
  </head>
  <body>
    <center>
      <table>
<?php
foreach( $list as $index => $value ) {
?>
        <tr>
          <td class="<?php print ( ( $index %2 ) ? 'even' : 'odd' ); ?>" style="border-bottom: 1px solid black; cursor: pointer" onclick="selectAdvCalc(<?php print $value['acid']; ?>)">
            <strong><?php print $value['name']; ?></strong>
            <br/>
            <?php print $value['description']; ?>
          </td>
        </tr>
<?php
}
?>
      </table>
    </center>
  </body>
</html>
