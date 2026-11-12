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
// Each page load that locks something also has a list file, <token><uid>.token, naming what it locked. Its release
// names the token, and the list says what to unlock. The page sends its release as it goes away, and a page that
// submits to itself posts its token, so the next request releases it first. An element a list opens from its icon,
// after the list has loaded, is locked for the list's page load and added to the list's list file, so those
// releases cover it too. The list releases it on its own when the element is saved or closed.
//
// formulize_deleteEntryLock.php is requested on its own, without the rest of Formulize, so this file needs nothing
// else, and has nothing in it but functions. functions.php requires it, so the functions are available wherever
// functions.php is.
//
// See ai-context/filesystem-dependencies.md.

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

/**
 * Whether a value is a page load's token, as getEntryLockSecurityToken() makes them
 *
 * @param mixed $token The value
 * @return bool
 */
function formulize_isEntryLockToken($token) {
	return is_string($token) AND preg_match('/^[0-9a-f]{64}$/', $token);
}

/**
 * The file that lists what a page load locked
 *
 * @param string $token The page load's token
 * @param int $uid The person the page load was for
 * @return string|false The path of the list file, or false if the token isn't one
 */
function formulize_entryLockListFileName($token, $uid) {
	if(!formulize_isEntryLockToken($token)) {
		return false;
	}
	return __DIR__."/../temp/".$token.intval($uid).".token";
}

/**
 * The entries a page load locked, from its list file
 *
 * @param string $fileName The list file
 * @return array Form ids, each with an array whose keys are entry ids. Empty if the file can't be read.
 */
function formulize_readEntryLockList($fileName) {
	$contents = $fileName ? @file_get_contents($fileName) : false;
	$entries = $contents === false ? false : unserialize($contents, array('allowed_classes' => false));
	return is_array($entries) ? $entries : array();
}

/**
 * Add entries to what a page load locked, so its release unlocks them too. Creates the list file if the page load
 * hasn't one yet.
 *
 * @param string $token The page load's token
 * @param int $uid The person the page load was for
 * @param array $entries Form ids, each with an array whose keys are entry ids, as in $entriesThatHaveBeenLockedThisPageLoad
 * @return bool Whether the list was written
 */
function formulize_recordEntryLocksForPageLoad($token, $uid, $entries) {
	$fileName = formulize_entryLockListFileName($token, $uid);
	if(!$fileName OR !is_array($entries) OR count($entries) == 0) {
		return false;
	}
	$listed = file_exists($fileName) ? formulize_readEntryLockList($fileName) : array();
	foreach($entries as $fid => $theseEntries) {
		foreach(array_keys((array) $theseEntries) as $entry_id) {
			$listed[intval($fid)][intval($entry_id)] = true;
		}
	}
	return file_put_contents($fileName, serialize($listed), LOCK_EX) !== false;
}

/**
 * Release what an earlier page load locked, when this request names one in $_POST['formulize_entry_lock_token']. A page
 * that submits to itself posts its token, and a page that goes away sends it to formulize_deleteEntryLock.php. If the
 * request also names an entry, in $_POST['formulize_entry_lock_entry'], only that entry is released, and the page
 * load keeps the rest. A list sends that when an element opened from its icon is saved or closed. The list doesn't
 * say which form the element is in, and only one can be open at a time, so the entry is released in every form the
 * page load locked it in.
 *
 * Runs once per request. A request made on its own, without the rest of Formulize, says whose page load it was in
 * $_POST['formulize_entry_lock_uid'], since the person may have logged out since. Otherwise it's the person logged in.
 *
 * @return void
 */
function formulize_releaseRequestedEntryLocks() {
	static $done = false;
	if($done OR !isset($_POST['formulize_entry_lock_token'])) {
		return;
	}
	$done = true;
	$token = $_POST['formulize_entry_lock_token'];
	global $xoopsUser;
	$uid = is_object($xoopsUser) ? intval($xoopsUser->getVar('uid')) : 0;
	if(defined('XOOPS_MAINFILE_INCLUDED')) {
		$pageLoadUid = $uid;
	} elseif(isset($_POST['formulize_entry_lock_uid'])) {
		$pageLoadUid = intval($_POST['formulize_entry_lock_uid']);
	} else {
		return;
	}
	$fileName = formulize_entryLockListFileName($token, $pageLoadUid);
	// If $uid is zero, it can't be checked against the uid of the page load, because the person may have just logged out, or this is a request on its own. The token and the uid have to name a list file that exists, though.
	if(!$fileName OR !file_exists($fileName) OR ($uid != 0 AND $uid != $pageLoadUid)) {
		return;
	}
	$entries = formulize_readEntryLockList($fileName);
	$onlyEntry = isset($_POST['formulize_entry_lock_entry']) ? intval($_POST['formulize_entry_lock_entry']) : 0;
	if($onlyEntry) {
		foreach($entries as $fid => $theseEntries) {
			if(isset($theseEntries[$onlyEntry])) {
				formulize_releaseEntryLock($onlyEntry, $fid, $pageLoadUid, $token);
				unset($entries[$fid][$onlyEntry]);
				if(count($entries[$fid]) == 0) {
					unset($entries[$fid]);
				}
			}
		}
		if(count($entries) == 0) {
			unlink($fileName);
		} else {
			file_put_contents($fileName, serialize($entries), LOCK_EX);
		}
		return;
	}
	unlink($fileName);
	unset($_POST['formulize_entry_lock_token']);
	foreach($entries as $fid => $theseEntries) {
		foreach(array_keys($theseEntries) as $entry_id) {
			// Only if this page load still holds the lock. A later page load can have taken it over: the same person's, if they reloaded the entry or followed a link to it, since this release is sent after that page was drawn, or a webmaster's, who overrode the lock.
			formulize_releaseEntryLock($entry_id, $fid, $pageLoadUid, $token);
		}
	}
}
