<?php
/*
 * Pages
 */

function buildPagesSummary()
{
	global $xoopsDB;

    $p_title = _AM_PAGEWORKS_TITLE_ADMIN;
    print "<h4 style='text-align:left;'>$p_title</h4>";


    // begin - Nov 6, 2005 - jpc - Freeform Solutions

	// added alpha sort
	switch($_GET['sort']) {
		case "id":
			$result = selectPages("id");
			break;
		case "name":
		default:
			$result = selectPages("alpha");
			break;
	}

    // end - Nov 6, 2005 - jpc - Freeform Solutions


    if($result)
    {
    	$even = true;
?>
    <a href="index.php?op=addpage">
        <?php echo "Click to add a new page"; ?></a><br>
	<p>Click 'Name' or 'Id' to change the sorting order</p>
	<table width="100%" class="outer">
	<tr class="head">
    	<td width="35%"><?php print "<a href='" . XOOPS_URL . "/modules/pageworks/admin/index.php?sort=name'>Name</a>"; ?></td>
<?php
	// begin - Nov 6, 2005 - jpc - Freeform Solutions 
?>
    	<td width="40%"><?php echo "Title"; ?></td>
    	<td><?php print "<a href='" . XOOPS_URL . "/modules/pageworks/admin/index.php?sort=id'>Id</a>"; ?></td>
        <td></td>
<?php
	// end - Nov 6, 2005 - jpc - Freeform Solutions 
?>
	</tr>
<?php
		while($resultArray = $xoopsDB->fetchArray($result)) 
        {
	        $thisPath = "index.php?page_id=" . $resultArray["page_id"];
?>
	<tr class="<?php echo ($even) ? "even" : "odd" ?>" valign="top">
    	<td width="35%"><?php echo $resultArray["page_name"]; ?></td>
<?php
	// begin - Nov 6, 2005 - jpc - Freeform Solutions 
?>
    	<td width="40%"><?php echo $resultArray["page_title"]; ?></td>
    	<td><?php echo $resultArray['page_id']; ?></td>
    	<td><nobr>
            <a href="<?php echo $thisPath; ?>&op=editpage">
        	<?php echo "Edit"; ?></a>
            &nbsp;&nbsp;&nbsp; 
        	<a href="<?php echo $thisPath; ?>&op=deletepage"
        	onclick="if(confirm('Are you sure?')) { return true; } else { return false; }">
        	<?php echo "Delete"; ?></a>
            </nobr></td>
<?php    
	// end - Nov 6, 2005 - jpc - Freeform Solutions 
?>
	</tr>
<?php
			$even = !($even);
		}
?>
	</table>
<?php
    }
}


function buildPageForm($nostrip="")
{
	global $param_op, $myts;

	include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";

    $page_id = @$_REQUEST["page_id"];
	$page = "";
	if(!(empty($page_id)))
    {
    	$page = "&page_id=" . $page_id;
	}        
	$form = new XoopsThemeForm("Page", "pageform", "index.php?op=$param_op" . $page);

	if(!$nostrip) {
		$sname = stripslashes($_REQUEST["page_name"]);
		$stitle = stripslashes($_REQUEST["page_title"]);
		$stemp = stripslashes($_REQUEST["page_template"]);
      } else {
		$sname = $_REQUEST["page_name"];	
		$stitle = $_REQUEST["page_title"];
		$stemp = $_REQUEST["page_template"];
	}

	$stemp = $myts->htmlSpecialChars($stemp);

    $page_name = new XoopsFormText("Name", "page_name", 20, 255, $sname);
    $page_title = new XoopsFormText("Title", "page_title", 40, 255, $stitle);
    $page_template = new XoopsFormTextArea("Template", "page_template", $stemp, 20, 85); 
	$page_template->setExtra("wrap=off");
	$page_searchable = new XoopsFormRadioYN("Searchable?", "page_searchable", $_REQUEST["page_searchable"]);
	$page_html_from_db = new XoopsFormRadioYN("Allow HTML from DB?", "page_html_from_db", $_REQUEST["page_html_from_db"]);


	$form->addElement($page_name);
	$form->addElement($page_title);
	$form->addElement($page_template);
	$form->addElement($page_searchable);
	$form->addElement($page_html_from_db);

    
	$submit_button = new XoopsFormButton("", "savebutton", "Save", "submit");
	$cancel_button = new XoopsFormButton("", "cancelbutton", "Cancel", "reset");
	$back_button = new XoopsFormButton("", "backbutton", "Back", "button");
    $back_button->setExtra(" onclick='window.location.href=\"index.php\"'");

	$operations_tray = new XoopsFormElementTray("", "");
	$operations_tray->addElement($submit_button);
	$operations_tray->addElement($cancel_button);
	$operations_tray->addElement($back_button);
    
	$form->addElement($operations_tray);
	$form->display();

	if($param_op != "addpage")
    {
    	buildFrameworksSummary($page_id);    
	}
        
}


function selectPages($order="id")
{
	global $xoopsDB;

     $sql = "SELECT page_id, page_name, page_title, page_template, page_searchable, page_html_from_db" .
     	" FROM " . $xoopsDB->prefix("pageworks_pages");

	if($order=="alpha") { 
		$sql .= " ORDER BY page_name"; 
	} else {
		$sql .= " ORDER BY page_id"; 
	}

	$sql .= ";";
	
	return $xoopsDB->query($sql);
}

function selectPage($page_id)
{
	global $xoopsDB;

     $sql = "SELECT page_name, page_title, page_template, page_searchable, page_html_from_db" .
     	" FROM " . $xoopsDB->prefix("pageworks_pages") .  
        " WHERE page_id = " . $page_id . ";";
	
	return $xoopsDB->query($sql);
}

function insertPage($page_name, $page_title, $page_template, $page_searchable, $page_html_from_db)
{
	global $xoopsDB, $myts;

	if(get_magic_quotes_gpc() == 0) {
		$page_name = mysql_real_escape_string($page_name);
		$page_title = mysql_real_escape_string($page_title);
		$page_template = mysql_real_escape_string($page_template);
	} 

     $sql = "INSERT INTO " . $xoopsDB->prefix("pageworks_pages") . 
     	" (page_name, page_title, page_template, page_searchable, page_html_from_db) VALUES ('" .
        $page_name . "', '" . $page_title . "', '" . $myts->htmlSpecialChars($page_template) . "', '" . $page_searchable . "', '" . $page_html_from_db . "');";

	if($xoopsDB->queryF($sql))
    {
			$pageId = $xoopsDB->getInsertId();
			// set permission for webmasters to view the page
			$module_handler =& xoops_gethandler('module');
      $pageworksModule =& $module_handler->getByDirname("pageworks");
			$gperm_handler = &xoops_gethandler('groupperm');
			$gperm_handler->addRight("view", $pageId, XOOPS_GROUP_ADMIN, $pageworksModule->getVar('mid'));
			return $pageId;    
    }
    else
    {
		return false;    
    }
}

function updatePage($page_name, $page_title, $page_template, $page_id, $page_searchable, $page_html_from_db)
{

	global $xoopsDB, $myts;

	if(get_magic_quotes_gpc() == 0) {
		$page_name = mysql_real_escape_string($page_name);
		$page_title = mysql_real_escape_string($page_title);
		$page_template = mysql_real_escape_string($page_template);
	} 

     $sql = "UPDATE " . $xoopsDB->prefix("pageworks_pages") . 
     	" SET page_name = '" . $page_name . "', page_title = '" . $page_title . 
        "', page_template = '" . $myts->htmlSpecialChars($page_template) . 
	  "', page_searchable = '" . $page_searchable . "', page_html_from_db = '" . $page_html_from_db . "'".
        " WHERE page_id = " . $page_id . ";";

	$xoopsDB->query($sql);         
}

function deletePage($page_id)
{
	global $xoopsDB;

     $sql = "DELETE FROM " . $xoopsDB->prefix("pageworks_pages") . 
     	" WHERE page_id = " . $page_id . ";";

	$xoopsDB->queryF($sql);         
}
?>