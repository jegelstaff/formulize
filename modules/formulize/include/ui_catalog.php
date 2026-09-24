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

// The reference for Formulize UI: every public token and class in
// modules/formulize/templates/css/formulize-ui.css, with what it does and an
// example of it in use.
//
// This is the one place they are documented. The in-app style guide
// (modules/formulize/styleguide.php) renders it, and the documentation site's
// pages and the reference for AI tools are generated from it, so none of them is
// ever written by hand. When a class or token is added to, changed in or removed
// from formulize-ui.css, change this file to match. Running this file from the
// command line checks that the two agree, and that the examples use only real
// classes and tokens, and fails if they don't. The formulize-ui-check workflow
// runs it on every pull request that changes either file:
//
//     php modules/formulize/include/ui_catalog.php --check
//
// The documentation site's pages are made from this file's --json output, by
// docs/_plugins/formulize_ui_pages.rb.
//
// This file depends on nothing else in Formulize, so that it can run on its own.
//
// Class names can be written as patterns, which the check and the reference
// expand: {a,b,c} is a choice (fz-{m,p}-4 is fz-m-4 and fz-p-4), {1-6} is a range
// of numbers, and {n} is the spacing scale's steps.

define('FORMULIZE_UI_VERSION', '1');

/**
 * The spacing scale's steps, which {n} in a class pattern stands for.
 *
 * @return array of step numbers, as strings
 */
function formulize_uiSpacingSteps() {
    return array('0', '1', '2', '3', '4', '5', '6', '8', '10', '12', '16');
}

/**
 * Expand a class name pattern into the class names it stands for.
 *
 * @param string $pattern eg: 'fz-{m,p}{,x,y}-{n}'
 * @return array of class names
 */
function formulize_uiExpandPattern($pattern) {
    if (!preg_match('/\{([^{}]*)\}/', $pattern, $match, PREG_OFFSET_CAPTURE)) {
        return array($pattern);
    }
    $inside = $match[1][0];
    if ($inside === 'n') {
        $options = formulize_uiSpacingSteps();
    } elseif (preg_match('/^(\d+)-(\d+)$/', $inside, $range)) {
        $options = array_map('strval', range((int) $range[1], (int) $range[2]));
    } else {
        $options = explode(',', $inside);
    }
    $before = substr($pattern, 0, $match[0][1]);
    $after = substr($pattern, $match[0][1] + strlen($match[0][0]));
    $names = array();
    foreach ($options as $option) {
        foreach (formulize_uiExpandPattern($before . $option . $after) as $name) {
            $names[] = $name;
        }
    }
    return $names;
}

/**
 * Everything there is to know about the Formulize UI classes and tokens, as data.
 *
 * Sections hold entries. An entry has an id, a name and a summary, and
 * optionally:
 *   'classes'  class name or pattern => what it does
 *   'tokens'   custom property => its value, or what it is
 *   'notes'    a list of sentences
 *   'example'  HTML, rendered live in the style guide and shown as code
 *
 * @return array
 */
function formulize_uiCatalog() {
    $sections = array();

    // ---------------------------------------------------------------------
    $sections[] = array(
        'id' => 'start',
        'title' => 'How it works',
        'intro' => 'Formulize UI is a set of CSS classes for building markup in Formulize: screen templates, template screens, derived values and text elements that output HTML, and theme tweaks. They look right in any theme, follow the colours, fonts, text size and density set on the Appearance page, and work full screen, in the drawer, on a phone and embedded in another site. They follow the conventions of Tailwind CSS, so they will be familiar if you, or your AI tools, know Tailwind.',
        'entries' => array(
            array(
                'id' => 'rules',
                'name' => 'The rules',
                'summary' => 'Five rules cover almost everything.',
                'notes' => array(
                    'Arrange things with a layout class: `fz-stack` puts things one above the other, `fz-cluster` side by side, `fz-auto-grid` in columns. The gap between them is the only space between them, so you do not need margins.',
                    'Use a component for a thing: `fz-btn` for a button, `fz-card` for a box of content, `fz-callout` for a message, `fz-badge` for a status, `fz-field` and `fz-input` for a form field.',
                    'Adjust with a utility: `fz-mt-4` adds space above, `fz-text-muted` greys text. On the same element, a utility wins over a layout class or a component.',
                    'Don\'t hard-code colours or sizes in your own CSS. Use the tokens, such as `var(--fz-color-accent)` and `calc(var(--fz-spacing) * 4)`, so your styles follow the theme, the Appearance page and the density.',
                    'Only the classes in this reference are public. Themes have their own classes, starting `lyris-` for Lyris, and Formulize has its own internal ones starting `formulize-`; those can change with any release.',
                ),
            ),
            array(
                'id' => 'naming',
                'name' => 'Naming',
                'summary' => 'Every public class and token starts with `fz-`. The rest of the name says what kind of class it is.',
                'notes' => array(
                    'A component or layout class is a word or two: `fz-card`, `fz-auto-grid`.',
                    'A part of a component adds two underscores: `fz-card__title`.',
                    'A variation adds two dashes, and is always used with the main class: `fz-card fz-card--interactive`.',
                    'A utility is named after the Tailwind CSS class that does the same thing, with `fz-` in front: `fz-mt-4` is Tailwind\'s `mt-4`, a top margin of 4 steps.',
                    'A token is a CSS custom property, named after Tailwind\'s with `fz-` in front: `--fz-color-accent`, `--fz-text-sm`, `--fz-spacing`.',
                    'In this reference, a set of related classes is written as one pattern. `{a,b}` is a choice, so `fz-{m,p}-4` is `fz-m-4` and `fz-p-4`; an empty choice means nothing there, so `fz-m{,x}-4` is `fz-m-4` and `fz-mx-4`. `{2-6}` is each number from 2 to 6. `{n}` is each step of the spacing scale. Use the class names the pattern stands for, never the pattern itself.',
                ),
            ),
            array(
                'id' => 'not-tailwind',
                'name' => 'What is not here',
                'summary' => 'Formulize UI is not Tailwind CSS. Tailwind builds its classes from your markup with a build step; Formulize markup is written at runtime, so every class has to exist already. That rules out some of Tailwind.',
                'notes' => array(
                    'No variant prefixes: `md:`, `lg:`, `hover:`, `focus:`, `dark:`, `group-hover:` and the rest do not exist. For layouts that change with the space available, use the layout classes, which respond to their own width; for small screens there are `fz-hide-on-mobile` and `fz-show-on-mobile`. States such as hover and focus are built into the components.',
                    'No arbitrary values, such as `p-[13px]` or `w-[300px]`. Use a style attribute instead, with the tokens where you can.',
                    'No `space-x-*` or `space-y-*`: use `fz-stack` or `fz-cluster`, or a gap utility.',
                    'No palette colours, such as `text-red-600`. Colours are named by what they are for (`fz-text-danger`, `fz-bg-surface`), since they come from the theme and the Appearance page.',
                    'A fixed set of spacing steps: 0, 1, 2, 3, 4, 5, 6, 8, 10, 12 and 16. `fz-p-7` does not exist.',
                    'One text size that Tailwind does not have: `fz-text-xs-plus` (13px), between xs and sm. It is the size of the content text in lists and tables in Lyris.',
                    'Every class starts `fz-`. A Tailwind class without the prefix does nothing.',
                ),
            ),
            array(
                'id' => 'where',
                'name' => 'Where you can use it',
                'summary' => 'Anywhere Formulize outputs your HTML, in any theme.',
                'notes' => array(
                    'List screen and form screen templates, and template screens.',
                    'Derived values whose value is HTML, and Full Width Content and Captioned Content elements (under Text for display).',
                    'A theme\'s own templates and stylesheets.',
                    'In Lyris the classes match the rest of the interface. In older themes, such as Anari, they use that theme\'s colours and fonts but may not match its other styles.',
                    'AI tools connected to Formulize through MCP can read this whole reference: it is the `formulize_ui` topic of the `get_documentation` tool, which is available to users who can write custom code.',
                ),
            ),
        ),
    );

    // ---------------------------------------------------------------------
    $sections[] = array(
        'id' => 'tokens',
        'title' => 'Tokens',
        'intro' => 'Tokens are the CSS custom properties that hold the design\'s values. Use them in your own CSS and style attributes instead of fixed values, so your styles follow the theme, the Appearance page and the density: color: `var(--fz-color-text-muted)`. Sizes are given at the default settings; they are in rem, so they grow with the Appearance page\'s Text and interface size setting.',
        'entries' => array(
            array(
                'id' => 'tokens-colour',
                'name' => 'Colour',
                'summary' => 'The theme\'s palette, including any colours set on the Appearance page.',
                'tokens' => array(
                    '--fz-color-page' => 'The page background.',
                    '--fz-color-surface' => 'Cards, tables and other raised areas.',
                    '--fz-color-surface-2' => 'A slightly darker surface, for headers and hover.',
                    '--fz-color-surface-3' => 'A darker surface still, for inset areas and stripes.',
                    '--fz-color-overlay' => 'The dimming behind a dialog or the drawer.',
                    '--fz-color-border' => 'Borders and dividers.',
                    '--fz-color-border-strong' => 'The borders of controls.',
                    '--fz-color-focus' => 'The border of a focused control.',
                    '--fz-color-text' => 'Body text.',
                    '--fz-color-text-muted' => 'Secondary text.',
                    '--fz-color-text-subtle' => 'Tertiary text, such as placeholders.',
                    '--fz-color-accent' => 'The main colour: primary buttons, links, selection.',
                    '--fz-color-accent-hover' => 'The accent, on hover.',
                    '--fz-color-accent-soft' => 'A pale tint of the accent, for backgrounds.',
                    '--fz-color-accent-soft-2' => 'A slightly stronger tint of the accent.',
                    '--fz-color-accent-text' => 'Accent-coloured text, such as links.',
                    '--fz-color-on-accent' => 'Text on an accent background.',
                    '--fz-color-success' => 'Success. With `--fz-color-success-soft`, a pale tint.',
                    '--fz-color-success-soft' => 'A pale tint of success.',
                    '--fz-color-warning' => 'Warning.',
                    '--fz-color-warning-soft' => 'A pale tint of warning.',
                    '--fz-color-danger' => 'Danger, errors.',
                    '--fz-color-danger-soft' => 'A pale tint of danger.',
                    '--fz-color-info' => 'Information.',
                    '--fz-color-info-soft' => 'A pale tint of information.',
                ),
            ),
            array(
                'id' => 'tokens-type',
                'name' => 'Type',
                'summary' => 'Fonts, text sizes (each with a line height), weights and line heights. The sizes are Tailwind\'s, plus xs-plus.',
                'tokens' => array(
                    '--fz-font-sans' => 'The main font.',
                    '--fz-font-heading' => 'The secondary font, for headings and labels. The main font unless the Appearance page sets another.',
                    '--fz-font-mono' => 'A monospaced font.',
                    '--fz-text-xs' => '0.75rem (12px)',
                    '--fz-text-xs-plus' => '0.8125rem (13px). Formulize\'s own step; Lyris\'s content text.',
                    '--fz-text-sm' => '0.875rem (14px)',
                    '--fz-text-base' => '1rem (16px)',
                    '--fz-text-lg' => '1.125rem (18px)',
                    '--fz-text-xl' => '1.25rem (20px)',
                    '--fz-text-2xl' => '1.5rem (24px)',
                    '--fz-text-3xl' => '1.875rem (30px)',
                    '--fz-text-{xs,xs-plus,sm,base,lg,xl,2xl,3xl}--line-height' => 'The line height that goes with each size, as in Tailwind: 1rem for xs, 1.25rem for xs-plus and sm, 1.5rem for base, and so on.',
                    '--fz-font-weight-normal' => '400',
                    '--fz-font-weight-medium' => '500',
                    '--fz-font-weight-semibold' => '600',
                    '--fz-font-weight-bold' => '700',
                    '--fz-leading-tight' => '1.25',
                    '--fz-leading-snug' => '1.375',
                    '--fz-leading-normal' => '1.5',
                ),
            ),
            array(
                'id' => 'tokens-space',
                'name' => 'Spacing and sizes',
                'summary' => 'One step that every space and size is a multiple of, as in Tailwind: `calc(var(--fz-spacing) * 4)` is 1rem, 16px. The density changes the step, so everything built on it follows.',
                'tokens' => array(
                    '--fz-spacing' => '0.25rem (4px) at standard density; 3.5px at tight, 4.5px at comfortable.',
                    '--fz-control-height' => 'Buttons: 8 steps, 32px.',
                    '--fz-field-height' => 'Form fields: 9.5 steps, 38px.',
                    '--fz-row-height' => 'Table rows: 10 steps, 40px.',
                    '--fz-container-2xl' => '42rem, the narrow container.',
                    '--fz-container-6xl' => '72rem, the container.',
                    '--fz-container-7xl' => '80rem, the wide container.',
                ),
            ),
            array(
                'id' => 'tokens-shape',
                'name' => 'Shape, depth and motion',
                'summary' => 'Corner radii, shadows, the focus ring, and the timing of transitions.',
                'tokens' => array(
                    '--fz-radius-sm' => '0.25rem (4px)',
                    '--fz-radius-md' => '0.375rem (6px)',
                    '--fz-radius-lg' => '0.5rem (8px)',
                    '--fz-radius-xl' => '0.75rem (12px)',
                    '--fz-radius-full' => 'Fully round.',
                    '--fz-shadow-xs' => 'A hairline shadow.',
                    '--fz-shadow-sm' => 'Resting cards.',
                    '--fz-shadow-md' => 'Hovered cards.',
                    '--fz-shadow-lg' => 'Floating panels.',
                    '--fz-focus-ring' => 'The keyboard focus ring, as a box-shadow.',
                    '--fz-ease-out' => 'The easing curve for transitions.',
                    '--fz-duration-fast' => '120ms, for small changes such as hover.',
                    '--fz-duration' => '200ms.',
                ),
            ),
            array(
                'id' => 'tokens-tuning',
                'name' => 'Tuning properties',
                'summary' => 'Custom properties you can set on an element, in a style attribute, to adjust a layout class or component.',
                'tokens' => array(
                    '--fz-auto-grid-min' => 'On `fz-auto-grid` or `fz-grid-list`: the narrowest a column can be. 10rem.',
                    '--fz-sidebar-width' => 'On `fz-with-sidebar`: the width of the side. 16rem.',
                    '--fz-switcher-threshold' => 'On `fz-switcher`: the width below which its children stack. 32rem.',
                    '--fz-field-label-width' => 'On `fz-field--horizontal`: the width of the label. 12rem.',
                ),
            ),
            array(
                'id' => 'tokens-internal',
                'name' => 'Set by the classes',
                'summary' => 'Custom properties the classes set for their own use. Don\'t set these yourself: use the classes that set them.',
                'tokens' => array(
                    '--fz-gap' => 'Set by the layout classes and the `fz-gap-`* utilities.',
                    '--fz-gap-x' => 'Set by the `fz-gap-x-`* utilities.',
                    '--fz-gap-y' => 'Set by the `fz-gap-y-`* utilities.',
                    '--fz-auto-grid-max' => 'Set by the --max-N modifiers of `fz-auto-grid` and `fz-grid-list`.',
                    '--fz-tone' => 'Set by the tone modifiers of `fz-badge` and `fz-callout`.',
                    '--fz-tone-soft' => 'Set by the tone modifiers of `fz-badge` and `fz-callout`.',
                ),
            ),
            array(
                'id' => 'density',
                'name' => 'Density',
                'summary' => 'How much space there is, and how tall buttons, form fields and table rows are. The Appearance page sets it for the whole site; these classes set it for one part of a page.',
                'classes' => array(
                    'fz-density-tight' => 'Less space and shorter controls: fits more on the screen.',
                    'fz-density-standard' => 'The default.',
                    'fz-density-comfortable' => 'More space and taller controls: easier to read and to tap.',
                ),
                'example' => <<<'HTML'
<div class="fz-auto-grid fz-auto-grid--max-3">
  <div class="fz-card fz-density-tight">
    <p class="fz-card__title">Tight</p>
    <div class="fz-cluster"><button type="button" class="fz-btn fz-btn--primary">Save</button><button type="button" class="fz-btn">Cancel</button></div>
  </div>
  <div class="fz-card fz-density-standard">
    <p class="fz-card__title">Standard</p>
    <div class="fz-cluster"><button type="button" class="fz-btn fz-btn--primary">Save</button><button type="button" class="fz-btn">Cancel</button></div>
  </div>
  <div class="fz-card fz-density-comfortable">
    <p class="fz-card__title">Comfortable</p>
    <div class="fz-cluster"><button type="button" class="fz-btn fz-btn--primary">Save</button><button type="button" class="fz-btn">Cancel</button></div>
  </div>
</div>
HTML
            ),
        ),
    );

    // ---------------------------------------------------------------------
    $sections[] = array(
        'id' => 'layout',
        'title' => 'Layout',
        'intro' => 'Layout classes arrange their children. They respond to the space they are given, not to the size of the screen, so the same markup works full screen, in the drawer, on a phone and embedded in another site. Nest them to build anything. The gap is the only space between the children: their top and bottom margins are removed. Change the gap with a gap utility.',
        'entries' => array(
            array(
                'id' => 'stack',
                'name' => 'Stack',
                'summary' => 'Children one above the other. The default way to arrange sections, fields and paragraphs.',
                'classes' => array(
                    'fz-stack' => 'Children one above the other, 4 steps (16px) apart.',
                ),
                'example' => <<<'HTML'
<div class="fz-stack">
  <h3 class="fz-text-lg fz-font-semibold">Program summary</h3>
  <p>A stack puts the same space between each of its children.</p>
  <p class="fz-text-muted">Change the space with a gap utility, such as fz-gap-2.</p>
</div>
HTML
            ),
            array(
                'id' => 'cluster',
                'name' => 'Cluster',
                'summary' => 'Children side by side, vertically centred, wrapping onto new lines when they run out of room. For buttons, badges, links and other small things.',
                'classes' => array(
                    'fz-cluster' => 'Side by side, wrapping, 2 steps (8px) apart.',
                ),
                'notes' => array(
                    'To push the rest of the row to the far end, put an `fz-spacer` element before it, or `fz-ms-auto` on the first thing to push.',
                ),
                'example' => <<<'HTML'
<div class="fz-cluster">
  <span class="fz-badge fz-badge--success">Approved</span>
  <span class="fz-badge">Grade 7</span>
  <span class="fz-badge fz-badge--info">Science</span>
  <button type="button" class="fz-btn fz-btn--sm fz-ms-auto">Edit tags</button>
</div>
HTML
            ),
            array(
                'id' => 'auto-grid',
                'name' => 'Auto grid',
                'summary' => 'Equal columns: as many as fit, each at least 10rem wide. There are fewer columns as the space narrows, and one column on a phone, with nothing else to set.',
                'classes' => array(
                    'fz-auto-grid' => 'Equal columns, as many as fit, 4 steps apart.',
                    'fz-auto-grid--max-{2-6}' => 'At most this many columns.',
                    'fz-auto-grid--narrow' => 'Columns can be as narrow as 8rem, so more fit.',
                    'fz-auto-grid--wide' => 'Columns are at least 18rem, so fewer fit.',
                ),
                'notes' => array(
                    'For a different minimum, set `--fz-auto-grid-min` on the element: `style="--fz-auto-grid-min: 14rem"`.',
                    'In the drawer at its narrowest, the default fits two columns.',
                ),
                'example' => <<<'HTML'
<div class="fz-auto-grid fz-auto-grid--max-3">
  <div class="fz-card"><p class="fz-text-sm fz-text-muted">Open</p><p class="fz-text-2xl fz-font-semibold fz-tabular-nums">24</p></div>
  <div class="fz-card"><p class="fz-text-sm fz-text-muted">In review</p><p class="fz-text-2xl fz-font-semibold fz-tabular-nums">7</p></div>
  <div class="fz-card"><p class="fz-text-sm fz-text-muted">Closed this month</p><p class="fz-text-2xl fz-font-semibold fz-tabular-nums">112</p></div>
</div>
HTML
            ),
            array(
                'id' => 'with-sidebar',
                'name' => 'With sidebar',
                'summary' => 'A side of a set width next to a main area that takes the rest. When the main area would be narrower than half the space, the two stack instead.',
                'classes' => array(
                    'fz-with-sidebar' => 'The first child is the side, 16rem wide; the second is the main area.',
                    'fz-with-sidebar--end' => 'The side is the second child, at the end.',
                ),
                'notes' => array(
                    'For a different width, set `--fz-sidebar-width` on the element: `style="--fz-sidebar-width: 12rem"`.',
                ),
                'example' => <<<'HTML'
<div class="fz-with-sidebar">
  <nav class="fz-card" aria-label="Sections">
    <div class="fz-stack fz-gap-2"><a href="#">Details</a><a href="#">Contacts</a><a href="#">History</a></div>
  </nav>
  <div class="fz-card"><p>The main area takes the rest of the width.</p></div>
</div>
HTML
            ),
            array(
                'id' => 'switcher',
                'name' => 'Switcher',
                'summary' => 'Two or three things in equal columns while there is room, and all stacked when there is not: never part way.',
                'classes' => array(
                    'fz-switcher' => 'Side by side while wider than 32rem, otherwise stacked.',
                ),
                'notes' => array(
                    'To switch at a different width, set `--fz-switcher-threshold` on the element.',
                ),
                'example' => <<<'HTML'
<div class="fz-switcher">
  <div class="fz-card"><p class="fz-card__title">Billing address</p><p class="fz-text-muted">123 Main Street</p></div>
  <div class="fz-card"><p class="fz-card__title">Shipping address</p><p class="fz-text-muted">Same as billing</p></div>
</div>
HTML
            ),
            array(
                'id' => 'container',
                'name' => 'Container',
                'summary' => 'Centres content at a comfortable maximum width, with space at the sides.',
                'classes' => array(
                    'fz-container' => 'At most 72rem wide, centred.',
                    'fz-container--narrow' => 'At most 42rem: for forms and reading.',
                    'fz-container--wide' => 'At most 80rem.',
                ),
                'example' => <<<'HTML'
<div class="fz-container fz-container--narrow">
  <div class="fz-card"><p>This card is at most 42rem wide, and centred.</p></div>
</div>
HTML
            ),
            array(
                'id' => 'spacer',
                'name' => 'Spacer',
                'summary' => 'An empty element that takes up the free space in a row, pushing what follows it to the far end.',
                'classes' => array(
                    'fz-spacer' => 'Grows to fill the free space.',
                ),
                'example' => <<<'HTML'
<div class="fz-cluster">
  <button type="button" class="fz-btn fz-btn--ghost">Back</button>
  <span class="fz-spacer"></span>
  <button type="button" class="fz-btn">Save draft</button>
  <button type="button" class="fz-btn fz-btn--primary">Submit</button>
</div>
HTML
            ),
            array(
                'id' => 'layout-utilities',
                'name' => 'Layout utilities',
                'summary' => 'Tailwind\'s layout utilities, with Tailwind\'s meanings. `fz-grid` only sets `display: grid`, and `fz-grid-cols-3` is exactly three columns at every width, since there are no screen-size variants: for columns that adapt, use `fz-auto-grid`.',
                'classes' => array(
                    'fz-flex' => '`display: flex`',
                    'fz-inline-flex' => '`display: inline-flex`',
                    'fz-grid' => '`display: grid`',
                    'fz-flex-{row,col}' => 'Children in a row, or a column.',
                    'fz-flex-{wrap,nowrap}' => 'Wrap onto new lines, or don\'t.',
                    'fz-flex-1' => 'On a child: grow and shrink, ignoring its own size.',
                    'fz-flex-auto' => 'On a child: grow and shrink from its own size.',
                    'fz-flex-none' => 'On a child: neither grow nor shrink.',
                    'fz-grow' => 'On a child: take up the free space.',
                    'fz-shrink-0' => 'On a child: never narrower than its content.',
                    'fz-items-{start,center,end,baseline,stretch}' => 'Align the children across the row.',
                    'fz-justify-{start,center,end,between}' => 'Distribute the children along the row.',
                    'fz-self-{start,center,end}' => 'On a child: align this one.',
                    'fz-grid-cols-{1-6}' => 'Exactly this many equal columns.',
                    'fz-col-span-{1-6}' => 'On a child: span this many columns.',
                    'fz-col-span-full' => 'On a child: span the whole row.',
                    'fz-gap-{n}' => 'The space between the children, on a layout class or anything with `fz-flex` or `fz-grid`.',
                    'fz-gap-x-{n}' => 'The space between columns only.',
                    'fz-gap-y-{n}' => 'The space between rows only.',
                ),
                'example' => <<<'HTML'
<div class="fz-grid fz-grid-cols-3 fz-gap-4">
  <div class="fz-card fz-col-span-2">Spans two columns</div>
  <div class="fz-card">One column</div>
  <div class="fz-card fz-col-span-full">The whole row</div>
</div>
HTML
            ),
        ),
    );

    // ---------------------------------------------------------------------
    $sections[] = array(
        'id' => 'components',
        'title' => 'Components',
        'intro' => 'Things to build with. Each has a main class, classes for its parts, and classes for its variations. They draw everything from the tokens, so they follow the theme, the Appearance page and the density.',
        'entries' => array(
            array(
                'id' => 'button',
                'name' => 'Button',
                'summary' => 'An action: a `<button>`, an `<a>` or an `<input type="submit">`. A secondary button by default.',
                'classes' => array(
                    'fz-btn' => 'A secondary button.',
                    'fz-btn--primary' => 'The main action. Usually one per area.',
                    'fz-btn--danger' => 'A destructive action, such as delete.',
                    'fz-btn--ghost' => 'No border or background until hovered, for less important actions.',
                    'fz-btn--{sm,lg}' => 'Smaller or larger.',
                    'fz-btn--icon' => 'Square, for an icon on its own. Give it an aria-label.',
                    'fz-btn--block' => 'The full width of its container.',
                ),
                'notes' => array(
                    'Use a `<button>` for an action on the page and an `<a>` for going somewhere else; they look the same.',
                    'A disabled button, or one with `aria-disabled="true"`, is faded.',
                ),
                'example' => <<<'HTML'
<div class="fz-cluster">
  <button type="button" class="fz-btn fz-btn--primary">Save</button>
  <button type="button" class="fz-btn">Cancel</button>
  <a href="#" class="fz-btn fz-btn--ghost">View history</a>
  <button type="button" class="fz-btn fz-btn--danger">Delete</button>
  <button type="button" class="fz-btn fz-btn--sm">Small</button>
  <button type="button" class="fz-btn fz-btn--lg">Large</button>
  <button type="button" class="fz-btn fz-btn--icon" aria-label="Settings">&#9881;</button>
  <button type="button" class="fz-btn" disabled>Disabled</button>
</div>
HTML
            ),
            array(
                'id' => 'field',
                'name' => 'Form field',
                'summary' => 'A label, a control and its help text.',
                'classes' => array(
                    'fz-field' => 'The field: its label above its control.',
                    'fz-field__label' => 'The label: a `<label for="…">`, or a `<legend>` on a `<fieldset>`.',
                    'fz-field__req' => 'The required marker, inside the label.',
                    'fz-field__body' => 'The control and its help text.',
                    'fz-field__help' => 'Help text, or an error message.',
                    'fz-field--horizontal' => 'The label beside the control, stacking when there isn\'t room.',
                ),
                'notes' => array(
                    'For a group of choices, use `fz-field` on a `<fieldset>`, with a `<legend class="fz-field__label">`.',
                    'Connect help text to its control with aria-describedby, so screen readers read it too.',
                ),
                'example' => <<<'HTML'
<div class="fz-stack">
  <div class="fz-field">
    <label class="fz-field__label" for="sg-name">Student name <span class="fz-field__req" aria-hidden="true">*</span></label>
    <div class="fz-field__body">
      <input class="fz-input" id="sg-name" required aria-describedby="sg-name-help">
      <p class="fz-field__help" id="sg-name-help">As it appears on the student's report card.</p>
    </div>
  </div>
  <div class="fz-field fz-field--horizontal">
    <label class="fz-field__label" for="sg-school">School</label>
    <div class="fz-field__body"><input class="fz-input" id="sg-school"></div>
  </div>
</div>
HTML
            ),
            array(
                'id' => 'controls',
                'name' => 'Inputs, dropdowns and text areas',
                'summary' => 'Text-like inputs, dropdowns and text areas, sharing one look. Their height follows the density.',
                'classes' => array(
                    'fz-input' => 'An `<input>`: text, email, number, date and the like.',
                    'fz-select' => 'A `<select>`, with its own chevron.',
                    'fz-textarea' => 'A `<textarea>`, several lines, taller when dragged.',
                ),
                'notes' => array(
                    '`aria-invalid="true"` marks a control as invalid. Say what is wrong in the field\'s help text too, since the colour alone doesn\'t.',
                ),
                'example' => <<<'HTML'
<div class="fz-auto-grid fz-auto-grid--max-2 fz-auto-grid--wide">
  <input class="fz-input" placeholder="A text input" aria-label="A text input">
  <select class="fz-select" aria-label="A dropdown"><option>Grade 6</option><option>Grade 7</option></select>
  <input class="fz-input" value="not-an-email" aria-invalid="true" aria-label="An invalid input">
  <input class="fz-input" value="Disabled" disabled aria-label="A disabled input">
  <textarea class="fz-textarea" placeholder="A text area" aria-label="A text area"></textarea>
</div>
HTML
            ),
            array(
                'id' => 'input-group',
                'name' => 'Input group',
                'summary' => 'An input with text or an icon attached before or after it, such as a currency or a unit.',
                'classes' => array(
                    'fz-input-group' => 'Wraps an `fz-input` and its add-ons.',
                    'fz-input-group__addon' => 'The attached text or icon.',
                ),
                'example' => <<<'HTML'
<div class="fz-input-group" style="max-width: 20rem">
  <span class="fz-input-group__addon">$</span>
  <input class="fz-input" inputmode="decimal" value="120.00" aria-label="Program fee">
  <span class="fz-input-group__addon">CAD</span>
</div>
HTML
            ),
            array(
                'id' => 'choices',
                'name' => 'Checkboxes, radios and toggles',
                'summary' => 'Real checkboxes and radio buttons, drawn in the theme\'s colours; a toggle is a checkbox drawn as an on/off switch.',
                'classes' => array(
                    'fz-checkbox' => 'An `<input type="checkbox">`.',
                    'fz-radio' => 'An `<input type="radio">`.',
                    'fz-toggle' => 'An `<input type="checkbox">` drawn as a switch.',
                    'fz-choice' => 'A `<label>` around a checkbox, radio or toggle and its text, all of it clickable.',
                    'fz-choice-group' => 'A set of choices, one below the other.',
                ),
                'example' => <<<'HTML'
<div class="fz-switcher">
  <fieldset class="fz-field">
    <legend class="fz-field__label">Transportation</legend>
    <div class="fz-choice-group">
      <label class="fz-choice"><input type="radio" class="fz-radio" name="sg-t" checked> Bus</label>
      <label class="fz-choice"><input type="radio" class="fz-radio" name="sg-t"> Picked up by a parent</label>
    </div>
  </fieldset>
  <div class="fz-choice-group">
    <label class="fz-choice"><input type="checkbox" class="fz-checkbox" checked> Photo consent</label>
    <label class="fz-choice"><input type="checkbox" class="fz-toggle" checked> Email updates</label>
  </div>
</div>
HTML
            ),
            array(
                'id' => 'card',
                'name' => 'Card',
                'summary' => 'A surface for one thing: an entry, a summary, a group of fields. Its children are stacked 3 steps apart.',
                'classes' => array(
                    'fz-card' => 'The card.',
                    'fz-card__header' => 'A row for the title and any actions, wrapping when out of room.',
                    'fz-card__title' => 'The title, on a heading that fits the page (h2, h3…).',
                    'fz-card__subtitle' => 'A line of secondary text under the title.',
                    'fz-card__footer' => 'A row at the bottom, separated by a line.',
                    'fz-card--flush' => 'No padding: for a table or an image that should reach the edges.',
                    'fz-card--interactive' => 'The whole card is clickable, through its `fz-card__link`.',
                    'fz-card__link' => 'The one link the whole card clicks through to: on the link, or on an element around it when the link is generated for you, such as by `viewEntryLink()` in a list template.',
                ),
                'notes' => array(
                    'In an interactive card, other links and buttons still work on their own.',
                ),
                'example' => <<<'HTML'
<div class="fz-auto-grid fz-auto-grid--max-2 fz-auto-grid--wide">
  <article class="fz-card">
    <div class="fz-card__header">
      <div><h3 class="fz-card__title">Robotics club</h3><p class="fz-card__subtitle">Thursdays, 3:30 to 5pm</p></div>
      <span class="fz-badge fz-badge--success">Open</span>
    </div>
    <p>An after-school program for students in grades 6 to 8.</p>
    <div class="fz-card__footer"><span class="fz-text-muted fz-text-sm">18 of 24 places taken</span><button type="button" class="fz-btn fz-btn--sm fz-ms-auto">Register</button></div>
  </article>
  <article class="fz-card fz-card--interactive">
    <h3 class="fz-card__title"><a href="#" class="fz-card__link">Coding camp</a></h3>
    <p class="fz-text-muted">The whole card is a link.</p>
    <div class="fz-cluster"><span class="fz-badge fz-badge--warning">Waitlist</span></div>
  </article>
</div>
HTML
            ),
            array(
                'id' => 'card-lists',
                'name' => 'Card list and grid list',
                'summary' => 'A list of entries shown as cards instead of table rows: one below the other, or in columns.',
                'classes' => array(
                    'fz-card-list' => 'On a `<ul>` or `<ol>`: cards one below the other.',
                    'fz-grid-list' => 'On a `<ul>` or `<ol>`: cards in columns, like `fz-auto-grid`.',
                    'fz-grid-list--max-{2-6}' => 'At most this many columns.',
                    'fz-grid-list--{narrow,wide}' => 'Columns as narrow as 8rem, or at least 18rem.',
                ),
                'notes' => array(
                    'Give each `<li>` the `fz-card` class.',
                ),
                'example' => <<<'HTML'
<ul class="fz-grid-list fz-grid-list--max-3">
  <li class="fz-card fz-card--interactive"><h3 class="fz-card__title fz-card__link"><a href="#">Amira Haddad</a></h3><div class="fz-cluster"><span class="fz-badge fz-badge--success">Approved</span><span class="fz-badge">Grade 7</span></div></li>
  <li class="fz-card fz-card--interactive"><h3 class="fz-card__title fz-card__link"><a href="#">Liam Chen</a></h3><div class="fz-cluster"><span class="fz-badge fz-badge--warning">Under review</span><span class="fz-badge">Grade 8</span></div></li>
  <li class="fz-card fz-card--interactive"><h3 class="fz-card__title fz-card__link"><a href="#">Sofia Rossi</a></h3><div class="fz-cluster"><span class="fz-badge fz-badge--info">Submitted</span><span class="fz-badge">Grade 6</span></div></li>
</ul>
HTML
            ),
            array(
                'id' => 'table',
                'name' => 'Table',
                'summary' => 'A plain data table: a header row, a line under each row, and rows the row height, which follows the density.',
                'classes' => array(
                    'fz-table' => 'On a `<table>`.',
                    'fz-table--striped' => 'Every other row shaded.',
                ),
                'notes' => array(
                    'For a table wider than its container, wrap it in an element with `fz-overflow-x-auto`.',
                ),
                'example' => <<<'HTML'
<div class="fz-card fz-card--flush fz-overflow-x-auto">
  <table class="fz-table">
    <thead><tr><th>Student</th><th>School</th><th class="fz-text-end">Grade</th><th>Status</th></tr></thead>
    <tbody>
      <tr><td>Amira Haddad</td><td>Riverside Public School</td><td class="fz-text-end fz-tabular-nums">7</td><td><span class="fz-badge fz-badge--success">Approved</span></td></tr>
      <tr><td>Liam Chen</td><td>Lakeview Middle School</td><td class="fz-text-end fz-tabular-nums">8</td><td><span class="fz-badge fz-badge--warning">Under review</span></td></tr>
    </tbody>
  </table>
</div>
HTML
            ),
            array(
                'id' => 'badge',
                'name' => 'Badge',
                'summary' => 'A short label: a status, a category, a count.',
                'classes' => array(
                    'fz-badge' => 'A neutral badge, with a grey dot.',
                    'fz-badge--{accent,info,success,warning,danger}' => 'A coloured tint and dot.',
                    'fz-badge--plain' => 'No dot.',
                ),
                'notes' => array(
                    'The colour is in the tint and the dot; the text stays the normal text colour, so it is readable in every theme. The word should say what the status is: don\'t rely on the colour alone.',
                ),
                'example' => <<<'HTML'
<div class="fz-cluster">
  <span class="fz-badge">Draft</span>
  <span class="fz-badge fz-badge--info">Submitted</span>
  <span class="fz-badge fz-badge--warning">Under review</span>
  <span class="fz-badge fz-badge--success">Approved</span>
  <span class="fz-badge fz-badge--danger">Rejected</span>
  <span class="fz-badge fz-badge--accent fz-badge--plain">12 new</span>
</div>
HTML
            ),
            array(
                'id' => 'callout',
                'name' => 'Callout',
                'summary' => 'A message set apart from the content around it: instructions, a warning, the result of an action.',
                'classes' => array(
                    'fz-callout' => 'A callout, in the accent colour.',
                    'fz-callout--{info,success,warning,danger}' => 'A status callout.',
                    'fz-callout__title' => 'A bold first line.',
                ),
                'notes' => array(
                    'Add `role="status"` to a callout that appears because of something the user just did, so screen readers announce it.',
                ),
                'example' => <<<'HTML'
<div class="fz-stack fz-gap-3">
  <div class="fz-callout"><p class="fz-callout__title">Before you start</p><p>Have your student number and your school's address ready.</p></div>
  <div class="fz-callout fz-callout--success" role="status"><p>Your application was submitted.</p></div>
  <div class="fz-callout fz-callout--warning"><p>This entry has not been reviewed yet.</p></div>
  <div class="fz-callout fz-callout--danger"><p>The due date has passed.</p></div>
</div>
HTML
            ),
            array(
                'id' => 'toolbar',
                'name' => 'Toolbar',
                'summary' => 'A row with a group at each end, such as a heading and its buttons, wrapping when it runs out of room.',
                'classes' => array(
                    'fz-toolbar' => 'The row.',
                    'fz-toolbar__start' => 'The group at the start.',
                    'fz-toolbar__end' => 'The group at the end.',
                ),
                'example' => <<<'HTML'
<div class="fz-toolbar">
  <div class="fz-toolbar__start"><h2 class="fz-text-xl fz-font-semibold">Applications</h2><span class="fz-badge fz-badge--plain">48</span></div>
  <div class="fz-toolbar__end"><button type="button" class="fz-btn">Export</button><button type="button" class="fz-btn fz-btn--primary">New application</button></div>
</div>
HTML
            ),
            array(
                'id' => 'dl',
                'name' => 'Description list',
                'summary' => 'Labelled values, such as an entry\'s fields in a card. On a `<dl>`; each `<dt>` and `<dd>` pair may be wrapped in a `<div>`.',
                'classes' => array(
                    'fz-dl' => 'Terms and values side by side.',
                    'fz-dl--stacked' => 'Each term above its value.',
                ),
                'example' => <<<'HTML'
<div class="fz-switcher">
  <dl class="fz-dl">
    <dt>Student</dt><dd>Amira Haddad</dd>
    <dt>School</dt><dd>Riverside Public School</dd>
    <dt>Grade</dt><dd>7</dd>
  </dl>
  <dl class="fz-dl fz-dl--stacked">
    <div><dt>Submitted</dt><dd>September 12, 2026</dd></div>
    <div><dt>Status</dt><dd><span class="fz-badge fz-badge--success">Approved</span></dd></div>
  </dl>
</div>
HTML
            ),
            array(
                'id' => 'empty',
                'name' => 'Empty state',
                'summary' => 'What to show where there is nothing to show yet, and what to do about it.',
                'classes' => array(
                    'fz-empty' => 'A centred, outlined area.',
                    'fz-empty__title' => 'Its heading.',
                ),
                'example' => <<<'HTML'
<div class="fz-empty">
  <p class="fz-empty__title">No applications yet</p>
  <p>They will appear here once students start submitting them.</p>
  <button type="button" class="fz-btn fz-btn--primary">Share the form</button>
</div>
HTML
            ),
        ),
    );

    // ---------------------------------------------------------------------
    $sections[] = array(
        'id' => 'utilities',
        'title' => 'Utilities',
        'intro' => 'Utilities do one thing each, with Tailwind\'s names and meanings. On the same element, a utility wins over a layout class or a component. `{n}` is a spacing step: 0, 1, 2, 3, 4, 5, 6, 8, 10, 12 or 16, each 4px at standard density. s and e mean start and end, which follow the text direction; l and r are always left and right, so prefer s and e.',
        'entries' => array(
            array(
                'id' => 'u-spacing',
                'name' => 'Margin and padding',
                'summary' => 'Space outside (margin) and inside (padding) an element. Every property comes in every step.',
                'classes' => array(
                    'fz-{m,p}-{n}' => 'All sides.',
                    'fz-{m,p}{x,y}-{n}' => 'Left and right (x), or top and bottom (y).',
                    'fz-{m,p}{t,b}-{n}' => 'Top, or bottom.',
                    'fz-{m,p}{s,e}-{n}' => 'Start, or end.',
                    'fz-{m,p}{l,r}-{n}' => 'Left, or right.',
                    'fz-m{,x,s,e,l,r}-auto' => 'Automatic margins: centre a block (`fz-mx-auto`), or push it to one end of a row (`fz-ms-auto`).',
                ),
                'notes' => array(
                    'The more specific one wins: `fz-m-4 fz-mt-0` leaves the top margin at 0.',
                    'A margin utility on a child of a layout class wins over the layout class\'s margin reset.',
                ),
            ),
            array(
                'id' => 'u-display',
                'name' => 'Display and visibility',
                'summary' => 'Show, hide, and change how an element is laid out.',
                'classes' => array(
                    'fz-{block,inline-block,inline}' => 'Display type.',
                    'fz-hidden' => 'Hidden.',
                    'fz-sr-only' => 'Hidden on screen but read by screen readers: a label for something only an icon shows.',
                    'fz-hide-on-mobile' => 'Hidden on screens narrower than 48rem (768px).',
                    'fz-show-on-mobile' => 'Hidden on screens 48rem and wider.',
                ),
            ),
            array(
                'id' => 'u-width',
                'name' => 'Width',
                'summary' => 'Width, and whether an element can shrink.',
                'classes' => array(
                    'fz-w-{full,auto,fit}' => 'The full width of its container, its natural width, or the width of its content.',
                    'fz-max-w-full' => 'Never wider than its container.',
                    'fz-min-w-0' => 'On a child of a flex or grid container: may get narrower than its content. `fz-truncate` needs it there.',
                ),
            ),
            array(
                'id' => 'u-text',
                'name' => 'Text',
                'summary' => 'Size, weight, font, line height, alignment, colour and wrapping.',
                'classes' => array(
                    'fz-text-{xs,xs-plus,sm,base,lg,xl,2xl,3xl}' => 'Size, with its line height.',
                    'fz-font-{normal,medium,semibold,bold}' => 'Weight.',
                    'fz-font-{sans,heading,mono}' => 'The main font, the secondary font, or the monospaced font.',
                    'fz-leading-{tight,snug,normal}' => 'Line height.',
                    'fz-text-{start,center,end,left,right}' => 'Alignment.',
                    'fz-text-{default,muted,subtle}' => 'The normal, secondary or tertiary text colour.',
                    'fz-text-{accent,on-accent,success,warning,danger,info}' => 'Coloured text. For short, emphasised text such as a total or a status word, not paragraphs: some themes\' status colours are too light for body text.',
                    'fz-truncate' => 'One line, cut off with an ellipsis.',
                    'fz-text-nowrap' => 'Never wraps.',
                    'fz-wrap-break-word' => 'Long words and URLs break rather than overflow.',
                    'fz-tabular-nums' => 'Figures of equal width, so columns of numbers line up.',
                ),
                'example' => <<<'HTML'
<div class="fz-stack fz-gap-2">
  <p class="fz-text-2xl fz-font-semibold fz-tabular-nums">$12,480.00</p>
  <p class="fz-text-muted fz-text-sm">Raised so far this year</p>
  <p><span class="fz-text-success fz-font-semibold">+18%</span> <span class="fz-text-muted">compared with last year</span></p>
</div>
HTML
            ),
            array(
                'id' => 'u-colour',
                'name' => 'Background, border, radius and shadow',
                'summary' => 'Surfaces and edges, from the tokens.',
                'classes' => array(
                    'fz-bg-{page,surface,surface-2,surface-3}' => 'Surface backgrounds.',
                    'fz-bg-{accent,accent-soft}' => 'The accent, or a pale tint of it.',
                    'fz-bg-{success,warning,danger,info}-soft' => 'A pale status tint.',
                    'fz-border' => 'A 1px border on every side.',
                    'fz-border-{t,b,s,e}' => 'A 1px border on one side.',
                    'fz-border-0' => 'No border.',
                    'fz-border-{strong,accent,success,warning,danger,info}' => 'The border\'s colour, with a border class.',
                    'fz-rounded-{none,sm,md,lg,xl,full}' => 'Corner radius.',
                    'fz-shadow-{none,xs,sm,md,lg}' => 'Shadow.',
                ),
                'example' => <<<'HTML'
<div class="fz-cluster fz-gap-4">
  <div class="fz-p-4 fz-bg-surface fz-border fz-rounded-lg fz-shadow-sm">Surface</div>
  <div class="fz-p-4 fz-bg-accent-soft fz-rounded-lg">Accent tint</div>
  <div class="fz-p-4 fz-bg-warning-soft fz-border fz-border-warning fz-rounded-lg">Warning</div>
</div>
HTML
            ),
            array(
                'id' => 'u-overflow',
                'name' => 'Overflow and lists',
                'summary' => 'What happens to content that doesn\'t fit, and list markers.',
                'classes' => array(
                    'fz-overflow-{auto,x-auto,hidden}' => 'Scroll when needed, scroll sideways only, or clip.',
                    'fz-list-none' => 'No bullets or numbers.',
                ),
            ),
        ),
    );

    // ---------------------------------------------------------------------
    $sections[] = array(
        'id' => 'recipes',
        'title' => 'Recipes',
        'intro' => 'Complete examples of the classes at work in the places Formulize runs your code, ready to copy. Change the element handles to your own form\'s.',
        'entries' => array(
            array(
                'id' => 'recipe-list-cards',
                'name' => 'A list screen as cards',
                'summary' => 'Show a list of entries as cards in columns instead of a table, each card opening its entry. On the list screen\'s Templates tab, replace the open list, list item and close list templates.',
                'notes' => array(
                    '`viewEntryLink()` makes the link that opens the entry; it has no class of its own, so put `fz-card__link` on the element around it. With `fz-card--interactive`, the whole card then opens the entry.',
                    '`display()` gives the value of a field in the entry. Pass it through `htmlspecialchars()` before printing it, so a value that contains HTML shows as text.',
                    '`fz-p-4` keeps the cards off the edges of the list area. Some themes, such as Anari, already pad it, and there you can leave it out.',
                    'Use `fz-card-list` instead of `fz-grid-list` for cards one below the other.',
                    'To colour the badges by status, see the next recipe.',
                ),
                'example' => <<<'HTML'
<ul class="fz-grid-list fz-grid-list--max-3">
  <li class="fz-card fz-card--interactive">
    <h3 class="fz-card__title fz-card__link"><a href="#">Amira Haddad</a></h3>
    <p class="fz-card__subtitle">Riverside Public School</p>
    <div class="fz-cluster"><span class="fz-badge">Approved</span></div>
  </li>
  <li class="fz-card fz-card--interactive">
    <h3 class="fz-card__title fz-card__link"><a href="#">Liam Chen</a></h3>
    <p class="fz-card__subtitle">Lakeview Middle School</p>
    <div class="fz-cluster"><span class="fz-badge">Under review</span></div>
  </li>
  <li class="fz-card fz-card--interactive">
    <h3 class="fz-card__title fz-card__link"><a href="#">Sofia Rossi</a></h3>
    <p class="fz-card__subtitle">Hillcrest Academy</p>
    <div class="fz-cluster"><span class="fz-badge">Submitted</span></div>
  </li>
</ul>
HTML
                ,
                'code' => array(
                    array(
                        'label' => 'Open list template',
                        'code' => <<<'CODE'
<?php
print "<ul class='fz-grid-list fz-grid-list--max-3 fz-p-4'>";
CODE
                    ),
                    array(
                        'label' => 'List item template',
                        'code' => <<<'CODE'
<?php
$student = htmlspecialchars(display($entry, 'applications_student'));
$school = htmlspecialchars(display($entry, 'applications_school'));
$status = htmlspecialchars(display($entry, 'applications_status'));
print "
<li class='fz-card fz-card--interactive'>
  <h3 class='fz-card__title fz-card__link'>" . viewEntryLink($student) . "</h3>
  <p class='fz-card__subtitle'>$school</p>
  <div class='fz-cluster'><span class='fz-badge'>$status</span></div>
</li>";
CODE
                    ),
                    array(
                        'label' => 'Close list template',
                        'code' => <<<'CODE'
<?php
print "</ul>";
CODE
                    ),
                ),
            ),
            array(
                'id' => 'recipe-derived-badge',
                'name' => 'A status as a badge',
                'summary' => 'A derived value that shows a status as a coloured badge, in lists and wherever else the value appears.',
                'notes' => array(
                    'Choose the tone from the value, and keep the word: the colour adds to the word, it doesn\'t replace it.',
                    'Formulize filters the HTML in a derived value, to keep scripts out of the page. Classes are kept, so the badge comes through.',
                ),
                'example' => <<<'HTML'
<div class="fz-cluster">
  <span class="fz-badge fz-badge--info">Submitted</span>
  <span class="fz-badge fz-badge--warning">Under review</span>
  <span class="fz-badge fz-badge--success">Approved</span>
  <span class="fz-badge fz-badge--danger">Rejected</span>
</div>
HTML
                ,
                'code' => array(
                    array(
                        'label' => 'Derived value',
                        'code' => <<<'CODE'
<?php
$tones = array(
    'Submitted' => 'info',
    'Under review' => 'warning',
    'Approved' => 'success',
    'Rejected' => 'danger',
);
$tone = isset($tones[$applications_status]) ? ' fz-badge--' . $tones[$applications_status] : '';
$value = "<span class='fz-badge$tone'>" . htmlspecialchars($applications_status) . "</span>";
CODE
                    ),
                ),
            ),
            array(
                'id' => 'recipe-form-intro',
                'name' => 'An introduction to a form',
                'summary' => 'Instructions at the top of a form, set apart from the fields. Put the HTML in a Full Width Content element, under Text for display.',
                'example' => <<<'HTML'
<div class="fz-callout">
  <p class="fz-callout__title">Before you start</p>
  <p>Have your student number and your school's address ready. The form takes about ten minutes, and you can save it and come back.</p>
</div>
HTML
            ),
            array(
                'id' => 'recipe-dashboard',
                'name' => 'A dashboard',
                'summary' => 'A template screen that sums up a form\'s entries, with a count for each status in a row of cards. The template screen\'s code gathers the numbers; its template lays them out.',
                'notes' => array(
                    'In the template, `<{$name}>` prints a variable the code set. The values are prepared in the code, escaped, so the template only arranges them.',
                    '`fz-container` centres the dashboard and gives it space at the sides, since a template screen has only what its template gives it.',
                ),
                'example' => <<<'HTML'
<div class="fz-stack fz-gap-6">
  <div class="fz-toolbar">
    <div class="fz-toolbar__start"><h2 class="fz-text-xl fz-font-semibold">Applications</h2></div>
  </div>
  <div class="fz-auto-grid fz-auto-grid--max-4">
    <div class="fz-card"><p class="fz-text-sm fz-text-muted">Submitted</p><p class="fz-text-3xl fz-font-semibold fz-tabular-nums">48</p></div>
    <div class="fz-card"><p class="fz-text-sm fz-text-muted">Under review</p><p class="fz-text-3xl fz-font-semibold fz-tabular-nums">12</p></div>
    <div class="fz-card"><p class="fz-text-sm fz-text-muted">Approved</p><p class="fz-text-3xl fz-font-semibold fz-tabular-nums">31</p></div>
    <div class="fz-card"><p class="fz-text-sm fz-text-muted">Rejected</p><p class="fz-text-3xl fz-font-semibold fz-tabular-nums">5</p></div>
  </div>
</div>
HTML
                ,
                'code' => array(
                    array(
                        'label' => 'Template screen code',
                        'code' => <<<'CODE'
<?php
$counts = array('Submitted' => 0, 'Under review' => 0, 'Approved' => 0, 'Rejected' => 0);
foreach (gatherDataset(12) as $application) {
    $status = display($application, 'applications_status');
    if (isset($counts[$status])) {
        $counts[$status]++;
    }
}
$cards = '';
foreach ($counts as $status => $count) {
    $cards .= "<div class='fz-card'><p class='fz-text-sm fz-text-muted'>" . htmlspecialchars($status) . "</p>"
        . "<p class='fz-text-3xl fz-font-semibold fz-tabular-nums'>$count</p></div>";
}
CODE
                    ),
                    array(
                        'label' => 'Template screen template',
                        'code' => <<<'CODE'
<div class="fz-container fz-py-6 fz-stack fz-gap-6">
  <div class="fz-toolbar">
    <div class="fz-toolbar__start"><h2 class="fz-text-xl fz-font-semibold">Applications</h2></div>
  </div>
  <div class="fz-auto-grid fz-auto-grid--max-4"><{$cards}></div>
</div>
CODE
                    ),
                ),
            ),
        ),
    );

    // ---------------------------------------------------------------------
    $sections[] = array(
        'id' => 'themes',
        'title' => 'For theme authors',
        'intro' => 'How a theme works with Formulize UI: giving the tokens its values, loading the stylesheet, and keeping its own rules from getting in the way of the classes.',
        'entries' => array(
            array(
                'id' => 'theme-tokens',
                'name' => 'Giving the tokens values',
                'summary' => 'Formulize UI sets a neutral default for every token. A theme gives them its own values in a `:root` rule, and the colours and font set on the Appearance page then override the theme\'s.',
                'notes' => array(
                    'Set the tokens, rather than restyling the classes: every class is built on them, so the theme\'s look reaches all of them at once, and the Appearance page keeps working.',
                    'The sizes are in rem, so the Appearance page\'s Text and interface size setting scales them. Keep a theme\'s own sizes in rem too.',
                ),
                'code' => array(
                    array(
                        'label' => 'In the theme\'s stylesheet',
                        'code' => <<<'CODE'
:root {
  --fz-color-accent: #0f5e9c;
  --fz-color-accent-hover: #0b4a7a;
  --fz-color-accent-soft: #e7f0f8;
  --fz-font-sans: "Source Sans 3", system-ui, sans-serif;
  --fz-radius-lg: 0.25rem;
}
CODE
                    ),
                ),
            ),
            array(
                'id' => 'theme-loading',
                'name' => 'Loading the stylesheet',
                'summary' => 'Formulize adds `formulize-ui.css` to its own pages. A theme adds it to every other page by calling `formulize_uiStylesheetLink()` in its `theme.html`, before the theme\'s own stylesheet.',
                'notes' => array(
                    'The function returns nothing on a page that already has the stylesheet, so it is never loaded twice.',
                    'The order matters: `formulize-ui.css` first, then Formulize\'s own stylesheet, then the theme\'s. Where a theme rule and a class have the same weight, the theme wins.',
                ),
                'code' => array(
                    array(
                        'label' => 'In theme.html, in the head',
                        'code' => <<<'CODE'
<{php}>
require_once XOOPS_ROOT_PATH . '/modules/formulize/include/functions.php';
echo formulize_uiStylesheetLink();
<{/php}>
<link rel="stylesheet" type="text/css" media="all" href="<{$icms_imageurl}>css/style.css" />
CODE
                    ),
                ),
            ),
            array(
                'id' => 'theme-weight',
                'name' => 'Keeping theme rules out of the way',
                'summary' => 'Every public class is a single class selector, with no `!important`, so a utility or a modifier can always adjust it. A theme keeps that working by not outweighing the classes.',
                'notes' => array(
                    'Give the theme\'s own classes the theme\'s prefix, such as `lyris-`, never `fz-`.',
                    'When styling Formulize\'s own markup, wrap the selector in `:where()`, which gives it no weight, so a class added to that markup still wins.',
                    'A rule that styles every element of a kind, such as every `button` or every checkbox, would also restyle the Formulize UI components. Add `:where(:not([class*="fz-"]))` to it: it skips any element with a Formulize UI class, and adds no weight, so the rule otherwise works as before.',
                ),
                'code' => array(
                    array(
                        'label' => 'In the theme\'s stylesheet',
                        'code' => <<<'CODE'
/* Formulize's own markup: no weight, so classes added to it still win */
:where(#formulize-list-of-entries) td {
  padding: 0.5rem 0.75rem;
}

/* Every button, except the Formulize UI ones */
button:where(:not([class*="fz-"])),
input[type="submit"]:where(:not([class*="fz-"])) {
  min-width: 8rem;
  border-radius: 0;
}
CODE
                    ),
                ),
            ),
        ),
    );

    return array(
        'version' => FORMULIZE_UI_VERSION,
        'title' => 'Formulize UI',
        'summary' => 'The CSS classes and tokens for any HTML you write for Formulize: derived values, Full Width Content and Captioned Content elements, screen templates and themes. Read it before writing HTML or CSS, and use only the classes it lists.',
        'sections' => $sections,
    );
}

/**
 * Every class and token the catalog documents, expanded.
 *
 * @param array $catalog from formulize_uiCatalog()
 * @return array with 'classes' and 'tokens', each a sorted list of names
 */
function formulize_uiCatalogNames($catalog) {
    $classes = array();
    $tokens = array();
    foreach ($catalog['sections'] as $section) {
        foreach ($section['entries'] as $entry) {
            foreach (array_keys(isset($entry['classes']) ? $entry['classes'] : array()) as $pattern) {
                foreach (formulize_uiExpandPattern($pattern) as $name) {
                    $classes[$name] = true;
                }
            }
            foreach (array_keys(isset($entry['tokens']) ? $entry['tokens'] : array()) as $pattern) {
                foreach (formulize_uiExpandPattern($pattern) as $name) {
                    $tokens[$name] = true;
                }
            }
        }
    }
    $classes = array_keys($classes);
    $tokens = array_keys($tokens);
    sort($classes);
    sort($tokens);
    return array('classes' => $classes, 'tokens' => $tokens);
}

/**
 * Every public class and token that formulize-ui.css defines.
 *
 * @param string $css the stylesheet's contents
 * @return array with 'classes' and 'tokens', each a sorted list of names
 */
function formulize_uiStylesheetNames($css) {
    $css = preg_replace('/\/\*.*?\*\//s', '', $css);
    preg_match_all('/\.(fz-[a-z0-9-]+(?:__[a-z0-9-]+)?)/', $css, $classMatches);
    preg_match_all('/(--fz-[a-z0-9-]+)\s*:/', $css, $tokenMatches);
    $classes = array_values(array_unique($classMatches[1]));
    $tokens = array_values(array_unique($tokenMatches[1]));
    sort($classes);
    sort($tokens);
    return array('classes' => $classes, 'tokens' => $tokens);
}

/**
 * Every example and piece of code in the catalog, as text.
 *
 * @param array $catalog from formulize_uiCatalog()
 * @return array of arrays with 'where' (the entry's name) and 'text'
 */
function formulize_uiCatalogSamples($catalog) {
    $samples = array();
    foreach ($catalog['sections'] as $section) {
        foreach ($section['entries'] as $entry) {
            if (!empty($entry['example'])) {
                $samples[] = array('where' => $entry['name'], 'text' => $entry['example']);
            }
            foreach (isset($entry['code']) ? $entry['code'] : array() as $code) {
                $samples[] = array('where' => $entry['name'] . ', ' . $code['label'], 'text' => $code['code']);
            }
        }
    }
    return $samples;
}

/**
 * Compare the catalog with formulize-ui.css: the classes and tokens it documents,
 * and the ones its examples and code use.
 *
 * @param array $catalog from formulize_uiCatalog()
 * @param string $css the stylesheet's contents
 * @return array of problems, as sentences; empty when the two agree
 */
function formulize_uiCheckCatalog($catalog, $css) {
    $documented = formulize_uiCatalogNames($catalog);
    $defined = formulize_uiStylesheetNames($css);
    $problems = array();
    foreach (array('classes' => 'class', 'tokens' => 'token') as $kind => $noun) {
        foreach (array_diff($defined[$kind], $documented[$kind]) as $name) {
            $problems[] = "The $noun $name is in formulize-ui.css but not in the catalog.";
        }
        foreach (array_diff($documented[$kind], $defined[$kind]) as $name) {
            $problems[] = "The $noun $name is in the catalog but not in formulize-ui.css.";
        }
    }
    // A class name is fz- not preceded by a dash or a word character (which would
    // make it part of a token or another name), followed by at least one letter or
    // number, so a partial name such as the fz- in [class*="fz-"] is not counted.
    foreach (formulize_uiCatalogSamples($catalog) as $sample) {
        preg_match_all('/(?<![\w-])fz-[a-z0-9]+(?:(?:-|--|__)[a-z0-9]+)*/', $sample['text'], $classMatches);
        preg_match_all('/--fz-[a-z0-9]+(?:-[a-z0-9]+)*/', $sample['text'], $tokenMatches);
        foreach (array_unique($classMatches[0]) as $name) {
            if (!in_array($name, $documented['classes'])) {
                $problems[] = "The example in $sample[where] uses the class $name, which is not in the catalog.";
            }
        }
        foreach (array_unique($tokenMatches[0]) as $name) {
            if (!in_array($name, $documented['tokens'])) {
                $problems[] = "The example in $sample[where] uses the token $name, which is not in the catalog.";
            }
        }
    }
    return $problems;
}

/**
 * The catalog as one Markdown document, for AI tools: everything the style guide
 * and the documentation site show, with the examples as code. The MCP server's
 * get_documentation tool serves it as the formulize_ui topic.
 *
 * @param array $catalog from formulize_uiCatalog()
 * @return string Markdown
 */
function formulize_uiAiReference($catalog) {
    $lines = array();
    $lines[] = "# $catalog[title] reference (version $catalog[version])";
    $lines[] = '';
    $lines[] = $catalog['summary'] . ' Every public class and token is listed here: a class that is not listed does not exist, and a Tailwind CSS class without the fz- prefix does nothing.';
    foreach ($catalog['sections'] as $section) {
        $lines[] = '';
        $lines[] = "## $section[title]";
        if (!empty($section['intro'])) {
            $lines[] = '';
            $lines[] = $section['intro'];
        }
        foreach ($section['entries'] as $entry) {
            $lines[] = '';
            $lines[] = "### $entry[name]";
            $lines[] = '';
            $lines[] = $entry['summary'];
            foreach (array('classes' => 'Classes', 'tokens' => 'Tokens') as $kind => $heading) {
                if (empty($entry[$kind])) {
                    continue;
                }
                $lines[] = '';
                $lines[] = "$heading:";
                foreach ($entry[$kind] as $name => $description) {
                    $lines[] = "- `$name`: $description";
                }
            }
            if (!empty($entry['notes'])) {
                $lines[] = '';
                foreach ($entry['notes'] as $note) {
                    $lines[] = "- $note";
                }
            }
            // a recipe's example is its result; its code is what to write
            if (!empty($entry['example']) AND empty($entry['code'])) {
                $lines[] = '';
                $lines[] = 'Example:';
                $lines[] = '';
                $lines[] = formulize_uiMarkdownCode($entry['example']);
            }
            foreach (isset($entry['code']) ? $entry['code'] : array() as $code) {
                $lines[] = '';
                $lines[] = "$code[label]:";
                $lines[] = '';
                $lines[] = formulize_uiMarkdownCode($code['code']);
            }
        }
    }
    return implode("\n", $lines) . "\n";
}

/**
 * A fenced Markdown code block, marked with its language.
 *
 * @param string $code HTML, PHP (starting with a <?php tag) or CSS
 * @return string Markdown
 */
function formulize_uiMarkdownCode($code) {
    $code = rtrim($code);
    if (substr($code, 0, 5) == '<?php') {
        $language = 'php';
    } elseif (substr(ltrim($code), 0, 1) == '<') {
        $language = 'html';
    } else {
        $language = 'css';
    }
    return "```$language\n$code\n```";
}

// Run from the command line:
//   php modules/formulize/include/ui_catalog.php --check     checks the catalog against formulize-ui.css
//   php modules/formulize/include/ui_catalog.php --json      prints the catalog, for the documentation site
//   php modules/formulize/include/ui_catalog.php --markdown  prints the reference for AI tools
if (PHP_SAPI === 'cli' AND isset($argv[0]) AND realpath($argv[0]) === __FILE__) {
    if (in_array('--check', $argv)) {
        $css = file_get_contents(dirname(__DIR__) . '/templates/css/formulize-ui.css');
        $problems = formulize_uiCheckCatalog(formulize_uiCatalog(), $css);
        foreach ($problems as $problem) {
            fwrite(STDERR, $problem . "\n");
        }
        if ($problems) {
            exit(1);
        }
        $names = formulize_uiCatalogNames(formulize_uiCatalog());
        echo 'The catalog documents all ' . count($names['classes']) . ' classes and ' . count($names['tokens']) . " tokens in formulize-ui.css.\n";
        exit(0);
    }
    if (in_array('--markdown', $argv)) {
        echo formulize_uiAiReference(formulize_uiCatalog());
        exit(0);
    }
    if (in_array('--json', $argv)) {
        echo json_encode(formulize_uiCatalog(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
        exit(0);
    }
    fwrite(STDERR, "Usage: php modules/formulize/include/ui_catalog.php --check | --json | --markdown\n");
    exit(2);
}
