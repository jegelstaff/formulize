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

use function Safe\file_put_contents;

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

	function &create() {
		return new formulizeQueue();
	}

	/**
	 * Get a queue based on the queued items identified by the given handle
	 * @param string queue_handle The identifier of the queue we're getting
	 * @return object The queue object based on the identifier
	 */
	function get($queue_handle) {
		$queue = $this->create();
		$queue->setVar('queue_handle', $this->sanitize_handle_name($queue_handle));
		$queue->setVar('items', formulize_scandirAndClean($this->queueDir, "_".$queue_handle."_"));
		return $queue;
	}

	/**
	 * Delete a queue by removing all the files for the queue from the queue directory
	 * @param mixed queue_or_queue_handle The queue object or a queue handle to identify the queue we are deleting.
	 */
	function delete($queue_or_queue_handle) {
		$queue_handle = (is_object($queue_or_queue_handle) AND is_a($queue_or_queue_handle, 'formulizeQueue')) ? $queue_or_queue_handle->getVar('queue_handle') : $queue_or_queue_handle;
		formulize_scandirAndClean($this->queueDir, "_".$queue_handle."_", 1);
		return true;
	}

	/**
	 * Append code to a queue, so it can be run later when the queue is processed
	 * @param mixed queue_or_queue_handle The queue object or a queue handle to identify the queue we are appending to.
	 * @param string code The code that we should add to the queue
	 * @param string item Optional. A string that provides a description of what this code is for. Meant to make the filename more intelligible.
	 * @return mixed Returns the number of bytes that were written, or false on failure. This is the return value of file_put_contents.
	 */
	function append($queue_or_queue_handle, $code, $item='') {
		$queue_handle = (is_object($queue_or_queue_handle) AND is_a($queue_or_queue_handle, 'formulizeQueue')) ? $queue_or_queue_handle->getVar('queue_handle') : $this->santitize_handle_name($queue_or_queue_handle);
		$fileName = microtime(true)."_".$queue_handle."_".$item.".php";
		return file_put_contents($this->queueDir.$fileName, "<?php\n$code");
	}

	/**
	 * Process a queue or all the queues
	 * @param object queue Optional. A queue object that represents the queue we're processing. If omitted, all queues are processed, items handled in the order they were created.
	 * @return array An array of the queue filenames that were processed
	 */
	function process($queue_or_queue_handle=null) {
		global $startTime, $maxExec;
		$startTime = $startTime ? $startTime : microtime(true);
		$maxExec = $maxExec ? $maxExec : 60;
		if(is_object($queue_or_queue_handle) AND is_a($queue_or_queue_handle, 'formulizeQueue')) {
			$queueFiles = $queue_or_queue_handle->getVar('items');
		} elseif($queue_or_queue_handle) {
			$queue = $this->get($queue_or_queue_handle);
			$queueFiles = $queue->getVar('items');
		} else {
			$queueFiles = formulize_scandirAndClean($this->queueDir, ".php");
		}
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
		return $processedFiles;
	}

}
