<?php
/**
 * English language constants commonly used in the module
 *
 * @copyright	The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.0
 * @author		Gustavo Pilla (aka nekro) <nekro@impresscms.org>
 * @package		profile
 * @version		$Id: common.php 22208 2011-08-09 13:37:50Z blauer-fisch $
 */

defined("ICMS_ROOT_PATH") or die("ImpressCMS root path not defined");

// field
define("_CO_PROFILE_FIELD_FIELDID", "ID");
define("_CO_PROFILE_FIELD_FIELDID_DSC", " ");
define("_CO_PROFILE_FIELD_CATID", "Category");
define("_CO_PROFILE_FIELD_CATID_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_TYPE", "Type");
define("_CO_PROFILE_FIELD_FIELD_TYPE_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_VALUETYPE", "Value");
define("_CO_PROFILE_FIELD_FIELD_VALUETYPE_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_NAME", "Identifier");
define("_CO_PROFILE_FIELD_FIELD_NAME_DSC", "Don't use capital letters, special characters, umlauts, or spaces, because it's an object name.");
define("_CO_PROFILE_FIELD_URL", "Image");
define("_CO_PROFILE_FIELD_URL_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_TITLE", "Title");
define("_CO_PROFILE_FIELD_FIELD_TITLE_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_DESCRIPTION", "Description");
define("_CO_PROFILE_FIELD_FIELD_DESCRIPTION_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_REQUIRED", "Required");
define("_CO_PROFILE_FIELD_FIELD_REQUIRED_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_MAXLENGTH", "Max length");
define("_CO_PROFILE_FIELD_FIELD_MAXLENGTH_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_WEIGHT", "Weight");
define("_CO_PROFILE_FIELD_FIELD_WEIGHT_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_DEFAULT", "Default");
define("_CO_PROFILE_FIELD_FIELD_DEFAULT_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_NOTNULL", "Not null");
define("_CO_PROFILE_FIELD_FIELD_NOTNULL_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_EDIT", "Editable");
define("_CO_PROFILE_FIELD_FIELD_EDIT_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_SHOW", "Show");
define("_CO_PROFILE_FIELD_FIELD_SHOW_DSC", " ");
define("_CO_PROFILE_FIELD_FIELD_OPTIONS", "Options");
define("_CO_PROFILE_FIELD_FIELD_OPTIONS_DSC", " ");
define("_CO_PROFILE_FIELD_EXPORTABLE", "Exportable");
define("_CO_PROFILE_FIELD_EXPORTABLE_DSC", " ");
define("_CO_PROFILE_FIELD_STEP_ID", "Step ID");
define("_CO_PROFILE_FIELD_STEP_ID_DSC", " ");
define("_CO_PROFILE_FIELD_SYSTEM", "System field");
define("_CO_PROFILE_FIELD_SYSTEM_DSC", " Indicator showing whether this field also exists in the core.");

// regstep
define("_CO_PROFILE_REGSTEP_STEP_NAME", "Name");
define("_CO_PROFILE_REGSTEP_STEP_NAME_DSC", " ");
define("_CO_PROFILE_REGSTEP_STEP_INTRO", "Description");
define("_CO_PROFILE_REGSTEP_STEP_INTRO_DSC", " ");
define("_CO_PROFILE_REGSTEP_STEP_ORDER", "Order");
define("_CO_PROFILE_REGSTEP_STEP_ORDER_DSC", " ");
define("_CO_PROFILE_REGSTEP_STEP_SAVE", "Save after confirm");
define("_CO_PROFILE_REGSTEP_STEP_SAVE_DSC", " If set to yes, the information is saved for the first time after this step and with all the steps following afterwars.");

// category
define("_CO_PROFILE_CATEGORY_CATID", "ID");
define("_CO_PROFILE_CATEGORY_CATID_DSC", " ");
define("_CO_PROFILE_CATEGORY_CAT_TITLE", "Title");
define("_CO_PROFILE_CATEGORY_CAT_TITLE_DSC", " ");
define("_CO_PROFILE_CATEGORY_CAT_DESCRIPTION", "Description");
define("_CO_PROFILE_CATEGORY_CAT_DESCRIPTION_DSC", " ");
define("_CO_PROFILE_CATEGORY_CAT_WEIGHT", "Weight");
define("_CO_PROFILE_CATEGORY_CAT_WEIGHT_DSC", " ");

// configs
define("_CO_PROFILE_CONFIGS_CONFIG_ID", "");
define("_CO_PROFILE_CONFIGS_CONFIG_ID_DSC", " ");
define("_CO_PROFILE_CONFIGS_CONFIG_UID", "User ID");
define("_CO_PROFILE_CONFIGS_CONFIG_UID_DSC", " ");
define("_CO_PROFILE_CONFIGS_PICTURES", "Show my pictures to:");
define("_CO_PROFILE_CONFIGS_PICTURES_DSC", " ");
define("_CO_PROFILE_CONFIGS_AUDIO", "Show my audio files to:");
define("_CO_PROFILE_CONFIGS_AUDIO_DSC", " ");
define("_CO_PROFILE_CONFIGS_VIDEOS", "Show my videos to:");
define("_CO_PROFILE_CONFIGS_VIDEOS_DSC", " ");
define("_CO_PROFILE_CONFIGS_FRIENDSHIP", "Show my friends to:");
define("_CO_PROFILE_CONFIGS_FRIENDSSHIP_DSC", " ");
define("_CO_PROFILE_CONFIGS_TRIBES", "Show my groups to:");
define("_CO_PROFILE_CONFIGS_TRIBES_DSC", " ");
define("_CO_PROFILE_CONFIGS_PROFILE_USERCONTRIBUTIONS", "Show my contributions to:");
define("_CO_PROFILE_CONFIGS_PROFILE_USERCONTRIBUTIONS_DSC", " ");
define("_CO_PROFILE_CONFIGS_SUSPENSION", "Suspend user");
define("_CO_PROFILE_CONFIGS_SUSPENSION_DSC", "You can revoke a users access for a period of time.");
define("_CO_PROFILE_CONFIGS_BACKUP_PASSWORD", "");
define("_CO_PROFILE_CONFIGS_BACKUP_PASSWORD_DSC", " ");
define("_CO_PROFILE_CONFIGS_BACKUP_EMAIL", "");
define("_CO_PROFILE_CONFIGS_BACKUP_EMAIL_DSC", " ");
define("_CO_PROFILE_CONFIGS_END_SUSPENSION", "When does suspension end?");
define("_CO_PROFILE_CONFIGS_END_SUSPENSION_DSC", " ");
define("_CO_PROFILE_CONFIGS_STATUS", "Status");
define("_CO_PROFILE_CONFIGS_STATUS_DSC", " ");
define("_CO_PROFILE_CONFIG_STATUS_EVERYBODY", "Anybody can see this");
define("_CO_PROFILE_CONFIG_STATUS_MEMBERS", "Only registered users can see this");
define("_CO_PROFILE_CONFIG_STATUS_FRIENDS", "Only friends can see this");
define("_CO_PROFILE_CONFIG_STATUS_PRIVATE", "Only I can see this");

// audio
define("_CO_PROFILE_AUDIO_AUDIO_ID", "Audio ID");
define("_CO_PROFILE_AUDIO_AUDIO_ID_DSC", " ");
define("_CO_PROFILE_AUDIO_TITLE", "Title");
define("_CO_PROFILE_AUDIO_TITLE_DSC", " ");
define("_CO_PROFILE_AUDIO_AUTHOR", "Author");
define("_CO_PROFILE_AUDIO_AUTHOR_DSC", " ");
define("_CO_PROFILE_AUDIO_URL", "Audio File");
define("_CO_PROFILE_AUDIO_URL_DSC", " ");
define("_CO_PROFILE_AUDIO_UID_OWNER", "Submitter");
define("_CO_PROFILE_AUDIO_UID_OWNER_DSC", " ");
define("_CO_PROFILE_AUDIO_CREATION_TIME", "Creation time");
define("_CO_PROFILE_AUDIO_CREATION_TIME_DSC", " ");

// tribes
define("_CO_PROFILE_TRIBES_TRIBES_ID", "Group ID");
define("_CO_PROFILE_TRIBES_TRIBES_ID_DSC", " ");
define("_CO_PROFILE_TRIBES_UID_OWNER", "Creator");
define("_CO_PROFILE_TRIBES_UID_OWNER_DSC", " ");
define("_CO_PROFILE_TRIBES_TITLE", "Name");
define("_CO_PROFILE_TRIBES_TITLE_DSC", " Please enter a desired name for the group.");
define("_CO_PROFILE_TRIBES_TRIBE_DESC", "Decription");
define("_CO_PROFILE_TRIBES_TRIBE_DESC_DSC", " Please write a little description about this group.<br />The description of a group is also taken into account in the search process.");
define("_CO_PROFILE_TRIBES_TRIBE_IMG", "Picture");
define("_CO_PROFILE_TRIBES_TRIBE_IMG_DSC", " Select a picture for your group.");
define("_CO_PROFILE_TRIBES_SECURITY", "Security level");
define("_CO_PROFILE_TRIBES_SECURITY_DSC", " Select the security level for this group.");
define("_CO_PROFILE_TRIBES_SECURITY_EVERYBODY", "Anybody can join this group");
define("_CO_PROFILE_TRIBES_SECURITY_APPROVAL", "Anybody can join this group (approval by the owner required)");
define("_CO_PROFILE_TRIBES_SECURITY_INVITATION", "Access by invitation only");

// tribetopic
define("_CO_PROFILE_TRIBETOPIC_TOPIC_ID", "Topic ID");
define("_CO_PROFILE_TRIBETOPIC_TOPIC_ID_DSC", " ");
define("_CO_PROFILE_TRIBETOPIC_TRIBES_ID", "Group ID");
define("_CO_PROFILE_TRIBETOPIC_TRIBES_ID_DSC", " ");
define("_CO_PROFILE_TRIBETOPIC_POSTER_UID", "Poster ID");
define("_CO_PROFILE_TRIBETOPIC_POSTER_UID_DSC", " ");
define("_CO_PROFILE_TRIBETOPIC_TITLE", "Title");
define("_CO_PROFILE_TRIBETOPIC_TITLE_DSC", " ");
define("_CO_PROFILE_TRIBETOPIC_CLOSED", "Closed");
define("_CO_PROFILE_TRIBETOPIC_CLOSED_DSC", " Yes to close the topic");
define("_CO_PROFILE_TRIBETOPIC_LAST_POST_ID", "Last post ID");
define("_CO_PROFILE_TRIBETOPIC_LAST_POST_ID_DSC", " ");
define("_CO_PROFILE_TRIBETOPIC_LAST_POST_TIME", "Last post time");
define("_CO_PROFILE_TRIBETOPIC_LAST_POST_TIME_DSC", " ");

// tribepost
define("_CO_PROFILE_TRIBEPOST_POST_ID", "Post ID");
define("_CO_PROFILE_TRIBEPOST_POST_ID_DSC", " ");
define("_CO_PROFILE_TRIBEPOST_TOPIC_ID", "Topic ID");
define("_CO_PROFILE_TRIBEPOST_TOPIC_ID_DSC", " ");
define("_CO_PROFILE_TRIBEPOST_TRIBES_ID", "Group ID");
define("_CO_PROFILE_TRIBEPOST_TRIBES_ID_DSC", " ");
define("_CO_PROFILE_TRIBEPOST_POSTER_UID", "Poster ID");
define("_CO_PROFILE_TRIBEPOST_POSTER_UID_DSC", " ");
define("_CO_PROFILE_TRIBEPOST_TITLE", "Title");
define("_CO_PROFILE_TRIBEPOST_TITLE_DSC", " Subject");
define("_CO_PROFILE_TRIBEPOST_BODY", "Body");
define("_CO_PROFILE_TRIBEPOST_BODY_DSC", " Body text");
define("_CO_PROFILE_TRIBEPOST_ATTACHSIG", "Attach Signature");
define("_CO_PROFILE_TRIBEPOST_ATTACHSIG_DSC", " Yes to attach signature to the post");
define("_CO_PROFILE_TRIBEPOST_POST_TIME", "Post Time");
define("_CO_PROFILE_TRIBEPOST_POST_TIME_DSC", " ");

// friendship
define("_CO_PROFILE_FRIENDSHIP_FRIENDSHIP_ID", "ID");
define("_CO_PROFILE_FRIENDSHIP_FRIENDSHIP_ID_DSC", " ");
define("_CO_PROFILE_FRIENDSHIP_FRIEND1_UID", "User ID");
define("_CO_PROFILE_FRIENDSHIP_FRIEND1_UID_DSC", " ");
define("_CO_PROFILE_FRIENDSHIP_FRIEND2_UID", "User ID");
define("_CO_PROFILE_FRIENDSHIP_FRIEND2_UID_DSC", " ");
define("_CO_PROFILE_FRIENDSHIP_STATUS", "");
define("_CO_PROFILE_FRIENDSHIP_STATUS_DSC", " ");
define("_CO_PROFILE_FRIENDSHIP_STATUS_PENDING", "Pending");
define("_CO_PROFILE_FRIENDSHIP_STATUS_ACCEPTED", "Accepted");
define("_CO_PROFILE_FRIENDSHIP_STATUS_REJECTED", "Rejected");

// pictures
define("_CO_PROFILE_PICTURES_PICTURES_ID", "Picture ID");
define("_CO_PROFILE_PICTURES_PICTURES_ID_DSC", " ");
define("_CO_PROFILE_PICTURES_TITLE", "Title of the picture");
define("_CO_PROFILE_PICTURES_TITLE_DSC", " ");
define("_CO_PROFILE_PICTURES_CREATION_TIME", "");
define("_CO_PROFILE_PICTURES_CREATION_TIME_DSC", " ");
define("_CO_PROFILE_PICTURES_UID_OWNER", "Submitter");
define("_CO_PROFILE_PICTURES_UID_OWNER_DSC", " ");
define("_CO_PROFILE_PICTURES_URL", "Picture");
define("_CO_PROFILE_PICTURES_URL_DSC", " ");
define("_CO_PROFILE_PICTURES_PRIVATE", "Make this a private picture");
define("_CO_PROFILE_PICTURES_PRIVATE_DSC", " ");

// videos
define("_CO_PROFILE_VIDEOS_VIDEOS_ID", "Video ID");
define("_CO_PROFILE_VIDEOS_VIDEOS_ID_DSC", " ");
define("_CO_PROFILE_VIDEOS_UID_OWNER", "Submitter");
define("_CO_PROFILE_VIDEOS_UID_OWNER_DSC", " ");
define("_CO_PROFILE_VIDEOS_VIDEO_DESC", "Video description");
define("_CO_PROFILE_VIDEOS_VIDEO_DESC_DSC", " ");
define("_CO_PROFILE_VIDEOS_YOUTUBE_CODE", "YouTube code");
define("_CO_PROFILE_VIDEOS_YOUTUBE_CODE_DSC", " Please enter the code of your clip in YouTube.<br /><b>Example:</b> 3UkPhvse8JA");
define("_CO_PROFILE_VIDEOS_VIDEO_TITLE", "Video title");
define("_CO_PROFILE_VIDEOS_VIDEO_TITLE_DSC", " ");
define("_CO_PROFILE_VIDEOS_CREATION_TIME", "Creation time");
define("_CO_PROFILE_VIDEOS_CREATION_TIME_DSC", " ");

// visitors
define("_CO_PROFILE_VISITORS_VISIT_ID", "ID");
define("_CO_PROFILE_VISITORS_VISIT_ID_DSC", " ");
define("_CO_PROFILE_VISITORS_UID_OWNER", "");
define("_CO_PROFILE_VISITORS_UID_OWNER_DSC", " ");
define("_CO_PROFILE_VISITORS_UID_VISITOR", "");
define("_CO_PROFILE_VISITORS_UID_VISITOR_DSC", " ");

// tribeuser
define("_CO_PROFILE_TRIBEUSER_TB_ID", "ID");
define("_CO_PROFILE_TRIBEUSER_TB_ID_DSC", " ");
define("_CO_PROFILE_TRIBEUSER_TRIBEUSER_ID", "Membership ID");
define("_CO_PROFILE_TRIBEUSER_TRIBEUSER_ID_DSC", " ");
define("_CO_PROFILE_TRIBEUSER_TRIBE_ID", "Group");
define("_CO_PROFILE_TRIBEUSER_TRIBE_ID_DSC", " ");
define("_CO_PROFILE_TRIBEUSER_USER_ID", "Display name");
define("_CO_PROFILE_TRIBEUSER_USER_ID_DSC", " ");
define("_CO_PROFILE_TRIBEUSER_APPROVED", "Approved");
define("_CO_PROFILE_TRIBEUSER_APPROVED_DSC", " Yes if membership for the group is approved by the owner.");
define("_CO_PROFILE_TRIBEUSER_ACCEPTED", "Accepted");
define("_CO_PROFILE_TRIBEUSER_ACCEPTED_DSC", " Yes if user accepted the invitation to this group.");
?>