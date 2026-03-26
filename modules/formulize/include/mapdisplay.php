<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2007 Freeform Solutions                  ##
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

if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/extract.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/entriesdisplay.php';

/**
 * Include a map screen template (top, map, or bottom), making the given
 * variables available in its scope.
 */
function formulize_screenMapTemplate($screen, $type, $vars) {
    foreach ($vars as $k => $v) { $$k = $v; }
    $path = getTemplatePath($screen ? $screen : 'map', $type . 'template');
    if ($path) {
        include $path;
    }
}

/**
 * Display form entries as interactive markers on a Leaflet.js map.
 *
 * @param int|string $frid  Form Relationship ID
 * @param int|string $fid   Form ID
 * @param formulizeMapScreen|null $screen  Map screen object with settings
 */
function displayMap($frid = 0, $fid = 0, $screen = null) {

		// --- 1. Resolve fid and frid from screen if not passed directly ---
    if (!$fid AND $screen) {
        $fid = $screen->getVar('fid');
    }
    if (!$frid AND $screen) {
        $frid = $screen->getVar('frid') ? $screen->getVar('frid') : -1;
    }

    // --- 2. Get screen settings ---
    $lat_element         = $screen ? $screen->getVar('lat_element') : '';
    $lng_element         = $screen ? $screen->getVar('lng_element') : '';
    $label_element       = $screen ? $screen->getVar('label_element') : '';
    $description_element = $screen ? $screen->getVar('description_element') : '';
    $viewentryscreen     = $screen ? $screen->getVar('viewentryscreen') : '';
    $columns             = ($screen AND is_array($screen->getVar('columns'))) ? $screen->getVar('columns') : array();

    // If no viewentryscreen is explicitly configured, fall back to the form's default form screen
    if (!$viewentryscreen OR !is_numeric($viewentryscreen) OR intval($viewentryscreen) <= 0) {
        $form_handler = xoops_getmodulehandler('forms', 'formulize');
        if ($formObject = $form_handler->get($fid)) {
            $viewentryscreen = $formObject->getVar('defaultform');
        }
    }

    // --- 3. Read saved map state from POST (filter reload / return from entry) or GET (back-navigation) ---
    $saved_map_lat  = null;
    $saved_map_lng  = null;
    $saved_map_zoom = null;
    if (isset($_POST['map_lat']) AND is_numeric($_POST['map_lat'])) {
        $saved_map_lat  = floatval($_POST['map_lat']);
        $saved_map_lng  = floatval($_POST['map_lng']);
        $saved_map_zoom = intval($_POST['map_zoom']);
    } elseif (isset($_GET['map_lat']) AND is_numeric($_GET['map_lat'])) {
        $saved_map_lat  = floatval($_GET['map_lat']);
        $saved_map_lng  = floatval($_GET['map_lng']);
        $saved_map_zoom = intval($_GET['map_zoom']);
    }

    // --- 4. Parse user-submitted search values from POST ---
    $searches = array();
    foreach ($columns as $col) {
        $handle = isset($col[0]) ? $col[0] : '';
        if (!$handle OR !is_string($handle)) {
            continue;
        }
			if(empty($_POST)) {
				if($searchTerm = isset($col[1]) ? $col[1] : '') {
					$searches[$handle] = $searchTerm;
				}
			} else {
        $postKey = 'search_' . $handle;
        if (isset($_POST[$postKey]) AND $_POST[$postKey] !== '') {
            $searches[$handle] = $_POST[$postKey];
				}
        }
    }

    // --- 5. Build settings array to carry map state through the view-entry round trip ---
    // writeHiddenSettings() will embed these as hidden fields in the view entry form so they
    // survive back to displayMap when the user returns.
    $settings = array();
    if ($saved_map_lat !== null) {
        $settings['map_lat']  = $saved_map_lat;
        $settings['map_lng']  = $saved_map_lng;
        $settings['map_zoom'] = $saved_map_zoom;
    }
    foreach ($searches as $handle => $value) {
        $settings['search_' . $handle] = $value;
    }

    // --- 2b. Jump to view entry screen if user clicked "View Entry" in a popup ---
    if (isset($_POST['ventry']) AND intval($_POST['ventry']) > 0) {
        $ventry = intval($_POST['ventry']);
        if ($viewentryscreen AND is_numeric($viewentryscreen) AND intval($viewentryscreen) > 0) {
            $screen_handler_base = xoops_getmodulehandler('screen', 'formulize');
            $viewEntryScreenObject = $screen_handler_base->get(intval($viewentryscreen));
            if ($viewEntryScreenObject) {
                $viewEntryScreen_handler = xoops_getmodulehandler($viewEntryScreenObject->getVar('type') . 'Screen', 'formulize');
                $displayScreen = $viewEntryScreen_handler->get($viewEntryScreenObject->getVar('sid'));
                $viewEntryScreen_handler->render($displayScreen, $ventry, $settings);
                return;
            }
        }
        // Fallback: no screen configured, display form directly
        include_once XOOPS_ROOT_PATH . '/modules/formulize/include/formdisplay.php';
        displayForm($fid, $ventry, '', '', '', $settings);
        return;
    }

    // --- 5. Build filter from searches, then merge with fundamental_filters ---
    $filter = formulize_parseSearchesIntoFilter($searches);
    if ($screen) {
        $fundamental_filters = $screen->getVar('fundamental_filters');
        if (is_array($fundamental_filters) AND count($fundamental_filters) > 0) {
            $filter = array('fundamental_filters' => $fundamental_filters, 'active_filters' => $filter);
        }
    }

    // --- 6. Fetch entries using gatherDataset (frid:0 = main form only, no relationships) ---
    $data = gatherDataset($fid, filter: $filter, frid: 0);

    // --- 7. Build entry data for map (server-side, as JSON) ---
    $map_entries = array();
    foreach ($data as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $lat = getValue($entry, $lat_element, raw: true);
        $lng = getValue($entry, $lng_element, raw: true);

        // Skip entries without valid, non-zero coordinates
        if ($lat === '' OR $lat === null OR $lng === '' OR $lng === null OR !is_numeric($lat) OR !is_numeric($lng) OR (floatval($lat) == 0 AND floatval($lng) == 0)) {
            continue;
        }

        $entry_ids = getEntryIds($entry, $fid);
        $entry_id  = isset($entry_ids[0]) ? $entry_ids[0] : 0;
        $label     = strip_tags((string) getValue($entry, $label_element));

        // Build popup HTML: label heading, optional description paragraph, optional View Entry link
        $popup_html = '<h3>' . htmlspecialchars($label, ENT_QUOTES) . '</h3>';
        if ($description_element AND $desc = displayPara($entry, $description_element)) {
                $popup_html .= '<p>' . nl2br(htmlspecialchars($desc, ENT_QUOTES)) . '</p>';
        }
        if ($viewentryscreen AND is_numeric($viewentryscreen) AND intval($viewentryscreen) > 0) {
            $popup_html .= '<p><a class="formulize-map-view-entry" href="#" onclick="formulizeMapViewEntry(' . intval($entry_id) . '); return false;">View Entry</a></p>';
        }

        $map_entries[] = array(
            'entry_id'   => intval($entry_id),
            'lat'        => floatval($lat),
            'lng'        => floatval($lng),
            'label'      => $label,
            'popup_html' => $popup_html,
        );
    }

    // --- 8. Build $filters array: heading (htmlspecialchars'd) => input markup ---
    $filters = array();
    if (!empty($columns)) {
        $handles = array();
        foreach ($columns as $col) {
            $handle = isset($col[0]) ? $col[0] : '';
            if ($handle AND is_string($handle)) {
                $handles[] = $handle;
            }
        }
        $raw_headings = getHeaders($handles); // handle => raw heading text

        foreach ($columns as $col) {
            $handle      = isset($col[0]) ? $col[0] : '';
            $search_type = isset($col[2]) ? $col[2] : 'Box';
            if (!$handle OR !is_string($handle)) {
                continue;
            }
            $heading     = htmlspecialchars(isset($raw_headings[$handle]) ? $raw_headings[$handle] : $handle, ENT_QUOTES);
            $current_val = isset($searches[$handle]) ? $searches[$handle] : '';
            switch ($search_type) {
                case 'Filter':
                    $input = formulize_buildQSFilter($handle, $current_val);
                    break;
                case 'NegativeFilter':
                    $input = formulize_buildQSFilter($handle, $current_val, false, true);
                    break;
                case 'MultiFilter':
                    $input = formulize_buildQSFilterMulti($handle, $current_val);
                    break;
                case 'DateRange':
                    $input = formulize_buildDateRangeFilter($handle, $current_val);
                    break;
                case 'Box':
                default:
                    $input = '<input type="text" name="search_' . htmlspecialchars($handle, ENT_QUOTES) . '" value="' . htmlspecialchars($current_val, ENT_QUOTES) . '">';
                    break;
            }
            $filters[$heading] = $input;
        }
    }

    // --- 9. Prepare form action URL and saved map state for the template ---
    $current_url = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES);

    // --- 10. Build $renderedMap string (map container + JS only) ---
    // Leaflet assets are output directly below, outside $renderedMap, so they are
    // siblings of the map wrapper div rather than nested inside it.  Having <link>
    // and <script src> inside a block div causes browsers to recompute that div's
    // layout, producing an incorrect offsetWidth when Leaflet reads it at init time.
    $map_id       = 'formulize-map-' . intval($fid) . '-' . ($screen ? intval($screen->getVar('sid')) : '0');
    $entries_json = json_encode(array_values($map_entries));
    $js_saved_lat  = ($saved_map_lat  !== null) ? $saved_map_lat  : 'null';
    $js_saved_lng  = ($saved_map_lng  !== null) ? $saved_map_lng  : 'null';
    $js_saved_zoom = ($saved_map_zoom !== null) ? $saved_map_zoom : 'null';
    $js_var_name   = preg_replace('/[^a-zA-Z0-9_]/', '_', $map_id);

    // $renderedMap is just the map markup; JS is output directly as page infrastructure
    $renderedMap = '<div id="' . htmlspecialchars($map_id, ENT_QUOTES) . '" style="height:600px;width:100%;"></div>' . "\n";

    // --- 11. Screen title and filter button text ---
    $title = $screen ? $screen->getVar('title') : '';
    $filter_button_text = $screen ? htmlspecialchars((string) $screen->getVar('filter_button_text'), ENT_QUOTES) : '';

    // --- 12. Render: Leaflet assets, then form wrapper around toptemplate, then map template ---
    // The form is always output (even with no filters) so that hidden map state fields
    // (lat/lng/zoom) are always available for restoring position after navigating to an entry.
    echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin="">' . "\n";
    echo '<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>' . "\n";
    echo '<form method="post" action="' . $current_url . '" class="formulize-map-filter-form">' . "\n";
    echo '<input type="hidden" name="map_lat"  class="formulize-map-lat"  value="' . ($saved_map_lat  !== null ? $saved_map_lat  : '') . '">' . "\n";
    echo '<input type="hidden" name="map_lng"  class="formulize-map-lng"  value="' . ($saved_map_lng  !== null ? $saved_map_lng  : '') . '">' . "\n";
    echo '<input type="hidden" name="map_zoom" class="formulize-map-zoom" value="' . ($saved_map_zoom !== null ? $saved_map_zoom : '') . '">' . "\n";
    echo '<input type="hidden" name="ventry"   class="formulize-map-ventry" value="">' . "\n";
    formulize_screenMapTemplate($screen, 'top', array(
        'title'              => $title,
        'filters'            => $filters,
        'filter_button_text' => $filter_button_text,
    ));
    echo '</form>' . "\n";
    formulize_screenMapTemplate($screen, 'map',    array('renderedMap' => $renderedMap));
    formulize_screenMapTemplate($screen, 'bottom', array());
    echo '<script>' . "\n";
    echo '(function() {' . "\n";
    echo '  var form = document.querySelector(".formulize-map-filter-form");' . "\n";
    echo '  if (form) {' . "\n";
    echo '    var selects = form.querySelectorAll("select");' . "\n";
    echo '    for (var i = 0; i < selects.length; i++) {' . "\n";
    echo '      selects[i].addEventListener("change", function() { form.submit(); });' . "\n";
    echo '    }' . "\n";
    echo '  }' . "\n";
    echo '})();' . "\n";
    echo 'var ' . $js_var_name . ' = ' . $entries_json . ';' . "\n";
    echo '(function() {' . "\n";
    echo '  var entries = ' . $js_var_name . ';' . "\n";
    echo '  var savedLat  = ' . $js_saved_lat  . ';' . "\n";
    echo '  var savedLng  = ' . $js_saved_lng  . ';' . "\n";
    echo '  var savedZoom = ' . $js_saved_zoom . ';' . "\n";
    echo '  window.addEventListener("formulize_pageShown", function() {' . "\n";
    echo '    var map = L.map(' . json_encode($map_id) . ');' . "\n";
    echo '    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {' . "\n";
    echo '      attribution: \'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors\',' . "\n";
    echo '      maxZoom: 19' . "\n";
    echo '    }).addTo(map);' . "\n";
    echo '    var bounds = L.latLngBounds();' . "\n";
    echo '    for (var i = 0; i < entries.length; i++) {' . "\n";
    echo '      var e = entries[i];' . "\n";
    echo '      var marker = L.marker([e.lat, e.lng], {title: e.label});' . "\n";
    echo '      marker.bindPopup(e.popup_html);' . "\n";
    echo '      marker.addTo(map);' . "\n";
    echo '      bounds.extend([e.lat, e.lng]);' . "\n";
    echo '    }' . "\n";
    echo '    if (savedLat !== null && savedZoom !== null) {' . "\n";
    echo '      map.setView([savedLat, savedLng], savedZoom);' . "\n";
    echo '    } else if (bounds.isValid()) {' . "\n";
    echo '      map.fitBounds(bounds, {padding: [40, 40], maxZoom: 14});' . "\n";
    echo '    } else {' . "\n";
    echo '      map.setView([0, 0], 2);' . "\n";
    echo '    }' . "\n";
    echo '    map.on("moveend zoomend", function() {' . "\n";
    echo '      var c = map.getCenter();' . "\n";
    echo '      var z = map.getZoom();' . "\n";
    echo '      var form = document.querySelector(".formulize-map-filter-form");' . "\n";
    echo '      if (form) {' . "\n";
    echo '        form.querySelector(".formulize-map-lat").value  = c.lat;' . "\n";
    echo '        form.querySelector(".formulize-map-lng").value  = c.lng;' . "\n";
    echo '        form.querySelector(".formulize-map-zoom").value = z;' . "\n";
    echo '      }' . "\n";
    echo '    });' . "\n";
    echo '  });' . "\n";
    echo '})();' . "\n";
    echo 'function formulizeMapViewEntry(entry_id) {' . "\n";
    echo '  var form = document.querySelector(".formulize-map-filter-form");' . "\n";
    echo '  if (form) {' . "\n";
    echo '    form.querySelector(".formulize-map-ventry").value = entry_id;' . "\n";
    echo '    form.submit();' . "\n";
    echo '  }' . "\n";
    echo '}' . "\n";
    echo '</script>' . "\n";
}
