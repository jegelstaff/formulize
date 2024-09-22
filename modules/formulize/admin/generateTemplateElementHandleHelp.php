<?php


//set options for all elements in entire framework
  //also, collect the handles from a framework if any, and prep the list of possible handles/ids for the list template
	if(isset($allFidsToUse)) {
		$allFids = $allFidsToUse;
  } elseif ($selectedFramework and isset($frameworks[$selectedFramework])) {
		$linkedForms = checkForLinks($selectedFramework, array($form_id), $form_id);
		$allFids = $linkedForms['fids'] + $linkedForms['sub_fids'];
  } else {
    $allFids = array(0=>$form_id);
  }
  $thisFidObj = "";
  $allFidObjs = array();
  $elementOptionsFid = array();
  $elementOptions = array();
  $listTemplateHelp = array();
  $class = "odd";
  foreach($allFids as $thisFid) {
      unset($thisFidObj);
      $thisFidObj = $form_handler->get($thisFid, true); // true causes all elements to be included, even if they're not visible
      $allFidObjs[$thisFid] = $thisFidObj; // for use later on
      $thisFidElements = $thisFidObj->getVar('elementsWithData');
      $thisFidCaptions = $thisFidObj->getVar('elementCaptions');
      $thisFidColheads = $thisFidObj->getVar('elementColheads');
      $thisFidHandles = $thisFidObj->getVar('elementHandles');
      foreach($thisFidElements as $zz => $thisFidElement) {
        $elementHeading = $thisFidColheads[$zz] ? $thisFidColheads[$zz] : $thisFidCaptions[$zz];
        $elementOptions[$thisFidHandles[$zz]] = printSmart(trans(strip_tags($thisFidObj->title.': '.$elementHeading)), 125);
        // for passing to custom button logic, so we know all the element options for each form in framework
        $elementOptionsFid[$thisFid][$thisFidElement] = printSmart(trans(strip_tags($elementHeading)), 75);
        $class = $class == "even" ? "odd" : "even";
        $listTemplateHelp[$thisFidObj->title][] = "<tr><td class=$class><nobr><b>" . printSmart(trans(strip_tags($elementHeading)), 75) . "</b></nobr></td><td class=$class><nobr>".$thisFidHandles[$zz]."</nobr></td></tr>";
      }
  }
