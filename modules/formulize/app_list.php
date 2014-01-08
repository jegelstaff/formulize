<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2013 Freeform Solutions                  ##
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
##  Author of this file: Freeform Solutions                                  ##
##  URL: http://www.freeformsolutions.ca/formulize                           ##
##  Project: Formulize                                                       ##
###############################################################################

include "../../mainfile.php";

/*
	This is an access point for mobile clients to retrieve the list 
	of availible menu links in Formulize for the given user in JSON.
	Clients must access this page with a valid cookie session.
*/

$application_handler = xoops_getmodulehandler('applications', 'formulize');
$application_list = $application_handler->getAllApplications();

// Convert the list of applications retrieved from application handler into an array representation
$applications_array = array();

foreach ($application_list as $application) {

	// Application metadata into array
	$app_data = array (
		"appid" => $application->getVar("appid"),
		"name" => $application->getVar("name"),
		"description" => $application->getVar("description"),
		);

	// Convert all menu links within the application into an array
	$links = $application->getVar("links");
	$links_arr = array();
	foreach ($links as $link) {

		// Convert the link object into a key value map array
		$link_data = array (
			"menu_id" => $link->getVar("menu_id"),
			"appid" => $link->getVar("appid"),
			"screen" => $link->getVar("screen"),
			"rank" => $link->getVar("rank"),
			"url" => $link->getVar("url"),
			"link_text" => $link->getVar("link_text"),
			"name" => $link->getVar("name"),
			"text" => $link->getVar("text"),
			//"permissions" => $link->getVar("permissions")
			);

		array_push($links_arr, $link_data);
	}

	// Only add applications that have links accessible to the user
	if (count($links_arr) > 0) {
		$app_data["links"] = $links_arr;
		array_push($applications_array, $app_data);
	}
}

// Output application menu links in a JSON format for mobile clients
exit(json_encode($applications_array));
