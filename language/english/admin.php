<?php
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
define("_AM_ELE_DESC","Descriptive text");
define("_AM_ELE_DESC_HELP","Whatever you type in this box will appear below the caption, just like this text does.");
define("_AM_ELE_COLHEAD","Column Heading (optional)");
define("_AM_ELE_COLHEAD_HELP","If you specify a column heading, then this text will be used instead of the caption, on the <b>List of Entries</b> screen.  This is useful if the caption is very long, or if you want the captions written from a user point of view, and the column headings written from a report-consumer point of view.");
define("_AM_ELE_DETAIL","Detail");
define("_AM_ELE_REQ","Required");
define("_AM_ELE_ORDER","Order");
define("_AM_ELE_DISPLAY","Display");
define("_AM_ELE_PRIVATE","Private");

define("_AM_ELE_TEXT","Text box");
define("_AM_ELE_TEXT_DESC","{NAME} will print full name;<br />{UNAME} will print user name;<br />{EMAIL} will print user email;<br />PHP Code (ending with the line '&#36;default = &#36;something;') will be interpreted to generate the default value.");
define("_AM_ELE_TEXT_DESC2","<br />PHP Code is the only situation where more than one line of this box will be read.");
define("_AM_ELE_TAREA","Text area");
define("_AM_ELE_MODIF","Text for display (two columns)");
define("_AM_ELE_MODIF_ONE","Text for display (one column)");
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
define("_AM_ELE_OPT_DESC1","<br />Only the first check is used if multiple selection is not allowed");
define("_AM_ELE_OPT_DESC2","Select the default value by checking the radio buttons<br>Boxes with no text in them will be ignored when you click <i>Save</i>");
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
define("_AM_ELE_FORMLINK_SCOPE_DESC", "The groups you pick define the total possible options to be used.  Optionally, you can choose to have the current user's group memberships further limit the options.  In that case, groups you select will be ignored if the current user is not also a member of the group.");
define("_AM_ELE_FORMLINK_SCOPE_ALL", "Use all groups");
define("_AM_ELE_FORMLINK_SCOPELIMIT_NO", "Use all these groups");
define("_AM_ELE_FORMLINK_SCOPELIMIT_YES", "Use only groups that the current user is also a member of");

// subforms
define("_AM_ELE_SUBFORM_FORM", "Which form do you want to include as a subform?");
define("_AM_ELE_SUBFORM", "Subform (from a form framework)");
define("_AM_ELE_SUBFORM_DESC", "When you display the current form as part of a framework, the subform interface can be included in the form.  The subform interface allows users to create and modify entries in a related subform without leaving the main form.  The list here shows all the possible subforms from all frameworks that this form is part of.");
define("_AM_ELE_SUBFORM_NONE", "No subforms available - define a framework first");
define("_AM_ELE_SUBFORM_ELEMENTS", "Which elements should be displayed as part of the subform interface?");
define("_AM_ELE_SUBFORM_ELEMENTS_DESC", "About three or four elements from the subform can be displayed comfortably as part of the main form.  More than four elements starts to make the interface cluttered.  You can choose which elements you want to display by selecting them from this list.  Users can always modify all elements by clicking a button next to each subform entry that it listed in the main form.");
define("_AM_ELE_SUBFORM_REFRESH", "Refresh elements list to match selected form");


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

define("_AM_VIEW_FORM", "View the form");
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

define("_AM_CONVERT", "Convert");
define("_AM_CONVERT_HELP", "Convert this textbox from a single to a multi-line box (or vice versa)");
define("_AM_ELE_CANNOT_CONVERT", "There are no conversion options for this type of element");
define("_AM_CONVERT_CONFIRM", "Do you want to convert this textbox from a single to a multi-line box (or vice versa)?");
define("_AM_ELE_CONVERTED_TO_TEXTBOX", "This multi-line textbox has been converted to a single-line textbox.");
define("_AM_ELE_CONVERTED_TO_TEXTAREA", "This single-line textbox has been converted to a multi-line textbox.");


// added - start - August 25 2005 - jpc
define("_AM_FORM_DISPLAY_MULTIPLE","Custom");
// added - end - August 25 2005 - jpc
define("_AM_FORM_DISPLAY_EXTRA", "Use this list to display certain elements in a form to only certain groups.  Meant for situations where users in different groups should see different parts of the same form.  Normally, you can leave this on 'All groups'.");
define("_AM_FORM_DISPLAY_ALLGROUPS", "All groups with permission for this form");
define("_AM_FORM_DISPLAY_NOGROUPS", "No groups");
define("_AM_FORM_FORCEHIDDEN", "Include as a hidden element for users who can't see it");
define("_AM_FORM_FORCEHIDDEN_DESC", "Currently only affects radio buttons and textboxes.  This option will cause a hidden form element to be created instead of the radio button series or textbox, and the value of the hidden element will be the default value specified above.  Useful when you always need a default value set in every form entry, but not all groups normally see this element.");

define("_AM_ELE_OTHER", 'For an option of "Other", put {OTHER|*number*} in one of the text boxes. e.g. {OTHER|30} generates a text box with 30 chars width.');

define("_AM_FORM_PRIVATE", "The information that users enter in this element is private");
define("_AM_FORM_PRIVATE_DESC", "If this box is checked, the information that users enter in this element will only be visible to other users who have the view_private_elements permission.  This option is useful for making personal information only available to the appropriate managers.");

?>