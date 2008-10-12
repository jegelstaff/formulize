<?php

// English strings for displaying information about this module in the site administration web pages


define("_TPL_REG_CODES_TYPECODE", "Type in a custom registration code, or leave blank to auto-generate a code");
define("_TPL_REG_CODES_SELECTGROUPS", "Select the groups for which this code will give membership");
define("_TPL_REG_CODES_EXPIRYDATE", "Select an expiry date for the code");
define("_TPL_REG_CODES_MAXUSES", "Select a maximum number of uses for this code (0 means unlimited uses)");
define("_TPL_REG_CODES_REDIRECT", "Enter a URL where users should be sent after their account is created (optional)");
define("_TPL_REG_CODES_INSTANT", "Do people who use this code have to fill in the registration form, or should they have accounts created for them instantly?");
define("_TPL_REG_CODES_REGFORM", "Fill in the registration form");
define("_TPL_REG_CODES_NOREGFORM", "Create accounts instantly");
define("_TPL_REG_CODES_APPROVAL_GROUPS", "Select the groups that can approve accounts created with this code");
define("_TPL_REG_CODES_PRE_APPROVED", "Enter the email addresses that are pre-approved (please put each address on a separate line)");
define("_TPL_REG_CODES_APPROVAL_GROUPS_NOTREQ", "None - approval not required");
define("_TPL_REG_CODES_SAVEIT", "Save Registration Code");
define("_TPL_REG_CODES_CREATEIT", "Create a new registration code");

// PA entries kw 09.17.2007
define("_TPL_REG_CODES_PA_HEADER_DISPLAY", "Display Pre-Approved Users");
define("_TPL_REG_CODES_PA_HEADER_USERS", "Pre-Approved Users:");
define("_TPL_REG_CODES_PA_HEADER_ADDUSERS", "Enter pre-approved user (email address)  ");
define("_MI_REG_CODES_PA_TITLE_MAIN", "Pre-Approved Users");  
define("_MI_REG_CODES_PA_TITLE_SUSPEND", "Delete Pre-Approved Users"); 
define("_MI_REG_CODES_PA_TITLE_EDIT", "Edit Pre-Approved Users");  
define("_MI_REG_CODES_PA_TITLE_ADD", "Add Pre-Approved Users"); 
define("_MI_REG_CODES_PA_TITLE_ADDSAVE", "Save New Pre-Approved User");   
define("_MI_REG_CODES_PA_TITLE_SAVE", "Save Pre-Approved Users");  
define("_MI_REG_CODES_PA_TITLE_SUSPENDSAVE", "Confirm suspending Pre-Approved User"); 
define("_TPL_REG_CODES_PA_NOTFOUND", "No pre-approved users assigned to this registration key");
define("_TPL_REG_CODES_PA_DISPLAY", "Display&nbsp;Pre-Approved&nbsp;Users");
define("_TPL_REGCODES_PA_SUSPENDSURE", "Are you sure you want to delete this user?");
define("_TPL_REGCODES_PA_DELETE", "Delete&nbsp;user");
define("_TPL_REGCODES_PA_ADDUSER", "Add&nbsp;new&nbsp;pre-approved&nbsp;user"); 
define("_TPL_REGCODES_PA_ADD", "Add&nbsp;user");
define("_TPL_REGCODES_PA_YES", "Yes");
define("_TPL_REGCODES_PA_NO", "No");

define("_TPL_REGCODES_CODELIST", "Registration Codes");
define("_TPL_REGCODES_HEADERCODE", "Code:");
define("_TPL_REGCODES_HEADERGROUPS", "Allows Membership in:");
define("_TPL_REGCODES_HEADEREXPIRY", "Valid Until:");
define("_TPL_REGCODES_HEADERMAXUSES", "Maximum Uses:");
define("_TPL_REGCODES_HEADERCURUSES", "Current Uses:");
define("_TPL_REGCODES_HEADERREDIRECT", "Redirect destination:");
define("_TPL_REGCODES_HEADERINSTANT", "Instant account creation:");
define("_TPL_REGCODES_HEADERAPPROVAL", "Approval by these groups:");
define("_TPL_REGCODES_HEADERSTATUS", "Status:");
define("_TPL_REGCODES_HEADERACTIONS", "Actions:");
define("_TPL_REGCODES_STATUS_ACTIVE", "Active");
define("_TPL_REGCODES_STATUS_EXPIRED", "<b>Expired</b>");
define("_TPL_REGCODES_STATUS_MAXEDOUT", "<b>Max Usage Exceeded</b>");

define("_TPL_REGCODES_DELETE", "Delete");
define("_TPL_REGCODES_MODIFY", "Modify");
define("_TPL_REGCODES_SUSPEND", "Suspend"); //kw 09.13.2007
define("_TPL_REGCODES_SAVE", "Save"); //kw 09.13.2007
define("_TPL_REGCODES_CANCEL", "Cancel"); //kw 09.13.2007
define("_TPL_REGCODES_GO_BACK", "Back to the previous page"); //kw 09.13.2007

define("_REGFORM_REGCODES_FULLNAME", "Full Name");
define("_REGFORM_REGCODES_REGCODE", "Registration Code");
define("_REGFORM_REGCODES_HELP", "If you do not have a registration code, you can <a href=\"register.php?code=nocode\">click here to register for a basic account</a>.");
define("_REGFORM_REGCODES_HELP2a", "If you do not have a registration code, please contact the ");
define("_REGFORM_REGCODES_HELP2b", "webmaster");
define("_REGFORM_REGCODES_HELP2c", " for assistance.");
define("_REGFORM_REGCODES_INVALID", "The registration code you entered is not valid.");
define("_US_WEBSITE", "If you have a website, enter it here");
define("_US_PASSWORD", "Password (must be at least 5 characters long)");


define("_TPL_REGCODES_NOCODES", "There are no currently valid codes.");

// The name of this module. Prefix (_MI_) is for Module Information
define("_MI_REG_CODES_NAME", "Registration Codes");
define("_MI_REG_CODES_TITLE_NEW", "Create a new Registration Code");
define("_MI_REG_CODES_TITLE_EDIT", "Edit Registration Code");
define("_MI_REG_CODES_TITLE_MAIN", "Registration Codes");
define("_MI_REG_CODES_DESC", "Create Registration Codes to automatically assign group membership to new users upon registration.");

// Names of blocks in this module. Note that not all modules have blocks
define("_MI_REG_CODES_BLOCK_ONE_TITLE", "Registration Codes: Sample Block");
define("_MI_REG_CODES_BLOCK_ONE_DESC", "A simple, working block example.");
define("_MI_REG_CODES_BLOCK_TWO_TITLE", "Registration Codes: Database Block");
define("_MI_REG_CODES_BLOCK_TWO_DESC", "A simple, working block example that queries a database.");

// Names of the menu items displayed for this module in the site administration web pages
define("_MI_REG_CODES_MENU_MAIN", "Main");
define("_MI_REG_CODES_MENU_MAIN_DESC", "Control permissions for issuing Registration Codes");
define("_MI_REG_CODES_MENU_USERMANAGER", "User Manager");
define("_MI_REG_CODES_MENU_USERMANAGER_DESC", "Move or copy users from group(s) to group(s)");

define("_MI_REG_CODES_MENU_EDIT", "Edit");
define("_MI_REG_CODES_MENU_EDIT_DESC", "Edit a Database Table in Registration Codes");
define("_MI_REG_CODES_MENU_CONFIG", "Configure");
define("_MI_REG_CODES_MENU_CONFIG_DESC", "Set configuration options for Registration Codes");
define("_MI_REG_CODES_MENU_HELP", "Help");
define("_MI_REG_CODES_MENU_HELP_DESC", "Open the help file for XooperStore in a new window");

define("_MI_REG_CODES_AVP", "Can anonymous users view registered users' profiles?");
define("_MI_REG_CODES_AVP_DESC", "If turned on, then the user profile pages -- and all the data people have entered into their profile forms -- can be viewed by anonymous users.  If turned off, the user profile pages are unavailable to anonymous users.");

define("_MI_REG_CODES_NOTDEF", "Default Notification Method");
define("_MI_REG_CODES_NOTDEF_DESC", "Choose whether new users should have their notification method set to Private Message or E-mail");
define("_MI_REG_CODES_NOTDEF_EMAIL", "E-mail");
define("_MI_REG_CODES_NOTDEF_PM", "Private Message");

define("_MI_REG_CODES_LIMITBYGROUPS", "Only allows users to view profiles of other users in the same group(s)?");
define("_MI_REG_CODES_EMAILASUSERNAME", "Use email address as user name?");

?>