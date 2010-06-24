<?php
define("_CO_ICMS_AUTOTASKS_NAME", "Task Name");
define("_CO_ICMS_AUTOTASKS_NAME_DSC", "Enter the task name.");
define("_CO_ICMS_AUTOTASKS_CODE", "Source Code");
define("_CO_ICMS_AUTOTASKS_CODE_DSC", "Here you can write PHP code to be executed as a task.<p style='color:red'>Without &lt;?php and ?&gt;</p><br /><br />mainfile.php will already be included.<br />Use <i>global \$xoopsDB</i> to make use of the database object.");
define("_CO_ICMS_AUTOTASKS_REPEAT", "Repeat");
define("_CO_ICMS_AUTOTASKS_REPEAT_DSC", "How often do you want this task to be repeated? Enter '0' if you want to create a forever running task.");
define("_CO_ICMS_AUTOTASKS_INTERVAL", "Interval");
define("_CO_ICMS_AUTOTASKS_INTERVAL_DSC", "Task execution interval (in minutes).<br /><br />60: once per hour<br />1440: once per day");
define("_CO_ICMS_AUTOTASKS_ONFINISH", "Auto-Delete");
define("_CO_ICMS_AUTOTASKS_ONFINISH_DSC", "Do you want this task to be deleted after the specified amount of repeats? Select 'Yes' if you want to remove this task from the task list automatically or 'No' to switch this task into pause mode.<br />This only applies to repeat greater than '0'.");
define("_CO_ICMS_AUTOTASKS_ENABLED", "Enabled");
define("_CO_ICMS_AUTOTASKS_ENABLED_DSC", "Select 'Yes' to enable this task.");
define("_CO_ICMS_AUTOTASKS_TYPE", "Type");
define("_CO_ICMS_AUTOTASKS_LASTRUNTIME", "Last Execution Time");

define("_CO_ICMS_AUTOTASKS_CREATE", "Create new task");
define("_CO_ICMS_AUTOTASKS_EDIT", "Edit task");

define("_CO_ICMS_AUTOTASKS_CREATED", "Task added");
define("_CO_ICMS_AUTOTASKS_MODIFIED", "Task modified");

define("_CO_ICMS_AUTOTASKS_NOTYETRUNNED", "Not yet executed");

define("_CO_ICMS_AUTOTASKS_TYPE_CUSTOM", "User");
define("_CO_ICMS_AUTOTASKS_TYPE_ADDON", "System");

define("_CO_ICMS_AUTOTASKS_FOREVER", "forever");

define("_CO_ICMS_AUTOTASKS_INIT_ERROR", "Error: Can't  initialize selected auto tasks subsystem.");

define("_CO_ICMS_AUTOTASKS_SOURCECODE_ERROR", "Error in Autotask SourceCode: Can't execute Autotask");
?>