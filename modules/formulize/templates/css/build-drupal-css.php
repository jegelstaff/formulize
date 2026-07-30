<?php
/**
 * Generates drupal.css: the stylesheets that dress Formulize's own markup, rewritten so every rule
 * only applies inside the #formulize_form div.
 *
 * When Formulize is embedded in another system (ie: the Drupal integration module), its screen markup
 * is wrapped in <div id="formulize_form">, but the stylesheets are not written with that in mind.
 * They carry a CSS reset plus bare element selectors (body, table, button, td...) which, dropped onto
 * a Drupal page, would restyle the whole page rather than just the screen. Scoping every selector to
 * #formulize_form contains them.
 *
 * Deliberately NOT included here are the third party widget stylesheets a live page also loads
 * (jQuery UI, jgrowl, colorbox). Those widgets append their DOM to document.body - outside the div -
 * so scoping them would leave every dialog and datepicker unstyled. The host system loads those
 * as-is instead.
 *
 * The generated file is per theme and lives in the module cache, not next to the sources:
 *
 *   modules/formulize/cache/drupal-{theme}.css
 *
 * That keeps templates/css read only in a deployment, lets several themes coexist without regenerating
 * when the active theme changes, and puts the output somewhere already gitignored and writable.
 *
 * It regenerates itself. A machine readable header records the modification time of every source, so
 * formulize_drupalCssIsStale() can tell whether the file still matches them. The Drupal integration
 * module calls that on each screen render and rebuilds only when something has actually changed, which
 * means a new theme, or an edit to a theme's stylesheet, is picked up with no manual step.
 *
 * Run from the command line to force a rebuild, optionally naming a theme:
 *   php modules/formulize/templates/css/build-drupal-css.php
 *   php modules/formulize/templates/css/build-drupal-css.php Anari
 *
 * @package Formulize
 */

// Selector that everything gets scoped to. Matches the div emitted by Formulize::getScreenHtml().
define('SCOPE', '#formulize_form');

// Marks the header line that records what the file was built from. Kept on one line so staleness can be
// decided by reading the first few KB rather than parsing the whole stylesheet.
define('SOURCES_MARKER', 'formulize-embed-sources:');

/**
 * Locate the Formulize root from this file's position.
 *
 * @return  string|false
 */
function formulize_drupalCssRoot() {
    $root = realpath(__DIR__ . '/../../../..');
    return ($root and is_file($root . '/mainfile.php')) ? $root : false;
}

/**
 * The theme whose stylesheets should be baked in.
 *
 * Inside a request that has already booted Formulize - which is the case whenever the Drupal module
 * calls this - the answer is free. From the command line it is read straight out of the config table,
 * using mainfile.php's nocommon mode to get the credentials without booting the CMS.
 *
 * @param   string  $root   Formulize root
 * @return  string|false    Theme name, or false if it could not be determined
 */
function formulize_drupalCssActiveTheme($root) {
    if (!empty($GLOBALS['icmsConfig']['theme_set'])) {
        return $GLOBALS['icmsConfig']['theme_set'];
    }
    if (!defined('XOOPS_DB_NAME')) {
        // mainfile.php is included in this function's scope, so it reads a local $xoopsOption. Declaring
        // it global first is what actually suppresses the CMS boot; setting $GLOBALS['xoopsOption']
        // alone leaves the local undefined and common.php loads anyway.
        global $xoopsOption;
        $xoopsOption['nocommon'] = true;
        include_once $root . '/mainfile.php';
    }
    if (!defined('XOOPS_DB_NAME')) {
        return false;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . XOOPS_DB_HOST . ';dbname=' . XOOPS_DB_NAME . ';charset=utf8mb4',
            XOOPS_DB_USER, XOOPS_DB_PASS, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        $statement = $pdo->query("SELECT conf_value FROM " . XOOPS_DB_PREFIX . "_config WHERE conf_name = 'theme_set' LIMIT 1");
        $theme = $statement ? $statement->fetchColumn() : false;
    } catch (Exception $e) {
        return false;
    }
    return $theme ? $theme : false;
}

/**
 * Where the generated stylesheet for a theme lives.
 *
 * @param   string  $root   Formulize root
 * @param   string  $theme  Theme name
 * @return  string
 */
function formulize_drupalCssPath($root, $theme) {
    return $root . '/modules/formulize/cache/drupal-' . preg_replace('/[^A-Za-z0-9_.-]/', '', $theme) . '.css';
}

/**
 * The source stylesheets, in the cascade order a live screen page loads them.
 *
 * ORDER IS LOAD BEARING, and it is dictated by the theme template rather than chosen here:
 *
 *   theme.html  <{$icms_module_header}>  emits everything registered on $xoTheme, which is icms.css
 *                                        (header.php) and then formulize.css (modules/formulize/index.php)
 *   theme.html  ...later...              the theme echoes its own stylesheets
 *
 * So the theme's stylesheets come LAST and override the module's, and every theme must be built that
 * way. Concretely: formulize.css paints the list edit/view glyphs white
 * (.loe-edit-entry:before { color: #fff }) and darkens them via text-shadow on hover, and it is the
 * theme that makes them visible again with color: inherit on a coloured anchor. Reverse the order and
 * those icons render white on white - invisible until hovered, when the shadow shows as a faint outline.
 *
 * Every .css file in the theme's css folder is included, sorted by name, since a theme may split its
 * styles across several files.
 *
 * @param   string  $root   Formulize root
 * @param   string  $theme  Theme name
 * @return  array           Paths relative to the Formulize root
 */
function formulize_drupalCssSources($root, $theme) {
    $sources = array(
        'icms.css',
        'modules/formulize/templates/css/formulize.css',
    );
    $themeCssDir = $root . '/themes/' . $theme . '/css';
    $found = is_dir($themeCssDir) ? glob($themeCssDir . '/*.css') : array();
    if ($found === false) {
        $found = array();
    }
    sort($found, SORT_STRING);
    foreach ($found as $path) {
        $sources[] = 'themes/' . $theme . '/css/' . basename($path);
    }
    return $sources;
}

/**
 * Whether the generated stylesheet is missing, or no longer matches its sources.
 *
 * Compares the modification times recorded in the generated file's header against the sources as they
 * are now. A source being added or removed counts as stale too, so a theme gaining a stylesheet is
 * picked up as well as one being edited.
 *
 * @param   string  $root   Formulize root
 * @param   string  $theme  Theme name
 * @return  bool
 */
function formulize_drupalCssIsStale($root, $theme) {
    $target = formulize_drupalCssPath($root, $theme);
    if (!is_file($target)) {
        return true;
    }
    $handle = @fopen($target, 'r');
    if (!$handle) {
        return true;
    }
    $head = fread($handle, 8192);
    fclose($handle);
    if (!preg_match('/' . preg_quote(SOURCES_MARKER, '/') . '\s*(\{.*?\})\s*\*\//s', $head, $matches)) {
        return true; // no header, so it predates this scheme
    }
    $recorded = json_decode($matches[1], true);
    if (!is_array($recorded)) {
        return true;
    }
    $current = array();
    foreach (formulize_drupalCssSources($root, $theme) as $source) {
        $path = $root . '/' . $source;
        $current[$source] = is_file($path) ? filemtime($path) : 0;
    }
    ksort($recorded);
    ksort($current);
    return $recorded != $current;
}

/**
 * Build the scoped stylesheet for a theme.
 *
 * Never exits and never throws: the Drupal module calls this mid request, where dying would take the
 * page with it. A failure returns ok => false with a reason, and the caller carries on with whatever
 * file is already there.
 *
 * @param   string  $root   Formulize root
 * @param   string  $theme  Theme name
 * @return  array           ok (bool), path (string), message (string), bytes (int)
 */
function formulize_buildDrupalCss($root, $theme) {
    $target = formulize_drupalCssPath($root, $theme);
    $targetDir = dirname($target);
    $sources = formulize_drupalCssSources($root, $theme);

    if (!is_dir($targetDir)) {
        return array('ok' => false, 'path' => $target, 'message' => 'Cache directory is missing: ' . $targetDir, 'bytes' => 0);
    }
    if (!is_writable($targetDir)) {
        return array('ok' => false, 'path' => $target, 'message' => 'Cache directory is not writable: ' . $targetDir, 'bytes' => 0);
    }

    // Record what this was built from, so staleness can be decided without re-reading the sources.
    $stamps = array();
    foreach ($sources as $source) {
        $path = $root . '/' . $source;
        if (!is_file($path)) {
            return array('ok' => false, 'path' => $target, 'message' => 'Missing source stylesheet: ' . $path, 'bytes' => 0);
        }
        $stamps[$source] = filemtime($path);
    }
    ksort($stamps);

    $output = "/*\n"
        . " * GENERATED FILE - DO NOT EDIT. Rebuild with build-drupal-css.php, or just edit a source\n"
        . " * stylesheet: the Drupal integration module regenerates this whenever the sources change.\n"
        . " *\n"
        . " * Theme: " . $theme . "\n"
        . " *\n"
        . " * Every selector has been scoped to " . SCOPE . " so that these styles apply only to Formulize\n"
        . " * screen markup when Formulize is embedded in another system, and cannot leak onto the host page.\n"
        . " *\n"
        . " * " . SOURCES_MARKER . " " . json_encode($stamps) . "\n"
        . " */\n";

    foreach ($sources as $source) {
        $path = $root . '/' . $source;
        $css = file_get_contents($path);
        // Relative url() references are relative to the stylesheet they came from, and this file lives
        // somewhere else entirely, so they have to be recalculated against the cache directory.
        $css = rewriteUrls($css, dirname($path), $targetDir);
        $output .= "\n\n/* ==========================================================================\n"
            . "   " . $source . "\n"
            . "   ========================================================================== */\n\n"
            . scopeCss($css);
    }

// Anari's rules assume they own the page width. Inside a host system's content column they do not, so
// wide screens (list tables, subforms, the pagination controls) spill out of whatever container they
// have been dropped into. Keep the overflow inside the screen instead of pushing the host page sideways.
$output .= "\n\n/* ==========================================================================\n"
    . "   Containment for embedding\n"
    . "   Not derived from a source stylesheet: these rules exist because the screen is inside\n"
    . "   someone else's layout rather than occupying the page on its own.\n"
    . "   ========================================================================== */\n\n"
    . SCOPE . " {\n"
    . "  max-width: 100%;\n"
    . "  overflow-x: auto;\n"
    . "}\n\n"
    . SCOPE . " table {\n"
    . "  max-width: 100%;\n"
    . "}\n\n"
    . SCOPE . " img {\n"
    . "  max-width: 100%;\n"
    . "  height: auto;\n"
    . "}\n";

    // Check our own work rather than trusting it. A selector that escaped scoping would silently
    // restyle the host system's whole page, which is exactly the failure this file exists to prevent.
    $problems = verifyScoped($output);
    if (count($problems)) {
        $shown = array_slice($problems, 0, 10);
        return array(
            'ok' => false,
            'path' => $target,
            'bytes' => 0,
            'message' => count($problems) . ' selector(s) are not scoped to ' . SCOPE . ': '
                . implode(' | ', $shown) . (count($problems) > 10 ? ' ...' : ''),
        );
    }

    // A rewritten url() that points nowhere would show as a missing icon font or image, which is easy
    // to miss by eye, so confirm each one resolves before publishing the file.
    $broken = verifyUrls($output, $targetDir);
    if (count($broken)) {
        return array(
            'ok' => false,
            'path' => $target,
            'bytes' => 0,
            'message' => count($broken) . ' url() reference(s) do not resolve: ' . implode(' | ', array_slice($broken, 0, 10)),
        );
    }

    // Write to a temporary file in the same directory and rename over the target. Renaming within one
    // filesystem is atomic, so a request reading the stylesheet never sees a half written one, and two
    // requests regenerating at once cannot interleave.
    $temporary = tempnam($targetDir, 'fzcss');
    if ($temporary === false or false === file_put_contents($temporary, $output)) {
        if ($temporary !== false) {
            @unlink($temporary);
        }
        return array('ok' => false, 'path' => $target, 'message' => 'Could not write to ' . $targetDir, 'bytes' => 0);
    }
    @chmod($temporary, 0644); // tempnam creates 0600, and a web server has to serve this
    if (!rename($temporary, $target)) {
        @unlink($temporary);
        return array('ok' => false, 'path' => $target, 'message' => 'Could not replace ' . $target, 'bytes' => 0);
    }

    return array('ok' => true, 'path' => $target, 'message' => '', 'bytes' => strlen($output));
}

/**
 * Confirm every relative url() in the generated stylesheet resolves to a file that exists.
 *
 * @param   string  $css        The generated stylesheet
 * @param   string  $targetDir  Directory the generated file lives in
 * @return  array               Descriptions of any references that do not resolve
 */
function verifyUrls($css, $targetDir) {
    $broken = array();
    if (!preg_match_all('/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i', $css, $matches)) {
        return $broken;
    }
    foreach (array_unique($matches[2]) as $url) {
        $url = trim($url);
        // absolute URLs, protocol relative URLs, root relative paths and data URIs are not ours to check
        if ($url === '' or preg_match('#^(?:[a-z][a-z0-9+.-]*:|//|/|\#)#i', $url)) {
            continue;
        }
        $path = $targetDir . '/' . preg_replace('/[?#].*$/', '', $url); // drop ?query and #fragment
        if (!file_exists($path)) {
            $broken[] = $url;
        }
    }
    return $broken;
}

// ---------------------------------------------------------------------------------------------------
// Command line entry point. Guarded so the Drupal integration module can include this file purely for
// its functions without anything running.
// ---------------------------------------------------------------------------------------------------
if (PHP_SAPI === 'cli' and isset($argv) and realpath($argv[0]) === realpath(__FILE__)) {
    $root = formulize_drupalCssRoot();
    if (!$root) {
        fwrite(STDERR, "Could not locate the Formulize root from " . __DIR__ . "\n");
        exit(1);
    }
    $theme = isset($argv[1]) ? $argv[1] : formulize_drupalCssActiveTheme($root);
    if (!$theme) {
        fwrite(STDERR, "Could not determine the active theme. Pass one as an argument.\n");
        exit(1);
    }
    $sources = formulize_drupalCssSources($root, $theme);
    print "Theme: " . $theme . "\n";
    foreach ($sources as $source) {
        print "  source: " . $source . "\n";
    }
    $result = formulize_buildDrupalCss($root, $theme);
    if (!$result['ok']) {
        fwrite(STDERR, "FAILED: " . $result['message'] . "\n");
        exit(1);
    }
    print "Wrote " . $result['path'] . " (" . number_format($result['bytes']) . " bytes)\n";
    print "Verified: every selector is scoped to " . SCOPE . ", and every url() resolves.\n";
    exit(0);
}

/**
 * Confirm every style rule in the generated stylesheet is scoped.
 *
 * At-rules whose bodies hold declarations rather than rules (@font-face, @keyframes and friends) are
 * skipped, since they have no selectors to scope.
 *
 * @param   string  $css    The generated stylesheet
 * @return  array   Human readable descriptions of any unscoped selectors found
 */
function verifyScoped($css) {
    $problems = array();
    $length = strlen($css);
    $buffer = '';
    $i = 0;

    while ($i < $length) {
        if ($css[$i] === '/' and $i + 1 < $length and $css[$i + 1] === '*') {
            $end = strpos($css, '*/', $i + 2);
            $i = (false === $end) ? $length : $end + 2;
            continue;
        }
        if ($css[$i] === '"' or $css[$i] === "'") {
            $end = matchString($css, $i);
            $buffer .= substr($css, $i, $end - $i);
            $i = $end;
            continue;
        }
        if ($css[$i] === '{') {
            $prelude = preludeSignificant($buffer);
            $buffer = '';
            $body = readBlock($css, $i);

            if ($prelude !== '' and $prelude[0] === '@') {
                $name = strtolower(preg_replace('/^@([a-z-]+).*$/is', '$1', $prelude));
                if (in_array($name, array('media', 'supports', 'container', 'layer', 'scope'), true)) {
                    $problems = array_merge($problems, verifyScoped($body));
                }
                continue; // declaration-holding at-rules have no selectors
            }

            foreach (splitSelectors($prelude) as $selector) {
                $selector = trim($selector);
                if ($selector === '') {
                    continue;
                }
                // Must START with the scope, not merely contain it somewhere: a selector that only
                // mentions it partway along still matches elements outside the div.
                if (strpos($selector, SCOPE) !== 0) {
                    $problems[] = $selector;
                    continue;
                }
                // An at-rule that came down the selector path has been scoped as though it were a
                // selector, which breaks it. @font-face preceded by a comment is the real world case.
                if (strpos($selector, '@') !== false) {
                    $problems[] = $selector . '   <- at-rule scoped as a selector';
                }
            }
            continue;
        }
        if ($css[$i] === ';') {
            $buffer = '';
            $i++;
            continue;
        }
        $buffer .= $css[$i];
        $i++;
    }

    return $problems;
}

/**
 * Rewrite relative url() references so they still resolve from the generated file's directory.
 *
 * A source stylesheet's relative paths are relative to its own directory. The generated file lives
 * somewhere else, so those paths have to be recalculated. Absolute URLs, root relative paths and
 * data: URIs are left alone. Paths are kept relative rather than made root relative so the result
 * works whether Formulize is served from a domain root or a subfolder.
 *
 * @param   string  $css        The stylesheet text
 * @param   string  $sourceDir  Directory the stylesheet came from
 * @param   string  $targetDir  Directory the generated file will live in
 * @return  string
 */
function rewriteUrls($css, $sourceDir, $targetDir) {
    if (realpath($sourceDir) === realpath($targetDir)) {
        return $css; // same directory, so relative paths are already correct
    }
    return preg_replace_callback(
        '/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i',
        function ($matches) use ($sourceDir, $targetDir) {
            $quote = $matches[1];
            $url = trim($matches[2]);
            // leave absolute URLs, protocol relative URLs, root relative paths and data URIs alone
            if ($url === '' or preg_match('#^(?:[a-z][a-z0-9+.-]*:|//|/|\#)#i', $url)) {
                return $matches[0];
            }
            // split off any ?query or #fragment so it survives the path calculation
            $suffix = '';
            if (false !== $cut = strcspn($url, '?#') and $cut < strlen($url)) {
                $suffix = substr($url, $cut);
                $url = substr($url, 0, $cut);
            }
            $rewritten = relativePath($targetDir, $sourceDir . '/' . $url);
            return 'url(' . $quote . $rewritten . $suffix . $quote . ')';
        },
        $css
    );
}

/**
 * Express one path relative to another, without requiring either to exist.
 *
 * @param   string  $from   Directory the result should be relative to
 * @param   string  $to     Path being described
 * @return  string
 */
function relativePath($from, $to) {
    $split = function ($path) {
        $path = str_replace('\\', '/', $path);
        $parts = array();
        foreach (explode('/', $path) as $part) {
            if ($part === '' or $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }
        return $parts;
    };
    $fromParts = $split($from);
    $toParts = $split($to);
    // drop the common leading segments
    while (count($fromParts) and count($toParts) and $fromParts[0] === $toParts[0]) {
        array_shift($fromParts);
        array_shift($toParts);
    }
    return str_repeat('../', count($fromParts)) . implode('/', $toParts);
}

/**
 * Scope every selector in a stylesheet to the SCOPE selector.
 *
 * Walks the stylesheet tracking brace depth so nested at-rules are handled. At-rules that contain
 * declarations rather than rules (@font-face, @keyframes, @page, @property) are passed through
 * untouched; at-rules that contain rules (@media, @supports, @container, @layer) are recursed into.
 *
 * @param   string  $css    The stylesheet text
 * @return  string
 */
function scopeCss($css) {
    $out = '';
    $length = strlen($css);
    $buffer = ''; // text seen since the last rule or block ended
    $i = 0;

    while ($i < $length) {
        // carry comments through verbatim, so a brace inside one cannot confuse the parse
        if ($css[$i] === '/' and $i + 1 < $length and $css[$i + 1] === '*') {
            $end = strpos($css, '*/', $i + 2);
            $end = (false === $end) ? $length : $end + 2;
            $buffer .= substr($css, $i, $end - $i);
            $i = $end;
            continue;
        }

        // carry strings through verbatim for the same reason
        if ($css[$i] === '"' or $css[$i] === "'") {
            $end = matchString($css, $i);
            $buffer .= substr($css, $i, $end - $i);
            $i = $end;
            continue;
        }

        if ($css[$i] === '{') {
            $prelude = $buffer;
            $buffer = '';
            $body = readBlock($css, $i); // advances $i past the matching close brace

            // A prelude can open with comments and whitespace, so look past those to tell whether this
            // is an at-rule or a style rule. Testing the raw prelude would misread a commented at-rule
            // such as formulize.css's /* Icon Font */ @font-face as a selector, and scope it.
            $significant = preludeSignificant($prelude);

            if ($significant !== '' and $significant[0] === '@') {
                $name = strtolower(preg_replace('/^@([a-z-]+).*$/is', '$1', $significant));
                if (in_array($name, array('media', 'supports', 'container', 'layer', 'scope'), true)) {
                    $out .= $prelude . '{' . scopeCss($body) . '}'; // contains rules: recurse
                } else {
                    $out .= $prelude . '{' . $body . '}'; // contains declarations: leave alone
                }
            } elseif ($significant === '') {
                $out .= $prelude . '{' . $body . '}'; // no selector to scope
            } else {
                $out .= scopeSelectorList($prelude) . '{' . $body . '}';
            }
            continue;
        }

        // a statement level at-rule such as @charset or @import
        if ($css[$i] === ';') {
            $out .= $buffer . ';';
            $buffer = '';
            $i++;
            continue;
        }

        $buffer .= $css[$i];
        $i++;
    }

    return $out . $buffer;
}

/**
 * Read a brace delimited block, returning its contents and advancing the cursor past it.
 *
 * @param   string  $css    The stylesheet text
 * @param   int     $i      Cursor, positioned on the opening brace; left just past the closing brace
 * @return  string  The block contents, excluding the braces
 */
function readBlock($css, &$i) {
    $length = strlen($css);
    $depth = 0;
    $start = $i + 1;
    while ($i < $length) {
        $char = $css[$i];
        if ($char === '/' and $i + 1 < $length and $css[$i + 1] === '*') {
            $end = strpos($css, '*/', $i + 2);
            $i = (false === $end) ? $length : $end + 2;
            continue;
        }
        if ($char === '"' or $char === "'") {
            $i = matchString($css, $i);
            continue;
        }
        if ($char === '{') {
            $depth++;
        } elseif ($char === '}') {
            $depth--;
            if ($depth === 0) {
                $body = substr($css, $start, $i - $start);
                $i++;
                return $body;
            }
        }
        $i++;
    }
    return substr($css, $start); // unbalanced input; take what is left
}

/**
 * Find the end of a quoted string, honouring backslash escapes.
 *
 * @param   string  $css    The stylesheet text
 * @param   int     $i      Cursor, positioned on the opening quote
 * @return  int     Offset just past the closing quote
 */
function matchString($css, $i) {
    $length = strlen($css);
    $quote = $css[$i];
    $i++;
    while ($i < $length) {
        if ($css[$i] === '\\') {
            $i += 2;
            continue;
        }
        if ($css[$i] === $quote) {
            return $i + 1;
        }
        $i++;
    }
    return $length;
}

/**
 * Return the part of a rule's prelude that determines what kind of rule it is, ie: with any leading
 * whitespace and comments removed.
 *
 * @param   string  $prelude    The text preceding a rule's opening brace
 * @return  string
 */
function preludeSignificant($prelude) {
    preg_match('/^(?:\s*(?:\/\*.*?\*\/)?)*(.*)$/s', $prelude, $parts);
    return trim($parts[1]);
}

/**
 * Scope each selector in a comma separated list, preserving the surrounding whitespace and comments.
 *
 * @param   string  $prelude    The text preceding a rule's opening brace
 * @return  string
 */
function scopeSelectorList($prelude) {
    // keep any leading whitespace/comments so the output stays readable
    preg_match('/^((?:\s*\/\*.*?\*\/)*\s*)(.*)$/s', $prelude, $parts);
    $leading = $parts[1];
    $selectors = $parts[2];

    if (trim($selectors) === '') {
        return $prelude;
    }

    $scoped = array();
    foreach (splitSelectors($selectors) as $selector) {
        $scoped[] = scopeSelector($selector);
    }
    // Different source selectors can collapse onto the same scoped one - Anari's reset opens with
    // "html, body, div..." and both html and body become the scope element. Duplicates are harmless
    // but noisy, so drop them while keeping the original order.
    $scoped = array_values(array_unique($scoped));
    return $leading . implode(', ', $scoped) . ' ';
}

/**
 * Split a selector list on commas that are at the top level, ignoring commas inside :not(),
 * :is(), attribute selectors and strings.
 *
 * @param   string  $selectors  A selector list
 * @return  array
 */
function splitSelectors($selectors) {
    $out = array();
    $current = '';
    $depth = 0;
    $inBracket = false;
    $length = strlen($selectors);
    for ($i = 0; $i < $length; $i++) {
        $char = $selectors[$i];
        if ($char === '"' or $char === "'") {
            $end = matchString($selectors, $i);
            $current .= substr($selectors, $i, $end - $i);
            $i = $end - 1;
            continue;
        }
        if ($char === '(') {
            $depth++;
        } elseif ($char === ')') {
            $depth--;
        } elseif ($char === '[') {
            $inBracket = true;
        } elseif ($char === ']') {
            $inBracket = false;
        } elseif ($char === ',' and $depth === 0 and !$inBracket) {
            $out[] = $current;
            $current = '';
            continue;
        }
        $current .= $char;
    }
    if (trim($current) !== '') {
        $out[] = $current;
    }
    return $out;
}

/**
 * Scope a single selector.
 *
 * Selectors naming the document root or everything (html, body, :root, *) become the scope element
 * itself, because the real html and body elements are ancestors of it and so out of reach. Anything
 * else becomes a descendant of the scope element.
 *
 * @param   string  $selector   One selector
 * @return  string
 */
function scopeSelector($selector) {
    $selector = trim($selector);
    if ($selector === '') {
        return $selector;
    }

    // already scoped
    if (strpos($selector, SCOPE) === 0) {
        return $selector;
    }

    // a selector that IS the root or a universal selector: becomes the scope element itself
    if (preg_match('/^(?:html|body|:root|\*)$/i', $selector)) {
        return SCOPE;
    }

    // a compound on the root, ie: body.foo, html[dir=rtl], :root.bar - replace the element part.
    // These are host-page hooks that will not match inside the div anyway, but rewriting them keeps
    // the reset's `html, body, div, span...` list from reintroducing a bare body selector.
    if (preg_match('/^(?:html|body|:root|\*)(?=[.:\#\[])(.*)$/is', $selector, $matches)) {
        return SCOPE . $matches[1];
    }

    // the root used as an ancestor, ie: body .foo, html > div - drop it and scope the rest
    if (preg_match('/^(?:html|body|:root|\*)\s*([>+~]\s*|\s+)(.*)$/is', $selector, $matches)) {
        $combinator = trim($matches[1]);
        $rest = $matches[2];
        return SCOPE . ($combinator === '' ? ' ' : ' ' . $combinator . ' ') . $rest;
    }

    return SCOPE . ' ' . $selector;
}
