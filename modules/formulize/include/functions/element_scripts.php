<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################

// The JavaScript that form elements rely on, wherever they're drawn: a full screen form, a list, or the drawer.
//
// Part of functions.php, which requires this file at its end. It is a file of its own only to keep
// functions.php to a size that can be worked with: the functions are global, and are available wherever
// functions.php is.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

/**
 * The scripts that elements' markup calls. Every page that draws elements prints them: a full screen form
 * (drawJavascript() in formdisplay.php), a list, for editing in the list (interfaceJavascript() in
 * entriesdisplay.php), and the drawer, with each form it shows (formdisplay-elementsonly.php). So an element
 * works the same whichever of these drew it, and whatever page the drawer is over. A page can print them more
 * than once, harmlessly, which happens when the drawer is over a form or a list.
 *
 * - autocomplete.js, for autocomplete lists
 * - check_date_limits(), which a date element with an earliest or latest date calls (dateElement.php), with
 *   its words from the language file, the constants starting _formulize_JS_DATE_LIMIT_
 *
 * @return string HTML: the script tags
 */
function formulize_elementScripts() {
	$dateLimitText = json_encode(formulize_scriptText('_formulize_JS_DATE_LIMIT_'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	return "<script type='text/javascript' src='".XOOPS_URL.formulize_textScriptPath()."'></script>\n"
		."<script type='text/javascript' src='".XOOPS_URL."/modules/formulize/include/js/autocomplete.js?v=".formulize_get_file_version('/modules/formulize/include/js/autocomplete.js')."'></script>\n"
		."<script type='text/javascript'>\nwindow.formulizeDateLimitText = $dateLimitText;\n"
		.<<<'JS'
// Empties a date element whose date is before its earliest date or after its latest one, and says why. Those are the
// field's min and max. A date that isn't valid is emptied too.
function check_date_limits(element_id) {
    var date_input = jQuery("#"+element_id);
    var min_date = date_input.attr('min');
    var max_date = date_input.attr('max');
    var selected_date = new Date(date_input.val());
    // a date that isn't valid has no time, and NaN isn't equal to itself
    if (selected_date.getTime() !== selected_date.getTime()) {
        date_input.val('');
        return;
    }
    var problem = '';
    var limit = null;
    if (min_date && selected_date < new Date(min_date)) {
        problem = 'TOO_EARLY';
        limit = new Date(min_date);
    } else if (max_date && selected_date > new Date(max_date)) {
        problem = 'TOO_LATE';
        limit = new Date(max_date);
    }
    if (problem) {
        date_input.val('');
        // the limit is read as midnight UTC, so it's shown in UTC, or west of UTC it would be the day before
        var options = { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' };
        // in the page's language, like the words around it. Some themes give it as xml:lang.
        var language = document.documentElement.getAttribute('lang') || document.documentElement.getAttribute('xml:lang') || undefined;
        var shownLimit;
        try {
            shownLimit = limit.toLocaleDateString(language, options);
        } catch (e) { // a page language the browser doesn't recognise
            shownLimit = limit.toLocaleDateString(undefined, options);
        }
        alert(formulizeText(window.formulizeDateLimitText).text(problem, shownLimit));
    }
}

JS
		."</script>\n";
}

/**
 * The checks a form has to pass before it's submitted. Full screen's validateAndSubmit() and submitForm(), and the drawer's
 * validateCurrentForm() in drawer.js, all call these, so a form is checked the same way wherever it's shown. What happens
 * once it passes is up to each of them. drawJavascript() prints this, and the drawer's endpoint does with each form it
 * shows, so a page can have it more than once, harmlessly. Its words are the constants starting _formulize_JS_SUBMIT_, and
 * it needs formulize_elementScripts(), which loads the script that gives them.
 * @return string The JavaScript, without script tags
 */
function formulize_submitStepJs() {
	$text = json_encode(formulize_scriptText('_formulize_JS_SUBMIT_'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	return "window.formulizeSubmitText = $text;\n".<<<'JS'
// Are conditional elements still on their way? If it's been too long, something has gone wrong, so say so.
window.needWaitForConditionalAlert = window.needWaitForConditionalAlert || false;
function conditionalCheckIsInProgress() {
    if(typeof conditionalCheckInProgress !== 'undefined' && conditionalCheckInProgress > 0) {
        if(!needWaitForConditionalAlert) {
            needWaitForConditionalAlert = setTimeout(function() {
                if(typeof conditionalCheckInProgress !== 'undefined' && conditionalCheckInProgress > 0) {
                    alert(formulizeText(window.formulizeSubmitText).text('CONDITIONAL_ERROR'));
                }
            }, 10000);
        }
        return true;
    } else {
        clearTimeout(needWaitForConditionalAlert);
        needWaitForConditionalAlert = false;
        return false;
    }
}

// If conditional elements are still on their way, try again in a second, and return true
function formulize_waitForConditionalElements(tryAgain) {
    if(!conditionalCheckIsInProgress()) {
        return false;
    }
    setTimeout(tryAgain, 1000);
    return true;
}

// Letters in a numbers only box are removed when it's saved, so ask first. Returns false if any of the answers is no.
// A browser's number box can only hold letters as part of a number, such as 1e5.
function formulize_lettersInNumberBoxesAreOK(form) {
    var answersAreYes = true;
    jQuery('.numbers-only-textbox').filter(function() { return this.form === form; }).each(function() {
        var value = jQuery(this).val();
        if(value.match(/[a-z]/i) !== null && value != '{ID}' && value != '{SEQUENCE}') {
            if(!confirm(formulizeText(window.formulizeSubmitText).text('NUMBERS_ONLY', value))) {
                jQuery(this).focus();
                answersAreYes = false;
            }
        }
    });
    return answersAreYes;
}

// Run the form's validation, and then the form's optional extra validation function, formulizeExtraFormValidation, which a
// screen template or a form text element can provide. It gets what the validation came to, and the form, and what it
// returns is the answer. The page's is on window. The drawer keeps its form's with the form, because the two share a page.
// When the form passes, its rich text is copied into the fields that are saved.
function formulize_validateForm(form, leave) {
    var valid = window['xoopsFormValidate_'+form.getAttribute('id')](leave, form);
    var extraValidation = typeof form.formulizeExtraFormValidation !== 'undefined' ? form.formulizeExtraFormValidation : window.formulizeExtraFormValidation;
    if(typeof extraValidation === 'function') {
        valid = extraValidation(valid, form);
    }
    if(valid && typeof updateCKEditors === 'function') {
        updateCKEditors();
    }
    return valid;
}

JS;
}

/**
 * Validation code for an element whose values have to be unique. It asks the server whether the value is in use already,
 * and validation stops until the answer comes. formulize_xhr_return() stores the answer and tries again what was being
 * done (full screen's validateAndSubmit(), or in the drawer the action the person took), and then this finds it. An
 * answer is only for the value that was checked, so a value changed since then is checked again: the drawer saves
 * without reloading the page, and a value checked before a save must not count for one typed after it.
 * @param string $markupName The element's markup name, which the server files its answer under
 * @param string $valueJs JavaScript for the value that has to be unique
 * @param int $elementId The element's id
 * @param int|string $entryId The entry being saved, or 'new'
 * @param string $message What to tell the person when the value is in use already
 * @param string $fieldJs JavaScript for the field to put the cursor in then
 * @param string $checkedValueJs JavaScript for the value as the server checks it, when that's not $valueJs, such as a phone number's digits
 * @return array Lines of validation code, for the validation function full screen and the drawer call with leave and myform
 */
function formulize_uniqueValueValidationCode($markupName, $valueJs, $elementId, $entryId, $message, $fieldJs, $checkedValueJs = null) {
	$checkedValueJs = $checkedValueJs ? $checkedValueJs : $valueJs;
	$entryJs = json_encode(is_numeric($entryId) ? intval($entryId) : (string) $entryId);
	return array(
		"if($valueJs != '') {",
		"    var formulize_uniqueKey = ".json_encode($markupName).";",
		"    window.formulize_uniqueValueChecked = window.formulize_uniqueValueChecked || {};",
		"    var formulize_uniqueAnswer = formulize_xhr_returned_check_for_unique_value[formulize_uniqueKey];",
		"    if(formulize_uniqueAnswer && formulize_uniqueAnswer != 'notreturned' && formulize_uniqueValueChecked[formulize_uniqueKey] === $valueJs) {",
		"        if(formulize_uniqueAnswer != 'valuenotfound') {",
		"            window.alert(".json_encode($message).");",
		"            if(typeof hideSavingGraphic === 'function') { hideSavingGraphic(); }",
		"            delete formulize_xhr_returned_check_for_unique_value[formulize_uniqueKey];",
		"            $fieldJs.focus();",
		"            return false;",
		"        }",
		"    } else {",
		"        formulize_xhr_returned_check_for_unique_value[formulize_uniqueKey] = 'notreturned';",
		"        formulize_uniqueValueChecked[formulize_uniqueKey] = $valueJs;",
		"        formulize_xhr_send('check_for_unique_value', [$checkedValueJs, ".intval($elementId).", $entryJs, leave]);",
		"        return false;",
		"    }",
		"}",
	);
}
