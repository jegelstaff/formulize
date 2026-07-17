<?php
/**
 * Formulize Theme Editor
 * Admin page for viewing and editing theme files
 */

// Ajax save request: the editor posts directly to this file (not through ui.php), so this
// branch bootstraps the CMS itself and exits before the page-render logic below (which
// assumes ui.php has already set up $xoopsUser, ICMS_THEME_PATH, etc.) ever runs.
if (isset($_POST['themeeditor_save'])) {
    include_once "../../../mainfile.php";
    include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';

    global $xoopsUser;
    if (!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
        print "Could not save: you do not have permission to save theme files.";
        exit;
    }
    if (sendSaveLockPrefToTemplate()) {
        print "Could not save: this system is locked.";
        exit;
    }

    $theme = isset($_POST['theme']) ? $_POST['theme'] : '';
    $file = isset($_POST['file']) ? $_POST['file'] : '';
    $content = isset($_POST['file_content']) ? $_POST['file_content'] : '';

    // Only themes actually installed in this Formulize installation may be targeted
    $themes = icms_view_theme_Factory::getThemesList();
    if ($theme === '' OR !isset($themes[$theme])) {
        print "Could not save: unknown theme.";
        exit;
    }

    $themeDir = realpath(ICMS_THEME_PATH . '/' . $theme);
    if ($themeDir === false) {
        print "Could not save: theme folder not found on the server.";
        exit;
    }

    // Resolve the target file within the theme folder and refuse anything that escapes it
    // (e.g. via ../) or that isn't a real, already-existing file there. The editor only
    // ever offers files it already listed from that same folder, so a legitimate save
    // always targets an existing file; this also means we never create new files here.
    $targetPath = ($file !== '') ? realpath($themeDir . '/' . $file) : false;
    if ($targetPath === false OR strpos($targetPath, $themeDir . DIRECTORY_SEPARATOR) !== 0 OR !is_file($targetPath)) {
        print "Could not save: invalid file.";
        exit;
    }

    if (!is_writable($targetPath)) {
        print "Could not save: the file is not writable on the server.";
        exit;
    }

    if (file_put_contents($targetPath, $content) === false) {
        print "Could not save: the file could not be written on the server.";
        exit;
    }

    exit; // empty body = success
}

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

// Nested folder/file tree for the file browser, so files are grouped visually by the
// folder they live in rather than shown as one flat alphabetical list
$themeFileTree = formulize_themeeditor_buildFileTree($adminPage['theme_files']);
formulize_themeeditor_sortFileTree($themeFileTree);
$adminPage['theme_files_tree'] = formulize_themeeditor_renderFileTree($themeFileTree, $adminPage['selected_theme'], $adminPage['selected_file']);

// Image files can't be sanely edited as text, so show a preview instead of dumping
// raw binary bytes into the textarea
$imageExtensions = array('png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp');
$selectedExtension = strtolower(pathinfo($adminPage['selected_file'], PATHINFO_EXTENSION));
$adminPage['selected_file_is_image'] = in_array($selectedExtension, $imageExtensions, true);

if(substr($adminPage['selected_file'], -3) == '.js') {
  $adminPage['editorClass']	= 'code-textarea-js';
} elseif(substr($adminPage['selected_file'], -4) == '.css') {
	$adminPage['editorClass']	= 'code-textarea-css';
} else {
	$adminPage['editorClass']	= 'code-textarea';
}

// Contents of the selected file, for display in the editor
if ($adminPage['selected_file_is_image']) {
    $adminPage['selected_file_content'] = '';
    $adminPage['selected_file_url'] = ICMS_THEME_URL . '/' . $adminPage['selected_theme'] . '/' . $adminPage['selected_file'];
} else {
    $adminPage['selected_file_content'] = $adminPage['selected_file'] !== ''
        ? file_get_contents(ICMS_THEME_PATH . '/' . $adminPage['selected_theme'] . '/' . $adminPage['selected_file'])
        : '';
}

/**
 * Recursively list every editable file inside a theme's folder. Images and any other
 * non-editable file types are left out entirely, which also means folders that contain
 * only images (e.g. an "images/" folder) never show up, since they end up with no files in them.
 * @param string themeDir - full filesystem path to the theme's folder
 * @return array Sorted list of file paths, relative to the theme's folder, using forward slashes
 */
function formulize_themeeditor_getThemeFiles($themeDir) {
    $editableExtensions = array('html', 'htm', 'css', 'js');
    $files = array();
    if(!is_dir($themeDir)) {
        return $files;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($themeDir, FilesystemIterator::SKIP_DOTS)
    );
    foreach($iterator as $fileInfo) {
        if($fileInfo->isFile()) {
            $extension = strtolower($fileInfo->getExtension());
            if(!in_array($extension, $editableExtensions, true)) {
                continue;
            }
            $relativePath = substr($fileInfo->getPathname(), strlen($themeDir) + 1);
            $files[] = str_replace('\\', '/', $relativePath);
        }
    }
    sort($files, SORT_STRING);
    return $files;
}

/**
 * Turn a flat list of theme-relative file paths (e.g. "css/foo.css") into a nested
 * associative tree of dir/file nodes, keyed by path segment at each level.
 * @param array files - output of formulize_themeeditor_getThemeFiles()
 * @return array Nested tree; each node is array('type' => 'dir'|'file', 'name' => ..., 'path' => ..., and 'children' => array() for dirs)
 */
function formulize_themeeditor_buildFileTree($files) {
    $tree = array();
    foreach ($files as $file) {
        $parts = explode('/', $file);
        $node = &$tree;
        $pathSoFar = '';
        foreach ($parts as $i => $part) {
            $pathSoFar = ($pathSoFar === '') ? $part : $pathSoFar . '/' . $part;
            $isFile = ($i === count($parts) - 1);
            if ($isFile) {
                $node[$part] = array('type' => 'file', 'name' => $part, 'path' => $pathSoFar);
            } else {
                if (!isset($node[$part]) OR $node[$part]['type'] !== 'dir') {
                    $node[$part] = array('type' => 'dir', 'name' => $part, 'path' => $pathSoFar, 'children' => array());
                }
                $node = &$node[$part]['children'];
            }
        }
        unset($node);
    }
    return $tree;
}

/**
 * Recursively sort a file tree in place: folders before files, alphabetically within each group.
 * @param array tree - reference to a tree node's children, as produced by formulize_themeeditor_buildFileTree()
 */
function formulize_themeeditor_sortFileTree(&$tree) {
    uasort($tree, function($a, $b) {
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'dir' ? -1 : 1;
        }
        return strcasecmp($a['name'], $b['name']);
    });
    foreach ($tree as &$node) {
        if ($node['type'] === 'dir') {
            formulize_themeeditor_sortFileTree($node['children']);
        }
    }
}

/**
 * Render a file tree as nested <ul> markup for the theme editor's file browser.
 * Folders containing the selected file are marked "open" so its path is visible on load.
 * @param array tree - a tree node's children, as produced by formulize_themeeditor_buildFileTree()
 * @param string theme - theme directory name, for building file links
 * @param string selectedFile - theme-relative path of the currently selected file
 * @return string HTML markup
 */
function formulize_themeeditor_renderFileTree($tree, $theme, $selectedFile) {
    $html = '<ul class="themeeditor-tree">';
    foreach ($tree as $node) {
        if ($node['type'] === 'dir') {
            $isOpen = ($selectedFile !== '' AND strpos($selectedFile, $node['path'] . '/') === 0);
            $html .= '<li class="themeeditor-tree-dir' . ($isOpen ? ' open' : '') . '">';
            $html .= '<span class="themeeditor-tree-toggle">' . htmlspecialchars($node['name']) . '/</span>';
            $html .= formulize_themeeditor_renderFileTree($node['children'], $theme, $selectedFile);
            $html .= '</li>';
        } else {
            $isActive = ($node['path'] === $selectedFile);
            $href = 'ui.php?page=themeeditor&theme=' . urlencode($theme) . '&file=' . urlencode($node['path']);
            $html .= '<li class="themeeditor-tree-file' . ($isActive ? ' active' : '') . '">';
            $html .= '<a href="' . htmlspecialchars($href) . '">' . htmlspecialchars($node['name']) . '</a>';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';
    return $html;
}



