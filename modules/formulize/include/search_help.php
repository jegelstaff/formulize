<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2004 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
###############################################################################
##  Author of this file: Freeform Solutions                                  ##
##  Project: Formulize                                                       ##
###############################################################################

// The search cheat sheet: the popup opened by the ? in the search row of a list of entries.
//
// What it says is in search_help.html in each language folder, as Formulize UI markup.
// It is too long to be a set of constants, and its examples differ from one language
// to another, so each language has its own copy. A language without one gets the English.
// This page draws it in a popup page, so it looks like the rest of the site in whichever
// theme is in use.

require_once "../../../mainfile.php";

global $xoopsConfig;
include_once XOOPS_ROOT_PATH . "/modules/formulize/language/english/main.php";
if (file_exists(XOOPS_ROOT_PATH . "/modules/formulize/language/" . $xoopsConfig['language'] . "/main.php")) {
    include_once XOOPS_ROOT_PATH . "/modules/formulize/language/" . $xoopsConfig['language'] . "/main.php";
}

include_once XOOPS_ROOT_PATH . "/modules/formulize/include/popup_functions.php";

// the language comes from the site's settings, never from the request, so it can only name one of the site's own folders
$helpFile = XOOPS_ROOT_PATH . "/modules/formulize/language/" . basename($xoopsConfig['language']) . "/search_help.html";
if (!file_exists($helpFile)) {
    $helpFile = XOOPS_ROOT_PATH . "/modules/formulize/language/english/search_help.html";
}

formulize_popupHeader(_formulize_DE_SEARCH_HELP_TITLE);
readfile($helpFile);
formulize_popupFooter();
