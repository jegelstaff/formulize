<?php

// Wrapper for a form rendered inside the right slide-out drawer.
//
// The drawer supplies its own header (with the form's title) and its own footer
// (Save/Cancel, paging), so this template emits no chrome of its own - only the
// container the theme's form styling is scoped to. Keeping that container the
// same one the theme's form/multiPage screens use is what makes a form look the
// same in the drawer as it does full screen.
//
// This is the system default. A theme overrides it at
// modules/formulize/templates/screens/<Theme>/default/drawer/toptemplate.php.

print "
<div class='form-container formulize-drawer-form'>
";
