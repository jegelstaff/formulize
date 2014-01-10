<?php


class icms_DebugEventHandler {

	static public function setup() {
		icms_Event::attach('icms', 'loadService', array(__CLASS__, 'loadService'));
	}

	/**
	 * Called after the kernel initializes a service
	 * @return	void
	 */
	static public function loadService($params, $event) {
		switch ($params['name']) {
		case "config":
			global $xoopsOption, $icmsConfig;
			if (!isset($xoopsOption['nodebug']) || !$xoopsOption['nodebug']) {
				if ($icmsConfig['debug_mode'] == 1 || $icmsConfig['debug_mode'] == 2) {
					error_reporting(E_ALL);
					icms::$logger->enableRendering();
					icms::$logger->usePopup = ( $icmsConfig['debug_mode'] == 2 );
					if (icms::$db) {
						icms_Event::attach('icms_db_IConnection', 'prepare', array(__CLASS__, 'prepareQuery'));
						icms_Event::attach('icms_db_IConnection', 'execute', array(__CLASS__, 'executeQuery'));
					}
				} else {
					// ICMS_ERROR_LOG_SEVERITY ADDED BY FREEFORM SOLUTIONS
					if (defined("ICMS_ERROR_LOG_SEVERITY")) {
						ini_set('display_errors', 0);	
					} else {
						error_reporting(0);
					}
					icms::$logger->activated = false;
				}
			}
			break;
		}
	}
	static public function prepareQuery($params, $event) {
		icms::$logger->addQuery('prepare: ' . $params['sql']);
	}
	static public function executeQuery($params, $event) {
		icms::$logger->addQuery('execute: ' . $params['sql']);
	}

}
icms_DebugEventHandler::setup();
