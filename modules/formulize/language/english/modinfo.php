<?php
// Module Info

// The name of this module
define("_MI_formulize_NAME","Forms");

// A brief description of this module
define("_MI_formulize_DESC","For provisioning forms and analyzing data");

// admin/menu.php
define("_MI_formulize_ADMENU0","Form management");
define("_MI_formulize_ADMENU1","Menu");

// notifications
define("_MI_formulize_NOTIFY_FORM", "Form Notifications");
define("_MI_formulize_NOTIFY_FORM_DESC", "Notifications related to the current form");
define("_MI_formulize_NOTIFY_NEWENTRY", "New Entry in a Form");
define("_MI_formulize_NOTIFY_NEWENTRY_CAP", "Notify me when someone makes a new entry in this form");
define("_MI_formulize_NOTIFY_NEWENTRY_DESC", "A notification option that alerts users when new entries are made in a form");
define("_MI_formulize_NOTIFY_NEWENTRY_MAILSUB", "New Entry in a Form");

define("_MI_formulize_NOTIFY_UPENTRY", "Updated Entry in a Form");
define("_MI_formulize_NOTIFY_UPENTRY_CAP", "Notify me when someone updates an entry in this form");
define("_MI_formulize_NOTIFY_UPENTRY_DESC", "A notification option that alerts users when entries are updated in a form");
define("_MI_formulize_NOTIFY_UPENTRY_MAILSUB", "Updated Entry in a Form");

define("_MI_formulize_NOTIFY_DELENTRY", "Entry deleted from a Form");
define("_MI_formulize_NOTIFY_DELENTRY_CAP", "Notify me when someone deletes an entry from this form");
define("_MI_formulize_NOTIFY_DELENTRY_DESC", "A notification option that alerts users when entries are deleted from a form");
define("_MI_formulize_NOTIFY_DELENTRY_MAILSUB", "Entry deleted from a Form");


//	preferences
define("_MI_formulize_TEXT_WIDTH","Default width of text boxes");
define("_MI_formulize_TEXT_MAX","Default maximum length of text boxes");
define("_MI_formulize_TAREA_ROWS","Default rows of text areas");
define("_MI_formulize_TAREA_COLS","Default columns of text areas");
define("_MI_formulize_DELIMETER","Default delimiter for check boxes and radio buttons");
define("_MI_formulize_DELIMETER_SPACE","White space");
define("_MI_formulize_DELIMETER_BR","Line break");
define("_MI_formulize_SEND_METHOD","Send method");
define("_MI_formulize_SEND_METHOD_DESC","Note: Form submitted by anonymous users cannot be sent by using private message.");
define("_MI_formulize_SEND_METHOD_MAIL","Email");
define("_MI_formulize_SEND_METHOD_PM","Private message");
define("_MI_formulize_SEND_GROUP","Send to group");
define("_MI_formulize_SEND_ADMIN","Send to site admin only");
define("_MI_formulize_SEND_ADMIN_DESC","Settings of \"Send to group\" will be ignored");
define("_MI_formulize_PROFILEFORM","Which form is to be used as part of the registration process and when viewing and editing accounts? (requires use of the Registration Codes module)");

define("_MI_formulize_ALL_DONE_SINGLES","Should the 'All Done' button appear at the bottom of the form when editing an entry, and creating a new entry in a 'one-entry-per-user' form?");
define("_MI_formulize_SINGLESDESC","The 'All Done' button is used to leave a form without saving the information in the form.  If you have made changes to the information in a form and then you click 'All Done' without first clicking 'Save', you get a warning that your data has not been saved.  Because of the way the 'Save' button and 'All Done' button work in tandem, there is normally no way to save information and leave a form all at once.  This bothers/confuses some users.  Set this option to 'Yes' to remove the 'All Done' button and turn the behaviour of the 'Save' button to 'save-and-leave-the-form-all-at-once'.  This option does not affect situations where the user is adding multiple entries to a form (where the form reloads blank every time you click 'Save').");

define("_MI_formulize_LOE_limit", "What is the maximum number of entries that should be displayed in a list of entries, without confirmation from the user that they want to see all entries?");
define("_MI_formulize_LOE_limit_DESC", "If a dataset is very large, displaying a list of entries screen can take a long time, several minutes even.  Use this preference to specify the maximum number of entries that your system should try to display at once.  If a dataset contains more entries than this limit, the user will be asked if they want to load the entire dataset or not.");
       
define("_MI_formulize_USETOKEN", "Use the security token system to validate form submissions?");
define("_MI_formulize_USETOKENDESC", "By default, when a form is submitted, no data is saved unless Formulize can validate a unique token that was submitted with the form.  This is a partial defence against cross site scripting attacks, meant to ensure only people actually visiting your website can submit forms.  In some circumstances, depending on firewalls or other factors, the token cannot be validated even when it should be.  If this is happening to you repeatedly, you can turn off the token system for Formulize here.  <b>NOTE: you can override this global setting on a screen by screen basis.</b>");

define("_MI_formulize_NUMBER_DECIMALS", "By default, how many decimal places should be displayed for numbers?");
define("_MI_formulize_NUMBER_DECIMALS_DESC", "Normally, leave this as 0, unless you want every number in all forms to have a certain number of decimal places.");
define("_MI_formulize_NUMBER_PREFIX", "By default, should any symbol be shown before numbers?");
define("_MI_formulize_NUMBER_PREFIX_DESC", "For example, if your entire site only uses dollar figures in forms, then put '$' here.  Otherwise, leave it blank.");
define("_MI_formulize_NUMBER_SUFFIX", "By default, should any symbol be shown after numbers?");
define("_MI_formulize_NUMBER_SUFFIX_DESC", "For example, if your entire site only uses percentage figures in forms, then put '%' here.  Otherwise, leave it blank.");
define("_MI_formulize_NUMBER_DECIMALSEP", "By default, if decimals are used, what punctuation should separate them from the rest of the number?");
define("_MI_formulize_NUMBER_SEP", "By default, what punctuation should be used to separate thousands in numbers?");

define("_MI_formulize_HEADING_HELP_LINK", "Should the help link ([?]) appear at the top of each column in a list of entries?");
define("_MI_formulize_HEADING_HELP_LINK_DESC", "This link provides a popup window that shows details about the question in the form, such as the full text of the question, the choice of options if the question is a radio button, etc.");
       
define("_MI_formulize_USECACHE", "Use caching to speed up Procedures?");
define("_MI_formulize_USECACHEDESC", "By default, caching is on.");

define("_MI_formulize_DOWNLOADDEFAULT", "When users are exporting data, use a compatibility trick for some versions of Excel by default?");
define("_MI_formulize_DOWNLOADDEFAULT_DESC", "When users export data, they can check a box on the download page that adds a special code to the file which is necessary to make accented characters appear properly in some versions of Microsoft Excel.  This option controls whether that checkbox is checked by default or not.  You should experiment with your installation to see if exports work best with or without this option turned on.");
       
define("_MI_formulize_LOGPROCEDURE", "Use logging to monitor Procedures and parameters?");
define("_MI_formulize_LOGPROCEDUREDESC", "By default, logging is off.");


// The name of this module
define("_MI_formulizeMENU_NAME","MyMenu");

// A brief description of this module
define("_MI_formulizeMENU_DESC","Displays an individually configurable menu in a block");

// Names of blocks for this module (Not all module has blocks)
define("_MI_formulizeMENU_BNAME","Form Menu");
