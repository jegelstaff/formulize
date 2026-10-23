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
include_once XOOPS_ROOT_PATH.'/modules/formulize/include/popup_functions.php';


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

if( $_GET['advcalc_acid'] ) {
  formulize_addProcedureChoicesToPost(strip_tags(htmlspecialchars($_GET['advcalc_acid'])));
  $acid = $_POST['acid'];
} else {
  $acid = null;
}

if(!$acid AND $_GET['acid']) {
	$acid = intval($_GET['acid']);
}



$gperm_handler = &xoops_gethandler('groupperm');
$member_handler = &xoops_gethandler('member');
$groups = $xoopsUser ? $xoopsUser->getGroups() : array(0=>XOOPS_GROUP_ANONYMOUS);
$uid = $xoopsUser ? $xoopsUser->getVar('uid') : 0;

if( !$scheck = security_check( $fid, "", $uid, "", $groups, $mid, $gperm_handler ) ) {
	print formulize_noPermissionMessage();
	exit;
}


// build advanced calculation list
$advanced_calculation_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
$list = $advanced_calculation_handler->getList($fid);

ob_start();
?>
    <script type="text/javascript" src="<?php print XOOPS_URL ?>/include/xoops.js"></script>
<?php
	include_once XOOPS_ROOT_PATH."/include/calendarjs.php";
?>
    <script>
<?php
  if( $acid ) {
    print "current_acid = " . intval($acid) . ";\n";
  } else {
    print "current_acid = null;\n";
  }
?>
    function selectAdvCalc( acid ) {
      if( current_acid == null || current_acid != acid ) {
        window.location = window.location + "&acid=" + acid;
      }
    }

    function selectAdvCalc2( acid ) {
      var output = "acid=" + acid;

      var form = document.getElementById( "procedure_" + acid );
      var form_inputs = form.getElementsByTagName( "input" );
      var form_selects = form.getElementsByTagName( "select" );

      index = 0;
      while(form_inputs[index] != null) {
        var form_input = form_inputs[ index ];
        if( form_input.type == "text" || ( form_input.type == "checkbox" && form_input.checked ) ) {
          //alert( form_input.id + " = " + form_input.value );
          output += "&" + form_input.id + "=" + encodeURI( form_input.value )
        }
	index++;
      }

      index = 0
      while(form_selects[index] != null ) {
        var form_select = form_selects[ index ];
        if( form_select.id ) {
          var option = form_select.options[ form_select.selectedIndex ].value;
          //alert( form_select.id + " = " + option );
          output += "&" + form_select.id + "=" + encodeURI( option )
        }
	index++;
      }

      //alert( output );

      window.opener.document.controls.advcalc_acid.value = output;
	    window.opener.showLoading();
	    window.self.close();
    }
    </script>
<?php
formulize_popupHeader(_formulize_DE_PICKCALCS, ob_get_clean());

// each procedure is a card. Clicking one picks it, and the one that is picked shows its options.
print "<h1 class='fz-text-lg fz-font-semibold'>" . _formulize_DE_PICKCALCS . "</h1>\n";
print "<ul class='fz-card-list'>\n";
foreach( $list as $index => $value ) {
  if( $acid == $value['acid'] ) {
    $procedures_handler = xoops_getmodulehandler('advancedCalculation', 'formulize');
    $allFiltersAndGroupings = $procedures_handler->getAllFiltersAndGroupings($acid);
    print "<li class='fz-card'>\n";
    print "<h2 class='fz-card__title'>" . $value['name'] . "</h2>\n";
    print "<p class='fz-text-muted'>" . $value['description'] . "</p>\n";
    // the controls of the filters and groupings are made by the procedure itself, so they have the theme's own look
    print "<form id='procedure_" . intval($acid) . "' class='fz-stack'>\n";
    print "<div class='fz-switcher'>\n";
    if( count((array) $allFiltersAndGroupings["filters"]) > 0 ) {
      print "<div class='fz-stack fz-gap-3'>\n";
      foreach($allFiltersAndGroupings["filters"] as $thisFilter) {
        print formulize_popupField($thisFilter['label'], $thisFilter['html'], '', $thisFilter['id']);
      }
      print "</div>\n";
    }
    if( count((array) $allFiltersAndGroupings["groupings"]) > 0 ) {
      print "<div class='fz-choice-group'>\n";
      foreach($allFiltersAndGroupings["groupings"] as $thisGrouping) {
        print "<label class='fz-choice'>" . $thisGrouping['html'] . " " . $thisGrouping['label'] . "</label>\n";
      }
      print "</div>\n";
    }
    print "</div>\n";
    print "<div class='fz-cluster'><input type='button' class='fz-btn fz-btn--primary' value='Go' onclick='selectAdvCalc2( " . intval($acid) . " );' /></div>\n";
    print "</form>\n";
    print "</li>\n";
  } else {
    print "<li class='fz-card fz-card--interactive'>\n";
    print "<h2 class='fz-card__title'><a class='fz-card__link' href='" . htmlspecialchars($_SERVER['REQUEST_URI'] . "&acid=" . intval($value['acid'])) . "' onclick='selectAdvCalc(" . intval($value['acid']) . ");return false;'>" . $value['name'] . "</a></h2>\n";
    print "<p class='fz-text-muted'>" . $value['description'] . "</p>\n";
    print "</li>\n";
  }
}
print "</ul>\n";

formulize_popupFooter();
