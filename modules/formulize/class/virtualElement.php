<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                       ##
###############################################################################
##  Author of this file: Formulize Incorporated                               ##
##  Project: Formulize                                                        ##
###############################################################################

// Base class for virtual element types — elements that have no backing database
// column and are populated post-query by injection functions. Virtual elements
// are always system-managed; they cannot be created via the admin element picker.
//
// Subclass this to define a typed virtual column. Implement buildSearchWhereClause()
// on the handler to enable search delegation from parseTableFormFilter().

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/elements.php";

class formulizeVirtualElement extends formulizeElement {

	var $isVirtualElement = true;

	function __construct() {
		parent::__construct();
		$this->name           = "Virtual Element";
		$this->isSystemElement = true;
		$this->hasData        = false;
		$this->needsDataType  = false;
	}

}

class formulizeVirtualElementHandler extends formulizeElementsHandler {

	function create() {
		return new formulizeVirtualElement();
	}

	function prepareDataForDataset($value, $handle, $entry_id) {
		return $value;
	}

}
