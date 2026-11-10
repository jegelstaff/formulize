<?php

// The header of the right slide-out drawer, when it is showing an entry.
//
// This is the system default. A theme overrides it at
// modules/formulize/templates/screens/<Theme>/default/drawer/headertemplate.php.
// The drawer's footer has its own template, footertemplate.php, alongside this one.
//
// Each variable below is a placeholder that the drawer replaces with the real control:
//
//   $backButton   the Back icon, shown in a sub entry, which goes back to the parent entry
//   $formTitle    the title of the screen or form
//   $closeButton  the close icon
//
// The Back and close icons are always in the header, so there is always a way out of the
// drawer: if the template leaves one out, it goes at the end.
//
// Also available: $currentPage, $totalPages, $form_id, $screen_id and $entry_id.
//
// The AI assistant, when it is open in the drawer, keeps the drawer's built in header.

print "$backButton $formTitle <div class='formulize-drawer__spacer'></div> $closeButton";
