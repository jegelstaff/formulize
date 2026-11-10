<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################

// Alternate URLs: the clean addresses screens are reached at when the site has them turned on (the
// formulizeRewriteRulesEnabled preference), instead of /modules/formulize/index.php?sid=. The server sends every
// path that is not a file to index.php as formulizeRewriteRuleAddress, and formulize_handleHtaccessRewriteRule()
// reads it. An address has the shape /address/entry/page/:
//
//  - address: the alternate address the administrator gave the screen, or the screen's id
//  - entry: optional, the entry's value in the screen's identifying element (rewriteruleElement), or its id
//  - page: optional, the title of a page in a multipage screen
//
// A form screen with no address of its own is reached at the address of the list it shows entries for, with the
// entry identified the way that list identifies it. Reached at that address, the list hands the entry to the
// screen, the way it does when someone clicks the entry in the list.
//
// Reading an address and writing one both go through the same description of it, the parts:
//
//   array('screen' => the screen shown, 'listScreen' => the list whose address it is reached at, or null,
//         'entryId' => the entry id, or 0, 'page' => the page number in a multipage screen, or null)
//
// Old addresses keep working: index.php?sid= always does, and so does every alternate address that has been typed.
//
// Part of functions.php, which requires this file at its end. It is a file of its own only to keep functions.php
// to a size that can be worked with: the functions are global, and are available wherever functions.php is.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

/**
 * Whether the site has alternate URLs turned on. Nothing writes an alternate URL when it does not, since the server
 * may not have the rewrite rules that serve them.
 *
 * @return bool
 */
function formulize_alternateURLsEnabled() {
	return formulize_moduleConfigValue('formulizeRewriteRulesEnabled') ? true : false;
}

/**
 * The screen object for a screen object or a screen id
 *
 * @param object|int $screenOrId The screen, or its id
 * @return object|false The screen, or false if there is no such screen
 */
function formulize_alternateURLScreenObject($screenOrId) {
	if(is_object($screenOrId)) {
		return is_a($screenOrId, 'formulizeScreen') ? $screenOrId : false;
	}
	if(!$screenOrId OR !is_numeric($screenOrId)) {
		return false;
	}
	$screen_handler = xoops_getmodulehandler('screen', 'formulize');
	return $screen_handler->get(intval($screenOrId));
}

/**
 * The address a screen is reached at: the alternate address typed for it, or otherwise its id
 *
 * @param object|int $screen The screen, or its id
 * @return string The address, or an empty string when alternate URLs are off or there is no such screen
 */
function formulize_alternateURLAddressForScreen($screen) {
	if(!formulize_alternateURLsEnabled() OR !$screen = formulize_alternateURLScreenObject($screen)) {
		return '';
	}
	if($address = $screen->getVar('rewriteruleAddress')) {
		return $address;
	}
	return (string) $screen->getVar('sid');
}

/**
 * The screen a list shows its entries in, as the list is configured: its view entry screen, or when it has none,
 * the form's default form screen. A screen id passed in a request to override this does not count, since an
 * address has to lead to the same screen whoever follows it.
 *
 * @param object|int $listScreen The list screen, or its id
 * @return int The id of the screen, or 0 if the list shows its entries in something else, such as a Pageworks page
 */
function formulize_alternateURLListViewEntryScreenId($listScreen) {
	static $cachedViewEntryScreenIds = array();
	$listScreen = formulize_alternateURLScreenObject($listScreen);
	if(!$listScreen OR $listScreen->getVar('type') != 'listOfEntries') {
		return 0;
	}
	$sid = $listScreen->getVar('sid');
	if(!isset($cachedViewEntryScreenIds[$sid])) {
		$cachedViewEntryScreenIds[$sid] = 0;
		if(!is_a($listScreen, 'formulizeListOfEntriesScreen')) {
			$listScreen_handler = xoops_getmodulehandler('listOfEntriesScreen', 'formulize');
			$listScreen = $listScreen_handler->get($sid);
		}
		if($listScreen) {
			$viewentryscreen = $listScreen->getVar('viewentryscreen');
			if($viewentryscreen AND $viewentryscreen != 'none' AND is_numeric($viewentryscreen)) {
				$cachedViewEntryScreenIds[$sid] = intval($viewentryscreen);
			} elseif(!$viewentryscreen OR $viewentryscreen == 'none') {
				$form_handler = xoops_getmodulehandler('forms', 'formulize');
				if($formObject = $form_handler->get($listScreen->getVar('fid'))) {
					$cachedViewEntryScreenIds[$sid] = intval($formObject->getVar('defaultform'));
				}
			}
			// otherwise it is a Pageworks page, 'p' and a number, which no address leads to
		}
	}
	return $cachedViewEntryScreenIds[$sid];
}

/**
 * The list whose address a screen is reached at, when the screen shows that list's entries. That is the list
 * the screen was opened from, if it is showing an entry of that list, or otherwise the form's default list.
 * The list must be on the same form as the screen, so that its address can identify the entry the screen shows.
 *
 * @param object|int $screen The screen, or its id
 * @return object|null The list screen, or null if there is none
 */
function formulize_alternateURLListScreenFor($screen) {
	static $cachedDefaultLists = array();
	if(!$screen = formulize_alternateURLScreenObject($screen) OR $screen->getVar('type') == 'listOfEntries') {
		return null;
	}
	$sid = $screen->getVar('sid');
	$fid = $screen->getVar('fid');
	$candidates = array();
	// set by displayEntries when a list hands an entry to the screen it shows entries in
	if(isset($GLOBALS['formulize_alternateURLListScreen']) AND is_object($GLOBALS['formulize_alternateURLListScreen'])) {
		$candidates[] = $GLOBALS['formulize_alternateURLListScreen'];
	}
	if(!isset($cachedDefaultLists[$sid])) {
		$cachedDefaultLists[$sid] = false;
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		if($formObject = $form_handler->get($fid) AND $defaultListId = $formObject->getVar('defaultlist')) {
			$cachedDefaultLists[$sid] = formulize_alternateURLScreenObject($defaultListId);
		}
	}
	$candidates[] = $cachedDefaultLists[$sid];
	foreach($candidates as $listScreen) {
		if($listScreen
			AND $listScreen->getVar('sid') != $sid
			AND $listScreen->getVar('fid') == $fid
			AND formulize_alternateURLListViewEntryScreenId($listScreen) == $sid) {
			return $listScreen;
		}
	}
	return null;
}

/**
 * Describe the alternate URL of a screen, and optionally of an entry in it and a page of it
 *
 * A screen with an alternate address of its own is reached at that. Otherwise, a screen showing an entry of a
 * list is reached at the list's address.
 *
 * @param object|int $screen The screen, or its id
 * @param int $entryId Optional. The entry shown.
 * @param int|null $page Optional. The page shown, in a multipage screen.
 * @return array|false The parts of the URL, as described at the top of this file, or false if there is no such screen
 */
function formulize_alternateURLParts($screen, $entryId = 0, $page = null) {
	if(!$screen = formulize_alternateURLScreenObject($screen)) {
		return false;
	}
	$parts = array(
		'screen' => $screen,
		'listScreen' => null,
		'entryId' => intval($entryId),
		'page' => $page ? intval($page) : null
	);
	if($parts['entryId'] AND !$screen->getVar('rewriteruleAddress')) {
		$parts['listScreen'] = formulize_alternateURLListScreenFor($screen);
	}
	return $parts;
}

/**
 * The screen whose address and identifying element an alternate URL uses
 *
 * @param array $parts The parts of the URL
 * @return object The screen
 */
function formulize_alternateURLAddressScreen($parts) {
	return $parts['listScreen'] ? $parts['listScreen'] : $parts['screen'];
}

/**
 * How an entry is identified in an alternate URL: by its value in the screen's identifying element, or by its id
 *
 * @param object $screen The screen whose address the URL uses
 * @param int $entryId The entry
 * @return string The identifier, not yet encoded for a URL
 */
function formulize_alternateURLEntryIdentifier($screen, $entryId) {
	$identifier = '';
	if($elementId = $screen->getVar('rewriteruleElement')) {
		include_once XOOPS_ROOT_PATH . "/modules/formulize/include/extract.php"; // prepvalues
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		if($elementObject = $element_handler->get($elementId)) {
			$dataHandler = new formulizeDataHandler($elementObject->getVar('fid'));
			$dbValue = $dataHandler->getElementValueInEntry($entryId, $elementObject);
			$preppedValue = prepvalues($dbValue, $elementObject->getVar('ele_handle'), $entryId); // an array for some kinds of element
			$preppedValue = is_array($preppedValue) ? (isset($preppedValue[0]) ? $preppedValue[0] : '') : $preppedValue;
			$identifier = htmlspecialchars_decode((string) $preppedValue);
		}
	}
	return strlen($identifier) ? $identifier : (string) intval($entryId);
}

/**
 * How a page of a multipage screen appears in an alternate URL, which is by its title
 *
 * @param object $screen The screen
 * @param int|null $pageNumber The page, counting from 1
 * @return string The page as it appears in the URL, not yet encoded, or an empty string if the URL has no page in it
 */
function formulize_alternateURLPageSegment($screen, $pageNumber) {
	if(!$pageNumber OR !$screen OR $screen->getVar('type') != 'multiPage') {
		return '';
	}
	$pageTitles = formulize_alternateURLPageTitles($screen);
	if(count($pageTitles) > 1 AND isset($pageTitles[$pageNumber])) {
		return $pageTitles[$pageNumber];
	}
	return '';
}

/**
 * The page of a multipage screen that a page in an alternate URL refers to. The reverse of
 * formulize_alternateURLPageSegment().
 *
 * @param object|int $screen The screen, or its id
 * @param string $pageSegment The page as it appears in the URL
 * @return int|false The page, counting from 1, or false if there is no such page
 */
function formulize_alternateURLPageNumber($screen, $pageSegment) {
	if(!$pageSegment OR !$screen = formulize_alternateURLScreenObject($screen) OR $screen->getVar('type') != 'multiPage') {
		return false;
	}
	return array_search(urldecode($pageSegment), formulize_alternateURLPageTitles($screen));
}

/**
 * The titles of the pages of a multipage screen, as they appear in alternate URLs
 *
 * @param object $screen The multipage screen
 * @return array The titles, keyed by page number
 */
function formulize_alternateURLPageTitles($screen) {
	$multiPageScreenHandler = xoops_getmodulehandler('multiPageScreen', 'formulize');
	if(!is_a($screen, 'formulizeMultiPageScreen') AND !$screen = $multiPageScreenHandler->get($screen->getVar('sid'))) {
		return array();
	}
	list($pages, $pageTitles, $pageConditions) = $multiPageScreenHandler->traverseScreenPages($screen);
	foreach($pageTitles as $pageNumber=>$pageTitle) {
		$pageTitles[$pageNumber] = trans(htmlspecialchars_decode((string) $pageTitle));
	}
	return $pageTitles;
}

/**
 * Write an alternate URL
 *
 * @param array|false $parts The parts of the URL, from formulize_alternateURLParts(). An 'entryIdentifier' can be
 * included, when the caller already has the entry's value in the identifying element.
 * @return string The URL, or an empty string if the screen has no alternate URL
 */
function formulize_buildAlternateURL($parts) {
	if(!$parts) {
		return '';
	}
	$addressScreen = formulize_alternateURLAddressScreen($parts);
	if(!$address = formulize_alternateURLAddressForScreen($addressScreen)) {
		return '';
	}
	$url = XOOPS_URL.'/'.rawurlencode($address);
	if($parts['entryId']) {
		$identifier = (isset($parts['entryIdentifier']) AND strlen((string) $parts['entryIdentifier'])) ? (string) $parts['entryIdentifier'] : formulize_alternateURLEntryIdentifier($addressScreen, $parts['entryId']);
		$url .= '/'.rawurlencode($identifier).'/';
		if($page = formulize_alternateURLPageSegment($parts['screen'], $parts['page'])) {
			$url .= rawurlencode($page).'/';
		}
	}
	return $url;
}

/**
 * The URL of a screen: its alternate URL, or otherwise its index.php?sid= URL
 *
 * @param object|int $screen The screen, or its id
 * @return string The URL
 */
function formulize_screenURL($screen) {
	if($url = formulize_buildAlternateURL(formulize_alternateURLParts($screen))) {
		return $url;
	}
	$sid = is_object($screen) ? $screen->getVar('sid') : intval($screen);
	return XOOPS_URL.'/modules/formulize/index.php?sid='.$sid;
}

/**
 * Whether the address bar should be changed to show a page's alternate URL. Every link Formulize writes uses the
 * alternate URL, but an address the user went to themselves is left alone, if the screen has no alternate address
 * typed for it and the user did not go there by an alternate URL. Whoever set up that screen never chose an
 * address for it, and the address the user went to works, and can be shared.
 *
 * @param array $parts The parts of the URL
 * @return bool
 */
function formulize_alternateURLShouldRewriteAddressBar($parts) {
	if(formulize_isEmbeddedRequest()) {
		// the frame may be on another of the site's domain names, where the address cannot be changed to this one
		return false;
	}
	if(formulize_alternateURLAddressScreen($parts)->getVar('rewriteruleAddress')) {
		return true;
	}
	global $formulizeCanonicalURI;
	return $formulizeCanonicalURI ? true : false;
}

/**
 * Check for a rewrite rule value having been passed to the url, and if found then update $_GET and the $_SERVER['REQUEST_URI'] to match.
 * If a rewrite rule was found, the canonical Formulize URL for the page will be set in the global variable $formulizeCanonicalURI
 */
function formulize_handleHtaccessRewriteRule() {
	if(isset($_GET['formulizeRewriteRuleAddress']) AND $_GET['formulizeRewriteRuleAddress']) {
		global $formulizeCanonicalURI;
		global $formulizeRemoveEntryIdentifier;
		global $formulizeAlternateURLRefusedEntry;
		$formulizeCanonicalURI = '';
		$formulizeRemoveEntryIdentifier = false;
		$formulizeAlternateURLRefusedEntry = 0;
		$trimedFormulizeRewriteRuleAddress = trim($_GET['formulizeRewriteRuleAddress'], '/');
		$addressData = explode('/', $trimedFormulizeRewriteRuleAddress);
		$address = $addressData[0];
		$entryIdentifier = isset($addressData[1]) ? $addressData[1] : null;
		$pageIdentifier = isset($addressData[2]) ? $addressData[2] : null;
		if($sid = formulize_getSidFromRewriteAddress($address, $entryIdentifier)) {
			$queryString = "sid=$sid";
			if($entryIdentifier) {
				$currentPage = "";
				$ve = intval($entryIdentifier);
				$screen_handler = xoops_getmodulehandler('screen', 'formulize');
				if($screenObject = $screen_handler->get($sid)) {
					$ve = formulize_getEntryIdFromRewriteruleElement($screenObject, $entryIdentifier);
					if(!isset($_POST['formulize_currentPage'])) {
						// a list hands the entry to the screen it shows entries in, so the page is a page of that screen
						$pageScreen = $screenObject->getVar('type') == 'listOfEntries' ? formulize_alternateURLListViewEntryScreenId($screenObject) : $screenObject;
						$currentPage = formulize_alternateURLPageNumber($pageScreen, $pageIdentifier);
					}
				}
				if($ve AND (!$screenObject OR security_check($screenObject->getVar('fid'), $ve))) {
					$queryString = "sid=$sid&ve=$ve";
					$_GET['ve'] = $ve;
					$_REQUEST['ve'] = $ve;
					if($currentPage) {
						$_POST['formulize_currentPage'] = $currentPage;
					}
					if($pageIdentifier) {
						// when we get to JS later, we'll need to alter the URL to remove the page identifier. It will be put back in URL by history api in JS. And the loading of the page will work based only on the entry identifier. Page identifier is simply a UI convenience in URL when rewriting is enabled.
						$formulizeRemoveEntryIdentifier = "window.history.replaceState(null, '', ".json_encode(XOOPS_URL."/$address/".$entryIdentifier."/").");";
						// seed the current URL with the correct address
						getCurrentURL($address."/".$entryIdentifier."/");
					}
				} else {
					// someone not logged in may be asked to log in to see this entry. See formulize_anonMustLogInForEntry()
					$formulizeAlternateURLRefusedEntry = $ve;
					// when we get to JS later, we'll need to alter the URL to remove the invalid identifier
					$formulizeRemoveEntryIdentifier = "window.history.replaceState(null, '', ".json_encode(XOOPS_URL."/$address/").");";
					// seed the current URL with the correct address
					getCurrentURL($address);
					// redetermine the sid, since there is in fact no valid identifier so maybe we want a list-ish screen instead?
					$sid = formulize_getSidFromRewriteAddress($address);
					$queryString = "sid=$sid";
				}
			}
			$_GET['sid'] = $sid;
			$_REQUEST['sid'] = $sid;
			$formulizeCanonicalURI = "/modules/formulize/index.php?$queryString";
		}
		if(!$formulizeCanonicalURI) {
			icms::$logger->disableLogger();
			while(ob_get_level()) {
					ob_end_clean();
			}
			http_response_code(404);
			exit();
		}
	}
}

/**
 * Determine the entry ID that matches a given rewrite rule entry identifier, for the given screen
 *
 * @param mixed $screenObjectOrIdentifier The screen object, or an identifier
 * @param string $entryIdentifier The identifier from the clean URL
 * @return int The entry ID found, or zero if none found
 */
function formulize_getEntryIdFromRewriteruleElement($screenObjectOrIdentifier, $entryIdentifier) {
	$entryId = $entryIdentifier;
	$screenObject = $screenObjectOrIdentifier;
	if(!is_object($screenObject) OR !is_a($screenObject, 'formulizeScreen')) {
		$screen_handler = xoops_getmodulehandler('screen', 'formulize');
		$screenObject = $screen_handler->get($screenObjectOrIdentifier);
	}
	if($screenObject AND $entryIdentifier AND $rewriteruleElement = $screenObject->getVar('rewriteruleElement')) {
		$element_handler = xoops_getmodulehandler('elements', 'formulize');
		if($rewriteruleElementObject = $element_handler->get($rewriteruleElement)) {
			$ele_type = $rewriteruleElementObject->getVar('ele_type');
			// re-get the element handler based on the specific type, because it will have the proper prepareLiteralTextForDB logic in it (generic handler has nothing specific)
			$element_handler = xoops_getmodulehandler($ele_type."Element", 'formulize');
			$rewriteruleElementObject = $element_handler->get($rewriteruleElement);
			$searchValue = $element_handler->prepareLiteralTextForDB(urldecode($entryIdentifier), $rewriteruleElementObject);
			$dataHandler = new formulizeDataHandler($screenObject->getVar('fid'));
			$entryId = $dataHandler->findFirstEntryWithValue($rewriteruleElementObject, $searchValue);
			// an entry with no value in the element is identified by its id instead (see formulize_alternateURLEntryIdentifier)
			if(!$entryId AND ctype_digit((string) $entryIdentifier)) {
				$entryId = $entryIdentifier;
			}
		}
	}
	return intval($entryId);
}

/**
 * Get the screen id for a given alternate URL address: a screen's alternate address, or when alternate URLs are on
 * and no screen has that address, a screen's id.
 *
 * @param string $address Optional. The alternate URL address to lookup. If none specified, reuse the first one we were passed.
 * @param string $entryIdentifier Optional. An entry identifier gathered from the URL. Could be invalid! Used as a flag to indicate if we should be gathering a list-ish screen or not.
 * @return int|bool Returns the screen ID, or false if there is no match, or nothing to match with.
 */
function formulize_getSidFromRewriteAddress($address="", $entryIdentifier="") {
	global $xoopsDB;
	static $originalAddress = '';
	if(!$originalAddress) {
		$originalAddress = $address;
	}
	if(!$address) {
		$address = $originalAddress;
	}
	if($address) {

		// check if it's our special check-if-this-is-enabled address, and if so, output 1 and exit
		if($address == "formulize-check-if-alternate-urls-are-properly-enabled-please") {
			icms::$logger->disableLogger();
			while(ob_get_level()) {
					ob_end_clean();
			}
			print 1;
			exit();
		}

		$sql = "SELECT sid, type FROM ".$xoopsDB->prefix('formulize_screen')." WHERE MATCH(`rewriteruleAddress`) AGAINST('".formulize_db_escape($address)."') AND `rewriteruleAddress` = '".formulize_db_escape($address)."'";
		if($res = $xoopsDB->query($sql)) {
			$candidateScreen = 0;
			$rowsFound = $xoopsDB->getRowsNum($res);
			if($rowsFound == 1 AND $record = $xoopsDB->fetchArray($res)) {
				$candidateScreen = $record['sid'] ? $record['sid'] : $candidateScreen;
			} elseif($rowsFound > 1) {
				while($record = $xoopsDB->fetchArray($res)) {
					if($record['sid']) {
						$candidateScreen = $record['sid'];
						// stop looking if we've found the right type based on whether an entry is being displayed
						if((!$entryIdentifier AND ($record['type'] == 'listOfEntries' OR $record['type'] == 'calendar' OR $record['type'] == 'graph'))
							OR ($entryIdentifier AND $record['type'] != 'listOfEntries' AND $record['type'] != 'calendar' AND $record['type'] != 'graph')) {
							break;
						}
					}
				}
			}
			if($candidateScreen) {
				return $candidateScreen;
			}
		}

		// no screen has this address, so it may be a screen's id
		if(ctype_digit((string) $address) AND formulize_alternateURLsEnabled() AND $screenObject = formulize_alternateURLScreenObject($address)) {
			return intval($screenObject->getVar('sid'));
		}
	}
	return false;
}

/**
 * Create a js history api snippet to update the URL if there are rewrite rules in effect
 *
 * @param object $screen The screen object being displayed
 * @param int|string $entry_id The entry id being displayed, or 'new' when a blank form is displayed yet to be saved
 * @param array $settings Optional. The $settings array if applicable. Used with multipage screens to identify the current page.
 * @return string The code snippet generated, or and empty string if there is no snippet
 */
function updateAlternateURLIdentifierCode($screen, $entry_id, $settings=array()) {
	$entry_id = intval($entry_id);
	if(!$screen OR !$entry_id) {
		return '';
	}
	$page = ($screen->getVar('type') == 'multiPage' AND isset($settings['formulize_currentPage'])) ? $settings['formulize_currentPage'] : null;
	$parts = formulize_alternateURLParts($screen, $entry_id, $page);
	if(!$parts OR !formulize_alternateURLShouldRewriteAddressBar($parts) OR !$url = formulize_buildAlternateURL($parts)) {
		return '';
	}
	$code = "window.history.replaceState(null, '', ".json_encode($url).");";
	if(formulize_alternateURLPageSegment($screen, $page)) {
		// the address ends in the page, so the other pages' tabs are its neighbours
		$code .= "
		jQuery(window).on('load', function() {
			jQuery('a.navtab:not(:first)').each(function() {
				jQuery(this).attr('href', '../' + jQuery(this).text());
			});
		});";
	}
	return $code;
}

/**
 * Deduce the correct done destination for a screen from the current URL.
 * If the done destination is for this specific screen that we're rendering,
 * switch done destination to the default list for the form if any.
 *
 * @param object|false $screen The screen that we're rendering
 * @return string The done destination to use
 */
function determineDoneDestinationFromURL($screen = false) {
	global $formulizeCanonicalURI;
	$done_dest = getCurrentURL();
	$screen_handler = xoops_getmodulehandler('screen', 'formulize');
	if(!$screen AND isset($_GET['sid'])) {
		$screen = $screen_handler->get(intval($_GET['sid']));
	}
	if(!$screen) {
		return $done_dest;
	}
	$sid = $screen->getVar('sid');
	// if the current URL is for this very screen, going there when done would go nowhere
	if(strstr($done_dest, 'sid='.$sid)
		OR strstr($done_dest, 'fid='.$screen->getVar('fid'))
		OR ($formulizeCanonicalURI AND isset($_GET['sid']) AND $_GET['sid'] == $sid)) {
		// if this screen's alternate address is shared with another screen, go there... (will prefer lists when no entry identifier passed to this function, so almost certainly an alternate screen id will be of a list screen)
		if(formulize_alternateURLsEnabled()
			AND $alternateURLForSid = $screen->getVar('rewriteruleAddress')
			AND $doneScreenId = formulize_getSidFromRewriteAddress($alternateURLForSid)
			AND $doneScreenId != $sid) {
			return formulize_screenURL($doneScreenId);
		}
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		if($formObject = $form_handler->get($screen->getVar('fid'))) {
			if($defaultListScreenId = $formObject->getVar('defaultlist')) {
				if($defaultListScreenObject = $screen_handler->get($defaultListScreenId)) {
					$done_dest = formulize_screenURL($defaultListScreenObject);
				}
			}
		}
	}
	return $done_dest;
}

/**
 * Clean up a done destination for a screen, so it does not contain an entry reference.
 * Avoids the user ending up in a loop where the done action takes them back to the same page.
 * Works for standard and clean URLs
 *
 * @param string $done_dest The address the user is meant to return to
 * @return string The cleaned done destination with entry reference stripped
 */
function stripEntryFromDoneDestination($done_dest) {
	// strip out any ve portion of a done destination, so we don't end up forcing the user back to this entry after they're done
	$veTarget = strstr($done_dest, '&ve=') ? '&ve=' : '?ve=';
	if($done_dest AND $vepos = strpos($done_dest, $veTarget)) {
			if(is_numeric(substr($done_dest, $vepos+4))) {
					$done_dest = substr($done_dest, 0, $vepos);
			}
	}
	// if there was an alternate URL used to access the page, and a ve was specified, scale back to remove the ve from the done_dest
	global $formulizeCanonicalURI;
	if($done_dest AND $formulizeCanonicalURI AND isset($_GET['ve']) AND $_GET['ve']) {
		$trimmedDoneDest = trim($done_dest, '/'); // take off last slash if any
		$trailingSlash = $trimmedDoneDest === $done_dest ? '' : '/'; // if there was a slash on the end, remember this for later
		$doneDestParts = explode('/', $trimmedDoneDest); // split on slashes
		$entryIdentifier = $doneDestParts[count($doneDestParts)-1];
		$entryId = formulize_getEntryIdFromRewriteruleElement($_GET['sid'], $entryIdentifier);
		if($entryId === intval($_GET['ve'])) { // make sure this is the ve we're talking about
			unset($doneDestParts[count($doneDestParts)-1]); // remove the last value, which will be the ve number
			$done_dest = implode('/', $doneDestParts).$trailingSlash; // put back together, with trailing slash if necessary
		}
	}
	return $done_dest;
}
