<?php
define("_formulize_FORM_TITLE", "Contact us by filling out this form.");
//define("_formulize_MSG_SUBJECT", $xoopsConfig['sitename'].' - Contact Us Form');
define("_formulize_MSG_SUBJECT", '['.$xoopsConfig['sitename'].'] -');
define("_formulize_MSG_FORM", ' Form: ');
//next two added by jwe 7/23/04
define("_formulize_INFO_RECEIVED", "Your information has been received.");
define("_formulize_NO_PERMISSION", "You do not have permission to view this form.");
define("_formulize_MSG_SENT", "Your message has been sent.");
define("_formulize_MSG_THANK", "<br />Thank you for your comments.");
define("_formulize_MSG_SUP","<br /> Take care data have been erased");
define("_formulize_MSG_BIG","The join file is too big to be uploaded.");
define("_formulize_MSG_UNSENT","Please join a file with a size down to ");
define("_formulize_MSG_UNTYPE","You could not join this type's file.<br>Types which are authorize are : ");

define("_formulize_NEWFORMADDED","New form added successfully!");
define("_formulize_FORMMOD","Form title modified successfully!");
define("_formulize_FORMDEL","Form erased successfully!");
define("_formulize_FORMCHARG","Form Loading");
define("_formulize_FORMSHOW","Form results: ");
define("_formulize_FORMTITRE","Form sent parameters have been modify with success");
define("_formulize_NOTSHOW","Form: ");
define("_formulize_NOTSHOW2"," does not contain any registers.");
define("_formulize_FORMCREA","Form created with success!");

define("_MD_ERRORTITLE","Error ! You did not put the form title !!!!");
define("_MD_ERROREMAIL","Error ! You did not put a valid E-mail address !!!!");
define("_MD_ERRORMAIL","Error ! You did not put the form recipient !!!!");

define("_FORM_ACT","Actions");
define("_FORM_CREAT","Create a Form");
define("_FORM_RENOM","Rename the Form");
define("_FORM_RENOM_IMG","<img src='../images/attach.png'>");
define("_FORM_SUP","Erase a form");
define("_FORM_ADD","Modify the Form's Settings");
define("_FORM_SHOW","Consult the results");
define("_FORM_TITLE","Form title:");
define("_FORM_EMAIL","E-mail: ");
define("_FORM_ADMIN","Send to the admin only:");
define("_FORM_EXPE","Receive the form filled:");
define("_FORM_GROUP","Send to a group:");
define("_FORM_MODIF","Modify a form");
define("_FORM_DELTITLE","Form title to erase:");
define("_FORM_NEW","New form");
define("_FORM_NOM","Enter the new file name");
define("_FORM_OPT","Options");
define("_FORM_MENU","Modify entries in the Form Menu block");
define("_FORM_PREF","Modify the Preferences");

//next section added by jwe 7/25/07
define("_FORM_SINGLEENTRY","This form allows each user only one entry (filling in the form again updates the same entry):");
define("_FORM_GROUPSCOPE","Entries in this form are shared and visible to all users in the same groups (not just the user who entered them):");
define("_FORM_HEADERLIST","Form elements displayed on the 'View Entries' page:");
define("_FORM_SHOWVIEWENTRIES","Users can view previous entires made in this form:");
define("_FORM_MAXENTRIES","After a user has filled in the form this many times, they cannot access the form again (0 means no limit):");
define("_FORM_DEFAULTADMIN","Groups that have rights to this form:");

define("_FORM_COLOREVEN","First alternate colour for report writing page (alternate colours override default colours to help distinguish one form from another):");
define("_FORM_COLORODD","Second alternate colour for report writing page:");


define("_FORM_MODIF","Modify the Form's Questions");
define("_AM_FORM","Form: ");
define("_FORM_EXPORT","Export in CSV format");
define("_FORM_ALT_EXPORT","Export");
define("_FORM_DROIT","Athorized group to consult the form");
define("_FORM_MODPERM","Modify form access permissions");
define("_FORM_PERM","Permissions");

define("_FORM_MODPERMLINKS","Modify scope of linked selectboxes");
define("_FORM_PERMLINKS","Linked Selectbox Scopes");

define("_FORM_MODFRAME","Create or Modify a Form Framework");
define("_FORM_FRAME", "Frameworks");


// commented the line below since it's a duplicate of a line above --jwe 7/25/04
//define("_AM_FORM","Form : ");
define("_AM_FORM_SELECT","Select a form");
define("_MD_FILEERROR","Error in sending the file");
define("_AM_FORMUL","Forms");

//added by jwe - 7/28/04
define("_AM_FORM_TITLE", "Form Access Permissions"); // not used
define("_AM_FORM_CURPERM", "Current Permission:"); 
define("_AM_FORM_CURPERMLINKS", "Current Linked Selectbox:"); 
define("_AM_FORM_PERMVIEW", "View");
define("_AM_FORM_PERMADD", "Add/Update");
define("_AM_FORM_PERMADMIN", "Admin");
define("_AM_FORM_SUBMITBUTTON", "Show New Permission"); // not used

define("_AM_FORMLINK_PICK", "Choose an option");
define("_AM_CONFIRM_DEL", "You are about to delete this form!  Please confirm.");

define("_AM_FRAME_NEW", "Create a New Framework:");
define("_AM_FRAME_NEWBUTTON", "Create Now!");
define("_AM_FRAME_EDIT", "Modify an Existing Framework:");
define("_AM_FRAME_NONE", "No Frameworks Exist");
define("_AM_FRAME_CHOOSE", "Choose a Framework");
define("_AM_FRAME_TYPENEWNAME", "Type New Name Here");
define("_AM_CONFIRM_DEL_FF_FORM", "You are about to remove this set of forms from the framework!  Please confirm.");
define("_AM_CONFIRM_DEL_FF_FRAME", "You are about to delete this framework!  Please confirm.");
define("_AM_FRAME_NAMEOF", "Framework Name:");
define("_AM_FRAME_ADDFORM", "Add a pair of forms to this Framework:");
define("_AM_FRAME_FORMSIN", "Forms in this Framework: (click a form name to edit its details)");
define("_AM_FRAME_DELFORM", "Remove");
define("_AM_FRAME_EDITFORM", "Details for:");
define("_AM_FRAME_DONEBUTTON", "Done");
define("_AM_FRAME_NOFORMS", "There are no forms in this Framework");
define("_AM_FRAME_AVAILFORMS1", "Form One:");
define("_AM_FRAME_AVAILFORMS2", "Form Two:");
define("_AM_FRAME_DELETE", "Delete an Existing Framework:");
define("_AM_FRAME_SUBFORM_OF", "Make it a subform of:");
define("_AM_FRAME_NOPARENTS", "No Forms in Framework"); 
define("_AM_FRAME_TYPENEWFORMNAME", "Type a short name here");
define("_AM_FRAME_NEWFORMBUTTON", "Add Forms!");
define("_AM_FRAME_NOKEY", "none specified!");
define("_AM_FRAME_FORMNAMEPROMPT", "Name for this form in this framework:");
define("_AM_FRAME_RELATIONSHIP", "Relationship:");
define("_AM_FRAME_ONETOONE", "One to One");
define("_AM_FRAME_ONETOMANY", "One to Many");
define("_AM_FRAME_MANYTOONE", "Many to One");
define("_AM_FRAME_LINKAGE", "Link between these forms:");
define("_AM_FRAME_DISPLAY", "Display these forms as one?");
define("_AM_FRAME_UIDLINK", "User ID of the person who filled them in");
define("_AM_FRAME_UPDATEBUTTON", "Update this Framework with these settings");
define("_AM_FRAME_UPDATEFORMBUTTON", "Update this Form with these Handles");
define("_AM_FRAME_UPDATEANDGO", "Update, and return to previous page");

define("_AM_FRAME_FORMHANDLE", "Handle for this form:");
define("_AM_FRAME_FORMELEMENTS", "Elements In This Form");
define("_AM_FRAME_ELEMENT_CAPTIONS", "Captions");
define("_AM_FRAME_ELEMENT_HANDLES", "Handles");
define("_AM_FRAME_HANDLESHELP", "Use this page to specify <i>Handles</i> for this form and its elements.  Handles are short names that can be used to refer to this form and its elements from outside the Formulize module.");

define("_FORM_EXP_CREE","File has been exported with success");

//template constants added by jwe 7/24/04
define("_formulize_TEMP_ADDENTRY", "ADD AN ENTRY");
define("_formulize_TEMP_VIEWENTRIES", "VIEW ENTRIES");
define("_formulize_TEMP_ADDINGENTRY", "ADDING AN ENTRY");
define("_formulize_TEMP_VIEWINGENTRIES", "VIEWING ENTRIES");
define("_formulize_TEMP_SELENTTITLE", "Your entries in '");
define("_formulize_TEMP_SELENTTITLE_GS", "All entries in '");
define("_formulize_TEMP_SELENTTITLE_RP", "Search Results for '");
define("_formulize_TEMP_SELENTTITLE2_RP", "Calculation Results for '");
define("_formulize_TEMP_VIEWTHISENTRY", "View this entry");
define("_formulize_TEMP_EDITINGENTRY", "EDITING AN ENTRY");
define("_formulize_TEMP_NOENTRIES", "No entries.");
define("_formulize_TEMP_ENTEREDBY", "Entered by: ");
define("_formulize_TEMP_ENTEREDBYSINGLE", "Entered ");
define("_formulize_TEMP_ON", "on");
define("_formulize_TEMP_QYES", "YES");
define("_formulize_TEMP_QNO", "NO");
define("_formulize_REPORT_ON", "Turn Report Writing Mode ON");
define("_formulize_REPORT_OFF", "Turn Reporting Writing Mode OFF");
define("_formulize_VIEWAVAILREPORTS", "View Report:");
define("_formulize_NOREPORTSAVAIL", "Default View");
define("_formulize_CHOOSEREPORT", "Default View");
define("_formulize_REPORTING_OPTION", "Reporting Options");
define("_formulize_SUBMITTEXT", "Apply");
define("_formulize_RESETBUTTON", "RESET");
define("_formulize_QUERYCONTROLS", "Query Controls");
define("_formulize_SEARCH_TERMS", "Search Terms:");
define("_formulize_STERMS", "Terms:");
define("_formulize_AND", "AND");
define("_formulize_OR", "OR");
define("_formulize_SEARCH_OPERATOR", "Operator:");
define("_formulize_NOT", "NOT");
define("_formulize_LIKE", "LIKE");
define("_formulize_NOTLIKE", "NOT LIKE");
define("_formulize_CALCULATIONS", "Calculations:");
define("_formulize_SUM", "Sum");
define("_formulize_SUM_TEXT", "Total of all values in column:");
define("_formulize_AVERAGE", "Average");
define("_formulize_AVERAGE_INCLBLANKS", "Average value in column:");
define("_formulize_AVERAGE_EXCLBLANKS", "Average value excluding blanks and zeros:");
define("_formulize_MINIMUM", "Minimum");
define("_formulize_MINIMUM_INCLBLANKS", "Minimum value in column:");
define("_formulize_MINIMUM_EXCLBLANKS", "Minimum value excluding blanks and zeros:");
define("_formulize_MAXIMUM", "Maximum");
define("_formulize_MAXIMUM_TEXT", "Maximum value in column:");
define("_formulize_COUNT", "Counts");
define("_formulize_COUNT_INCLBLANKS", "Total values in column:");
define("_formulize_COUNT_ENTRIES", "Total entries in column:");
define("_formulize_COUNT_NONBLANKS", "Total non-blank, non-zero entries in column:");
define("_formulize_COUNT_EXCLBLANKS", "Total non-blank, non-zero values in column:");
define("_formulize_COUNT_PERCENTBLANKS", "Percentage of non-blank, non-zero values:");
define("_formulize_COUNT_UNIQUES", "Total unique values in column:");
define("_formulize_COUNT_UNIQUEUSERS", "Number of users who have made entries in column:");
define("_formulize_PERCENTAGES", "Percentages");
define("_formulize_PERCENTAGES_VALUE", "Value:");
define("_formulize_PERCENTAGES_COUNT", "Count:");
define("_formulize_PERCENTAGES_PERCENT", "% of total:");
define("_formulize_PERCENTAGES_PERCENTEXCL", "% excl. blanks:");
define("_formulize_SORTING_ORDER", "Sorting Order:");
define("_formulize_SORT_PRIORITY", "Sort Priority:");
define("_formulize_NONE", "None");
define("_formulize_CHANGE_COLUMNS", "Change to viewing different columns:");
define("_formulize_CHANGE", "Change");
define("_formulize_SEARCH_HELP", "If you specify search terms in more than one column, the Interfield AND/OR Setting determines whether to search for entries that match in all columns (AND), or at least one column (OR).<br><br>The AND/OR option below the terms box determines whether to search for entries that match all the terms (AND), or at least one of the terms (OR).<br><br>Use commas to separate terms.  Use [,] to specify a comma within a term.");
define("_formulize_SORT_HELP", "You can sort by any element, except ones that accept multiple inputs, such as checkboxes.");
define("_formulize_REPORTSCOPE", "Select the scope of the report:");
define("_formulize_SELECTSCOPEBUTTON", "Select");
define("_formulize_GROUPSCOPE", "Group: ");
define("_formulize_USERSCOPE", "User: ");
define("_formulize_GOREPORT", "Go");
define("_formulize_REPORTSAVING", "Save this query as one of your reports:");
define("_formulize_SAVEREPORTBUTTON", "Save");
define("_formulize_REPORTNAME", "Report Name:");
define("_formulize_ANDORTITLE", "Interfield AND/OR Setting:");

define("_formulize_PUBLISHINGOPTIONS", "Publishing Options:");
define("_formulize_PUBLISHREPORT", "Publish this report to other users.");
define("_formulize_PUBLISHNOVE", "Remove 'View this entry' links from the report (so users can't see the full details of each entry).");
define("_formulize_PUBLISHCALCONLY", "Remove the list of entries entirely, and show only the aggregate calculations.");


define("_formulize_LOCKSCOPE", "<b>Save report with the current scope locked</b> (otherwise viewers are limited to their default scope).");
define("_formulize_REPORTPUBGROUPS", "Select the groups to publish to:");
define("_formulize_REPORTDELETE", "Delete the currently selected report:");
define("_formulize_DELETE", "Delete");
define("_formulize_DELETECONFIRM", "Check this box and then press the button to delete");

define("_formulize_REPORTEXPORTING", "Export this query as a spreadsheet file:");
define("_formulize_EXPORTREPORTBUTTON", "Export");
define("_formulize_EXPORTEXPLANATION", "Click the <b>Export</b> button to download a spreadsheet-readable file containing the results of the current query.  Note that you can specify the delimiter used between the fields.  If the delimiter character you choose is present in the your results, then the spreadsheet file will not open correctly, so try exporting with a different delimiter.");
define("_formulize_FILEDELTITLE", "Field Delimiter:");
define("_formulize_FDCOMMA", "Comma");
define("_formulize_FDTAB", "Tab");
define("_formulize_FDCUSTOM", "Custom");
define("_formulize_exfile", "exported_data_");
define("_formulize_DLTEXT", "<b>Right-click on the link below and select <i>Save</i>.</b> (Ctrl-click on a Mac.)  Once the file is on your computer, you will be able to open it in a spreadsheet program.  If the fields do not align properly when you open the file, try exporting with a different delimiter.");
define("_formulize_DLHEADER", "Your file is ready for download.");

define("_formulize_PICKAPROXY", "No Proxy User Selected");
define("_formulize_PROXYFLAG", "(Proxy)");

define("_formulize_DELBUTTON", "Delete");
define("_formulize_DELCONF", "You are about to delete an entry!  Please confirm.");


?>
