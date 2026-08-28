<?php

// Lyris's drawer wrapper.
//
// This is the only theme-level drawer override in the tree: the system default
// (modules/formulize/templates/screens/default/drawer/toptemplate.php) emits a
// single `.form-container`, and that is all a theme needs when it scopes its
// form field styling to that one class - which is why Anari has no override at
// all and renders from the default.
//
// Lyris needs two nested containers because its own form and multiPage screens
// have two, and the rule for drawer templates is that they emit the same
// containers the theme's form screens do. The split is not cosmetic; each level
// does a different job:
//
//   `.fz-form-screen` (outer) is the scoping root for Lyris's field styling.
//   Roughly eighty rules in themes/Lyris/css/style.css hang off it - inputs,
//   selects, textareas, radios/checkboxes, file and range and colour controls,
//   multi-selects and the autocomplete. Without it the drawer renders unstyled
//   browser-default fields.
//
//   `.fz-form--compact` (inner) sets the density. It cannot be merged onto the
//   outer element: `.fz-form-screen` re-declares --field-h/--field-pad-x later
//   in the stylesheet than `.fz-form--compact` does, so on a single element the
//   alias wins on source order and the fields render at the 38px default
//   instead of Lyris's 32px compact size. Measured, by collapsing the two divs
//   into one and reading the computed styles in the drawer: control height went
//   32px -> 38px, while Lyris full screen stayed at 32px. That is exactly the
//   full-screen/in-drawer mismatch this screen type exists to remove, so the
//   nesting stays.
//
// The card wrapper is deliberately dropped - the drawer itself plays that part.

print "
<div class='fz-form-screen formulize-drawer-form'>
<div class='fz-form fz-form--label-top fz-form--compact form-container'>
";
