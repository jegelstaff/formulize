<?php
/**
 * Extended User Profile
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license	LICENSE.txt
 * @license	GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package	modules
 * @since	1.2
 * @author	Jan Pedersen
 * @author	Marcello Brandao <marcello.brandao@gmail.com>
 * @author	Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version	$Id$
 */

define('_PROFILE_MA_MAKE_CONFIG_FIRST', 'This is the first time you open your Profile. Please specify your Profile Settings first.');
define('_PROFILE_MA_USER_NOT_FOUND', 'User not found.');
define("_PROFILE_MA_ERRORDURINGSAVE", "Error during save");
define('_PROFILE_MA_REALNAME', 'Real Name');
define('_PROFILE_MA_EMAIL','Email');
define('_PROFILE_MA_OPENID', 'OpenID');
define('_PROFILE_MA_OPENID_VIEW', 'Allow other users to view my OpenID');
define('_PROFILE_MA_AVATAR','Avatar');
define('_PROFILE_MA_VERIFYPASS','Verify Password');
define('_PROFILE_MA_SUBMIT','Submit');
define('_PROFILE_MA_USERNAME','Username');
define('_PROFILE_MA_USERLOGINNAME','Login name');
define('_PROFILE_MA_FINISH','Finish');
define('_PROFILE_MA_REGISTERNG','Could not register new user.');
define('_PROFILE_MA_DISCLAIMER','Disclaimer');
define('_PROFILE_MA_IAGREE','I agree to the above');
define('_PROFILE_MA_UNEEDAGREE', 'Sorry, you have to agree to our disclaimer to get registered.');
define('_PROFILE_MA_NOREGISTER','Sorry, we are currently closed for new user registrations');
define("_PROFILE_MA_NOSTEPSAVAILABLE", "No registration steps are defined");
define("_PROFILE_MA_REQUIRED", "Required");
define("_PROFILE_MA_REGISTER_FINISH", "Thanks for registering");
define('_PROFILE_MA_USERKEYFOR','User activation key for %s');
define('_PROFILE_MA_ACTLOGIN','The account has been activated and can now login with the registered password.');
define('_PROFILE_MA_ACTKEYNOT','Activation key not correct!');
define('_PROFILE_MA_ACONTACT','Selected account is already activated!');

define('_PROFILE_MA_YOURREGISTERED','An email containing a user activation key has been sent to the email account you provided. Please follow the instructions in the mail to activate your account. ');
define('_PROFILE_MA_YOURREGMAILNG','You are now registered. However, we were unable to send the activation mail to your email account due to an internal error that had occurred on our server. We are sorry for the inconvenience, please send the webmaster an email notifying him/her of the situation.');
define('_PROFILE_MA_YOURREGISTERED2','You are now registered.  Please wait for your account to be activated by the adminstrators.  You will receive an email once you are activated.  This could take a while so please be patient.');

define('_PROFILE_MA_NEWUSERREGAT','New user registration at %s');
define('_PROFILE_MA_HASJUSTREG','%s has just registered!');

define('_PROFILE_MA_INVALIDMAIL','ERROR: Invalid email');
define('_PROFILE_MA_EMAILNOSPACES','ERROR: Email addresses do not contain spaces.');
define('_PROFILE_MA_DISPLAYNAMETOOLONG','Displayname is too long. It must be less than %s characters.');
define('_PROFILE_MA_DISPLAYNAMETOOSHORT','Displayname is too short. It must be more than %s characters.');
define('_PROFILE_MA_DISPLAYNAMERESERVED','ERROR: Displayname is reserved.');
define('_PROFILE_MA_DISPLAYNAMETAKEN','ERROR: Displayname taken.');
define('_PROFILE_MA_LOGINNAMETAKEN','ERROR: Loginname taken.');
define('_PROFILE_MA_EMAILTAKEN','ERROR: Email address already registered.');
define('_PROFILE_MA_ENTERPWD','ERROR: You must provide a password.');
define('_PROFILE_MA_SORRYNOTFOUND','Sorry, no corresponding user info was found.');
define("_PROFILE_MA_WRONGPASSWORD", "ERROR: Wrong Password");
define("_PROFILE_MA_USERALREADYACTIVE", "User with email %s is already activated");

// %s is your site name
define('_PROFILE_MA_YOURACCOUNT', 'Your account at %s');

// %s is a username
define('_PROFILE_MA_ACTVMAILNG', 'Failed sending notification mail to %s');
define('_PROFILE_MA_ACTVMAILOK', 'Notification mail to %s sent.');

define("_PROFILE_MA_DEFAULT", "Basic Information");

//%%%%%%		File Name userinfo.php 		%%%%%
define('_PROFILE_MA_SELECTNG','No User Selected! Please go back and try again.');
define('_PROFILE_MA_EDITPROFILE','Edit Profile');
define('_PROFILE_MA_LOGOUT','Logout');
define('_PROFILE_MA_ALLABOUT','All about %s');
define('_PROFILE_MA_SHOWALL','Show All');

//%%%%%%		File Name edituser.php 		%%%%%
define('_PROFILE_MA_PROFILE','Profile');
define('_PROFILE_MA_DISPLAYNAME','Displayname');
define('_PROFILE_MA_PASSWORD','Password');
define('_PROFILE_MA_TYPEPASSTWICE','(type a new password twice to change it)');
define('_PROFILE_MA_SAVECHANGES','Save Changes');
define('_PROFILE_MA_NOEDITRIGHT',"Sorry, you don't have the right to edit this user's info.");
define('_PROFILE_MA_PASSNOTSAME','Both passwords are different. They must be identical.');
define('_PROFILE_MA_PWDTOOSHORT','Sorry, your password must be at least <b>%s</b> characters long.');
define("_PROFILE_MA_NOPASSWORD", "Please input a password");
define('_PROFILE_MA_PROFUPDATED','Your Profile Updated!');
define('_PROFILE_MA_NO','No');
define('_PROFILE_MA_DELACCOUNT','Delete Account');
define('_PROFILE_MA_UPLOADMYAVATAR', 'Upload Avatar');
define('_PROFILE_MA_MAXPIXEL','Max Pixels');
define('_PROFILE_MA_MAXIMGSZ','Max Image Size (Bytes)');
define('_PROFILE_MA_SELFILE','Select file');
define('_PROFILE_MA_OLDDELETED','Your old avatar will be deleted!');
define('_PROFILE_MA_CHOOSEAVT', 'Choose avatar from the available list');
define('_PROFILE_MA_ADMINNO', 'User in the webmasters group cannot be removed');
define('_PROFILE_MA_NOPERMISS',"Sorry, you don't have the permission to perform this action!");
define('_PROFILE_MA_SURETODEL','Are you sure you want to delete your account?');
define('_PROFILE_MA_REMOVEINFO','This will remove all your info from our database.');
define('_PROFILE_MA_BEENDELED','Your account has been deleted.');

//changepass.php
define("_PROFILE_MA_CHANGEPASSWORD", "Change Password");
define("_PROFILE_MA_PASSWORDCHANGED", "Password Changed Successfully");
define("_PROFILE_MA_OLDPASSWORD", "Current Password");
define("_PROFILE_MA_NEWPASSWORD", "New Password");

//search.php
define("_PROFILE_MA_SORTBY", "Sort By");
define("_PROFILE_MA_ORDER", "Order");
define("_PROFILE_MA_PERPAGE", "Users per page");
define("_PROFILE_MA_LATERTHAN", "%s is later than");
define("_PROFILE_MA_EARLIERTHAN", "%s is earlier than");
define("_PROFILE_MA_LARGERTHAN", "%s is larger than");
define("_PROFILE_MA_SMALLERTHAN", "%s is smaller than");

define("_PROFILE_MA_NOUSERSFOUND", "No users found");
define("_PROFILE_MA_RESULTS", "Search Results");

//changemail.php
define("_PROFILE_MA_SENDPM", "Send Email");
define("_PROFILE_MA_CHANGEMAIL", "Change Email");
define("_PROFILE_MA_NEWMAIL", "New Email Address");

define("_PROFILE_MA_NEWEMAILREQ", "New Email Address Request");
define("_PROFILE_MA_NEWMAILMSGSENT", "Your New Email Address Change Request has been received and logged. You must confirm your new email address before your session expires. An email with activation instructions has been sent to the new email address you entered. Please follow the instructions in that email to make the change. Do not close your browser until you click on the confirmation link in the e-mail address. Your email address WILL NOT CHANGE UNLESS you confirm it.");
define("_PROFILE_MA_EMAILCHANGED", "Your Email Address Has Been Changed");

define("_PROFILE_MA_DEACTIVATE", "Deactivate");
define("_PROFILE_MA_ACTIVATE", "Activate");
define("_PROFILE_MA_CONFCODEMISSING", "Confirmation Code Missing");
define("_PROFILE_MA_SITEDEFAULT", "Site default");


define("_PROFILE_MA_USERINFO","User profile");
define("_PROFILE_MA_REGISTER","Registration form");
//Present in many files (videos pictures etc...)
define("_MD_PROFILE_DELETE", "Delete");
define("_MD_PROFILE_EDITDESC", "Edit description");
define("_MD_PROFILE_PAGETITLE","%s's Profile");
define("_MD_PROFILE_VIDEOS","Videos");

define("_MD_PROFILE_PHOTOS","Photos");
define("_MD_PROFILE_FRIENDS","Friends");
define("_MD_PROFILE_TRIBES","Tribes");
define("_MD_PROFILE_PROFILE","Profile");

define("_MD_PROFILE_UPLOADLIMIT", "You have already reached your upload limit of %s file(s).");

//friendship.php
define("_MD_PROFILE_FRIENDSHIPS_NOCONTENT", "There are no friends yet");

//audio.php
define("_MD_PROFILE_AUDIOS_NOCONTENT", "There is no audio content yet");
define("_MD_PROFILE_AUDIOS_SUBMIT", "Submit new audio file");
define("_MD_PROFILE_AUDIOS_EDIT", "Edit existing audio file");
define("_MD_PROFILE_AUDIOS_CREATED", "Audio file successfully added");
define("_MD_PROFILE_AUDIOS_MODIFIED", "Audio file successfully modified");
define("_MD_PROFILE_AUDIOS_PLAYER", "Player");
define("_MD_PROFILE_AUDIOS_AUTHOR", "Author");
define("_MD_PROFILE_AUDIOS_TITLE", "Title");
define("_MD_PROFILE_AUDIOS_ACTIONS", "Actions");

//pictures.php
define("_MD_PROFILE_PICTURES_NOCONTENT", "There are no pictures in this album yet");
define("_MD_PROFILE_PICTURES_SUBMIT", "Submit new photo");
define("_MD_PROFILE_PICTURES_EDIT", "Edit existing photo");
define("_MD_PROFILE_PICTURES_CREATED", "Photo successfully added");
define("_MD_PROFILE_PICTURES_MODIFIED", "Photo successfully modified");
define("_MD_PROFILE_PICTURES_AVATAR_EDITED", "Avatar successfully modified");
define("_MD_PROFILE_PICTURES_AVATAR_NOCOPY", "Error while trying to copy the avatar");
define("_MD_PROFILE_PICTURES_AVATAR_NOTEDITED", "There was an error while updating your avatar");
define("_MD_PROFILE_PICTURES_AVATAR_DELETED", "Avatar successfully deleted");
define("_MD_PROFILE_PICTURES_AVATAR_NOTDELETED", "There was an error while deleting your avatar");
define("_MD_PROFILE_PICTURES_AVATAR_SET", "Set this picture as avatar");

//tribes.php
define("_MD_PROFILE_TRIBES_NOCONTENT", "There are no tribes or tribe memberships yet");
define("_MD_PROFILE_TRIBES_SUBMIT", "Create new tribe");
define("_MD_PROFILE_TRIBES_EDIT", "Edit existing tribe");
define("_MD_PROFILE_TRIBES_CREATED", "Tribe successfully created");
define("_MD_PROFILE_TRIBES_MODIFIED", "Tribe successfully modified");
define("_MD_PROFILE_TRIBES_OWN", "Own Tribes");
define("_MD_PROFILE_TRIBES_MEMBERSHIPS", "Memberships");
define("_MD_PROFILE_TRIBEUSER_SUBMIT", "Add a tribeuser");
define("_MD_PROFILE_TRIBEUSER_JOIN", "Join this tribe");
define("_MD_PROFILE_TRIBEUSER_CREATED", "The tribeuser has been successfully created.");
define("_MD_PROFILE_TRIBEUSER_MODIFIED", "The tribeuser has been successfully modified.");
define("_PROFILE_TRIBEUSER_DUPLICATE", "The specified user is already a member of this tribe.");
define("_PROFILE_TRIBEUSER_OWNER", "The specified user is already the owner of this tribe and therefore cannot be a member.");
define("_MD_PROFILE_TRIBES_MEMBERS", "Members");
define("_MD_PROFILE_TRIBES_TOPICS", "Topics");
define("_MD_PROFILE_TRIBES_DISCUSSIONS", "Discussions");
define("_MD_PROFILE_TRIBES_STATISTICS", "Statistics");
define("_MD_PROFILE_TRIBES_CREATION_TIME", "Since");
define("_MD_PROFILE_TRIBES_CLICKS", "Clicks");
define("_MD_PROFILE_TRIBES_OWNER", "Owner");
define("_MD_PROFILE_TRIBES_NOTFOUND", "Tribe not found.");
define("_MD_PROFILE_TRIBETOPIC_SUBMIT", "Submit new topic");
define("_MD_PROFILE_TRIBETOPIC_CREATED", "Topic successfully created");
define("_MD_PROFILE_TRIBETOPIC_MODIFIED", "Topic successfully modified");
define("_MD_PROFILE_TRIBEPOST_SUBMIT", "Submit new post");
define("_MD_PROFILE_TRIBEPOST_CREATED", "Post successfully created");
define("_MD_PROFILE_TRIBEPOST_MODIFIED", "Post successfully modified");
define("_MD_PROFILE_TRIBETOPIC_TITLE", "Title");
define("_MD_PROFILE_TRIBETOPIC_AUTHOR", "Author");
define("_MD_PROFILE_TRIBETOPIC_REPLIES", "Replies");
define("_MD_PROFILE_TRIBETOPIC_VIEWS", "Views");
define("_MD_PROFILE_TRIBETOPIC_LAST_POST_TIME", "Last post");
define("_MD_PROFILE_TRIBETOPIC_NOTFOUND", "Topic not found.");
define("_MD_PROFILE_TRIBETOPIC_EDIT", "Edit Topic");
define("_MD_PROFILE_TRIBEPOST_EDIT", "Edit Post");
define("_MD_PROFILE_TRIBETOPIC_SHOW_LAST_POST", "Show last post");
define("_MD_PROFILE_TRIBETOPIC_CLOSE", "Close");
define("_MD_PROFILE_TRIBETOPIC_CLOSED", "Closed");
define("_MD_PROFILE_TRIBETOPIC_REOPEN", "Reopen");
define("_MD_PROFILE_TRIBES_JOINFIRST", "You have to be a member of this tribe to see all others members and disucssions.");
define("_MD_PROFILE_TRIBES_SEARCH", "Search a tribe");
define("_MD_PROFILE_TRIBES_SEARCH_TITLE", "Search results for: %s");
define("_MD_PROFILE_TRIBES_SEARCH_NORESULTS", "No tribes found for: %s");

//videos.php
define("_MD_PROFILE_VIDEOS_NOCONTENT", "There are no videos yet");
define("_MD_PROFILE_VIDEOS_SUBMIT", "Submit new video");
define("_MD_PROFILE_VIDEOS_EDIT", "Edit existing video");
define("_MD_PROFILE_VIDEOS_CREATED", "Video successfully added");
define("_MD_PROFILE_VIDEOS_MODIFIED", "Video successfully modified");
define("_MD_PROFILE_VIDEOS_VIDEO", "Video");
define("_MD_PROFILE_VIDEOS_DESCRIPTION", "Video description");
define("_MD_PROFILE_VIDEOS_ACTIONS", "Actions");

//index.php
define("_MD_PROFILE_VISITORS","Visitors (who visited your profile recently)");
define("_MD_PROFILE_USERDETAILS","User details");
define("_MD_PROFILE_USERCONTRIBUTIONS","User contributions");
define("_MD_PROFILE_EDITPROFILE","Edit your profile");
define("_MD_PROFILE_SELECTAVATAR","Upload pictures to your album and select one as your avatar.");
define("_MD_PROFILE_CONTACTINFO","Contact Info");
define("_MD_PROFILE_SECURITY_CHECK_FAILED", "");
define("_MD_PROFILE_TRIBES_INVITATIONS", "You've been invited to join the following tribes");
define("_MD_PROFILE_TRIBES_APPROVALS", "The following users want to be approved to join your tribe");
define("_MD_PROFILE_TRIBEUSER_NOTFOUND", "Tribeuser not found.");
define("_MD_PROFILE_TRIBEUSER_APPROVE", "Approve this user");
define("_MD_PROFILE_TRIBEUSER_ACCEPT", "Accept invitation to this tribe");
define("_MD_PROFILE_TRIBEUSER_OP_SUCCESS", "Operation successfully performed");
define("_MD_PROFILE_AUDIOS","Audio");
define("_MD_PROFILE_FRIENDSHIP_ADD", "Add this user as a friend");
define("_MD_PROFILE_FRIENDSHIP_CREATED", "This user has been added to your friendlist! They will appear once they approve your request.");
define("_MD_PROFILE_FRIENDSHIP_MODIFIED", "The friendship was successfully modified.");
define("_MD_PROFILE_FRIENDSHIP_PENDING", "Pending friendships (only you can see this)");
define("_MD_PROFILE_FRIENDSHIP_ACCEPTED", "Accepted friendships");
define("_MD_PROFILE_FRIENDSHIP_REJECTED", "Rejected friendships (only you can see this)");
define("_MD_PROFILE_FRIENDSHIP_ACCEPT", "Accept friendship");
define("_MD_PROFILE_FRIENDSHIP_REJECT", "Reject friendship");
define("_MD_PROFILE_GOTO", "goto: ");

// configs.php
define('_MD_PROFILE_CONFIGS_SUBMIT', 'Change Profile Settings');
define('_MD_PROFILE_CONFIGS_EDIT', 'Edit Profile Settings');
define('_MD_PROFILE_CONFIGS_CREATED', 'The Profile Settings were created');
define('_MD_PROFILE_CONFIGS_MODIFIED', 'The Profile Settings were updated');
?>