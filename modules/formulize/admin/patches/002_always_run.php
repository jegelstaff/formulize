<?php


// Auto-discovery entry point: called by xoops_module_update_formulize() via the patches loop.
// These operations should always run with an update... regardless of dbversion
function formulize_patch_002_always_run($prev_dbversion, $required_dbversion) {
	global $xoopsConfig, $xoopsDB;

	// Clear the compiled Smarty templates. Not everything in here belongs to Formulize, and updating
	// the module is not enough on its own to refresh those, so they are swept away on every update.
	foreach((array) scandir(XOOPS_ROOT_PATH.'/templates_c') as $templateFile) {
		if($templateFile !== '.' AND $templateFile !== '..' AND $templateFile !== 'index.html') {
			unlink(XOOPS_ROOT_PATH.'/templates_c/'.$templateFile);
		}
	}

	// clear the admin menu cache files, so that any changes to the menu structure or labels will be reflected in the admin interface
	$adminMenuLangs = [ 'english', $xoopsConfig['language'] ];
	$adminMenuLangs = array_unique($adminMenuLangs);
	foreach($adminMenuLangs as $lang) {
		$adminMenuFile = XOOPS_ROOT_PATH.'/cache/adminmenu_'.$lang.'.php';
		if (file_exists($adminMenuFile)) {
			unlink($adminMenuFile);
		}
	}

	// Clear the generated wrappers for form custom code, so they are rebuilt from whatever source is on
	// disk now. The wrappers are content-addressed (see formulizeForm::procedure_cache_filename), so a
	// stale one cannot be used in the ordinary course of things - this is here for the update itself, to
	// sweep away the accumulated files of every earlier version in one go, including any left by the fixed
	// -name scheme that came before. Deleting is always safe: a missing wrapper is regenerated on demand.
	foreach((array) glob(ICMS_CACHE_PATH.'/form_*_on_before_save*.php') as $cacheFile) { @unlink($cacheFile); }
	foreach((array) glob(ICMS_CACHE_PATH.'/form_*_on_after_save*.php') as $cacheFile) { @unlink($cacheFile); }
	foreach((array) glob(ICMS_CACHE_PATH.'/form_*_on_delete*.php') as $cacheFile) { @unlink($cacheFile); }
	foreach((array) glob(ICMS_CACHE_PATH.'/form_*_custom_edit_check*.php') as $cacheFile) { @unlink($cacheFile); }

	// ensure that use_mysession is set to 1 if session_name is set
	$configTable = $xoopsDB->prefix('config');
	$result = $xoopsDB->queryF("SELECT conf_value FROM $configTable WHERE conf_modid = 0 AND conf_name = 'session_name'");
	if (!$result) {
			echo '<p>Error: failed to read session_name: ' . htmlspecialchars($xoopsDB->error()) . ' Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>';
			return false;
	}
	$row = $xoopsDB->fetchRow($result);
	if (empty($row[0])) {
			echo '<p>session_name is not set; leaving use_mysession unchanged.</p>';
	} elseif (!$xoopsDB->queryF("UPDATE $configTable SET conf_value = '1' WHERE conf_modid = 0 AND conf_name = 'use_mysession'")) {
			echo '<p>Error: failed to set use_mysession to 1: ' . htmlspecialchars($xoopsDB->error()) . ' Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>';
			return false;
	}

	// Rename any element handles containing hyphens before running other schema work.
	// This is idempotent: handles without hyphens are untouched on repeat runs.
	formulize_migrate_hyphenated_handles();

	// Add to the Primary Relationship any connection between forms that is missing from it.
	// Runs on every update, because a Primary Relationship can be left incomplete by an earlier
	// version and there is otherwise nothing that would ever notice.
	if (!formulize_repair_primary_relationship()) {
		return false;
	}

	if (!formulize_restore_webmaster_view_form_permissions()) {
		return false;
	}

	formulize_remove_stray_registered_user_edit_form_permissions();
	formulize_ensure_screen_template_folder_for_theme();
	formulize_warn_about_element_containers_missing_element_class();
	formulize_repair_custom_openlisttemplate_functions();

	// Keep the timezone list in step with PHP's timezone database, which changes with the PHP
	// version the site runs on rather than with anything Formulize does.
	formulize_update_timezone_options($xoopsDB);

  return true;
}

/**
 * Give the Webmasters group explicit view_form permission on every form.
 *
 * Webmasters need this on every form, always. Without it the owner groups column cannot report the
 * Webmasters group for entries webmasters created, and the owner group information then runs out of
 * step with the dataset it is supposed to describe.
 *
 * @return bool FALSE only if a permission could not be written
 */
function formulize_restore_webmaster_view_form_permissions() {
	global $xoopsDB;
	$sql = "SELECT id_form FROM ".$xoopsDB->prefix('formulize_id')." AS f WHERE NOT EXISTS(SELECT 1 FROM ".$xoopsDB->prefix("group_permission")." AS p WHERE p.gperm_itemid = f.id_form AND p.gperm_name = 'view_form' AND p.gperm_groupid = 1)";
	$res = $xoopsDB->query($sql);
	if (!$res) {
		print "Error: could not assign 'View Form' permission for Webmasters to all forms.<br>".$xoopsDB->error()."<br>Assign this permission manually for Webmasters to all forms, or please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.";
		return false;
	}
	$assigned = true;
	$formulizeModId = getFormulizeModId();
	while($row = $xoopsDB->fetchRow($res)) {
		$formId = intval($row[0]);
		$insertSql = "INSERT INTO ".$xoopsDB->prefix("group_permission")." (`gperm_itemid`, `gperm_groupid`, `gperm_name`, `gperm_modid`) VALUES ($formId, 1, 'view_form', $formulizeModId)";
		if($xoopsDB->queryF($insertSql) == false) {
			$assigned = false;
		}
	}
	if(!$assigned) {
		print "Error: could not assign 'View Form' permission for Webmasters to all forms.<br>".$xoopsDB->error()."<br>Assign this permission manually for Webmasters to all forms, or please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.";
	}
	return $assigned;
}

/**
 * Take edit_form away from Registered Users when that group is not a module administrator.
 *
 * Sites carry these from time immemorial, and the group should not hold them without admin rights.
 *
 * @return void
 */
function formulize_remove_stray_registered_user_edit_form_permissions() {
	global $xoopsDB;
	$gperm_handler = xoops_gethandler('groupperm');
	if($gperm_handler->checkRight("module_admin", getFormulizeModId(), XOOPS_GROUP_USERS, 1) !== false) {
		return;
	}
	$sql = "DELETE FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_name='edit_form' AND gperm_modid=".getFormulizeModId()." AND gperm_groupid=".XOOPS_GROUP_USERS;
	if(!$xoopsDB->queryF($sql)) {
		print "Error: could not remove stray 'edit form' permissions from Registered Users.<br>".$xoopsDB->error()."<br>";
	}
}

/**
 * Make sure the screen templates folder exists for the theme the site is currently using.
 *
 * A site that switches theme has no folder for the new one until something creates it, and screens
 * fall back to the system defaults in the meantime.
 *
 * @return void
 */
function formulize_ensure_screen_template_folder_for_theme() {
	global $xoopsConfig;
	$screenpathname = XOOPS_ROOT_PATH."/modules/formulize/templates/screens/".$xoopsConfig['theme_set']."/";
	if(!file_exists($screenpathname)) {
		recurse_copy(XOOPS_ROOT_PATH."/modules/formulize/templates/screens/default/", $screenpathname);
	}
}

/**
 * Warn about custom elementcontainero.php files that predate the $elementClass variable.
 *
 * These are files the site's own people maintain, and a copy of an old one can arrive at any time,
 * so this is worth checking on every update rather than once. The Office Use Only layout at the
 * bottom of a form does not work on screens whose container file lacks the variable.
 *
 * @return void
 */
function formulize_warn_about_element_containers_missing_element_class() {
	$missingElementClass = [];
	$baseElementContainerDir = XOOPS_ROOT_PATH . '/modules/formulize/templates/screens';
	if (!is_dir($baseElementContainerDir)) {
		return;
	}
	$ecoDirectory = new RecursiveDirectoryIterator($baseElementContainerDir);
	$iterator = new RecursiveIteratorIterator($ecoDirectory);
	$regex = new RegexIterator($iterator, '/^.+\/elementcontainero\.php$/i', RecursiveRegexIterator::GET_MATCH);
	foreach (array_keys(iterator_to_array($regex)) as $ecoFilePath) {
		$contents = file_get_contents($ecoFilePath);
		if (strlen($contents) > 0 AND strpos($contents, '$elementClass') === false) {
			$missingElementClass[] = $ecoFilePath;
		}
	}
	if(count($missingElementClass) > 0) {
		$ecoMessage = "You have one or more 'elementcontainero.php' files which are missing the \$elementClass variable used in Formulize 8.1+.\n\nThe normal usage looks like this:\n\nprint \"<div class='form-row \$elementClass' \$style id='\$elementContainerId'>\";\n\nThe 'Office Use Only' layout at the bottom of forms will not work correctly on the affected screens until these files are updated.\n\nThe affected files are:\n\n" . implode("\n", $missingElementClass);
		echo '<script>alert(' . json_encode($ecoMessage) . ');</script>';
	}
}

/**
 * Repair custom openlisttemplate.php files that still carry functions which have moved into Formulize.
 *
 * getAriaSort() now lives in functions.php, so a custom template that still defines it causes a fatal
 * "cannot redeclare function" error. clickableSortLink() changed to support sorting by several columns,
 * and a template with the old version keeps the old behaviour. Like the elementcontainero.php check,
 * these are files the site's own people maintain, and a copy of an old one can arrive at any time, so
 * this runs on every update rather than once.
 *
 * @return void
 */
function formulize_repair_custom_openlisttemplate_functions() {
	if (!is_dir(XOOPS_ROOT_PATH . '/modules/formulize/templates/screens')) {
		return;
	}
	// Scan custom per-screen openlisttemplate.php files for getAriaSort() definition.
	// This function was moved to functions.php; if it remains in a custom template file it will
	// cause a PHP fatal error ("cannot redeclare function") that breaks the site.
	// Files in numeric subdirectories are user-customised copies that survive the update patch;
	// files in named dirs (e.g. "default") are replaced by the patch and do not need handling.
	$ariaAutoFixed = [];
	$ariaMismatch = [];
	$ariaNotWritable = [];
	$ariaCanonicalRaw = <<<'CANONICAL'
function getAriaSort($elementHandle) {
	$sort = isset($_POST['sort']) ? $_POST['sort'] : null;
	$order = isset($_POST['order']) ? $_POST['order'] : null;

	if ($sort == $elementHandle) {
		if ($order == 'SORT_ASC') {
			return 'ascending';
		}
		if ($order == 'SORT_DESC') {
			return 'descending';
		}
	}
	return 'none';
}
CANONICAL;
	$ariaCanonical = preg_replace('/\s+/', '', $ariaCanonicalRaw);
	$ariaScreensDir = XOOPS_ROOT_PATH . '/modules/formulize/templates/screens';
	$ariaDirectory = new RecursiveDirectoryIterator($ariaScreensDir, RecursiveDirectoryIterator::SKIP_DOTS);
	$ariaIter = new RecursiveIteratorIterator($ariaDirectory);
	$ariaRegex = new RegexIterator($ariaIter, '/^.+\/openlisttemplate\.php$/i', RecursiveRegexIterator::GET_MATCH);
	$ariaFiles = array_keys(iterator_to_array($ariaRegex));
	foreach ($ariaFiles as $ariaFilePath) {
		if (!is_numeric(basename(dirname($ariaFilePath)))) {
			continue; // skip named dirs (default, Anari/default, etc.) — replaced by the patch
		}
		$ariaContent = file_get_contents($ariaFilePath);
		if ($ariaContent === false || strlen($ariaContent) === 0) {
			continue;
		}
		$funcPos = strpos($ariaContent, 'function getAriaSort(');
		if ($funcPos === false) {
			continue;
		}
		// Walk back to include any preceding docblock
		$blockStart = $funcPos;
		$beforeFunc = substr($ariaContent, 0, $funcPos);
		if (substr(rtrim($beforeFunc), -2) === '*/') {
			$docStart = strrpos($beforeFunc, '/**');
			if ($docStart !== false) {
				$blockStart = $docStart;
			}
		}
		// Include leading whitespace on the line that starts the block
		$lineStart = strrpos(substr($ariaContent, 0, $blockStart), "\n");
		$blockStart = ($lineStart !== false) ? $lineStart + 1 : 0;
		// Find the matching closing brace by counting depth
		$bracePos = strpos($ariaContent, '{', $funcPos);
		if ($bracePos === false) {
			continue;
		}
		$depth = 0;
		$blockEnd = $bracePos;
		for ($i = $bracePos, $ariaLen = strlen($ariaContent); $i < $ariaLen; $i++) {
			if ($ariaContent[$i] === '{') {
				$depth++;
			} elseif ($ariaContent[$i] === '}') {
				$depth--;
				if ($depth === 0) {
					$blockEnd = $i;
					break;
				}
			}
		}
		// Normalize the function body (without docblock) for comparison
		$funcBody = substr($ariaContent, $funcPos, $blockEnd - $funcPos + 1);
		$funcNormalized = preg_replace('/\s+/', '', $funcBody);
		if (!is_writable($ariaFilePath)) {
			$ariaNotWritable[] = $ariaFilePath;
			continue;
		}
		// Consume trailing newlines after the closing brace
		$afterBlock = $blockEnd + 1;
		while ($afterBlock < strlen($ariaContent) && ($ariaContent[$afterBlock] === "\n" || $ariaContent[$afterBlock] === "\r")) {
			$afterBlock++;
		}
		if ($funcNormalized === $ariaCanonical) {
			// Canonical version — safe to delete entirely
			$newContent = substr($ariaContent, 0, $blockStart) . substr($ariaContent, $afterBlock);
			file_put_contents($ariaFilePath, $newContent);
			$ariaAutoFixed[] = $ariaFilePath;
		} else {
			// Modified version — wrap in function_exists guard and alert the user
			$fullBlock = substr($ariaContent, $blockStart, $blockEnd - $blockStart + 1);
			$wrapped = "if (!function_exists('getAriaSort')) {\n" . $fullBlock . "\n}";
			$newContent = substr($ariaContent, 0, $blockStart) . $wrapped . substr($ariaContent, $afterBlock);
			file_put_contents($ariaFilePath, $newContent);
			$ariaMismatch[] = $ariaFilePath;
		}
	}
	if (count($ariaMismatch) > 0) {
		$ariaMessage = "IMPORTANT: One or more custom 'openlisttemplate.php' files contained a modified version of the getAriaSort() function, which has been moved to functions.php.\n\nThe custom version has been DEACTIVATED (wrapped in a function_exists guard) to prevent a PHP fatal error. The built-in version of getAriaSort() is now active. If your custom version had important changes, review functions.php and merge your changes there.\n\nThe affected files are:\n\n" . implode("\n", $ariaMismatch);
		echo '<script>alert(' . json_encode($ariaMessage) . ');</script>';
	}
	if (count($ariaNotWritable) > 0) {
		$ariaWriteMessage = "BREAKING: The following custom 'openlisttemplate.php' files still contain the getAriaSort() function, which has been moved to functions.php. These files could not be automatically patched (not writable by the web server). Your site will produce PHP fatal errors until these files are manually updated.\n\nFor each file, either remove the getAriaSort() function definition entirely (and migrate important changes to functions.php), or wrap it in:\n\nif (!function_exists('getAriaSort')) { ... }\n\nThe affected files are:\n\n" . implode("\n", $ariaNotWritable);
		echo '<script>alert(' . json_encode($ariaWriteMessage) . ');</script>';
	}

	// Scan custom per-screen openlisttemplate.php files for the old version of clickableSortLink().
	// The function signature is the same but the implementation changed to support multi-column sorting.
	// If the file has the old canonical version, replace it automatically.
	// If the file has a customised version, leave it alone but warn the user.
	$sortLinkAutoFixed = [];
	$sortLinkMismatch = [];
	$sortLinkNotWritable = [];
	$sortLinkOldCanonicalRaw = <<<'OLDCANONICAL'
function clickableSortLink($elementHandle, $clickableContent) {

	// get current sorting element and order
	$sort = isset($_POST['sort']) ? $_POST['sort'] : null;
	$order = isset($_POST['order']) ? $_POST['order'] : null;

	// setup containers for the clickable item
	$clickableSortLink = "
		<div style='padding-right:20px;'>
			<a style='display:flex;' href='' alt='"._formulize_DE_SORTTHISCOL."' title='"._formulize_DE_SORTTHISCOL."' onclick='javascript:sort_data(\"$elementHandle\");return false;'>
				<div>$clickableContent</div>
				<div style='min-width:15px; padding-left:5px;' aria-hidden='true'>";

					// if the element is the current sorting element, add an icon to show this
					if($elementHandle == $sort) {
						$iconClass = $order == "SORT_DESC" ? "fas fa-sort-amount-down" : "fas fa-sort-amount-up";
						$clickableSortLink .= "<i class='$iconClass'></i>";
					}

				// close the markup
				$clickableSortLink .= "
				</div>
			</a>
		</div>";

	return $clickableSortLink;
}
OLDCANONICAL;
	$sortLinkOldCanonical = preg_replace('/\s+/', '', $sortLinkOldCanonicalRaw);
	$sortLinkNewVersion = <<<'NEWVERSION'
/**
 * Generate the markup for the clickable sort links in the column headers, including the appropriate sort icon and tooltip text based on the current sort state.
 * The link will have an onclick handler that calls the sort_data JavaScript function with the element handle and whether the shift key was held (for multi-column sorting).
 * @param string elementHandle - the handle of the element for the column header we are generating the link for
 * @param string clickableContent - the text or markup that will be displayed to the user as the clickable thing on screen
 * @return string The HTML markup for the clickable sort link, including the appropriate sort icon and tooltip text based on the current sort state.
 */
function clickableSortLink($elementHandle, $clickableContent) {

	list($title, $icon) = getSortTitleAndIcon($elementHandle);

	return "
		<div class='sort-link-wrapper'>
			<a href='' alt='" . htmlspecialchars($title) . "' title='" . htmlspecialchars($title) . "' onclick='javascript:sort_data(\"$elementHandle\", event.shiftKey);return false;'>
				<div>$clickableContent</div>
				<div class='sort-link-icon' aria-hidden='true'>$icon</div>
			</a>
		</div>";
}
NEWVERSION;
	// Re-use the same files array from the getAriaSort scan above
	foreach ($ariaFiles as $sortLinkFilePath) {
		if (!is_numeric(basename(dirname($sortLinkFilePath)))) {
			continue;
		}
		$sortLinkContent = file_get_contents($sortLinkFilePath);
		if ($sortLinkContent === false || strlen($sortLinkContent) === 0) {
			continue;
		}
		$slFuncPos = strpos($sortLinkContent, 'function clickableSortLink(');
		if ($slFuncPos === false) {
			continue;
		}
		// Walk back to include any preceding comment (// or /**)
		$slBlockStart = $slFuncPos;
		$slBeforeFunc = substr($sortLinkContent, 0, $slFuncPos);
		$slBeforeTrimmed = rtrim($slBeforeFunc);
		if (substr($slBeforeTrimmed, -2) === '*/') {
			$slDocStart = strrpos($slBeforeFunc, '/**');
			if ($slDocStart !== false) {
				$slBlockStart = $slDocStart;
			}
		} elseif (substr($slBeforeTrimmed, -1) !== '') {
			// Check for // line comment on the immediately preceding line
			$slLastNewline = strrpos($slBeforeTrimmed, "\n");
			$slPrecedingLine = ($slLastNewline !== false) ? substr($slBeforeTrimmed, $slLastNewline + 1) : $slBeforeTrimmed;
			if (strpos(ltrim($slPrecedingLine), '//') === 0) {
				// Walk back through consecutive // comment lines
				$slSearchPos = $slLastNewline;
				while ($slSearchPos > 0) {
					$slPrevNewline = strrpos(substr($slBeforeTrimmed, 0, $slSearchPos), "\n");
					$slLine = ($slPrevNewline !== false) ? substr($slBeforeTrimmed, $slPrevNewline + 1, $slSearchPos - $slPrevNewline - 1) : substr($slBeforeTrimmed, 0, $slSearchPos);
					if (strpos(ltrim($slLine), '//') === 0) {
						$slSearchPos = ($slPrevNewline !== false) ? $slPrevNewline : 0;
					} else {
						break;
					}
				}
				$slBlockStart = $slSearchPos + 1;
			}
		}
		// Include leading whitespace on the line that starts the block
		$slLineStart = strrpos(substr($sortLinkContent, 0, $slBlockStart), "\n");
		$slBlockStart = ($slLineStart !== false) ? $slLineStart + 1 : 0;
		// Find the matching closing brace
		$slBracePos = strpos($sortLinkContent, '{', $slFuncPos);
		if ($slBracePos === false) {
			continue;
		}
		$slDepth = 0;
		$slBlockEnd = $slBracePos;
		for ($i = $slBracePos, $slLen = strlen($sortLinkContent); $i < $slLen; $i++) {
			if ($sortLinkContent[$i] === '{') {
				$slDepth++;
			} elseif ($sortLinkContent[$i] === '}') {
				$slDepth--;
				if ($slDepth === 0) {
					$slBlockEnd = $i;
					break;
				}
			}
		}
		// Normalize the function body only (without any preceding comment) for comparison
		$slFuncBody = substr($sortLinkContent, $slFuncPos, $slBlockEnd - $slFuncPos + 1);
		$slFuncNormalized = preg_replace('/\s+/', '', $slFuncBody);
		if ($slFuncNormalized !== $sortLinkOldCanonical) {
			// Customised — leave file untouched, warn the user
			$sortLinkMismatch[] = $sortLinkFilePath;
			continue;
		}
		if (!is_writable($sortLinkFilePath)) {
			$sortLinkNotWritable[] = $sortLinkFilePath;
			continue;
		}
		// Consume trailing newlines after the closing brace
		$slAfterBlock = $slBlockEnd + 1;
		while ($slAfterBlock < strlen($sortLinkContent) && ($sortLinkContent[$slAfterBlock] === "\n" || $sortLinkContent[$slAfterBlock] === "\r")) {
			$slAfterBlock++;
		}
		// Replace the old block with the new version
		$newContent = substr($sortLinkContent, 0, $slBlockStart) . $sortLinkNewVersion . "\n" . substr($sortLinkContent, $slAfterBlock);
		file_put_contents($sortLinkFilePath, $newContent);
		$sortLinkAutoFixed[] = $sortLinkFilePath;
	}
	if (count($sortLinkMismatch) > 0) {
		$sortLinkMismatchMessage = "NOTE: One or more custom 'openlisttemplate.php' files contain a customised version of the clickableSortLink() function. These files have NOT been modified.\n\nTo enable multi-column sorting on these screens, update the clickableSortLink() function in each file to match the new version found in the default template:\n\nmodules/formulize/templates/screens/default/listOfEntries/openlisttemplate.php\n\nThe affected files are:\n\n" . implode("\n", $sortLinkMismatch);
		echo '<script>alert(' . json_encode($sortLinkMismatchMessage) . ');</script>';
	}
	if (count($sortLinkNotWritable) > 0) {
		$sortLinkWriteMessage = "NOTE: The following custom 'openlisttemplate.php' files contain the old version of clickableSortLink() and could not be automatically updated (not writable by the web server). Multi-column sorting will not work on these screens until the files are manually updated.\n\nReplace the clickableSortLink() function with the new version found in:\n\nmodules/formulize/templates/screens/default/listOfEntries/openlisttemplate.php\n\nThe affected files are:\n\n" . implode("\n", $sortLinkNotWritable);
		echo '<script>alert(' . json_encode($sortLinkWriteMessage) . ');</script>';
	}
}

/**
 * Add to the Primary Relationship every connection between forms that is missing from it.
 *
 * The Primary Relationship is built once, by createPrimaryRelationship(), when a site is upgraded to
 * the version that introduced it. Nothing has ever revisited that result afterwards, so a site whose
 * Primary Relationship came out incomplete stays that way forever, and the symptoms are indirect and
 * hard to attribute: relationships that quietly resolve against the wrong form, derived value formulas
 * that cannot see elements in their own form, screens that cannot reach data they should be able to.
 *
 * It could come out incomplete for more than one reason. Versions before the fix in
 * populatePrimaryRelationship deleted an invalid link while still reading the list of links, which
 * overwrote the result handle being read and abandoned the rest of the list, so a single stale link
 * early in the table stopped every later link from being considered. The set of element types that
 * count as linked elements has also grown since, so a site built before that grew is missing whatever
 * the newer types would have contributed.
 *
 * Rather than try to detect any particular cause, this simply asks for the whole Primary Relationship
 * to be worked out again and adds whatever is not already there. It is safe to run on every update:
 * populatePrimaryRelationship is idempotent once the existing links have been declared to it via
 * primePrimaryRelationshipLinkPairs(), and on a site with a complete Primary Relationship it adds
 * nothing and prints nothing.
 *
 * Two deliberate differences from building a Primary Relationship from scratch:
 * - Invalid links found along the way are reported, not deleted. Deleting them is reasonable while
 *   setting up the Primary Relationship in the first place; quietly deleting relationship links on a
 *   live system during a routine update is not.
 * - A connection that is already present keeps its current unified delete setting unless one of the
 *   links feeding it says that setting should be off, in which case it is turned off. So a repair can
 *   turn cascading deletion off, matching the rule that it only applies when every link connecting the
 *   two forms asks for it, but it will never turn cascading deletion on.
 *
 * @return boolean False only if the Primary Relationship could not be read or rebuilt, which should
 *   stop the update; true otherwise, including when there was nothing to do.
 */
function formulize_repair_primary_relationship() {
	global $linkForms;

	// No Primary Relationship yet means there is nothing to repair. 000_schema_migrations creates one
	// from scratch on a site old enough not to have one, and it runs before this patch, so by this point
	// any site that should have one does.
	if (!primaryRelationshipExists()) {
		return true;
	}

	// insertLinkIntoPrimaryRelationship() collects the forms it touches in this global
	if (!isset($linkForms) OR !is_array($linkForms)) {
		$linkForms = array();
	}

	// Declare the links that already exist, so they are not inserted a second time
	if (!primePrimaryRelationshipLinkPairs()) {
		echo '<p>Error: could not read the existing Primary Relationship links. Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>';
		return false;
	}

	$report = populatePrimaryRelationship(false);

	if ($report['error']) {
		echo '<p>Error: could not check the Primary Relationship for missing connections: ' . $report['error']
			. '<br>Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>';
		return false;
	}

	if ($report['added']) {
		echo '<h3>Connections added to the Primary Relationship:</h3>';
		echo '<p>Your Primary Relationship was missing ' . count($report['added'])
			. (count($report['added']) == 1 ? ' connection' : ' connections')
			. ' that should have been in it. ' . (count($report['added']) == 1 ? 'It has' : 'They have')
			. ' been added. Forms connected this way can now find each other in derived value formulas,'
			. ' screens, and anywhere else the Primary Relationship is used.</p><ul>';
		foreach ($report['added'] as $line) {
			echo '<li>' . htmlspecialchars($line) . '</li>';
		}
		echo '</ul>';
	}

	if ($report['invalid']) {
		echo '<h3>Invalid relationship links found:</h3>';
		echo '<p>These links refer to elements that no longer exist, or claim a connection that the'
			. ' elements do not actually have. They have been left alone, and are not part of the Primary'
			. ' Relationship. You can delete them in the relationships area of the admin interface.</p><ul>';
		foreach ($report['invalid'] as $line) {
			echo '<li>' . htmlspecialchars($line) . '</li>';
		}
		echo '</ul>';
	}

	if ($report['problems']) {
		echo '<h3>Problems encountered while checking the Primary Relationship:</h3><ul>';
		foreach ($report['problems'] as $line) {
			echo '<li>' . htmlspecialchars($line) . '</li>';
		}
		echo '</ul>';
	}

	return true;
}

/**
 * Rename every element handle that contains a hyphen (e.g. "my-handle" → "my_handle").
 *
 * Hyphens are illegal in PHP variable names, so hyphenated handles silently break
 * derived-value formulas, on_before_save, on_after_save, and on_delete code that
 * reference elements as variables. This migration is idempotent: elements whose
 * handles contain no hyphens are left untouched on repeat runs.
 *
 * Collision policy: if "my_handle" already exists in the same form, the new name
 * becomes "my_handle_2", "my_handle_3", etc., until a free slot is found.
 *
 * All structural updates (captions, screen maps, saved views, code files, cache)
 * are delegated to formulizeElementsHandler::renameElementResources(), which is the
 * same path used when an admin renames a handle through the UI.
 *
 * Userland code that cannot be auto-updated (on_before_save / on_after_save /
 * on_delete / advanced calculations) is reported via an alert if it still contains
 * any of the old handle strings.
 */
function formulize_migrate_hyphenated_handles() {
    global $xoopsDB;

    // Find all elements whose handles contain a hyphen
    $res = $xoopsDB->queryF(
        "SELECT ele_id, ele_handle, id_form FROM " . $xoopsDB->prefix('formulize') . " WHERE ele_handle LIKE '%-%'"
    );
    if (!$res || $xoopsDB->getRowsNum($res) == 0) {
        return;
    }

    // Build rename map: ele_id => ['old' => ..., 'new' => ..., 'fid' => ...]
    // 'new' is the name asked for, not necessarily the name granted: what an element can actually be
    // called is settled per element in the loop below, because the answer depends on what has already
    // been renamed by the time that element's turn comes.
    $renameMap = array();
    while ($row = $xoopsDB->fetchArray($res)) {
        $renameMap[intval($row['ele_id'])] = array(
            'old' => $row['ele_handle'],
            'new' => str_replace('-', '_', $row['ele_handle']),
            'fid' => intval($row['id_form']),
        );
    }

    if (empty($renameMap)) {
        return;
    }

    print "<h3>Renaming element handles containing hyphens:</h3>\n";

    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $oldHandles      = array();

    foreach ($renameMap as $eleId => $rename) {
        $element = $element_handler->get($eleId);
        if (!$element) {
            print "<p>Error: could not load element ele_id=" . intval($eleId) . " for renaming.</p>";
            continue;
        }
        $element->setVar('ele_handle', $rename['new']);
        // Settle on the final name before anything is renamed to it. insert() runs the handle through
        // validateElementHandle anyway; running it here first means we know what the element is going to
        // be called, rather than assuming the hyphens simply became underscores. They may not have:
        // handles are unique across every form rather than within one (isElementHandleUnique queries
        // formulize without an id_form condition), the metadata names are reserved as well, and a handle
        // is truncated to 59 characters - so "creation-uid" or a name another form already uses comes
        // back suffixed. Running the same check again inside insert() settles on the same answer, since
        // an element is excluded from its own uniqueness check.
        $newHandle = $element_handler->validateElementHandle($element);
        // Move the data table column before the element definition is saved. If it cannot be moved, leave
        // the handle alone too, so the definition never ends up pointing at a column that did not follow it.
        $columnError = formulize_rename_hyphenated_data_column($rename['fid'], $rename['old'], $newHandle);
        if ($columnError !== '') {
            print "<p>Error renaming the data table field for ele_id=" . intval($eleId) . ": " . htmlspecialchars($columnError) . " The handle has been left as <code>" . htmlspecialchars($rename['old']) . "</code>.</p>";
            continue;
        }
        if (!$element_handler->insert($element, true)) {
            print "<p>Error renaming ele_id=" . intval($eleId) . ": " . htmlspecialchars($xoopsDB->error()) . "</p>";
            continue;
        }
        print "<p>Renamed: <code>" . htmlspecialchars($rename['old']) . "</code> &rarr; <code>" . htmlspecialchars($newHandle) . "</code> (form_id=" . $rename['fid'] . ")</p>\n";
        $oldHandles[] = $rename['old'];
        $element_handler->renameElementResources($element, $rename['old']);
    }

    // Alert for userland code that cannot be auto-updated.
    // Scan on_before_save / on_after_save / on_delete and advanced calculations
    // for any surviving occurrences of the old handle strings.
    $alertLines = array();

    $formProcRes = $xoopsDB->queryF(
        "SELECT id_form, on_before_save, on_after_save, on_delete FROM " . $xoopsDB->prefix('formulize_id')
    );
    if ($formProcRes) {
        while ($row = $xoopsDB->fetchArray($formProcRes)) {
            $code = $row['on_before_save'] . "\n" . $row['on_after_save'] . "\n" . $row['on_delete'];
            foreach ($oldHandles as $oldH) {
                if (strpos($code, $oldH) !== false) {
                    $alertLines[] = "Form ID " . intval($row['id_form']) . ": on_before_save / on_after_save / on_delete code references \"" . $oldH . "\"";
                    break;
                }
            }
        }
    }

    $acTableRes = $xoopsDB->queryF("SHOW TABLES LIKE '" . $xoopsDB->prefix('formulize_advanced_calculations') . "'");
    if ($acTableRes && $xoopsDB->getRowsNum($acTableRes) > 0) {
        $acRes = $xoopsDB->queryF(
            "SELECT acid, fid, input, output, steps FROM " . $xoopsDB->prefix('formulize_advanced_calculations')
        );
        if ($acRes) {
            while ($row = $xoopsDB->fetchArray($acRes)) {
                $allCode = $row['input'] . "\n" . $row['output'] . "\n" . $row['steps'];
                foreach ($oldHandles as $oldH) {
                    if (strpos($allCode, $oldH) !== false) {
                        $alertLines[] = "Form ID " . intval($row['fid']) . " (advanced calculation ID " . intval($row['acid']) . "): calculation code references \"" . $oldH . "\"";
                        break;
                    }
                }
            }
        }
    }

    if (!empty($alertLines)) {
        $msg  = "ATTENTION: Element handles containing hyphens were renamed during this update, "
              . "but the following userland code still references the old handle names. "
              . "These references must be updated manually before the affected code will work correctly:\n\n"
              . implode("\n", $alertLines) . "\n\n"
              . "In each case, replace the old handle (e.g. \$my-handle or {my-handle}) "
              . "with the new underscore form (e.g. \$my_handle or {my_handle}).";
        echo '<script>alert(' . json_encode($msg) . ');</script>';
    }
}

/**
 * Rename the data table column that belongs to an element whose handle is losing its hyphens.
 *
 * This is the work formulizeFormsHandler::updateField() would normally do, done here directly.
 * updateField cannot be used: it runs both names through sanitize_handle_name(), which turns the
 * hyphenated old name into the new one, so the rename becomes a silent no-op (or, when a data type
 * is supplied to get past the equal-names shortcut, an ALTER against a column that does not exist).
 * The old name has to reach the ALTER verbatim, and that is true only of this migration, so the
 * statement is issued here rather than adding a bypass to the shared method for one caller.
 *
 * Idempotent, and safe on elements that have no column of their own: a column already sitting under
 * the new name, or absent under both names (content elements), is left alone.
 *
 * @param int $fid The form the element belongs to
 * @param string $oldHandle The handle as it stands in the data table, hyphens included
 * @param string $newHandle The handle it is being renamed to
 * @return string Empty string when there is nothing left to do, otherwise what stopped it
 */
function formulize_rename_hyphenated_data_column($fid, $oldHandle, $newHandle) {
    global $xoopsDB;

    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    if (!$formObject = $form_handler->get(intval($fid))) {
        return "could not load form " . intval($fid) . ".";
    }
    $formHandle = $formObject->getVar('form_handle');
    if ($formHandle === '') {
        // Form handles arrive in 000_schema_migrations, which runs before this file, so by this point
        // every form should have one. An empty handle here means that step did not do its work for this
        // form. Nothing is renamed for it: the handle stays hyphenated, matching its column, so the site
        // is left consistent and the rename can be completed once the form handle is sorted out.
        return "form " . intval($fid) . " has no form handle, so its data table cannot be located. Its element handles have been left hyphenated. Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.";
    }

    // A column name is not escapable inside backticks, so filter it instead. Hyphens are kept, which
    // is the whole point here, and everything outside the character set handles are built from goes.
    $oldColumn = preg_replace('/[^a-zA-Z0-9_-]/', '', $oldHandle);
    $newColumn = preg_replace('/[^a-zA-Z0-9_-]/', '', $newHandle);
    if ($oldColumn === '' OR $newColumn === '' OR $oldColumn === $newColumn) {
        return '';
    }

    $tables = array($xoopsDB->prefix("formulize_" . $formHandle));
    $revisionsTable = $xoopsDB->prefix("formulize_" . $formHandle . "_revisions");
    // Checked directly rather than through formulizeFormsHandler::revisionsTableExists(), which creates
    // the revisions table when revisions-for-all-forms is on. A table built mid-migration would be built
    // from the element definitions, and so would arrive with columns under names the data table does not
    // have yet. Nothing here needs a revisions table that does not already exist.
    $revisionsRes = $xoopsDB->queryF("SHOW TABLES LIKE '" . formulize_db_escape($revisionsTable) . "'");
    if ($revisionsRes AND $xoopsDB->getRowsNum($revisionsRes) > 0) {
        $tables[] = $revisionsTable;
    }

    foreach ($tables as $table) {
        // The whole column list is read, instead of a SHOW COLUMNS ... LIKE for the one name, because
        // underscores are single character wildcards in a LIKE pattern and every handle is full of them.
        $columns = array();
        if (!$colRes = $xoopsDB->queryF("SHOW COLUMNS FROM `$table`")) {
            return "could not read the columns of $table: " . $xoopsDB->error();
        }
        while ($colRow = $xoopsDB->fetchArray($colRes)) {
            $columns[$colRow['Field']] = $colRow;
        }
        if (!isset($columns[$oldColumn])) {
            continue; // already renamed on an earlier run, or an element type that stores no data
        }
        if (isset($columns[$newColumn])) {
            return "$table has columns named both `$oldColumn` and `$newColumn`; they must be reconciled by hand.";
        }
        // Rebuild the definition rather than passing the type alone, so that a nullable column does not
        // come back NOT NULL and a column with a default does not come back without one.
        $definition = $columns[$oldColumn]['Type'];
        $definition .= ($columns[$oldColumn]['Null'] == 'YES') ? ' NULL' : ' NOT NULL';
        if ($columns[$oldColumn]['Default'] !== null) {
            $definition .= ' DEFAULT ' . $xoopsDB->quoteString($columns[$oldColumn]['Default']);
        }
        if (!$xoopsDB->queryF("ALTER TABLE `$table` CHANGE `$oldColumn` `$newColumn` $definition")) {
            return "could not rename `$oldColumn` to `$newColumn` in $table: " . $xoopsDB->error();
        }
    }

    return '';
}



