<?php
/**
* English language constants commonly used in the module
*
* @copyright	The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @since		1.0
* @author		Phoenyx <phoenyx@impresscms.de>
* @package		banners
* @version		$Id: common.php 23919 2012-03-21 03:08:59Z qm-b $
*/
defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

// client
define("_CO_BANNERS_CLIENT_FIRST_NAME", "First name");
define("_CO_BANNERS_CLIENT_FIRST_NAME_DSC", " ");
define("_CO_BANNERS_CLIENT_LAST_NAME", "Last name");
define("_CO_BANNERS_CLIENT_LAST_NAME_DSC", " ");
define("_CO_BANNERS_CLIENT_STREET", "Street");
define("_CO_BANNERS_CLIENT_STREET_DSC", " ");
define("_CO_BANNERS_CLIENT_STREET_NUMBER", "Street number");
define("_CO_BANNERS_CLIENT_STREET_NUMBER_DSC", " ");
define("_CO_BANNERS_CLIENT_ZIP_CODE", "zip code");
define("_CO_BANNERS_CLIENT_ZIP_CODE_DSC", " ");
define("_CO_BANNERS_CLIENT_CITY", "City");
define("_CO_BANNERS_CLIENT_CITY_DSC", " ");
define("_CO_BANNERS_CLIENT_STATE", "State or Providence");
define("_CO_BANNERS_CLIENT_STATE_DSC", " ");
define("_CO_BANNERS_CLIENT_COUNTRY", "Country");
define("_CO_BANNERS_CLIENT_COUNTRY_DSC", " ");
define("_CO_BANNERS_CLIENT_SINCE", "Customer since");
define("_CO_BANNERS_CLIENT_SINCE_DSC", " The date this customer was added to the database");
define("_CO_BANNERS_CLIENT_COMPANY", "Company");
define("_CO_BANNERS_CLIENT_COMPANY_DSC", " ");
define("_CO_BANNERS_CLIENT_EMAIL", "E-Mail address");
define("_CO_BANNERS_CLIENT_EMAIL_DSC", " ");
define("_CO_BANNERS_CLIENT_PHONE", "Telephone number");
define("_CO_BANNERS_CLIENT_PHONE_DSC", " ");
define("_CO_BANNERS_CLIENT_UID", "Username");
define("_CO_BANNERS_CLIENT_UID_DSC", " If a link to a user id is present, the user can see statistics for all banners assigned to this client.");
define("_CO_BANNERS_CLIENT_EXTRA", "Extra information");
define("_CO_BANNERS_CLIENT_EXTRA_DSC", " ");
define("_CO_BANNERS_CLIENT_BANNER_COUNT", "Banners");
define("_CO_BANNERS_CLIENT_BANNER_COUNT_DSC", "");
define("_CO_BANNERS_CLIENT_ACTIVE", "Active");
define("_CO_BANNERS_CLIENT_ACTIVE_DSC", "Only active clients can upload banners and see statistics for their banners.");

// banner
define("_CO_BANNERS_BANNER_BANNER_ID", "Id");
define("_CO_BANNERS_BANNER_BANNER_ID_DSC", "");
define("_CO_BANNERS_BANNER_TYPE", "Type");
define("_CO_BANNERS_BANNER_TYPE_DSC", "");
define("_CO_BANNERS_BANNER_FILENAME", "Banner");
define("_CO_BANNERS_BANNER_FILENAME_DSC", " Filename for the banner. <strong>Please note:</strong> When using the URL field, there will be no dimension check for the positions assigned.<br /><br /><strong>Maximum file size:</strong> ".(@icms::$module->config['maxfilesize'] / 1024)."kb<br /><strong>Supported file types</strong>:<br /><i>Image:</i> jpg, gif, png<br /><i>Flash:</i> swf");
define("_CO_BANNERS_BANNER_SOURCE", "HTML Source");
define("_CO_BANNERS_BANNER_SOURCE_DSC", "");
define("_CO_BANNERS_BANNER_CLIENT_ID", "Client");
define("_CO_BANNERS_BANNER_CLIENT_ID_DSC", " Name of client that has ordered the banner.");
define("_CO_BANNERS_BANNER_CONTRACT", "Contract type");
define("_CO_BANNERS_BANNER_CONTRACT_DSC", " Which type of contract was concluded? Impressions or a fixed period of time?");
define("_CO_BANNERS_BANNER_IMPRESSIONS_PURCHASED", "Impressions purchased");
define("_CO_BANNERS_BANNER_IMPRESSIONS_PURCHASED_DSC", " How many impressions have been purchased by the customer? (put 0 for unlimited impressions)");
define("_CO_BANNERS_BANNER_BEGIN", "Begin date");
define("_CO_BANNERS_BANNER_BEGIN_DSC", " When should this banner be shown for the first time?");
define("_CO_BANNERS_BANNER_END", "End date");
define("_CO_BANNERS_BANNER_END_DSC", " When should this banner be shown for the last time?");
define("_CO_BANNERS_BANNER_LINK", "Click url");
define("_CO_BANNERS_BANNER_LINK_DSC", " URL has to start with <strong>http://</strong><br /><br /><strong>Example:</strong> http://www.impresscms.org<br />You can use the tag {ICMS_URL} to print <i>".ICMS_URL."</i>");
define("_CO_BANNERS_BANNER_TARGET", "Target");
define("_CO_BANNERS_BANNER_TARGET_DSC", " Use same window or open a new one?");
define("_CO_BANNERS_BANNER_IMPRESSIONS_MADE", "Impressions made");
define("_CO_BANNERS_BANNER_IMPRESSIONS_MADE_DSC", " How many impressions have been made so far.");
define("_CO_BANNERS_BANNER_CLICKS", "Clicks");
define("_CO_BANNERS_BANNER_CLICKS_DSC", " How many clicks have been made so far.");
define("_CO_BANNERS_BANNER_CLICKS_PERCENT", "%");
define("_CO_BANNERS_BANNER_CLICKS_PERCENT_DSC", " ");
define("_CO_BANNERS_BANNER_EXTRA", "Extra information");
define("_CO_BANNERS_BANNER_EXTRA_DSC", " ");
define("_CO_BANNERS_BANNER_POSITIONS", "Banner positions");
define("_CO_BANNERS_BANNER_POSITIONS_DSC", "Maximum Dimensions: 1000x1000. Beware of the position dimensions!");
define("_CO_BANNERS_BANNER_ACTIVE", "Active");
define("_CO_BANNERS_BANNER_ACTIVE_DSC", " A switch to overwrite the impressions, begin and end date.");
define("_CO_BANNERS_BANNER_DESCRIPTION", "Description");
define("_CO_BANNERS_BANNER_DESCRIPTION_DSC", " Purpose of this banner");
define("_CO_BANNERS_BANNER_VISIBLEIN", "Visible in");
define("_CO_BANNERS_BANNER_VISIBLEIN_DSC", " Select the pages where the banner should be visible in.");
define("_CO_BANNERS_BANNER_STATUS", "Status");
define("_CO_BANNERS_BANNER_STATUS_DSC", "");
define("_CO_BANNERS_BANNER_TYPE_IMAGE", "Image");
define("_CO_BANNERS_BANNER_TYPE_HTML", "HTML");
define("_CO_BANNERS_BANNER_TYPE_FLASH", "Flash");
define("_CO_BANNERS_BANNER_CONTRACT_IMPRESSIONS", "Impressions");
define("_CO_BANNERS_BANNER_CONTRACT_TIME", "Fixed period of time");
define("_CO_BANNERS_BANNER_TARGET_SELF", "Same window");
define("_CO_BANNERS_BANNER_TARGET_BLANK", "New window");
define("_CO_BANNERS_BANNER_DIMENSIONCHECK", "Error while verifying image dimensions for position %s. Maximum width is %spx (image: %spx), maximum height is %spx (image: %spx).");

// position
define("_CO_BANNERS_POSITION_NAME", "Position name");
define("_CO_BANNERS_POSITION_NAME_DSC", "Name of position. Use a name with small_caption letters, without spaces and special characters.");
define("_CO_BANNERS_POSITION_TITLE", "Position title");
define("_CO_BANNERS_POSITION_TITLE_DSC", " This will be shown in the selection list for each banner.");
define("_CO_BANNERS_POSITION_DESCRIPTION", "Description");
define("_CO_BANNERS_POSITION_DESCRIPTION_DSC", " ");
define("_CO_BANNERS_POSITION_WIDTH", "Width");
define("_CO_BANNERS_POSITION_WIDTH_DSC", "in pixels");
define("_CO_BANNERS_POSITION_HEIGHT", "Height");
define("_CO_BANNERS_POSITION_HEIGHT_DSC", "in pixels");
define("_CO_BANNERS_POSITION_DIMENSION", "Dimension");
define("_CO_BANNERS_POSITION_DIMENSION_DSC", " ");

// positionlink
define("_CO_BANNERS_POSITIONLINK_BANNER_ID", "Banner Id");
define("_CO_BANNERS_POSITIONLINK_BANNER_ID_DSC", "");
define("_CO_BANNERS_POSITIONLINK_POSITION_ID", "Position Id");
define("_CO_BANNERS_POSITIONLINK_POSITION_ID_DSC", "");

// visiblein
define("_CO_BANNERS_VISIBLEIN_BANNER_ID", "Banner Id");
define("_CO_BANNERS_VISIBLEIN_BANNER_ID_DSC", "");
define("_CO_BANNERS_VISIBLEIN_MODULE", "Module");
define("_CO_BANNERS_VISIBLEIN_MODULE_DSC", "");
define("_CO_BANNERS_VISIBLEIN_PAGE", "Page");
define("_CO_BANNERS_VISIBLEIN_PAGE_DSC", "");