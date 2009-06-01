<?php

// English strings for displaying information about this module in the site administration web pages


define("_TPL_REG_CODES_SELECTGROUPS", "Select the groups for which this code will give membership");
define("_TPL_REG_CODES_EXPIRYDATE", "Select an expiry date for the code");
define("_TPL_REG_CODES_MAXUSES", "Select a maximum number of uses for this code (0 means unlimited uses)");
define("_TPL_REG_CODES_CREATEIT", "Create Code");

define("_TPL_REGCODES_CODELIST", "Currently Valid Codes");
define("_TPL_REGCODES_HEADERCODE", "Code:");
define("_TPL_REGCODES_HEADERGROUPS", "Allows Membership in:");
define("_TPL_REGCODES_HEADEREXPIRY", "Valid Until:");
define("_TPL_REGCODES_HEADERMAXUSES", "Maximum Uses:");
define("_TPL_REGCODES_HEADERCURUSES", "Current Uses:");

define("_TPL_REGCODES_DELETE", "Delete");

define("_REGFORM_REGCODES_FULLNAME", "Nom complet");
define("_REGFORM_REGCODES_REGCODE", "Code d'enregistrement");
define("_REGFORM_REGCODES_HELP", "Si vous n'avez pas un code d'enregistrement, vous pouvez <a href=\"register.php?code=nocode\">cliquer ici pour créer un compte de base</a>.");
define("_REGFORM_REGCODES_HELP2a", "Si vous n'avez pas un code d'enregistrement, ");
define("_REGFORM_REGCODES_HELP2b", "envoyez un courriel au webmaster");
define("_REGFORM_REGCODES_HELP2c", ".");
define("_REGFORM_REGCODES_INVALID", "Le code d'enregistrement que vous avez utilisé n'est pas valide.");
define("_US_WEBSITE", "If you have a website, enter it here");
define("_US_PASSWORD", "Password (must be at least 5 characters long)");


define("_TPL_REGCODES_NOCODES", "There are no currently valid codes.");

// The name of this module. Prefix (_MI_) is for Module Information
define("_MI_REG_CODES_NAME", "Registration Codes");
define("_MI_REG_CODES_TITLE", "Create a new registration code");

// The description of this module
define("_MI_REG_CODES_DESC", "Create Registration Codes to automatically assign group membership to new users upon registration.");

// Names of blocks in this module. Note that not all modules have blocks
define("_MI_REG_CODES_BLOCK_ONE_TITLE", "Registration Codes: Sample Block");
define("_MI_REG_CODES_BLOCK_ONE_DESC", "A simple, working block example.");
define("_MI_REG_CODES_BLOCK_TWO_TITLE", "Registration Codes: Database Block");
define("_MI_REG_CODES_BLOCK_TWO_DESC", "A simple, working block example that queries a database.");

// Names of the menu items displayed for this module in the site administration web pages
define("_MI_REG_CODES_MENU_MAIN", "Main");
define("_MI_REG_CODES_MENU_MAIN_DESC", "Control permissions for issuing Registration Codes");
define("_MI_REG_CODES_MENU_EDIT", "Edit");
define("_MI_REG_CODES_MENU_EDIT_DESC", "Edit a Database Table in Registration Codes");
define("_MI_REG_CODES_MENU_CONFIG", "Configure");
define("_MI_REG_CODES_MENU_CONFIG_DESC", "Set configuration options for Registration Codes");
define("_MI_REG_CODES_MENU_HELP", "Help");
define("_MI_REG_CODES_MENU_HELP_DESC", "Open the help file for XooperStore in a new window");

// Config language
define("_MI_REG_CODES_AVP", "Can anonymous users view registered users' profiles?");
define("_MI_REG_CODES_AVP_DESC", "If turned on, then the user profile pages -- and all the data people have entered into their profile forms -- can be viewed by anonymous users.  If turned off, the user profile pages are unavailable to anonymous users.");



?>