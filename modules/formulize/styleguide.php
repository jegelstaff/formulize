<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################
##  Author of this file: Formulize Incorporated                              ##
##  Project: Formulize                                                       ##
###############################################################################

// The Formulize UI style guide: every public token and class, with each example
// rendered live in this site's own theme, Appearance settings and density, next
// to the markup that produces it. Everything on the page comes from the catalog
// in include/ui_catalog.php; nothing about the classes is written here.
//
// Webmasters only, for now: they are the people who edit templates and screens.
// Linked from Admin > Appearance.

require_once "../../mainfile.php";

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/common.php';
include_once XOOPS_ROOT_PATH . '/modules/formulize/include/ui_catalog.php';

global $xoopsUser;
if (!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
	redirect_header(XOOPS_URL, 3, _NOPERM);
	exit();
}

include_once XOOPS_ROOT_PATH . '/header.php';

global $xoTheme;
if ($xoTheme) {
	$xoTheme->addStylesheet(formulize_uiStylesheetPath());
}

$catalog = formulize_uiCatalog();

/**
 * A class name, token or pattern as it appears in a table: in code, with the
 * separators in a pattern left breakable.
 *
 * @param string $name
 * @return string HTML
 */
function formulize_styleguideName($name) {
	return '<code>' . str_replace(',', ',<wbr>', htmlspecialchars($name, ENT_QUOTES)) . '</code>';
}

/**
 * Text from the catalog, which marks code the way Markdown does, in backticks.
 *
 * @param string $text
 * @return string HTML
 */
function formulize_styleguideText($text) {
	return preg_replace('/`([^`]+)`/', '<code>$1</code>', htmlspecialchars($text, ENT_QUOTES));
}

?>
<style>
/* This page's own layout. The classes are formulize-, since they are Formulize's
   internals; everything else on the page is Formulize UI's public classes. */
.formulize-styleguide code,
.formulize-styleguide pre {
	font-family: var(--fz-font-mono);
	font-size: var(--fz-text-xs-plus);
}
/* The page's own reading styles, rather than the theme's (Anari's reset sets the
   line height to 1 and removes list bullets). */
.formulize-styleguide { line-height: var(--fz-leading-normal); }
.formulize-styleguide__notes { list-style: disc; }
/* inline code in running text doesn't break at its hyphens */
.formulize-styleguide :where(p, li) > code { white-space: nowrap; }
.formulize-styleguide__nav {
	position: sticky;
	top: calc(var(--fz-spacing) * 4);
	align-self: flex-start;
	max-height: calc(100vh - 6rem);
	overflow: auto;
}
.formulize-styleguide__nav a { color: var(--fz-color-text-muted); text-decoration: none; }
.formulize-styleguide__nav a:hover { color: var(--fz-color-text); text-decoration: underline; }
.formulize-styleguide__example {
	padding: calc(var(--fz-spacing) * 5);
	background: var(--fz-color-page);
	border: 1px dashed var(--fz-color-border-strong);
	border-radius: var(--fz-radius-lg);
	margin-inline: auto;
	width: 100%;
	box-sizing: border-box;
}
.formulize-styleguide__code {
	position: relative;
}
.formulize-styleguide__code pre {
	margin: 0;
	padding: calc(var(--fz-spacing) * 4);
	padding-right: calc(var(--fz-spacing) * 20);
	overflow: auto;
	background: var(--fz-color-surface-3);
	border-radius: var(--fz-radius-md);
	white-space: pre;
}
.formulize-styleguide__copy {
	position: absolute;
	top: calc(var(--fz-spacing) * 2);
	right: calc(var(--fz-spacing) * 2);
}
.formulize-styleguide__names td:first-child { width: 40%; }
.formulize-styleguide__names td { white-space: normal; }
</style>

<div class="formulize-styleguide fz-container fz-container--wide fz-py-6">
	<div class="fz-stack fz-gap-6">

		<div class="fz-toolbar">
			<div class="fz-toolbar__start">
				<h1 class="fz-text-2xl fz-font-semibold">Formulize UI style guide</h1>
				<span class="fz-badge fz-badge--plain">Version <?php echo htmlspecialchars($catalog['version']); ?></span>
			</div>
		</div>

		<p class="fz-text-muted">Every class and token you can use in templates, template screens, derived values and text elements, shown in this site's theme and its Appearance settings. Copy the markup under any example to start from it.</p>

		<div class="fz-cluster fz-gap-6" role="group" aria-label="How the examples are shown">
			<label class="fz-cluster fz-gap-2" for="formulize-styleguide-density">Density
				<select class="fz-select fz-w-auto" id="formulize-styleguide-density">
					<option value="">This site's setting</option>
					<option value="fz-density-tight">Tight</option>
					<option value="fz-density-standard">Standard</option>
					<option value="fz-density-comfortable">Comfortable</option>
				</select>
			</label>
			<label class="fz-cluster fz-gap-2" for="formulize-styleguide-width">Width
				<select class="fz-select fz-w-auto" id="formulize-styleguide-width">
					<option value="">Full width</option>
					<option value="350px">350px: the drawer at its narrowest</option>
					<option value="358px">358px: a phone</option>
					<option value="600px">600px</option>
				</select>
			</label>
			<label class="fz-choice"><input type="checkbox" class="fz-checkbox" id="formulize-styleguide-rtl"> Right to left</label>
		</div>

		<div class="fz-with-sidebar fz-gap-8" style="--fz-sidebar-width: 14rem">

			<nav class="formulize-styleguide__nav fz-hide-on-mobile" aria-label="Style guide contents">
				<ul class="fz-list-none fz-p-0 fz-stack fz-gap-4">
				<?php foreach ($catalog['sections'] as $section) { ?>
					<li class="fz-stack fz-gap-1">
						<a class="fz-font-semibold" href="#sg-<?php echo $section['id']; ?>"><?php echo htmlspecialchars($section['title']); ?></a>
						<ul class="fz-list-none fz-p-0 fz-stack fz-gap-1 fz-text-sm">
						<?php foreach ($section['entries'] as $entry) { ?>
							<li><a href="#sg-<?php echo $entry['id']; ?>"><?php echo htmlspecialchars($entry['name']); ?></a></li>
						<?php } ?>
						</ul>
					</li>
				<?php } ?>
				</ul>
			</nav>

			<div class="fz-stack fz-gap-12 fz-min-w-0">
			<?php foreach ($catalog['sections'] as $section) { ?>
				<section class="fz-stack fz-gap-8" id="sg-<?php echo $section['id']; ?>" aria-labelledby="sg-<?php echo $section['id']; ?>-title">
					<div class="fz-stack fz-gap-2">
						<h2 class="fz-text-xl fz-font-semibold" id="sg-<?php echo $section['id']; ?>-title"><?php echo htmlspecialchars($section['title']); ?></h2>
						<?php if (!empty($section['intro'])) { ?><p><?php echo formulize_styleguideText($section['intro']); ?></p><?php } ?>
					</div>

					<?php foreach ($section['entries'] as $entry) { ?>
					<article class="fz-stack fz-gap-3" id="sg-<?php echo $entry['id']; ?>">
						<h3 class="fz-text-lg fz-font-semibold"><?php echo htmlspecialchars($entry['name']); ?></h3>
						<p><?php echo formulize_styleguideText($entry['summary']); ?></p>

						<?php foreach (array('classes' => 'Class', 'tokens' => 'Token') as $kind => $heading) {
							if (empty($entry[$kind])) { continue; } ?>
						<div class="fz-card fz-card--flush fz-overflow-x-auto">
							<table class="fz-table formulize-styleguide__names">
								<thead><tr><th scope="col"><?php echo $heading; ?></th><th scope="col"><?php echo $kind == 'tokens' ? 'Value' : 'What it does'; ?></th></tr></thead>
								<tbody>
								<?php foreach ($entry[$kind] as $name => $description) { ?>
									<tr><td><?php echo formulize_styleguideName($name); ?></td><td><?php echo formulize_styleguideText($description); ?></td></tr>
								<?php } ?>
								</tbody>
							</table>
						</div>
						<?php } ?>

						<?php if (!empty($entry['notes'])) { ?>
						<ul class="formulize-styleguide__notes fz-stack fz-gap-2 fz-ps-5">
						<?php foreach ($entry['notes'] as $note) { ?>
							<li><?php echo formulize_styleguideText($note); ?></li>
						<?php } ?>
						</ul>
						<?php } ?>

						<?php if (!empty($entry['example'])) { ?>
						<div class="formulize-styleguide__example" data-formulize-styleguide-example>
							<?php echo $entry['example']; ?>
						</div>
						<div class="formulize-styleguide__code">
							<pre><code><?php echo htmlspecialchars(rtrim($entry['example']), ENT_QUOTES); ?></code></pre>
							<button type="button" class="fz-btn fz-btn--sm formulize-styleguide__copy" data-formulize-styleguide-copy>Copy</button>
						</div>
						<?php } ?>
					</article>
					<?php } ?>
				</section>
			<?php } ?>
			</div>

		</div>
	</div>
</div>

<script>
(function () {
	var examples = document.querySelectorAll('[data-formulize-styleguide-example]');
	function each(fn) { Array.prototype.forEach.call(examples, fn); }
	document.getElementById('formulize-styleguide-density').addEventListener('change', function () {
		var density = this.value;
		each(function (example) {
			example.classList.remove('fz-density-tight', 'fz-density-standard', 'fz-density-comfortable');
			if (density) { example.classList.add(density); }
		});
	});
	document.getElementById('formulize-styleguide-width').addEventListener('change', function () {
		var width = this.value;
		// the width is the content's: the frame's own padding and border go on top
		each(function (example) { example.style.maxWidth = width ? 'calc(' + width + ' + var(--fz-spacing) * 10 + 2px)' : ''; });
	});
	document.getElementById('formulize-styleguide-rtl').addEventListener('change', function () {
		var rtl = this.checked;
		each(function (example) { example.dir = rtl ? 'rtl' : ''; });
	});
	// The examples' own links go nowhere.
	each(function (example) {
		example.addEventListener('click', function (event) {
			if (event.target.closest('a[href="#"]')) { event.preventDefault(); }
		});
	});
	Array.prototype.forEach.call(document.querySelectorAll('[data-formulize-styleguide-copy]'), function (button) {
		button.addEventListener('click', function () {
			var code = button.parentNode.querySelector('code').textContent;
			var done = function () {
				button.textContent = 'Copied';
				setTimeout(function () { button.textContent = 'Copy'; }, 1500);
			};
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(code).then(done);
			} else {
				var area = document.createElement('textarea');
				area.value = code;
				document.body.appendChild(area);
				area.select();
				document.execCommand('copy');
				document.body.removeChild(area);
				done();
			}
		});
	});
})();
</script>
<?php
include XOOPS_ROOT_PATH . '/footer.php';
