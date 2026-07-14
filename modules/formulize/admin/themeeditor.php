<?php
/**
 * Formulize Theme Editor
 * Admin page for viewing and editing theme files
 */

// Only webmasters can access this page
global $xoopsUser;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}
// Set the page chrome up front, so the several early returns below (logging off,
// no log dir, no files) still render with the admin tabs and breadcrumb.
$adminPage['home_tabs'] = getHomeTabs('themeeditor');
$breadcrumbtrail[1]['url'] = "page=themeeditor";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Theme Editor";

// Themes installed in this Formulize installation
$adminPage['themes'] = icms_view_theme_Factory::getThemesList();

// Site's current default theme
global $xoopsConfig;
$adminPage['default_theme'] = $xoopsConfig['theme_set'];

// Which theme's files to show: whatever was picked in the select, falling back to the site's default theme
$requestedTheme = isset($_GET['theme']) ? $_GET['theme'] : (isset($_POST['theme']) ? $_POST['theme'] : '');
$adminPage['selected_theme'] = isset($adminPage['themes'][$requestedTheme]) ? $requestedTheme : $adminPage['default_theme'];

// All the files inside the selected theme's folder, so the picklist and file list stay in sync
$adminPage['theme_files'] = formulize_themeeditor_getThemeFiles(ICMS_THEME_PATH . '/' . $adminPage['selected_theme']);

/**
 * Recursively list every file inside a theme's folder
 * @param string themeDir - full filesystem path to the theme's folder
 * @return array Sorted list of file paths, relative to the theme's folder, using forward slashes
 */
function formulize_themeeditor_getThemeFiles($themeDir) {
    $files = array();
    if(!is_dir($themeDir)) {
        return $files;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($themeDir, FilesystemIterator::SKIP_DOTS)
    );
    foreach($iterator as $fileInfo) {
        if($fileInfo->isFile()) {
            $relativePath = substr($fileInfo->getPathname(), strlen($themeDir) + 1);
            $files[] = str_replace('\\', '/', $relativePath);
        }
    }
    sort($files, SORT_STRING);
    return $files;
}






