<?php

$cache = strstr($_GET['cache'], ".") ? "" : $_GET['cache']; // don't allow inclusion of badly formed cache filenames, could be hacking attempt
$term = $_GET['q'];
$found = array();

if(file_exists("../../../cache/".$cache)) {
  include "../../../cache/".$cache;  
} else {
  include "../../../".$cache; // if the file doesn't exist in the cache folder, then look in the root of the system
}

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

