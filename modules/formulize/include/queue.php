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


if(!defined("XOOPS_MAINFILE_INCLUDED")) {
	include_once "../../../mainfile.php";
	include_once XOOPS_ROOT_PATH."/modules/formulize/include/common.php";
	set_time_limit(0);
	ignore_user_abort(true);
	$queueHandle = FormulizeObject::sanitize_handle_name($argv[1]);
} else {
	global $formulize_publicApiStartTime, $formulize_publicApiMaxExec;
	$formulize_publicApiStartTime = $formulize_publicApiStartTime ? $formulize_publicApiStartTime : microtime(true);
	$formulize_publicApiMaxExec = $formulize_publicApiMaxExec ? $formulize_publicApiMaxExec : 60;
	// $queueHandle must be set in code that includes this file, if mainfile was already included. ie: we're running inside a normal page load so we have to have already delcared the queue we're going to process
}

global $xoopsDB;
$xoopsDB->allowWebChanges = true;
define('FORMULIZE_QUEUE_PROCESSING', true);
$queueHandler = xoops_getmodulehandler('queue', 'formulize');
$queueDir = $queueHandler->queueDir;
$tempDir = XOOPS_ROOT_PATH."/modules/formulize/temp";
$queueHandle = !$queueHandle ? 'all' : $queueHandle;

// Clear the active flag for queues that started processing over 10 minutes ago and didn't finish... can't have them hanging around forever, need to try again.
// Intended to avoid processing queues that are already being processed. But after 10 mins, try again because something might have hung/crashed.
// After clearing, check for an active flag on the current queue, and also check for 'all' if no flag for a specific queue was found.
// Exit if the queue has been attempted in the last ten minutes. Or create an active flag for it if we're going ahead.
formulize_scandirAndClean($tempDir, ".queueprocessing", 600);
if(file_exists($tempDir."/$queueHandle.queueprocessing") OR ($queueHandle != 'all' AND file_exists($tempDir."/all.queueprocessing"))) {
	exit();
}
file_put_contents($tempDir."/$queueHandle.queueprocessing",'');

// Gather the queue files
if($queueHandle == 'all') {
	$queueFiles = formulize_scandirAndClean($queueDir, ".php", 0);
} else {
	$queue = $queueHandler->get($queueHandle);
	$queueFiles = $queue->getVar('items');
}

// Process the queue
$processedFiles = array();

writeToFormulizeLog(array(
	'formulize_event'=>'queue-processing-beginning',
	'queue_id'=>$queueHandle,
	'queue_item_or_items'=>implode(',',$queueFiles)
));

foreach($queueFiles as $file) {
	$curTime = microtime(true);
	if(!isset($formulize_publicApiStartTime) OR $curTime - $formulize_publicApiStartTime < $formulize_publicApiMaxExec - 10) { // if we're running in an http request context (not command line), then ten second window when running inside a request because we hope no single queue operation takes over ten seconds by itself??
		include $queueDir.$file;
		unlink($queueDir.$file);
		$processedFiles[] = $file;
	} else {
		break;
	}
}

// Remove the active flag for this queue because we got to the end
unlink($tempDir."/$queueHandle.queueprocessing");

writeToFormulizeLog(array(
	'formulize_event'=>'queue-processing-complete',
	'queue_id'=>$queueHandle,
	'queue_item_or_items'=>implode(',',$processedFiles)
));
