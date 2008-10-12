<?php
/*
 * Frameworks
 */

function buildFrameworksSummary($page_id)
{
	global $xoopsDB;

?>
    <p><? echo "Frameworks used in this page:"; ?></p>
<?
	$result = selectFrameworks($page_id);
    if($result)
    {
    	$even = true;
?>
	<table width="100%" class="outer">
	<tr class="head">
        <td></td>
    	<td><? echo "Name"; ?></td>
    	<td><? echo "Main Form"; ?></td>
    	<td><? echo "Filters"; ?></td>
	<td><? echo "Sort?"; ?></td>
    	<td><? echo "Sort Field"; ?></td>
    	<td><? echo "Output Name"; ?></td>
	<td><? echo "Search Title"; ?></td>
	</tr>
<?
		while($resultArray = $xoopsDB->fetchArray($result)) 
        {
			//var_dump($resultArray);	        
            
		$searchTitleResult = getSearchTitle($resultArray["pf_id"]);
		if($searchTitleResult) {
			$array = $xoopsDB->fetchArray($searchTitleResult);
			$searchTitle = $array['fe_handle'];
		}

            $thisPath = "index.php?page_id=" . $page_id .
				"&framework_id=" . $resultArray["pf_id"];            
?>
	<tr class="<? echo ($even) ? "even" : "odd" ?>">
    	<td><nobr><a href="<? echo $thisPath; ?>&op=deleteframework"
        	onclick="if(confirm('Are you sure?')) { return true; } else { return false; }">
        	<? echo "Delete"; ?></a>&nbsp;&nbsp;&nbsp; 
            <a href="<? echo $thisPath; ?>&op=editframework">
        	<? echo "Edit"; ?></a></nobr></td>
    	<td><? echo $resultArray["pf_framework_frame_name"]; ?></td>
    	<td><? echo $resultArray["pf_mainform_ff_handle"]; ?></td>
    	<td><? echo $resultArray["pf_filters"]; ?></td>
	<td><? 
		if($resultArray["pf_sortable"] == "1") { echo "Yes"; }
		if($resultArray["pf_sortable"] == "0") { echo "No"; }
		?></td>
    	<td><? echo $resultArray["pf_sort_fe_handle"]; ?></td>
    	<td><? echo $resultArray["pf_output_name"]; ?></td>
    	<td><? echo $searchTitle; ?></td>
	</tr>
<?
			$even = !($even);
		}
?>
	</table>
<?
    }
?>
	<br>
    <a href="index.php?op=addframework&page_id=<? echo $page_id; ?>"><? echo "Add another framework"; ?></a>
<?    
}


function buildFrameworkForm()
{
	global $param_op;

    global $xoopsDB;


	include_once XOOPS_ROOT_PATH."/class/xoopsformloader.php";

    $framework_id = @$_REQUEST["framework_id"];
	$page = "";
	if(empty($framework_id))
    {
    	$page = "&page_id=" . $_REQUEST["page_id"];
	}
    else
    {        
    	$page = "&page_id=" . $_REQUEST["page_id"] . "&framework_id=" . $_REQUEST["framework_id"];
	}        
	$form = new XoopsThemeForm("Framework", "frameworkform", "index.php?op=$param_op" . $page);

    
    $framework = new XoopsFormSelect("Framework", "pf_framework", $_REQUEST["pf_framework"]);
	$result = selectFormulizeFrameworks();
    if($result)
    {
		$addframeworkFirstItem = true;
        
	    while($resultArray = $xoopsDB->fetchArray($result)) 
	    {
	        if($param_op == "addframework"
            	&& $addframeworkFirstItem == true
                && empty($_REQUEST["pf_framework"]))
	        {
				$_REQUEST["pf_framework"] = $resultArray["frame_id"];
	        }
            $addframeworkFirstItem = false;         

	        $framework->addOption($resultArray["frame_id"], $resultArray["frame_name"]);
	    }
	}
    $framework->setExtra(" onchange='frameworkform.submit();'");

    
    $main_form = new XoopsFormSelect("Main Form", "pf_mainform", @$_REQUEST["pf_mainform"]);
	$result = selectFormulizeFrameworkForms($_REQUEST["pf_framework"]);
    //echo "main_form " . $_REQUEST["pf_framework"];
	//$result = selectFormulizeFrameworkForms(@$_REQUEST["pf_framework"]);
    if($result)
    {
		$addframeworkFirstItem = true;
        
	    while($resultArray = $xoopsDB->fetchArray($result)) 
	    {
	        if($param_op == "addframework"
            	&& $addframeworkFirstItem == true
                && empty($_REQUEST["pf_mainform"]))
	        {
				$_REQUEST["pf_mainform"] = $resultArray["ff_form_id"];
	        }
            $addframeworkFirstItem = false;         

	        $main_form->addOption($resultArray["ff_form_id"], $resultArray["ff_handle"]);
	    }
	}
    else
    {
    	echo "Error: unable to find the Framework in the database<br>";
        die;
	}        
    $main_form->setExtra(" onchange='frameworkform.submit();'");

	$form->addElement($framework);
	$form->addElement($main_form);

    $page_filters = new XoopsFormTextArea("Filters", "pf_filters", @$_REQUEST["pf_filters"], 10); 

	$form->addElement($page_filters);

	$sortable = new XoopsFormRadioYN("Sort results?", "pf_sortable", $_REQUEST["pf_sortable"]);
	$form->addElement($sortable);

	$sortdir = new XoopsFormRadio("Sort direction", "pf_sortdir", $_REQUEST["pf_sortdir"]);
	$sortdir->addOption("0", "Ascending (a..z)");
	$sortdir->addOption("1", "Descending (z..a)");
	$form->addElement($sortdir);

    $sort = new XoopsFormSelect("Sort", "pf_sort", @$_REQUEST["pf_sort"]);
	$result = selectFormulizeFrameworkElements($_REQUEST["pf_framework"], $_REQUEST["pf_mainform"]);
    //echo "sort " . $_REQUEST["pf_framework"] . ", " . $_REQUEST["pf_mainform"];
	//$result = selectFormulizeFrameworkElements(@$_REQUEST["pf_framework"], @$_REQUEST["pf_mainform"]);
    if($result)
    {

		$addframeworkFirstItem = true;

	    while($resultArray = $xoopsDB->fetchArray($result)) 
	    {
	        if($param_op == "addframework"
            	&& $addframeworkFirstItem == true
                && empty($_REQUEST["pf_sort"]))
	        {
				$_REQUEST["pf_sort"] = $resultArray["fe_id"];
	        }
            $addframeworkFirstItem = false;         

	        $sort->addOption($resultArray["fe_id"], $resultArray["fe_handle"]);
	    }
	}

    $form->addElement($sort);

    $output_name = new XoopsFormText("Output Name", "pf_output_name", 40, 255, @$_REQUEST["pf_output_name"]);
	$form->addElement($output_name);

    $search_title = new XoopsFormSelect("Search Title", "pf_search_title", @$_REQUEST["pf_search_title"]);
	$result = selectFormulizeFrameworkElements($_REQUEST["pf_framework"], $_REQUEST["pf_mainform"]);
    //echo "sort " . $_REQUEST["pf_framework"] . ", " . $_REQUEST["pf_mainform"];
	//$result = selectFormulizeFrameworkElements(@$_REQUEST["pf_framework"], @$_REQUEST["pf_mainform"]);
    if($result)
    {

		$addframeworkFirstItem = true;

	    while($resultArray = $xoopsDB->fetchArray($result)) 
	    {
	        if($param_op == "addframework"
            	&& $addframeworkFirstItem == true
                && empty($_REQUEST["pf_search_title"]))
	        {
				$_REQUEST["pf_search_title"] = $resultArray["fe_id"];
	        }
            $addframeworkFirstItem = false;         

	        $search_title->addOption($resultArray["fe_id"], $resultArray["fe_handle"]);
	    }
	}

    $form->addElement($search_title);


    
	$submit_button = new XoopsFormButton("", "savebutton", "Save", "submit");
	$cancel_button = new XoopsFormButton("", "cancelbutton", "Cancel", "reset");
	$back_button = new XoopsFormButton("", "backbutton", "Back", "button");
	$back_button->setExtra(" onclick='window.location.href=\"index.php?page_id=" . $_REQUEST["page_id"] . "&op=editpage\"'");


	$operations_tray = new XoopsFormElementTray("", "");
	$operations_tray->addElement($submit_button);
	$operations_tray->addElement($cancel_button);
	$operations_tray->addElement($back_button);
    
	$form->addElement($operations_tray);
	$form->display();

}


function selectFrameworks($page_id)
{
	global $xoopsDB;

     $sql = "SELECT pf_id, pf_framework, pf_mainform," .
     	" pf_filters, pf_sort, pf_output_name, pf_sortable, " .
     	" frame_name AS pf_framework_frame_name," .
     	" ff_handle AS pf_mainform_ff_handle," .
     	" fe_handle AS pf_sort_fe_handle" .
     	" FROM " . $xoopsDB->prefix("pageworks_frameworks") .  
     	", " . $xoopsDB->prefix("formulize_frameworks") .   
     	", " . $xoopsDB->prefix("formulize_framework_forms") . 
     	", " . $xoopsDB->prefix("formulize_framework_elements") . 
        " WHERE (" . 
		$xoopsDB->prefix("pageworks_frameworks") . ".pf_framework = " .          
        $xoopsDB->prefix("formulize_frameworks") . ".frame_id) AND (" .
		$xoopsDB->prefix("pageworks_frameworks") . ".pf_framework = " .          
        $xoopsDB->prefix("formulize_framework_forms") . ".ff_frame_id AND " .
	 	$xoopsDB->prefix("pageworks_frameworks") . ".pf_mainform = " .
	  $xoopsDB->prefix("formulize_framework_forms") . ".ff_form_id) AND (" .
		$xoopsDB->prefix("pageworks_frameworks") . ".pf_sort = " . 
	  $xoopsDB->prefix("formulize_framework_elements") . ".fe_id)" .
        " AND " . $xoopsDB->prefix("pageworks_frameworks") . ".pf_page_id = " . $page_id . " ORDER BY pf_id;"; 


	return $xoopsDB->query($sql);         
}

function getSearchTitle($pf_id) {

	global $xoopsDB;

	$sql = "SELECT fe_handle FROM " . $xoopsDB->prefix("formulize_framework_elements") . ", " . $xoopsDB->prefix("pageworks_frameworks") . " WHERE " . $xoopsDB->prefix("formulize_framework_elements") . ".fe_id = " . $xoopsDB->prefix("pageworks_frameworks") . ".pf_search_title AND " . $xoopsDB->prefix("pageworks_frameworks") . ".pf_id = '" . $pf_id . "'";
	return $xoopsDB->query($sql);

}

function selectFramework($pf_id)
{
	global $xoopsDB;

     $sql = "SELECT pf_page_id, pf_framework, pf_mainform," .
     	" pf_filters, pf_sort, pf_output_name, pf_search_title, pf_sortable, pf_sortdir" .
     	" FROM " . $xoopsDB->prefix("pageworks_frameworks") . ", " . $xoopsDB->prefix("formulize_frameworks") . 
        " WHERE pf_id = " . $pf_id . ";";

	return $xoopsDB->query($sql);         
}

function insertFramework($pf_page_id, $pf_framework, $pf_mainform, $pf_filters, $pf_sort, $pf_output_name, $pf_search_title, $pf_sortable, $pf_sortdir)
{
	global $xoopsDB;

     $sql = "INSERT INTO " . $xoopsDB->prefix("pageworks_frameworks") . 
     	" (pf_page_id, pf_framework, pf_mainform," .
        " pf_filters, pf_sort, pf_output_name, pf_search_title, pf_sortable, pf_sortdir) VALUES (" .
        $pf_page_id . ", " . $pf_framework . ", " . 
        $pf_mainform . ", '" . $pf_filters . "', '" . 
        $pf_sort . "', '" . $pf_output_name . "', '" .
	  $pf_search_title . "', '" . $pf_sortable . "', '" .
	  $pf_sortdir . "');";

	if($xoopsDB->queryF($sql))
    {
		return $xoopsDB->getInsertId();    
    }
    else
    {
		return false;    
    }
             
}

function updateFramework($pf_page_id, $pf_framework, $pf_mainform, $pf_filters, $pf_sort, $pf_output_name, $pf_search_title, $pf_id, $pf_sortable, $pf_sortdir)
{
	global $xoopsDB;

     $sql = "UPDATE " . $xoopsDB->prefix("pageworks_frameworks") . 
     	" SET pf_page_id = " . $pf_page_id . 
        ", pf_framework = " . $pf_framework . 
        ", pf_mainform = " . $pf_mainform . "," .
     	" pf_filters = '" . $pf_filters . "', pf_sort = '" . $pf_sort . 
        "', pf_output_name = '" . $pf_output_name . "', pf_search_title = '" . $pf_search_title . "', pf_sortable = '" . $pf_sortable . "', pf_sortdir = '" . $pf_sortdir .
        "' WHERE pf_id = " . $pf_id . ";";

	return $xoopsDB->query($sql);         
}

function deleteFramework($pf_id)
{
	global $xoopsDB;

     $sql = "DELETE FROM " . $xoopsDB->prefix("pageworks_frameworks") . 
     	" WHERE pf_id = " . $pf_id . ";";

	$xoopsDB->queryF($sql);         
}


function selectFormulizeFrameworks()
{
	global $xoopsDB;

     $sql = "SELECT frame_id, frame_name" .
     	" FROM " . $xoopsDB->prefix("formulize_frameworks") . ";"; 

	return $xoopsDB->query($sql);         
}

function selectFormulizeFrameworkForms($ff_frame_id)
{
	global $xoopsDB;

     $sql = "SELECT ff_form_id, ff_handle" .
     	" FROM " . $xoopsDB->prefix("formulize_framework_forms") . 
     	" WHERE ff_frame_id = " . $ff_frame_id . ";"; 

	return $xoopsDB->query($sql);         
}

function selectFormulizeFrameworkElements($fe_frame_id, $fe_form_id)
{
	global $xoopsDB;

     $sql = "SELECT fe_id, fe_handle" .
     	" FROM " . $xoopsDB->prefix("formulize_framework_elements") . 
     	" WHERE fe_frame_id = " . $fe_frame_id . 
     	" AND fe_form_id = " . $fe_form_id . ";"; 

	return $xoopsDB->query($sql);         
}
?>