<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################

// Entry locks. While someone has an entry open in a form, a file in modules/formulize/temp/ says who has it, and
// which page load drew it for them. displayElement() writes the lock, and formulize_deleteEntryLock.php releases it
// when that page goes away. These functions are the only things that name, write or read a lock file.
//
// The page load is recorded because a page's release can arrive after the next page was drawn. A person who
// reloads an entry, or follows a link to it, has a new page that takes the lock over, and only that page's
// release may remove it. A webmaster who overrides a lock takes it over the same way.
//
// A lock with no token can be released by any of that person's page loads that drew the entry. Elements redrawn
// on their own, after their page has loaded, write those, because they're part of a page whose token they
// don't have. So did every lock written before locks recorded the page load.
//
// formulize_deleteEntryLock.php is requested on its own, without the rest of Formulize, so this file needs nothing
// else, and has nothing in it but functions. functions.php requires it, so the functions are available wherever
// functions.php is.

/**
 * The file that holds the lock on an entry
 *
 * @param int $entry_id The entry
 * @param int $fid The form the entry is in
 * @return string The path of the lock file
 */
function formulize_entryLockFileName($entry_id, $fid) {
	return __DIR__."/../temp/entry_".intval($entry_id)."_in_form_".intval($fid)."_is_locked_for_editing";
}

/**
 * Lock an entry for a person, on behalf of the page load drawing it for them. Replaces any lock already on it.
 *
 * @param int $entry_id The entry
 * @param int $fid The form the entry is in
 * @param int $uid The person who has it open
 * @param string $username Their name, for the message other people get
 * @param string $token The page load's token, from getEntryLockSecurityToken(), or blank for an element redrawn on its own
 * @return bool Whether the lock was written
 */
function formulize_writeEntryLock($entry_id, $fid, $uid, $username, $token) {
	$lockFile = fopen(formulize_entryLockFileName($entry_id, $fid), "w");
	if($lockFile === false) {
		return false;
	}
	fwrite($lockFile, intval($uid).",".$token.",".$username);
	fclose($lockFile);
	return true;
}

/**
 * Who holds the lock on an entry
 *
 * @param int $entry_id The entry
 * @param int $fid The form the entry is in
 * @return array|false The lock's uid, token and username, or false if the entry isn't locked. The token is blank for
 * a lock with none, including one written before locks recorded the page load, which only had the uid and the username.
 */
function formulize_readEntryLock($entry_id, $fid) {
	$fileName = formulize_entryLockFileName($entry_id, $fid);
	if(!file_exists($fileName)) {
		return false;
	}
	$contents = file_get_contents($fileName);
	if($contents === false) {
		return false;
	}
	$parts = explode(",", $contents, 3);
	if(count($parts) == 3 AND preg_match('/^([0-9a-f]{64})?$/', $parts[1])) {
		return array('uid' => intval($parts[0]), 'token' => $parts[1], 'username' => $parts[2]);
	}
	$parts = explode(",", $contents, 2);
	return array('uid' => intval($parts[0]), 'token' => '', 'username' => isset($parts[1]) ? $parts[1] : '');
}

/**
 * Release the lock on an entry, if the page load releasing it is the one that holds it
 *
 * @param int $entry_id The entry
 * @param int $fid The form the entry is in
 * @param int $uid The person the page load was for
 * @param string $token The page load's token
 * @return void
 */
function formulize_releaseEntryLock($entry_id, $fid, $uid, $token) {
	$lock = formulize_readEntryLock($entry_id, $fid);
	if($lock AND $lock['uid'] === intval($uid) AND ($lock['token'] === '' OR $lock['token'] === $token)) {
		unlink(formulize_entryLockFileName($entry_id, $fid));
	}
}
