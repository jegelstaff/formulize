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

// TODO: ADD MENU PERMISSIONS TO THIS!
// AND LIST OF ENTRIES CUSTOMACTIONS FIELD
// AND SAVED VIEWS PUBGROUPS FIELD

if($sourceGroupId AND count($targetGroupIds)>0 AND $allOrFormulizeOnly) {

    include_once XOOPS_ROOT_PATH . '/modules/formulize/class/usersGroupsPerms.php';

    $modidForCopy = ($allOrFormulizeOnly == 'formulize') ? getFormulizeModId() : null;

    foreach($targetGroupIds as $targetGroupId) {

        // Build name-based group ID mapping for groupscope resolution.
        // This process works for groups named "Glenwood Counsellors" but not "Counsellors Glenwood".
        // ie: Common grouping identifier comes first, then the type of users within that grouping.
        $groupIdMapping = array();
        foreach($groupList as $gid => $gname) {
            if($gid == $sourceGroupId) {
                $groupIdMapping[$gid] = $targetGroupId;
            } else {
                $commonFrontPart = commonFrontPart($targetGroupId, $sourceGroupId);
                $uncommonFrontPart = uncommonPart($targetGroupId, $sourceGroupId);
                $commonMiddlePart = commonMiddlePart($targetGroupId, $sourceGroupId, $gid);
                $uncommonEndPart = uncommonPart($gid, $sourceGroupId);
                $commonEndPart = commonEndPart($gid, $sourceGroupId);
                $candidateGroupName = $commonFrontPart.$uncommonFrontPart.$commonMiddlePart.$uncommonEndPart.$commonEndPart;
                $candidateGroupId = array_search($candidateGroupName, $groupList);
                if($candidateGroupId) {
                    $groupIdMapping[$gid] = $candidateGroupId;
                }
            }
        }

        // Copy permissions, filters, and groupscope (with name-based mapping)
        if(!formulizePermHandler::copyGroupPermissions($sourceGroupId, $targetGroupId, $modidForCopy, $groupIdMapping)) {
            print "<p>Error: could not replicate permissions for Group Number $targetGroupId, which was supposed to be copied from Group Number $sourceGroupId</p>";
        }

    }

    // Synchronize element display/disabled/filter settings from source group to targets
    $form_handler = xoops_getmodulehandler('forms', 'formulize');
    $element_handler = xoops_getmodulehandler('elements', 'formulize');
    $forms = $form_handler->getAllForms(true); // true causes all elements, even ones that show for no groups, to be included in the element list properties of the form objects
    $groupMap = array($sourceGroupId => $targetGroupIds);

    foreach($forms as $thisForm) {
        foreach($thisForm->getVar('elements') as $elementId) {
            $element = $element_handler->get($elementId);
            if(formulizePermHandler::synchronizeGroupReferencesInElement($element, $groupMap)) {
                if(!$element_handler->insert($element)) {
                    print "<p>Error: could not update element '".$element->getVar('ele_caption')."' with new group settings!</p>";
                }
            }
        }
    }
}

$adminPage['groups'] = $groupList;
$adminPage['home_tabs'] = getHomeTabs('managepermissions');

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
