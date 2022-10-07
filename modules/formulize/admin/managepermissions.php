<?php

// only webmasters can interact with this page!
global $xoopsUser, $groupList;
if(!$xoopsUser OR !in_array(XOOPS_GROUP_ADMIN, $xoopsUser->getGroups())) {
    return;
}

$member_handler = xoops_gethandler('member');
$allGroups = $member_handler->getGroups();
$groupList = array();
foreach($allGroups as $group) {
    $groupid = $group->getVar('groupid');
    if($groupid != XOOPS_GROUP_USERS AND $groupid != XOOPS_GROUP_ADMIN AND $groupid != XOOPS_GROUP_ANONYMOUS){
        $groupList[$groupid] = $group->getVar('name');
    }
}

$targetGroupIds = array();
foreach($groupList as $id=>$groupName) {
    if(isset($_POST[$id])){
        $targetGroupIds[] = $id;
    }
}
$sourceGroupId = intval($_POST['managepermissions-source']);
$allOrFormulizeOnly = $_POST['formulize-or-all'] == 'formulize-perms' ? 'formulize' : 'all';

// NEED TO MAKE THIS RERUNNABLE WITHOUT DAMAGING ANYTHING
// NEED TO ADD MENU PERMISSIONS TO THIS!

if($sourceGroupId AND count($targetGroupIds)>0 AND $allOrFormulizeOnly) {
    
    foreach($targetGroupIds as $targetGroupId) {
        
        $allOrFormulizeFilter = $allOrFormulizeOnly == 'formulize' ? ' AND gperm_modid = '.getFormulizeModId() : '';
        
        // copy permission table data from source to targets        
        $sql = "INSERT INTO ".$xoopsDB->prefix("group_permission"). " (`gperm_groupid`, `gperm_itemid`, `gperm_modid`, `gperm_name`)
        SELECT $targetGroupId, gperm_itemid, gperm_modid, gperm_name FROM ".$xoopsDB->prefix("group_permission")." WHERE gperm_groupid = $sourceGroupId $allOrFormulizeFilter";
        if(!$res = $xoopsDB->query($sql)) {
            print "<p>Error: could not replicate permissions for Group Number $targetGroupId, which was supposed to be copied from Group Number $sourceGroupId</p>";
        }
        
        // copy per group visibility filters from source to targets
        $sql = "INSERT INTO ".$xoopsDB->prefix("formulize_group_filters")." (`fid`, `groupid`, `filter`)
        SELECT fid, $targetGroupId, filter FROM ".$xoopsDB->prefix("formulize_group_filters")." WHERE groupid = $sourceGroupId";
        if(!$res = $xoopsDB->query($sql)) {
            print "<p>Error: could not set the group filters for Group Number $targetGroupId, which were supposed to be copied from Group Number $sourceGroupId</p>";
        }
        
    }
    
    // get all the forms, since we have to check for various settings in each form that might reference groups
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $forms = $form_handler->getAllForms(true); // true causes all elements, even ones that show for no groups, to be included in the element list properties of the form objects
    
    foreach($forms as $thisForm) {
        // assign any per group groupscope settings, but we have to determine the corresponding groups first if possible!
        $formulize_permHandler = new formulizePermHandler($thisForm->getVar('id_form'));
        $currentGroups = $formulize_permHandler->getGroupScopeGroupIds($sourceGroupId);
        if(is_array($currentGroups)) {
            foreach($targetGroupIds as $targetGroupId) {
                $scopeGroups = array();
                foreach($currentGroups as $thisGroupId) {
                    if($thisGroupId == $sourceGroupId) {
                        $candidateGroupId = $targetGroupId;
                    } else {
                        // This process works for groups named Glenwood Counsellors, but not Counsellors Glenwood.
                        // ie: Common grouping identifier comes first, then the type of users within that grouping
                        // This could work the other way too, if we bothered to detect what was common between target and source, either the beginning or the end, and then acted accordingly, but since no sites I know of follow the backwards convention, haven't bothered. This was enough trouble! Argh.
                        $commonFrontPart = commonFrontPart($targetGroupId, $sourceGroupId);
                        $uncommonFrontPart = uncommonPart($targetGroupId, $sourceGroupId);
                        $commonMiddlePart = commonMiddlePart($targetGroupId, $sourceGroupId, $thisGroupId);
                        $uncommonEndPart = uncommonPart($thisGroupId, $sourceGroupId);
                        $commonEndPart = commonEndPart($thisGroupId,$sourceGroupId);
                        $candidateGroupName = $commonFrontPart.$uncommonFrontPart.$commonMiddlePart.$uncommonEndPart.$commonEndPart;
                        $candidateGroupId = array_search($candidateGroupName, $groupList);
                    }                    
                    $scopeGroups[] = $candidateGroupId ? $candidateGroupId : $thisGroupId;
                }
                if(!$formulize_permHandler->setGroupScopeGroups($targetGroupId, $scopeGroups)) {
                    print "<p>Error: could not assign groupscope groups for Group Number $targetGroupId</p>";
                }
            }
        }

        foreach($thisForm->getVar('elements') as $elementId) {
            $element = $element_handler->get($elementId);
            $ele_display = $element->getVar('ele_display');
            $ele_disabled = $element->getVar('ele_disabled');
            $ele_type = $element->getVar('ele_type');
            if($ele_type == "select") {
                $ele_value = $element->getVar('ele_value');
                $filterGroups = explode(",", $ele_value[3]); // ele_value[3] does not have leading and trailing commas, so we have to convert to an array to search it easily, otherwise matching the first and last items in the string is problematic
            }
            if($ele_type == "checkbox") {
                $ele_value = $element->getVar('ele_value');
                $filterGroups = explode(",", $ele_value['formlink_scope']); // ele_value['formlink_scope'] does not have leading and trailing commas, so we have to convert to an array to search it easily, otherwise matching the first and last items in the string is problematic
            }
            $writeElement = false;
            foreach($targetGroupIds as $targetGroupId) {
                // match any display or disabled settings that might apply to only certain groups
                if(strstr($ele_display, ",$sourceGroupId,")) {
                    $writeElement = true;
                    $ele_display .= "$targetGroupId,";
                }
                if(strstr($ele_disabled, ",$sourceGroupId,")) {
                    $writeElement = true;
                    $ele_disabled .= "$targetGroupId,";
                }
                // check ele_value[3] data, which is the group filter for the sources for linked select boxes
                if($ele_type == "select" OR $ele_type == "checkbox") {
                    if(in_array($sourceGroupId, $filterGroups)) {
                        $writeElement = true;
                        $filterGroups[] = $targetGroupId;
                    }
                }
            }
            if($writeElement) {
                if($ele_type == "select") {
                    $ele_value[3] = implode(",", $filterGroups);
                    $element->setVar('ele_value', $ele_value);
                }
                if($ele_type == "checkbox") {
                    $ele_value['formlink_scope'] = implode(",", $filterGroups);
                    $element->setVar('ele_value', $ele_value);
                }
                $element->setVar('ele_display', $ele_display);
                $element->setVar('ele_disabled', $ele_disabled);
                if(!$element_handler->insert($element)) {
                    print "<p>Error: could not update element '".$element->getVar('ele_caption')."' with new group settings!</p>";
                }
            }
        }
    }
}

$adminPage['groups'] = $groupList;
$adminPage['template'] = "db:admin/managepermissions.html";

$breadcrumbtrail[1]['url'] = "page=home";
$breadcrumbtrail[1]['text'] = "Home";
$breadcrumbtrail[2]['text'] = "Copy Group Permissions";

function commonFrontPart($groupId1, $groupId2) {
    return findMatchingPart($groupId1, $groupId2, 0);    
}

function commonMiddlePart($groupId1, $groupId2, $groupId3) {
    global $groupList;
    
    // Glenwood Counsellors vs Hotelworx Counsellors start of middle is 8 (where the space is)
    $lengthCommonFront = strlen(commonFrontPart($groupId2, $groupId1)); 
    $lengthUncommonPart = strlen(uncommonPart($groupId2, $groupId1));
    $startOfMiddle = $lengthCommonFront+$lengthUncommonPart;

    // Glenwood Counsellors vs Glenwood Coaches length of middle is 3 (space plus 'Co')
    $lengthCommonFront = strlen(commonFrontPart($groupId2, $groupId3));
    $lengthOfMiddle = $lengthCommonFront-$startOfMiddle;

    return substr($groupList[$groupId2], $startOfMiddle, $lengthOfMiddle);
}

function commonEndPart($groupId1, $groupId2) {
    return findMatchingPart($groupId1, $groupId2, -1);
}

function uncommonPart($groupId1, $groupId2) {
    global $groupList;
    $lengthCommonFront = strlen(commonFrontPart($groupId1, $groupId2));
    $lengthCommonEnd = strlen(commonEndPart($groupId1, $groupId2));
    return substr($groupList[$groupId1],$lengthCommonFront,strlen($groupList[$groupId1])-$lengthCommonFront-$lengthCommonEnd);
}

// if middle is passed, find the match between the end of the uncommon part, and the start of the common end
function findMatchingPart($groupId1, $groupId2, $limit) {
    static $cachedParts = array();
    if(!isset($cachedParts[$limit][$groupId1][$groupId2]) AND !isset($cachedParts[$limit][$groupId2][$groupId1])) {
        global $groupList;
        $match = "";
        while(substr($groupList[$groupId1],$limit,1) == substr($groupList[$groupId2],$limit,1)) {
            if($limit < 0) {
                $match = substr($groupList[$groupId1],$limit,1).$match;
                $limit--;
                if($limit*-1 > strlen($groupList[$groupId1])) { break; }
            } else {
                $match .= substr($groupList[$groupId1],$limit,1);
                $limit++;
                if($limit == strlen($groupList[$groupId1])) { break; }
            }
        }
        $cachedParts[$limit][$groupId1][$groupId2] = $match;
    }
    return (isset($cachedParts[$limit][$groupId1][$groupId2])) ? $cachedParts[$limit][$groupId1][$groupId2] : $cachedParts[$middle][$limit][$groupId2][$groupId1];
}
 