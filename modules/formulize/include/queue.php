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
	$queue_handle = $argv[1];
	$queueDir = $argv[2];
} else {
	global $formulize_publicApiStartTime, $formulize_publicApiMaxExec;
	$formulize_publicApiStartTime = $formulize_publicApiStartTime ? $formulize_publicApiStartTime : microtime(true);
	$formulize_publicApiMaxExec = $formulize_publicApiMaxExec ? $formulize_publicApiMaxExec : 60;
}

global $xoopsDB;
$xoopsDB->allowWebChanges = true;
define('FORMULIZE_QUEUE_PROCESSING', true);

if(!$queue_handle OR $queue_handle == 'all') {
	$queueFiles = formulize_scandirAndClean($queueDir, ".php", 0);
} else {
	$queue_handler = xoops_getmodulehandler('queue', 'formulize');
	$queue = $queue_handler->get($queue_handle);
	$queueFiles = $queue->getVar('items');
}

$processedFiles = array();

foreach($queueFiles as $file) {
	$curTime = microtime(true);
	if(!isset($formulize_publicApiStartTime) OR $curTime - $formulize_publicApiStartTime < $formulize_publicApiMaxExec - 10) { // if we're running in an http request context (not command line), then ten second window when running inside a request because we hope no single queue operation takes over ten seconds by itself??
		writeToFormulizeLog(array(
			'formulize_event'=>'processing-queue-item',
			'queue_id'=>$queue_handle,
			'queue_item_or_items'=>$file
		));
		include $queueDir.$file;
		unlink($queueDir.$file);
		$processedFiles[] = $file;
	} else {
		break;
	}
}
writeToFormulizeLog(array(
	'formulize_event'=>'queue-processing-complete',
	'queue_id'=>$queue_handle,
	'queue_item_or_items'=>implode(',',$processedFiles)
));
