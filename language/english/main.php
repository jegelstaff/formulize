<?php
define("_formulize_FORM_TITLE", "Contact us by filling out this form.");
define("_AM_CATGENERAL", "General Forms");
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

define("_FORM_ANON_USER","Someone on the internet");

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
define("_FORM_SINGLETYPE", "How many entries are allowed for this form?");
define("_FORM_SINGLE_GROUP", "One per group");
define("_FORM_SINGLE_ON", "One per user");
define("_FORM_SINGLE_MULTI", "More than one per user");
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

define("_FORM_MODCLONE", "Clone this form");
define("_FORM_MODCLONEDATA", "Clone this form and data");
define("_FORM_MODCLONED_FORM", "Cloned Form");

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
define("_AM_CONFIRM_DEL", "You are about to delete this form!  All data in this form will be deleted too.  Please confirm.");

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

define("_AM_SELECT_PROXY", "Is this info submitted on behalf of someone else?");

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

define("_formulize_SHOWCALCONLY", "Show Calculations Only (no list of entries)");

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

define("_CONTINUE", "Continue to the next part");
define("_SAVE_AND_GO_BACK", "Submit, and return to main form");
define("_formulize_RELENTRIES", "Related Entries:");
define("_formulize_SUBFORM_MESSAGE", "Continuing to the next part of the form...");
define("_formulize_SUBFORM_RETURN", "Returning to the main form...");
define("_formulize_ADDNEW_SUBFORM", "Add a new entry");

define("_formulize_GROUPS", "Groups");

define("_AM_MODIFY_MULTI","Permissions for multiple groups");
define("_AM_MULTI_PERMISSIONS","Modify Permissions for these Groups");
define("_AM_MULTI_CREATION_ORDER","Creation Order");
define("_AM_MULTI_ALPHABETICAL_ORDER","Alphabetical Order");
define("_AM_MULTI_GROUP_LISTS","Saved Group Lists");
define("_AM_MULTI_GROUP_LISTS_NOSELECT","No Group List Selected");
define("_AM_MULTI_SAVE_LIST","Save List");
define("_AM_MULTI_DELETE_LIST","Delete List");
define("_AM_MULTI_MODIFICATION","Type of Modification");
define("_AM_MULTI_ADD_PERMISSIONS","Add Permissions");
define("_AM_MULTI_REMOVE_PERMISSIONS","Remove Permissions");
define("_AM_MULTI_GROUP_LIST_NAME","Enter a group list name");
define("_AM_MULTI_GROUP_LIST_DELETE","Are you sure that you want to delete item");

define("_formulize_FORM_LIST", "Modify Permissions for these Forms");
define("_formulize_SHOW_PERMS", "Show these Permissions");
define("_formulize_MODFORM_TITLE", "Choose the Groups and Forms you want to change permissions for:");
define("_formulize_MODPERM_TITLE", "Modify the permissions:");

define("_formulize_FD_ABOUT", "About this entry:");
define("_formulize_FD_CREATED", "Created by: ");
define("_formulize_FD_MODIFIED", "Modified by: ");
define("_formulize_FD_NEWENTRY", "This is a new entry that has not been saved yet.");

define("_formulize_ADD_ONE", "Add one");
define("_formulize_ADD_ANOTHER", "Add another");
define("_formulize_DELETE_CHECKED", "Delete checked items");
define("_formulize_ADD_HELP", "Click an item on the left side to view the details for that entry.");
define("_formulize_ADD_HELP2", "Hover the mouse over an item for an explanation of what it is.");
define("_formulize_SAVE", "Save");
define("_formulize_DONE", "All Done");
define("_formulize_CONFIRMNOSAVE", "You have not saved your changes!  Is that OK?  Click 'Cancel' to return to the form and then click 'Save' to save your changes.");

define("_formulize_INFO_SAVED", "Your information has been saved.");
define("_formulize_INFO_DONE1", "Click the <i>");
define("_formulize_INFO_DONE2", "</i> button if you are finished.");
define("_formulize_INFO_CONTINUE1", "You can update your information below.");
define("_formulize_INFO_CONTINUE2", "You can make another entry by filling in the form again.");
define("_formulize_INFO_SAVEBUTTON", "Click the <i>" . _formulize_SAVE . "</i> button to save your changes.");
define("_formulize_INFO_MAKENEW", "You can make a new entry by filling in the form below.");

define("_formulize_NOSUBNAME", "Entry: ");

define("_formulize_DEL_ENTRIES", "You are about to delete the selected entries!  Please confirm.");

define("_formulize_PRINTVIEW", "Printable View");

// constants related to the new display entries functions...

define("_formulize_DE_CURRENT_VIEW", "Current View: ");
define("_formulize_DE_STANDARD_VIEWS", "STANDARD VIEWS:");
define("_formulize_DE_NO_STANDARD_VIEWS", "No standard views available");
define("_formulize_DE_SAVED_VIEWS", "YOUR SAVED VIEWS:");
define("_formulize_DE_PUB_VIEWS", "PUBLISHED VIEWS:");
define("_formulize_DE_WARNLOCK", "<p>The view that you have selected is set to <i>lock the controls</i>.  This means that you cannot change the columns, do calculations, do advanced searches, or export data.</p><p>You can perform sorting and basic searches using the controls at the top of each column.</p>");
define("_formulize_DE_MINE", "Entries by me");
define("_formulize_DE_GROUP", "Entries by all users in my group(s)");
define("_formulize_DE_ALL", "Entries by all users in all groups");
define("_formulize_DE_GO", "Apply search terms");
define("_formulize_DE_CHANGECOLS", "Change columns");
define("_formulize_DE_PICKNEWCOLS", "Pick different columns to view");
define("_formulize_DE_AVAILCOLS", "Available columns:");
define("_formulize_DE_LASTMOD", "Last modified by");
define("_formulize_DE_CREATED", "Created by");
define("_formulize_DE_ON", "on");
define("_formulize_DE_VIEWDETAILS", "Click to view details for this entry.");
define("_formulize_DE_RESETVIEW", "Reset current view");
define("_formulize_DE_CALCS", "Calculations");
define("_formulize_DE_EXPORT", "Export data");
define("_formulize_DE_SAVE", "Save current view");
define("_formulize_DE_DELETE", "Delete current view");
define("_formulize_DE_ADDENTRY", "Add one entry");
define("_formulize_DE_ADD_MULTIPLE_ENTRY", "Add multiple entries");
define("_formulize_DE_PROXYENTRY", "Make a proxy entry");
define("_formulize_DE_UPDATEENTRY", "Update your entry");
define("_formulize_DE_DELETESEL", "Delete selected");
define("_formulize_DE_SELALL", "Select all entries");
define("_formulize_DE_CLEARALL", "Clear selection");
define("_formulize_DE_CONFIRMDEL", "You are about to delete the selected entries.  Please confirm!");
define("_formulize_DE_DELBOXDESC", "Check this box to select/unselect this entry for deletion.");
define("_formulize_DE_CHOOSE_EXPORT", "Choose the export format you want");
define("_formulize_DE_EXPORT_INST", "Choose the format you would like your data exported in.  Comma delimited should work with all data.  However, if you have especially complex combinations of quotation marks and commas within your data itself, and your exported files are not formatting correctly, you may want to try one of the other delimiters instead.");
define("_formulize_DE_XCOMMA", "Comma delimited");
define("_formulize_DE_XTAB", "Tab delimited");
define("_formulize_DE_XCUST", "Custom:");
define("_formulize_DE_XF", "exported_");
define("_formulize_DE_CLICKSAVE", "Right click and save to download your data.");
define("_formulize_DE_CANCELCALCS", "Cancel calculations");
define("_formulize_DE_SHOWLIST", "Switch to entries");
define("_formulize_DE_HIDELIST", "Switch to calcs.");
define("_formulize_DE_SORTTHISCOL", "Click to sort entries by this column");

define("_formulize_DE_DELETE_ALERT", "You are not allowed to delete that view from the list.");
define("_formulize_DE_CONF_DELVIEW", "You are about to delete this view!  Please confirm.");

//calculations
define("_formulize_DE_PICKCALCS", "Pick the calculations you want");
define("_formulize_DE_MODCALCS", "Modify Calculations");
define("_formulize_DE_CALC_COL", "Data to use for the calculations:");
define("_formulize_DE_CALCSUB", "Add Calculation(s) to list");
define("_formulize_DE_CALC_CALCS", "Calculations to perform on the column:");
define("_formulize_DE_CALCGO", "Perform Requested Calculations");
define("_formulize_DE_REQDCALCS", "Requested Calculations:");
define("_formulize_DE_CALCALL", "Include blanks/zeros"); 
define("_formulize_DE_CALCNOBLANKS", "Exclude blanks/zeros");
define("_formulize_DE_CALCONLYBLANKS", "Include only blanks/zeros");
define("_formulize_DE_CALC_GROUPING", "Group results by...");
define("_formulize_DE_NOGROUPING", "Do not group results");
define("_formulize_DE_GROUPBYCREATOR", "Group by: User who made entry");
define("_formulize_DE_GROUPBYCREATEDATE", "Group by: Creation date");
define("_formulize_DE_GROUPBYMODIFIER", "Group by: User who last modified entry");
define("_formulize_DE_GROUPBYMODDATE", "Group by: Last modification date");
define("_formulize_DE_CALC_LISTDISPLAY", "Only display calculations<br>(hide the list of entries)");
define("_formulize_DE_CALC_CREATOR", "User who made entry");
define("_formulize_DE_CALC_CREATEDATE", "Creation date");
define("_formulize_DE_CALC_MODIFIER", "User who last modified entry");
define("_formulize_DE_CALC_MODDATE", "Last modification date");
define("_formulize_DE_REMOVECALC", "Remove this calculation from the list");
define("_formulize_DE_CALC_BTEXT", "Which entries?");
define("_formulize_DE_CALC_GTEXT", "Group Results?");
define("_formulize_DE_CALCHEAD", "Calculation Results");
define("_formulize_DE_CALC_SUM", "Sum Total");
define("_formulize_DE_CALC_AVG", "Averages");
define("_formulize_DE_CALC_MIN", "Minimum Value");
define("_formulize_DE_CALC_MAX", "Maximum Value");
define("_formulize_DE_CALC_COUNT", "Count Entries");
define("_formulize_DE_CALC_PER", "Percentage Breakdown");
define("_formulize_DE_EXCLBLANKS", "Excludes blanks/zeros");
define("_formulize_DE_INCLBLANKS", "Includes blanks/zeros");
define("_formulize_DE_INCLONLYBLANKS", "Includes <i>only</i> blanks/zeros");
define("_formulize_DE_CALC_MEAN", "Mean");
define("_formulize_DE_CALC_MEDIAN", "Median");
define("_formulize_DE_CALC_MODE", "Mode");
define("_formulize_DE_CALC_NUMENTRIES", "Number of Entries");
define("_formulize_DE_CALC_NUMUNIQUE", "Number of Unique Values");
define("_formulize_DE_PER_ITEM", "Item");
define("_formulize_DE_PER_COUNT", "Count");
define("_formulize_DE_PER_PERCENT", "Percentage");
define("_formulize_DE_DATAHEADING", "List of Entries");

//ADVANCED SEARCH:
define("_formulize_DE_BUILDQUERY", "Build your query");
define("_formulize_DE_AS_FIELD", "Search this field:");
define("_formulize_DE_AS_OPTERM", "With this operator and term:");
define("_formulize_DE_AS_ADD", "Add this search to the query");
define("_formulize_DE_AS_ADDOTHER", "Other items you can add:");
define("_formulize_DE_AS_REMOVE", "Remove last item from the query");
define("_formulize_DE_ADVSEARCH", "Advanced search");
define("_formulize_DE_ADVSEARCH_ERROR", "There was a \"parse error\" in the advanced search query you specified.  Most often, this is caused by not having an AND or an OR in between two search terms.  Another common cause is not having ( ) arranged correctly, or not having an equal number of opening and closing ones.");
define("_formulize_DE_SEARCHGO", "Perform Requested Query");
define("_formulize_DE_AS_QUERYSOFAR", "Requested Query So Far:");
define("_formulize_DE_CANCELASEARCH", "Cancel this search");
define("_formulize_DE_MOD_ADVSEARCH", "Modify search");

//CHANGE SCOPE:
define("_formulize_DE_ADVSCOPE", "Advanced scope");
define("_formulize_DE_PICKASCOPE", "Choose the groups to use for the scope");
define("_formulize_DE_AVAILGROUPS", "Available groups:");
define("_formulize_DE_USETHISSCOPE", "Use these groups as the scope");
define("_formulize_DE_AS_ENTRIESBY", "Entries by: ");
define("_formulize_DE_AS_PICKGROUPS", "Entries by all users in...[pick groups]");
define("_formulize_DE_PICKDIFFGROUP", "Pick diff. groups");
define("_formulize_DE_NOGROUPSPICKED", "Please click on one or more groups from the list above.  Use CTRL-click to select more than one group.");


//SAVE VIEW:
define("_formulize_DE_SAVEVIEW", "Options for saving this view");
define("_formulize_DE_SAVE_UPDATE", "Update: ");
define("_formulize_DE_SAVE_REPLACE", "Replace: ");
define("_formulize_DE_SAVE_LASTLOADED", "most recently loaded view");
define("_formulize_DE_SAVE_AS", "[Save a new view]");
define("_formulize_DE_SAVE_USECURRENT", "Use the current view settings to...");
define("_formulize_DE_SAVE_SCOPE", "When this view is selected, only show entries made by...");
// this help line not used at the moment
define("_formulize_DE_SAVE_SCOPE_HELP", "To select specific groups, close this window and choose the [pick groups] option from the Current View.  Then click the <i>Save</i> button again.");
define("_formulize_DE_SAVE_SCOPE1", "The person viewing it and no one else");
define("_formulize_DE_SAVE_SCOPE2", "Everyone in the viewer's groups");
define("_formulize_DE_SAVE_SCOPE3", "Everyone in all groups (no limit)");
define("_formulize_DE_SAVE_SCOPE4", "Everyone in: ");
define("_formulize_DE_SAVE_SCOPE1_SELF", "Me");
define("_formulize_DE_SAVE_SCOPE2_SELF", "Everyone in my groups");
define("_formulize_DE_SAVE_SCOPE3_SELF", "Everyone in all groups (no limit)");
define("_formulize_DE_SAVE_SCOPE4_SELF", "Everyone in: ");
define("_formulize_DE_SAVE_NOSPECIFICS", "[no specific groups picked]");
define("_formulize_DE_SAVE_PUBGROUPS", "Publish this view to these groups");
define("_formulize_DE_SAVE_NOPUB", "[Do not publish this view]");
define("_formulize_DE_SAVE_LOCKCONTROLS", "Lock the controls?");
define("_formulize_DE_SAVE_LOCKCONTROLS_HELP1", "<span style=\"font-weight: bold;\">About locking the controls:</span>");
define("_formulize_DE_SAVE_LOCKCONTROLS_HELP2", "<span style=\"font-weight: normal;\">Certain actions, such as advanced searches, calculations and changing columns, can reveal more information to the viewer than what is presented by default.  When the controls are locked, and this view is selected by viewers who don't otherwise have access to these entries, then all actions that can reveal more information are turned off.  Locking the controls has no effect on viewers who can normally view all the details by themselves.</span>");
define("_formulize_DE_SAVE_BUTTON", "Save the current view settings with these options");
define("_formulize_DE_SAVE_NEWPROMPT", "Please type a name for this new view:");




define("_formulize_CAL_ADD_ITEM", "Click to add a new item on this day.");

define("_formulize_CAL_MONTH_01", "January");
define("_formulize_CAL_MONTH_02", "February");
define("_formulize_CAL_MONTH_03", "March");
define("_formulize_CAL_MONTH_04", "April");
define("_formulize_CAL_MONTH_05", "May");
define("_formulize_CAL_MONTH_06", "June");
define("_formulize_CAL_MONTH_07", "July");
define("_formulize_CAL_MONTH_08", "August");
define("_formulize_CAL_MONTH_09", "September");
define("_formulize_CAL_MONTH_10", "October");
define("_formulize_CAL_MONTH_11", "November");
define("_formulize_CAL_MONTH_12", "December");

define("_formulize_CAL_WEEK_1", "Sunday");
define("_formulize_CAL_WEEK_2", "Monday");
define("_formulize_CAL_WEEK_3", "Tuesday");
define("_formulize_CAL_WEEK_4", "Wednesday");
define("_formulize_CAL_WEEK_5", "Thursday");
define("_formulize_CAL_WEEK_6", "Friday");
define("_formulize_CAL_WEEK_7", "Saturday");
define("_formulize_CAL_WEEK_1_3ABRV", "Sun");
define("_formulize_CAL_WEEK_2_3ABRV", "Mon");
define("_formulize_CAL_WEEK_3_3ABRV", "Tue");
define("_formulize_CAL_WEEK_4_3ABRV", "Wed");
define("_formulize_CAL_WEEK_5_3ABRV", "Thu");
define("_formulize_CAL_WEEK_6_3ABRV", "Fri");
define("_formulize_CAL_WEEK_7_3ABRV", "Sat");
?>
