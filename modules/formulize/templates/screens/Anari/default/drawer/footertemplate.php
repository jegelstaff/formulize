<?php

// Anari's drawer footer. The buttons are in the same order as on Anari's full screen forms
// (multiPage/bottomtemplate.php): previous, save, close, next, then the page indicator.
// Back, which only appears in a sub entry whose screen has no close button, takes close's place.
//
// The system default (modules/formulize/templates/screens/default/drawer/footertemplate.php)
// lists all the variables a drawer footer template can use.

print "$printableViewButton $previousPageButton $savePageButton $closePageButton $backButton $nextPageButton $pageIndicator";
