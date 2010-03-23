<?php

$cache = strstr($_GET['cache'], ".") ? "" : $_GET['cache']; // don't allow inclusion of badly formed cache filenames, could be hacking attempt
$term = $_GET['q'];
$found = array();

include "../../../cache/".$cache;

if(count($found) == 0) {
  include_once "../../../mainfile.php";
  global $xoopsConfig;
  if ( file_exists("../language/".$xoopsConfig['language']."/main.php") ) {
  	include_once "../language/".$xoopsConfig['language']."/main.php";
  } else {
  	include_once "../language/english/main.php";
  }
  $found[]='["'._formulize_NO_MATCH_FOUND.'","none"]';
}

print "[".implode(",", $found)."]";

