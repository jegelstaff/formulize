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

// Which file to show in the editor: whatever was picked in the file list, falling back to
// index.html (every theme has one), falling back to the first file in the theme's folder
$requestedFile = isset($_GET['file']) ? $_GET['file'] : (isset($_POST['file']) ? $_POST['file'] : '');
if(in_array($requestedFile, $adminPage['theme_files'], true)) {
    $adminPage['selected_file'] = $requestedFile;
} elseif(in_array('theme.html', $adminPage['theme_files'], true)) {
    $adminPage['selected_file'] = 'theme.html';
} elseif(!empty($adminPage['theme_files'])) {
    $adminPage['selected_file'] = $adminPage['theme_files'][0];
} else {
    $adminPage['selected_file'] = '';
}

// Contents of the selected file, for display in the editor
$adminPage['selected_file_content'] = $adminPage['selected_file'] !== ''
    ? file_get_contents(ICMS_THEME_PATH . '/' . $adminPage['selected_theme'] . '/' . $adminPage['selected_file'])
    : '';

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






