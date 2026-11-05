<?php

// Closes the wrapper opened by toptemplate.php, for a form rendered inside the right
// slide-out drawer.
//
// The drawer's buttons are not laid out here. They follow the form's own bottomtemplate
// (the multiPage one, by default), in the order it prints them on the full screen bar.
// A theme that wants the drawer to differ can print them here instead, inside a
// <div class='formulize-drawer-buttons'>, using $printableViewButton, $saveAndLeaveButton,
// $previousPageButton, $savePageButton, $closePageButton and $nextPageButton (see
// formulize_drawerButtonTemplateVariables in modules/formulize/include/formdisplay.php).
//
// This is the system default. A theme overrides it at
// modules/formulize/templates/screens/<Theme>/default/drawer/bottomtemplate.php.

print "
<div style='clear: both;'></div>
</div>
";
