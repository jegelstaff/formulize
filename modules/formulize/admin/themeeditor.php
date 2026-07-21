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

    // Resolve the virtual path to a real file and refuse anything that escapes its root
    // (e.g. via ../) or isn't an already-existing file. The path may target the theme's own
    // folder or, via the "screens/" prefix, the theme's Formulize screen-template folder;
    // the resolver handles both. The editor only ever offers files it already listed from
    // those roots, so a legitimate save always targets an existing file - we never create
    // new files here.
    $resolved = formulize_themeeditor_resolveVirtualPath($theme, $file);
    if ($resolved === false) {
        print "Could not save: invalid file.";
        exit;
    }
    $targetPath = $resolved['full'];

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
// This page is a sub-view of the Appearance subject tab (see include/configsettings_registry.php),
// so the shared subject handler (admin/configsubject.php) owns the tabs and the
// Home > Appearance > Theme Editor breadcrumb. We only contribute the file-specific
// final crumb via $adminPage['extra_breadcrumbs'], set once the selected file is known.

// Themes installed in this Formulize installation
$adminPage['themes'] = icms_view_theme_Factory::getThemesList();

// Site's current default theme
global $xoopsConfig;
$adminPage['default_theme'] = $xoopsConfig['theme_set'];

// Which theme's files to show: whatever was picked in the select, falling back to the site's default theme
$requestedTheme = isset($_GET['theme']) ? $_GET['theme'] : (isset($_POST['theme']) ? $_POST['theme'] : '');
$adminPage['selected_theme'] = isset($adminPage['themes'][$requestedTheme]) ? $requestedTheme : $adminPage['default_theme'];

// All editable files across the selected theme's roots (its own folder plus its Formulize
// screen-templates folder, exposed under "screens/"), so the tree and save stay in sync
$adminPage['theme_files'] = formulize_themeeditor_getThemeFiles($adminPage['selected_theme']);

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

// The file being edited becomes the final breadcrumb crumb (no url = current page),
// which is why the editor no longer shows the filename as a title above the code box.
if ($adminPage['selected_file'] !== '') {
    $adminPage['extra_breadcrumbs'] = array(array('text' => $adminPage['selected_file']));
}

// Contents of the selected file, for display in the editor
if ($adminPage['selected_file_is_image']) {
    $adminPage['selected_file_content'] = '';
    $adminPage['selected_file_url'] = ICMS_THEME_URL . '/' . $adminPage['selected_theme'] . '/' . $adminPage['selected_file'];
} else {
    $resolvedSelected = ($adminPage['selected_file'] !== '')
        ? formulize_themeeditor_resolveVirtualPath($adminPage['selected_theme'], $adminPage['selected_file'])
        : false;
    $adminPage['selected_file_content'] = $resolvedSelected ? file_get_contents($resolvedSelected['full']) : '';
}

/**
 * The physical roots that make up a theme's editable file set, keyed by the virtual-path
 * prefix each is exposed under in the file tree:
 *   ''        => the theme's own folder (themes/<theme>)
 *   'screens' => the theme's default Formulize screen templates
 *                (modules/formulize/templates/screens/<theme>/default), which hold the
 *                default list/form/map/multipage/etc. layouts for that theme
 * Splitting the theme's files across two folders on disk is a quirk of how Formulize
 * stores screen templates; merging them here lets the whole editable layout for a theme
 * be browsed as one tree. A root that doesn't exist on disk (e.g. a theme with no custom
 * screen templates) is skipped by callers, so its pseudo-folder simply won't appear.
 * @param string $theme - theme directory name
 * @return array Ordered map of prefix => absolute root directory
 */
function formulize_themeeditor_getRoots($theme) {
    return array(
        '' => array(
            'dir' => ICMS_THEME_PATH . '/' . $theme,
            // Theme folder: markup and assets only. PHP is deliberately excluded so a
            // theme's own logic files aren't exposed as editable content.
            'extensions' => array('html', 'htm', 'css', 'js'),
        ),
        'screens' => array(
            // The theme's DEFAULT screen templates only: modules/formulize/templates/screens/<theme>/default,
            // which holds the default layouts for each screen type (form, listOfEntries, map,
            // multiPage, ...). The sibling screen-id folders (e.g. .../screens/<theme>/43) are
            // per-screen overrides and deliberately left out.
            'dir' => XOOPS_ROOT_PATH . '/modules/formulize/templates/screens/' . $theme . '/default',
            // Formulize screen templates are PHP files (they hold eval'd template code),
            // so PHP must be editable here for the layouts to be reachable at all.
            'extensions' => array('html', 'htm', 'css', 'js', 'php'),
        ),
    );
}

/**
 * Resolve a virtual file path (as listed by formulize_themeeditor_getThemeFiles) to a real
 * file on disk, enforcing that it stays within its root. Prefixed roots (e.g. "screens/…")
 * are matched first; anything else resolves against the theme's own folder.
 * @param string $theme       - theme directory name (already validated against the installed themes)
 * @param string $virtualPath - virtual path from the file tree
 * @return array|false array('full'=>real path, 'root'=>real root dir, 'prefix'=>matched prefix), or false if invalid
 */
function formulize_themeeditor_resolveVirtualPath($theme, $virtualPath) {
    if ($virtualPath === '') {
        return false;
    }
    $roots = formulize_themeeditor_getRoots($theme);
    // Check prefixed roots first; the '' (theme folder) root is the catch-all.
    foreach ($roots as $prefix => $root) {
        if ($prefix === '') {
            continue;
        }
        if (strpos($virtualPath, $prefix . '/') === 0) {
            return formulize_themeeditor_containedFile($root['dir'], substr($virtualPath, strlen($prefix) + 1), $prefix);
        }
    }
    return formulize_themeeditor_containedFile($roots['']['dir'], $virtualPath, '');
}

/**
 * Helper for formulize_themeeditor_resolveVirtualPath: realpath a relative file within a
 * root directory and confirm it's a real, existing file that hasn't escaped the root
 * (e.g. via ../).
 * @param string $rootDir  - the root directory the file must live inside
 * @param string $relative - path relative to that root
 * @param string $prefix   - the virtual prefix this root is exposed under (for the return value)
 * @return array|false
 */
function formulize_themeeditor_containedFile($rootDir, $relative, $prefix) {
    $rootReal = realpath($rootDir);
    if ($rootReal === false OR $relative === '') {
        return false;
    }
    $full = realpath($rootDir . '/' . $relative);
    if ($full === false OR strpos($full, $rootReal . DIRECTORY_SEPARATOR) !== 0 OR !is_file($full)) {
        return false;
    }
    return array('full' => $full, 'root' => $rootReal, 'prefix' => $prefix);
}

/**
 * Recursively list every editable file available for a theme, across all of its roots (see
 * formulize_themeeditor_getRoots). Paths are returned as "virtual" paths: files from the
 * theme folder keep their plain relative path (e.g. "css/foo.css"), while files from a
 * prefixed root are prefixed (e.g. "screens/listOfEntries/foo.html"). Images and other
 * non-editable file types are left out entirely, which also means folders that contain
 * only images never show up, since they end up with no files in them.
 * @param string $theme - theme directory name
 * @return array Sorted list of virtual file paths, using forward slashes
 */
function formulize_themeeditor_getThemeFiles($theme) {
    $files = array();
    foreach (formulize_themeeditor_getRoots($theme) as $prefix => $root) {
        $rootDir = $root['dir'];
        $editableExtensions = $root['extensions'];
        if (!is_dir($rootDir)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $extension = strtolower($fileInfo->getExtension());
            if (!in_array($extension, $editableExtensions, true)) {
                continue;
            }
            $relativePath = str_replace('\\', '/', substr($fileInfo->getPathname(), strlen($rootDir) + 1));
            // Exclusions: index.html (in any folder) and theme_admin.html are boilerplate
            // that shouldn't be edited here, and any admin/ folder holds admin-only markup
            // that's out of scope for the Theme Editor. Under screens, the "form" folder is
            // the legacy (deprecated) single-page form screen type, so it's left out too.
            $baseName = basename($relativePath);
            $firstSegment = (strpos($relativePath, '/') !== false) ? substr($relativePath, 0, strpos($relativePath, '/')) : '';
            if ($baseName === 'index.html' OR $baseName === 'theme_admin.html' OR $firstSegment === 'admin'
                OR ($prefix === 'screens' AND $firstSegment === 'form')) {
                continue;
            }
            $files[] = ($prefix === '') ? $relativePath : $prefix . '/' . $relativePath;
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
            $href = 'ui.php?page=appearance&view=themeeditor&theme=' . urlencode($theme) . '&file=' . urlencode($node['path']);
            $html .= '<li class="themeeditor-tree-file' . ($isActive ? ' active' : '') . '">';
            $html .= '<a href="' . htmlspecialchars($href) . '">' . htmlspecialchars($node['name']) . '</a>';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';
    return $html;
}

