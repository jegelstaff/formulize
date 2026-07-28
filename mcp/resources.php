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

		$this->resources['groups_list'] = [
			'uri' => 'formulize://system/groups_list.json',
			'name' => 'List of Groups',
			'description' => 'All the groups in the system. Groups are collections of users. Each group can have its own permissions to access a form, such as viewing the form, updating entries by other users in the same group, seeing entries by anyone in any group, etc.',
			'mimeType' => 'application/json'
		];

		$this->resources['users_list'] = [
			'uri' => 'formulize://system/users_list.json',
			'name' => 'List of Users',
			'description' => 'All the users in the system. Users are collected into groups. Users can be members of multiple groups. Permissions are assigned to groups, and users inherit all the permissions from all the groups they are a member of. Permissions include things like viewing a form, creating entries in a form, updating entries created by other users in the same group, seeing entries by anyone in any group, etc.',
			'mimeType' => 'application/json'
		];

		$this->resources['forms_list'] = [
			'uri' => 'formulize://system/forms_list.json',
			'name' => 'List of Forms',
			'description' => "All the forms in the system, and their elements, screens and connections to other forms. Forms are the main part of Formulize. Users enter data into forms, and can access data in forms that they or other users have entered. The interactions with forms and data is controlled by the permissions assigned to the groups, and users can be assigned to one or more groups.",
			'mimeType' => 'application/json'
		];

		$this->resources['screens_list'] = [
			'uri' => 'formulize://system/screens_list.json',
			'name' => 'List of Screens',
			'description' => "All the screens in the system. Screens are ways of presenting a form and its entries to users. Lists screens show the entries in the form, and have extensive configuration options to of entries are a type of screen. Versions of the form which users can fill in, are a type of form. , Connection are based pairs of elements, one in each form, that have matching values. Entries in the forms are connected when they have the same value in the paired elements, or when one element is 'linked' to the other, in which case the values in the linked element will be entry_ids in the other form (foreign keys).",
			'mimeType' => 'application/json'
		];

		$this->resources['form_connections_list'] = [
			'uri' => 'formulize://system/form_connections_list.json',
			'name' => 'List of Connections between Forms',
			'description' => "All the connections between forms. Connections are based pairs of elements, one in each form, that have matching values. Entries in the forms are connected when they have the same value in the paired elements, or when one element is 'linked' to the other, in which case the values in the linked element will be entry_ids in the other form (foreign keys).",
			'mimeType' => 'application/json'
		];

		// Dynamically add form schema resources
		$formsList = $this->forms_list();
		$forms = isset($formsList['forms']) ? $formsList['forms'] : [];
		$groupPermsForFormResources = [];
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
				$groupPermsForFormResources["group_permissions_for_form_$formId"] = [
					'uri' => "formulize://permissions/group_perms_for_$sanitizedTitle"."_(form_$formId).json",
					'name' => "Perms for $formTitle (form $formId)",
					'description' => "All the permissions for all groups on the $formTitle form (form $formId)",
					'mimeType' => 'application/json'
				];
			}
		}
		$this->resources = $this->resources + $groupPermsForFormResources;
		// resources for each groups permissions across all forms
		foreach($this->groups_list()['groups'] as $thisGroupData) {
			$groupId = $thisGroupData['groupid'];
			$groupName = trans($thisGroupData['name']);
			$sanitizedGroupName = formulizeObject::sanitize_handle_name($groupName);
			$this->resources["form_permissions_for_group_$groupId"] = [
				'uri' => "formulize://permissions/form_perms_for_$sanitizedGroupName"."_(group_$groupId).json",
				'name' => "Perms for $groupName (group $groupId)",
				'description' => "All the permissions for $groupName (group $groupId) all the forms in the system that they have access to.",
				'mimeType' => 'application/json'
			];
		}

	}

	/**
	 * Handle resources list request
	 *
	 * @return array JSON-RPC response with list of resources
	 */
	private function handleResourcesList()
	{
		// Re-register resources to ensure fresh data
		$this->registerResources();

		return [
			'resources' => array_values($this->resources)
		];
	}

	/**
	 * Handle resource read request
	 * @param array $params Parameters from the JSON-RPC request
	 * @return array JSON-RPC response with resource contents or error if paramaters are missing
	 * @throws Exception If the resource cannot be read, or the URI format is invalid, or resource type is unknown
	 */
	private function handleResourceRead($params)
	{
		$uri = $params['uri'] ?? '';

		if (!$uri) {
			throw new FormulizeMCPException(
				'Missing required parameter: uri',
				'missing_uri',
				-32602
			);
		}

		try {
			// Enhanced URI parsing with better validation
			$parsedUri = $this->parseResourceUri($uri);

			switch ($parsedUri['type']) {
				case 'schemas':
				case 'permissions':
					$result = $this->handleSchemaOrPermissionResource($parsedUri);
					break;
				case 'system':
					$result = $this->handleSystemResource($parsedUri);
					break;
				default:
					throw new FormulizeMCPException(
						'Unknown resource type: ' . $parsedUri['type'],
						'unknown_resource_type'
					);
			}

			return [
				'contents' => [
					[
						'uri' => $uri,
						'mimeType' => 'application/json',
						'text' => json_encode($result, JSON_PRETTY_PRINT)
					]
				]
			];
		} catch (Exception $e) {
			$context = [];
			$type = 'resource_read_error';
			if(is_a($e, 'FormulizeMCPException')) {
				$context = $e->getContext();
				$type = $e->getType();
			}
			$context = array_merge($context, [
					'requested_uri' => $uri,
					'uri_format' => 'formulize://type/resource_name.extension',
					'available_types' => ['system', 'schemas', 'permissions']
				]);
			throw new FormulizeMCPException(
				'Resource read failed: ' . $e->getMessage(),
				$type,
				-32603,
				$context
			);
		}
	}

	/**
	 * Parse and validate resource URI
	 */
	private function parseResourceUri($uri)
	{
		// Parse: formulize://schemas/form_name_(form_1).json
		if (!preg_match('/^formulize:\/\/([^\/]+)\/([^\/\.]+)\.([^\/]+)$/', $uri, $matches)) {
			throw new FormulizeMCPException(
				'Invalid resource URI format. Expected: formulize://type/name.extension',
				'invalid_uri',
			);
		}

		$type = $matches[1];
		$filename = $matches[2];
		$extension = $matches[3];

		// Validate extension
		if ($extension !== 'json') {
			throw new FormulizeMCPException(
				'Unsupported file extension: ' . $extension . '. Only .json is supported.',
				'invalid_uri'
			);
		}

		// Validate type
		$validTypes = ['system', 'schemas', 'permissions'];
		if (!in_array($type, $validTypes)) {
			throw new FormulizeMCPException(
				'Invalid resource type: ' . $type . '. Valid types: ' . implode(', ', $validTypes),
				'invalid_uri'
			);
		}

		return [
			'type' => $type,
			'filename' => strtolower($filename),
			'extension' => $extension,
			'full_match' => $matches
		];
	}

	/**
	 * Handle schema or permission resources
	 */
	private function handleSchemaOrPermissionResource($parsedUri)
	{
		$filename = $parsedUri['filename'];
		$type = $parsedUri['type'];

		$filenameParts = explode('_', $filename);

		if (empty($filenameParts)) {
			throw new FormulizeMCPException(
				'Invalid filename format for ' . $type . ' resource',
				'invalid_uri'
			);
		}

		$firstPart = $filenameParts[0];
		$secondLastPart = $filenameParts[count($filenameParts)-2];
		$lastPart = end($filenameParts);

		// Extract ID from last part (e.g., "(form_1)" -> "1")
		if (!$id = trim($lastPart, ")")) {
			throw new FormulizeMCPException(
				'Could not extract ID from filename: ' . $filename,
				'invalid_uri'
			);
		}
		// Extract type from second part (e.g., "(form_1)" -> "form", or "(group_1)" -> "group")
		if (!$idType = trim($secondLastPart, "(")) {
			throw new FormulizeMCPException(
				'Could not extract type from filename: ' . $filename,
				'invalid_uri'
			);
		}

		$id = intval($id);
		switch ($type) {
			case 'schemas':
				if ($idType !== 'form') {
					throw new FormulizeMCPException(
						'Schema resources must reference a form ID',
						'invalid_uri'
					);
				}
				return $this->form_schemas($id);

			case 'permissions':
				if ($firstPart === 'form' && $idType === 'group') {
					return $this->group_permissions($id);
				} elseif ($firstPart === 'group' && $idType === 'form') {
					return $this->form_permissions($id);
				} else {
					throw new FormulizeMCPException(
						'Invalid permission resource format. Expected form_perms_for_group or group_perms_for_form',
						'invalid_uri'
					);
				}

			default:
				throw new FormulizeMCPException(
					'Unhandled resource type in schema/permission handler: ' . $type,
					'invalid_uri'
				);
		}
	}

	/**
	 * Handle system resources
	 */
	private function handleSystemResource($parsedUri)
	{
		$filename = $parsedUri['filename'];

		// Dynamically determine valid system resources from registered resources
    $validSystemResources = $this->getSystemResourceNames();

		if (!in_array($filename, $validSystemResources)) {
			throw new FormulizeMCPException(
				'Unknown system resource: ' . $filename . '. Valid resources: ' . implode(', ', $validSystemResources),
				'invalid_uri'
			);
		}

		return $this->$filename();
	}

	/**
	 * Extract system resource names from registered resources
	 */
	private function getSystemResourceNames() {
			$systemResources = [];

			foreach ($this->resources as $resourceKey => $resource) {
					if (isset($resource['uri']) && preg_match('/^formulize:\/\/system\/([^\/\.]+)\.json$/', $resource['uri'], $matches)) {
							$systemResources[] = $matches[1];
					}
			}

			return array_unique($systemResources);
	}

	/**
	 * List all forms
	 * This function retrieves all forms from the Formulize database and returns them sorted by name.
	 * Includes simple element list, screens list, connections to other forms. Complete data on all forms.
	 * @param array $arguments An associative array containing any parameters for the request (not used in this case).
	 * @return array An array containing the list of forms.
	 */
	private function forms_list() {

		$sql = "SELECT id_form, form_title, singular, plural, form_handle as database_table_name FROM " . $this->db->prefix('formulize_id');

		$result = $this->db->query($sql);

		if (!$result) {
			return ['error' => 'Query failed', 'sql' => $sql];
		}

		$forms = [];
		$formTitles = [];
		while ($row = $this->db->fetchArray($result)) {
			$formId = $row['id_form'];
			if(security_check($formId)) {
				// element list not included to reduce context usage
				// add element identifiers to the $row, not all element data because that would be too much when listing all forms
				/*$row['elements'] = $this->metadataFields();
				$sql = "SELECT ele_handle as element_handle, ele_id as element_id, ele_required, ele_type, ele_display FROM " . $this->db->prefix('formulize') . " WHERE id_form = " . intval($formId) . " ORDER BY ele_order";
				if($elementsResult = $this->db->query($sql)) {
					while($elementRow = $this->db->fetchArray($elementsResult)) {
						if($elementRow['ele_display'] == 1
							OR in_array(XOOPS_GROUP_ADMIN, $this->userGroups)
							OR (
								strstr($elementRow['ele_display'], ",")
								AND array_intersect($this->userGroups, explode(",", $elementRow['ele_display']))
							)) {
								$row['elements'][] = $elementRow;
						}
					}
				}
				$row['element_count'] = count($row['elements']);
				*/

				$sql = "SELECT count(ele_handle) FROM " . $this->db->prefix('formulize') . " WHERE id_form = " . intval($formId);
				if($elementsResult = $this->db->query($sql)) {
					$elementCountRow = $this->db->fetchRow($elementsResult);
					$row['element_count'] = $elementCountRow[0];
				}

				$formTitle = trans($row['form_title']);
				$row['form_title'] = $formTitle; // Use the translated title for display
				$row['database_table_name'] = $this->db->prefix('formulize_'.$row['database_table_name']);
				$forms[] = $row + $this->form_connections_list($formId); // + $this->screens_list($formId, simple: true); // screens_list not included to reduce context usage
				$formTitles[] = $formTitle;
			}
		}

		array_multisort($formTitles, SORT_NATURAL, $forms);

		return [
			'forms' => $forms,
			'form_count' => count($forms)
		];

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
			throw new FormulizeMCPException(
				'Permission denied: user does not have access to form ' . intval($formId),
				'permission_denied',
			);
		}
		// Get form details
		$formSql = "SELECT `id_form`, `form_title`, `form_handle`, `pi`, `singleentry`, `entry_description`, `usage_notes`, `data_conventions`, `entries_are_users`, `entries_are_groups` FROM " . $this->db->prefix('formulize_id') . " WHERE id_form = " . intval($formId);
		$formResult = $this->db->query($formSql);
		$formData = $this->db->fetchArray($formResult);

		if (!$formData) {
			throw new FormulizeMCPException(
				'Form not found: ' . intval($formId),
				'form_not_found',
			);
		}

		// rename pi to principal_identifying_element for clarity
		$formData['principal_identifying_element'] = $formData['pi'];
		unset($formData['pi']);

		// report the entry limit the same way the create/update form tools accept it
		$formData['limit_entries'] = $this->readableLimitEntries($formData['singleentry']);
		unset($formData['singleentry']);

		// these are stored raw (escaping happens on output via getVar, not on the way in), so they are
		// passed through as-is - just normalize a NULL column to an empty string
		foreach(array('entry_description', 'usage_notes', 'data_conventions') as $descriptionField) {
			$formData[$descriptionField] = $formData[$descriptionField] ?? '';
		}

		// Whether the entries in this form are user accounts or groups. Reported because it changes what
		// writing an entry means: an entry here is not only a row, it is a person who can log in, or a set
		// of groups other permissions are given to. Nothing else about the form reveals that, and an
		// assistant that treats such a form as ordinary data will create accounts or groups without
		// realising it.
		$formData['entries_are_users'] = (bool) $formData['entries_are_users'];
		$formData['entries_are_groups'] = (bool) $formData['entries_are_groups'];
		if($formData['entries_are_users']) {
			$formData['about_entries_are_users'] = 'Each entry in this form has a corresponding user account. Creating an entry will also create an account that can log in; updating an entry can alter that account too. The account details are not stored in this form - the entry holds only a link to the account - so they are the same details the create_users and update_users tools reach, and editing them here or there comes to the same thing. Use this form rather than those tools when the form\'s own fields are involved too.';
		}
		if($formData['entries_are_groups']) {
			$formData['about_entries_are_groups'] = 'Each entry in this form generates its own group, or set of groups, named after the entry. There is always an "All Users" group created for the entry. Optionally, a webmaster can create additional categories, that will spawn additional groups. For example, on a Movie Studios form, a webmaster might create additional categories called Directors, Actors and Producers. In that case, creating a new entry in the form for "Disney" would spawn four groups: "Disney - All Users", "Disney - Directors", "Disney - Actors", and "Disney - Producers". Creating an entry creates those groups, with the name based on the value for the principal identifier element in the entry; changing the value of the principal identifier element in the entry causes the related groups to be renamed too. Groups are what permissions are given to, so an entry here is the beginning of a set of permissions rather than only a record. Use list_groups to see the template groups the sets are made from. Use the get_form_permissions_by_group tool to see what permissions the groups have.';
		}

		// Get form elements. Only the identifying properties of each element are included here, so that forms
		// with a large number of elements do not overwhelm the context of the AI assistant reading this.
		// Use the get_element_details tool to get the full settings of specific elements.
		$elementsSql = "SELECT `ele_id`, `ele_type`, `ele_caption`, `ele_handle`, `ele_display` FROM " . $this->db->prefix('formulize') . " WHERE id_form = " . intval($formId) . " ORDER BY ele_order";
		$elementsResult = $this->db->query($elementsSql);

		$elements = $this->metadataFields();
		while ($row = $this->db->fetchArray($elementsResult)) {
			if($this->userCanSeeElement($row['ele_display'])) {
				$elements[] = [
					'element_id' => $row['ele_id'], // since the database has the ancient shortform name ele_id, use 'element_id' explicitly
					'ele_handle' => $row['ele_handle'],
					'ele_caption' => $row['ele_caption'],
					'ele_type' => $row['ele_type']
				];
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
			'custom_code_present' => $this->customCodePresent($formData['form_handle']),
			'elements' => $elements,
			'element_count' => count($elements),
		]
		+ $this->screens_list($formId, simple: true)
		+ $this->form_connections_list($formId);

	}

	/**
	 * Render the stored per-group entry limit in the same shape the create/update form tools accept,
	 * so that what an AI assistant reads can be handed straight back when writing.
	 *
	 * Internally this is an array of groupid => value where the value stored under the Registered Users
	 * group is the base - the default for everybody, including anonymous visitors, whenever no group they
	 * belong to has its own setting. Almost every form only has that base value, so in that case a plain
	 * value is reported and the caller never has to know about the group keying. Only when a form actually
	 * has group-specific limits is the full map reported.
	 * See buildLimitEntriesArray() in tools.php for the write side of this.
	 *
	 * @param string|array $storedValue The raw singleentry column (normally a serialized array), or the already unserialized array from the form object
	 * @return string|array A plain value, or a map of group id => value when there are group-specific limits
	 */
	private function readableLimitEntries($storedValue) {
		if(is_array($storedValue)) {
			$limits = $storedValue;
		} else {
			// legacy rows can hold a bare value instead of an array
			$limits = (strpos((string)$storedValue, 'a:') === 0) ? unserialize($storedValue) : array(XOOPS_GROUP_USERS => $storedValue);
		}
		if(!is_array($limits)) {
			$limits = array();
		}
		$base = $limits[XOOPS_GROUP_USERS] ?? 'off';
		unset($limits[XOOPS_GROUP_USERS]);
		if(empty($limits)) {
			return $base ?: 'off';
		}
		return array(XOOPS_GROUP_USERS => $base ?: 'off') + $limits;
	}

	/**
	 * Look up the full settings of specific elements, identified by handle and/or id.
	 *
	 * Only elements the authenticated user is allowed to see are returned: the form has to pass
	 * security_check, and the element's ele_display group list has to include one of the user's groups
	 * (webmasters see everything). Anything the caller asked for that could not be returned - because it
	 * does not exist, is in a form they cannot access, is hidden from their groups, or is in a different
	 * form than the one they named - is reported back rather than silently omitted.
	 *
	 * @param array $identifiers Element handles (strings) and/or element ids (integers)
	 * @param int $formId Optional. Restrict the lookup to this form, and validate that the elements belong to it.
	 * @return array 'elements', 'element_count', and 'elements_not_found' when applicable
	 */
	private function element_details($identifiers, $formId = 0) {

		$formId = intval($formId);
		if($formId AND !security_check($formId)) {
			throw new FormulizeMCPException(
				'Permission denied: user does not have access to form ' . $formId,
				'permission_denied',
			);
		}

		$ids = [];
		$handles = [];
		foreach($identifiers as $identifier) {
			if(is_numeric($identifier)) {
				$ids[] = intval($identifier);
			} elseif(is_string($identifier) AND trim($identifier) !== '') {
				$handles[] = $this->db->quoteString(trim($identifier));
			}
		}
		if(empty($ids) AND empty($handles)) {
			throw new FormulizeMCPException(
				'No valid element handles or ids were provided.',
				'invalid_data',
				context: [ 'hint' => 'Each item in the elements array must be an element handle (string) or an element id (number). Use get_form_details to find them.' ]
			);
		}

		$matchClauses = [];
		if(!empty($ids)) { $matchClauses[] = "ele_id IN (".implode(',', $ids).")"; }
		if(!empty($handles)) { $matchClauses[] = "ele_handle IN (".implode(',', $handles).")"; }
		$formClause = $formId ? " AND id_form = $formId" : "";
		$sql = "SELECT * FROM ".$this->db->prefix('formulize')." WHERE (".implode(' OR ', $matchClauses).")$formClause ORDER BY id_form, ele_order";

		if(!$result = $this->db->query($sql)) {
			throw new FormulizeMCPException(
				'Failed to look up element data. '.$this->db->error(),
				'database_error'
			);
		}

		// the list of fields that are stored serialized is declared in one place in the codebase, so read it
		// from there rather than repeating it here - that way a newly added serialized field is handled
		// automatically instead of being returned to the AI assistant as a raw serialized blob
		$serializedFields = FormulizeObject::serializedDBFields();
		$serializedElementFields = $serializedFields['formulize'] ?? [];

		$elements = [];
		$found = [];
		while($row = $this->db->fetchArray($result)) {
			if(!security_check($row['id_form'])) {
				continue; // a form this user has no access to
			}
			if(!$this->userCanSeeElement($row['ele_display'])) {
				continue;
			}
			$found[strtolower($row['ele_handle'])] = true;
			$found[$row['ele_id']] = true;
			$elements[] = $this->prepareElementRow($row, $serializedElementFields);
		}

		$notFound = [];
		foreach($identifiers as $identifier) {
			$key = is_numeric($identifier) ? intval($identifier) : strtolower(trim((string)$identifier));
			if(!isset($found[$key])) {
				$notFound[] = $identifier;
			}
		}

		if(empty($elements)) {
			throw new FormulizeMCPException(
				'None of the requested elements could be found: '.implode(', ', $notFound),
				'unknown_element',
				context: array_filter([
					'requested' => $identifiers,
					'valid_element_handles' => $formId ? $this->elementHandlesForForm($formId) : null,
					'hint' => 'Use get_form_details to see the elements in a form.'
				])
			);
		}

		$response = [
			'elements' => $elements,
			'element_count' => count($elements)
		];
		if(!empty($notFound)) {
			// a partial result is more useful than an error, so report the misses alongside the hits
			$response['elements_not_found'] = $notFound;
			if($formId) {
				$response['valid_element_handles'] = $this->elementHandlesForForm($formId);
			}
		}
		return $response;
	}

	/**
	 * Turn a raw row from the elements table into the shape reported to an AI assistant:
	 * serialized fields unserialized, condition fields converted to readable condition lists, and the
	 * ancient ele_id column name reported explicitly as element_id.
	 * @param array $row A row from the formulize elements table
	 * @param array $serializedElementFields The fields on that table which are stored serialized
	 * @return array The prepared element
	 */
	private function prepareElementRow($row, $serializedElementFields) {
		foreach($serializedElementFields as $field) {
			if(!isset($row[$field])) {
				continue;
			}
			$row[$field] = ($row[$field] === '' OR $row[$field] === null) ? [] : unserialize($row[$field]);
			// both of these are built by parseSubmittedConditions(), so both are stored as parallel arrays
			// and both need converting into something readable
			if($field == 'ele_filtersettings' OR $field == 'ele_disabledconditions') {
				$row[$field] = $this->tidyUpOldConditionsArrayFormat($row[$field]);
			}
		}
		$additionalFields = [
			'element_id' => $row['ele_id'],
			'form_id' => $row['id_form']
		];
		unset($row['ele_id'], $row['id_form']);
		return $additionalFields + $row;
	}

	/**
	 * Whether the authenticated user is allowed to see an element, based on its ele_display setting.
	 * Webmasters always can. Otherwise ele_display is either 1 (everyone) or a comma separated group list.
	 * @param string $eleDisplay The element's ele_display value
	 * @return bool
	 */
	private function userCanSeeElement($eleDisplay) {
		return ($eleDisplay == 1
			OR in_array(XOOPS_GROUP_ADMIN, $this->userGroups)
			OR (
				strstr($eleDisplay, ",")
				AND array_intersect($this->userGroups, explode(",", $eleDisplay))
			));
	}

	/**
	 * The handles of the elements in a form, for putting into an error response so the AI assistant can
	 * correct a bad identifier without having to make another call.
	 * @param int $formId
	 * @return array
	 */
	private function elementHandlesForForm($formId) {
		$handles = [];
		$sql = "SELECT ele_handle, ele_display FROM ".$this->db->prefix('formulize')." WHERE id_form = ".intval($formId)." ORDER BY ele_order";
		if($result = $this->db->query($sql)) {
			while($row = $this->db->fetchArray($result)) {
				if($this->userCanSeeElement($row['ele_display'])) {
					$handles[] = $row['ele_handle'];
				}
			}
		}
		return $handles;
	}

	/**
	 * Work out what would be lost or broken by deleting an element, so the impact can be reported before
	 * anything is destroyed.
	 *
	 * Two different kinds of consequence are reported, and the distinction matters. Some things the delete
	 * handles itself: the data column is dropped, the element is removed from form screen pages, and the
	 * form's principal identifier is reset if it was this element. Other things are NOT cleaned up, and are
	 * simply left pointing at an element that no longer exists - list screen columns, derived value formulas,
	 * and custom code that names the handle. Those are the references that will actually break.
	 *
	 * @param object $elementObject The element being considered for deletion
	 * @return array The impact report
	 */
	private function elementDeletionImpact($elementObject) {

		$elementId = intval($elementObject->getVar('ele_id'));
		$formId = intval($elementObject->getVar('fid'));
		$handle = $elementObject->getVar('ele_handle');
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		$formObject = $form_handler->get($formId);

		$impact = [
			'element_id' => $elementId,
			'element_handle' => $handle,
			'element_caption' => $elementObject->getVar('ele_caption'),
			'element_type' => $elementObject->getVar('ele_type'),
			'form_id' => $formId,
			'form_title' => $formObject ? $formObject->getVar('form_title') : null,
		];

		// how much data would be destroyed
		$impact['stores_data'] = (bool) $elementObject->hasData;
		$impact['entries_with_a_value_in_this_element'] = 0;
		if($elementObject->hasData AND $formObject) {
			$dataTable = $this->db->prefix('formulize_'.$formObject->getVar('form_handle'));
			$countSql = "SELECT COUNT(*) AS c FROM `$dataTable` WHERE `".formulize_db_escape($handle)."` IS NOT NULL AND `".formulize_db_escape($handle)."` != ''";
			if($countResult = $this->db->query($countSql)) {
				$countRow = $this->db->fetchArray($countResult);
				$impact['entries_with_a_value_in_this_element'] = intval($countRow['c']);
			}
		}

		// the form's principal identifier is reset to nothing if this element was it
		$impact['is_the_principal_identifier'] = ($formObject AND intval($formObject->getVar('pi')) === $elementId);

		// form screens the element will be removed from automatically
		$impact['removed_from_form_screens'] = [];
		$screenSql = "SELECT s.sid, s.title, m.pages FROM ".$this->db->prefix('formulize_screen')." s
			INNER JOIN ".$this->db->prefix('formulize_screen_multipage')." m ON m.sid = s.sid
			WHERE s.fid = $formId";
		if($screenResult = $this->db->query($screenSql)) {
			while($screenRow = $this->db->fetchArray($screenResult)) {
				$pages = @unserialize($screenRow['pages']);
				if(!is_array($pages)) { continue; }
				foreach($pages as $pageElements) {
					if(is_array($pageElements) AND in_array($elementId, array_map('intval', $pageElements))) {
						$impact['removed_from_form_screens'][] = ['screen_id' => intval($screenRow['sid']), 'screen_title' => $screenRow['title']];
						break;
					}
				}
			}
		}

		// references that are NOT cleaned up, and so will be left broken
		$broken = [];

		// list screen columns, hidden columns and inline editable columns
		$listSql = "SELECT s.sid, s.title, l.advanceview, l.hiddencolumns, l.decolumns FROM ".$this->db->prefix('formulize_screen')." s
			INNER JOIN ".$this->db->prefix('formulize_screen_listofentries')." l ON l.sid = s.sid";
		if($listResult = $this->db->query($listSql)) {
			while($listRow = $this->db->fetchArray($listResult)) {
				$referenced = false;
				$advanceview = @unserialize($listRow['advanceview']);
				if(is_array($advanceview)) {
					foreach($advanceview as $column) {
						if(is_array($column) AND isset($column[0]) AND $column[0] === $handle) { $referenced = true; }
					}
				}
				foreach(array('hiddencolumns', 'decolumns') as $columnSetting) {
					$columnList = @unserialize($listRow[$columnSetting]);
					if(is_array($columnList) AND (in_array($elementId, array_map('intval', $columnList)) OR in_array($handle, $columnList))) {
						$referenced = true;
					}
				}
				if($referenced) {
					$broken[] = "list screen ".intval($listRow['sid'])." (".$listRow['title'].") uses this element as a column";
				}
			}
		}

		// Other elements that name this one. There are two ways a handle gets referenced: as a PHP variable
		// in derived value code ($some_handle), and in curly braces in the default value of a text or
		// textarea element and in the content of a static content element ({some_handle}).
		// The SQL is only a coarse filter - it is deliberately loose, because an underscore is a single
		// character wildcard in LIKE and handles are full of them. The precise test happens in PHP, where a
		// trailing name character can be excluded so that $artifacts_year does not match $artifacts_year_era.
		// The excluded set is PHP's own grammar for what may continue a variable name,
		// [a-zA-Z0-9_\x80-\xff], which includes the high bytes that let variable names hold accented and
		// other non-ASCII characters. No /u modifier: that grammar is defined in bytes, and a multibyte
		// character is a sequence of bytes that are all inside the excluded range anyway.
		$continuesAName = '(?![A-Za-z0-9_\x80-\xff])';
		$referencePattern = '/(\$'.preg_quote($handle, '/').$continuesAName.'|\{'.preg_quote($handle, '/').'\})/';
		$referenceSql = "SELECT ele_id, ele_handle, ele_type, id_form, ele_value FROM ".$this->db->prefix('formulize')."
			WHERE ele_value LIKE ".$this->db->quoteString('%'.$handle.'%');
		if($referenceResult = $this->db->query($referenceSql)) {
			while($referenceRow = $this->db->fetchArray($referenceResult)) {
				if(intval($referenceRow['ele_id']) === $elementId) { continue; }
				if(preg_match($referencePattern, (string) $referenceRow['ele_value'])) {
					$broken[] = "the ".$referenceRow['ele_type']." element '".$referenceRow['ele_handle']."' (form ".intval($referenceRow['id_form']).") refers to this element by name in its settings";
				}
			}
		}

		// custom code files that name the handle, including the code files written for derived value and
		// static content elements, which live in the same folder
		$codeDir = XOOPS_ROOT_PATH.'/modules/formulize/code/';
		if(is_dir($codeDir)) {
			foreach((array) glob($codeDir.'*.php') as $codeFile) {
				$contents = file_get_contents($codeFile);
				if($contents !== false AND preg_match($referencePattern, $contents)) {
					$broken[] = "custom code file '".basename($codeFile)."' refers to this element by name";
				}
			}
		}

		$impact['references_that_will_be_left_broken'] = $broken;

		return $impact;
	}

	/**
	 * Report who can do what with a form.
	 *
	 * Reported as a list of permission SETS rather than a row per group, because a system that uses
	 * form-based groups can easily have hundreds of groups whose permissions are byte identical - entry
	 * groups have theirs copied from their template group - and listing them individually would be almost
	 * entirely repetition. Groups whose grants match are therefore reported together, and the enumerated
	 * names are capped so that a very large family stays readable.
	 *
	 * Two things beyond the permissions themselves are reported because without them the answer would be
	 * misleading: whether the form's permissions are inherited from another form (in which case they cannot
	 * be edited here at all), and whether a group has visibility conditions, which further restrict which
	 * entries its members see in a way no permission name reveals.
	 *
	 * @param int $formId The form to report on
	 * @param array $groupIds Optional. Restrict the report to these groups.
	 * @return array The permission report
	 */
	private function form_permissions_report($formId, $groupIds = []) {

		$formId = intval($formId);
		$form_handler = xoops_getmodulehandler('forms', 'formulize');
		if(!$formObject = $form_handler->get($formId)) {
			throw new FormulizeMCPException(
				"Form not found: $formId",
				'form_not_found',
				context: [ 'hint' => 'Use the list_forms tool to see the forms in this system.' ]
			);
		}
		$moduleId = getFormulizeModId();

		$groupLimit = '';
		if(!empty($groupIds)) {
			$groupIds = array_filter(array_map('intval', (array) $groupIds));
			if(!empty($groupIds)) {
				$groupLimit = ' AND p.gperm_groupid IN ('.implode(',', $groupIds).')';
			}
		}

		// every grant on this form in one query, with the group names alongside
		$sql = "SELECT p.gperm_groupid AS group_id, g.name AS group_name, p.gperm_name AS permission
			FROM ".$this->db->prefix('group_permission')." p
			LEFT JOIN ".$this->db->prefix('groups')." g ON g.groupid = p.gperm_groupid
			WHERE p.gperm_itemid = $formId AND p.gperm_modid = ".intval($moduleId)."$groupLimit
			ORDER BY p.gperm_groupid, p.gperm_name";
		if(!$result = $this->db->query($sql)) {
			throw new FormulizeMCPException(
				'Failed to look up the permissions for this form. '.$this->db->error(),
				'database_error'
			);
		}
		$byGroup = [];
		while($row = $this->db->fetchArray($result)) {
			$groupId = intval($row['group_id']);
			if(!isset($byGroup[$groupId])) {
				$byGroup[$groupId] = [ 'name' => $row['group_name'], 'permissions' => [] ];
			}
			$byGroup[$groupId]['permissions'][] = $row['permission'];
		}

		// which of these groups are generated from a form's entries, so a template group is not mistaken
		// for an unused one: it has no members, but its permissions are copied to every group made from it
		$groupKinds = [];
		if(!empty($byGroup)) {
			$kindSql = "SELECT groupid, is_group_template, form_id, entry_id FROM ".$this->db->prefix('groups')."
				WHERE groupid IN (".implode(',', array_map('intval', array_keys($byGroup))).")";
			if($kindResult = $this->db->query($kindSql)) {
				while($kindRow = $this->db->fetchArray($kindResult)) {
					$groupKinds[intval($kindRow['groupid'])] = $this->groupKind($kindRow);
				}
			}
		}

		// custom groupscope targets, and any visibility conditions
		$groupScope = $this->groupScopeTargetsForForm($formId);
		$visibilityConditions = $this->visibilityConditionsForForm($formId);

		// collapse groups whose grants are identical in every respect
		$sets = [];
		foreach($byGroup as $groupId => $groupData) {
			// The webmaster group is left out entirely. icms_member_groupperm_Handler::checkRight() short
			// circuits to true whenever that group is among the ones being tested, so whatever is recorded
			// against it means nothing - the Artifacts form stores only view_form and edit_form for
			// webmasters, who can nonetheless do everything. Reporting those rows would describe a limit
			// that does not exist, so the fact is stated once on the response instead.
			// Loose comparison on purpose: the group constants are defined as strings in mainfile.php
			// ('1', '2', '3'), so a strict comparison against an integer group id never matches.
			if($groupId == XOOPS_GROUP_ADMIN) {
				continue;
			}
			sort($groupData['permissions']);
			$scope = $groupScope[$groupId] ?? [];
			sort($scope);
			$conditions = $visibilityConditions[$groupId] ?? [];
			// Permissions come in two flavours and are reported as two fields. Access decides whether members
			// can reach the form at all, and makes this group one of "their groups" for any group-scoped
			// permission its members hold, from any group - no other permission has that second effect.
			// Abilities are everything else: what members may do and see once they are in. A group can
			// legitimately grant access and no abilities, or abilities and no access, and both read as
			// deliberate in this shape where a single flat list of names does not.
			$grantsAccess = in_array('view_form', $groupData['permissions']);
			// view_their_own_entries and manage_own are written for every group the admin interface touches,
			// whatever was ticked, so they distinguish nothing and are stated once on the response instead.
			// set_form_permissions refuses them as input for the same reason, and reporting what the write
			// tool will not accept is the read/write mismatch worth avoiding. Leaving them in also made a
			// group with no real abilities look as though it had two.
			$abilities = array_values(array_diff($groupData['permissions'], ['view_form', 'view_their_own_entries', 'manage_own']));
			$key = md5(serialize([$grantsAccess, $abilities, $scope, $conditions]));
			if(!isset($sets[$key])) {
				$sets[$key] = [
					'groups' => [],
					'group_count' => 0,
					'grants_access' => $grantsAccess,
					'abilities' => $abilities,
					'what_this_set_provides' => $this->describeVisibility($groupData['permissions'], $scope),
				];
				if(!empty($conditions)) {
					$sets[$key]['visibility_conditions'] = $conditions;
					$sets[$key]['about_these_conditions'] = 'Members of these groups only see entries matching these conditions. This applies to anyone in the group, including users who get their access to the form from a different group.';
				}
			}
			$sets[$key]['group_count']++;
			if(count($sets[$key]['groups']) < 15) { // enough to recognise the family without listing hundreds
				$group = [ 'group_id' => $groupId, 'name' => $groupData['name'] ];
				if(($groupKinds[$groupId] ?? 'regular') !== 'regular') {
					$group['group_kind'] = $groupKinds[$groupId];
				}
				$sets[$key]['groups'][] = $group;
			}
		}
		foreach($sets as $key => $set) {
			if($set['group_count'] > count($set['groups'])) {
				$sets[$key]['groups_not_listed'] = $set['group_count'] - count($set['groups']);
			}
		}

		// which forms this one's permissions are tied to, in either direction
		$childIds = [];
		$childSql = "SELECT id_form FROM ".$this->db->prefix('formulize_id')." WHERE parent_perm_fid = $formId";
		if($childResult = $this->db->query($childSql)) {
			while($childRow = $this->db->fetchArray($childResult)) {
				$childIds[] = intval($childRow['id_form']);
			}
		}
		$parentId = intval($formObject->getVar('parent_perm_fid'));

		$response = [
			'form_id' => $formId,
			'form_title' => $formObject->getVar('form_title'),
			// deliberately before the data: two different AI assistants have read a per-group report of
			// this shape and concluded that a group granting nothing means its members have no access,
			// when in practice those same people hold access through another group they also belong to
			'how_to_interpret_group_permissions' => $this->describeHowToInterpretPermissions(!empty($groupIds)),
			'inherits_permissions_from_form' => $parentId ?: null,
			'forms_inheriting_permissions_from_this_form' => $childIds,
			'permission_sets' => array_values($sets),
			'permission_set_count' => count($sets),
			'always_on_for_every_group' => ['view_their_own_entries', 'manage_own'],
			'about_webmasters' => 'Members of the Webmasters group can do anything on every form and can see every entry, no matter what permissions are set, so that group is not listed above.',
		];
		if($parentId) {
			$response['note_about_inheritance'] = "These permissions are inherited from form $parentId and are maintained there. They cannot be changed on this form.";
		}
		if(in_array('form_based_template', $groupKinds)) {
			$response['about_form_based_groups'] = "Some of the groups above are marked form_based_template.\n\n".$this->describeFormBasedGroups();
		}
		// an empty list reads as "nobody can use this form", which is wrong in both directions: webmasters
		// always can, and a narrowed report says nothing about the groups that were left out of it
		if(empty($sets)) {
			$response['note_about_the_empty_result'] = !empty($groupIds)
				? 'None of the groups you asked about have any permissions on this form. That does not tell you the form is unused - other groups may have full access to it, and this report was narrowed to the groups you named. Call again without group_ids to see every group.'
				: 'No group has been given any permissions on this form, so only webmasters can reach it. That is normal for a form that exists to support other forms - holding a list of options that are chosen from elsewhere, for example - rather than being worked with directly.';
		}
		return $response;
	}

	/**
	 * Explain how to read a per-group permission report, and say so before the data rather than after it.
	 *
	 * Worth the words: two different AI assistants, shown a report of this shape, both concluded that a
	 * group holding nothing meant its members had no access - when those same people in fact reached the
	 * form through another group they also belonged to. The advice is deliberately concrete rather than
	 * conceptual, because "combine the permissions across a user's groups" is a mental operation an
	 * assistant can get wrong, whereas re-calling this tool with that user's group ids is a request the
	 * tool answers itself.
	 *
	 * REVISIT WHEN get_form_permissions_for_user EXISTS: the unscoped branch below tells the caller to look
	 * up a user's groups and call again with those ids, which is the best available route only while there
	 * is no per-user tool. Two more copies of that advice live in tools.php, on this tool's description and
	 * on its group_ids parameter. The opening paragraph and the scoped branch stay correct either way.
	 *
	 * @param bool $scopedToRequestedGroups Whether the caller narrowed the report with group_ids
	 * @return string
	 */
	private function describeHowToInterpretPermissions($scopedToRequestedGroups) {
		$explanation = 'These are the permissions configured on each group. They are not necessarily what any particular user can do: users can be members of more than one group, and receive all the permissions from all their groups.

';
		$explanation .= $scopedToRequestedGroups
			? 'This report covers only the groups you asked about. If those are the groups a single user belongs to, then everything shown here applies all together to that user, and equally to anyone else who belongs to the same combination of groups.

'
			: 'To see what one user gets, look up their groups with the list_a_users_groups tool, then call this tool again passing those group ids in the group_ids parameter. The report is then narrowed to that user\'s combination, and describes what they can do - and what anyone else in the same combination of groups can do. Doing that once for a real user is also the quickest way to learn about the organization of groups in this system, which is worth knowing before drawing conclusions from any single group\'s permissions: membership patterns are usually just conventional, and are not necessarily recorded or enforced anywhere.

One arrangement you will see is a series of groups related to a single entity, subdividing users by role or function - Eastern Managers, Eastern Staff, Eastern Clients, Western Managers, Western Staff, Western Clients - often alongside higher level groups covering everyone of a given type: All Managers, All Staff, All Clients. How the permissions are then distributed across those groups varies: each group may grant access and abilities, or the abilities may be defined once on a higher level group while the narrower groups grant access and thereby determine which groups count as "their groups" for group members with group-level abilities (update_group_entries, delete_group_entries, view_groupscope). Several arrangements are properly supported, so none of them is unusual, and the one in front of you was chosen deliberately. Work out which is in use and extend it consistently rather than reshaping it toward any particular style.

';
		$explanation .= 'A group that appears to grant nothing does not mean its members lack access - they very often reach the form through another group they also belong to. Groups are containers for permissions, not descriptions of users.';
		return $explanation;
	}

	/**
	 * The custom groupscope targets set on a form, as groupid => array of target group ids.
	 * An absent entry means the group uses the default, which is every group the user belongs to that can
	 * view this form. Only rows with a real target group are stored, so absence is the normal case.
	 * @param int $formId
	 * @return array
	 */
	private function groupScopeTargetsForForm($formId) {
		$targets = [];
		$sql = "SELECT groupid, view_groupid FROM ".$this->db->prefix('formulize_groupscope_settings')."
			WHERE fid = ".intval($formId)." AND view_groupid > 0";
		if($result = $this->db->query($sql)) {
			while($row = $this->db->fetchArray($result)) {
				$targets[intval($row['groupid'])][] = intval($row['view_groupid']);
			}
		}
		return $targets;
	}

	/**
	 * The visibility conditions set on a form, as groupid => readable list of conditions.
	 *
	 * These restrict which entries a group's members may see, over and above the permissions, and nothing
	 * in the permission names reveals them, so they have to be reported alongside. A row is commonly
	 * present with an empty conditions array - the admin UI writes one whenever the panel is saved - so
	 * only rows that actually hold conditions are returned. Reporting an empty row as "this group has
	 * visibility conditions" would be wrong, and would make an unrestricted group look restricted.
	 *
	 * @param int $formId
	 * @return array groupid => conditions, in the readable form used elsewhere
	 */
	private function visibilityConditionsForForm($formId) {
		$conditions = [];
		$sql = "SELECT groupid, filter FROM ".$this->db->prefix('formulize_group_filters')." WHERE fid = ".intval($formId);
		if($result = $this->db->query($sql)) {
			while($row = $this->db->fetchArray($result)) {
				$filter = @unserialize($row['filter']);
				if(!is_array($filter) OR empty($filter) OR empty($filter[0])) {
					continue; // a row with no conditions in it is the same as having none
				}
				$conditions[intval($row['groupid'])] = $this->tidyUpOldConditionsArrayFormat($filter);
			}
		}
		return $conditions;
	}

	/**
	 * Put what a permission set grants into a sentence, because working it out from three permission names
	 * is exactly the part of Formulize permissions that people get wrong.
	 *
	 * Phrased as what the SET grants, never as what the group's members can do. People are usually in
	 * several groups and receive the combination of everything all of those groups grant, so a set that
	 * grants nothing does not mean its members have no access - they may well reach the form through
	 * another group entirely.
	 *
	 * @param array $permissions The permissions in this set
	 * @param array $scopeTargets Custom groupscope targets, if any
	 * @return string
	 */
	private function describeVisibility($permissions, $scopeTargets) {
		if(!in_array('view_form', $permissions)) {
			// abilities held without view_form are not inert - they apply once the user reaches the form
			// through some other group, which is how a broad group can carry the abilities for a site while
			// narrow groups carry only view_form and thereby define who sees whose entries
			$abilities = array_diff($permissions, ['view_their_own_entries', 'manage_own']);
			if(!empty($abilities)) {
				return "No access to the form from this set on its own, but these permissions are not inactive: a user can be a member of another group that grants access to the form. A broad group that holds abilities and a narrower group that grants access, is a deliberate and common arrangement - the narrower groups are then what decide which groups are \"their groups\" for the purposes of group-level permissions (update_group_entries, delete_group_entries, view_groupscope).";
			}
			return "No access to the form from this set. Anyone in these groups reaches the form only if another group they belong to grants access to it.";
		}
		if(in_array('view_globalscope', $permissions)) {
			return "Access to the form, and every entry in it made by anyone.";
		}
		if(in_array('view_groupscope', $permissions)) {
			return empty($scopeTargets)
				? "Access to the form, and entries belonging to members of their groups (including their own entries). \"Their groups\" means every group they belong to that **also** grants access to this form. A user can be a member of multiple groups, and some might not grant access to this form, so \"their groups\" are limited to only the ones that do."
				: "Access to the form, their own entries, and entries made by members of these specific groups: ".implode(', ', $scopeTargets).".";
		}
		return "Access to the form, and only the entries they made themselves.";
	}

	/**
	 * Report which of a form's custom code procedures have code written for them.
	 * The code itself is not included, since it can be long and is rarely what the caller is after.
	 * Mirrors the file naming used by formulizeForm::getVar() when it reads these procedures off disk.
	 * @param string $formHandle The handle of the form
	 * @return array Map of procedure name => bool
	 */
	private function customCodePresent($formHandle) {
		$present = [];
		foreach(array('on_before_save', 'on_after_save', 'on_delete', 'custom_edit_check') as $procedure) {
			$fileName = XOOPS_ROOT_PATH."/modules/formulize/code/".$procedure."_".$formHandle.".php";
			$present[$procedure] = file_exists($fileName);
		}
		return $present;
	}

	/**
	 * Get all the permissions across all forms, for a given group
	 * @param int groupId - the id of the group
	 * @return array returns an array with the permissions this group has across all forms
	 */
	private function group_permissions($groupId) {

		if(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups) AND !in_array($groupId, $this->userGroups)) {
			throw new FormulizeMCPException(
				"Permission denied: user is not a member of group $groupId.",
				'permission_denied',
			);
		}

		$groupDataSql = "SELECT groupid, `name`, `description` FROM " . $this->db->prefix('groups') . " WHERE groupid = ".intval($groupId);
		$groupDataResult = $this->db->query($groupDataSql);
		$groupData = $this->db->fetchArray($groupDataResult);

		$permissions = [];
		$forms = $this->forms_list();
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
			throw new FormulizeMCPException(
				"Permission denied: user does not have access to form $formId.",
				'permission_denied',
			);
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
	private function groups_list($group_id = 0, $user_id = 0)
	{

		$group_id = intval($group_id);
		$user_id = intval($user_id);

		$groupIdsToUse = $user_id ? $this->getGroupIdsFromUserId($user_id) : ($this->isUserAWebmaster() ? [] : $this->userGroups);

		$groupLimitWhereClause = "";
		// if a group id is requested, allow for webmasters and members of the group
		if($group_id AND ($this->isUserAWebmaster() OR in_array($group_id, $groupIdsToUse))) {
			$groupLimitWhereClause = "WHERE groupid = $group_id"; // already sanitized by intval above

		// otherwise, if a set of group ids has been determined, either from a passed in user, or the authenticated user, limit to those groups
		} elseif(!$group_id AND !empty($groupIdsToUse)) {
			$groupLimitWhereClause = "WHERE groupid IN (".implode(", ", array_filter($groupIdsToUse, 'is_numeric')).")";

		// otherwise, no limits specified, so if they're not a webmaster, then this is not allowed
		} elseif($this->isUserAWebmaster() == false) {
			throw new FormulizeMCPException(
				"Permission denied: user does not have access to this group information.",
				'authentication_error'
			);
		}

		// Get groups
		$groupsSql = "SELECT groupid, name, description, is_group_template, form_id, entry_id
			FROM " . $this->db->prefix('groups') . " $groupLimitWhereClause ORDER BY name";
		$groups = [];
		$formBasedGroupsPresent = false;
		if($groupsResult = $this->db->query($groupsSql)) {
			while ($row = $this->db->fetchArray($groupsResult)) {
				$group = [
					'groupid' => $row['groupid'],
					'name' => $row['name'],
					'description' => $row['description'],
					'group_kind' => $this->groupKind($row),
				];
				if($group['group_kind'] !== 'regular') {
					$formBasedGroupsPresent = true;
					$group['comes_from_form'] = intval($row['form_id']);
					if($group['group_kind'] === 'form_based_entry') {
						$group['comes_from_entry'] = intval($row['entry_id']);
					}
				}
				$groups[] = $group;
			}
		}

		$response = [
			'groups' => $groups,
			'group_count' => count($groups),
		];
		// only worth explaining when this system actually has them, and stated once rather than against
		// every group, since a system can have hundreds of generated ones
		if($formBasedGroupsPresent) {
			$response['about_form_based_groups'] = "Some groups here are marked form_based_template or form_based_entry, meaning they come from the entries in a form rather than being created by hand.\n\n".$this->describeFormBasedGroups();
		}
		return $response;
	}

	/**
	 * Explain form-based groups, for any tool whose results contain them.
	 *
	 * Shared rather than written twice: the same explanation is owed by the group list and by the
	 * permission report, and two copies of a nine-hundred-word concept drift apart quietly.
	 *
	 * Delivered in the response rather than left to the guide on purpose. A pull-based guide only helps an
	 * assistant that knows it has something to learn, and the failure this prevents is the opposite - one
	 * assistant, shown a template group with no members, confidently concluded that setting permissions on
	 * it would achieve nothing and declined to do it. Nothing about that state prompts a question. So the
	 * decision-critical part has to arrive unbidden, and it only costs anything on calls that return a
	 * form-based group at all.
	 *
	 * SPLIT THIS AT STEP 24 (the guide): the background - how entries-are-groups is enabled, how categories
	 * are created, how entry groups are kept in sync - belongs in the guide, where someone reads it
	 * deliberately. What must stay here is the part that stops the wrong conclusion: a template looks empty
	 * and is not unused, and it is where permissions should be set.
	 *
	 * @return string
	 */
	private function describeFormBasedGroups() {
		return 'A form-based template group is a group associated with a form that has the entries-are-groups setting enabled. Whenever an entry is made in such a form, a form-based entry group is created, and it inherits all its permissions from the corresponding form-based template group. There can be several template groups arising from a single form, each representing a different category of users. There is always at least one, known as "All Users", and a webmaster can create as many additional categories as they wish.

For example, a Departments form with entries-are-groups enabled will automatically generate a form-based template group called "Departments - All Users". A webmaster might choose to add the categories "Managers" and "Staff". Then, when entries such as HR and Legal are created in the Departments form, three groups are created for each entry: HR - All Users, HR - Managers, HR - Staff, and Legal - All Users, Legal - Managers, Legal - Staff. Additional categories are not mandatory, but they are often useful.

A form-based template group has no members of its own, so its membership will look empty, but it is not unused: each form-based entry group automatically receives the permissions its template group has, both when the entry group is created and whenever the template group\'s permissions are changed.

That makes the template groups the right place to set and change permissions that should apply to a given role for every entry group arising from the form. Setting permissions on the form-based entry groups directly is far more fragile: those settings are likely to drift out of sync, and to be overwritten by the template group\'s permissions eventually.';
	}

	/**
	 * Which of the three kinds of group this row is.
	 *
	 * Reported as one value rather than as the two raw columns, because the distinction only makes sense
	 * as a combination - a template has a form and no entry, an entry group has both, and a regular group
	 * has neither - and asking a reader to derive that from two nullable columns is how the distinction
	 * gets missed.
	 *
	 * Both form-based kinds carry the "form_based" prefix on purpose. The admin interface calls these
	 * "Form-based" groups, but it applies that label to a whole row covering the template, its categories
	 * and the groups arising from entries - so the term names the family there, not one member of it.
	 * Naming only the template "form-based" here would quietly narrow a word the administrator reads more
	 * broadly, and the two would then disagree while appearing to match. "Regular" matches the label the
	 * admin interface uses for everything else.
	 *
	 * @param array $row A row from the groups table
	 * @return string 'regular', 'form_based_template' or 'form_based_entry'
	 */
	private function groupKind($row) {
		if(!empty($row['is_group_template'])) {
			return 'form_based_template';
		}
		return !empty($row['entry_id']) ? 'form_based_entry' : 'regular';
	}

	/**
	 * Get the groups that a given user id is a member of
	 */
	private function getGroupIdsFromUserId($user_id) {
		$user_id = intval($user_id);
		$groupIds = [];
		$sql = "SELECT groupid FROM ".$this->db->prefix('groups_users_link')." WHERE uid = $user_id";
		if($result = $this->db->query($sql)) {
			while($row = $this->db->fetchArray($result)) {
				$groupIds[] = $row['groupid'];
			}
		}
		return $groupIds;
	}

	/**
	 * Get a list of the users in the system, all users for webmasters, users in groups the authenticated user can see data from otherwise
	 */
	private function users_list() {
		$limitBy = null;
		$user_id = null;
		if($this->isUserAWebmaster() == false) {
			if($groupIds = $this->groupsAuthenticatedUserCanSeeDataFrom()) {
				$limitBy = " INNER JOIN ".$this->db->prefix('groups_users_link')." as l
				ON l.uid = u.uid WHERE l.groupid IN (".implode(",", $groupIds).")";
			} else {
				$user_id = $this->authenticatedUid;
			}
		}
		$users = [];
		if($result = $this->getUserDetails($user_id, $limitBy)) {
			while ($row = $this->db->fetchArray($result)) {
				$users[] = $this->formatTimestamps($row);
			}
		}
		return [
			'users' => $users,
			'user_count' => count($users),
		];
	}

	/**
	 * Convert any timestamps in the user data to ISO 8601 format
	 */
	private function formatTimestamps($userData) {
		foreach($userData as $k=>$v) {
			// format timestamps as ISO 8601 dates
			if(strstr($k, 'timestamp') !== false) {
				$userData[$k] = $v ? date('c', $v) : null; // dates are stored as unix timestamps and PHP is always set to UTC
			}
		}
		return $userData;
	}

	/**
	 * Query for user data
	 * @param int|null $user_id The ID of a specific user to retrieve, or null for all users
	 * @param string|null $limitBy Optional SQL clause to limit which users are returned, e.g. a JOIN to groups_users_link and a WHERE clause. Strong assumption that this is sanitized by the caller!
	 * @return PDOStatement|false The database result, or false on failure
	 */
	private function getUserDetails($user_id = null, $limitBy = null) {
		$user_id = intval($user_id);
		if(!$limitBy AND $user_id) {
			$limitBy = "WHERE u.uid = ".intval($user_id);
		}
		$fields = "u.uid as user_id, u.uname as name, u.timezone_offset as user_timezone";
		if($this->isUserAWebmaster()) {
			$fields .= ", u.user_regdate as registration_timestamp, u.email as email, u.login_name, u.last_login as last_login_timestamp";
		}
		$sql = "SELECT $fields FROM ".$this->db->prefix('users')." as u $limitBy ORDER BY name";
		return $this->db->query($sql);
	}

	/**
	 * Get the connections between forms, based on the Primary Relationship
	 * Optionally can be limited to the connections of a specific form
	 * Only includes connections if the user has permission for at least one of the forms
	 * Each connection has a string describing the relationship, e.g. "Each Provice has many Cities", and the ids for the two forms involved.
	 * @param int|null $formId The ID of the form to limit connections to, or null for all connections
	 * @return array Returns an array with 'connections' (list of connections) and 'connection_count' (number of connections).
	 */
	private function form_connections_list($formId = null)
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
			$formsList = $this->forms_list();
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
			throw new FormulizeMCPException(
				'Failed to lookup application data. '.$this->db->error(),
				'database_error'
			);
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

	/**
	 * Assemble one application for the applications list.
	 * Reports whether the application has custom code the same way form_schemas() reports it for a form,
	 * because otherwise there is nothing to tell an AI assistant that the code exists: an application can
	 * carry code that runs on every page, and without this the only way to find it would be to guess and
	 * call get_custom_code on the off chance.
	 * @return array The application
	 */
	private function assignAppDataToApplicationsArray($id, $name, $desc, $forms) {
		return [
			'id' => $id,
			'name' => $name,
			'description' => $desc,
			'custom_code_present' => file_exists(XOOPS_ROOT_PATH.'/modules/formulize/code/application_custom_code_'.intval($id).'.php'),
			'forms' => $forms
		];
	}

	/**
	 * Convert the internal screen type stored in the database into the name used with AI assistants.
	 * The internal names are historical and would be misleading on their own: a 'multiPage' screen is
	 * simply what a user calls a form. The old single page 'form' screen type is reported separately as
	 * 'legacy_form' rather than being folded in with the others, because it only exists on a couple of
	 * systems and the form screen tools do not operate on it.
	 * @param string $internalType The type as stored in the formulize_screen table
	 * @return string The name to use when talking to an AI assistant
	 */
	private function friendlyScreenType($internalType) {
		$types = [
			'multiPage' => 'form',
			'form' => 'legacy_form',
			'listOfEntries' => 'list',
			'calendar' => 'calendar',
			'map' => 'map',
			'template' => 'template'
		];
		return $types[$internalType] ?? $internalType;
	}

	/**
	 * List the info about screens, or a single screen
	 * Optionally filtered by a formId. Naturally limits to screens on forms the user has access to.
	 * Optionally get a simple list of just the ids and titles
	 */
	private function screens_list($formId = null, $screenId = null, $simple = false) {
		if($formId AND !security_check($formId)) {
			throw new FormulizeMCPException(
				"Permission denied: user does not have access to form $formId.",
				'permission_denied',
			);
		}
		// take passed in form id, otherwise, allow all if the user is an admin
		$formIds = [];
		if($formId) {
			$formIds = [ $formId ];
		} elseif(!in_array(XOOPS_GROUP_ADMIN, $this->userGroups)) {
			$formsList = $this->forms_list();
			$forms = isset($formsList['forms']) ? $formsList['forms'] : [];
			$formIds = array_column($forms, 'id_form');
		}
		$limitScreensByFids = !empty($formIds) ? 'AND fid IN ('.implode(',', array_filter($formIds, 'is_numeric')).')' : '';
		$limitScreensBySid = $screenId ? 'AND sid = '.intval($screenId) : '';
		$sql = "SELECT * FROM ".$this->db->prefix('formulize_screen')." WHERE 1 $limitScreensBySid $limitScreensByFids ORDER BY fid,title";
		if(!$res = $this->db->query($sql)) {
			throw new FormulizeMCPException(
				'Failed to lookup screen data. '.$this->db->error(),
				'database_error'
			);
		}
		$serializedFields = FormulizeObject::serializedDBFields();
		$screens = [];
		while($row = $this->db->fetchArray($res)) {
			if($formId == $row['fid'] OR security_check($row['fid'])) { // already did security check for formId above
				if($simple) {
					$screens[] = [
						'screen_id' => $row['sid'],
						'screen_title' => $row['title'],
						'screen_type' => $this->friendlyScreenType($row['type'])
					];
				} else {
					$screenSQL = "SELECT * FROM ".$this->db->prefix('formulize_screen_'.strtolower($row['type']))." WHERE sid = ".$row['sid'];
					$screenRes = $this->db->query($screenSQL);
					$screenTypeData = $this->db->fetchArray($screenRes);
					$processedFields = [];
					if(isset($serializedFields['formulize_screen_'.strtolower($row['type'])])) {
						foreach($serializedFields['formulize_screen_'.strtolower($row['type'])] as $field) {
							$screenTypeData[$field] = unserialize($screenTypeData[$field]);
							// tidy up the conditions array
							if($row['type'] == 'multiPage' AND $field == 'conditions') {
								foreach($screenTypeData[$field] as $pageOrdinal=>$pageConditions) {
									$screenTypeData[$field][$pageOrdinal] = $this->tidyUpOldConditionsArrayFormat($pageConditions);
								}
							}
							$processedFields[$field] = true;
						}
					}
					foreach($screenTypeData as $field=>$value) {
						if(!isset($processedFields[$field]) AND is_string($value)) {
							$screenTypeData[$field] = undoAllHTMLChars($value);
							$processedFields[$field] = true;
						}
					}
					$screens[] = ['screen_type' => $this->friendlyScreenType($row['type'])] + $row + $screenTypeData;
				}
			}
		}
		if($screenId AND empty($screens)) {
			throw new FormulizeMCPException(
				"Permission denied: user does not have access to the screen $screenId.",
				'permission_denied',
			);
		}
		return [
			'screens' => $screens,
			'screen_count' => count($screens)
		];

	}

	/**
	 * Tidy up the conditions array format for
	 * Go from parallel arrays where the same key in each belongs together, to an array of arrays, where each item is a sub array with 'element', 'operator', 'value', and 'type' keys.
	 * This is purely for AI readability
	 * @param array $conditions The conditions array to tidy up
	 * @return array The tidied up conditions array
	 */
	private function tidyUpOldConditionsArrayFormat($conditions) {
		$tidiedConditions = [];
		if(is_array($conditions) AND count($conditions) > 0 AND !empty($conditions[0])) {
			$elements = $conditions[0];
			$operators = $conditions[1];
			$values = $conditions[2];
			$types = $conditions[3];
			$length = count($elements);
			for($i = 0; $i < $length; $i++) {
				$condition = [
					'element' => $this->elementHandleFromId($elements[$i]),
					'operator' => $operators[$i],
					'value' => $values[$i],
					'type' => ($types[$i] == 'all' ? 'match-all' : 'match-one-or-more')
				];
				$tidiedConditions[] = $condition;
			}
		}
		return empty($tidiedConditions) ? $conditions : $tidiedConditions;
	}

	/**
	 * Conditions normally store the element as an id, but every tool that accepts conditions asks for a
	 * handle ("provided as an element handle or id"), so reporting the stored value back verbatim means the
	 * read and write vocabularies disagree. That matters because reading conditions, changing one and
	 * writing the set back is the supported way to edit them - conditions are a single property, so a write
	 * replaces the whole set. Translating here keeps every consumer consistent, since they all come through
	 * tidyUpOldConditionsArrayFormat().
	 *
	 * Older conditions may hold a handle rather than an id: saving rewrites them as ids, so anything that
	 * has not been saved in a long time can still be in the original form. This resolves through the element
	 * handler, which already takes an id or a handle, so both forms arrive at the same answer without us
	 * having to tell them apart. That also validates the reference - a condition naming an element that no
	 * longer exists resolves to nothing and is returned unchanged, rather than being reported as some other
	 * element or as a bare id with no indication that it is stale.
	 *
	 * @param mixed $elementIdOrHandle The element reference held in the conditions array
	 * @return mixed The element handle, or the original value if it cannot be resolved
	 */
	private function elementHandleFromId($elementIdOrHandle) {
		static $element_handler = null;
		if($element_handler === null) {
			$element_handler = xoops_getmodulehandler('elements', 'formulize');
		}
		// the handler caches its lookups, so repeated references to the same element cost nothing
		if($elementObject = $element_handler->get($elementIdOrHandle)) {
			return $elementObject->getVar('ele_handle');
		}
		return $elementIdOrHandle;
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
