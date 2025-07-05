<?php

trait resources {

	/**
	 * Register available MCP resources
	 * Presently, only webmasters can access resources.
	 * Sets the resources property of the FormulizeMCP class
	 * This method should be called in the constructor of the FormulizeMCP class
	 * @return void
	 */
	private function registerResources()
	{
		$this->resources = [];

		// Dynamically add form schema resources
		$formsList = $this->listForms(null);
		$forms = isset($formsList['forms']) ? $formsList['forms'] : [];
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
				$this->resources["group_permissions_for_form_$formId"] = [
					'uri' => "formulize://permissions/group_perms_for_$sanitizedTitle"."_(form_$formId).json",
					'name' => "Permissions for all groups on $formTitle (form $formId)",
					'description' => "All the permissions for all groups on the $formTitle form (form $formId)",
					'mimeType' => 'application/json'
				];
			}
		}

		// only webmasters can access system info
		if(in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$this->resources['system_info'] = [
				'uri' => 'formulize://system/info.json',
				'name' => 'System Information',
				'description' => 'Formulize system info and status',
				'mimeType' => 'application/json'
			];
		}

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

		// resources for each groups permissions across all forms
		foreach($this->groups_list() as $groupData) {
			$groupId = $groupData['groupid'];
			$groupName = trans($groupData['name']);
			$sanitizedGroupName = formulizeObject::sanitize_handle_name($groupName);
			$this->resources["form_permissions_for_group_$groupId"] = [
					'uri' => "formulize://permissions/form_perms_for_$sanitizedGroupName"."_(group_$groupId).json",
					'name' => "Permissions for $groupName (group $groupId) on all forms",
					'description' => "All the permissions for $groupName (group $groupId) all the forms in the system that they have access to.",
					'mimeType' => 'application/json'
				];
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
			return $this->errorResponse('Missing required parameter: uri', -32602, $id);
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
					$id = trim($filenameParts[array_key_last($filenameParts)], ")");
					switch ($type) {
						case 'schemas':
							$methodName = 'form_schemas';
							break;
						case 'permisisons':
							$methodName = $firstFilenamePart == 'form' ? 'group_permissions' : 'form_permissions'; // filename is either form_perm_for_group, or group_perm_for_form. method name is based on the last part, the item we're gathering based on.
							break;
					}
					$result = $this->$methodName($id);
					break;

				case 'system':
					if ($filename === 'info') {
						$result = $this->system_info();
					} elseif ($filename === 'groups') {
						$result = $this->groups_list();
					} elseif ($filename === 'all_form_connections') {
						$result = $this->connection_list();
					}
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
			return $this->errorResponse('Resource read failed: ' . $e->getMessage(), -32603, $id);
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
			$this->sendAuthError('Permission denied: user does not have permission to view this form.');
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
			$elements[] = $row;
		}

		// count entries
		$entryCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('formulize_' . $formData['form_handle']);
		$entryCountResult = $this->db->query($entryCountSql);
		$entryCount = $this->db->fetchArray($entryCountResult)['count'];

		return [
			'form' => $formData,
			'elements' => $elements,
			'element_count' => count($elements),
			'data_table' => $this->db->prefix('formulize_' . $formData['form_handle']),
			'entry_count' => $entryCount
		] + $this->getFormConnections($formId);
	}

	/**
	 * Get all the permissions across all forms, for a given group
	 * @param int groupId - the id of the group
	 * @return array returns an array with the permissions this group has across all forms
	 */
	private function group_permissions($groupId) {

		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups()) AND !in_array($groupId, $this->userGroups)) {
			$this->sendAuthError("Permission denied: user is not a member of group $groupId.");
		}

		$groupDataSql = "SELECT groupid, name, description FROM " . $this->db->prefix('groups') . " WHERE groupid = ".intval($groupId);
		$groupDataResult = $this->db->query($groupDataSql);
		$groupData = $this->db->fetchArray($groupDataResult);

		$permissions = [];
		$forms = $this->list_forms();
		$gperm_handler = xoops_gethandler('groupperm');
		foreach($forms['forms'] as $formData) {
			$permissions['form_id'] = $formData['id_form'];
			$permissions['form_title'] = trans($formData['form_title']);
			$permissions['permissions'] = $gperm_handler->getRights($formData['id_form'], $groupId, getFormulizeModId());
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
			$this->sendAuthError("Permission denied: user does not have access to form $formId.");
		}

		// limit non webmasters to their own groups
		$groupLimitWhereClause = "";
		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$groupLimitWhereClause = "WHERE groupid IN ".implode(", ", array_filter($this->userGroups, 'is_numeric'));
		}

		// Get groups
		$groupsSql = "SELECT groupid, name, description FROM " . $this->db->prefix('groups') . " $groupLimitWhereClause ORDER BY name";
		$groupsResult = $this->db->query($groupsSql);
		$groupIds = $this->db->fetchColumn($groupsResult, 0); // groupid column 0
		$groupNames = $this->db->fetchColumn($groupsResult, 1); // name column 1

		$permissions = [];
		$gperm_handler = xoops_gethandler('groupperm');
		foreach($groupIds as $i=>$groupId) {
			$permissions['group_id'] = $groupId;
			$permissions['group_name'] = trans($groupNames[$i]);
			$permissions['permissions'] = $gperm_handler->getRights($formId, $groupId, getFormulizeModId());
		}

		$formData = $this->form_schemas($formId);

		return [
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

		$this->verifyUserIsWebmaster(__FUNCTION__);

		global $xoopsConfig;

		// Count forms
		$formCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('formulize_id');
		$formCountResult = $this->db->query($formCountSql);
		$formCount = $this->db->fetchArray($formCountResult)['count'];

		// Count users
		$userCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('users');
		$userCountResult = $this->db->query($userCountSql);
		$userCount = $this->db->fetchArray($userCountResult)['count'];

		// Count groups
		$groupCountSql = "SELECT COUNT(*) as count FROM " . $this->db->prefix('groups');
		$groupCountResult = $this->db->query($groupCountSql);
		$groupCount = $this->db->fetchArray($groupCountResult)['count'];

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
		$versionSQL = "SELECT @@version as version";
		$versionResult = $this->db->query($versionSQL);
		$versionData = $this->db->fetchArray($versionResult);

		return [
			'site_name' => $xoopsConfig['sitename'] ?? 'Unknown',
			'formulize_version' => $metadata['version'] ?? 'Unknown',
			'formulize_mcp_version' => FORMULIZE_MCP_VERSION,
			'php_version' => PHP_VERSION,
			'db_version' => $versionData['version'] ?? 'Unknown',
			'form_count' => $formCount,
			'user_count' => $userCount,
			'group_count' => $groupCount,
			'server_time' => $timeData['server_time'] ?? 'Unknown',
			'utc_time' => date('Y-m-d H:i:s', time()),
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
			$groupLimitWhereClause = "WHERE groupid IN ".implode(", ", array_filter($this->userGroups, 'is_numeric'));
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
	private function connection_list($formId = null)
	{

		$connections = array();
		$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
		$primaryRelationshipSchema = $framework_handler->formatFrameworksAsRelationships(array($framework_handler->get(-1)), $formId);
		foreach($primaryRelationshipSchema[0]['content']['links'] as $link) {
			if(security_check($link['form1Id']) OR security_check($link['form1Id'])) {
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
