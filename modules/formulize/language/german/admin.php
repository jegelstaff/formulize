<?php
// Admin
define("_FORM_RENAME_TEXT", "Formular umbenennen");
define("_FORM_EDIT_ELEMENTS_TEXT", "Elemente des Formulars bearbeiten");
define("_FORM_EDIT_SETTINGS_TEXT", "Einstellungen des Formulars bearbeiten");
define("_FORM_CLONE_TEXT", "Formular klonen");
define("_FORM_CLONEDATA_TEXT", "Clone this form and its data");
define("_FORM_DELETE_TEXT", "Dieses Formular löschen");

define("_AM_SAVE","Speichern");
define("_AM_COPIED","%s kopieren");
define("_AM_DBUPDATED","Datenbank erfolgreich aktualisiert!");
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
define("_AM_ELE_LEFTRIGHT_TEXT","Contents of the left side");
define("_AM_ELE_LEFTRIGHT_DESC","Any text or HTML code that you type here will appear on the left beside the caption.  You can use PHP code instead of text or HTML, just make sure it contains '&#36;value = &#36;something;' and Formulize will read this text as PHP code.");
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
define("_AM_ELE_PRIVATE","Private");
define("_AM_ELE_HANDLE_HEADING","Data handle/ID");
define("_AM_ELE_TYPE_HEADING","Type");
define("_AM_ELE_DISPLAY_HEADING","Display");


define("_AM_ELE_TEXT","Text box");
define("_AM_ELE_TEXT_DESC","{NAME} will print full name;<br />{UNAME} will print user name;<br />{EMAIL} will print user email;<br />{ID} will cause the entry ID number of the entry to be inserted into the textbox, when the entry is first saved.<br />{SEQUENCE} will cause the values in the box to be a series of consecutive numbers.<br />PHP Code (ending with the line '&#36;default = &#36;something;') will be interpreted to generate the default value.");
define("_AM_ELE_TEXT_DESC2","<br />PHP Code is the only situation where more than one line of this box will be read.");
define("_AM_ELE_TAREA","Text area");
define("_AM_ELE_MODIF","Text for display (left and right cells)");
define("_AM_ELE_MODIF_ONE","Text for display (spanning both cells)");
define("_AM_ELE_INSERTBREAK","HTML content for this line:");
define("_AM_ELE_IB_DESC","The caption will not display.  Only the text in this box will appear on screen, in a single row spanning both columns of the form.");
define("_AM_ELE_IB_CLASS","CSS class for the row:");
define("_AM_ELE_SELECT","Select box");
define("_AM_ELE_CHECK","Check boxes");
define("_AM_ELE_RADIO","Radio buttons");
define("_AM_ELE_YN","Simple yes/no radio buttons");
define("_AM_ELE_DATE","Date");
define("_AM_ELE_REQ_USELESS","Not usable for select box, check boxes nor radio buttons");
define("_AM_ELE_SEP","Break up line");
define("_AM_ELE_NOM_SEP","Break up name");
define("_AM_ELE_UPLOAD","Join a file");
define("_AM_ELE_CLR","with the color");

// number options for textboxes
define("_AM_ELE_NUMBER_OPTS","If a number is typed...");
define("_AM_ELE_NUMBER_OPTS_DESC","You can set these options to control how decimals are handled, and how numbers are formatted on screen.");
define("_AM_ELE_NUMBER_OPTS_DEC","Number of decimal places:");
define("_AM_ELE_NUMBER_OPTS_PREFIX","Display numbers with this prefix (ie: '$'):");
define("_AM_ELE_NUMBER_OPTS_DECSEP","Separate decimals with this character (ie: '.'):");
define("_AM_ELE_NUMBER_OPTS_SEP","Separate thousands with this character (ie: ','):");
define("_AM_ELE_DERIVED_NUMBER_OPTS","If this formula produces a number ...");

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
define("_AM_ELE_OPT","Options");
define("_AM_ELE_OPT_DESC","Setting a single option of '{FULLNAMES}' or '{USERNAMES}' will produce a list of users based on the group limits set below.<br /><br />Tick the check boxes for selecting default values");
define("_AM_ELE_OPT_DESC_CHECKBOXES","Tick the check boxes for selecting default values<br>Boxes with no text in them will be ignored when you click <i>Save</i>");
define("_AM_ELE_OPT_DESC1","<br />Only the first check is used if multiple selection is not allowed");
define("_AM_ELE_OPT_DESC2","Select the default value by checking the radio buttons<br>Boxes with no text in them will be ignored when you click <i>Save</i>");
define("_AM_ELE_OPT_UITEXT", "The text visible to the user can be different from what is stored in the database.  This is useful if you want to have numbers saved in the database, but text visible to the user so they can make their selection.  To do this, use the \"pipe\" character (usually above the Enter key) like this:  \"10|It has been 10 days since I visited this website\"");
define("_AM_ELE_ADD_OPT","Add %s options");
define("_AM_ELE_ADD_OPT_SUBMIT","Add");
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
define("_AM_FORMLINK_NONE", "No link -- above options in effect");
define("_AM_ELE_FORMLINK_TEXTBOX", "Associate values with another form element");
define("_AM_ELE_FORMLINK_DESC_TEXTBOX","If you select another form element here, then text that users type into this element will be compared with values entered in the other element.  If a match is found then the text users type into this element will be clickable in the \"List of Entries\" screen, and will take users to the matching entry in the other form.");
define("_AM_FORMLINK_NONE_TEXTBOX", "No association in effect");
define("_AM_ELE_FORMLINK_SCOPE", "If the options are linked -- or are {FULLNAMES} or {USERNAMES} -- limit them to values from the groups selected here.");
define("_AM_ELE_FORMLINK_SCOPE_DESC", "The groups you pick define the total possible options to be used.  Optionally, you can choose to have the current user's group memberships further limit the options.  In that case, groups you select will be ignored if the current user is not also a member of the group.<br><br>Also, you can specify whether entries must be created by users who are members of all the groups, or just any one group.  Note that this option can interact powerfully with \"Use only groups that the current user is also a member of\", to let you limit the options to ones from entries created by users who are members of <b>all</b> the same groups as the current user.");
define("_AM_ELE_FORMLINK_SCOPE_ALL", "Use all groups");
define("_AM_ELE_FORMLINK_SCOPELIMIT_NO", "Use all these groups<br>");
define("_AM_ELE_FORMLINK_SCOPELIMIT_YES", "Use only groups that the current user is also a member of<br><br>");
define("_AM_ELE_FORMLINK_ANYALL_ANY", "Include entries by users who are members of any group in use<br>");
define("_AM_ELE_FORMLINK_ANYALL_ALL", "Include entries by users who are members of all groups in use");

// formlink scope filters -- feb 6 2008
define("_AM_ELE_FORMLINK_SCOPEFILTER", "If the options are linked -- or are {FULLNAMES} OR {USERNAMES} -- filter them based on these properties of their entry in the source form.");
define("_AM_ELE_FORMLINK_SCOPEFILTER_DESC", "When you link to values in another form, you may wish to limit the values included in the list based on certain properties of the entries in the other form.  For example, if you are linking to the names of tasks in a task form, you might want to list only tasks that are incomplete.  If there's a question in the task form that asks if the task is complete, you could specify a filter like: \"Task is complete = No\".<br><br>If the options are {FULLNAMES} or {USERNAMES}, and you are using a custom profile form in conjunction with the Registration Codes module, you can filter the names based on the profile form.");
define("_AM_ELE_FORMLINK_SCOPEFILTER_ALL", "No filter in effect (select this to clear existing filters).");
define("_AM_ELE_FORMLINK_SCOPEFILTER_CON", "Filter the options based on this/these conditions:");
define("_AM_ELE_FORMLINK_SCOPEFILTER_ADDCON", "Add another condition");
define("_AM_ELE_FORMLINK_SCOPEFILTER_REFRESHHINT", "(If the first list here is empty, click the 'Add another condition' button to refresh it.)");
       
       
  

// subforms
define("_AM_ELE_SUBFORM_FORM", "Which form do you want to include as a subform?");
define("_AM_ELE_SUBFORM", "Subform (from a form framework)");
define("_AM_ELE_SUBFORM_DESC", "When you display the current form as part of a framework, the subform interface can be included in the form.  The subform interface allows users to create and modify entries in a related subform without leaving the main form.  The list here shows all the possible subforms from all frameworks that this form is part of.");
define("_AM_ELE_SUBFORM_NONE", "No subforms available - define a framework first");
define("_AM_ELE_SUBFORM_ELEMENTS", "Which elements should be displayed as part of the subform interface?");
define("_AM_ELE_SUBFORM_ELEMENTS_DESC", "About three or four elements from the subform can be displayed comfortably as part of the main form.  More than four elements starts to make the interface cluttered.  You can choose which elements you want to display by selecting them from this list.  Users can always modify all elements by clicking a button next to each subform entry that it listed in the main form.");
define("_AM_ELE_SUBFORM_REFRESH", "Refresh elements list to match selected form");
define("_AM_ELE_SUBFORM_BLANKS", "How many blank spaces should be shown for this subform when the page first loads?");

// grids
define("_AM_ELE_GRID", "Table of existing elements (place BEFORE the elements it contains)");
define("_AM_ELE_GRID_HEADING", "What text should appear as the heading for this table?");
define("_AM_ELE_GRID_HEADING_USE_CAPTION", "The caption typed above");
define("_AM_ELE_GRID_HEADING_USE_FORM", "The title of this form");
define("_AM_ELE_GRID_HEADING_NONE", "No heading");
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

define("_AM_ELE_SELECT_NONE","No element selected.");
define("_AM_ELE_CONFIRM_DELETE","Are you sure you want to delete this form element?<br>All data associated with this form element will be deleted as well.");

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
define("_AM_BOLD","Fett");
define("_AM_NORMAL","normal");
define("_AM_MARGINBOTTOM","Bottom margin");
define("_AM_MARGIN_BOTTOMSHORT","mrg. bott.");
define("_AM_MARGINTOP","Top margin");
define("_AM_MARGIN_TOPSHORT","mrg. top");
define("_AM_EDIT","Bearbeiten");
define("_AM_DELETE","Löschen");
define("_AM_ADDMENUITEM","Add menu item");
define("_AM_CHANGEMENUITEM","Modify menu item");
define("_AM_SITENAMET","Site Name:");
define("_AM_URLT","URL:");
define("_AM_FONT","Schriftart:");
define("_AM_STATUST","Status:");
define("_AM_MEMBERSONLY","Authorized users");
define("_AM_MEMBERSONLY_SHORT","Reg.<br>only");
define("_AM_MEMBERS","members only");
define("_AM_ALL","all users");
define("_AM_ADD","Hinzufügen");
define("_AM_EDITMENUITEM","Edit menu item");
define("_AM_DELETEMENUITEM","Delete menu item");
define("_AM_SAVECHANG","Save changes");
define("_AM_WANTDEL","Do you really want to delete this menu item?");
define("_AM_YES","Ja");
define("_AM_NO","Nein");
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
define("_AM_FORM_FORCEHIDDEN", "Include as a hidden element for users who can't see it");
define("_AM_FORM_FORCEHIDDEN_DESC", "Currently only affects radio buttons and textboxes.  This option will cause a hidden form element to be created instead of the radio button series or textbox, and the value of the hidden element will be the default value specified above.  Useful when you always need a default value set in every form entry, but not all groups normally see this element.");

define("_AM_ELE_DISABLED", "Disable this element for any groups?");
define("_AM_FORM_DISABLED_EXTRA", "Use this option to make this element inactive for certain groups.  The element will still be shown to users according to the display option above, but you can use this option to disable the element so users cannot change its value.  This option currently works only for textboxes and textarea boxes.");
define("_AM_FORM_DISABLED_ALLGROUPS", "Disable for all groups");
define("_AM_FORM_DISABLED_NOGROUPS", "Disable for no groups");


define("_AM_ELE_OTHER", 'For an option of "Other", put {OTHER|*number*} in one of the text boxes. e.g. {OTHER|30} generates a text box with 30 chars width.');

define("_AM_FORM_PRIVATE", "The information that users enter in this element is private");
define("_AM_FORM_PRIVATE_DESC", "If this box is checked, the information that users enter in this element will only be visible to other users who have the view_private_elements permission.  This option is useful for making personal information only available to the appropriate managers.");

//added by felix <INBOX International> for sedonde (colorpicker feature)
define("_AM_ELE_COLORPICK","Colorpicker");

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
define("_AM_FORMULIZE_SCREEN_DONEDEST", "The URL for the link users get at the end of the form");
define("_AM_FORMULIZE_SCREEN_BUTTONTEXT", "The text of the link users get at the end of the form");
define("_AM_FORMULIZE_SCREEN_PRINTALL", "Make the 'Printable View - All Pages' button available at the end of the form"); //nmc 2007.03.24
define("_AM_FORMULIZE_SCREEN_PRINTALL_Y", "Yes"); //nmc 2007.03.24
define("_AM_FORMULIZE_SCREEN_PRINTALL_N", "No"); //nmc 2007.03.24
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
define("_AM_FORMULIZE_SCREEN_LOE_DESC_DECOLUMNS", "<b>WARNING:</b> do not enable the checkboxes above if you are displaying any checkbox elements in the list!");
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
<p>For Quicksearch boxes, use \"\$quickSearch<i>Column</i>\" where <i>Column</i> is either the element ID number, or the element handle if using a Framework.</p>\n
<p>You can also make Quickfilter dropdown boxes, by using \"\$quickFilter<i>Column</i>\".  This only works for selectboxes, radio buttons and checkboxes.</p>\n
<p>For Custom Buttons, use \"\$handle\" where <i>handle</i> is the handle you specified for that button.  You can use \"\$messageText\" to control where the clicked button's message will appear on the screen.  By default, the message appears centred at the top.</p>\n<p>If the current view list is available, you can determine which view was last selected from the list, by checking whether <i>\$The_view_name</i> is true or not.  You can also check <i>\$viewX</i> where X is a number corresponding to the position of the view in the list, 1 through n.  You can use this to put if..else clauses into your template, so it changes depending what view is selected.</p>\n<p><b>List Template</b></p>\n<p>If you specify any PHP code for the List Template, it will be used to draw in each row of the list.</p>\n<p>You do not need to create a foreach loop or any other loop structure in this template.  The PHP code you specify will be executed inside a loop that runs once for each entry.</p>\n<p>You have full access to XOOPS and Formulize objects, functions, variables and constants in this template, including <i>\$fid</i> for the form ID.  Use \$entry to refer to the current entry in the list.  For example:</p>\n<p style=\"font-family: courier\">&nbsp;&nbsp;&nbsp;display(\$entry, \"phonenumber\");</p>\n<p>That code will display the phone number recorded in that entry (assuming \"phonenumber\" is a valid element handle).</p><p>You can use \"\$selectionCheckbox\" to display the special checkbox used to select an entry.</p><p>You can use a special function called \"viewEntryLink\" to create a link to the entry so users can edit it.  This function takes one parameter, which is the text that will be clickable.  Examples:</p><p style=\"font-family: courier\">&nbsp;&nbsp;&nbsp;print viewEntryLink(\"Click to view this entry\");<br>&nbsp;&nbsp;&nbsp;print viewEntryLink(display(\$entry, \"taskname\"));<br>&nbsp;&nbsp;&nbsp;print viewEntryLink(\"&lt;img src='\" . XOOPS_ROOT_PATH . \"/images/button.jpg'&gt;\");</p></span>\n");
define("_AM_FORMULIZE_SCREEN_LOE_TOPTEMPLATE", "Template for the top portion of the page, above the list:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_TOPTEMPLATE", "If you turn off the scrollbox, and do not use the Export buttons, then the code you type here and in the List and Bottom Templates, will all be drawn to the screen consecutively.  This means you can start a table in the Top Template, specify the &lt;tr&gt; tags in the List Template and close the  table in the Bottom Template.  Essentially, these three Templates give you control over the entire page layout.");
define("_AM_FORMULIZE_SCREEN_LOE_BOTTOMTEMPLATE", "Template for the bottom portion of the page, below the list:");
define("_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE", "Template for each entry in the list portion of the page:");
define("_AM_FORMULIZE_SCREEN_LOE_DESC_LISTTEMPLATE", "If you specify a List Template, certain buttons and configuration options mentioned above may be unavailable.");
define("_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE_HELPINTRO_FRAMEWORK", "Below is a list of handles for all the form elements in this Framework.  Use them with the <i>display</i> function.");
define("_AM_FORMULIZE_SCREEN_LOE_LISTTEMPLATE_HELPINTRO_FORM", "Below is a list of element IDs for all the elements in this form. Use them with the <i>display</i> function.");
// CUSTOM BUTTONS
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTONINTRO", "Specify any custom buttons for this screen:");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTONINTRO2", "Custom buttons can be added above, below, or inside a list, using the templates (see below).  You must specify what effects each custom button should have.  For instance, a custom button labelled 'Cancel Subscription' might update a form element called 'Subscription end date', and use today's date as the value to put there.");
define("_AM_FORMULIZE_SCREEN_LOE_ADDCUSTOMBUTTON", "Add a new custom button");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON", "Custom button");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_HANDLE", "What handle is used to refer to this button?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_NEW", "New Custom Button");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_BUTTONTEXT", "What text should appear on this button?");
define("_AM_FORMULIZE_SCREEN_LOE_CUSTOMBUTTON_MESSAGETEXT", "What text should appear at the top of the screen after this button is clicked?");
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
?>