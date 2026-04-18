<?php
###############################################################################
##     Formulize - configurable data management, reporting, and workflows    ##
##                Copyright (c) 2026 The Formulize Project                   ##
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
##  Author of this file: The Formulize Project                               ##
##  URL: http://www.formulize.org                                            ##
###############################################################################

// this file handles saving of submissions from the screen_map page of the new admin UI

// if we aren't coming from what appears to be save.php, then return nothing
if (!isset($processedValues)) {
    return;
}

$sid = $_POST['formulize_admin_key'];

$screen_handler = xoops_getmodulehandler('mapScreen', 'formulize');
$screen = $screen_handler->get($sid);

// check if the user has permission to edit the form
if (!$gperm_handler->checkRight("edit_form", $screen->getVar('fid'), $groups, $mid)) {
    return;
}

// Build the columns (filter) array from POST table rows
$columns = array();
if (isset($_POST['col-value']) AND is_array($_POST['col-value'])) {
    foreach ($_POST['col-value'] as $index => $col) {
        if (!is_numeric($col) OR intval($col) != 0) {
            $columns[] = array(
                $col,
                isset($_POST['search-value'][$index]) ? $_POST['search-value'][$index] : '',
                isset($_POST['search-type'][$index]) ? $_POST['search-type'][$index] : 'Box',
            );
        }
    }
}

$screen->setVar('lat_element', isset($_POST['screens-lat_element']) ? $_POST['screens-lat_element'] : '');
$screen->setVar('lng_element', isset($_POST['screens-lng_element']) ? $_POST['screens-lng_element'] : '');
$screen->setVar('label_element', isset($_POST['screens-label_element']) ? $_POST['screens-label_element'] : '');
$screen->setVar('description_element', isset($_POST['screens-description_element']) ? $_POST['screens-description_element'] : '');
$screen->setVar('viewentryscreen', isset($_POST['screens-viewentryscreen']) ? $_POST['screens-viewentryscreen'] : '');
$screen->setVar('show_view_entry_link', (isset($_POST['screens-show_view_entry_link']) AND intval($_POST['screens-show_view_entry_link']) === 0) ? 0 : 1);
$screen->setVar('filter_button_text', isset($_POST['screens-filter_button_text']) ? $_POST['screens-filter_button_text'] : '');
$screen->setVar('tileset', isset($_POST['screens-tileset']) ? $_POST['screens-tileset'] : 'osm');
$screen->setVar('tileset_url', isset($_POST['screens-tileset_url']) ? $_POST['screens-tileset_url'] : '');
$screen->setVar('tileset_key', isset($_POST['screens-tileset_key']) ? $_POST['screens-tileset_key'] : '');
$screen->setVar('tileset_attribution', isset($_POST['screens-tileset_attribution']) ? $_POST['screens-tileset_attribution'] : '');
$screen->setVar('columns', serialize($columns));

list($parsedFundamentalFilters, $_POST['reload_map_screen_page']) = parseSubmittedConditions('fundamentalfilters', 'ffdelete');
$screen->setVar('fundamental_filters', serialize($parsedFundamentalFilters));

if (!$screen_handler->insert($screen)) {
    print "Error: could not save the map screen properly: " . $xoopsDB->error();
}

if (isset($_POST['reload_map_screen_page']) AND $_POST['reload_map_screen_page']) {
    print "/* evalnow */ if(redirect=='') { redirect = 'reloadWithScrollPosition();'; }";
}
