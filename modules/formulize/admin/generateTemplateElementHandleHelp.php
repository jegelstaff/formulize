<?php


//set options for all elements in entire framework
  //also, collect the handles from a framework if any, and prep the list of possible handles/ids for the list template
	if(isset($allFidsToUse)) {
		$allFids = $allFidsToUse;
		$allLinkedFids = $allFids;
  } elseif ($selectedFramework and isset($frameworks[$selectedFramework])) {
		$allFids = $frameworks[$selectedFramework]->getVar('fids');
		$linkedForms = checkForLinks($selectedFramework, array($form_id), $form_id);
		$allLinkedFids = array_merge($linkedForms['fids'], (array) $linkedForms['sub_fids']);
  } else {
    $allFids = array(0=>$form_id);
		$allLinkedFids = $allFids;
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
		$thisUserAccountElements = $thisFidObj->getVar('userAccountElements');
		foreach($thisFidHandles as $elementId => $thisFidElementHandle) {
			$elementHeading = $thisFidColheads[$elementId] ? $thisFidColheads[$elementId] : $thisFidCaptions[$elementId];
			// for now, only allow elements with data into elementOptionsFid - user account elements probably need more features added in order to be supported everywhere elementOptionsFid shows up
			if(isset($thisFidElements[$elementId])) {
				// Base on all fids in relationship - for passing to custom button logic, so we know all the element options for each form in framework
				$elementOptionsFid[$thisFid][$elementId] = printSmart(trans(strip_tags($elementHeading)), 75);
			}
			// Base on only fids linked to mainform in relationship...
			if(in_array($thisFid, $allLinkedFids)) {
				if(isset($thisFidElements[$elementId]) OR isset($thisUserAccountElements[$elementId])) { // for the list template help, we want to include user account elements even if they don't have data, since we want to show that they're available as options in the template
					$elementOptions[$thisFidHandles[$elementId]] = (isset($allFidsToUse) OR $selectedFramework) ? printSmart(trans(strip_tags($thisFidObj->title.': '.$elementHeading)), 125) : printSmart(trans(strip_tags($elementHeading)), 40);
					$class = $class == "even" ? "odd" : "even";
					$listTemplateHelp[$thisFidObj->title][] = "<tr><td class=$class><nobr><b>" . printSmart(trans(strip_tags($elementHeading)), 75) . "</b></nobr></td><td class=$class><nobr>".$thisFidElementHandle."</nobr></td></tr>";
				}
			}
		}
  }
