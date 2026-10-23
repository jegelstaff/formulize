<?php

// The buttons for a form rendered inside the right slide-out drawer, in the order
// the drawer shows them. The drawer moves everything in .formulize-drawer-buttons
// into its footer and gives each button its action, so a theme puts the buttons in
// the order it wants by overriding this template, as its multiPage bottomtemplate
// orders the full screen bar. The variables are empty for a button the screen
// does not have (see formulize_drawerButtonTemplateVariables in
// modules/formulize/include/formdisplay.php).
//
// This is the system default. A theme overrides it at
// modules/formulize/templates/screens/<Theme>/default/drawer/bottomtemplate.php.

print "
<div style='clear: both;'></div>
</div>
<div class='formulize-drawer-buttons'>
$printableViewButton $saveAndLeaveButton $previousPageButton $savePageButton $closePageButton $nextPageButton
</div>
";
