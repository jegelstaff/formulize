<?php

trait resources {

	/**
	 * Register available MCP resources
	 * Presently, only webmasters can access resources.
	 * Sets the resources property of the FormulizeMCP class
	 * @return void
	 */
	private function registerResources()
	{
		$this->resources = [];

		$this->resources['system_info'] = [
			'uri' => 'formulize://system/system_info.json',
			'name' => 'System Information',
			'description' => 'Formulize system info and status',
			'mimeType' => 'application/json'
		];

		$this->resources['applications_list'] = [
			'uri' => 'formulize://system/applications_list.json',
			'name' => 'List of Applications',
			'description' => "All the applications in the system. Applications are collections of forms that work together. A form can be part of one or more applications. Applications are purly for organizing forms, any form can still interact with any other form regardless of the application(s) they're in.",
			'mimeType' => 'application/json'
		];

		$this->resources['screens_list'] = [
			'uri' => 'formulize://system/screens_list.json',
			'name' => 'List of Screens',
			'description' => "All the screens in the system. Screens are ways of presenting a form and its entries to users. Lists screens show the entries in the form, and have extensive configuration options to of entries are a type of screen. Versions of the form which users can fill in, are a type of form. , Connection are based pairs of elements, one in each form, that have matching values. Entries in the forms are connected when they have the same value in the paired elements, or when one element is 'linked' to the other, in which case the values in the linked element will be entry_ids in the other form (foreign keys).",
			'mimeType' => 'application/json'
		];

		$this->resources['groups_list'] = [
			'uri' => 'formulize://system/groups_list.json',
			'name' => 'List of Groups',
			'description' => 'All the groups in the system. Groups are collections of users. Each group can have its own permissions to access a form, such as viewing the form, updating entries by other people in the same group, seeing entries by anyone in any group, etc.',
			'mimeType' => 'application/json'
		];

		$this->resources['all_form_connections'] = [
			'uri' => 'formulize://system/all_form_connections.json',
			'name' => 'List of Connections for all Forms',
			'description' => "All the connections between forms. Connection are based pairs of elements, one in each form, that have matching values. Entries in the forms are connected when they have the same value in the paired elements, or when one element is 'linked' to the other, in which case the values in the linked element will be entry_ids in the other form (foreign keys).",
			'mimeType' => 'application/json'
		];

		// Dynamically add form schema resources
		$formsList = $this->list_forms();
		$forms = isset($formsList['forms']) ? $formsList['forms'] : [];
		$groupPermsForFormResource = [];
		foreach ($forms as $form) {
			$formId = $form['id_form'];
			if(security_check($formId)) {
				$formTitle = trans($form['form_title']);
				$sanitizedTitle = strtolower(formulizeObject::sanitize_handle_name($formTitle));
				$this->resources["schema_form_$formId"] = [
					'uri' => "formulize://schemas/$sanitizedTitle"."_(form_$formId).json",
					'name' => "Schema of $formTitle (form $formId)",
					'description' => "Complete schema, element definitions, screens, and form connections, for form $formId: $formTitle",
					'mimeType' => 'application/json'
				];
				$groupPermsForFormResource["group_permissions_for_form_$formId"] = [
					'uri' => "formulize://permissions/group_perms_for_$sanitizedTitle"."_(form_$formId).json",
					'name' => "Permissions for all groups on $formTitle (form $formId)",
					'description' => "All the permissions for all groups on the $formTitle form (form $formId)",
					'mimeType' => 'application/json'
				];
			}
		}
		$this->resources = $this->resources + $groupPermsForFormResource;
		// resources for each groups permissions across all forms
		foreach($this->groups_list() as $groupData) {
			foreach($groupData as $thisGroupData) {
				$groupId = $thisGroupData['groupid'];
				$groupName = trans($thisGroupData['name']);
				$sanitizedGroupName = formulizeObject::sanitize_handle_name($groupName);
				$this->resources["form_permissions_for_group_$groupId"] = [
					'uri' => "formulize://permissions/form_perms_for_$sanitizedGroupName"."_(group_$groupId).json",
					'name' => "Permissions for $groupName (group $groupId) on all forms",
					'description' => "All the permissions for $groupName (group $groupId) all the forms in the system that they have access to.",
					'mimeType' => 'application/json'
				];
			}
		}

	}

	/**
	 * Handle resources list request
	 * @param string $id The JSON-RPC request ID from the MCP client
	 * @return array JSON-RPC response with list of resources
	 */
	private function handleResourcesList($id)
	{
		// Re-register resources to ensure fresh data
		$this->registerResources();

		return [
			'jsonrpc' => '2.0',
			'result' => [
				'resources' => array_values($this->resources)
			],
			'id' => $id
		];
	}

	/**
	 * Handle resource read request
	 * @param array $params Parameters from the JSON-RPC request
	 * @param string $id The JSON-RPC request ID from the MCP client
	 * @return array JSON-RPC response with resource contents or error if paramaters are missing
	 * @throws Exception If the resource cannot be read, or the URI format is invalid, or resource type is unknown
	 */
	private function handleResourceRead($params, $id)
	{

		$uri = $params['uri'] ?? '';

		if (!$uri) {
			return $this->JSONerrorResponse('Missing required parameter: uri', -32602, $id);
		}

		try {
			// Parse: formulize://schemas/BP_Readings_(form_1).json
			if (!preg_match('/^formulize:\/\/([^\/]+)\/([^\/\.]+)\.([^\/]+)$/', $uri, $matches)) {
				throw new Exception('Invalid resource URI format');
			}

			$type = $matches[1];      // "schemas"
			$filename = strtolower($matches[2]);  // "BP_Readings_(form_1)"
			$extension = $matches[3]; // "json"

			switch ($type) {
				case 'schemas':
				case 'permissions':
					$filenameParts = explode('_', $filename);
					$firstFilenamePart = $filenameParts[array_key_first($filenameParts)];
					$id = trim($filenameParts[array_key_last($filenameParts)], ")"); // remove ) from ...form_1)
					switch ($type) {
						case 'schemas':
							$methodName = 'form_schemas';
							break;
						case 'permissions':
							$methodName = $firstFilenamePart == 'form' ? 'group_permissions' : 'form_permissions'; // filename is either form_perm_for_group, or group_perm_for_form. method name is based on the last part, ie: the item we're basing the scope of data on.
							break;
					}
					$result = $this->$methodName($id);
					break;

				case 'system':
					$result = $this->$filename();
					break;

				default:
					throw new Exception('Unknown resource type: ' . $uri);
					break;
			}

			return [
				'jsonrpc' => '2.0',
				'result' => [
					'contents' => [
						[
							'uri' => $uri,
							'mimeType' => 'application/json',
							'text' => json_encode($result, JSON_PRETTY_PRINT)
						]
					]
				],
				'id' => $id
			];
		} catch (Exception $e) {
			return $this->JSONerrorResponse('Resource read failed: ' . $e->getMessage(), -32603, $id);
		}
	}

	/**
	 * Get form schema
	 * @param int $formId The ID of the form to get schema for
	 * @return array Form schema including elements and entry count
	 * @throws Exception If the form does not exist or cannot be retrieved
	 */
	private function form_schemas($formId)
	{

		if(security_check($formId) === false) {
			$this->sendAuthError('Permission denied: user does not have permission to view this form.', 403);
		}
		// Get form details
		$formSql = "SELECT * FROM " . $this->db->prefix('formulize_id') . " WHERE id_form = " . intval($formId);
		$formResult = $this->db->query($formSql);
		$formData = $this->db->fetchArray($formResult);

		if (!$formData) {
			throw new Exception('Form not found');
		}

		// Get form elements
		$elementsSql = "SELECT * FROM " . $this->db->prefix('formulize') . " WHERE id_form = " . intval($formId) . " ORDER BY ele_order";
		$elementsResult = $this->db->query($elementsSql);

		$elements = [];
		while ($row = $this->db->fetchArray($elementsResult)) {
			// if user can see the element or is a webmaster
			if($row['ele_display'] == 1
				OR in_array(XOOPS_GROUP_ADMIN, $this->userGroups)
				OR (
					strstr($row['ele_display'], ",")
					AND array_intersect($this->userGroups, explode(",", $row['ele_display']))
				)) {
				$additionalFields = [
					'element_id' => $row['ele_id'],
					'database_field_name' => $row['ele_handle']
				];
				unset($row['ele_id']); // since the database has this ancient shortform name, remove it and use 'element_id' explicitly
				$row = $additionalFields + $row;
				$elements[] = $row;
			}
		}

		// count entries
		$entryCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('formulize_' . $formData['form_handle']);
		$entryCountResult = $this->db->query($entryCountSql);
		$entryCount = $this->db->fetchArray($entryCountResult)['count'];

		return [
			'form' => $formData,
			'database_table_name' => $this->db->prefix('formulize_' . $formData['form_handle']),
			'entry_count' => $entryCount,
			'elements' => $elements,
			'element_count' => count($elements),
		]
		+ $this->screens_list($formId)
		+ $this->all_form_connections($formId);

	}

	/**
	 * Get all the permissions across all forms, for a given group
	 * @param int groupId - the id of the group
	 * @return array returns an array with the permissions this group has across all forms
	 */
	private function group_permissions($groupId) {

		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups) AND !in_array($groupId, $this->userGroups)) {
			$this->sendAuthError("Permission denied: user is not a member of group $groupId.", 403);
		}

		$groupDataSql = "SELECT groupid, `name`, `description` FROM " . $this->db->prefix('groups') . " WHERE groupid = ".intval($groupId);
		$groupDataResult = $this->db->query($groupDataSql);
		$groupData = $this->db->fetchArray($groupDataResult);

		$permissions = [];
		$forms = $this->list_forms();
		$gperm_handler = xoops_gethandler('groupperm');
		foreach($forms['forms'] as $formData) {
			$permissions[] = [
				'form_id' => $formData['id_form'],
				'form_title' => trans($formData['form_title']),
				'permissions' => $gperm_handler->getRights($formData['id_form'], $groupId, getFormulizeModId())
			];
		}

		return [
			'group_id' => $groupData['groupid'],
			'group_name' => $groupData['name'],
			'form_permissions' => $permissions
		];

	}

	/**
	 * Get all the permissions across all groups, for a given form
	 * @param int formId - the id of the form
	 * @return array returns an array with the permissions on this form across all groups
	 */
	private function form_permissions($formId) {

		if(!security_check($formId)) {
			$this->sendAuthError("Permission denied: user does not have access to form $formId.", 403);
		}

		// limit non webmasters to their own groups
		$groupLimitWhereClause = "";
		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$groupLimitWhereClause = "WHERE groupid IN (".implode(", ", array_filter($this->userGroups, 'is_numeric')).")";
		}

		// Get groups
		$groupsSql = "SELECT groupid, `name`, description FROM " . $this->db->prefix('groups') . " $groupLimitWhereClause ORDER BY name";
		$groupsResultIds = $this->db->query($groupsSql);
		$groupsResultNames = $this->db->query($groupsSql); // duplicate this since fetchColumn will move to next row... but we want all rows in both arrays... probably not better than just iterating through results with fetchArray, but something different
		$groupIds = $this->db->fetchColumn($groupsResultIds, 0); // groupid column 0
		$groupNames = $this->db->fetchColumn($groupsResultNames, 1); // name column 1

		$permissions = [];
		$gperm_handler = xoops_gethandler('groupperm');
		foreach($groupIds as $i=>$groupId) {
			$permissions[] = [
				'group_id' => $groupId,
				'group_name' => trans($groupNames[$i]),
				'permissions' => $gperm_handler->getRights($formId, $groupId, getFormulizeModId())
			];
		}

		$formData = $this->form_schemas($formId);

		return [
			'groupids' => $groupIds,
			'form_id' => $formId,
			'form_title' => trans($formData['form']['form_title']),
			'form_permissions' => $permissions
		];

	}

	/**
	 * Get system information
	 * @return array Returns an array with site name, Formulize version, PHP version, database version,
	 * form count, user count, group count, server time, and UTC time.
	 */
	private function system_info()
	{

		global $xoopsConfig;
		$gperm_handler = xoops_gethandler('groupperm');

		// Count forms
		if(in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$formCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('formulize_id');
			$formCountResult = $this->db->query($formCountSql);
			$formCount = $this->db->fetchArray($formCountResult)['count'];
		} else {
			$formIds = $gperm_handler->getItemIds('view_form', $this->userGroups, getFormulizeModId());
			$formCount = count($formIds);
		}

		// Count users
		$userCount = 'Unavailable';
		if(in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$userCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('users');
			$userCountResult = $this->db->query($userCountSql);
			$userCount = $this->db->fetchArray($userCountResult)['count'];
		}

		// Count groups
		$groupCount = count($this->userGroups);
		if(in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$groupCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('groups');
			$groupCountResult = $this->db->query($groupCountSql);
			$groupCount = $this->db->fetchArray($groupCountResult)['count'];
		}

		// Get module metadata
		$module_handler = xoops_gethandler('module');
		$formulizeModule = $module_handler->getByDirname("formulize");
		$metadata = $formulizeModule->getInfo();

		// server time zone is used by DB, so NOW() returns actual server time.
		// PHP is set to UTC
		$timeSQL = "SELECT NOW() as server_time";
		$timeResult = $this->db->query($timeSQL);
		$timeData = $this->db->fetchArray($timeResult);

		// check the version of mariadb or mysql
		$dbVersionData = ['version' => 'Unavailable'];
		if(in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$dbVersionSQL = "SELECT @@version as version";
			$dbVersionResult = $this->db->query($dbVersionSQL);
			$dbVersionData = $this->db->fetchArray($dbVersionResult);
		}

		return [
			'site_name' => $xoopsConfig['sitename'] ?? 'Unknown',
			'formulize_version' => $metadata['version'] ?? 'Unknown',
			'formulize_mcp_version' => FORMULIZE_MCP_VERSION,
			'author' => $metadata['author'] ?? 'Unknown',
			'license' => $metadata['license'] ?? 'Unknown',
			'php_version' => PHP_VERSION,
			'db_version' => $dbVersionData['version'] ?? 'Unknown',
			'form_count' => $formCount,
			'user_count' => $userCount,
			'group_count' => $groupCount,
			'server_timezone' => $xoopsConfig['server_TZ'] ?? 'Unknown',
			'server_time' => $timeData['server_time'] ?? 'Unknown',
			'utc_time' => date('Y-m-d H:i:s', time()),
			'authenticated_user_details' => $this->getAuthenticatedUserDetails()
		];
	}

	/**
	 * Get groups that the user is a member of, or all groups if the user is a webmaster
	 * @return array Returns an array with 'groups' (list of groups) and 'group_count' (number of groups). Each group is an associative array with 'groupid', 'name', and 'description.
	 */
	private function groups_list()
	{

		// limit non webmasters to their own groups
		$groupLimitWhereClause = "";
		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$groupLimitWhereClause = "WHERE groupid IN (".implode(", ", array_filter($this->userGroups, 'is_numeric')).")";
		}

		// Get groups
		$groupsSql = "SELECT groupid, name, description FROM " . $this->db->prefix('groups') . " $groupLimitWhereClause ORDER BY name";
		$groupsResult = $this->db->query($groupsSql);

		$groups = [];
		while ($row = $this->db->fetchArray($groupsResult)) {
			$groups[] = $row;
		}

		return [
			'groups' => $groups,
			'group_count' => count($groups),
		];
	}

	/**
	 * Get the connections between forms, based on the Primary Relationship
	 * Optionally can be limited to the connections of a specific form
	 * Only includes connections if the user has permission for at least one of the forms
	 * Each connection has a string describing the relationship, e.g. "Each Provice has many Cities", and the ids for the two forms involved.
	 * @param int|null $formId The ID of the form to limit connections to, or null for all connections
	 * @return array Returns an array with 'connections' (list of connections) and 'connection_count' (number of connections).
	 */
	private function all_form_connections($formId = null)
	{

		$connections = array();
		$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
		$primaryRelationshipSchema = $framework_handler->formatFrameworksAsRelationships(array($framework_handler->get(-1)), $formId);
		foreach($primaryRelationshipSchema[0]['content']['links'] as $link) {
			if(security_check($link['form1Id']) OR security_check($link['form2Id'])) {
				$connections[] = [
					'description' => "{$link['each']} {$link['form1']} {$link['has']} {$link['form2']}",
					'form1_id' => $link['form1Id'],
					'form2_id' => $link['form2Id'],
					'form1_connected_element_id' => $link['key1'],
					'form2_connected_element_id' => $link['key2'],
				];
			}
		}
		return [
			'connections' => $connections,
			'connection_count' => count($connections)
		];
	}

	/**
	 * List the applications
	 */
	private function applications_list() {
		$limitAppsSQL = "";
		$formIds = [];
		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$formsList = $this->list_forms();
			$forms = isset($formsList['forms']) ? $formsList['forms'] : [];
			$formIds = array_column($forms, 'id_form');
			$limitAppsSQL = 'AND afl.fid IN ('.implode(',', array_filter($formIds, 'is_numeric')).')';
		}
		// get the application and form data, in order! Proper order is required for the collection of data below to work
		$sql = "SELECT a.appid as appid, a.name as `name`, a.description as `desc`, f.id_form as form_id, f.form_title as form_title
			FROM ".$this->db->prefix("formulize_application_form_link")." AS afl
			LEFT JOIN ".$this->db->prefix("formulize_applications")." as a
			ON afl.appid = a.appid
			LEFT JOIN ".$this->db->prefix("formulize_id")." as f
			ON afl.fid = f.id_form
			WHERE afl.appid > 0
			$limitAppsSQL ORDER BY a.name, f.form_title";
		if(!$res = $this->db->query($sql)) {
			throw new Exception('Failed to lookup application data. '.$this->db->error());
		}
		$prevApp = 0;
		$applications = [];
		$forms = [];
		$id = '';
		$name = '';
		$desc = '';
		while($row = $this->db->fetchArray($res)) {
			if($prevApp AND $prevApp != $row['appid']) {
				$applications[] = $this->assignAppDataToApplicationsArray($id, $name, $desc, $forms);
				$forms = [];
			}
			$id = $row['appid'];
			$name = trans($row['name']);
			$desc = trans($row['desc']);
			if((empty($forms) OR !in_array($row['form_id'], array_column($forms, 'form_id'))) AND security_check($row['form_id'])) {
				$forms[] = [
					'form_id' => $row['form_id'],
					'form_title' => trans($row['form_title'])
				];
			}
			$prevApp = $id;
		}
		$applications[] = $this->assignAppDataToApplicationsArray($id, $name, $desc, $forms);
		return [
			'applications' => $applications,
			'application_count' => count($applications)
		];
	}

	private function assignAppDataToApplicationsArray($id, $name, $desc, $forms) {
		return [
			'id' => $id,
			'name' => $name,
			'description' => $desc,
			'forms' => $forms
		];
	}

	/**
	 * List the info about screens, or a single screen
	 * Optionally filtered by a formId. Naturally limits to screens on forms the user has access to.
	 * Optionally get a simple list of just the ids and titles
	 */
	private function screens_list($formId = null, $screenId = null, $simple = false) {
		$limitScreensSQL = "";
		if($formId AND !security_check($formId)) {
			$this->sendAuthError("Permission denied: user does not have access to form $formId.", 403);
		}
		// take passed in form id, otherwise, allow all if the user is an admin
		$formIds = [];
		if($formId) {
			$formIds = [ $formId ];
		} elseif(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$formsList = $this->list_forms();
			$forms = isset($formsList['forms']) ? $formsList['forms'] : [];
			$formIds = array_column($forms, 'id_form');
		}
		$limitScreensByFids = !empty($formIds) ? 'AND fid IN ('.implode(',', array_filter($formIds, 'is_numeric')).')' : '';
		$limitScreensBySid = $screenId ? 'AND sid = '.intval($screenId) : '';
		$sql = "SELECT * FROM ".$this->db->prefix('formulize_screen')." WHERE 1 $limitScreensBySid $limitScreensByFids ORDER BY fid,title";
		if(!$res = $this->db->query($sql)) {
			throw new Exception('Failed to lookup screen data. '.$this->db->error());
		}
		$screens = [];
		while($row = $this->db->fetchArray($res)) {
			if(security_check($row['fid'])) {
				if($simple) {
					$screens[] = [
						'screen_id' => $row['sid'],
						'screen_title' => $row['title']
					];
				} else {
					$screenSQL = "SELECT * FROM ".$this->db->prefix('formulize_screen_'.strtolower($row['type']))." WHERE sid = ".$row['sid'];
					$screenRes = $this->db->query($screenSQL);
					$screens[] = $row + $this->db->fetchArray($screenRes);
				}
			}
		}
		if($screenId AND empty($screens)) {
			$this->sendAuthError("Permission denied: user does not have access to the screen $screenId.", 403);
		}
		return [
			'screens' => $screens,
			'screen_count' => count($screens)
		];

	}

	/**
	 * Check if the user is a member of one or more groups
	 * Webmasters always pass regardless of their memberships
	 * @param array groups - an array of group ids to check
	 * @param boolean matchAll - a flag to indicate whether the user must be in all the groups or only one
	 * @return boolean returns true if the user is a member of a group (or all groups if matchAll is true), or false. Webmasters always return true.
	 */
	private function userBelongsToGroups($groups, $matchAll = false) {
		if(in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			return true;
		} else {
			foreach($groups as $groupId) {
				if(in_array($groupId, $this->userGroups) AND $matchAll == false) {
					return true;
				} elseif(!in_array($groupId, $this->userGroups) AND $matchAll == true) {
					return false;
				}
			}
		}
		return $matchAll; // we're still here, so either they matched none (when matchAll is false) or they matched them all (when matchAll is true)
	}

}
