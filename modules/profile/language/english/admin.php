<?php
/**
 * English language constants used in admin section of the module
 *
 * @copyright	The ImpressCMS Project <http://www.impresscms.org>
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Jan Pedersen
 * @author		Marcello Brandao <marcello.brandao@gmail.com>
 * @author		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @package		profile
 * @version		$Id: admin.php 22692 2011-09-18 10:43:13Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

// Requirements
define("_AM_PROFILE_REQUIREMENTS", "Requirements");
define("_AM_PROFILE_REQUIREMENTS_INFO", "We've reviewed your system, unfortunately it doesn't meet all the requirements needed for Profile to function. Below are the requirements needed.");
define("_AM_PROFILE_REQUIREMENTS_ICMS_BUILD", "Profile requires at least ImpressCMS Build %s (yours is %s)!");
define("_AM_PROFILE_REQUIREMENTS_SUPPORT", "Should you have any question or concerns, please visit our forums at <a href='http://community.impresscms.org'>http://community.impresscms.org</a>.");

// Users
define("_AM_PROFILE_DELETEDSUCCESS", "%s deleted successfully");
define("_AM_PROFILE_RUSUREDEL", "Are you sure you want to delete %s");
define("_AM_PROFILE_EDITUSER", "Edit user");
define("_AM_PROFILE_REMOVEDUSERS", "Banned/removed users");
define("_AM_PROFILE_SELECTUSER", "Select user");
define("_AM_PROFILE_ADDUSER","Add user");
define("_AM_PROFILE_USERCREATED", "User created");
define("_AM_PROFILE_USERMODIFIED","Profile updated!");
define("_AM_PROFILE_CANNOTDELETESELF", "Deleting your own account is not allowed - use your profile page to delete your own account");
define("_AM_PROFILE_CANNOTEDITWEBMASTERS", "You cannot edit a webmaster's account");
define("_AM_PROFILE_GROUP", "Groups");

// Categories
define("_AM_PROFILE_CATEGORY_CREATE", "Add a category");
define("_AM_PROFILE_CATEGORY", "Category");
define("_AM_PROFILE_CATEGORY_EDIT", "Edit category");
define("_AM_PROFILE_CATEGORY_CREATED", "The category has been successfully created.");
define("_AM_PROFILE_CATEGORY_MODIFIED", "The category was successfully modified.");
define("_AM_PROFILE_CATEGORY_NOTDELETED_FIELDS", "The category cannot be deleted because there are still %s fields assigned to it.");

// Field
define("_AM_PROFILE_FIELD_CREATE", "Add a field");
define("_AM_PROFILE_FIELD", "Field");
define("_AM_PROFILE_FIELD_EDIT", "Edit field");
define("_AM_PROFILE_FIELD_CREATED", "The field has been successfully created.");
define("_AM_PROFILE_FIELD_MODIFIED", "The field was successfully modified.");
define("_AM_PROFILE_FIELD_TYPE_CHECKBOX", "Checkbox");
define("_AM_PROFILE_FIELD_TYPE_GROUP", "Groups");
define("_AM_PROFILE_FIELD_TYPE_GROUPMULTI", "Groups Multi");
define("_AM_PROFILE_FIELD_TYPE_LANGUAGE", "Language");
define("_AM_PROFILE_FIELD_TYPE_RADIO", "Radio Buttons");
define("_AM_PROFILE_FIELD_TYPE_SELECT", "Select");
define("_AM_PROFILE_FIELD_TYPE_SELECTMULTI", "Multi Select");
define("_AM_PROFILE_FIELD_TYPE_TEXTAREA", "Text Area");
define("_AM_PROFILE_FIELD_TYPE_TEXTBOX", "Text Box");
define("_AM_PROFILE_FIELD_TYPE_DHTMLTEXTAREA", "DHTML Text Area");
define("_AM_PROFILE_FIELD_TYPE_TIMEZONE", "Timezone");
define("_AM_PROFILE_FIELD_TYPE_YESNO", "Radio Yes/No");
define("_AM_PROFILE_FIELD_TYPE_DATE", "Date");
define("_AM_PROFILE_FIELD_TYPE_DATETIME", "Date and Time");
define("_AM_PROFILE_FIELD_TYPE_LONGDATE", "Long Date");
define("_AM_PROFILE_FIELD_TYPE_IMAGE", "Image");
define("_AM_PROFILE_FIELD_TYPE_RANK", "Rank");
define("_AM_PROFILE_FIELD_TYPE_THEME", "Theme");
define("_AM_PROFILE_FIELD_TYPE_URL", "URL");
define("_AM_PROFILE_FIELD_TYPE_LOCATION", "Location (Google)");
define("_AM_PROFILE_FIELD_TYPE_EMAIL", "Email");
define("_AM_PROFILE_FIELD_TYPE_OPENID", "Open-ID");

// Registration Steps
define("_AM_PROFILE_REGSTEP_CREATE", "Add a registration step");
define("_AM_PROFILE_REGSTEP", "Registration Step");
define("_AM_PROFILE_REGSTEP_EDIT", "Edit registration Step");
define("_AM_PROFILE_REGSTEP_CREATED", "The Registration Step has been successfully created.");
define("_AM_PROFILE_REGSTEP_MODIFIED", "The Registration Step was successfully modified.");

// Visibility
define("_AM_PROFILE_FIELDVISIBLE", "The field ");
define("_AM_PROFILE_FIELDVISIBLEFOR", " is visible for ");
define("_AM_PROFILE_FIELDVISIBLEON", " viewing a profile of ");
define("_AM_PROFILE_FIELDVISIBLETOALL", "everyone");
define("_AM_PROFILE_FIELDNOTVISIBLE", "is not visible");

// Permissions
define("_AM_PROFILE_PROF_EDITABLE", "Field editable from profile");
define("_AM_PROFILE_PROF_SEARCH", "Searchable by these groups");

// Audio
define("_AM_PROFILE_AUDIO", "Audio");
define("_AM_PROFILE_AUDIOS", "Audios");
define("_AM_PROFILE_AUDIO_CREATE", "Add an audio file");
define("_AM_PROFILE_AUDIO_EDIT", "Edit audio");

// Tribes
define("_AM_PROFILE_TRIBE", "Group");
define("_AM_PROFILE_TRIBES", "Groups");
define("_AM_PROFILE_TRIBES_CREATE", "Create new group");
define("_AM_PROFILE_TRIBES_EDIT", "Edit group");
define("_AM_PROFILE_TRIBES_CREATED", "The group has been successfully created.");
define("_AM_PROFILE_TRIBES_MODIFIED", "The group was successfully modified.");
define("_AM_PROFILE_TRIBES_MERGE", "Merge");
define("_AM_PROFILE_TRIBES_MERGE_DSC", "Merge this group into another group.");
define("_AM_PROFILE_TRIBES_MERGING", "Merging");
define("_AM_PROFILE_TRIBES_MERGEWITH", "Group to merge with...");
define("_AM_PROFILE_TRIBES_MERGE_WARNING", "<span style='color:red;font-weight:bold;'>Warning</span>");
define("_AM_PROFILE_TRIBES_MERGE_WARNING_DSC", "The group \"%s\" will be merged into the group selected. Therefore, the group will be deleted and all users and topics will be transfered into the group selected. The title, description, picture, owner and settings of the group selected will be taken as master.");
define("_AM_PROFILE_TRIBES_MERGE_ERR_ID", "One of the groups selected does not exist.");
define("_AM_PROFILE_TRIBES_MERGE_SUCCESS", "Both groups were successfully merged.");
define("_AM_PROFILE_TRIBES_MERGE_ERR_SAME", "Invalid: You cannot join a group with itself. Please specify another target.");

// Pictures
define("_AM_PROFILE_PICTURE", "Picture");
define("_AM_PROFILE_PICTURES", "Pictures");
define("_AM_PROFILE_PICTURES_CREATE", "Add a picture");
define("_AM_PROFILE_PICTURES_EDIT", "Edit picture");
define("_AM_PROFILE_PICTURES_CREATED", "The picture has been successfully created.");
define("_AM_PROFILE_PICTURES_MODIFIED", "The picture was successfully modified.");

// Videos
define("_AM_PROFILE_VIDEO", "Video");
define("_AM_PROFILE_VIDEOS", "Videos");
define("_AM_PROFILE_VIDEOS_CREATE", "Add a video");
define("_AM_PROFILE_VIDEOS_EDIT", "Edit video");

// Tribeuser
define("_AM_PROFILE_TRIBEUSER", "Membership");
define("_AM_PROFILE_TRIBEUSERS", "Memberships");
define("_AM_PROFILE_TRIBEUSER_CREATE", "Create new membership");
define("_AM_PROFILE_TRIBEUSER_MODIFY", "Modify membership");
define("_AM_PROFILE_TRIBEUSER_CREATED", "The membership has been successfully created.");
define("_AM_PROFILE_TRIBEUSER_MODIFIED", "The membership has been successfully modified.");
if (!defined("_PROFILE_TRIBEUSER_DUPLICATE")) define("_PROFILE_TRIBEUSER_DUPLICATE", "The specified user is already a member of this group.");
if (!defined("_PROFILE_TRIBEUSER_OWNER")) define("_PROFILE_TRIBEUSER_OWNER", "The specified user is already the owner of this group and therefore cannot be a member.");
define("_AM_PROFILE_TRIBEUSER_NOTTRIBESYET", "There are no groups yet.");

//Find user
define("_AM_SPROFILE_FINDUSER_CRIT", "%s contains:");
define("_AM_SPROFILE_FINDUSER", "Find Users");
define("_AM_SPROFILE_UNAME", "Display name");
define("_AM_SPROFILE_UID", "Userid");
define("_AM_SPROFILE_EMAIL", "Email");
define("_AM_SPROFILE_EXPORT_ALL", "Export all matching users");
?>