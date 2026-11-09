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

	// Rename element handles, and the fields and code files named after them, that still have capitals, hyphens or
	// other characters handles cannot have any more, before running other schema work. Finds nothing to do on
	// repeat runs once everything is in shape, and tries again whatever it could not do last time.
	if (!formulize_normalize_element_handles($prev_dbversion)) {
		return false;
	}
	// Turning encryption on or off used to rename an element's field in the revisions table without putting it back
	formulize_repair_revisions_fields_left_by_encryption();

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
	formulize_remove_empty_screen_templates();
	formulize_warn_about_element_containers_missing_element_class();
	formulize_repair_custom_openlisttemplate_functions();

	// The PHP the site runs on can change between updates, so check for the optional image support every time
	formulize_warn_about_missing_tokenizer();
	formulize_warn_about_missing_image_support();

	// Backslashes in static content and default values used to be removed when they were shown, and no longer are,
	// so point out the ones that are there, every time, until someone has looked at them
	formulize_warn_about_backslashes_in_authored_content();

	// The web server config can change between updates too, so check every time that the uploads folder,
	// where files people upload are stored, cannot be used to run programs.
	formulize_warn_about_executable_uploads_folder();

	// Keep the timezone list in step with PHP's timezone database, which changes with the PHP
	// version the site runs on rather than with anything Formulize does.
	formulize_update_timezone_options($xoopsDB);

	// Keep the choices in the SMS provider setting in step with the provider classes on disk, so a new
	// provider becomes selectable just by adding its class file to libraries/icms/messaging/sms/.
	if (!formulize_update_sms_provider_options($xoopsDB)) {
		echo '<p>Warning: could not update the list of SMS providers: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
	}

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
 * Remove the screen template files that have nothing in them, and the screens' template folders that leaves
 * empty. Mark the ones that hold only the opening PHP tag as blank on purpose.
 *
 * A screen whose template has nothing in it has no template of its own and is drawn with the default, the same
 * as when there is no file. Saving a screen's templates leaves no files like that (see
 * formulizeScreen::writeTemplateFile), but earlier versions wrote them for every new screen, and for every
 * template box saved empty.
 *
 * A template holding only the opening PHP tag used to be drawn as it is, showing nothing in its place, so a
 * screen could have been made that way to keep the default out. Formulize now reads it as no template, and
 * draws the default, so a comment is added to keep those screens as they were. (Sites coming up from below
 * dbversion 18 had these emptied first, by emptyTemplateFixer() in 000_schema_migrations, as they always have.)
 *
 * A template screen's code.php is removed too when it has nothing in it, or only the opening PHP tag, and its
 * template.html when it has nothing in it: neither has a default it could be keeping out, and the screen is drawn
 * from whichever of the two it has. A screen left with neither shows that it has no code or template, where it
 * used to show nothing.
 *
 * Runs on every update, to catch them on every site, including any that come back with a copy of a site's files.
 *
 * @return void
 */
function formulize_remove_empty_screen_templates() {
	include_once XOOPS_ROOT_PATH.'/modules/formulize/class/screen.php';
	$removed = 0;
	$markedBlank = array();
	$notMarked = array();
	foreach((array) glob(XOOPS_ROOT_PATH.'/modules/formulize/templates/screens/*/*', GLOB_ONLYDIR) as $screenFolder) {
		if(!ctype_digit(basename($screenFolder))) {
			continue; // a theme's default templates, not one screen's own
		}
		foreach(array_merge((array) glob($screenFolder.'/*.php'), (array) glob($screenFolder.'/template.html')) as $templateFile) {
			if(!is_file($templateFile)) {
				continue;
			}
			$template = file_get_contents($templateFile);
			if(trim($template) !== '' AND (basename($templateFile) == 'template.html' OR !formulize_screenTemplateIsEmpty($template))) {
				continue;
			}
			$shortName = str_replace(XOOPS_ROOT_PATH.'/', '', $templateFile);
			if(trim($template) === '' OR basename($templateFile) == 'code.php') {
				if(unlink($templateFile)) {
					$removed++;
				}
			} elseif(file_put_contents($templateFile, "<?php\n// Blank on purpose, so the screen shows nothing here instead of the default template\n") !== false) {
				$markedBlank[] = $shortName;
			} else {
				$notMarked[] = $shortName;
			}
		}
		formulize_removeScreenTemplateFolderIfEmpty($screenFolder);
	}
	if($removed) {
		print "Removed $removed empty screen template files. result: OK<br>";
	}
	if($markedBlank) {
		print "These screen templates held nothing but &lt;?php, which shows nothing in their place. A comment now says they are blank on purpose, so they keep doing that. result: OK<br>"
			.implode("<br>", array_map('htmlspecialchars', $markedBlank))."<br>";
	}
	if($notMarked) {
		print "Warning: these screen templates hold nothing but &lt;?php, and could not be changed (not writable by the web server). Their screens will now show the default template in their place. To keep them showing nothing, add a comment after the &lt;?php, such as // Blank on purpose.<br>"
			.implode("<br>", array_map('htmlspecialchars', $notMarked))."<br>";
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
 * Warn the webmaster if PHP lacks the optional image support that file upload elements use for thumbnails.
 *
 * Both are parts of the server's PHP installation, not Formulize, so all we can do is point them out.
 * Thumbnails work without them (see formulizeFileUploadElementHandler::getThumbnailUrl), just not as well.
 *
 * @return void
 */
function formulize_warn_about_missing_image_support() {
	$missing = array();
	if(!function_exists('imagecreatefromwebp') OR !function_exists('imagewebp')) {
		$missing[] = '<li><b>WebP support in the GD extension.</b> WebP images uploaded to forms appear at full size, scaled down by the browser, instead of as small thumbnails. Pages that show many WebP images will be slower to load. The images themselves upload and download normally.</li>';
	}
	if(!function_exists('exif_read_data')) {
		$missing[] = '<li><b>The exif extension.</b> Thumbnails of photos taken with phones and cameras may appear sideways or upside down. The photos themselves are not affected.</li>';
	}
	if($missing) {
		echo '<h3>Image support missing from PHP on this server:</h3>';
		echo '<p>Formulize will keep working, but images uploaded to forms will not display as well as they could, because the PHP installation on this server is missing:</p><ul>' . implode('', $missing) . '</ul>';
		echo '<p>To fix this, ask your hosting provider or server administrator to enable the items above for the version of PHP that runs this website. Then run the Formulize update again, and this message will no longer appear.</p>';
	}
}

/**
 * Warn if PHP on this server has no tokenizer extension. Formulize reads the code people write in it with
 * token_get_all() - to fill in the {handle} references in code (see formulize_bindCurlyReferencesInPHPCode) and to
 * update code when an element's handle changes (see formulizeElementHandleCode) - and without the extension those
 * stop with a fatal error. PHP includes it unless it was built without it, but some Linux distributions package it
 * separately.
 *
 * @return void
 */
function formulize_warn_about_missing_tokenizer() {
	if(!extension_loaded('tokenizer')) {
		echo '<h3>Required PHP extension missing on this server: tokenizer</h3>';
		echo '<p><b>Some parts of Formulize will stop with an error until it is installed:</b> forms with elements whose code refers to other elements, and changing an element&#039;s handle.</p>';
		echo '<p>To fix this, ask your hosting provider or server administrator to enable the tokenizer extension for the version of PHP that runs this website (on some systems it is a separate package, such as php-tokenizer). Then run the Formulize update again, and this message will no longer appear.</p>';
	}
}

/**
 * List the static content and the text box and textarea default values that have a backslash in them.
 *
 * Formulize used to remove backslashes from these when it showed them, so an admin who wanted one to appear may have
 * typed two, and those now show as two, or stop PHP code from running. Whether a backslash is meant can only be
 * decided by a person, so this only points them out. It runs on every update, because nothing changes them by
 * itself, and any of them could still be waiting for someone to look at it.
 *
 * @return void
 */
function formulize_warn_about_backslashes_in_authored_content() {
	$element_handler = xoops_getmodulehandler('elements', 'formulize');
	$form_handler = xoops_getmodulehandler('forms', 'formulize');
	$criteria = new icms_db_criteria_Item('ele_type', "('fullWidthContent', 'captionedContent', 'text', 'textarea')", 'IN');
	$found = array();
	foreach((array) $element_handler->getObjects($criteria) as $elementObject) {
		$contentKey = in_array($elementObject->getVar('ele_type'), array('fullWidthContent', 'captionedContent')) ? ELE_VALUE_STATICCONTENT_CONTENT : $elementObject->defaultValueKey;
		$ele_value = $elementObject->getVar('ele_value'); // includes the code from the element's code file, if it has one
		if(is_array($ele_value) AND isset($ele_value[$contentKey]) AND strpos((string) $ele_value[$contentKey], '\\') !== false) {
			$formObject = $form_handler->get($elementObject->getVar('id_form'));
			$found[] = '<li>' . htmlspecialchars($formObject ? $formObject->getVar('title', 'n') : '') . ': '
				. htmlspecialchars(strip_tags($elementObject->getVar('ele_caption', 'n'))) . ' (<code>' . htmlspecialchars($elementObject->getVar('ele_handle')) . '</code>, element id ' . intval($elementObject->getVar('ele_id')) . ')</li>';
		}
	}
	if($found) {
		echo '<h3>Backslashes in static content and default values:</h3>';
		echo '<p>Formulize used to remove backslashes (\\) from static content, and from the default values of text boxes and textareas, when it showed them. It doesn&#039;t any more, so they now appear exactly as they were typed. If backslashes were doubled in any of these to make one appear, the extra ones will now show, or the PHP code may not run.</p>';
		echo '<p>These elements have a backslash in them. Check each one, and change it if it needs changing:</p><ul>' . implode('', $found) . '</ul>';
		echo '<p>This list appears after every update, for as long as these elements have backslashes in them.</p>';
	}
}

/**
 * Warn if the web server will run programs saved in the uploads folder.
 *
 * Files that people upload to forms are stored in uploads. A server that executes code from there lets
 * someone upload a program and then run it, so this is a serious security problem. A hardened
 * uploads/.htaccess ships with Formulize to prevent it, but nginx and other servers ignore .htaccess, and
 * Apache ignores it when AllowOverride is off, so whether it is actually taking effect is checked live on
 * every update. Nothing is said unless execution is positively confirmed.
 *
 * @return void
 */
function formulize_warn_about_executable_uploads_folder() {
	include_once XOOPS_ROOT_PATH.'/modules/formulize/include/uploads_execution_probe.php';
	$uploadsDir = XOOPS_ROOT_PATH.'/uploads';
	if(formulize_probeUploadsExecution($uploadsDir, XOOPS_URL.'/uploads') !== 'executable') {
		return; // 'safe', or could not be confirmed - say nothing rather than raise a false alarm
	}
	$htaccessPath = $uploadsDir.'/.htaccess';
	echo '<h3>Security warning: the uploads folder can run programs</h3>';
	echo '<p>Files that people upload to your forms are stored in the <code>uploads</code> folder, and this '
		. 'server will <strong>run a program saved there</strong>. That means someone who can upload a file '
		. 'could upload a program and then run it on your server. This should be fixed before your site is used.</p>';
	if(is_file($htaccessPath)) {
		// the protection ships with Formulize, so if the folder still runs code, the server is ignoring it
		echo '<p>Formulize installs protection for this folder (the <code>.htaccess</code> file in it), but '
			. 'this server is not applying it:</p><ul>'
			. '<li><strong>Apache:</strong> allow <code>.htaccess</code> files to take effect for this folder, '
			. 'by setting <code>AllowOverride</code> to <code>FileInfo Limit</code> (or <code>All</code>) for the '
			. 'website in the server configuration.</li>'
			. '<li><strong>nginx or another server:</strong> <code>.htaccess</code> is ignored, so add a rule to '
			. 'the server configuration that stops the <code>uploads</code> folder running PHP or other scripts.</li>'
			. '</ul>';
	} else {
		// the shipped protection is missing, which is itself likely why the folder runs code
		echo '<p>The <code>.htaccess</code> file that normally protects this folder is missing. On an Apache '
			. 'server, create <code>uploads/.htaccess</code> with the following, then run the update again:</p>';
		echo '<pre>'.htmlspecialchars(formulize_uploadsHardeningHtaccess($uploadsDir)).'</pre>';
		echo '<p>On nginx or another server, add a rule to the server configuration that stops the '
			. '<code>uploads</code> folder running PHP or other scripts.</p>';
	}
	echo '<p>There is more about this in the <a href="https://formulize.org/documentation/deploying_a_website/" '
		. 'target="_blank" rel="noopener">deployment documentation</a>. Once it is fixed, run the Formulize update '
		. 'again, and this message will no longer appear.</p>';
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
		// Already guarded, either by a previous run of this repair or by hand, so it cannot cause a fatal
		// error. Wrapping it again would nest another guard around it on every update.
		if (preg_match('/function_exists\s*\(\s*[\'"]getAriaSort[\'"]\s*\)/i', $ariaContent)) {
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
	// Only custom templates for the Anari theme are checked, since the replacement is Anari's markup.
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
		// Custom templates live in screens/{theme}/{sid}/. The replacement is the Anari version, so only
		// Anari's custom templates get it. Other themes have their own markup for this function.
		if (basename(dirname(dirname($sortLinkFilePath))) !== 'Anari') {
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
		// Already passes the shift key to sort_data, so it supports multi-column sorting: the new version
		// put there by a previous run of this repair, or a copy of a current default/Lyris template.
		if (strpos($slFuncBody, 'shiftKey') !== false) {
			continue;
		}
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
 * Restore the revisions table fields that turning encryption on or off left under a temporary name.
 *
 * formulizeDataHandler::toggleEncryption() renames an element's field to <handle>qaz, puts a new <handle> field in
 * its place and drops the renamed one. Until 2026-11, the rename reached the revisions table but the rest did not,
 * so the revisions table was left with <handle>qaz and no <handle>. Saving a revision then fails, because that copies
 * every field of the data table into the revisions table by name, and so does changing the element's handle.
 *
 * The field is renamed back, and its history encrypted or decrypted to match the field in the data table, which is
 * what toggleEncryption() does to it now. Idempotent: once restored, there is no <handle>qaz left to find.
 */
function formulize_repair_revisions_fields_left_by_encryption() {
    global $xoopsDB;

    // Every revisions table field ending in qaz, in one query. LIKE wildcards are only a coarse filter here; the
    // exact names are checked below.
    $colRes = $xoopsDB->queryF(
        "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE '%revisions' AND COLUMN_NAME LIKE '%qaz'"
    );
    if (!$colRes) {
        print "<p>Error: could not look for revisions table fields left behind by turning encryption on or off: " . htmlspecialchars($xoopsDB->error()) . " Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>";
        return;
    }
    $leftovers = array(); // revisions table name => the fields ending in qaz
    while ($row = $xoopsDB->fetchArray($colRes)) {
        if (substr($row['TABLE_NAME'], -strlen('_revisions')) == '_revisions' AND substr($row['COLUMN_NAME'], -3) == 'qaz') {
            $leftovers[$row['TABLE_NAME']][] = $row['COLUMN_NAME'];
        }
    }
    if (!$leftovers) {
        return;
    }

    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $headingShown = false;
    $formRes = $xoopsDB->queryF("SELECT id_form, form_handle FROM " . $xoopsDB->prefix('formulize_id') . " WHERE form_handle != ''");
    while ($formRes AND $formRow = $xoopsDB->fetchArray($formRes)) {
        $dataTable = $xoopsDB->prefix('formulize_' . $formRow['form_handle']);
        $revisionsTable = $dataTable . '_revisions';
        // every element, including those that are not displayed, since any of them can have a field - and read fresh,
        // since earlier steps of the update can have changed the form's elements after it was cached
        if (!isset($leftovers[$revisionsTable]) OR !$formObject = $form_handler->get(intval($formRow['id_form']), true, true)) {
            continue;
        }
        $dataColumns = formulize_patch_table_columns($dataTable);
        $revisionsColumns = formulize_patch_table_columns($revisionsTable);
        if ($dataColumns === false OR $revisionsColumns === false) {
            print "<p>Error: could not read the fields of $dataTable or $revisionsTable: " . htmlspecialchars($xoopsDB->error()) . "</p>";
            continue;
        }
        $elementHandles = $formObject->getVar('elementHandles');
        foreach ($leftovers[$revisionsTable] as $leftover) {
            $field = substr($leftover, 0, -3);
            // Only what toggleEncryption() leaves: an element of this form whose field the data table has and the
            // revisions table does not, where the revisions table has the field's temporary name instead. A field whose
            // name is itself the handle of one of the form's elements, or a field of the data table, is that element's
            // own field and not a leftover, whatever its name ends in.
            if (in_array($leftover, $elementHandles, true) OR isset($dataColumns[$leftover])
                OR !in_array($field, $elementHandles, true) OR !isset($dataColumns[$field]) OR isset($revisionsColumns[$field])) {
                continue;
            }
            if (!$headingShown) {
                print "<h3>Restoring revisions table fields that turning encryption on or off had renamed:</h3>\n";
                $headingShown = true;
            }
            // The history is in the state the field was in before encryption was first turned on or off for it -
            // encrypted when it is a blob - and is brought into the state the field is in now
            $historyIsEncrypted = stripos($revisionsColumns[$leftover]['Type'], 'blob') !== false;
            $fieldIsEncrypted = stripos($dataColumns[$field]['Type'], 'blob') !== false;
            if ($historyIsEncrypted === $fieldIsEncrypted) {
                $definition = $revisionsColumns[$leftover]['Type'] . (($revisionsColumns[$leftover]['Null'] == 'YES') ? ' NULL' : ' NOT NULL');
                if ($revisionsColumns[$leftover]['Default'] !== null) {
                    $definition .= ' DEFAULT ' . $xoopsDB->quoteString($revisionsColumns[$leftover]['Default']);
                }
                $restored = $xoopsDB->queryF("ALTER TABLE `$revisionsTable` CHANGE `$leftover` `$field` $definition");
            } else {
                $restored = $form_handler->moveFieldValuesThroughEncryption($revisionsTable, $leftover, $field, $fieldIsEncrypted);
            }
            if ($restored) {
                print "<p>Restored the field <code>" . htmlspecialchars($field) . "</code> in $revisionsTable. result: OK</p>\n";
            } else {
                print "<p>Error: could not restore the field <code>" . htmlspecialchars($field) . "</code> in $revisionsTable, which is currently called <code>" . htmlspecialchars($leftover) . "</code>: " . htmlspecialchars($xoopsDB->error()) . " Revisions of entries in this form cannot be saved until it is restored. Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>\n";
            }
        }
    }
}

/**
 * The fields of a table, as SHOW COLUMNS describes them, keyed by their exact names - or false if they cannot be read.
 * @param string $table The prefixed table name
 * @return array|false
 */
function formulize_patch_table_columns($table) {
    global $xoopsDB;
    if (!$res = $xoopsDB->queryF("SHOW COLUMNS FROM `$table`")) {
        return false;
    }
    $columns = array();
    while ($row = $xoopsDB->fetchArray($res)) {
        $columns[$row['Field']] = $row;
    }
    return $columns;
}

/**
 * Bring every element handle, and everything named after one, into the shape handles have now.
 *
 * What a handle may look like has narrowed over time, and each time the handles already stored, and what was
 * named after them, were not all brought along:
 * - until 2024-10, a handle could hold almost anything. Since then it has been limited to letters, numbers,
 *   underscores and hyphens.
 * - in 2025-10, handles became lowercase. Every element object has reported a lowercase handle since then,
 *   because assignVar() runs it through sanitize_handle_name() on loading, while the element table, the
 *   element's field in its form's data table, its code files and the settings naming it could all still have
 *   the capitals. A textbox whose field still has them saves and is searchable, but opens empty, because
 *   loadValue() in formdisplay.php looks its value up by the lowercase handle.
 * - in 2026-07, hyphens became underscores, and until 2026-09 elements were renamed without their fields.
 *
 * So an element is misspelled when its stored handle is not what sanitize_handle_name() makes of it, or when
 * its field or one of its code files is named by a spelling that sanitizes to its handle without being it.
 * Each is renamed to the sanitized handle - with a suffix, if that is already taken - along with everything
 * that refers to it under any of the spellings found. All of that is the same rename an admin makes through the
 * UI: updateField() for the field, and formulizeElementsHandler::renameElementResources() for everything else.
 *
 * Runs on every update, and finds nothing to do once everything is in shape. Whatever could not be renamed is
 * listed, and tried again on the next update.
 *
 * References left behind by the earlier changes, to elements whose own handles are already right, are rewritten
 * once, by the update that brings the database to version 27. Finding them means reading every setting that can
 * refer to an element, and once that has run nothing writes a stale one: every handle is in its current shape,
 * and a rename rewrites references to the handle exactly as it was.
 *
 * @param int $prev_dbversion The database version before this update
 * @return bool False only if the misspellings could not be looked for at all
 */
function formulize_normalize_element_handles($prev_dbversion) {
    global $xoopsDB;

    $found = formulize_find_misspelled_element_handles();
    if ($found === false) {
        return false;
    }
    list($misspelled, $problems) = $found;

    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $leftForAPerson = array(); // what the renames left in code and templates, for a person to look at

    // 1. misspelled elements
    $headingShown = false;
    foreach ($misspelled as $eleId => $misspelling) {
        if (!$headingShown) {
            print "<h3>Renaming element handles to match the way handles are written now:</h3>\n";
            $headingShown = true;
        }
        $stored = $misspelling['stored'];
        $newHandle = formulizeElement::sanitize_handle_name($stored);
        try {
            // Asked here rather than of validateElementHandle(), which takes an element whose handle matches the stored
            // one to have nothing to validate - and the two always match here, since both have been through assignVar()
            if ($newHandle !== $stored AND !$form_handler->isElementHandleUnique($newHandle, $eleId, $misspelling['fid'])) {
                // room is left for the suffix within the 64 characters a field name can have, as validateElementHandle() does
                $newHandle = formulizeHandler::enforceUniqueElementHandles(substr($newHandle, 0, 59), $eleId, $misspelling['fid']);
            }
            // The field first. If it cannot be renamed, nothing else is either, so the element never ends up named
            // differently from its field. updateField() finds the field under any spelling that sanitizes to the
            // same handle, and does nothing if it is already right.
            $form_handler->updateField($eleId, ($misspelling['field'] !== '' ? $misspelling['field'] : $stored), false, $newHandle);
        } catch (Exception $e) {
            $problems[] = "ele_id " . intval($eleId) . " (" . $stored . "): " . $e->getMessage();
            continue;
        }
        if (!$elementObject = $element_handler->get($eleId)) {
            $problems[] = "ele_id " . intval($eleId) . " (" . $stored . "): the element could not be loaded.";
            continue;
        }
        // in memory only, so renameElementResources() renames to it. assignVar rather than setVar, so it is not
        // left looking like a change waiting to be saved. It is saved below, by itself.
        $elementObject->assignVar('ele_handle', $newHandle);
        // once for each spelling found, since the element table, the data table and the code files can disagree,
        // and references could have been written from any of them
        $oldSpellings = array_values(array_filter($misspelling['spellings'], fn($spelling) => $spelling !== $newHandle));
        foreach ($oldSpellings as $spelling) {
            $leftForAPerson = array_merge($leftForAPerson, formulize_rename_handle_spelling($elementObject, $spelling));
        }
        // The handle itself last, written directly rather than through insert(), which would write every other
        // property of the element back as well. Until this is saved the element is still found as misspelled, so
        // an update that stops part way through picks it up again next time, and every step above is safe to repeat.
        // The cache goes first, since it holds the object given the new handle in memory above.
        $element_handler->clearElementCache();
        if ($newHandle !== $stored AND !$xoopsDB->queryF("UPDATE " . $xoopsDB->prefix('formulize') . " SET ele_handle = " . $xoopsDB->quoteString($newHandle) . " WHERE ele_id = " . intval($eleId))) {
            $problems[] = "ele_id " . intval($eleId) . " (" . $stored . "): the new handle " . $newHandle . " could not be saved: " . $xoopsDB->error();
            continue;
        }
        print "<p>Renamed: <code>" . implode('</code>, <code>', array_map('htmlspecialchars', $oldSpellings)) . "</code> &rarr; <code>" . htmlspecialchars($newHandle) . "</code> (ele_id=" . intval($eleId) . ", form_id=" . intval($misspelling['fid']) . ")</p>\n";
    }

    // 2. references with capitals or hyphens to elements whose handles are already right, once
    if ($prev_dbversion < 27) {
        $headingShown = false;
        foreach (formulize_find_stale_handle_spellings() as $eleId => $staleSpellings) {
            if (!$elementObject = $element_handler->get($eleId)) {
                continue;
            }
            if (!$headingShown) {
                print "<h3>Updating references to element handles that still use capitals or hyphens:</h3>\n";
                $headingShown = true;
            }
            foreach ($staleSpellings as $spelling) {
                $leftForAPerson = array_merge($leftForAPerson, formulize_rename_handle_spelling($elementObject, $spelling));
            }
            print "<p>Updated references to <code>" . htmlspecialchars($elementObject->getVar('ele_handle')) . "</code> written as <code>" . implode('</code>, <code>', array_map('htmlspecialchars', $staleSpellings)) . "</code></p>\n";
        }
    }

    $alerts = array();

    // 3. what could not be done, which the next update tries again
    if ($problems) {
        print "<h3>Element handles that could not be fixed</h3>\n"
            . "<p><b>Nothing was changed for these elements.</b> Each still has a handle, a data table field or a code file that does not match the way handles are written now, which can stop the element's values from appearing when an entry is opened. The update will try them again each time it runs. Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>\n<ul>\n";
        foreach ($problems as $problem) {
            print "<li>" . htmlspecialchars($problem) . "</li>\n";
        }
        print "</ul>\n";
        $alerts[] = "Some element handles could not be fixed during this update, and their values may not appear when entries are opened:\n\n" . implode("\n", $problems);
    }

    // 4. what the renames left in people's own code, which is theirs to change
    if ($leftForAPerson) {
        print "<h3>Old element handles still used in custom code</h3>\n"
            . "<p><b>These were not changed</b>, because they are in code or templates someone wrote, where a name can mean something an update cannot know about. Each should be checked and possibly changed to the new handle:</p>\n<ul>\n";
        foreach (array_unique($leftForAPerson) as $place) {
            print "<li>" . htmlspecialchars($place) . "</li>\n";
        }
        print "</ul>\n";
        $alerts[] = "Element handles were renamed during this update, but custom code still uses the old names. Those places are listed on this page, and must be updated by hand before that code will work correctly.";
    }

    if ($alerts) {
        echo '<script>alert(' . json_encode(implode("\n\n", $alerts)) . ');</script>';
    }
    return true;
}

/**
 * Rewrite everything that refers to an element by one old spelling of its handle.
 *
 * @param object $elementObject The element, carrying the handle it is to have
 * @param string $spelling The old spelling, exactly as it is written
 * @return array What the rename left for a person to look at, each saying where and what it is now called
 */
function formulize_rename_handle_spelling($elementObject, $spelling) {
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $leftForAPerson = array();
    foreach ($element_handler->renameElementResources($elementObject, $spelling) as $place) {
        $leftForAPerson[] = $place . ': "' . $spelling . '" is now "' . $elementObject->getVar('ele_handle') . '"';
    }
    return $leftForAPerson;
}

/**
 * The elements whose handle, data table field or code files are spelled in a way handles are not written now.
 *
 * A spelling is wrong when sanitize_handle_name() does not leave it as it is. The stored handle is checked
 * directly. A field belongs to an element when it sanitizes to the element's handle, within the element's own
 * form's data table and revisions table. A code file belongs to an element the same way, by the part of its name
 * after the type.
 *
 * Where several elements have handles that sanitize to the same thing - my-handle and my_handle, from before
 * hyphens were removed - a field or file only goes with an element whose stored handle is spelled exactly like
 * it. Another one could be either element's, so it is listed for a person instead.
 *
 * Elements of forms built on another table (tableform) are left out. Their handles are the names of fields in a
 * table Formulize does not own, and have to stay what that table calls them.
 *
 * Costs one read of the element table, one of the database's list of fields, and one of the code folder, so it
 * is cheap enough to run on every update. All the work happens only for what it finds.
 *
 * @return array|false array(ele_id => array('fid', 'stored', 'field', 'spellings'), list of problems for a person),
 *   or false if the lookup failed. 'field' is the data table field's spelling when it is wrong, else ''.
 */
function formulize_find_misspelled_element_handles() {
    global $xoopsDB;

    $eleRes = $xoopsDB->queryF(
        "SELECT e.ele_id, e.id_form, e.ele_handle, f.form_handle FROM " . $xoopsDB->prefix('formulize') . " AS e"
        . " INNER JOIN " . $xoopsDB->prefix('formulize_id') . " AS f ON f.id_form = e.id_form"
        . " WHERE f.tableform = '' OR f.tableform IS NULL"
    );
    if (!$eleRes) {
        print "<p>Error: could not look for element handles that need fixing: " . htmlspecialchars($xoopsDB->error()) . " Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>";
        return false;
    }
    $elements = array();
    $idsByHandle = array(); // sanitized handle => ele_ids, across every form
    $idsByFormAndHandle = array(); // form id => sanitized handle => ele_ids
    while ($row = $xoopsDB->fetchArray($eleRes)) {
        $eleId = intval($row['ele_id']);
        $handle = formulizeElement::sanitize_handle_name($row['ele_handle']);
        $elements[$eleId] = $row;
        $idsByHandle[$handle][] = $eleId;
        $idsByFormAndHandle[intval($row['id_form'])][$handle][] = $eleId;
    }

    // every wrongly spelled field in a Formulize table, in one query. The pattern's \_ is a literal underscore.
    $colRes = $xoopsDB->queryF(
        "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE()"
        . " AND TABLE_NAME LIKE " . $xoopsDB->quoteString($xoopsDB->prefix('formulize') . '\_%')
    );
    if (!$colRes) {
        print "<p>Error: could not look for data table fields that need renaming: " . htmlspecialchars($xoopsDB->error()) . " Please contact <a href=mailto:info@formulize.org>info@formulize.org</a> for assistance.</p>";
        return false;
    }
    $wrongFields = array(); // table => sanitized name => field names
    while ($row = $xoopsDB->fetchArray($colRes)) {
        $sanitized = formulizeElement::sanitize_handle_name($row['COLUMN_NAME']);
        if ($sanitized !== $row['COLUMN_NAME']) {
            $wrongFields[$row['TABLE_NAME']][$sanitized][] = $row['COLUMN_NAME'];
        }
    }

    // every wrongly spelled code file, by the part of its name after the type. Longest types first, so that
    // textarea_ is not read as text_ followed by a handle beginning "area".
    $types = formulizeElementsHandler::elementCodeFileTypes();
    usort($types, fn($a, $b) => strlen($b) - strlen($a));
    $codeFilePattern = '/^(' . implode('|', array_map(fn($type) => preg_quote($type, '/'), $types)) . ')_(.+)\.php$/';
    $wrongFiles = array(); // sanitized name => names
    foreach ((array) scandir(XOOPS_ROOT_PATH . '/modules/formulize/code/') as $file) {
        if (preg_match($codeFilePattern, $file, $match) AND formulizeElement::sanitize_handle_name($match[2]) !== $match[2]) {
            $wrongFiles[formulizeElement::sanitize_handle_name($match[2])][$match[2]] = $file;
        }
    }

    $misspelled = array();
    $problems = array();
    $claimedFiles = array();
    foreach ($elements as $eleId => $row) {
        $stored = $row['ele_handle'];
        $fid = intval($row['id_form']);
        $handle = formulizeElement::sanitize_handle_name($stored);
        if ($handle === '') {
            $problems[] = "ele_id $eleId (form_id $fid): the handle \"$stored\" has no characters a handle can keep, so it needs a new handle chosen by hand.";
            continue;
        }
        $spellings = ($stored !== $handle) ? array($stored) : array();
        $field = '';
        if ($row['form_handle'] !== '') {
            $sharedInForm = count($idsByFormAndHandle[$fid][$handle]) > 1;
            foreach (array('', '_revisions') as $tableSuffix) {
                $table = $xoopsDB->prefix('formulize_' . $row['form_handle'] . $tableSuffix);
                if (empty($wrongFields[$table][$handle])) {
                    continue;
                }
                $fields = $wrongFields[$table][$handle];
                if ($sharedInForm OR count($fields) > 1) {
                    // only the field spelled exactly like this element's handle is certainly its own
                    $fields = in_array($stored, $fields, true) ? array($stored) : array();
                }
                if ($fields AND $tableSuffix === '') {
                    $field = $fields[0];
                }
                $spellings = array_merge($spellings, $fields);
            }
        }
        if (!empty($wrongFiles[$handle])) {
            $names = array_keys($wrongFiles[$handle]);
            if (count($idsByHandle[$handle]) > 1) {
                $names = in_array($stored, $names, true) ? array($stored) : array();
            }
            foreach ($names as $name) {
                $claimedFiles[$name] = true;
            }
            $spellings = array_merge($spellings, $names);
        }
        $spellings = array_values(array_unique($spellings));
        if ($spellings) {
            $misspelled[$eleId] = array('fid' => $fid, 'stored' => $stored, 'field' => $field, 'spellings' => $spellings);
        }
    }

    // code files that could belong to more than one element, and so went with none of them
    foreach ($wrongFiles as $handle => $names) {
        if (isset($idsByHandle[$handle]) AND count($idsByHandle[$handle]) > 1) {
            foreach ($names as $name => $file) {
                if (!isset($claimedFiles[$name])) {
                    $problems[] = "the code file $file could belong to any of the elements with ids " . implode(', ', $idsByHandle[$handle]) . ", so it has been left as it is.";
                }
            }
        }
    }

    return array($misspelled, $problems);
}

/**
 * What could be an old spelling of a handle in the text of a stored setting.
 *
 * Serialized settings are text too, and the strings in them are written out whole, so this works on anything
 * that is stored. It only proposes: whether a word really is a reference is for formulizeElementReferences to
 * say - see formulize_find_stale_handle_spellings().
 *
 * A word only counts if it has a capital or a hyphen, and has an underscore or a hyphen - the shape of a handle
 * made from a form handle and a caption. A single word does not count, because Formulize has {tokens} of its own
 * in capitals - {USER}, {TODAY} - which are not old spellings of an element that happens to be called user or today.
 *
 * @param string $text What is stored
 * @return array The words that could be old spellings
 */
function formulize_handle_spellings_in_text($text) {
    $words = array();
    if (preg_match_all('/[A-Za-z0-9_\-\x80-\xff]+/', $text, $found)) {
        foreach ($found[0] as $word) {
            $words[] = $word;
            // what can be written straight in front of a handle, which is not part of it: a saved view's hidden
            // column, and the {search_handle} token. The word as it stands is kept too, since a handle can begin this way.
            foreach (array(formulizeSavedViewsHandler::HIDDEN_COLUMN_PREFIX, 'search_') as $prefix) {
                if (strpos($word, $prefix) === 0) {
                    $words[] = substr($word, strlen($prefix));
                }
            }
        }
    }
    // A notification's tags are capitals by design, so a tag for an element is only old if it has a hyphen. Only
    // those are picked out here, or every {ELEMENT_MY_HANDLE} would look like an old spelling of my_handle.
    if (preg_match_all('/\{(?:REVISION_ELEMENT_|ELEMENT_?)([A-Z0-9_\x80-\xff]*-[A-Z0-9_\-\x80-\xff]*)\}/', $text, $found)) {
        $words = array_merge($words, $found[1]);
    }
    $spellings = array();
    foreach ($words as $word) {
        if (preg_match('/[A-Z\-]/', $word) AND preg_match('/[_\-]/', $word)) {
            $spellings[$word] = (string) $word;
        }
    }
    return array_values($spellings);
}

/**
 * Every spelling of a current element handle, other than the handle itself, that is still used to refer to the
 * element in a setting. These are what is left behind when a handle loses its capitals or hyphens without every
 * reference to it being rewritten, which is what happened when handles were lowercased in 2025-10 and when hyphens
 * were removed in 2026-07.
 *
 * The settings that can refer to an element are the ones declared in $modversion['formulize_element_references'],
 * and those are the only ones read. Whether a spelling written in one of them really is a reference is asked of
 * formulizeElementReferences, so a word that happens to look like a handle - in a caption, in a list of options, in
 * the value a condition compares against - is not one, and is left out for the same reason a rename would leave it.
 *
 * Reads the declared columns of every declared table once, and then asks about each spelling they might hold.
 *
 * @return array ele_id => list of the other spellings found for that element's handle
 */
function formulize_find_stale_handle_spellings() {
    global $xoopsDB;
    xoops_getmodulehandler('elements', 'formulize'); // brings formulizeElementReferences with it
    include_once XOOPS_ROOT_PATH . '/modules/formulize/class/savedViews.php';

    // Elements with a handle that is still wrong are left out, since it is not settled what theirs is. Only ones
    // that step 1 of the normalization could not rename can be like that by now, and they are listed for a person.
    $elements = array(); // handle => the element
    if ($res = $xoopsDB->queryF("SELECT ele_id, id_form, ele_handle FROM " . $xoopsDB->prefix('formulize'))) {
        while ($row = $xoopsDB->fetchArray($res)) {
            if (!is_numeric($row['ele_handle']) AND $row['ele_handle'] === formulizeElement::sanitize_handle_name($row['ele_handle'])) {
                $elements[$row['ele_handle']] = $row;
            }
        }
    }

    $spellings = array();
    foreach (formulizeElementReferences::declaration() as $table => $tableSpec) {
        $tableColumns = formulize_patch_table_columns($xoopsDB->prefix($table)); // a table or column an older site does not have costs only that
        $columns = $tableColumns ? array_intersect(array_keys($tableSpec['columns']), array_keys($tableColumns)) : array();
        if (!$columns) {
            continue;
        }
        $columnList = implode(', ', array_map(fn($column) => "`$column`", $columns));
        if (!$result = $xoopsDB->queryF("SELECT $columnList FROM " . $xoopsDB->prefix($table))) {
            continue;
        }
        while ($row = $xoopsDB->fetchArray($result)) {
            foreach ($row as $text) {
                foreach (formulize_handle_spellings_in_text((string) $text) as $spelling) {
                    $spellings[$spelling] = (string) $spelling;
                }
            }
        }
    }

    $found = array();
    foreach ($spellings as $spelling) {
        // the element this would be a spelling of, by the same rule that makes every handle what it is now. A word that
        // only looks like a handle can come out as one too, which is what the question below is for.
        $handle = formulizeElement::sanitize_handle_name($spelling);
        if (!isset($elements[$handle]) OR $spelling === $handle) {
            continue;
        }
        // Asked about an id no element can have, because a reference by id is a reference to the element whatever a
        // handle looks like. What is found this way can only be the spelling, at a position that holds a reference.
        $references = new formulizeElementReferences(PHP_INT_MAX, $spelling, $elements[$handle]['id_form']);
        if ($references->references() OR $references->mentions()) {
            $found[intval($elements[$handle]['ele_id'])][] = $spelling;
        }
    }
    return $found;
}
