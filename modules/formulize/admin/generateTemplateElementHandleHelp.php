<?php


//set options for all elements in entire framework
  //also, collect the handles from a framework if any, and prep the list of possible handles/ids for the list template
  if ($selectedFramework and isset($frameworks[$selectedFramework])) {
      $allFids = $frameworks[$selectedFramework]->getVar('fids');
  } else {
      $allFids = array(0=>$form_id);
  }
  $thisFidObj = "";
  $allFidObjs = array();
  $elementOptionsFid = array();
  $listTemplateHelp = array();
  $class = "odd";
  foreach($allFids as $thisFid) {
      unset($thisFidObj);
      $thisFidObj = $form_handler->get($thisFid, true); // true causes all elements to be included, even if they're not visible
      $allFidObjs[$thisFid] = $thisFidObj; // for use later on
      $thisFidElements = $thisFidObj->getVar('elements');
      $thisFidCaptions = $thisFidObj->getVar('elementCaptions');
      $thisFidColheads = $thisFidObj->getVar('elementColheads');
      $thisFidHandles = $thisFidObj->getVar('elementHandles');
      foreach($thisFidElements as $i => $thisFidElement) {
        $elementHeading = $thisFidColheads[$i] ? $thisFidColheads[$i] : $thisFidCaptions[$i];
        $elementOptions[$thisFidHandles[$i]] = printSmart(trans(strip_tags($elementHeading)), 75);
        // for passing to custom button logic, so we know all the element options for each form in framework
        $elementOptionsFid[$thisFid][$thisFidElement] = printSmart(trans(strip_tags($elementHeading)), 75);
        $class = $class == "even" ? "odd" : "even";
        $listTemplateHelp[] = "<tr><td class=$class><nobr><b>" . printSmart(trans(strip_tags($elementHeading)), 75) . "</b></nobr></td><td class=$class><nobr>".$thisFidHandles[$i]."</nobr></td></tr>";
      }
  }