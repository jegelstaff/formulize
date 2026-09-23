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
//   `.fz-form` (inner) carries the design-system density tokens and the
//   label-mode modifier, exactly as the full-screen form templates do. As of
//   issue #113 no density modifier is applied here, so both levels resolve to
//   the design system's default (38px controls) and the two containers agree.
//   The nesting still stays: the levels mean different things, the drawer has
//   to mirror the containers the theme's form screens emit so shared rules
//   match, and if a density modifier is ever re-introduced as a setting it has
//   to go on this inner element. It cannot be merged onto `.fz-form-screen`,
//   which re-declares --lyris-field-height/--lyris-field-padding-x later in the stylesheet and so
//   wins on source order over any modifier placed on the same element - that
//   was measured during PR #100 (drawer controls went 32px -> 38px while full
//   screen stayed at 32px) and is why the two divs were never collapsed.
//
// The card wrapper is deliberately dropped - the drawer itself plays that part.

print "
<div class='fz-form-screen formulize-drawer-form'>
<div class='fz-form fz-form--label-top form-container'>
";
