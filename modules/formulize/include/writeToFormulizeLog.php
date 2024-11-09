<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project												 ##
###############################################################################
##                    XOOPS - PHP Content Management System                  ##
##                       Copyright (c) 2000 XOOPS.org                        ##
##                          <http://www.xoops.org/>                          ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
##                                                                           ##
##  You may not change or alter any portion of this comment or credits       ##
##  of supporting developers from this source code or any supporting         ##
##  source code which is considered copyrighted (c) material of the          ##
##  original comment or credit authors.                                      ##
##                                                                           ##
##  This program is distributed in the hope that it will be useful,          ##
##  but WITHOUT ANY WARRANTY; without even the implied warranty of           ##
##  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            ##
##  GNU General Public License for more details.                             ##
##                                                                           ##
##  You should have received a copy of the GNU General Public License        ##
##  along with this program; if not, write to the Free Software              ##
##  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA ##
###############################################################################
##  Author of this file: Formulize Project  					     									 ##
##  Project: Formulize                                                       ##
###############################################################################

/**
 * Record information in a log file. One log file per day. Log file location is a Formulize preference. Log file storage duration is a Formulize preference.
 *
 * @param array $data Key-Value pairs that should be written to the log entry
 * @return int|boolean Returns the number of bytes written to the file, or false on failure
 */
function writeToFormulizeLog($data) {

	// initialize the configuration settings
	static $formulizeConfig = false;
	static $formulizeLoggingOnOff = false;
	static $formulizeLogFileLocation = XOOPS_ROOT_PATH.'/logs';
	static $formulizeLogFileStorageDurationHours = 168;
	static $logFilesCleanedUp = false;
	static $phpUniqueId = '';
	static $todayLogFileExists = null;
	$phpUniqueId = $phpUniqueId ? $phpUniqueId : uniqid('', true);
	if(!$formulizeConfig) {
		global $xoopsDB;
		$config_handler = xoops_gethandler('config');
		if($res = $xoopsDB->query("SELECT mid FROM ".$xoopsDB->prefix("modules")." WHERE dirname='formulize'")) {
			$row = $xoopsDB->fetchRow($res);
      $mid = $row[0];
		}
		$formulizeConfig = $config_handler->getConfigsByCat(0, $mid);
		$formulizeLoggingOnOff = $formulizeConfig['formulizeLoggingOnOff'];
		$formulizeLogFileLocation = $formulizeConfig['formulizeLogFileLocation'];
		$formulizeLogFileStorageDurationHours = $formulizeConfig['formulizeLogFileStorageDurationHours'];
	}

	// check if logging is on and log file location is valid
	if(!$formulizeLoggingOnOff OR !$formulizeLogFileLocation OR !is_dir($formulizeLogFileLocation)) { return false; }

	// cleanup old log files (last param requires seconds) -- only once per page load
	if(!$logFilesCleanedUp) {
		formulize_scandirAndClean($formulizeLogFileLocation, 'formulize_log_', $formulizeLogFileStorageDurationHours * 60 * 60);
		$logFilesCleanedUp = true;
	}

	// UNIQUE ID is only available if the server has mod_unique_id turned on
	// All log lines have standard format, but values are dependent on the event that wrote to the log
	$data = array(
		'microtime' => microtime(true),
		'request_id' => (isset($_SERVER['UNIQUE_ID']) ? $_SERVER['UNIQUE_ID'] : $phpUniqueId),
		'session_id' => session_id(),
		'formulize_event' => (isset($data['formulize_event']) ? $data['formulize_event'] : ''),
		'user_id' => (isset($data['user_id']) ? $data['user_id'] : ''),
		'form_id' => (isset($data['form_id']) ? $data['form_id'] : ''),
		'screen_id' => (isset($data['screen_id']) ? $data['screen_id'] : ''),
		'entry_id' => (isset($data['entry_id']) ? $data['entry_id'] : ''),
		'form_screen_page_number' => (isset($data['form_screen_page_number']) ? $data['form_screen_page_number'] : ''),
		'searches' => (isset($data['searches']) ? $data['searches'] : ''),
		'sort' => (isset($data['sort']) ? $data['sort'] : ''),
		'order' => (isset($data['order']) ? $data['order'] : ''),
		'scope' => (isset($data['scope']) ? $data['scope'] : ''),
		'limit_start' => (isset($data['limit_start']) ? $data['limit_start'] : ''),
		'page_size' => (isset($data['page_size']) ? $data['page_size'] : ''),
		'uids_to_notify' => (isset($data['uids_to_notify']) ? $data['uids_to_notify'] : ''),
		'formulize_notification_emails_if_uid_is_zero' => (isset($data['formulize_notification_emails_if_uid_is_zero']) ? $data['formulize_notification_emails_if_uid_is_zero'] : ''),
		'subject' => (isset($data['subject']) ? $data['subject'] : ''),
		'template' => (isset($data['template']) ? $data['template'] : ''),
		'tags' => (isset($data['tags']) ? $data['tags'] : ''),
		'PHP_error_number' => (isset($data['PHP_error_number']) ? $data['PHP_error_number'] : ''),
		'PHP_error_string' => (isset($data['PHP_error_string']) ? $data['PHP_error_string'] : ''),
		'PHP_error_file' => (isset($data['PHP_error_file']) ? $data['PHP_error_file'] : ''),
		'PHP_error_errline' => (isset($data['PHP_error_errline']) ? $data['PHP_error_errline'] : '')
	);

	// write the new log entry (to a new file if necessary, active file has generic name, archived files are named with the current date based on server timezone)
	// write operation is self contained in file_put_contents and closes the file, so it's available for other concurrent requests to write to after
	$todayLogFile = $formulizeLogFileLocation.'/'.'formulize_log_'.date('Y-m-d').'.log';
	$yesterdayLogFile = $formulizeLogFileLocation.'/'.'formulize_log_'.date('Y-m-d', strtotime('-1 day')).'.log';
	$activeLogFile = $formulizeLogFileLocation.'/'.'formulize_log_active.log';
	$todayLogFileExists = $todayLogFileExists === null ? file_exists($todayLogFile) : $todayLogFileExists;
	if(!$todayLogFileExists) {
		file_put_contents($todayLogFile, '', LOCK_EX);
		rename($activeLogFile, $yesterdayLogFile);
	}
	return file_put_contents($activeLogFile, json_encode($data, JSON_NUMERIC_CHECK)."\n", FILE_APPEND | LOCK_EX);
}
