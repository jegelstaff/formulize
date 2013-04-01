<?php
	// No direct access
	defined('_JEXEC') or die('Restricted access');

	/**
	 * This plugin is used for user management
	 * It allows for user and group synchronization between Joomla and Formulize
	 * It is not responsible for the initial synchronization between the two systems
	 */
	
	// Get the path to Formulize stored as a component parameters
	$params = JComponentHelper::getParams( 'com_formulize' );
	$formulize_path = $params->get('formulize_path');
	// Include the Formulize API
	require_once $formulize_path."/integration_api.php";

	class plgUserFormulize extends JPlugin
	{
		/**
		 * Method triggered when a user login into Joomla
		 * Used for single sign-on
		 *
		 * @param $user: an associative array representing the user that is logging in
		 * @param $options: contains information that is not relevant to us
		 * @return true if the operation was successful, false otherwise
		 */
		public function onUserLogin($user, $options) {
		
			// Get a reference to current application in order to display message, if necessary
			$application = JFactory::getApplication();
			
			// Get the current userId by email since emails are unique
			$email = $user['email'];
			$userId = self::getUserId($email);
			
			// Display error message, if necessary
			if($userId<0) {
				$application->enqueueMessage(JText::_('Username '.$user['username'].': Unable to find the user in the database.'), 'error');
				return false;
			}
			
			// Set $GLOBALS to pass the user's Id to the Formulize system
			$GLOBALS['formulizeHostSystemUserId'] = $userId;
			
			// Start the session in formulize
			Formulize::init();
			
			return true;
		}
		
		/**
		 * Method triggered just before a user is saved into Joomla
		 * Used to save previous group memberships
		 *
		 * @param $user: an associative array representing the user that is being saved
		 * @param $isnew: boolean to identify if this is a new user
		 * @return true if the operation was successful, false otherwise
		 */		
		public function onUserBeforeSave($user, $isnew) {
		
			// Get a reference to current application in order to display message, if necessary
			$application = JFactory::getApplication();
			
			if($isnew) { // New user and no previous memberships
				$previousGroups = array();
			}
			else { // Existing user, get its previous memberships
				// Get the userId by email since emails are unique
				$email = $user['email'];
				$userId = self::getUserId($email);
			
				// Display error message, if necessary
				if($userId<0) {
					$application->enqueueMessage(JText::_('Username '.$user['username'].': Unable to find the user in the database.'), 'error');
					return false;
				}
			
				// Get previous memberships
				$previousGroups = self::getGroups($userId);
			
				// Display error message, if necessary
				if($previousGroups<0) {
					$application->enqueueMessage(JText::_('Username '.$user['username'].': Error finding the groups the user is member of.'), 'error');
					return false;
				}
			}
			
			// Store those previous groups in a global variable
			$GLOBALS['previousGroups'] = $previousGroups;
			
			return true;
		}
		
		/**
		 * Method triggered just after a user is saved into Joomla
		 * Used to create or update a user in Formulize
		 * Used to update user memberships by comparing previous groups and current groups
		 *
		 * @param $user: an associative array representing the user that is being saved
		 * @param $isnew: boolean to identify if this is a new user
		 * @param $success: boolean to identify if the store was successful 
		 * @param $msg: contains information that is not relevant to us
		 * @return true if the operation was successful, false otherwise
		 */	
		public function onUserAfterSave($user, $isnew, $success, $msg) {
		
			// If the store was successful
			if($success){
				// Get a reference to current application in order to display message, if necessary
				$application = JFactory::getApplication();
				
				// Get the userid by email since emails are unique
				$email = $user['email'];
				$userId = self::getUserId($email);
				
				// Get current memberships of user
				$currentGroups = self::getGroups($userId);
				
				// Get previous memberships of user
				$previousGroups = $GLOBALS['previousGroups'];
			
				// Store the user info in an array 
				$userData = array();
				$userData['uid'] = $userId;
				$userData['uname'] = $user['username'];
				$userData['login_name'] = $user['name'];
				$userData['email'] = $user['email'];
				$userData['timezone_offset'] = 0;
				
				// Create or update the user in Formulize
				if($isnew) // Create
				{
					// Create a new user in Formulize
					$newUser = new FormulizeUser($userData);
					$flag = Formulize::createUser($newUser);
					
					// Display error message if necessary
					if ( !$flag ) {
						$application->enqueueMessage(JText::_('User id '.$userId.': Error creating new user in Formulize.'), 'error');
						return false;
					}
					else {
						$application->enqueueMessage(JText::_('User was created in Formulize.'), 'message');
						// Add user to current groups
						foreach($currentGroups as $group) {
							Formulize::addUserToGroup($userId, $group);
						}
					}
				}
				else // Update
				{
					// Update the user in Formulize
					$flag = Formulize::updateUser($userId, $userData);
					
					// Display error message if necessary
					if ( !$flag ) {
						$application->enqueueMessage(JText::_('User id '.$userId.': Error updating user in Formulize.'), 'error');
						return false;
					}
					else {
						$application->enqueueMessage(JText::_('User was updated in Formulize.'), 'message');
					}
				}
				
				// Update this user's memberships
				// If necessary, remove user from groups
				// For each previous group, determine if the user is still a member of that group
				foreach($previousGroups as $prevGroup) {	
					$found = false;
					foreach($currentGroups as $curGroup){
						if($prevGroup == $curGroup) {
							$found = true;
						}
					}
					// If the previous group was not found in the current groups
					if($found == false) {
						// Remove user from group
						$flag = Formulize::removeUserFromGroup($userId, $prevGroup);
						// Display error message if necessary
						if ( !$flag ) {
							$application->enqueueMessage(JText::_('Group id '.$prevGroup.': Error removing user from group in Formulize.'), 'error');
							return false;
						}	
						else {
							$application->enqueueMessage(JText::_('Group id '.$prevGroup.': User was removed from group in Formulize.'), 'message');
						}
					}
				}
				// If necessary, add user to groups
				// For each current group, determine if the user was already a member of that group
				foreach($currentGroups as $curGroup) {
					$found = false;
					foreach($previousGroups as $group) {
						if($curGroup == $group) {
							$found = true;
						}
					}
					// If the current group was not found in the previous groups
					if($found == false) {
						// Add user to group
						$flag = Formulize::addUserToGroup($userId, $curGroup);
						// Display error message if necessary
						if ( !$flag ) {
							$application->enqueueMessage(JText::_('Group id '.$curGroup.': Error adding user to group in Formulize.'), 'error');
							return false;
						}
						else {
							$application->enqueueMessage(JText::_('Group id '.$curGroup.': User was added to group in Formulize.'), 'message');
						}
					}
				}
			}
			
			return true;
		}

		/**
		 * Method triggered just before a user is deleted
		 * Used to delete a user in Formulize
		 *
		 * @param $user: an associative array representing the user that is being deleted
		 * @return true if the operation was successful, false otherwise
		 */	
		public function onUserBeforeDelete($user) {
		
			// Get a reference to current application in order to display message, if necessary
			$application = JFactory::getApplication();
			
			// Get the userid by email since emails are unique
			$email = $user['email'];
			$userId = self::getUserId($email);
			
			// Delete the user in Formulize
			$flag = Formulize::deleteUser($userId);
			
			// Display error message if necessary
			if ( !$flag ) {
				$application->enqueueMessage(JText::_('User id '.$userId.': Error deleting user in Formulize.'), 'error');
				return false;
			}
			else {
				$application->enqueueMessage(JText::_('User was deleted in Formulize.'), 'message');
			}
			
			return true;
		}
	
		/**
		 * Method triggered just after a group is saved
		 * Used to create or rename a group in Formulize
		 *
		 * @param $context: contains information that is not relevant to us
		 * @param $group: an associative array representing the group that is being saved
		 * @param $isnew: boolean to identify if this is a new group
		 * @return true if the operation was successful, false otherwise
		 */
		public function onUserAfterSaveGroup($context, $group, $isnew) {
		
			// Get a reference to current application in order to display message, if necessary
			$application = JFactory::getApplication();
			
			// Create or update the group in Formulize
			if($isnew) // Create
			{
				// Store the group info in an array 
				$groupData = array();
				$groupData['name'] = $group->title;
				$groupData['groupid'] = $group->id; 
			
				// Create the Formulize group
				$formulizeGroup = new FormulizeGroup($groupData);
				$flag = Formulize::createGroup($formulizeGroup);
				// Display error message if necessary
				if ( !$flag ) {
					$application->enqueueMessage(JText::_('Group id '.$group->id.': Error creating new group in Formulize.'), 'error');
					return false;
				}
				else {
					$application->enqueueMessage(JText::_('Group was created in Formulize.'), 'message');
				}
			}
			else // Rename
			{
				$flag = Formulize::renameGroup($group->id, $group->title);
				// Display error message if necessary
				if ( !$flag ) {
					$application->enqueueMessage(JText::_('Group id '.$group->id.': Error updating group in Formulize.'), 'error');
					return false;
				}
				else {
					$application->enqueueMessage(JText::_('Group was updated in Formulize.'), 'message');
				}
			}
			
			return true;
		}

		/**
		 * Method triggered just before a group is deleted
		 * Used to delete a group in Formulize
		 *
		 * @param $group: an associative array representing the group that is being deleted
		 * @return true if the operation was successful, false otherwise
		 */
		public function onUserBeforeDeleteGroup($group) {
		
			// Get a reference to current application in order to display message, if necessary
			$application = JFactory::getApplication();
			
			// Delete the group in Formulize
			$flag = Formulize::deleteGroup($group['id']);
			
			// Display error message if necessary
			if ( !$flag ) {
				$application->enqueueMessage(JText::_('Group id '.$group['id'].': Error deleting group in Formulize.'), 'error');
				return false;
			}
			else {
				$application->enqueueMessage(JText::_('Group was deleted in Formulize.'), 'message');
			}
			
			return true;
		}

		/**
		 * Method used to get a userId from a user's email
		 *
		 * @param $email: the user's email
		 * @return the user's Id if found, -1 otherwise
		 */
		private function getUserId($email) {
		
			// Get a reference to the database
			$db = JFactory::getDbo();
			
			// Query the database
			$query = $db->getQuery(true);      
			$query->select('id')
				->from('#__users ')
				->where('email = ' .  "'". $email . "'" );            
			$db->setQuery($query);  
			
			// If the query was not successful
			if (!$db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return -1;
			}  
			
			// Get the result and return the userId
			$rows = $db->loadObjectList();  
			$userId = $rows[0]->id;
			
			return $userId;
		}
	
		/**
		 * Method used to get the groups a user is member of from its userId
		 *
		 * @param $userId: the user's Id
		 * @return an array containing the group's Ids
		 */
		private function getGroups($userId) {
			
			// Get a reference to the database
			$db = JFactory::getDbo();
			
			// Get parents of each user group
			$query = $db->getQuery(true);      
			$query->select(array('id', 'parent_id'))
				->from('#__usergroups ');          
			$db->setQuery($query);    
			
			// If the query was not successful
			if (!$db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return -1;
			}        
			// Get the results
			$parents = $db->loadObjectList();
			
			// Get the group this user is member of
			$query = $db->getQuery(true);      
			$query->select('group_id')
				->from('#__user_usergroup_map ')
				->where('user_id = ' .  "'". $userId . "'" );            
			$db->setQuery($query);    
			
			// If the query was not successful
			if (!$db->query()) {
				$this->setError($this->_db->getErrorMsg());
				return -1;
			}     

			// Get the result
			$rows = $db->loadObjectList();
			
			// Create an array with the group memberships
			$groups = array();
			foreach($rows as $row){  
				// Add each group to $groups
				$groups[] = $row->group_id;
				// Add parents and ancestors recursively
				$parent = self::getParent($parents, $row->group_id);
				while($parent!=0) {
					$groups[] = $parent;
					$parent = self::getParent($parents, $parent);
				}
			}
			
			// Remove duplicates
			$groups = array_unique($groups);
			
			// Return the array containing groups
			return $groups;
		}
	
		/**
		 * Method used to get the parent of a group from its group's Id
		 *
		 * @param $parents: an array containing each group's Id along with its parent group's Id
		 * @param $groupId: the group's Id
		 * @return the parent group's Id if found, -1 otherwise
		 */	
		private function getParent($parents, $groupId) {
			foreach($parents as $parent) {
				if($parent->id == $groupId) {
					return $parent->parent_id;
				}
			}
			return -1;
		}
	}
?>