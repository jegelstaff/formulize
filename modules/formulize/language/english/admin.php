<?php
/*mod Language for admin side, by Fran�ois T*/

/*mod Language for applications*/
define("_AM_APP_APPLICATION","Application: ");
define("_AM_APP_SETTINGS","Settings");
define("_AM_APP_FORMS","Forms");
define("_AM_APP_FORM","Form: ");
define("_AM_APP_RELATIONSHIPS_CREATE"," Create a new relationship");
define("_AM_APP_RELATIONSHIPS_MANAGE"," Manage existing relationships");
define("_AM_APP_RELATIONSHIPS_DELETE_CONFIRM","Are you sure you want to delete this relationship, and all its links?");
define("_AM_APP_RELATIONSHIPS","Relationships");
define("_AM_APP_FORMWITHNOAPP","Forms that don't belong to an application");
define("_AM_APP_SCREENS","Screens");
define("_AM_APP_NEWFORM","New form");
define("_AM_APP_USETITLE","Use the form's title");
define("_AM_APP_NAMEQUESTION","What is the name of this Application?");
define("_AM_APP_DESCQUESTION","Description of this application:");
define("_AM_APP_FORMSIN","Forms in this application:");
define("_AM_APP_CONFIGURE","Configure");
define("_AM_APP_VIEW_DEFAULT_SCREEN","View (with the Default Screen's options)");
define("_AM_APP_VIEW_OPTIONS_SCREEN","View (with all screen options on)");
define("_AM_APP_CLONE_SIMPLY","Clone");
define("_AM_APP_CLONE_WITHDATA","Clone with data");
define("_AM_APP_LOCKDOWN","Lockdown");
define("_AM_APP_DELETE_FORM","Delete");
define("_AM_APP_CREATE_NEW_SCREEN","Create a new screen");
define("_AM_APP_DEFAULTSCREENS","Default Screens:");
define("_AM_APP_MORESCREENS","...more screens");





/*mod Language for home*/
define("_AM_HOME_PREF"," Formulize Preferences");
define("_AM_HOME_NEWDATATABLE"," Create a new reference to a datatable");
define("_AM_HOME_MANAGEAPP","Manage your applications");
define("_AM_HOME_NEWFORM"," Create a new form");
define("_AM_HOME_CONFIRMDELETEFORM","Are you sure you want to delete this form?  All data associated with this form will be lost.");
define("_AM_HOME_CONFIRMDELETEAPP","Are you sure you want to delete this application?  All the forms will be unaffected, but the application will no longer appear in the application list.");
define("_AM_HOME_CONFIRMLOCKDOWN","Are you sure you want to lockdown this form?  You will be unable to change any of the configuration settings for the form or its elements once the form is locked down.");
define("_AM_HOME_APP_CONFIG","Configure this application and the relationships of its forms");
define("_AM_HOME_APP_DELETE","Delete this application");
define("_AM_HOME_APP_DESC","To assign a form to an application, look on the Settings tab when configuring the form.");
define("_AM_HOME_APP_RELATION","Configure relationships (frameworks) for these forms");
define("_AM_HOME_GOBACKTO","Go Back to ");
define("_AM_HOME_SAVECHANGES","Save your changes");
define("_AM_HOME_WARNING_UNSAVED","You have unsaved changes!");




/*mod Language for elements*/
define("_AM_ELE_NAMEANDSETTINGS","Name & Settings");
define("_AM_ELE_DISPLAYSETTINGS","Display Settings");
define("_AM_ELE_CONVERT_ML", "Convert to multi-line text box");
define("_AM_ELE_CONVERT_SL", "Convert to single-line text box");
define("_AM_ELE_CONVERT_CB", "Convert to check boxes");
define("_AM_ELE_CONVERT_RB", "Convert to radio buttons.");
define("_AM_ELE_ADDINGTOFORM","Add elements to the form");
define("_AM_ELE_MANAGINGELEFORM","Manage the elements in the form");
define("_AM_ELE_CLICKTOADD","Click an element name to add it");
define("_AM_ELE_CLICKDRAGANDDROP","Click and drag the elements to re-order them");
define("_AM_ELE_MLTEXT", "Multi-line text box");
define("_AM_ELE_DROPDORLIST", "(Dropdown box or List box)");
define("_AM_ELE_SELECTEXPLAIN","Select box (dropdowns and list boxes)");
define("_AM_ELE_DATEBOX","Date box");
define("_AM_ELE_SUBFORMEXPLAIN", "Subform (another form with a relationship to this one)");
define("_AM_ELE_LINKSELECTEDABOVE", "Use the linked field selected above");
define("_AM_ELE_VALUEINLIST", "Use the value displayed in the list");
define("_AM_ELE_LINKFIELD_ITSELF", "Use the linked field itself (alphabetical sort)");
define("_AM_CONVERT_RB_CB", "Convert these radio buttons to checkboxes?");
define("_AM_CONVERT_CB_RB", "Convert these checkboxes to radio buttons?");
define("_AM_CONVERT_SB_CB", "Convert this select box to checkboxes?");


/*mod Language for form*/
define("_AM_FORM_CREATE"," Create a new form");
define("_AM_FORM_CREATE_EXPLAIN","To assign a form to an application, look on the Settings tab when configuring the form.");
define("_AM_FORM_SCREEN","Screen: ");
define("_AM_FORM_SCREEN_TEXT","Text");
define("_AM_FORM_SCREEN_PAGES","Pages");
define("_AM_FORM_SCREEN_ENTRIES_DISPLAY","Entries");
define("_AM_FORM_SCREEN_HEADINGS_INTERFACE","Interface");
define("_AM_FORM_SCREEN_ACTION_BUTTONS","Buttons");
define("_AM_FORM_SCREEN_CUSTOM_BUTTONS","Custom buttons");
define("_AM_FORM_SCREEN_TEMPLATES","Templates");
define("_AM_SETTINGS_FORM_TITLE_QUESTION","What is the name of the form?");
define("_AM_SETTINGS_FORM_TITLE","Form title: ");
define("_AM_SETTINGS_MENU_ENTRY","Menu entry: ");
define("_AM_SETTINGS_MENU_LEAVE","Leave the 'menu entry' blank to remove this form from the default menu block");
define("_AM_SETTINGS_FORM_HANDLE","Form handle");
define("_AM_EOG_Repair","Repair entry ownership table");
define("_AM_SETTINGS_FORM_HANDLE_EXPLAIN","Optional. The name will you use to refer to this form in programming code and in the database. Defaults to the form ID number.");
define("_AM_SETTINGS_FORM_DATABASE","Which database table should this 'form' point to?");
define("_AM_SETTINGS_FORM_DATABASE_EXPLAIN","Type the exact name, including the prefix, ie: mysite_groups");
define("_AM_SETTINGS_FORM_ENTRIES_ALLOWED","How many entries are allowed in this form?");
define("_AM_SETTINGS_FORM_ENTRIES_ONEPERGROUP","One entry per <b>group</b>");
define("_AM_SETTINGS_FORM_ENTRIES_ONEPERUSER","One entry per <b>user</b>");
define("_AM_SETTINGS_FORM_ENTRIES_MORETHANONE","<b>More than one entry</b> per user");
define("_AM_SETTINGS_FORM_SHOWING_LIST","When showing a list of entries in this form, which elements should be displayed by default?");
define("_AM_SETTINGS_FORM_APP_PART","Which applications is this form part of?");
define("_AM_SETTINGS_FORM_APPNEW","Create a new application for this form to be part of?");
define("_AM_SETTINGS_FORM_DEFAULT_GROUP_PERM","Which groups of users should have permission to alter this form's settings?");


/*mod Language for permissions*/
define("_AM_PERMISSIONS_CHOOSE_GROUPS","Which groups do you want to set permissions for?");
define("_AM_PERMISSIONS_SHOW_PERMS_FOR_GROUPS", "Show permissions for these groups");
define("_AM_PERMISSIONS_LIST_GROUPS","List groups alphabetically or in creation order?");
define("_AM_PERMISSIONS_LIST_ALPHA","Alphabetical");
define("_AM_PERMISSIONS_LIST_CREATION","Creation order");
define("_AM_PERMISSIONS_LIST_ONCE","Select a list of groups at once");
define("_AM_PERMISSIONS_LIST_SAVE","Save these groups as a list");
define("_AM_PERMISSIONS_LIST_REMOVE","Remove the selected list");
define("_AM_PERMISSIONS_SAME_CHECKBOX","Set the same checkboxes for all groups?");
define("_AM_PERMISSIONS_SAME_CHECKBOX_YES","Yes, when I check a box for one group, check it for all groups");
define("_AM_PERMISSIONS_SAME_CHECKBOX_NO","No, I will set each group individually");
define("_AM_PERMISSIONS_SAME_CHECKBOX_EXPLAIN","You can change this setting at any time while you are adjusting the checkboxes.  Set it to <b>Yes</b>, to quickly set some checkboxes the same for all groups.  Change it to <b>No</b> when you need to set specific checkboxes for only certain groups.");
define("_AM_PERMISSIONS_SELECT_GROUP","Select some groups to see their permissions");
define("_AM_PERMISSIONS_DEFINE_BASIC","The basics:");
define("_AM_PERMISSIONS_DEFINE_VIEWFORM","View the form");
define("_AM_PERMISSIONS_DEFINE_CREATEOWNENTRIES","Create their own entries in the form");
define("_AM_PERMISSIONS_DEFINE_UPDATEOWNENTRIES","Update entries <i>made by themselves</i>");
define("_AM_PERMISSIONS_DEFINE_UPDATE_GROUP_ENTRIES","Update entries <i>made by their group(s)</i>");
define("_AM_PERMISSIONS_DEFINE_UPDATEOTHERENTRIES","Update entries <i>made by anyone</i>");
define("_AM_PERMISSIONS_DEFINE_DELETEOWNENTRIES","Delete entries <i>made by themselves</i>");
define("_AM_PERMISSIONS_DEFINE_DELETE_GROUP_ENTRIES","Delete entries <i>made by their group(s)</i>");
define("_AM_PERMISSIONS_DEFINE_DELETEOTHERENTRIES","Delete entries <i>made by anyone</i>");
define("_AM_PERMISSIONS_DEFINE_VISIBILITY","Visibility:");
define("_AM_PERMISSIONS_DEFINE_VISIBILITY_PRIVATE","View elements in the form that are marked as 'private'");
define("_AM_PERMISSIONS_DEFINE_VISIBILITY_THEIROWN","View their own entries (always on)");
define("_AM_PERMISSIONS_DEFINE_VISIBILITY_VIEWALL","View entries by all other users in all groups");
define("_AM_PERMISSIONS_DEFINE_VISIBILITY_VIEWOTHERGROUPONLY","View entries by other users from these groups only:");
define("_AM_PERMISSIONS_DEFINE_VISIBILITY_VIEWOTHERGROUPISAMEMEBER","All the groups the user is a member of, that can view the form");
define("_AM_PERMISSIONS_DEFINE_VISIBILITY_DISABLED","disabled");
define("_AM_PERMISSIONS_DEFINE_VISIBILITY_CONDITIONS","View only entries that meet these conditions:");
define("_AM_PERMISSIONS_DEFINE_VIEW_CONDITIONS","Publishing 'Saved Views' of form entries:");
define("_AM_PERMISSIONS_DEFINE_VIEW_THEIROWN","Create, update, delete their own 'saved views' (always on)");
define("_AM_PERMISSIONS_DEFINE_VIEW_INTHEIR","Publish 'saved views' for other users <i>in their group(s)</i>");
define("_AM_PERMISSIONS_DEFINE_VIEW_FOROTHER","Publish 'saved views' for other users <i>in any group</i>");
define("_AM_PERMISSIONS_DEFINE_VIEW_UPDATE","Update 'saved views' that other people have published");
define("_AM_PERMISSIONS_DEFINE_VIEW_DELETE","Delete 'saved views' that other people have published");
define("_AM_PERMISSIONS_ADVANCED","Advanced options:");
define("_AM_PERMISSIONS_ADVANCED_IMPORT","Import data from a spreadsheet");
define("_AM_PERMISSIONS_ADVANCED_NOTIFICATIONS","Create notifications that get sent to other users");
define("_AM_PERMISSIONS_ADVANCED_CREATEFOROTHER","Create entries on behalf of other users");
define("_AM_PERMISSIONS_ADVANCED_CHANGEOWNER","Change the owner/creator of an existing entry");
define("_AM_PERMISSIONS_ADVANCED_ALTER","Alter this form's configuration settings");
define("_AM_PERMISSIONS_ADVANCED_DELETEFORM","Delete this form");
define("_AM_PERMISSIONS_REVIEW_PERMISSIONS","Review permissions for a user");

/*mod Language for procedures*/
define("_AM_CALC_EXPLAIN","let you create a series of queries and logical steps, that get carried out on the data that users have submitted in the form.  You can use Procedures for advanced, multi-step calculations, or any other situation where a single query or single operation is not enough to get to the outcome you want.");
define("_AM_CALC_CLONE"," Clone");
define("_AM_CALC_DELETE"," Delete");
define("_AM_CALC_CONFIRM_DELETE","Are you sure you want to delete this procedure?  All settings for this procedure will be lost!");
define("_AM_CALC_PROCEDURE_NAME","Name of the Procedure");
define("_AM_CALC_PROCEDURE_DESCR","Description of the Procedure:");
define("_AM_CALC_PROCEDURE_SETTINGS","Settings for the Procedure: ");
define("_AM_CALC_PROCEDURE_FILTER_CLONE"," Clone this filter and grouping option");
define("_AM_CALC_PROCEDURE_FILTER_DELETE"," Delete this filter and grouping option");
define("_AM_CALC_CREATE_NEW", "Create a new Procedure");

/*mod Language for screens*/
define("_AM_SCREEN_EXPLAIN","<p><i>Screens</i> let you show users different versions of the form, and the entries people have made in the form.  One screen might be a list of entries, another might be a control panel for administrators to edit and delete entries, another might be a multi-page version of a form.  You can have as many different screens as you want, all based on the same underlying form.</p>
	<p>Each screen has its own URL, and can be included in any navigation structure you wish to use.  Screens can also be embedded in any PHP web page, anywhere on your web server, even inside other software like Wordpress or Drupal.  See the <i>Settings</i> page of each screen for details.</p>
	<p>When someone visits a Formulize form, but no specific screen has been requested in the URL, then the user will get either the default list of entries screen, or the default form screen.  Formulize will figure out what the user should see, based on the configuration and permission settings for the form.</p><br>");
define("_AM_SCREEN_CREATE"," Create a new Screen");
define("_AM_SCREEN_FORMSCREENS","Form Screens");
define("_AM_SCREEN_LISTSCREENS","List Screens");
define("_AM_SCREEN_DELETESCREENS","Are you sure you want to delete this screen? All configuration settings for this screen will be lost!");























/*End mod Language for admin side, by Fran�ois T*/

// Admin
define("_FORM_RENAME_TEXT", "Rename this form");
define("_FORM_EDIT_ELEMENTS_TEXT", "Edit this form's elements");
define("_FORM_EDIT_SETTINGS_TEXT", "Edit this form's settings");
define("_FORM_CLONE_TEXT", "Clone this form");
define("_FORM_CLONEDATA_TEXT", "Clone this form and its data");
define("_FORM_DELETE_TEXT", "Delete this form");
define("_FORM_LOCK_TEXT", "Lockdown this form");
define("_FORM_LOCK", "Prevent anyone from editing this form again");
define("_AM_CONFIRM_LOCK", "If you lockdown this form, then no one, not even you, will be able to make any changes to the form or its elements.  Are you sure you want to lockdown this form?");
define("_formulize_FORMLOCK", "This form has now been locked.  No further changes to this form will be possible.");
define("_formulize_FORMLOCK_FAILED", "There was an error and Formulize could not lock this form.");
define("_FORM_NUM_ENTRIES_ANON_HELP", "<b>About Anonymous Users:</b> Formulize determines entry ownership based on a user's id number, and all Anonymous Users are viewed as \"User Number 0\".  Therefore, \"One entry per user\" does not really work for anonymous users in most cases, since everyone who is not logged in will share the same entry, because they all share the same id number, 0.<br><br>Also, \"More than one entry per user\" will behave differently for Anonymous Users, since all the entries created by anyone who isn't logged in, will all belong to \"user 0\", so everyone who is not logged in, will be treated as the same person.<br><br>Formulize is designed primarily for use in a website with a strict set of usernames and groups of users, but there are ways around these issues, in some cases using the API.  Post to the <a href=\"http://www.freeformsolutions.ca/en/forums\">support forums</a> for more information.");  

define("_AM_FORCE_GROUPSCOPE_HELP", "If view_groupscope is selected, you can pick specific groups it should apply to.  If none are selected, then when a list of entries is shown to a user, groupscope will apply to the groups they are a member of, which also have view_form permission.");
define("_AM_FORCE_GROUPSCOPE_INTRO", "Use specific groups for groupscope?");

define("_AM_PER_GROUP_FILTER_INTRO", "Filter the entries this group can see in this form?");
       
define("_AM_SAVE","Save");
define("_AM_COPIED","%s copy");
define("_AM_DBUPDATED","Database Updated Successfully!");
define("_AM_ELE_CREATE","Create form elements");
define("_AM_ELE_EDIT","Edit form element: %s");
define("_AM_FORM","Form : ");
define("_AM_REQ","Results of the form module : ");
define("_AM_SEPAR",'{SEPAR}');
define("_AM_ELE_FORM","Form elements");
define("_AM_PARA_FORM","Form parameters");

define("_AM_ELE_CAPTION","Caption");
define("_AM_ELE_CAPTION_DESC","<br /></b>{SEPAR} permit you to not display the element name");
define("_AM_ELE_DEFAULT","Default value");
define("_AM_ELE_LEFTRIGHT_TEXT","Contents of the right side");
define("_AM_ELE_LEFTRIGHT_DESC","Any text or HTML code that you type here will appear on the right beside the caption.  You can use PHP code instead of text or HTML, just make sure it contains '&#36;value = &#36;something;' and Formulize will read this text as PHP code.");
define("_AM_ELE_DESC","Descriptive text");
define("_AM_ELE_DESC_HELP","Whatever you type in this box will appear below the caption, just like this text does.");
define("_AM_ELE_COLHEAD","Column Heading (optional)");
define("_AM_ELE_COLHEAD_HELP","If you specify a column heading, then this text will be used instead of the caption, on the <b>List of Entries</b> screen.  This is useful if the caption is very long, or if you want the captions written from a user point of view, and the column headings written from a report-consumer point of view.");
define("_AM_ELE_HANDLE","Data handle (optional)");
define("_AM_ELE_HANDLE_HELP","You can specify a short name for this element.  The short name will be used by the database when storing information.  If you leave this blank, the element ID number will be used.");
define("_AM_ELE_DETAIL","Detail");
define("_AM_ELE_REQ","Required");
define("_AM_ELE_ORDER","Order");
define("_AM_ELE_DISPLAY","Display this element to these groups");
//define("_AM_ELE_DISPLAYLIST","Display this element to these groups, in the list of entries");
define("_AM_ELE_ELEMENTCONDITIONS","Only include this element in the form if the entry being edited meets these conditions:");
define("_AM_ELE_PRIVATE","Private");
define("_AM_ELE_HANDLE_HEADING","Data handle/ID");
define("_AM_ELE_TYPE_HEADING","Type");
define("_AM_ELE_DISPLAY_HEADING","Display");




define("_AM_ELE_TEXT","Text box");
define("_AM_ELE_TEXT_DESC","{NAME} will print full name;<br />{UNAME} will print user name;<br />{EMAIL} will print user email;<br />{ID} will cause the entry ID number of the entry to be inserted into the textbox, when the entry is first saved.<br />{SEQUENCE} will cause the values in the box to be a series of consecutive numbers.<br />PHP Code (ending with the line '&#36;default = &#36;something;') will be interpreted to generate the default value.");
define("_AM_ELE_TEXT_DESC2","<br />PHP Code is the only situation where more than one line of this box will be read.  In your PHP code, you can use \$form_id to get the ID number of the form, and \$entry_id to get the ID number of the particular entry that the user is editing.");
define("_AM_ELE_TAREA","Text area");
define("_AM_ELE_MODIF","Text for display (left and right cells)");
define("_AM_ELE_MODIF_ONE","Text for display (spanning both cells)");
define("_AM_ELE_INSERTBREAK","HTML content for this line:");
define("_AM_ELE_IB_DESC","The caption will not display.  Only the text in this box will appear on screen, in a single row spanning both columns of the form.");
define("_AM_ELE_IB_CLASS","CSS class for the row:");
define("_AM_ELE_SELECT","Select box");
define("_AM_ELE_CHECK","Check boxes");
define("_AM_ELE_RADIO","Radio buttons");
define("_AM_ELE_RANKORDERLIST","Ranked list of options (ie: order from highest to lowest)");
define("_AM_ELE_YN","Simple yes/no radio buttons");
define("_AM_ELE_DATE","Date");
define("_AM_ELE_REQ_USELESS","Not usable for select box, check boxes nor radio buttons");
define("_AM_ELE_SEP","Break up line");
define("_AM_ELE_NOM_SEP","Break up name");
define("_AM_ELE_UPLOAD","Join a file");
define("_AM_ELE_CLR","with the color");
define("_AM_ELE_PLACEHOLDER_DESC","How do you want to use the default value?");
define("_AM_ELE_NO_PLACEHOLDER","Add it to the text box when the form loads, it will be saved as-is if the user leaves it alone");
define("_AM_ELE_PLACEHOLDER_OPTION","Show it as an example in the text box, but don't save it if the form is submitted");

// number options for textboxes
define("_AM_ELE_NUMBER_OPTS","If a number is typed...");
define("_AM_ELE_NUMBER_OPTS_DESC","You can set these options to control how decimals are handled, and how numbers are formatted on screen.");
define("_AM_ELE_NUMBER_OPTS_DEC","Number of decimal places:");
define("_AM_ELE_NUMBER_OPTS_PREFIX","Display numbers with this prefix (ie: '$'):");
define("_AM_ELE_NUMBER_OPTS_DECSEP","Separate decimals with this character (ie: '.'):");
define("_AM_ELE_NUMBER_OPTS_SUFFIX","Display numbers with this suffix (ie: '%'):");
define("_AM_ELE_NUMBER_OPTS_SEP","Separate thousands with this character (ie: ','):");
define("_AM_ELE_DERIVED_NUMBER_OPTS","If this formula produces a number ...");

// require unique option for textboxes
define("_AM_ELE_REQUIREUNIQUE", "Users must enter a unique value into this box (no duplicates allowed)");

// added - start - August 227 2005 - jpc
define("_AM_ELE_TYPE","What should people type in this box?");
define("_AM_ELE_TYPE_DESC","Choose 'Numbers Only' to strip non-numeric characters from the box when an entry is saved.  This ensures mathematical operations can be performed on the contents of the box.");
define("_AM_ELE_TYPE_STRING","Anything");
define("_AM_ELE_TYPE_NUMBER","Numbers Only");
// added - end - August 22 2005 - jpc


define("_AM_ELE_SIZE","Size");
define("_AM_ELE_MAX_LENGTH","Maximum length");
define("_AM_ELE_ROWS","Rows");
define("_AM_ELE_COLS","Columns");
define("_AM_ELE_USERICHTEXT","Display this element using a Rich Text Editor");
define("_AM_ELE_RICHTEXT_DESC","This option provides a full editor interface in the textbox, with font sizes and bold, etc, instead of just a simple box. You can control which editor is used on <a href='../../system/admin.php?fct=preferences&op=show&confcat_id=1' target='_blank'>the General Settings page</a>");
define("_AM_ELE_OPT","Options");
define("_AM_ELE_OPT_DESC","Setting a single option of '{FULLNAMES}' or '{USERNAMES}' will produce a list of users based on the group limits set below.<br /><br />Tick the check boxes for selecting default values");
define("_AM_ELE_OPT_DESC_CHECKBOXES","Tick the check boxes for selecting default values<br>Boxes with no text in them will be ignored when you click <i>Save</i>");
define("_AM_ELE_OPT_DESC_RANKORDERLISTS","Boxes with no text in them will be ignored when you click <i>Save</i>");
define("_AM_ELE_OPT_DESC1","<br />Only the first check is used if multiple selection is not allowed");
define("_AM_ELE_OPT_DESC2","Select the default value by checking the radio buttons<br>Boxes with no text in them will be ignored when you click <i>Save</i>");
define("_AM_ELE_OPT_UITEXT", "The text visible to the user can be different from what is stored in the database.  This is useful if you want to have numbers saved in the database, but text visible to the user so they can make their selection.  To do this, use the \"pipe\" character (usually above the Enter key) like this:  \"10|It has been 10 days since I visited this website\"");
define("_AM_ELE_ADD_OPT","Add %s options");
define("_AM_ELE_ADD_OPT_SUBMIT","Add");
define("_AM_ELE_OPT_CHANGEUSERVALUES", "When saving changes to these options, also change the values users have made in the form to match the new options (ie: where users had selected the old first option, replace their selection with the new first option instead)");
define("_AM_ELE_SELECTED","Selected");
define("_AM_ELE_CHECKED","Checked");
define("_AM_ELE_MULTIPLE","Allow multiple selections");
define("_AM_ELE_TYPE","Display the break up in");
define("_AM_ELE_GRAS","Gras");
define("_AM_ELE_RGE","Red");
define("_AM_ELE_CTRE","Center");
define("_AM_ELE_ITALIQ","Italic");
define("_AM_ELE_SOUL","Underlined");
define("_AM_ELE_BLEU","Blue");
define("_AM_ELE_FICH",'File');
define("_AM_ELE_TAILLEFICH","Max size on the file");
define("_AM_ELE_PDS","poids");
define("_AM_ELE_TYPE",'Allowed types');
define("_AM_ELE_DELIM_CHOICE",'Delimiter between each option');
define("_MI_formulize_DELIMETER_SPACE","White space");
define("_MI_formulize_DELIMETER_BR","Line break");
define("_MI_formulize_DELIMETER_CUSTOM","Custom HTML");

//added to handle the formlink part of the selectbox element -- jwe 7/29/04
define("_AM_ELE_FORMLINK", "Options linked from another form");
define("_AM_ELE_FORMLINK_DESC","Select a field in another form and use those entries for the options in this Select Box. (This setting overrides any options specified above.)");
define("_AM_FORMLINK_NONE", "No link -- options below are in effect");
define("_AM_ELE_FORMLINK_TEXTBOX", "Associate values with another form element");
define("_AM_ELE_FORMLINK_DESC_TEXTBOX","If you select another form element here, then text that users type into this element will be compared with values entered in the other element.  If a match is found then the text users type into this element will be clickable in the \"List of Entries\" screen, and will take users to the matching entry in the other form.");
define("_AM_FORMLINK_NONE_TEXTBOX", "No association in effect");
define("_AM_ELE_FORMLINK_SCOPE", "If the options are linked -- or are {FULLNAMES} or {USERNAMES} -- limit them to values from the groups selected here.");
define("_AM_ELE_FORMLINK_SCOPE_DESC", "<p>The groups you pick define the total possible options to be used.  Optionally, you can choose to have the current user's group memberships further limit the options.  In that case, groups you select will be ignored if the current user is not also a member of the group.</p><p>Also, you can specify whether entries must be created by users who are members of all the groups, or just any one group.  Note that this option can interact powerfully with \"Use only groups that the current user is also a member of\", to let you limit the options to ones from entries created by users who are members of <b>all</b> the same groups as the current user.</p><p><b>Exception:</b> If you limit to only the groups the user is a member of, and you also require users to be members of all selected groups, the system will ignore any groups that do not contain at least one user who has at least one of these groups in common with the current user.  This is to meant to support situations where you have sets of groups in parallel, and you want the interpretation to be based on only the current user's set of groups, ie: East Coast Staff and East Coast Volunteers vs. West Coast Staff and West Coast Volunteers.  If the current user belongs to the East Coast set, then all West Coast groups will be ignored in this case.</p>");
define("_AM_ELE_FORMLINK_SCOPE_ALL", "Use all groups");
define("_AM_ELE_FORMLINK_SCOPELIMIT_NO", "Use all these groups");
define("_AM_ELE_FORMLINK_SCOPELIMIT_YES", "Use only groups that the current user is also a member of");
define("_AM_ELE_FORMLINK_ANYALL_ANY", "Include entries by users who are members of any selected group");
define("_AM_ELE_FORMLINK_ANYALL_ALL", "Include entries by users who are members of all selected groups");

// formlink scope filters -- feb 6 2008
define("_AM_ELE_FORMLINK_SCOPEFILTER", "If the options are linked -- or are {FULLNAMES} OR {USERNAMES} -- filter them based on these properties of their entry in the source form.");
define("_AM_ELE_FORMLINK_SCOPEFILTER_DESC", "When you link to values in another form, you may wish to limit the values included in the list based on certain properties of the entries in the other form.  For example, if you are linking to the names of tasks in a task form, you might want to list only tasks that are incomplete.  If there's a question in the task form that asks if the task is complete, you could specify a filter like: \"Task is complete = No\".<br><br>You can use {datahandle} to refer to the value of an element in the current entry.  Just use the correct data handle surrounded by curly brackets.<br><br>You can also use { } values to refer to values in the URL, ie: if you use {checkpoint} and the URL contains \"&checkpoint=Start\" then the value \"Start\" will be used.  <b>Note that the URL is checked first for { } matches, and then the data handles, so a URL property with the same name as a data handle may cause problems for you!</b><br><br>If the options are {FULLNAMES} or {USERNAMES}, and you are using a custom profile form in conjunction with the Registration Codes module, you can filter the names based on the profile form.");
define("_AM_ELE_FORMLINK_SCOPEFILTER_ALL", "No filter in effect (select this to clear existing filters).");
define("_AM_ELE_FORMLINK_SCOPEFILTER_CON", "Filter the options based on this/these conditions:");
define("_AM_ELE_FORMLINK_SCOPEFILTER_ADDCON", "Add another condition");
define("_AM_ELE_FORMLINK_SCOPEFILTER_REFRESHHINT", "(If the first list here is empty, click the 'Add another condition' button to refresh it.)");
       
       
  

// subforms
define("_AM_ELE_SUBFORM_FORM", "Which form do you want to include as a subform?");
define("_AM_ELE_SUBFORM_IFFORM", "If the subform entries are shown in a full form:");
define("_AM_ELE_SUBFORM_SCREEN", "Which screen should be used to display each entry?");
define("_AM_ELE_SUBFORM", "Subform (from a form framework)");
define("_AM_ELE_SUBFORM_DESC", "When you display the current form as part of a framework, the subform interface can be included in the form.  The subform interface allows users to create and modify entries in a related subform without leaving the main form.  The list here shows all the possible subforms from all frameworks that this form is part of.");
define("_AM_ELE_SUBFORM_NONE", "No subforms available - define a framework first");
define("_AM_ELE_SUBFORM_ELEMENTS", "Element options");
define("_AM_ELE_SUBFORM_ELEMENT_LIST", "Choose the elements to show in the row, or to use as the heading if you're showing the full form");
define("_AM_ELE_SUBFORM_ELEMENTS_DESC", "When displayed in a row, about three or four elements from the subform can be displayed comfortably as part of the main form.  More than four elements starts to make the interface cluttered.  You can choose which elements you want to display by selecting them from this list.  Users can always modify all elements by clicking a button next to each subform entry that it listed in the main form. <b>You do not need to choose the element that joins the subform to the mainform; Formulize will automatically populate that element with the correct values for you.</b>");
define("_AM_ELE_SUBFORM_VIEW", "Include <i>View</i> buttons beside each entry?");
define("_AM_ELE_SUBFORM_VIEW_DESC", "The <i>View</i> buttons let users click through to the complete entry in the subform.  This may be useful when only some elements in the subform are visible in the main interface.");
define("_AM_ELE_SUBFORM_REFRESH", "Refresh elements list to match selected form");
define("_AM_ELE_SUBFORM_IFROW", "If the subform entries are shown as a row:");
define("_AM_ELE_SUBFORM_HEADINGSORCAPTIONS", "Should each element be labeled with its column heading or caption?");
define("_AM_ELE_SUBFORM_HEADINGSORCAPTIONS_HEADINGS", "Column heading (captions will be used for elements with no column heading)");
define("_AM_ELE_SUBFORM_HEADINGSORCAPTIONS_CAPTIONS", "Caption");
define("_AM_ELE_SUBFORM_BLANKS", "How many blank spaces should be shown for this subform when the page first loads?");
define("_AM_ELE_SUBFORM_BLANKS_HELP", "Note: if you have more than one blank space, do not use file upload elements in your subform.  File upload elements only work effectively with one blank subform row at a time.");
define("_AM_ELE_SUBFORM_UITYPE_ROW", "Display each subform entry as a row with only the elements selected below showing");
define("_AM_ELE_SUBFORM_UITYPE_FORM", "Display each subform entry using the full form, inside a collapsable area that the user can open and close");
define("_AM_ELE_SUBFORM_ADD_NONE", "No");
define("_AM_ELE_SUBFORM_ADD_SUBFORM", "Yes, only if the user can add entries in the subform");
define("_AM_ELE_SUBFORM_ADD_PARENT", "Yes, only if the user can add entries in the main form");


// grids
define("_AM_ELE_GRID", "Table of existing elements (place BEFORE the elements it contains)");
define("_AM_ELE_GRID_HEADING", "What text should appear as the heading for this table?");
define("_AM_ELE_GRID_HEADING_USE_CAPTION", "The caption typed above");
define("_AM_ELE_GRID_HEADING_USE_FORM", "The title of this form");
define("_AM_ELE_GRID_HEADING_NONE", "No heading");
define("_AM_ELE_GRID_HEADING_SIDEORTOP", "If there is a heading, where should it appear?");
define("_AM_ELE_GRID_HEADING_SIDE", "Heading should be at the side like a regular element");
define("_AM_ELE_GRID_HEADING_TOP", "Heading should be above the grid, and the grid will span both columns of the form");
define("_AM_ELE_GRID_ROW_CAPTIONS", "Enter the captions for the rows of this table");
define("_AM_ELE_GRID_ROW_CAPTIONS_DESC", "Each table is a grid of colums and rows.  The left side of the table has one caption in each cell to start each row.  Type in the text you want to use for the captions, separated by commas.  If your captions are long, it may work best visually to put each caption on its own line.");
define("_AM_ELE_GRID_COL_CAPTIONS", "Enter the captions for the columns of this table");
define("_AM_ELE_GRID_COL_CAPTIONS_DESC", "Each table is a grid of colums and rows.  The top side of the table has one caption in each cell to head each column.  Type in the text you want to use for the captions, separated by commas.  If your captions are long, it may work best visually to put each caption on its own line.");
define("_AM_ELE_GRID_BACKGROUND", "Background shading");
define("_AM_ELE_GRID_BACKGROUND_HOR", "Alternate the shading of each row in the table");
define("_AM_ELE_GRID_BACKGROUND_VER", "Alternate the shading of each column in the table");
define("_AM_ELE_GRID_START", "Choose the first element, which will appear in the upper left corner of the table");
define("_AM_ELE_GRID_START_DESC", "Each table will have a number of elements in it, equal to the rows times the columns.  ie: if you have three rows and four columns, you will have 12 elements in your table.  The first element appears in the upper left corner, and the next element after that appears in the next cell to the right.  Once the end of a row has been reached, the next element appears in the first cell of the next row.  Elements are drawn from the form according to the order currently assigned to them; if you have 12 elements in your table, then the next 11 elements after the first element will be used in your table.  Therefore, make sure all the elements you want to use in tables are consecutively ordered in your form.");

// derived columns
define("_AM_ELE_DERIVED", "Value derived from other elements");
define("_AM_ELE_DERIVED_CAP", "Formula for generating values in this element");
define("_AM_ELE_DERIVED_DESC", "Select an element above to add it to your formula.  You can also use element ID numbers or Framework handles in your formula, as long as they are inside double quotes.  The formula can have multiple lines, or steps, and you can use PHP code in the formula.  The last line should be of the format <i>\$value = \$something</i> where \$something is the final number or formula that you want use.<br /><br />Example:<br />\$value = \"Number of hits\" / \"Total shots\" * 100<br /><br />Note: only use double quotes (\") to refer to a field.  If you need to use quotes in a line of PHP code, use single quotes (').");
define("_AM_ELE_DERIVED_ADD", "Add to Formula");
define("_AM_ELE_DERIVED_DONE","Finished updating values!");
define("_AM_ELE_DERIVED_UPDATE", "Update Derived Values");
define("_AM_ELE_DERIVED_UPDATE_CAP", "Calculate values for this element");
define("_AM_ELE_DERIVED_UPDATE_DESC", "This may take a while depending on how many records are contained within your form.");

define("_AM_ELE_SELECT_NONE","No element selected.");
define("_AM_ELE_CONFIRM_DELETE","Are you sure you want to delete this form element?<br>All data anyone has ever entered into this form element will be deleted as well.");

define("_AM_TITLE","Menu administration");
define("_AM_ID","ID");
define("_AM_POS","Position");
define("_AM_POS_SHORT","Pos.");
define("_AM_INDENT","Left indent");
define("_AM_INDENT_SHORT","Ind.");
define("_AM_ITEMNAME","Name");
define("_AM_ITEMURL","URL");
define("_AM_STATUS","Status");
define("_AM_FUNCTION","Function");
define("_AM_ACTIVE","active");
define("_AM_INACTIVE","inactive");
define("_AM_BOLD","bold");
define("_AM_NORMAL","normal");
define("_AM_MARGINBOTTOM","Bottom margin");
define("_AM_MARGIN_BOTTOMSHORT","mrg. bott.");
define("_AM_MARGINTOP","Top margin");
define("_AM_MARGIN_TOPSHORT","mrg. top");
define("_AM_EDIT","Edit");
define("_AM_DELETE","Delete");
define("_AM_ADDMENUITEM","Add menu item");
define("_AM_CHANGEMENUITEM","Modify menu item");
define("_AM_SITENAMET","Site Name:");
define("_AM_URLT","URL:");
define("_AM_FONT","Font:");
define("_AM_STATUST","Status:");
define("_AM_MEMBERSONLY","Authorized users");
define("_AM_MEMBERSONLY_SHORT","Reg.<br>only");
define("_AM_MEMBERS","members only");
define("_AM_ALL","all users");
define("_AM_ADD","Add");
define("_AM_EDITMENUITEM","Edit menu item");
define("_AM_DELETEMENUITEM","Delete menu item");
define("_AM_SAVECHANG","Save changes");
define("_AM_WANTDEL","Do you really want to delete this menu item?");
define("_AM_YES","Yes");
define("_AM_NO","No");
define("_AM_formulizeMENUSTYLE","MyMenu-Style");
define("_AM_MAINMENUSTYLE","MainMenu-Style");

define("_AM_VERSION","1.0");
define("_AM_REORD","New sort");
define("_AM_SAVE_CHANGES","Save Changes");

define("_formulize_CAPTION_MATCH", "The caption you entered is already in use. A '2' has been appended to it.");
define("_formulize_CAPTION_QUOTES", "Captions cannot have quotes. They have been removed.");
define("_formulize_CAPTION_SLASH", "Captions cannot have backslashes. They have been removed.");
define("_formulize_CAPTION_LT", "Captions cannot have < signs. They have been removed.");
define("_formulize_CAPTION_GT", "Captions cannot have > signs. They have been removed.");

define("_AM_VIEW_FORM", "View this form");
define("_AM_GOTO_PARAMS", "Edit the form's settings");
define("_AM_PARAMS_EXTRA", "(Specify what elements appear<br>on the <i>View Entries</i> page)");
define("_AM_GOTO_MAIN", "Return to main page");
define("_AM_GOTO_MODFRAME", "Back to first<br>Frameworks page");

define("_AM_CLEAR_DEFAULT", "Clear Default");

define("_AM_SAVING_CHANGES", "Saving Changes");
define("_AM_EDIT_ELEMENTS", "Edit the form's elements");

define("_AM_CONFIRM_DELCAT", "You are about to delete a Menu Category!  Please confirm.");
define("_AM_MENUCATEGORIES", "Menu Categories");
define("_AM_MENUCATNAME", "Name:");
define("_AM_MENUSAVEADD", "Add/Save");
define("_AM_MENUNOCATS", "No Categories");
define("_AM_MENUEDIT", "Edit");
define("_AM_MENUDEL", "Delete");
define("_AM_MENUCATLIST", "Categories:");
define("_AM_CATSHORT", "Category");
define("_AM_CATGENERAL", "General Forms");

define("_AM_CANCEL", "Cancel");

define("_AM_CONVERTTEXT", "Convert to text area");
define("_AM_CONVERTTEXTAREA", "Convert to text box");
define("_AM_CONVERTRADIO", "Convert to check boxes");
define("_AM_CONVERTCHECKBOX", "Convert to radio buttons");
define("_AM_CONVERTTEXT_HELP", "Convert this text box to a multi-line text area box");
define("_AM_CONVERTTEXTAREA_HELP", "Convert this text area box to a single-line text box");
define("_AM_CONVERTRADIO_HELP", "Convert these radio buttons to check boxes");
define("_AM_CONVERTCHECKBOX_HELP", "Convert these check boxes to radio buttons");
define("_AM_ELE_CANNOT_CONVERT", "There are no conversion options for this type of element");
define("_AM_CONVERTTEXT_CONFIRM", "Do you want to convert this text box to a multi-line text area box?");
define("_AM_CONVERTTEXTAREA_CONFIRM", "Do you want to convert this text area box to a single-line text box?");
define("_AM_CONVERTRADIO_CONFIRM", "Do you want to convert these radio buttons to check boxes?");
define("_AM_CONVERTCHECKBOX_CONFIRM", "Do you want to convert these check boxes to radio buttons?");
define("_AM_ELE_CONVERTED_TO_TEXTBOX", "This multi-line textbox has been converted to a single-line textbox.");
define("_AM_ELE_CONVERTED_TO_TEXTAREA", "This single-line textbox has been converted to a multi-line textbox.");
define("_AM_ELE_CONVERTED_TO_RADIO", "These check boxes have been coverted to radio buttons.");
define("_AM_ELE_CONVERTED_TO_CHECKBOX", "These radio buttons have been converted to check boxes.");
define("_AM_ELE_CHECKBOX_DATA_NOT_READY", "These radio buttons were converted, but the data people have submitted was not updated for use in the check boxes.  Contact <a href=\"mailto:support@freeformsolutions.ca\">support@freeformsolutions.ca</a> for assistance.");
define("_AM_ELE_RADIO_DATA_NOT_READY", "These check boxes were converted, but the data people have submitted was not updated for use in the radio buttons.  Contact <a href=\"mailto:support@freeformsolutions.ca\">support@freeformsolutions.ca</a> for assistance.");


// added - start - August 25 2005 - jpc
define("_AM_FORM_DISPLAY_MULTIPLE","Custom");
// added - end - August 25 2005 - jpc
define("_AM_FORM_DISPLAY_EXTRA", "Use this list to display certain elements to only certain groups when the form is shown.  Meant for situations where users in different groups should see different parts of the same form.  Normally, you can leave this on 'All groups'.");
//define("_AM_FORM_DISPLAYLIST_EXTRA", "Use this list to display certain elements to only certain groups when the list of entries is shown.  Meant for situations where users in different groups should see different data from the same form.  Normally, you can leave this on 'All groups'.");
define("_AM_FORM_DISPLAY_ALLGROUPS", "All groups with permission for this form");
define("_AM_FORM_DISPLAY_NOGROUPS", "No groups");
define("_AM_FORM_FORCEHIDDEN", "Include as a hidden element for users who cannot see it");
define("_AM_FORM_FORCEHIDDEN_DESC", "Currently only affects radio buttons and textboxes.  This option will cause a hidden form element to be created instead of the radio button series or textbox, and the value of the hidden element will be the default value specified above.  Useful when you always need a default value set in every form entry, but not all groups normally see this element.");

define("_AM_ELE_DISABLED", "Disable this element for any groups?");
define("_AM_FORM_DISABLED_EXTRA", "Use this option to make this element inactive for certain groups.  The element will still be shown to users according to the display option above, but you can use this option to disable the element so users cannot change its value.  This option currently works only for textboxes and textarea boxes.");
define("_AM_FORM_DISABLED_ALLGROUPS", "Disable for all groups");
define("_AM_FORM_DISABLED_NOGROUPS", "Disable for no groups");


define("_AM_ELE_OTHER", 'For an option of "Other", put {OTHER|*number*} in one of the text boxes. e.g. {OTHER|30} generates a text box with 30 chars width.');

define("_AM_FORM_PRIVATE", "The information that users enter in this element is private");
define("_AM_FORM_PRIVATE_DESC", "If this box is checked, the information that users enter in this element will only be visible to other users who have the view_private_elements permission.  This option is useful for making personal information only available to the appropriate managers.");

define("_AM_FORM_ENCRYPT","Encrypt this information");
define("_AM_FORM_ENCRYPT_DESC", "If this is very sensitive information, Formulize can encrypt it before it is stored in the database.  Most features of Formulize work with encrypted information, but some unusual operations, like grouping calculation results by an encrypted field, don't work.<br><br>Also, encrypting information does slow down the form a bit.<br><br>The encryption is dependent on the current database password; if you change the database password, Formulize will not be able to read any information that was saved previously!");

//added by felix <INBOX International> for sedonde (colorpicker feature)
define("_AM_ELE_COLORPICK","Colorpicker");

//datatype controls
define("_AM_FORM_DATATYPE_CONTROLS","How should the data for this element by stored in the database?");
define("_AM_FORM_DATATYPE_CONTROLS_DESC","<b>Elements that will only contain numbers should use a numeric type, so that sorting and calculations work optimally.</b><br><br>This is an advanced option that you can use to control the MySQL datatype that is used in the underlying database field for this element.  The value in ( ) shows which datatype will be used.<br><br>If you don't know what all this means, then just accept the defaults.  Formulize can intelligently select appropriate values for regular text boxes based on the 'numbers only' setting, and the number formatting options.");
define("_AM_FORM_DATATYPE_OTHER","Continue using this datatype: ");
define("_AM_FORM_DATATYPE_TEXT","It doesn't matter (text)");
define("_AM_FORM_DATATYPE_TEXT_NEWTEXT","Let Formulize figure it out, based on the 'numbers only' setting, and the number formatting options");
define("_AM_FORM_DATATYPE_INT","Store as a number with <b>no</b> decimal places (int)");
define("_AM_FORM_DATATYPE_DECIMAL1","Store as a number with ");
define("_AM_FORM_DATATYPE_DECIMAL2"," decimal places (decimal)");
define("_AM_FORM_DATATYPE_VARCHAR1","Store as text, up to a maximum of ");
define("_AM_FORM_DATATYPE_VARCHAR2"," characters (varchar)");
define("_AM_FORM_DATATYPE_CHAR1","Store as text, exactly ");
define("_AM_FORM_DATATYPE_CHAR2"," characters in length (char)");


// SCREENS...including multipage

define("_AM_FORMULIZE_SCREEN_TYPE", "Type: ");

define("_AM_FORMULIZE_DEFINED_SCREENS", "Defined Screens for This Form");
define("_AM_FORMULIZE_DELETE_SCREEN", "Delete");
define("_AM_FORMULIZE_ADD_NEW_SCREEN_OF_TYPE", "Add a new screen of this type:");
define("_AM_FORMULIZE_SCREENTYPE_MULTIPAGE", "Multi Page Version of Form");
define("_AM_FORMULIZE_SCREENTYPE_LISTOFENTRIES", "List of Entries in this Form");
define("_AM_FORMULIZE_ADD_SCREEN_NOW", "Add it Now!");
define("_AM_FORMULIZE_SCREEN_FORM", "Create or Modify a Screen");
define("_AM_FORMULIZE_SCREEN_TITLE", "Title of this screen");
define("_AM_FORMULIZE_USE_NO_FRAMEWORK", "Use this form only, no Framework");
define("_AM_FORMULIZE_SELECT_FRAMEWORK", "Framework to use on this screen, if any");
define("_AM_FORMULIZE_SCREEN_SECURITY", "Use the XOOPS security token on this screen?");
define("_AM_FORMULIZE_SCREEN_SECURITY_DESC", "The XOOPS security token is a defense against cross-site scripting attacks.  However, it can cause problems if you are using an advanced Ajax-based UI in a List of Entries screen, and possibly other screen types.");


define("_AM_FORMULIZE_SCREEN_PARAENTRYFORM", "Should answers from a previous entry be shown as part of this form?  If so, choose the form.");
define("_AM_FORMULIZE_SCREEN_PARAENTRYFORM_FALSE", "No, don't show previous answers.");
define("_AM_FORMULIZE_SCREEN_PARAENTRYRELATIONSHIP", "If previous answers are shown, what is the relationship of this form to the other form with the previous entries?");
define("_AM_FORMULIZE_SCREEN_PARAENTRYREL_BYGROUP", "Entries belong to the same group");

define("_AM_FORMULIZE_SCREEN_INTRO", "Introductory text for the first page of this form");
define("_AM_FORMULIZE_SCREEN_THANKS", "Thank-you text for the final page of this form");
define("_AM_FORMULIZE_SCREEN_MULTIPAGE_TEMPLATES", "Templates for Multi page form:");
define("_AM_FORMULIZE_SCREEN_MULTIPAGE_TEMPLATES_HELP", "<p>The following variables can be used in these templates to render certain text or UI elements:</p>
<ul>
<li>\$currentPage</li>
<li>\$totalPages</li>
<li>\$nextPageButton</li>
<li>\$previousPageButton</li>
<li>\$savePageButton &mdash; does not change the current page when clicked</li>
<li>\$pageSelectionList &mdash; draws in the dropdown list for jumping to another page</li>
<li>\$skippedPagesMessage &mdash; the message saying one or more pages were skipped, it will be an empty string is no pages were skipped</li>
</ul>
<p>In addition, in the element template, the following variables are also available.  The element template will be used multiple times, once for each element on the page.  In each case, these variables will refer to the element that is currently being rendered.</p>
<ul>
<li>\$elementCaption &mdash; the actual question text</li>
<li>\$elementDescription &mdash; any help or descriptive text for the question</li>
<li>\$elementMarkup &mdash; the HTML for rendering the question on the page</li>
<li>\$element_id &mdash; the ID number of the element that is being rendered</li>
<li>\$elementObjectForRendering &mdash; the object that has been prepared, based on the element settings, and has been used to generate the markup.  This is not the same as the Element Object in the Formulize API, which simply contains all those settings.  Use this object if you need to get the caption, as prepared in the actual element as it is rendered, if, for example, you are using a custom element that changes the caption text at render time.</li>
</ul>");
define("_AM_FORMULIZE_SCREEN_MULTIPAGE_TEMPLATES_INTRO", "Leave these blank to use the default layout.  Currently, not all features are supported for rendering elements in custom templates.  Element conditions will be ignored, as well as elements being required or any other validation conditions.");
define("_AM_FORMULIZE_SCREEN_TOPTEMPLATE", "Template for the top part of the page, above the form");
define("_AM_FORMULIZE_SCREEN_ELEMENTTEMPLATE", "This template will be used once for drawing each element on the page");
define("_AM_FORMULIZE_SCREEN_BOTTOMTEMPLATE", "Template for the bottom part of the page, below the form");
define("_AM_FORMULIZE_SCREEN_FINISHISDONE", "The final page of the form should be...");
define("_AM_FORMULIZE_SCREEN_FINISHISDONE_THANKSPAGE", "<b>The Thank-you page</b>, which the user gets after clicking the \"Save and Finish\" button on the last page with questions");
define("_AM_FORMULIZE_SCREEN_FINISHISDONE_FINISHBUTTON", "<b>The last page with questions</b>, and when the user clicks the \"Save and Finish\" button, they leave the form");
define("_AM_FORMULIZE_SCREEN_DONEDEST", "The URL that users go to when leaving the form (Optional, Formulize will usually set this automatically when the form is displayed, based on where the user came from.  If the last page of the form has questions, then the next page the users go to should be a Formulize page or else the answers to the questions won't be saved)");
define("_AM_FORMULIZE_SCREEN_BUTTONTEXT", "If the Thank-you page is shown, what should be used as the clickable text for the URL?");
define("_AM_FORMULIZE_SCREEN_PRINTALL", "Make the 'Printable View - All Pages' button available at the end of the form"); //nmc 2007.03.24
define("_AM_FORMULIZE_SCREEN_PRINTALL_Y", "Yes"); //nmc 2007.03.24
define("_AM_FORMULIZE_SCREEN_PRINTALL_N", "No"); //nmc 2007.03.24
define("_AM_FORMULIZE_SCREEN_PRINTALL_NONE", "No, and not the regular 'Printable View' button either");
define("_AM_FORMULIZE_DELETE_THIS_PAGE", "Delete this page");
define("_AM_FORMULIZE_CONFIRM_SCREEN_DELETE", "Are you sure you want to delete this screen?  Please confirm!");
define("_AM_FORMULIZE_CONFIRM_SCREEN_DELETE_PAGE", "Are you sure you want to delete this page?  Please confirm!");
define("_AM_FORMULIZE_SCREEN_A_PAGE", "Form elements to display on page");
define("_AM_FORMULIZE_SCREEN_ADDPAGE", "Add another page");
define("_AM_FORMULIZE_SCREEN_INSERTPAGE", "Insert a new page here");
define("_AM_FORMULIZE_SCREEN_SAVE", "Save this screen");
define("_AM_FORMULIZE_SCREEN_SAVED", "The details for this screen have been saved in the database");
define("_AM_FORMULIZE_SCREEN_PAGETITLE", "Title for page number");
define("_AM_FORMULIZE_SCREEN_CONS_PAGE", "Conditions in which to display page");
define("_AM_FORMULIZE_SCREEN_CONS_NONE", "Always display this page");
define("_AM_FORMULIZE_SCREEN_CONS_YES", "Only display when the following conditions are true:");
define("_AM_FORMULIZE_SCREEN_CONS_ADDCON", "Add an another condition");
define("_AM_FORMULIZE_SCREEN_CONS_HELP", "Conditions are useful if a page should only appear based on answers to questions in a previous page.  Select the questions from the previous page and specify the answers that should result in this page being displayed.");

// LIST OF ENTRIES SCREEN
define("_AM_FORMULIZE_SCREEN_LOE_BUTTONINTRO", "Specify which buttons you want included on this screen:");
define("_AM_FORMULIZE_SCREEN_LOE_BUTTON1", "What text should be on the '");
define("_AM_FORMULIZE_SCREEN_LOE_BUTTON2", "' button?");
define("_AM_FORMULIZE_SCREEN_LOE_CONFIGINTRO", "Specify which configuration options you want to use:");
define("_AM_FORMULIZE_SCREEN_LOE_CURRENTVIEWLIST", "What text should introduce the 'Current View' list?");
define("_AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEW", "Which published view should be used as the default view?");
define("_AM_FORMULIZE_SCREEN_LOE_EDIT_VIEW", "Configure the available views for this screen");
define("_AM_FORMULIZE_SCREEN_LOE_EDIT_VIEW_DETAILS", "This link will open a new page that shows the master version of the list of entries, where you can manage the views.  If a relationship is in effect for this screen, then it will be active on the page that opens.");
define("_AM_FORMULIZE_SCREEN_LOE_BLANK_DEFAULTVIEW", "Use a blank default view (ie: display no entries)");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_DEFAULTVIEW", "If you are customizing the list template, the default view will still be used to control which entries are initially included in the list.");
define("_AM_FORMULIZE_SCREEN_LOE_LIMITVIEWS", "If the 'Current View' list is in use, include these views:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_LIMITVIEWS", "If you include the basic views (\"Entries by...\"), then the selected view will switch to a basic view when the user makes a change, such as a sort or Quicksearch.");
define("_AM_FORMULIZE_SCREEN_LOE_DEFAULTVIEWLIMIT", "Include all views");
define("_AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_IN_FRAME", "only avail. in framework: ");
define("_AM_FORMULIZE_SCREEN_LOE_VIEW_ONLY_NO_FRAME", "only avail. with no framework");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK", "Leave blank to turn this button off");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_LEAVEBLANK_LIST", "Leave blank to turn off the list");
define("_AM_FORMULIZE_SCREEN_LOE_NOPUBDVIEWS", "There are no published views for this form");
define("_AM_FORMULIZE_SCREEN_LOE_NOVIEWSAVAIL", "There are no views available");
define("_AM_FORMULIZE_SCREEN_LOE_USEWORKING", "Should the 'Working' message appear when the page is reloading?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_USEWORKING", "If the user is likely to click the back button in your interface, turning off this message may improve usability.");
define("_AM_FORMULIZE_SCREEN_LOE_USESCROLLBOX", "Should the list of entries be contained inside a scrolling box?");
define("_AM_FORMULIZE_SCREEN_LOE_USESEARCHCALCMSGS", "Should the 'Advanced Search' or 'Calculations' status messages appear at the top of the list?");
define("_AM_FORMULIZE_SCREEN_LOE_USCM_NEITHER", "use neither<br>");
define("_AM_FORMULIZE_SCREEN_LOE_USCM_BOTH", "use both<br>");
define("_AM_FORMULIZE_SCREEN_LOE_USCM_SEARCH", "just the 'Advanced Search' status<br>");
define("_AM_FORMULIZE_SCREEN_LOE_USCM_CALC", "just the 'Calculations' status");
define("_AM_FORMULIZE_SCREEN_LOE_USEHEADINGS", "Should headings appear at the top of each column?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_USEHEADINGS", "Without headings at the top of columns, no one will be able to sort the entries in the view.");
define("_AM_FORMULIZE_SCREEN_LOE_REPEATHEADERS", "If you are using headings, how often should they repeat within the list of entries?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_REPEATHEADERS", "Repeating the headings makes it easier for users to know what column they are looking at when they scroll through the list.  Set to '0' to have headings only at the top of the list.");
define("_AM_FORMULIZE_SCREEN_LOE_ENTRIESPERPAGE", "How many entries should appear on each page of the list?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_ENTRIESPERPAGE", "Set to '0' to have all entries appear on one page.");
define("_AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN", "What screen should be used to display individual entries when users click on them?");
define("_AM_FORMULIZE_SCREEN_LOE_VIEWENTRYSCREEN_DEFAULT", "Use the default version of this form");
define("_AM_FORMULIZE_SCREEN_LOE_VIEWENTRYPAGEWORKS", "Pageworks page");
define("_AM_FORMULIZE_SCREEN_LOE_COLUMNWIDTH", "How many pixels wide should each column be?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_COLUMNWIDTH", "Set to '0' to have columns expand to their natural width.");
define("_AM_FORMULIZE_SCREEN_LOE_TEXTWIDTH", "How many characters of text should be displayed in any cell?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_TEXTWIDTH", "Set to '0' for no limit.");
define("_AM_FORMULIZE_SCREEN_LOE_USESEARCH", "Should the 'Quicksearch' boxes appear at the top of each column?");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_USESEARCH", "If the 'Quicksearch' boxes are turned off, they will still be available in the Top and Bottom Templates (see below).");
define("_AM_FORMULIZE_SCREEN_LOE_USECHECKBOXES", "Should checkboxes appear to the left of each entry, so that they can be selected by the user?");
define("_AM_FORMULIZE_SCREEN_LOE_UCHDEFAULT", "Yes, show the checkboxes based on the user's permission to delete entries<br>");
define("_AM_FORMULIZE_SCREEN_LOE_UCHALL", "Yes, show the checkboxes on all entries<br>");
define("_AM_FORMULIZE_SCREEN_LOE_UCHNONE", "No, do not show the checkboxes");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_USECHECKBOXES", "If you use a custom List Template, this option will control whether the <i>\$selectionCheckbox</i> variable is set for each row in the list.");
define("_AM_FORMULIZE_SCREEN_LOE_USEVIEWENTRYLINKS", "Should the 'magnifying glass links' appear to the left of each entry, so users can click through to the full details?");
define("_AM_FORMULIZE_SCREEN_LOE_HIDDENCOLUMNS", "Select any columns where you would like the current value from each entry to be included in the list as a hidden form element.");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_HIDDENCOLUMNS", "This option is useful if you need some text on the screen to be sent back in <i>\$_POST</i> as part of the next page load.  You can use <i>gatherHiddenValue('</i>handle<i>');</i> in a custom button access the values you receive.  Any columns you choose will still be displayed normally in the list, in addition to having the hidden form elements created.");
define("_AM_FORMULIZE_SCREEN_LOE_DECOLUMNS", "Select any columns where you would like the data displayed as a form element, rather than as text:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_DECOLUMNS", "<b>WARNING:</b> if you are displaying any checkbox elements in the list, then disable the checkboxes to the left of each entry!");
define("_AM_FORMULIZE_SCREEN_LOE_DESAVETEXT", "If you have selected any columns to display as form elements, what text should be used on a 'Submit' button below the list of entries?");
define("_AM_FORMULIZE_SCREEN_LOE_DVMINE", "Entries by the current user");
define("_AM_FORMULIZE_SCREEN_LOE_DVGROUP", "Entries by the current user's group(s)");
define("_AM_FORMULIZE_SCREEN_LOE_DVALL", "Entries by all groups");
define("_AM_FORMULIZE_SCREEN_LOE_BUTTON_SECTION1", "You can change the text on the buttons below.  Also, if you use a custom Top or Bottom Template, these buttons will be available there.");
define("_AM_FORMULIZE_SCREEN_LOE_BUTTON_SECTION2", "You can change the text on the buttons below.  If you use a custom List Template, these buttons will not appear on the screen by default, but you can use a custom Top or Bottom Template to specifically include them.");
define("_AM_FORMULIZE_SCREEN_LOE_CONFIG_SECTION1", "The configuration options below have an effect regardless of whether you use a custom List Template.");
define("_AM_FORMULIZE_SCREEN_LOE_CONFIG_SECTION2", "Most configuration options below have NO effect if you use a custom List Template, except as noted.");
define("_AM_FORMULIZE_SCREEN_LOE_TEMPLATEINTRO", "OPTIONAL - Specify any custom template options for this screen:");
define("_AM_FORMULIZE_SCREEN_LOE_TEMPLATEINTRO2", "<span style=\"font-weight: normal\"><p><b>Top and Bottom Templates</b></p>\n<p>If you specify any PHP code in the Top or Bottom Templates, it will be used to control the appearance of the space either above or below the list of entries.</p>\n<p><b>WARNING:</b> if you include any checkbox elements in your templates, turn off the checkboxes that appear on the left side of the list!</p>\n<p>Use this PHP code to setup your preferred layout of buttons, or include custom instructions, etc.</p>\n<p>To include buttons and controls, use these variables:</p>
<table cellpadding=5 border=0>
<tr>
<td>
<ul>
<li>\$addButton</li>
<li>\$addMultiButton</li>
<li>\$addProxyButton</li>
<li>\$exportButton</li>
<li>\$importButton</li>
<li>\$notifButton</li>
<li>\$currentViewList</li>
<li>\$changeColsButton</li>
<li>\$saveButton (if any columns are displayed as form elements)</li>
</ul>
</td><td>
<ul>
<li>\$calcButton</li>
<li>\$advSearchButton</li>
<li>\$cloneButton</li>
<li>\$deleteButton</li>
<li>\$selectAllButton</li>
<li>\$clearSelectButton</li>
<li>\$resetViewButton</li>
<li>\$saveViewButton</li>
<li>\$deleteViewButton</li>
<li>\$pageNavControls (if there is more than one page of entries)</li>
</ul>
</td>
</tr>
</table>
<p>For Quicksearch boxes, use \"\$quickSearch<i>Column</i>\" where <i>Column</i> is the element's data handle. See the List Template above for <a href=\"#elementhandles\">a list of the element handles</a>.  <b>Note:</b> you must turn off the quicksearch boxes at the top of the columns before you can use them in a top template.</p>\n
<p>You can also make Quickfilter dropdown boxes, by using \"\$quickFilter<i>Column</i>\".  This only works for selectboxes, radio buttons and checkboxes.</p>\n
<p>You can also make Quickfilter date range selectors, by using \"\$quickDateRange<i>Column</i>\".  This only works for date boxes.</p>\n
<p>For Custom Buttons, use \"\$handle\" where <i>handle</i> is the handle you specified for that button.  You can use \"\$messageText\" to control where the clicked button's message will appear on the screen.  By default, the message appears centred at the top.</p>\n<p>If the current view list is available, you can determine which view was last selected from the list, by checking whether <i>\$The_view_name</i> is true or not.  You can also check <i>\$viewX</i> where X is a number corresponding to the position of the view in the list, 1 through n.  You can use this to put if..else clauses into your template, so it changes depending what view is selected.</p>\n<p><b>List Template</b></p>\n<p>If you specify any PHP code for the List Template, it will be used to draw in each row of the list.</p>\n<p>You do not need to create a foreach loop or any other loop structure in this template.  The PHP code you specify will be executed inside a loop that runs once for each entry.</p>\n<p>You have full access to Formulize objects, functions, variables and constants in this template, including <i>\$fid</i> for the form ID.  Use \$entry to refer to the current entry in the list.  For example:</p>\n<p style=\"font-family: courier\">&nbsp;&nbsp;&nbsp;display(\$entry, \"phonenumber\");</p>\n<p>That code will display the phone number recorded in that entry (assuming \"phonenumber\" is a valid element handle).</p><p>You can use \"\$selectionCheckbox\" to display the special checkbox used to select an entry.</p><p>You can use a special function called \"viewEntryLink\" to create a link to the entry so users can edit it.  This function takes up to three parameters.  The first is the text that will be clickable.  Examples:</p><p style=\"font-family: courier\">&nbsp;&nbsp;&nbsp;print viewEntryLink(\"Click to view this entry\");<br>&nbsp;&nbsp;&nbsp;print viewEntryLink(display(\$entry, \"taskname\"));<br>&nbsp;&nbsp;&nbsp;print viewEntryLink(\"&lt;img src='images/button.jpg'&gt;\");</p>
<p>Optionally, you can also specify a specific entry to edit, and also which screen to edit it in.  This is often useful when the dataset contains more than one form (from a relationship), and you are using the <i>internalRecordIds</i> function to identify which entries in the other forms are being included.  Examples:</p><p style=\"font-family: courier\">&nbsp;&nbsp;&nbsp;print viewEntryLink(\"Click to view this entry\", 123); // takes the user to entry 123 in this form<br><br>
&nbsp;&nbsp;&nbsp;\$ids = internalRecordIds(\$entry, 12); <br>
&nbsp;&nbsp;&nbsp;foreach(\$ids as \$id) { <br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\$taskname = display(\$entry, \"taskname\", \"\", \$id);<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;print viewEntryLink(\$taskname, \$id, 45); <br>
&nbsp;&nbsp;&nbsp;}<br><br>
&nbsp;&nbsp;&nbsp;/*<br>
&nbsp;&nbsp;&nbsp;The above example gets all the ids of the records from form 12<br>
&nbsp;&nbsp;&nbsp;that are part of the current entry (ie: this row).  Form 12 is presumably<br>
&nbsp;&nbsp;&nbsp;a subform in a relationship to the form that this screen belongs to.<br>
&nbsp;&nbsp;&nbsp;Then the code loops through all the ids of the subform entries,<br>
&nbsp;&nbsp;&nbsp;and for each one it gets the value of the task name element in that entry.<br>
&nbsp;&nbsp;&nbsp;Then it makes a clickable link out of the task name, that will send the user<br>
&nbsp;&nbsp;&nbsp;to that subform entry, and will use screen 45 to display the entry (presumably<br>
&nbsp;&nbsp;&nbsp;screen 45 is a regular form screen for form 12).<br>
&nbsp;&nbsp;&nbsp;*/</p>
<p>You can also use <i>viewEntryButton</i> instead of <i>viewEntryLink</i> if you want a clickable button instead of a link.</p>
<p>You can use a special function called \"clickableSortLink\" to create a clickable element on the page, so users can control the sorting order of entries in the list.  This function takes two parameters.  The first is the element handle of the element you want to sort by.  The second is the text or HTML that you want to be clickable.  When the sort is active, a black triangle will appear next to the clickable element.  Examples:</p><p style=\"font-family: courier\">&nbsp;&nbsp;&nbsp;print clickableSortLink(\"last_name\", \"Sort by last name\");<br>&nbsp;&nbsp;&nbsp;print clickableSortLink(\"creation_datetime\", \"&lt;img src='images/clock.jpg'&gt;\");</p>
</span>\n");
define("_AM_FORMULIZE_SCREEN_LOE_TOPTEMPLATE", "Template for the top portion of the page, above the list:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE", "You can type PHP code into any or all of the three template boxes below.  Code in the <i>top template</i> box will replace the default user interface above the list.  Code in the <i>list template</i> box will replace the default way that each row in the list is displayed.  Code in the <i>bottom template</i> box will be rendered below the last row in the list.");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE2", "If you turn off the scrollbox, then these three templates will all be drawn to the screen consecutively.  This means you can start a table in the <i>top template</i>, specify the &lt;tr&gt; tags in the <i>list template</i> and close the table in the <i>bottom template</i>.  Essentially, these three templates give you control over the entire page layout.");
define("_AM_FORMULIZE_SCREEN_LOE_BOTTOMTEMPLATE", "Template for the bottom portion of the page, below the list:");
define("_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE", "Template for each entry in the list portion of the page:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_LISTTEMPLATE", "If you specify a List Template, certain buttons and configuration options mentioned above may be unavailable.");
define("_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE_HELPINTRO_FRAMEWORK", "Below is a list of handles for all the form elements in this Framework.  Use them with the <i>display</i> function.<br><br>Use \"<i>\$entry_id</i>\" to refer to the main form's entry id number.<br><br>Use \"<i>\$form_id</i>\" to refer to the id number of the main form.");
define("_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE_HELPINTRO_FORM", "Below is a list of element data handles for all the elements in this form. Use them with the <i>display</i> function.<br><br>Use \"<i>\$entry_id</i>\" to refer to the entry id number.<br><br>Use \"<i>\$form_id</i>\" to refer to the form id number.");
// CUSTOM BUTTONS
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTONINTRO", "Specify any custom buttons for this screen:");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTONINTRO2", "Custom buttons can be added above, below, or inside a list, using the templates (see below).  You must specify what effects each custom button should have.  For instance, a custom button labelled 'Cancel Subscription' might update a form element called 'Subscription end date', and use today's date as the value to put there.");
define("_AM_FORMULIZE_SCREEN_LOE_ADDCUSTOMBUTTON", "Add a new custom button");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON", "Custom button");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_HANDLE", "What handle is used to refer to this button?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_NEW", "New Custom Button");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_BUTTONTEXT", "What text should appear on this button?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_MESSAGETEXT", "What text should appear at the top of the screen after this button is clicked?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_GROUPS", "For which groups should this custom button appear?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_INLINE", "Should this button appear once on every line of the list of entries?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_INLINE_DESC", "If no, then the button will be available in the Top and Bottom Templates.  If yes, the button will appear in the list, or will be available in the List Template if you use one.");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO", "Which entries should be modified when this button is clicked?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_INLINE", "Only the entry on the line where the button is (only works if this button appears on every line)");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_SELECTED", "Only the selected entries (only works if checkboxes are enabled above)");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_ALL", "All entries in this form");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_CODE", "None.  Run custom PHP code when this button is clicked.");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_CUSTOM_HTML", "None.  Use PHP to render some HTML wherever this button would appear.");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW", "The button should create a new entry in this form");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEWPERSELECTED", "The button should create a new entry in this form for each checkbox that's checked");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEW_OTHER", "The button should create a new entry in the form '");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_APPLYTO_NEWPERSELECTED_OTHER", "' for each checkbox that's checked");
define("_AM_FORMULIZE_SCREEN_LOE_ADDCUSTOMBUTTON_EFFECT", "Add an effect for this button");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_DELETE", "Delete this button");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT", "Effect number");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_DESC", "Specify the element that should be affected, the action that should be performed on that element, and the value to use.  The value can contain PHP code, including <i>gatherHiddenValue('</i>handle<i>');</i> to retrieve the value of a specific field from a selected entry.  Use hidden elements above to send those values.  To use PHP code, the last line of the value should be <i>\$value = \$something;</i>");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_CODE_DESC", "Enter the PHP code that should be executed when this button is clicked.  You can use the global variable \$formulize_thisEntryId to access the entry ID number of the line on which the button was clicked, of if the button does not appear on each line of the list this PHP code will be run once for each checkbox that was checked, and \$formulize_thisEntryId will contain the ID of a different checkbox each time.  If the button is not inline and no checkboxes were checked, then the code will be run once and \$formulize_thisEntryId will be blank.  You can use <i>gatherHiddenValue('</i>handle<i>');</i> to retrieve the value of a specific field from a selected entry.  Use hidden elements above to send those values.");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_CUSTOM_HTML_DESC", "Enter the PHP code that should be executed to render this \"button\".  This is useful in conjunction with the \"appear on every line\" setting, so you can insert some HTML into a column of the list.  Use <i>display(\$entry, \$handle);</i> to include the value of any field form the current entry.");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_DELETE", "Delete this effect");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ELEMENT", "Affect which element?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION", "Perform what action?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_VALUE", "Use what value?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_REPLACE", "Replace the current value with the specified value");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_REMOVE", "Remove the specified value from the current value");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_EFFECT_ACTION_APPEND", "Append the specified value to the end of the current value");

define("_AM_FORMULIZE_CLONING_TITLE", "Cloning options");
define("_AM_FORMULIZE_CLONING_FOUND_ELEMENTS", "After cloning, the following linked selectboxes in this form can be relinked to source elements in these other recently cloned forms:");
define("_AM_FORMULIZE_CLONING_CANBELINKEDTO", "can be linked to:");
define("_AM_FORMULIZE_CLONING_NOCHANGE", "keep it linked to its current source");

define("_AM_FORMULIZE_USE_DEFAULT_WHEN_BLANK", "Show the default value for this element:");
define("_AM_FORMULIZE_USE_DEFAULT_WHEN_BLANK_DESC", "For example, showing defaults when the saved value is blank can be useful on multipage forms, if later pages have elements which should still use the default value, even though the user has saved the entry after the first page.<br><b>Note</b> that required elements are always treated as if this option is turned on regardless, since required elements should never have empty/blank values.");
define("_AM_FORMULIZE_USE_DEFAULT_WHEN_BLANK_ONLY_NEW", "Only for new entries");
define("_AM_FORMULIZE_USE_DEFAULT_WHEN_BLANK_ALL_WHEN_BLANK", "For any entry, when the saved value is blank");
?>
