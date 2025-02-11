<?php

###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Project                        ##
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
##  Author of this file: Formulize Project                                   ##
##  Project: Formulize                                                       ##
###############################################################################

if(!defined('FORMULIZE_PUBLIC_API_REQUEST')) {
	http_response_code(500);
  exit();
}

// $id and $method set in the controller that landed us here
// $id will be queue id or 0 or 'all' for all queues
// $method will be what to do with the queue - initial support only for 'process' to run the queue

// goal is that we could hook in various queue processing systems perhaps, but for now queues handled natively in PHP
// queues will be snippets of PHP code that need to run
// each snippet is its own file, in the queue folder, named with its own queue id, plus an identifier (could be just a timestamp of creation)
// ie: {timestamp}_queueX_newuser.php

// An alternate queue handling system could read all the files in sequence and execute each one in the context of PHP. Each queue file must be executed in the context of /mainfile.php having been included, and /modules/formulize/include/common.php. Anything that can invoke that context and then just read and execute the files could process the queue.

switch($method) {
	case "process":
		$queueDir = XOOPS_ROOT_PATH.'/modules/formulize/queue/';
		$queueFilter = ($id AND $id != 'all') ? "_".$id."_" : ".php";
		$queueFiles = formulize_scandirAndClean($queueDir, $queueFilter);
		$processedFiles = array();
		foreach($queueFiles as $file) {
			$curTime = microtime(true);
			if($curTime - $startTime < $maxExec - 10) { // ten second window because we hope no single queue operation takes over ten seconds by itself??
				include $queueDir.$file;
				unlink($queueDir.$file);
				$processedFiles[] = $file;
			} else {
				break;
			}
		}
		writeToFormulizeLog(array(
			'formulize_event'=>'processing-queue',
			'queue_id'=>$id,
			'queue_items'=>implode(',',$processedFiles)
		));
		break;
}

