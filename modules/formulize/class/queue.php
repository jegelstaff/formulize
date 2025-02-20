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

if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

require_once XOOPS_ROOT_PATH.'/kernel/object.php';
class formulizeQueue extends FormulizeObject {

	function __construct() {
    $this->initVar('queue_handle', XOBJ_DTYPE_TXTBOX, '', 255);
		$this->initVar('items', XOBJ_DTYPE_ARRAY, '', false, null);
	}

	function __get($name) {
		if (!isset($this->$name)) {
			if (method_exists($this, $name)) {
					$this->$name = $this->$name();
			} else {
					$this->$name = $this->getVar($name);
			}
		}
		return $this->$name;
	}

}

class formulizeQueueHandler {

	var $queueDir = XOOPS_ROOT_PATH.'/modules/formulize/queue/';
	var $db;
	function __construct(&$db) {
		$this->db =& $db;
	}

	function &getInstance(&$db) {
		static $instance;
		if (!isset($instance)) {
			$instance = new formulizeQueueHandler($db);
		}
		return $instance;
	}

	/**
	 * Create a new queue object with the handle name passed in, and and empty item list
	 * @param string queue_handle The identifier of the queue we're creating
	 * @return object The new queue object with this identifier
	 */
	function &create($queue_handle) {
		$queue = new formulizeQueue();
		$queue->setVar('queue_handle', FormulizeObject::sanitize_handle_name($queue_handle));
		$queue->setVar('items', serialize(array()));
		return $queue;
	}

	/**
	 * Get a queue based on the queued items identified by the given handle. Will create the queue if necessary.
	 * @param string queue_handle The identifier of the queue we're getting
	 * @return object The queue object based on the identifier, with all items already in the queue
	 */
	function get($queue_handle) {
		$queue = $this->create($queue_handle);
		$queue->setVar('items', serialize(formulize_scandirAndClean($this->queueDir, "_".$queue->getVar('queue_handle')."_", 0)));
		return $queue;
	}

	/**
	 * Delete a queue by removing all the files for the queue from the queue directory
	 * @param mixed queue_or_queue_handle The queue object or a queue handle to identify the queue we are deleting.
	 */
	function delete($queue_or_queue_handle) {
		$queue_handle = (is_object($queue_or_queue_handle) AND is_a($queue_or_queue_handle, 'formulizeQueue')) ? $queue_or_queue_handle->getVar('queue_handle') : FormulizeObject::sanitize_handle_name($queue_or_queue_handle);
		formulize_scandirAndClean($this->queueDir, "_".$queue_handle."_", 1);
		return true;
	}

	/**
	 * Append code to a queue, so it can be run later when the queue is processed. A passed queue object has its items updated to include the newly appended item/file. Objects are passed by reference by default in PHP since v5.
	 * @param mixed queue_or_queue_handle The queue object or a queue handle to identify the queue we are appending to.
	 * @param string code The code that we should add to the queue
	 * @param string item Optional. A string that provides a description of what this code is for. Meant to make the filename more intelligible.
	 * @param bool allowDuplicates Optional. A flag to indicate whether we allow a duplicate item into the queue. Use the item descriptor thoughtfully.
	 * @return mixed Returns the number of bytes that were written (this is the return value of file_put_contents), or null if the item is already in the queue, or false on failure to write.
	 */
	function append($queue_or_queue_handle, $code, $item='', $allowDuplicates=false) {
		global $xoopsUser;
		$queue_handle = (is_object($queue_or_queue_handle) AND is_a($queue_or_queue_handle, 'formulizeQueue')) ? $queue_or_queue_handle->getVar('queue_handle') : FormulizeObject::santitize_handle_name($queue_or_queue_handle);
		$fileName = microtime(true)."_".$queue_handle."_".$item.".php";
		if(!$allowDuplicates) {
			$existingQueueItems = formulize_scandirAndClean($this->queueDir, "_".$queue_handle."_".$item.".php", 0); // check for files with same queue_handle and item descriptor
		}
		$writeResult = null;
		if($allowDuplicates OR count($existingQueueItems) == 0) {
			$writeResult = false;
			$code = "<?php
try {
	writeToFormulizeLog(array(
		'formulize_event'=>'processing-queue-item',
		'queue_id'=>'$queue_handle',
		'queue_item_or_items'=>'$fileName'
	));
	$code
} catch (Exception \$e) {
	error_log('Formulize Queue Error: queue item: $fileName, message: '.\$e->getMessage().', line: '.\$e->getLine());
	writeToFormulizeLog(array(
		'formulize_event'=>'error-processing-queue-item',
		'queue_id'=>'$queue_handle',
		'queue_item_or_items'=>'$fileName',
		'PHP_error_string'=>\$e->getMessage(),
		'PHP_error_file'=>'$fileName',
		'PHP_error_errline'=>\$e->getLine()
	));
}";
			if(formulize_validatePHPCode($code) == '') { // no errors returned
				if($writeResult = file_put_contents($this->queueDir.$fileName, $code)) {
					if(is_object($queue_or_queue_handle) AND is_a($queue_or_queue_handle, 'formulizeQueue')) {
						$items = $queue_or_queue_handle->getVar('items');
						$items[] = $fileName;
						$queue_or_queue_handle->setVar('items', serialize($items));
					}
					writeToFormulizeLog(array(
						'formulize_event'=>'item-written-to-queue',
						'queue_id'=>$queue_handle,
						'queue_item_or_items'=>$fileName,
						'user_id'=>($xoopsUser ? $xoopsUser->getVar('uid') : 0)
					));
				} else {
					writeToFormulizeLog(array(
						'formulize_event'=>'error-writing-item-to-queue',
						'queue_id'=>$queue_handle,
						'queue_item_or_items'=>$fileName,
						'user_id'=>($xoopsUser ? $xoopsUser->getVar('uid') : 0)
					));
				}
			} else {
				error_log("Formulize Queue Error: the code for item $item in queue $queue_handle has syntax errors. This item has not been added to the queue.");
				writeToFormulizeLog(array(
					'formulize_event'=>'syntax-error-in-queue-item',
					'queue_id'=>$queue_handle,
					'queue_item_or_items'=>$fileName,
					'user_id'=>($xoopsUser ? $xoopsUser->getVar('uid') : 0)
				));
			}
		}
		return $writeResult;
	}

	/**
	 * Process a queue or all the queues. If command line execution is available, entire queue processed that way without time limit. If not, then attempt as many queue items as possible in the time available for the current request.
	 * @param object queue Optional. A queue object that represents the queue we're processing. If omitted, all queues are processed, items handled in the order they were created.
	 * @return mixed An array of the queue filenames that were processed, if done as part of this request. True if the queue was handed off to the command line
	 */
	function process($queue_or_queue_handle=null) {
		if(is_object($queue_or_queue_handle) AND is_a($queue_or_queue_handle, 'formulizeQueue')) {
			$queue_handle = $queue_or_queue_handle->getVar('queue_handle');
		} elseif($queue_or_queue_handle) {
			$queue_handle = $queue_or_queue_handle;
		} else {
			$queue_handle = 'all';
		}
		$queueDir = $this->queueDir;
		$queueIncludeFile = XOOPS_ROOT_PATH.'/modules/formulize/include/queue.php';
		if(isEnabled('exec')) {
			exec('php -f '.$queueIncludeFile.' '.escapeshellarg($queue_handle).' '.escapeshellarg($queueDir).' > /dev/null 2>&1 & echo $!');
			return true;
		} else {
			include $queueIncludeFile; // sets processedFiles
			return $processedFiles;
		}
	}

}
