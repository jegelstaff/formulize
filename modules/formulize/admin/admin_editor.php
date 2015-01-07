<?php

$ele_name = $_GET['ele_name'];
$preg_array = explode('_', $ele_name);
$_GET = array();
$_GET['ele_id'] = array_pop($preg_array);
$_GET['page'] ='element';
$_GET['aid'] = 0;

// initialize the ImpressCMS admin page template
include_once("admin_header.php");
define('_FORMULIZE_UI_PHP_INCLUDED', 1);

// include necessary Formulize files/functions
include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";

global $xoopsTpl;

if(!isset($xoopsTpl)) {
  global $xoopsOption, $xoopsConfig, $xoopsModule;

  $xoopsOption['theme_use_smarty'] = 1;

  // include Smarty template engine and initialize it
  require_once XOOPS_ROOT_PATH . '/class/template.php';
  require_once XOOPS_ROOT_PATH . '/class/theme.php';
  require_once XOOPS_ROOT_PATH . '/class/theme_blocks.php';

  if ( @$xoopsOption['template_main'] ) {
    if ( false === strpos( $xoopsOption['template_main'], ':' ) ) {
      $xoopsOption['template_main'] = 'db:' . $xoopsOption['template_main'];
    }
  }
  $xoopsThemeFactory = new xos_opal_ThemeFactory();
  $xoopsThemeFactory->allowedThemes = $xoopsConfig['theme_set_allowed'];
  $xoopsThemeFactory->defaultTheme = $xoopsConfig['theme_set'];

  $xoTheme =& $xoopsThemeFactory->createInstance( array(
    'contentTemplate' => @$xoopsOption['template_main'],
  ) );
  $xoopsTpl =& $xoTheme->template;

  //$xoTheme->addScript( '/include/xoops.js', array( 'type' => 'text/javascript' ) );
  //$xoTheme->addScript( '/include/linkexternal.js', array( 'type' => 'text/javascript' ) );
}

// handle any operations requested as part of this page load
// sets up a template variable with the results of the op, called opResults
include_once "op.php";

// create the contents that we want to display for the currently selected page
// the included php files create the values for $adminPage that are used for this page
$adminPage = array();
switch($_GET['page']) {
  case "application":
    include "application.php"; 
    break;
  case "form":
    include "form.php";
    break;
  case "screen":
    include "screen.php";
    break;
  case "relationship":
    include "relationship.php";
    break;
  case "element":
    include "element.php";
    break;
  case "advanced-calculation":
    include "advanced_calculation.php";
    break;
  default:
  case "home":
    include "home.php";
    break;

}
//$adminPage['logo'] = "/modules/formulize/images/formulize-logo.png";

// assign the default selected tab, if any:
if(isset($_GET['tab']) AND (!isset($_POST['tabs_selected']) OR $_POST['tabs_selected'] === "")) {
  foreach($adminPage['tabs'] as $selected=>$tabData) {
    if(strtolower($tabData['name']) == $_GET['tab']) {
      $adminPage['tabselected'] = $selected-1;
      break;
    }
  }
} elseif($_POST['tabs_selected'] !== "") {
  $adminPage['tabselected']  = intval($_POST['tabs_selected']);
}


// assign the contents to the template and display
$adminPage['formulizeModId'] = getFormulizeModId();
$xoopsTpl->assign('adminPage', $adminPage);
$xoopsTpl->assign('breadcrumbtrail', $breadcrumbtrail);
$xoopsTpl->assign('scrollx', intval($_POST['scrollx']));
$accordion_active = (isset($_POST['accordion_active']) AND $_POST['accordion_active'] !== "" AND $_POST['accordion_active'] !== "false") ? intval($_POST['accordion_active']) : "false";
$xoopsTpl->assign('accordion_active', $accordion_active);
$xoopsTpl->display("db:admin/ui.html");
