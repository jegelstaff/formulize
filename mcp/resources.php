<?php

trait resources {

	/**
	 * Register available MCP resources
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
			$formTitle = trans($form['form_title']);
			$sanitizedTitle = strtolower(formulizeObject::sanitize_handle_name($formTitle));

			// Form schema resource
			$this->resources["form_schema_$formId"] = [
				'uri' => "formulize://schemas/form_$formId:_".$sanitizedTitle.".json",
				'name' => "Schema of form $formId: $formTitle",
				'description' => "Complete schema, element definitions, and form connections, for form $formId: $formTitle",
				'mimeType' => 'application/json'
			];
		}

		// to add: group-form permissions! For a form, show all the permissions for each group.
		// MCP provides a cheap interface, let the AI assistant handle the details. We don't have to build a full presentation layer.

		// System-level resources
		$this->resources['system_info'] = [
			'uri' => 'formulize://system/info.json',
			'name' => 'System Information',
			'description' => 'Formulize system info and status',
			'mimeType' => 'application/json'
		];

		$this->resources['groups'] = [
			'uri' => 'formulize://system/groups.json',
			'name' => 'Groups',
			'description' => 'List of groups in the system. Groups are collections of users, and they have permissions assigned.',
			'mimeType' => 'application/json'
		];

		$this->resources['all_form_connections'] = [
			'uri' => 'formulize://system/all_form_connections.json',
			'name' => 'All Form Connections',
			'description' => 'All the connections between forms',
			'mimeType' => 'application/json'
		];
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
	 * @return array JSON-RPC response with resource contents or error
	 * @throws Exception If the resource cannot be read or parameters are missing
	 */
	private function handleResourceRead($params, $id)
	{
		$uri = $params['uri'] ?? '';

		if (!$uri) {
			return $this->errorResponse('Missing required parameter: uri', -32602, $id);
		}

		try {
			$result = $this->readResource($uri);
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
	 * Read a resource by URI
	 * @param string $uri The resource URI to read
	 * @return array Resource contents as JSON
	 * @throws Exception If the URI format is invalid or resource type is unknown
	 */
	private function readResource($uri)
	{
		// Parse: formulize://schemas/form_1:_BP_Readings.json
		if (!preg_match('/^formulize:\/\/([^\/]+)\/([^\/\.]+)\.([^\/]+)$/', $uri, $matches)) {
    	throw new Exception('Invalid resource URI format');
	}

	$type = $matches[1];      // "schemas"
	$filename = strtolower($matches[2]);  // "form_1:_BP_Readings"
	$extension = $matches[3]; // "json"

		switch ($type) {
			case 'schemas':
				if ($subtype === 'schema') {
					$filenameParts = explode('_', $filename);
					$formId = trim($filenameParts[1], ':');
					return $this->getFormSchema($formId);
				}
				break;

			case 'system':
				if ($filename === 'info') {
					return $this->getSystemInfo();
				} elseif ($filename === 'groups') {
					return $this->getGroups();
				} elseif ($filename === 'all_form_connections') {
					return $this->getFormConnections();
				}
				break;
		}

		throw new Exception('Unknown resource type: ' . $uri);
	}

	/**
	 * Get form schema
	 * @param int $formId The ID of the form to get schema for
	 * @return array Form schema including elements and entry count
	 * @throws Exception If the form does not exist or cannot be retrieved
	 */
	private function getFormSchema($formId)
	{
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
	 * Get system information
	 * @return array Returns an array with site name, Formulize version, PHP version, database version,
	 * form count, user count, group count, server time, and UTC time.
	 */
	private function getSystemInfo()
	{
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
	 * Get users and groups
	 * @return array Returns an array with 'groups' (list of groups) and 'group_count' (number of groups). Each group is an associative array with 'groupid', 'name', and 'description.
	 */
	private function getGroups()
	{
		// Get groups
		$groupsSql = "SELECT groupid, name, description FROM " . $this->db->prefix('groups') . " ORDER BY name";
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
	 * Each connection has a string describing the relationship, e.g. "Each Provice has many Cities", and the ids for the two forms involved.
	 * @param int|null $formId The ID of the form to limit connections to, or null for all connections
	 * @return array Returns an array with 'connections' (list of connections) and 'connection_count' (number of connections).
	 */
	private function getFormConnections($formId = null)
	{
		$connections = array();
		$framework_handler = xoops_getmodulehandler('frameworks', 'formulize');
		$primaryRelationshipSchema = $framework_handler->formatFrameworksAsRelationships(array($framework_handler->get(-1)), $formId);
		foreach($primaryRelationshipSchema[0]['content']['links'] as $link) {
			$connections[] = [
				'description' => "{$link['each']} {$link['form1']} {$link['has']} {$link['form2']}",
				'form1Id' => $link['form1Id'],
				'form2Id' => $link['form2Id']
			];
		}
		return [
			'connections' => $connections,
			'connection_count' => count($connections)
		];
	}

}
