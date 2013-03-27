
<style>
.formulize_button {
width:120px;
}
#formulize_addMultiButton {
width:130px;
}
#formulize_addButton {
width:130px;
}
</style>

<div id = topHeader>
  <?php
  $pageTitle = $screen->getVar('title');
  echo "<h2 style=font-size:25px;color:#6a6a6a>$pageTitle</h2>";
  ?>
</div>

<div id = wrapper style=overflow:auto>
<div id = fillInForm style=float:left;width:32%>
<div style=font-weight:bold;color:#6a6a6a>Fill In This Form:</div>

<?php
//Begin Fill In Form buttons
if($thisButtonCode = $buttonCodeArray['addButton']) {
  print "<div>$thisButtonCode</div>";
}
if($thisButtonCode = $buttonCodeArray['addMultiButton']) {
  print "<div>$thisButtonCode</div>";
}
print "</div>";

// Begin Actions buttons
$actionsList = array();
if($thisButtonCode = $buttonCodeArray['changeColsButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['resetViewButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['saveViewButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['deleteViewButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['selectAllButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['clearSelectButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['cloneButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['deleteButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['calcButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['exportButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['importButton']) {
  array_push($actionsList, $thisButtonCode);
} 
if($thisButtonCode = $buttonCodeArray['notifButton']) {
  array_push($actionsList, $thisButtonCode);
} 

print "<div id = actions style=float:left;width:68%>
<div style=font-weight:bold;color:#6a6a6a>Actions:</div>";

print "<div id=actionList1 style=float:left;padding-right:20px>";
if(count($actionsList) >= "4") {
  for($x=0;$x<"4";$x++) {
    print "<div>$actionsList[$x]</div>";
  }
} else {
  for($x=0;$x<count($actionsList);$x++) {
    print "<div>$actionsList[$x]</div>";
  }
}
  print "</div>"; /* End first list */

print "<div id=actionList2 style=float:left;padding-right:20px>";
if(count($actionsList) >= 8) {
  for($x=4;$x<"8";$x++) {
    print "<div>$actionsList[$x]</div>";
  }
} else {
  for($x=4;$x<count($actionsList);$x++) {
    print "<div>$actionsList[$x]</div>";
  }
}
print "</div>"; /* End second list */

print "<div id=actionList3 style=float:left;padding-right:20px>";
for($x=8;$x<count($actionsList);$x++) {
  print "<div>$actionsList[$x]</div>";
}

print "</div>"; /* End third list */

print "</div>"; /* End actions section */

if ($currentViewList = $buttonCodeArray['currentViewList']) { 
  print "<div id=navDropdown style=font-weight:bold;color:#6a6a6a;padding-bottom:20px>$currentViewList</div>";
}

print "</div>"; /* End */