<?php
// The words of the looks (include/appearance.php), the Looks tab (admin/looks.php)
// and the appearance editor (appearance_editor.php and include/js/appearance_editor.js).
// Loaded by include/appearance.php.
//
// %s is filled in with a name or a number; where a sentence has more than one, they
// are numbered (%1$s, %2$s), so a translation can put them in another order. In words
// that have a blank, write a % sign as %%.

// ---- Looks, and what they are made of (include/appearance.php) ----------------

define('_AM_APPEARANCE_LOOK_DEFAULT', 'Default'); // the name the first look is given

// the site's colours, in the editor and on the Looks tab
define('_AM_APPEARANCE_COLOUR_PRIMARY', 'Primary');
define('_AM_APPEARANCE_COLOUR_PRIMARY_DESC', 'Buttons, links, selected rows, focus rings');
define('_AM_APPEARANCE_COLOUR_BACKGROUND', 'Page background');
define('_AM_APPEARANCE_COLOUR_BACKGROUND_DESC', 'The backdrop behind all content');
define('_AM_APPEARANCE_COLOUR_SURFACE', 'Surface');
define('_AM_APPEARANCE_COLOUR_SURFACE_DESC', 'Cards, tables, panels and other raised areas');
define('_AM_APPEARANCE_COLOUR_TEXT', 'Text');
define('_AM_APPEARANCE_COLOUR_TEXT_DESC', 'Main text colour; muted and subtle text are derived from it');
define('_AM_APPEARANCE_COLOUR_BORDER', 'Borders');
define('_AM_APPEARANCE_COLOUR_BORDER_DESC', 'Dividers and outlines; strong borders are derived');
define('_AM_APPEARANCE_COLOUR_SUCCESS', 'Success');
define('_AM_APPEARANCE_COLOUR_SUCCESS_DESC', 'Positive statuses and badges');
define('_AM_APPEARANCE_COLOUR_WARNING', 'Warning');
define('_AM_APPEARANCE_COLOUR_WARNING_DESC', 'Caution statuses and badges');
define('_AM_APPEARANCE_COLOUR_DANGER', 'Danger');
define('_AM_APPEARANCE_COLOUR_DANGER_DESC', 'Errors, destructive actions');
define('_AM_APPEARANCE_COLOUR_INFO', 'Info');
define('_AM_APPEARANCE_COLOUR_INFO_DESC', 'Informational statuses and badges');

// the font choices: the theme's own font is named by the theme
define('_AM_APPEARANCE_FONT_DEFAULT', '%s (default)');
define('_AM_APPEARANCE_FONT_SYSTEM', 'System UI');
define('_AM_APPEARANCE_FONT_SYSTEM_LABEL', 'System UI (no webfont)');
define('_AM_APPEARANCE_FONT_CUSTOM', 'Other Google Font…');
define('_AM_APPEARANCE_FONT_HEADING_SAME', 'Same as the main font');

// the sizes: Standard, and the presets in include/appearance_sizes
define('_AM_APPEARANCE_SIZE_STANDARD', 'Standard');
define('_AM_APPEARANCE_SIZE_STANDARD_DESC', 'The theme\'s own sizes.');
define('_AM_APPEARANCE_SIZE_COMPACT', 'Compact');
define('_AM_APPEARANCE_SIZE_COMPACT_DESC', 'Smaller text, and shorter fields, buttons and list rows, with less space in forms, cards and the drawer: more fits on the screen.');
define('_AM_APPEARANCE_SIZE_COMFORTABLE', 'Comfortable');
define('_AM_APPEARANCE_SIZE_COMFORTABLE_DESC', 'Larger and roomier: larger text, taller fields, buttons and list rows, and more space, so it is easier to read and to tap.');

// the logo and favicon uploads
define('_AM_APPEARANCE_UPLOAD_LOGO', 'logo');
define('_AM_APPEARANCE_UPLOAD_LOGO_TYPES', 'PNG, JPEG, GIF, SVG, or WebP');
define('_AM_APPEARANCE_UPLOAD_FAVICON', 'favicon');
define('_AM_APPEARANCE_UPLOAD_FAVICON_TYPES', 'PNG, ICO, SVG, GIF, JPEG, or WebP');
define('_AM_APPEARANCE_UPLOAD_NOT_MOVED', 'Could not move the uploaded %1$s into %2$s. Check the folder permissions.');
define('_AM_APPEARANCE_UPLOAD_WRONG_TYPE', 'The %1$s must be a %2$s image.');

// what a look changes, on the Looks tab: a label and what it is for each kind of
// change, and a phrase for each, for a sentence ("logo, 2 colours, Compact sizes")
define('_AM_APPEARANCE_CHANGE_LOGO', 'Logo');
define('_AM_APPEARANCE_CHANGE_FAVICON', 'Favicon');
define('_AM_APPEARANCE_CHANGE_COLOURS', 'Colours');
define('_AM_APPEARANCE_CHANGE_FONT', 'Font');
define('_AM_APPEARANCE_CHANGE_FONTS', 'Fonts');
define('_AM_APPEARANCE_CHANGE_WIDTH', 'Page width');
define('_AM_APPEARANCE_CHANGE_SIZE', 'Size');
define('_AM_APPEARANCE_CHANGE_PART', 'Part');
define('_AM_APPEARANCE_CHANGE_PARTS', 'Parts');
define('_AM_APPEARANCE_CHANGE_THEME_OWN', 'The %s theme\'s'); // a logo, font or width that is the theme's
define('_AM_APPEARANCE_CHANGE_MAIN_FONT', 'the main font');
define('_AM_APPEARANCE_CHANGE_HEADINGS', '%s for headings');
define('_AM_APPEARANCE_CHANGE_FULL_WIDTH', 'Full width');
define('_AM_APPEARANCE_CHANGE_AT_MOST', '%spx at most');
define('_AM_APPEARANCE_CHANGE_CUSTOM_SIZE', 'Custom · %s');
define('_AM_APPEARANCE_CHANGE_MORE', '%1$s and %2$s more');
define('_AM_APPEARANCE_PHRASE_LOGO', 'logo');
define('_AM_APPEARANCE_PHRASE_FAVICON', 'favicon');
define('_AM_APPEARANCE_PHRASE_COLOUR', '%s colour');
define('_AM_APPEARANCE_PHRASE_COLOURS', '%s colours');
define('_AM_APPEARANCE_PHRASE_FONT', 'font');
define('_AM_APPEARANCE_PHRASE_FONTS', 'fonts');
define('_AM_APPEARANCE_PHRASE_WIDTH', 'page width');
define('_AM_APPEARANCE_PHRASE_NAMED_SIZE', '%s sizes');
define('_AM_APPEARANCE_PHRASE_SIZE', '%s size');
define('_AM_APPEARANCE_PHRASE_SIZES', '%s sizes');
define('_AM_APPEARANCE_PHRASE_PART', '%s part change');
define('_AM_APPEARANCE_PHRASE_PARTS', '%s part changes');

// why something couldn't be done to a look
define('_AM_APPEARANCE_ERR_NOT_SAVED', 'The %1$s theme\'s looks could not be saved: the web server cannot write to %2$s. Make that folder writable and try again.');
define('_AM_APPEARANCE_ERR_NAME_EMPTY', 'Please give the look a name.');
define('_AM_APPEARANCE_ERR_NAME_LONG', 'Please give the look a name of 60 characters or fewer.');
define('_AM_APPEARANCE_ERR_NAME_BAD', 'Please give the look another name.');
define('_AM_APPEARANCE_ERR_NAME_TAKEN', '%1$s already has a look called %2$s. Please give this one another name.');
define('_AM_APPEARANCE_ERR_BASE_GONE', 'The look to base it on is gone. Please choose another.');
define('_AM_APPEARANCE_ERR_LOOK_GONE', 'That look is gone.');
define('_AM_APPEARANCE_ERR_LOOK_GONE_UNSAVED', 'That look is gone, so nothing was saved.');
define('_AM_APPEARANCE_ERR_BASE_SELF', '%s can\'t be based on itself.');
define('_AM_APPEARANCE_ERR_BASE_DESCENDANT', '%1$s can\'t be based on %2$s, which is based on it.');
define('_AM_APPEARANCE_ERR_DELETE_SITE', '%s is the site look, so it can\'t be deleted. Change the site look first.');
define('_AM_APPEARANCE_ERR_DELETE_CHILDREN', '%s can\'t be deleted while other looks are based on it. Change what they are based on first.');
define('_AM_APPEARANCE_ERR_NOT_DELETED', 'The look could not be deleted: the web server cannot change %s. Make that folder writable and try again.');

// ---- The Looks tab (admin/looks.php) ------------------------------------------

// what was done, or why it couldn't be
define('_AM_APPEARANCE_LOOKS_DONE_CREATE', 'Created %s.');
define('_AM_APPEARANCE_LOOKS_DONE_DUPLICATE', 'Created %s, a copy.');
define('_AM_APPEARANCE_LOOKS_DONE_RENAME', 'Renamed it %s.');
define('_AM_APPEARANCE_LOOKS_DONE_REBASE', 'Changed what %s is based on.');
define('_AM_APPEARANCE_LOOKS_DONE_SITE', '%s is now the site look.');
define('_AM_APPEARANCE_LOOKS_DONE_SITE_THEME', '%1$s is now the site look for %2$s.');
define('_AM_APPEARANCE_LOOKS_DONE_DELETE', 'Deleted %s.');
define('_AM_APPEARANCE_LOOKS_ERR_THEME', 'Nothing was changed: that theme\'s looks can\'t be managed here.');
define('_AM_APPEARANCE_LOOKS_ERR_LOCKED', 'Nothing was changed, because this system is locked. Turn off the lock in the Formulize preferences to make changes.');
define('_AM_APPEARANCE_LOOKS_ERR_TOKEN', 'Nothing was changed, because the page had been open too long. Please try again.');
define('_AM_APPEARANCE_LOOKS_ERR_ACTION', 'Nothing was changed: that isn\'t something that can be done to a look.');
define('_AM_APPEARANCE_LOOKS_LOCKED', 'Nothing can be changed, because this system is locked. Turn off the lock in the Formulize preferences to make changes.');
define('_AM_APPEARANCE_LOOKS_NOT_WRITABLE', 'Nothing can be changed for the %1$s theme: the web server cannot write to %2$s. Make that folder writable on your server.');
define('_AM_APPEARANCE_LOOKS_NO_THEMES', 'None of the themes installed here has looks to manage: a theme has them when it provides sample screens for the appearance editor.');

// the top of the tab
define('_AM_APPEARANCE_LOOKS_THEME', 'Theme');
define('_AM_APPEARANCE_LOOKS_THEME_ACTIVE', '%s (active)');
define('_AM_APPEARANCE_LOOKS_SHOW', 'Show');
define('_AM_APPEARANCE_LOOKS_SITE_LOOK_IS', 'Site look:');
define('_AM_APPEARANCE_LOOKS_SITE_LOOK_IS_THEME', 'Site look when %s is active:');
define('_AM_APPEARANCE_LOOKS_CHANGE_SITE', 'Change site look');
define('_AM_APPEARANCE_LOOKS_CHANGE_SITE_THEME', 'Change site look for %s');
define('_AM_APPEARANCE_LOOKS_CREATE', 'Create new look');
define('_AM_APPEARANCE_LOOKS_INTRO', 'A look is the logo, colours, fonts, page width and sizes that pages are shown with. The site uses one look, and a screen can use another. A look based on another follows it, except for what it changes itself.');
define('_AM_APPEARANCE_LOOKS_OTHER_THEME_TITLE', 'You\'re looking at %s\'s looks.');
define('_AM_APPEARANCE_LOOKS_OTHER_THEME', 'The site\'s theme is %1$s. Choosing a theme here doesn\'t change it; that\'s in %2$s.');
define('_AM_APPEARANCE_LOOKS_SETTINGS_LINK', 'Appearance › Settings');

// the list
define('_AM_APPEARANCE_LOOKS_TABLE', '%s\'s looks');
define('_AM_APPEARANCE_LOOKS_COL_LOOK', 'Look');
define('_AM_APPEARANCE_LOOKS_COL_BASE', 'Based on');
define('_AM_APPEARANCE_LOOKS_COL_CHANGES', 'Changes');
define('_AM_APPEARANCE_LOOKS_COL_USED', 'Used by');
define('_AM_APPEARANCE_LOOKS_COL_ACTIONS', 'Actions');
define('_AM_APPEARANCE_LOOKS_SITE_LOOK', 'Site look');
define('_AM_APPEARANCE_LOOKS_NOTHING', 'No base look');
define('_AM_APPEARANCE_LOOKS_THEME_SETTINGS', 'the %s theme'); // what a look with no base look starts from, in a sentence

// the Changes column, and its popover
define('_AM_APPEARANCE_LOOKS_BRANDING', 'Branding');
define('_AM_APPEARANCE_LOOKS_LAYOUT', 'Layout');
define('_AM_APPEARANCE_LOOKS_PAGE_WIDTH', 'Page width');
define('_AM_APPEARANCE_LOOKS_CUSTOM_SIZE', 'Custom size');
define('_AM_APPEARANCE_LOOKS_PART', '%s part');
define('_AM_APPEARANCE_LOOKS_PARTS', '%s parts');
define('_AM_APPEARANCE_LOOKS_CHANGES_ARE', 'Changes: %s');
define('_AM_APPEARANCE_LOOKS_NO_CHANGES', 'No changes');
define('_AM_APPEARANCE_LOOKS_BASED_ON', 'Based on');
define('_AM_APPEARANCE_LOOKS_CHANGES', 'Changes');
define('_AM_APPEARANCE_LOOKS_IMAGE', '%1$s\'s %2$s'); // a look's logo or favicon, for people who can't see it
define('_AM_APPEARANCE_LOOKS_EVERYTHING_ELSE', 'Everything else comes from %s.');
define('_AM_APPEARANCE_LOOKS_NOTHING_YET', 'Nothing yet: it looks the same as %s.');

// the Used by column, and its popover
define('_AM_APPEARANCE_LOOKS_NOT_USED', 'Not used');
define('_AM_APPEARANCE_LOOKS_USED_SITE', 'The site');
define('_AM_APPEARANCE_LOOKS_USED_SITE_SCREEN', 'The site, %s screen');
define('_AM_APPEARANCE_LOOKS_USED_SITE_SCREENS', 'The site, %s screens');
define('_AM_APPEARANCE_LOOKS_USED_SCREEN', '%s screen');
define('_AM_APPEARANCE_LOOKS_USED_SCREENS', '%s screens');
define('_AM_APPEARANCE_LOOKS_USED_NO_SCREENS', 'No screens');
define('_AM_APPEARANCE_LOOKS_INDIRECTLY', '+%s indirectly');
define('_AM_APPEARANCE_LOOKS_THE_SITE', 'The site');
define('_AM_APPEARANCE_LOOKS_EVERY_PAGE', 'Every page');
define('_AM_APPEARANCE_LOOKS_FOLLOWS_IT', '%s screen follows it');
define('_AM_APPEARANCE_LOOKS_FOLLOW_IT', '%s screens follow it');
define('_AM_APPEARANCE_LOOKS_WHEN_THEME', 'when %s is the site\'s theme');
define('_AM_APPEARANCE_LOOKS_SET_TO_IT', 'Screens set to it');
define('_AM_APPEARANCE_LOOKS_THROUGH', 'Through looks based on it');
define('_AM_APPEARANCE_LOOKS_VIA', 'via %s');
define('_AM_APPEARANCE_LOOKS_REACHES', 'A change to %s reaches these too, except where their look sets its own.');

// a look's menus
define('_AM_APPEARANCE_LOOKS_EDIT', 'Edit');
define('_AM_APPEARANCE_LOOKS_SIMPLE', 'Simple editor');
define('_AM_APPEARANCE_LOOKS_SIMPLE_NOTE', 'Logo, colours, fonts, page width');
define('_AM_APPEARANCE_LOOKS_SIMPLE_NOTE_SIZE', 'Logo, colours, fonts, page width and size');
define('_AM_APPEARANCE_LOOKS_ADVANCED', 'Advanced editor');
define('_AM_APPEARANCE_LOOKS_ADVANCED_NOTE', 'Every part, one at a time');
define('_AM_APPEARANCE_LOOKS_SIMPLE_ONLY', '%s has the simple editor only.');
define('_AM_APPEARANCE_LOOKS_MORE', 'More for %s');
define('_AM_APPEARANCE_LOOKS_CREATE_BASED', 'Create look based on this');
define('_AM_APPEARANCE_LOOKS_DUPLICATE', 'Duplicate');
define('_AM_APPEARANCE_LOOKS_RENAME', 'Rename');
define('_AM_APPEARANCE_LOOKS_REBASE', 'Change base');
define('_AM_APPEARANCE_LOOKS_SET_SITE', 'Set as site look');
define('_AM_APPEARANCE_LOOKS_SET_SITE_THEME', 'Set as site look for %s');
define('_AM_APPEARANCE_LOOKS_DELETE', 'Delete');

// the dialogs
define('_AM_APPEARANCE_LOOKS_NAME', 'Name');
define('_AM_APPEARANCE_LOOKS_CANCEL', 'Cancel');
define('_AM_APPEARANCE_LOOKS_CLOSE', 'Close');
define('_AM_APPEARANCE_LOOKS_BASE_NOTHING', 'No base look: start from the %s theme');
define('_AM_APPEARANCE_LOOKS_BASE_THIS', '(this look)');
define('_AM_APPEARANCE_LOOKS_BASE_DESCENDANT', '(based on this look)');
define('_AM_APPEARANCE_LOOKS_CREATE_BASE_HELP', 'The new look follows its base, except for what you change in it.');
define('_AM_APPEARANCE_LOOKS_SIZE', 'Size');
define('_AM_APPEARANCE_LOOKS_SIZE_SAME', 'The same as its base');
define('_AM_APPEARANCE_LOOKS_SIZE_HELP', 'A size is a starting point: its values are written into the new look, which you can then change.');
define('_AM_APPEARANCE_LOOKS_CREATE_SUBMIT', 'Create look');
define('_AM_APPEARANCE_LOOKS_SITE_LEGEND', 'Every page uses the site look, except screens set to another');
define('_AM_APPEARANCE_LOOKS_NOW', 'now');
define('_AM_APPEARANCE_LOOKS_LIST', ' (%s)'); // what a look changes, after its name in a sentence
define('_AM_APPEARANCE_LOOKS_DUPLICATE_TITLE', 'Duplicate %s');
define('_AM_APPEARANCE_LOOKS_COPY_NAME', '%s copy');
define('_AM_APPEARANCE_LOOKS_DUPLICATE_TEXT', 'The copy is based on %1$s, like %2$s, and starts with %2$s\'s own changes%3$s. It looks the same, but won\'t follow %2$s after this.');
define('_AM_APPEARANCE_LOOKS_DUPLICATE_TEXT_NO_BASE', 'The copy has no base look, like %1$s, and starts with %1$s\'s own changes%2$s. It looks the same, but won\'t follow %1$s after this.');
define('_AM_APPEARANCE_LOOKS_DUPLICATE_CHILDREN', 'The looks based on %s aren\'t copied.');
define('_AM_APPEARANCE_LOOKS_RENAME_TITLE', 'Rename %s');
define('_AM_APPEARANCE_LOOKS_RENAME_TEXT', 'Screens and looks that use it keep using it.');
define('_AM_APPEARANCE_LOOKS_REBASE_TITLE', 'Change what %s is based on');
define('_AM_APPEARANCE_LOOKS_REBASE_TEXT', '%1$s keeps its own changes%2$s, and takes everything else from its new base. Any of its changes that are the same as the new base\'s are dropped.');
define('_AM_APPEARANCE_LOOKS_REBASE_CHILDREN', 'The looks based on %s move with it.');
define('_AM_APPEARANCE_LOOKS_SITE_TITLE', 'Set %s as the site look?');
define('_AM_APPEARANCE_LOOKS_SITE_TEXT', 'Every page will use %s, except screens set to another look.');
define('_AM_APPEARANCE_LOOKS_SITE_TEXT_THEME', 'When %1$s is the site\'s theme, every page will use %2$s, except screens set to another look.');
define('_AM_APPEARANCE_LOOKS_SITE_NOW', 'It is %s now.');
define('_AM_APPEARANCE_LOOKS_DELETE_CANT_TITLE', '%s can\'t be deleted');
define('_AM_APPEARANCE_LOOKS_DELETE_SITE', '%s is the site look. Change the site look first, then delete it.');
define('_AM_APPEARANCE_LOOKS_DELETE_SITE_THEME', '%1$s is the site look for %2$s. Change the site look first, then delete it.');
define('_AM_APPEARANCE_LOOKS_DELETE_YET_TITLE', '%s can\'t be deleted yet');
define('_AM_APPEARANCE_LOOKS_DELETE_CHILD', '1 look is based on %s:');
define('_AM_APPEARANCE_LOOKS_DELETE_CHILDREN', '%1$s looks are based on %2$s:');
define('_AM_APPEARANCE_LOOKS_DELETE_CHILD_FIRST', 'Change what it is based on, or delete it, first.');
define('_AM_APPEARANCE_LOOKS_DELETE_CHILDREN_FIRST', 'Change what they are based on, or delete them, first.');
define('_AM_APPEARANCE_LOOKS_DELETE_TITLE', 'Delete %s?');
define('_AM_APPEARANCE_LOOKS_DELETE_SCREENS', 'These screens use %1$s, and will use the site look, %2$s, instead:');
define('_AM_APPEARANCE_LOOKS_DELETE_UNUSED', 'Nothing uses %s.');
define('_AM_APPEARANCE_LOOKS_DELETE_UNDONE', 'This can\'t be undone.');
define('_AM_APPEARANCE_LOOKS_DELETE_SUBMIT', 'Delete look');

// ---- The appearance editor's page (appearance_editor.php) ---------------------

define('_AM_APPEARANCE_EDITOR_TITLE', 'Appearance - %s');
define('_AM_APPEARANCE_EDITOR_HEADING', 'Appearance');
define('_AM_APPEARANCE_EDITOR_NO_SAMPLES', 'The %s theme can\'t be edited here: it has no sample screens to preview its appearance on (see "What Formulize expects from a theme" in the documentation).');
define('_AM_APPEARANCE_EDITOR_BACK', 'Back to Looks');
define('_AM_APPEARANCE_EDITOR_BASED_ON', '%1$s · based on %2$s');
define('_AM_APPEARANCE_EDITOR_BASED_ON_NOTHING', '%s · no base look');
define('_AM_APPEARANCE_EDITOR_MODE', 'Editor');
define('_AM_APPEARANCE_EDITOR_SIMPLE', 'Simple');
define('_AM_APPEARANCE_EDITOR_ADVANCED', 'Advanced');
define('_AM_APPEARANCE_EDITOR_PREVIEW', 'Preview');
define('_AM_APPEARANCE_EDITOR_PREVIEW_WIDTH', 'Preview width');
define('_AM_APPEARANCE_EDITOR_PHONE', 'Phone');
define('_AM_APPEARANCE_EDITOR_SETTINGS', 'Settings');
define('_AM_APPEARANCE_EDITOR_ERR_LOCKED', 'Nothing was saved, because this system is locked. Turn off the lock in the Formulize preferences to make changes.');
define('_AM_APPEARANCE_EDITOR_ERR_TOKEN', 'Nothing was saved, because the page had been open too long. Reload the page, make the changes again and save.');
define('_AM_APPEARANCE_EDITOR_LOCKED', 'Nothing can be saved, because this system is locked. Turn off the lock in the Formulize preferences to make changes.');
define('_AM_APPEARANCE_EDITOR_NOT_WRITABLE', 'Nothing can be saved for the %1$s theme: the web server cannot write to %2$s, and was not able to correct the permissions itself. Make that folder writable on your server.');
define('_AM_APPEARANCE_EDITOR_UNSUPPORTED', 'The %s theme does not use these settings, so what is saved for it is kept, but won\'t change how the theme looks. A theme uses them by calling formulize_renderAppearanceHead() in its theme.html.');

// ---- The appearance editor's script (include/js/appearance_editor.js) ---------
// Every constant starting _AM_APPEARANCE_EDITOR_JS_ goes to the script, which knows
// it by the rest of its name: _AM_APPEARANCE_EDITOR_JS_SAVE is SAVE there (see
// formulize_scriptText() and "Translating text" in the docs).

// the page
define('_AM_APPEARANCE_EDITOR_JS_SAVE', 'Save');
define('_AM_APPEARANCE_EDITOR_JS_SAVING', 'Saving…');
define('_AM_APPEARANCE_EDITOR_JS_DESKTOP', 'Desktop');
define('_AM_APPEARANCE_EDITOR_JS_PHONES', 'Phones (768px and below)');
define('_AM_APPEARANCE_EDITOR_JS_HINT_SIMPLE', 'Change the look’s logo, colours, fonts, page width and size on the right.');
define('_AM_APPEARANCE_EDITOR_JS_HINT_ADVANCED', 'Click anything in the preview to change it.');
define('_AM_APPEARANCE_EDITOR_JS_HINT_PART', '%1$s · click to change %2$s');
define('_AM_APPEARANCE_EDITOR_JS_UNSAVED_CHANGE', '%s unsaved change');
define('_AM_APPEARANCE_EDITOR_JS_UNSAVED_CHANGES', '%s unsaved changes');
define('_AM_APPEARANCE_EDITOR_JS_RESET_CONFIRM', 'Throw away the changes you haven’t saved, so %s is as it was last saved?');
define('_AM_APPEARANCE_EDITOR_JS_CLOSE', 'Close');

// what a save reaches, and what it did
define('_AM_APPEARANCE_EDITOR_JS_REACH_SCREEN', '%s screen');
define('_AM_APPEARANCE_EDITOR_JS_REACH_SCREENS', '%s screens');
define('_AM_APPEARANCE_EDITOR_JS_REACH_SITE', 'the site');
define('_AM_APPEARANCE_EDITOR_JS_REACH_SITE_AND', 'the site and %s');
define('_AM_APPEARANCE_EDITOR_JS_REACH_WHEN', '%1$s when %2$s is the site’s theme');
define('_AM_APPEARANCE_EDITOR_JS_SAVING_CHANGES', 'Saving changes %s');
define('_AM_APPEARANCE_EDITOR_JS_SAVED', 'Saved %s.');
define('_AM_APPEARANCE_EDITOR_JS_SAVED_REACH', 'This changed %s.');
define('_AM_APPEARANCE_EDITOR_JS_OFFLINE', 'Nothing was saved: the site didn’t answer. Check your connection and save again.');
define('_AM_APPEARANCE_EDITOR_JS_UNREADABLE', 'The site’s answer couldn’t be read, so the changes may or may not have been saved. Reload the page to see what was saved.');

// the inspector's head
define('_AM_APPEARANCE_EDITOR_JS_SELECTED', 'Selected');
define('_AM_APPEARANCE_EDITOR_JS_DESELECT', 'Deselect');
define('_AM_APPEARANCE_EDITOR_JS_BRANDING_PART', 'Part of the look’s branding: it changes %s.');
define('_AM_APPEARANCE_EDITOR_JS_APPLIES_TO', 'Changes apply to %s on the site, not just the one you clicked.');
define('_AM_APPEARANCE_EDITOR_JS_USED_ON', 'Used on these screens, outlined in each:');
define('_AM_APPEARANCE_EDITOR_JS_NOTHING_SELECTED', 'Nothing selected');
define('_AM_APPEARANCE_EDITOR_JS_SETTINGS', 'Settings');
define('_AM_APPEARANCE_EDITOR_JS_BRANDING_LAYOUT', 'Branding and layout'); // the look's own settings: logo, colours, fonts, page width

// values, and what they are measured against
define('_AM_APPEARANCE_EDITOR_JS_POSSESSIVE', '%s’s');
define('_AM_APPEARANCE_EDITOR_JS_THEMES', 'the %s theme’s'); // %s is the theme, for a look with no base look
define('_AM_APPEARANCE_EDITOR_JS_THEME_OWN', 'from the %s theme');
define('_AM_APPEARANCE_EDITOR_JS_THEME_OWN_CAP', 'The %s theme’s');
define('_AM_APPEARANCE_EDITOR_JS_THEME_APPEARANCE', 'the %s theme');
define('_AM_APPEARANCE_EDITOR_JS_FROM', 'from %s');
define('_AM_APPEARANCE_EDITOR_JS_CHANGED_BY', 'changed by this look · %1$s is %2$s');
define('_AM_APPEARANCE_EDITOR_JS_CHARACTERS', '%s characters');
define('_AM_APPEARANCE_EDITOR_JS_FULLY_ROUNDED', 'Fully rounded');
define('_AM_APPEARANCE_EDITOR_JS_MAIN_FONT', 'Main font');
define('_AM_APPEARANCE_EDITOR_JS_SECONDARY_FONT', 'Secondary font');
define('_AM_APPEARANCE_EDITOR_JS_FONT_IS', '%1$s (%2$s)');
define('_AM_APPEARANCE_EDITOR_JS_SAME_AS_MAIN', '%s, the same as the main font');
define('_AM_APPEARANCE_EDITOR_JS_UNNAMED_FONT', 'Other Google Font (no name yet)');
define('_AM_APPEARANCE_EDITOR_JS_FULL_WIDTH', 'Full width');
define('_AM_APPEARANCE_EDITOR_JS_MAX_WIDTH_PX', 'Maximum width, %s pixels');

// a part's settings
define('_AM_APPEARANCE_EDITOR_JS_SECTION_TEXT', 'Text');
define('_AM_APPEARANCE_EDITOR_JS_SECTION_COLOURS', 'Colours');
define('_AM_APPEARANCE_EDITOR_JS_SECTION_CORNERS', 'Corners');
define('_AM_APPEARANCE_EDITOR_JS_SECTION_LINES', 'Lines');
define('_AM_APPEARANCE_EDITOR_JS_SECTION_SPACE', 'Size and spacing');
define('_AM_APPEARANCE_EDITOR_JS_RESET', 'Reset');
define('_AM_APPEARANCE_EDITOR_JS_UNDO', 'Undo');
define('_AM_APPEARANCE_EDITOR_JS_SMALLER', 'Smaller');
define('_AM_APPEARANCE_EDITOR_JS_LARGER', 'Larger');
define('_AM_APPEARANCE_EDITOR_JS_COLOUR_OWN', 'its own');
define('_AM_APPEARANCE_EDITOR_JS_SITE_COLOURS', 'The site’s colours');
define('_AM_APPEARANCE_EDITOR_JS_OWN_COLOUR', 'A colour of its own');
define('_AM_APPEARANCE_EDITOR_JS_PICK_COLOUR', 'Pick a colour');
define('_AM_APPEARANCE_EDITOR_JS_HEX_COLOUR', 'Hex colour');
define('_AM_APPEARANCE_EDITOR_JS_NOT_FOLLOWING', 'Doesn’t follow the site’s colours.');
define('_AM_APPEARANCE_EDITOR_JS_FLOOR_TEXT', 'the %1$s (%2$s)');
define('_AM_APPEARANCE_EDITOR_JS_FLOOR_TOKEN', 'the %1$s (%2$s) and its padding');
define('_AM_APPEARANCE_EDITOR_JS_RAISED', 'Raised to %1$s so it fits %2$s.');
define('_AM_APPEARANCE_EDITOR_JS_AT_FLOOR', 'This is as small as it goes with %s.');
define('_AM_APPEARANCE_EDITOR_JS_MEASURED', 'Shown at %spx here: the theme keeps it at least that tall at this width.');
define('_AM_APPEARANCE_EDITOR_JS_CONTRAST_TEXT_ON', 'This text on %1$s has a contrast of %2$s. Text needs %3$s:1 to be easy to read.');
define('_AM_APPEARANCE_EDITOR_JS_CONTRAST_ON_THIS', '%1$s on this has a contrast of %2$s. Text needs %3$s:1 to be easy to read.');
define('_AM_APPEARANCE_EDITOR_JS_BOTH_DEVICES', '%s, on desktop and on phones.');
define('_AM_APPEARANCE_EDITOR_JS_ALSO_CHANGES', 'Also changes %s.');
define('_AM_APPEARANCE_EDITOR_JS_NOT_ADJUSTABLE', 'Not adjustable yet');

// branding and layout: the look's own settings
define('_AM_APPEARANCE_EDITOR_JS_INTRO', 'This look’s logo, colours and fonts.');
define('_AM_APPEARANCE_EDITOR_JS_INTRO_WIDTH', 'This look’s logo, colours, fonts and page width.');
define('_AM_APPEARANCE_EDITOR_JS_INTRO_SIZE', 'This look’s logo, colours, fonts and size.');
define('_AM_APPEARANCE_EDITOR_JS_INTRO_WIDTH_SIZE', 'This look’s logo, colours, fonts, page width and size.');
define('_AM_APPEARANCE_EDITOR_JS_INTRO_BASE', 'Anything it doesn’t change comes from %s, and follows it when it changes.');
define('_AM_APPEARANCE_EDITOR_JS_INTRO_THEME', 'Anything it doesn’t change comes from the %s theme.');
define('_AM_APPEARANCE_EDITOR_JS_INTRO_CLICK', 'Click anything in the preview to change just that part.');
define('_AM_APPEARANCE_EDITOR_JS_SECTION_LOGO', 'Logo');
define('_AM_APPEARANCE_EDITOR_JS_SECTION_FONTS', 'Fonts');
define('_AM_APPEARANCE_EDITOR_JS_SECTION_SIZE', 'Size');
define('_AM_APPEARANCE_EDITOR_JS_SECTION_PAGE', 'Page');
define('_AM_APPEARANCE_EDITOR_JS_LOGO', 'Logo');
define('_AM_APPEARANCE_EDITOR_JS_LOGO_DESC', 'At the top left of every page.');
define('_AM_APPEARANCE_EDITOR_JS_FAVICON', 'Favicon');
define('_AM_APPEARANCE_EDITOR_JS_FAVICON_DESC', 'The small icon in the browser tab and in bookmarks.');
define('_AM_APPEARANCE_EDITOR_JS_UPLOAD', 'Upload…');
define('_AM_APPEARANCE_EDITOR_JS_UPLOAD_NEW', 'A new image, saved when you save.');
define('_AM_APPEARANCE_EDITOR_JS_UPLOAD_OWN', 'This look’s own.');
define('_AM_APPEARANCE_EDITOR_JS_UPLOAD_BASE', '%s’s.');
define('_AM_APPEARANCE_EDITOR_JS_UPLOAD_THEME', 'The %s theme’s.');
define('_AM_APPEARANCE_EDITOR_JS_UPLOAD_STATUS', '%1$s %2$s.'); // whose image it is, then the kinds of file it can be
define('_AM_APPEARANCE_EDITOR_JS_USE_BASE', 'Use %s’s');
define('_AM_APPEARANCE_EDITOR_JS_USE_THEME', 'Use the %s theme’s');
define('_AM_APPEARANCE_EDITOR_JS_RESET_TO', 'Reset to %s');
define('_AM_APPEARANCE_EDITOR_JS_MAIN_FONT_DESC', 'Fields, buttons, lists and help text, and any other part set to the main font.');
define('_AM_APPEARANCE_EDITOR_JS_SECONDARY_FONT_DESC', 'Titles and field labels, and any other part set to the secondary font. Leave it on the main font to use one font throughout.');
define('_AM_APPEARANCE_EDITOR_JS_GOOGLE_FONT', 'Google Font name');
define('_AM_APPEARANCE_EDITOR_JS_MAIN_FONT_EXAMPLE', 'e.g. Roboto');
define('_AM_APPEARANCE_EDITOR_JS_SECONDARY_FONT_EXAMPLE', 'e.g. Playfair Display');
define('_AM_APPEARANCE_EDITOR_JS_GOOGLE_FONT_HELP', 'The exact name as it appears on %s. The font is loaded from Google Fonts.');
define('_AM_APPEARANCE_EDITOR_JS_SIZE', 'Size');
define('_AM_APPEARANCE_EDITOR_JS_SIZE_SAME', 'The same as %s');
define('_AM_APPEARANCE_EDITOR_JS_SIZE_SAME_LOWER', 'the same as %s');
define('_AM_APPEARANCE_EDITOR_JS_SIZE_CUSTOM', 'Custom (changed in the advanced editor)');
define('_AM_APPEARANCE_EDITOR_JS_SIZE_CUSTOM_DESC', 'Its sizes have been changed one by one in the advanced editor. Choosing a size replaces them.');
define('_AM_APPEARANCE_EDITOR_JS_SIZE_BASE_DESC', 'It has the sizes of %s, and follows them when they change.');
define('_AM_APPEARANCE_EDITOR_JS_SIZE_CONFIRM', 'Its sizes have been changed in the advanced editor. Replace them with %s?');
define('_AM_APPEARANCE_EDITOR_JS_WIDTH', 'Page width');
define('_AM_APPEARANCE_EDITOR_JS_WIDTH_DESC', 'How wide pages can get on a wide screen. A maximum width keeps forms and lists to a column of that width, in the middle of the window. The %2$s theme’s is %1$s. The preview is narrower than most screens, so it may not show the difference.');
define('_AM_APPEARANCE_EDITOR_JS_MAX_WIDTH', 'Maximum width');
define('_AM_APPEARANCE_EDITOR_JS_WIDTH_PX', 'Maximum width, in pixels, from %1$s to %2$s');

// the Changes tab
define('_AM_APPEARANCE_EDITOR_JS_SAVED_CHANGES', 'Saved changes'); // the top bar's button, which lists what the look, as saved, changes from its base
define('_AM_APPEARANCE_EDITOR_JS_UNDO_ALL', 'Undo all');
define('_AM_APPEARANCE_EDITOR_JS_UNSAVED', 'Unsaved changes'); // the top bar's button, which lists the changes made since the last save, to undo them
define('_AM_APPEARANCE_EDITOR_JS_ALL_SAVED', 'Nothing: everything is saved.');
define('_AM_APPEARANCE_EDITOR_JS_WAS', 'was %s');
define('_AM_APPEARANCE_EDITOR_JS_NEW_IMAGE', 'A new image');
define('_AM_APPEARANCE_EDITOR_JS_UPLOADED_IMAGE', 'An uploaded image');
define('_AM_APPEARANCE_EDITOR_JS_SHOW', 'Show');
define('_AM_APPEARANCE_EDITOR_JS_DIFFERS', 'The saved settings that differ from %s.');
define('_AM_APPEARANCE_EDITOR_JS_CHANGED_NOT_SAVED', 'Changed again, not saved yet');
define('_AM_APPEARANCE_EDITOR_JS_RESET_NOT_SAVED', 'Reset, not saved yet');
define('_AM_APPEARANCE_EDITOR_JS_BASE_IS', '%1$s is %2$s');
define('_AM_APPEARANCE_EDITOR_JS_SAME_AS_REF', 'Nothing saved differs from %s.');
