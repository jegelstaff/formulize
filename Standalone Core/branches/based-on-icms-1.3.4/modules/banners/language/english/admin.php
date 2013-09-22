<?php
/**
* English language constants used in admin section of the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: admin.php 22633 2011-09-10 11:55:02Z phoenyx $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

// Requirements
define("_AM_BANNERS_REQUIREMENTS", "Requirements");
define("_AM_BANNERS_REQUIREMENTS_INFO", "We've reviewed your system, unfortunately it doesn't meet all the requirements needed for Banners to function. Below are the requirements needed.");
define("_AM_BANNERS_REQUIREMENTS_ICMS_BUILD", "Banners requires at least ImpressCMS Build %s (yours is %s)!");
define("_AM_BANNERS_REQUIREMENTS_SUPPORT", "Should you have any question or concerns, please visit our forums at <a href='http://community.impresscms.org'>http://community.impresscms.org</a>.");
define("_AM_BANNERS_REQUIREMENTS_SMARTY_PLUGIN", "This module requires a smarty plugin to work properly. Please copy the following file:<br /><strong>Source:</strong> %s<br /><strong>Destination:</strong> %s.");

// Clients
define("_AM_BANNERS_CLIENTS", "Clients");
define("_AM_BANNERS_CLIENTS_DSC", "All clients");
define("_AM_BANNERS_CLIENT_CREATE", "Add a client");
define("_AM_BANNERS_CLIENT", "Client");
define("_AM_BANNERS_CLIENT_EDIT", "Edit this client");
define("_AM_BANNERS_CLIENT_MODIFIED", "The client was modified successfully.");
define("_AM_BANNERS_CLIENT_CREATED", "The client was created successfully.");
define("_AM_BANNERS_CLIENT_NODELETE_BANNER", "This client cannot be deleted since there are still banners assigned to it.");
define("_AM_BANNERS_CLIENT_USERNOTUNIQUE", "The specified user is already assigned to another client. Please select another user.");

// Banners
define("_AM_BANNERS_BANNERS", "Banners");
define("_AM_BANNERS_BANNERS_DSC", "All banners");
define("_AM_BANNERS_BANNER_CREATE", "Add a banner");
define("_AM_BANNERS_BANNER", "Banner");
define("_AM_BANNERS_BANNER_EDIT", "Edit banner");
define("_AM_BANNERS_BANNER_MODIFIED", "The banner was modified successfully.");
define("_AM_BANNERS_BANNER_CREATED", "The banner was created successfully.");
define("_AM_BANNERS_BANNER_NOPOSITIONS", "Please create a position first.");
define("_AM_BANNERS_BANNER_NOCLIENTS", "Please create a client first.");

// Position
define("_AM_BANNERS_POSITIONS", "Positions");
define("_AM_BANNERS_POSITIONS_DSC", "All positions");
define("_AM_BANNERS_POSITION_CREATE", "Add a position");
define("_AM_BANNERS_POSITION", "Position");
define("_AM_BANNERS_POSITION_EDIT", "Edit this position");
define("_AM_BANNERS_POSITION_MODIFIED", "The position was modified successfully.");
define("_AM_BANNERS_POSITION_CREATED", "The position was created successfully.");
define("_AM_BANNERS_POSITION_INFO", "To include a new banner position inside your theme, please place the code below where you would like the banner to appear. Make sure to edit the name of the example position to your custom position.<br /><br />Default (cache deactivated):
<div style=\"border: 1px dashed #AABBCC; padding:10px; width:30%;margin:10px;\">
  <{banners position=<strong>name_of_position</strong>}>
</div>
In case you don't want to have the same banner twice on your screen, set cache to true (for each banner position):
<div style=\"border: 1px dashed #AABBCC; padding:10px; width:30%;margin:10px;margin-bottom:20px;\">
  <{banners position=<strong>name_of_position</strong> cache=true}>
</div>");
define("_AM_BANNERS_POSITION_NODELETE_BANNER", "This position cannot be deleted since there are still banners assigned to it.");