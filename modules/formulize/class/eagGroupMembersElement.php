<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) 2006 Freeform Solutions                  ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                       ##
###############################################################################
##  Author of this file: Formulize Incorporated                               ##
##  Project: Formulize                                                        ##
###############################################################################

// Virtual element type for the "Members" column on the Groups management page.
// Shows up to DISPLAY_LIMIT member names for each group; appends a total count
// summary when the group has more members than the limit.
// Has no real database column — data is injected post-query by
// injectGroupMembersData() in usersAndGroups.php.
// buildSearchWhereClause uses a correlated EXISTS subquery against
// groups_users_link JOIN users so that searching by member name filters groups.
//
// For EAG forms with multiple group categories (e.g. All Users, Manager, Staff),
// render() emits one widget per category wrapped in outer category tabs.
// Hidden input names include the groupId: group_members_add_{fid}_{entryId}_{groupId}
// processGroupSubmission() in groupTableElement.php iterates all valid entry
// groups for this fid+entryId and processes each independently.

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

require_once XOOPS_ROOT_PATH . "/modules/formulize/class/virtualElement.php";
require_once XOOPS_ROOT_PATH . "/modules/formulize/class/userAccountGroupMembershipElement.php";

class formulizeEagGroupMembersElement extends formulizeVirtualElement {

	function __construct() {
		parent::__construct();
		$this->name             = "EAG Group Members";
		$this->isGroupTableElement = true;
	}

}

class formulizeEagGroupMembersElementHandler extends formulizeVirtualElementHandler {

	function create() {
		return new formulizeEagGroupMembersElement();
	}

	// No DB column to read — the widget populates itself via AJAX on load.
	function loadValue($element, $value, $entry_id) {
		return null;
	}

	// Query group members, optionally filtered by a search term.
	// Returns array('total' => int, 'results' => array of array('uid', 'display', 'protected')).
	// total is the unfiltered member count; results are capped at $limit rows.
	// When $checkProtection is true each result gains a 'protected' bool indicating the user
	// must stay in this group due to entries-are-users settings.
	// Used by both renderSingleWidget() for server-side pre-population and the XHR responder for AJAX searches.
	static function queryMembers($groupId, $term = '', $limit = 10, $checkProtection = false) {
		global $xoopsDB;
		$gulTable   = $xoopsDB->prefix('groups_users_link');
		$usersTable = $xoopsDB->prefix('users');
		$groupId    = intval($groupId);

		$cntRes = $xoopsDB->query(
			"SELECT COUNT(*) AS cnt FROM `$gulTable` gul"
			. " JOIN `$usersTable` u ON u.uid = gul.uid"
			. " WHERE gul.groupid = $groupId"
		);
		$total = 0;
		if ($cntRes && $cntRow = $xoopsDB->fetchArray($cntRes)) {
			$total = intval($cntRow['cnt']);
		}

		$where = "gul.groupid = $groupId";
		if ($term !== '') {
			$safeTerm = formulize_db_escape($term);
			$where .= " AND (u.name LIKE '%$safeTerm%' OR u.uname LIKE '%$safeTerm%' OR u.email LIKE '%$safeTerm%')";
		}

		$res = $xoopsDB->query(
			"SELECT u.uid, u.uname, u.name FROM `$usersTable` u"
			. " JOIN `$gulTable` gul ON gul.uid = u.uid"
			. " WHERE $where ORDER BY u.name, u.uname LIMIT $limit"
		);
		$results = array();
		while ($res && $row = $xoopsDB->fetchArray($res)) {
			$uid       = intval($row['uid']);
			$display   = ($row['name'] !== '') ? $row['name'] . ' (' . $row['uname'] . ')' : $row['uname'];
			$entry     = array('uid' => $uid, 'display' => htmlspecialchars($display, ENT_QUOTES, 'UTF-8'));
			if ($checkProtection) {
				$entry['protected'] = formulizeUserAccountGroupMembershipElementHandler::isGroupMandatoryForUser($uid, $groupId);
			}
			$results[] = $entry;
		}

		return array('total' => $total, 'results' => $results);
	}

	// Build a map of categoryName => entryGroupId for this form+entry.
	// For EAG forms with group_categories defined, resolves each template group to
	// the corresponding entry group using buildTemplateToEntryGroupMap.
	// Falls back to a single-group lookup for system forms or EAG forms with no categories set.
	// Returns an array with at least one entry; keys are category names (empty string for non-EAG).
	private static function buildCategoryGroupMap($fid, $entry_id) {
		global $xoopsDB;
		$groupsTable = $xoopsDB->prefix('groups');

		$form_handler    = xoops_getmodulehandler('forms', 'formulize');
		$formObject      = $form_handler->get($fid);
		$groupCategories = ($formObject && $formObject->getVar('entries_are_groups'))
			? $formObject->getVar('group_categories')
			: null;

		if (is_array($groupCategories) && !empty($groupCategories)) {
			require_once XOOPS_ROOT_PATH . '/modules/formulize/class/formulize.php';
			$templateToEntry = formulizeHandler::buildTemplateToEntryGroupMap($fid, $entry_id, $groupCategories);
			$map = array();
			foreach ($groupCategories as $templateGroupId => $catName) {
				if (isset($templateToEntry[$templateGroupId])) {
					$map[$catName] = $templateToEntry[$templateGroupId];
				}
			}
			if (!empty($map)) {
				return $map;
			}
		}

		// Fall back: single group lookup (system groups form or legacy EAG form with no categories)
		$res = $xoopsDB->query(
			"SELECT groupid FROM `$groupsTable` WHERE form_id = $fid AND entry_id = " . intval($entry_id) . " AND is_group_template = 0"
		);
		if ($res && $row = $xoopsDB->fetchArray($res)) {
			return array('' => intval($row['groupid']));
		}
		return array('' => intval($entry_id)); // system groups form: entry_id is the groupid
	}

	// Render the hidden inputs + inner widget HTML for a single category/group.
	// CSS and JS are emitted separately by render() via globals guards.
	private static function renderSingleWidget($fid, $entry_id, $groupId, $uid, $ajaxUrl) {
		$key       = $fid . '_' . $groupId;
		$fidJs     = json_encode($fid);
		$groupIdJs = json_encode($groupId);
		$ajaxUrlJs = json_encode($ajaxUrl);
		$uidJs     = json_encode($uid);

		// Pre-populate the members list server-side to avoid an initial AJAX round-trip.
		$memberData  = self::queryMembers($groupId, '', 10, true);
		$total       = $memberData['total'];
		$memberCount = count($memberData['results']);
		$memberRows  = '';
		foreach ($memberData['results'] as $m) {
			$removeBtn  = empty($m['protected'])
				? '<button type="button" onclick="gmmMarkRemove(' . $fidJs . ',' . $groupIdJs . ',' . $m['uid'] . ')">' . _formulize_GMM_REMOVE_BUTTON . '</button>'
				: '';
			$memberRows .= '<div class="gmm-member-row" id="gmm-row-' . $key . '-' . $m['uid'] . '">'
				. '<span>' . $m['display'] . '</span>'
				. $removeBtn
				. '</div>';
		}
		$infoHtml = '<div class="gmm-info">' . $total . ' ' . ($total === 1 ? _formulize_GMM_MEMBER : _formulize_GMM_MEMBERS);
		if ($total > $memberCount) {
			$infoHtml .= ' — ' . _formulize_GMM_SHOWING_FIRST . ' ' . $memberCount;
		}
		$infoHtml .= '</div>';
		$initialMemberListHtml = $infoHtml . '<div class="gmm-rows">' . $memberRows . '</div>';

		$html = '';
		// Hidden inputs carry add/remove UID lists to processGroupSubmission on save.
		// Name includes groupId so multiple categories on the same form don't collide.
		$html .= '<input type="hidden" name="group_members_add_'    . $fid . '_' . $entry_id . '_' . $groupId . '" id="gmm-add-input-'    . $key . '" value="[]">';
		$html .= '<input type="hidden" name="group_members_remove_' . $fid . '_' . $entry_id . '_' . $groupId . '" id="gmm-remove-input-' . $key . '" value="[]">';

		$html .= '<div class="gmm-widget" id="gmm-' . $key . '">';

		// Tab bar
		$html .= '<div class="gmm-tabs">';
		$html .= '<span class="gmm-tab gmm-tab-active" data-panel="members">' . htmlspecialchars(_formulize_GMM_TAB_MEMBERS, ENT_QUOTES, 'UTF-8') . '</span>';
		$html .= '<span class="gmm-tab" data-panel="add">' . htmlspecialchars(_formulize_GMM_TAB_ADD_MEMBERS, ENT_QUOTES, 'UTF-8') . '</span>';
		$html .= '</div>';

		// Members panel
		$html .= '<div class="gmm-panel" id="gmm-panel-members-' . $key . '">';
		$html .= '<div class="gmm-searchbar">';
		$html .= '<input type="text" id="gmm-member-search-' . $key . '" placeholder="' . htmlspecialchars(_formulize_GMM_SEARCH_PLACEHOLDER, ENT_QUOTES, 'UTF-8') . '">';
		$html .= '<button type="button" onclick="gmmSearchMembers(' . $fidJs . ',' . $groupIdJs . ')">' . htmlspecialchars(_formulize_GMM_SEARCH_BUTTON, ENT_QUOTES, 'UTF-8') . '</button>';
		$html .= '</div>';
		$html .= '<div id="gmm-member-list-' . $key . '">' . $initialMemberListHtml . '</div>';
		$html .= '<div class="gmm-pending-removes" id="gmm-pending-removes-' . $key . '"></div>';
		$html .= '</div>';

		// Add Members panel
		$html .= '<div class="gmm-panel" id="gmm-panel-add-' . $key . '" style="display:none">';
		$html .= '<div class="gmm-searchbar">';
		$html .= '<input type="text" id="gmm-add-search-' . $key . '" placeholder="' . htmlspecialchars(_formulize_GMM_FIND_USER_PLACEHOLDER, ENT_QUOTES, 'UTF-8') . '">';
		$html .= '<button type="button" onclick="gmmSearchNonMembers(' . $fidJs . ',' . $groupIdJs . ')">' . htmlspecialchars(_formulize_GMM_SEARCH_BUTTON, ENT_QUOTES, 'UTF-8') . '</button>';
		$html .= '</div>';
		$html .= '<div id="gmm-add-results-' . $key . '"></div>';
		$html .= '<div class="gmm-pending-adds" id="gmm-pending-adds-' . $key . '"></div>';
		$html .= '</div>';

		$html .= '</div>'; // .gmm-widget
		$html .= '<script>jQuery(document).ready(function(){gmmInit(' . $fidJs . ',' . $groupIdJs . ',' . $ajaxUrlJs . ',' . $uidJs . ');});</script>';

		return $html;
	}

	// Render the member management widget for the group edit form.
	// Returns an XoopsFormLabel wrapping the tab widget HTML + JS.
	// For new (unsaved) groups, returns a read-only notice instead.
	// For EAG forms with multiple categories, wraps each category's widget in outer category tabs.
	function render($ele_value, $caption, $markupName, $isDisabled, $element, $entry_id, $screen = false, $owner = null) {
		$fid = intval($element->getVar('id_form'));

		if ($isDisabled) {
			return new XoopsFormLabel($caption, '');
		}

		if ($entry_id === 'new' || !is_numeric($entry_id) || intval($entry_id) <= 0) {
			return new XoopsFormLabel($caption, '<em>' . _formulize_GMM_SAVE_FIRST . '</em>');
		}

		global $xoopsUser;
		$ajaxUrl = XOOPS_URL . '/modules/formulize/formulize_xhr_responder.php';
		$uid     = $xoopsUser ? intval($xoopsUser->getVar('uid')) : 0;

		$categoryGroupMap = self::buildCategoryGroupMap($fid, intval($entry_id));
		$multiCategory    = count($categoryGroupMap) > 1;

		// CSS and JS are output once per page via a global guard.
		$css = '';
		if (empty($GLOBALS['gmmStyleOutput'])) {
			$GLOBALS['gmmStyleOutput'] = true;
			$css = '
<style>
.gmm-outer{box-sizing:border-box;border:1px solid #ccc;width:100%;max-width:300px;margin:4px 0;}
.gmm-outer .gmm-widget{border:none;max-width:none;margin:0;}
.gmm-widget{box-sizing:border-box;border:1px solid #ccc;width:100%;max-width:300px;margin:4px 0;}
.gmm-widget button{min-width:4em;width:auto;padding-left:1em;padding-right:1em;}
.gmm-cat-tabs{display:flex;background:#e8e8e8;border-bottom:1px solid #ccc;}
.gmm-cat-tab{padding:6px 14px;cursor:pointer;border-right:1px solid #ccc;font-size:.9em;}
.gmm-cat-tab:last-child{border-right:none;}
.gmm-cat-tab.gmm-cat-tab-active{background:#fff;font-weight:bold;}
.gmm-tabs{display:flex;border-bottom:1px solid #ccc;background:#f5f5f5;}
.gmm-tab{padding:7px 16px;cursor:pointer;border-right:1px solid #ccc;}
.gmm-tab:last-child{border-right:none;}
.gmm-tab.gmm-tab-active{background:#fff;font-weight:bold;border-bottom:2px solid #fff;margin-bottom:-1px;}
.gmm-panel{padding:10px;}
.gmm-searchbar{display:flex;align-items:center;margin-bottom:8px;}
.gmm-searchbar input[type=text]{flex:1;min-width:0;padding:4px 6px;margin-right:6px;max-width:none;}
.gmm-hidden{display:none!important;}
.gmm-rows{display:table;}
.gmm-member-row,.gmm-add-row,.gmm-pending-row{display:table-row;}
.gmm-member-row span,.gmm-add-row span,.gmm-pending-row span{display:table-cell;white-space:nowrap;padding:2px 8px 2px 0;vertical-align:middle;height:2em;}
.gmm-member-row button,.gmm-add-row button,.gmm-pending-row button{display:table-cell;vertical-align:middle;opacity:0;pointer-events:none;transition:opacity 200ms;}
.gmm-member-row:hover button,.gmm-member-row button:focus,.gmm-add-row:hover button,.gmm-add-row button:focus,.gmm-pending-row:hover button,.gmm-pending-row button:focus{opacity:1;pointer-events:auto;}
.gmm-info{color:#666;font-size:.9em;margin-bottom:6px;}
.gmm-pending-adds,.gmm-pending-removes{margin-top:10px;padding-top:8px;border-top:1px solid #eee;}
.gmm-pending-header{font-weight:bold;font-size:.9em;margin-bottom:4px;color:#666;}
.gmm-pending-adds .gmm-pending-row span{color:#060;}
.gmm-pending-removes .gmm-pending-row span{color:#c00;}
@media (min-width:768px){.col2 .gmm-outer,.col2 .gmm-widget{min-width:700px;max-width:none;}}
</style>';
		}

		$js = '';
		if (empty($GLOBALS['gmmJsOutput'])) {
			$GLOBALS['gmmJsOutput'] = true;
			$gmmLang = array(
				'member'           => _formulize_GMM_MEMBER,
				'members'          => _formulize_GMM_MEMBERS,
				'showingFirst'     => _formulize_GMM_SHOWING_FIRST,
				'matching'         => _formulize_GMM_MATCHING,
				'remove'           => _formulize_GMM_REMOVE_BUTTON,
				'cancel'           => _formulize_GMM_CANCEL_BUTTON,
				'add'              => _formulize_GMM_ADD_BUTTON,
				'searching'        => _formulize_GMM_SEARCHING,
				'noMembersFound'   => _formulize_GMM_NO_MEMBERS_FOUND,
				'errorLoading'     => _formulize_GMM_ERROR_LOADING,
				'pendingRemovals'  => _formulize_GMM_PENDING_REMOVALS,
				'minChars'         => _formulize_GMM_MIN_CHARS,
				'noUsersFound'     => _formulize_GMM_NO_USERS_FOUND,
				'noNewUsers'       => _formulize_GMM_NO_NEW_USERS,
				'errorSearching'   => _formulize_GMM_ERROR_SEARCHING,
				'pendingAdditions' => _formulize_GMM_PENDING_ADDITIONS,
			);
			$js = '<script>var gmmLang=' . json_encode($gmmLang) . ';
var gmmState={};
function gmmKey(f,g){return f+"_"+g;}
function gmmSwitchCategory(el,panelId,inputId,groupId){
	var outer=jQuery(el).closest(".gmm-outer");
	outer.find(".gmm-cat-tab").removeClass("gmm-cat-tab-active");
	jQuery(el).addClass("gmm-cat-tab-active");
	outer.find(".gmm-cat-panel").hide();
	jQuery("#"+panelId).show();
	jQuery("#"+inputId).val(groupId);
}
function gmmInit(fid,gid,ajaxUrl,uid){
	var k=gmmKey(fid,gid);
	gmmState[k]={fid:fid,gid:gid,url:ajaxUrl,uid:uid,toAdd:[],toRemove:[]};
	jQuery("#gmm-"+k+" .gmm-tab").on("click",function(e){
		e.preventDefault();
		var panel=jQuery(this).data("panel");
		jQuery("#gmm-"+k+" .gmm-tab").removeClass("gmm-tab-active");
		jQuery(this).addClass("gmm-tab-active");
		jQuery("#gmm-"+k+" .gmm-panel").hide();
		jQuery("#gmm-panel-"+panel+"-"+k).show();
	});
	jQuery("#gmm-member-search-"+k).on("keypress",function(e){if(e.which===13){e.preventDefault();gmmSearchMembers(fid,gid);}});
	jQuery("#gmm-add-search-"+k).on("keypress",function(e){if(e.which===13){e.preventDefault();gmmSearchNonMembers(fid,gid);}});
}
function gmmSearchMembers(fid,gid){
	var k=gmmKey(fid,gid),term=jQuery("#gmm-member-search-"+k).val(),list=jQuery("#gmm-member-list-"+k);
	list.html("<em>"+gmmLang.searching+"</em>");
	jQuery.ajax({url:gmmState[k].url,type:"GET",data:{op:"group_member_search",uid:gmmState[k].uid,action:"members",groupid:gid,fid:fid,term:term},dataType:"json",
		success:function(data){
			if(!data||!data.results){list.html("<em>"+gmmLang.noMembersFound+"</em>");return;}
			var tr=gmmState[k].toRemove,html="",rows="";
			html+="<div class=\"gmm-info\">"+data.total+" "+(data.total===1?gmmLang.member:gmmLang.members);
			if(data.total>data.results.length)html+=" — "+gmmLang.showingFirst+" "+data.results.length+(term?" "+gmmLang.matching+" \""+jQuery("<span>").text(term).html()+"\"":"");
			html+="</div>";
			jQuery.each(data.results,function(i,m){
				if(jQuery.inArray(m.uid,tr)!==-1)return;
				var removeBtn=m.protected?"":"<button type=\"button\" onclick=\"gmmMarkRemove(\'"+fid+"\',\'"+gid+"\',"+m.uid+")\">"+gmmLang.remove+"</button>";
				rows+="<div class=\"gmm-member-row\" id=\"gmm-row-"+k+"-"+m.uid+"\"><span>"+m.display+"</span>"+removeBtn+"</div>";
			});
			list.html(html+"<div class=\"gmm-rows\">"+rows+"</div>");
		},
		error:function(){list.html("<em>"+gmmLang.errorLoading+"</em>");}
	});
}
function gmmMarkRemove(fid,gid,uid){
	var k=gmmKey(fid,gid);
	if(jQuery.inArray(uid,gmmState[k].toRemove)!==-1)return;
	var display=jQuery("#gmm-row-"+k+"-"+uid+" span").first().text();
	gmmState[k].toRemove.push(uid);
	gmmSyncHiddens(fid,gid);
	formulizechanged=1;
	jQuery("#gmm-row-"+k+"-"+uid).addClass("gmm-hidden");
	var sec=jQuery("#gmm-pending-removes-"+k);
	if(sec.find(".gmm-pending-header").length===0)sec.append("<div class=\"gmm-pending-header\">"+gmmLang.pendingRemovals+"</div><div class=\"gmm-rows\"></div>");
	sec.find(".gmm-rows").append("<div class=\"gmm-pending-row\" id=\"gmm-remove-row-"+k+"-"+uid+"\"><span>"+jQuery("<div>").text(display).html()+"</span><button type=\"button\" onclick=\"gmmCancelRemove(\'"+fid+"\',\'"+gid+"\',"+uid+")\">"+gmmLang.cancel+"</button></div>");
}
function gmmCancelRemove(fid,gid,uid){
	var k=gmmKey(fid,gid);
	gmmState[k].toRemove=jQuery.grep(gmmState[k].toRemove,function(v){return v!==uid;});
	gmmSyncHiddens(fid,gid);
	jQuery("#gmm-remove-row-"+k+"-"+uid).remove();
	jQuery("#gmm-row-"+k+"-"+uid).removeClass("gmm-hidden");
	if(jQuery("#gmm-pending-removes-"+k+" .gmm-pending-row").length===0)jQuery("#gmm-pending-removes-"+k).empty();
}
function gmmSearchNonMembers(fid,gid){
	var k=gmmKey(fid,gid),term=jQuery("#gmm-add-search-"+k).val();
	if(!term||term.length<2){jQuery("#gmm-add-results-"+k).html("<em>"+gmmLang.minChars+"</em>");return;}
	jQuery("#gmm-add-results-"+k).html("<em>"+gmmLang.searching+"</em>");
	jQuery.ajax({url:gmmState[k].url,type:"GET",data:{op:"group_member_search",uid:gmmState[k].uid,action:"nonmembers",groupid:gid,fid:fid,term:term},dataType:"json",
		success:function(data){
			if(!data||data.length===0){jQuery("#gmm-add-results-"+k).html("<em>"+gmmLang.noUsersFound+"</em>");return;}
			var toAdd=gmmState[k].toAdd,html="";
			jQuery.each(data,function(i,u){
				if(jQuery.inArray(u.uid,toAdd)!==-1)return;
				html+="<div class=\"gmm-add-row\" id=\"gmm-add-row-"+k+"-"+u.uid+"\"><span>"+u.display+"</span><button type=\"button\" onclick=\"gmmAddPending(\'"+fid+"\',\'"+gid+"\',"+u.uid+","+JSON.stringify(u.display).replace(/\"/g,\'&quot;\')+")\">"+gmmLang.add+"</button></div>";
			});
			jQuery("#gmm-add-results-"+k).html(html?"<div class=\"gmm-rows\">"+html+"</div>":"<em>"+gmmLang.noNewUsers+"</em>");
		},
		error:function(){jQuery("#gmm-add-results-"+k).html("<em>"+gmmLang.errorSearching+"</em>");}
	});
}
function gmmAddPending(fid,gid,uid,display){
	var k=gmmKey(fid,gid);
	if(jQuery.inArray(uid,gmmState[k].toAdd)!==-1)return;
	gmmState[k].toAdd.push(uid);
	gmmSyncHiddens(fid,gid);
	formulizechanged=1;
	jQuery("#gmm-add-row-"+k+"-"+uid).addClass("gmm-hidden");
	var sec=jQuery("#gmm-pending-adds-"+k);
	if(sec.find(".gmm-pending-header").length===0)sec.append("<div class=\"gmm-pending-header\">"+gmmLang.pendingAdditions+"</div><div class=\"gmm-rows\"></div>");
	sec.find(".gmm-rows").append("<div class=\"gmm-pending-row\" id=\"gmm-pending-"+k+"-"+uid+"\"><span>"+jQuery("<div>").text(display).html()+"</span><button type=\"button\" onclick=\"gmmCancelAdd(\'"+fid+"\',\'"+gid+"\',"+uid+")\">"+gmmLang.cancel+"</button></div>");
}
function gmmCancelAdd(fid,gid,uid){
	var k=gmmKey(fid,gid);
	gmmState[k].toAdd=jQuery.grep(gmmState[k].toAdd,function(v){return v!==uid;});
	gmmSyncHiddens(fid,gid);
	jQuery("#gmm-pending-"+k+"-"+uid).remove();
	jQuery("#gmm-add-row-"+k+"-"+uid).removeClass("gmm-hidden");
	if(jQuery("#gmm-pending-adds-"+k+" .gmm-pending-row").length===0)jQuery("#gmm-pending-adds-"+k).empty();
}
function gmmSyncHiddens(fid,gid){
	var k=gmmKey(fid,gid);
	jQuery("#gmm-add-input-"+k).val(JSON.stringify(gmmState[k].toAdd));
	jQuery("#gmm-remove-input-"+k).val(JSON.stringify(gmmState[k].toRemove));
}
</script>';
		}

		$html = $css . $js;

		if ($multiCategory) {
			// Restore the active category from the last submission, defaulting to the first.
			$activeCatInputName = 'gmm_active_category_' . $fid . '_' . intval($entry_id);
			$postedGroupId      = isset($_POST[$activeCatInputName]) ? intval($_POST[$activeCatInputName]) : 0;
			$activeGroupId      = (in_array($postedGroupId, $categoryGroupMap) && $postedGroupId > 0)
				? $postedGroupId
				: reset($categoryGroupMap);

			$outerKey  = $fid . '-' . intval($entry_id);
			$inputId   = 'gmm-active-cat-' . $outerKey;

			$html .= '<input type="hidden" id="' . $inputId . '" name="' . $activeCatInputName . '" value="' . intval($activeGroupId) . '">';
			$html .= '<div class="gmm-outer" id="gmm-outer-' . $outerKey . '">';
			$html .= '<div class="gmm-cat-tabs">';
			foreach ($categoryGroupMap as $catName => $groupId) {
				$panelId     = 'gmm-cat-panel-' . $fid . '-' . $groupId;
				$activeClass = ($groupId === $activeGroupId) ? ' gmm-cat-tab-active' : '';
				$html .= '<span class="gmm-cat-tab' . $activeClass . '" onclick="gmmSwitchCategory(this,\'' . htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8') . '\',\'' . $inputId . '\',' . intval($groupId) . ')">'
					. htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') . '</span>';
			}
			$html .= '</div>';

			foreach ($categoryGroupMap as $catName => $groupId) {
				$panelId     = 'gmm-cat-panel-' . $fid . '-' . $groupId;
				$panelHidden = ($groupId === $activeGroupId) ? '' : ' style="display:none"';
				$html .= '<div class="gmm-cat-panel" id="' . $panelId . '"' . $panelHidden . '>';
				$html .= self::renderSingleWidget($fid, intval($entry_id), $groupId, $uid, $ajaxUrl);
				$html .= '</div>';
			}

			$html .= '</div>'; // .gmm-outer
		} else {
			// Single category (or fallback): render widget directly, no outer wrapper needed.
			$groupId = reset($categoryGroupMap);
			$html   .= self::renderSingleWidget($fid, intval($entry_id), $groupId, $uid, $ajaxUrl);
		}

		return new XoopsFormLabel($caption, $html);
	}

	// Return a correlated EXISTS subquery that matches groups having at least one
	// member whose display name contains the search term.
	function buildSearchWhereClause($term, $operator, $quotes, $likebits, $fid, $tableAlias = 'main') {
		global $xoopsDB;
		$usersTable     = $xoopsDB->prefix('users');
		$gulTable       = $xoopsDB->prefix('groups_users_link');
		// For IN / NOT IN, $term is already a fully escaped, parenthesized list from prepareValueForInOperator; escaping it again would corrupt the structural quotes. Escape only scalar terms.
		$escapedTerm = (trim($operator) === 'IN' || trim($operator) === 'NOT IN') ? $term : formulize_db_escape($term);
		$safeTermClause = ' ' . trim($operator) . ' ' . $quotes . $likebits . $escapedTerm . $likebits . $quotes;
		return "EXISTS (SELECT 1 FROM `$gulTable` AS gm"
			. " JOIN `$usersTable` AS gm_u ON gm_u.uid = gm.uid"
			. " WHERE gm.groupid = `{$tableAlias}`.groupid"
			. " AND gm_u.uname" . $safeTermClause . ")";
	}

}
