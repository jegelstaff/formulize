<?php
###############################################################################
##            Formulize - ad hoc form creation and reporting module          ##
##                    Copyright (c) 2008 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions 					                       ##
##  Project: Formulize                                                       ##
###############################################################################

include_once "../../../mainfile.php";

// get the info for this element
// format it onto the screen
// we're interested in the full caption and any options that there might be

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php";

// load the formulize language constants if they haven't been loaded already
include_once XOOPS_ROOT_PATH."/modules/formulize/language/english/main.php";
if ( file_exists(XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php") ) {
  include_once XOOPS_ROOT_PATH."/modules/formulize/language/".$xoopsConfig['language']."/main.php";
} 

$elementMetaData = formulize_getElementMetaData($_GET['col'], true);

print "<HTML>";
print "<head>";
print "<title>" . _formulize_DE_MOREINFO_TITLE . "</title>\n";
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"" . XOOPS_URL . "/xoops.css\" />\n";
$themecss = xoops_getcss();
print "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"$themecss\" />\n";
print "</head>";
print "<body><center>"; 
print "<table width=100%><tr><td width=5%></td><td width=90%>";
?>
<br><br>
<table class="outer">
  <tr><td class="head"><center><p>
  <?php print _formulize_DE_MOREINFO_QUESTION."<br>".trans($elementMetaData['ele_caption']); ?>
  </p><center></td></tr>
  <?php
  $ele_value=unserialize($elementMetaData['ele_value']);
  $ele_uitext=unserialize($elementMetaData['ele_uitext']);
  switch($elementMetaData['ele_type']) {
    case "radio":
      print "<tr><td class=\"odd\"><p><b>"._formulize_DE_MOREINFO_OPTIONS."</b></p>\n";
      print "<ul>\n";
      foreach($ele_value as $option=>$selected) {
        $optionText = isset($ele_uitext[$option]) ? trans($option) ." &mdash; ".trans($ele_uitext[$option]) : trans($option);
        print "<li>$optionText</li>\n";
      }
      print "</ul></td></tr>\n";
  }
print "</table>\n";

print "</td><td width=5%></td></tr></table>";
print "</center></body>";
print "</HTML>";
?>