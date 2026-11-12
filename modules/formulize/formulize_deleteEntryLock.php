<?php

// Where a page sends the release of the entries it locked, as it goes away. A list also sends one here when an
// element opened from its icon is saved or closed, for just that entry. See formulize_releaseRequestedEntryLocks()
// in include/entryLocks.php, which does the releasing, and which a page that submits to itself runs as well.

// IF THE WEBMASTER IS BUILDING A CUSTOM INTERFACE RELYING ON RENDERING ELEMENTS ON THEIR OWN, LOCKS WILL NOT WORK RIGHT
// Custom screens (and template screens?) won't have the release in place for when the window goes away, and they don't pass the token through POST like a standard screen does

// requested on its own, the rest of Formulize isn't loaded
require_once __DIR__.'/include/entryLocks.php';

if(!defined('XOOPS_MAINFILE_INCLUDED')) {
	ignore_user_abort(true);
	header("Access-Control-Allow-Origin: *");
	// a request on its own must say whose page load it was, or it's not valid
	if(!isset($_POST['formulize_entry_lock_uid'])) {
		exit();
	}
}

formulize_releaseRequestedEntryLocks();
