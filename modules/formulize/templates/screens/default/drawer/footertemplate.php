<?php

// The footer of the right slide-out drawer, when it is showing an entry: which of the
// form's buttons it shows, and in what order.
//
// This is the system default. A theme overrides it at
// modules/formulize/templates/screens/<Theme>/default/drawer/footertemplate.php.
// The drawer's header has its own template, headertemplate.php, alongside this one.
//
// The drawer's footer is not the full screen form's bottom template (multiPage/bottomtemplate.php).
// To change the order of the buttons on both, change both templates.
//
// Each variable below is a placeholder that the drawer replaces with the real button. A button
// that does not apply to the page being shown is removed, so a template can list every one.
// A button that is left out of the template does not appear in the drawer.
//
//   $printableViewButton  the printable view button
//   $pageIndicator        "Page 1 of 3", on multi-page forms without tabs
//   $previousPageButton   the previous page button; on the first page, and on single page
//                         forms, the save and leave button
//   $closePageButton      the close (done) button; in a sub entry it goes back to the parent
//   $nextPageButton       the next page button; on the last page, the finish button
//   $backButton           Back, in a sub entry whose screen has no close button
//   $savePageButton       the save button, which saves and stays on the page
//
// Also available: $currentPage, $totalPages, $form_id, $screen_id and $entry_id.
//
// The footer lays out its direct children as a flex row, so wrapping buttons in an element
// of your own takes them out of that layout, unless your theme styles the wrapper too.

print "$pageIndicator $printableViewButton $previousPageButton $closePageButton $nextPageButton $backButton $savePageButton";
