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

// What the popup windows of the list of entries screens have in common: the page
// they are drawn in, and the Formulize UI markup of their fields and controls.
// Used by changecols.php, changescope.php, pickcalcs.php, pickadvcalcs.php, save.php, setnot.php,
// export.php, import.php and moreinfo.php.
//
// A popup is a page of its own, opened with showPop(), which no theme draws. So it
// has to ask for what a theme's page would give it: the Formulize UI stylesheet,
// the theme's stylesheet, and the site's Appearance settings. That is done once,
// here, so each popup looks like the rest of the site in whichever theme is in use.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/appearance.php';

/**
 * Print the start of a popup's page: the head, and the opening of the body.
 * Everything the popup prints after this is one item in a stack, so a popup is
 * made of cards one below the other. End the page with formulize_popupFooter().
 *
 * @param string $title the page title, shown in the window's title bar
 * @param string $headContent HTML to add to the head, such as the popup's own script
 * @return void
 */
function formulize_popupHeader($title, $headContent = '') {
    $themeCss = xoops_getcss();
    $themeCssVersion = formulize_get_file_version(str_replace(XOOPS_URL, '', $themeCss));
    print "<!DOCTYPE html>\n";
    print "<html lang=\"" . _LANGCODE . "\">\n";
    print "<head>\n";
    print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . _CHARSET . "\" />\n";
    print "<meta name=\"viewport\" content=\"width=device-width, minimum-scale=1, initial-scale=1\" />\n";
    print "<title>" . $title . "</title>\n";
    // the same order as on a page the theme draws: Formulize UI, the theme, then the Appearance settings
    print "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"" . XOOPS_URL . formulize_uiStylesheetPath() . "\" />\n";
    print "<link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"" . $themeCss . "?v=" . $themeCssVersion . "\" />\n";
    print formulize_renderAppearanceHead();
    print $headContent;
    print "</head>\n";
    // fz-p-5 is the space at the edges of the page in a theme that has no rule of its own for body.formulize-popup.
    // fz-m-0 takes off the margin a browser gives the body, which a theme's reset would, when the popup loaded it.
    print "<body class=\"formulize-popup fz-m-0 fz-p-5\">\n";
    // fz-text-sm is the size of the text in the controls, so the text around them matches whatever size the theme's own text is
    print "<main class=\"fz-stack fz-mx-auto fz-text-sm\" style=\"max-width: var(--fz-container-6xl);\">\n";
}

/**
 * Print the end of a popup's page.
 *
 * @return void
 */
function formulize_popupFooter() {
    print "</main>\n";
    print "</body>\n";
    print "</html>\n";
}

/**
 * One field of a popup: a label, with its control or controls below it.
 *
 * @param string $label the label, as HTML
 * @param string $body the control or controls, as HTML
 * @param string $help help text, as HTML
 * @param string $controlId the id of the control the label is for. Leave it out for a group of controls, which is drawn as a fieldset with the label as its legend.
 * @return string HTML
 */
function formulize_popupField($label, $body, $help = '', $controlId = '') {
    $help = $help ? "<p class='fz-field__help'>" . $help . "</p>" : "";
    if ($controlId) {
        return "<div class='fz-field'>\n"
            . "<label class='fz-field__label' for='" . $controlId . "'>" . $label . "</label>\n"
            . "<div class='fz-field__body'>" . $body . $help . "</div>\n"
            . "</div>\n";
    }
    return "<fieldset class='fz-field'>\n"
        . "<legend class='fz-field__label'>" . $label . "</legend>\n"
        . "<div class='fz-field__body'>" . $body . $help . "</div>\n"
        . "</fieldset>\n";
}

/**
 * A radio button or a checkbox, with its label.
 *
 * @param string $type 'radio' or 'checkbox'
 * @param string $name the name of the input
 * @param string $value the value of the input
 * @param string $label the label, as HTML
 * @param bool $checked whether it starts out chosen
 * @param string $attributes other attributes for the input, as HTML
 * @return string HTML
 */
function formulize_popupChoice($type, $name, $value, $label, $checked = false, $attributes = '') {
    return "<label class='fz-choice'><input type='" . $type . "' class='fz-" . $type . "' name='" . $name . "' value='" . htmlspecialchars($value) . "'"
        . ($checked ? " checked='checked'" : "") . ($attributes ? " " . $attributes : "") . " /> " . $label . "</label>\n";
}

/**
 * A dropdown list, or a listbox when it has a size or takes several choices.
 *
 * @param string $name the name of the select, which is also its id (without any [] on the end)
 * @param array $options the options, value => label, the labels as HTML
 * @param mixed $selected the value chosen, or an array of them
 * @param string $attributes other attributes for the select, as HTML
 * @param string $classes other classes for the select, such as fz-w-auto for one that is only as wide as its options
 * @return string HTML
 */
function formulize_popupSelect($name, $options, $selected = array(), $attributes = '', $classes = '') {
    $selected = array_map('strval', (array) $selected);
    $html = "<select class='fz-select" . ($classes ? " " . $classes : "") . "' name='" . $name . "' id='" . str_replace('[]', '', $name) . "'" . ($attributes ? " " . $attributes : "") . ">\n";
    foreach ((array) $options as $value => $label) {
        $html .= "<option value='" . htmlspecialchars($value) . "'" . (in_array(strval($value), $selected, true) ? " selected='selected'" : "") . ">" . $label . "</option>\n";
    }
    $html .= "</select>";
    return $html;
}
