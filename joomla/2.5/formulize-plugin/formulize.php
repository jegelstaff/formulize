<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

// Get the path to Formulize stored as a component parameters
$params = JComponentHelper::getParams( 'com_formulize' );
$formulize_path = $params->get('formulize_path');
// Include the API
require_once $formulize_path."/integration_api.php";

/*
General comments:
** To be able to test the plugin, I synchronized the two systems manually (the initial sync is not ready)
** Note to Jeff:
		-Need to add this code in session.php
		if(isset($GLOBALS['joomlaUserId'])) { // Joomla
		    $externalUid = $GLOBALS['joomlaUserId'];
		}
		-Need to create the table formulize_external_group_mapping
 
** Still need to find an event triggered by batch processing...
*/

class plgUserFormulize extends JPlugin
{
	/*
		Triggered when a user login into Joomla
		Used for single sign-on
	*/
	public function onUserLogin($user, $options)
	{
		// Get a reference to current application in order to display message
		$application = JFactory::getApplication();
		
		// Get the current userId by email (maybe better by username?)
		$email = $user['email'];
		$userId = self::getUserId($email);
		
		// Display error message, if necessary
		if($userId<0) {
			$application->enqueueMessage(JText::_('Username '.$user['username'].': Error querying the database'), 'error');
			return false;
		}
		
		// Set $GLOBALS to pass userId to the Formulize system
		$GLOBALS['joomlaUserId'] = $userId;
		// Start session in formulize
		Formulize::init();

		// For debugging, will be removed
		$application->enqueueMessage(JText::_('User ID:'.$GLOBALS['joomlaUserId']), 'message');
		$application->enqueueMessage(JText::_('Session ID:'.session_id()), 'message');
		
        return true;
    }
	
	/*
		Triggered just before a user is saved into Joomla
		Used to save previous group memberships
	*/
	public function onUserBeforeSave($user, $isnew, $success, $msg)
	{
		// Get a reference to current application in order to display message
		$application = JFactory::getApplication();
		
		if($isnew) { // New user and no previous memberships
			$previousGroups = array();
		}
		else { // Existing user, get its previous memberships
			// Get the userId by email (maybe better by username?)
			$email = $user['email'];
			$userId = self::getUserId($email);
		
			// Display error message, if necessary
			if($userId<0) {
				$application->enqueueMessage(JText::_('Username '.$user['username'].': Error querying the database'), 'error');
				return false;
			}
		
			// Get previous memberships
			$previousGroups = self::getGroups($userId);
		
			// Display error message, if necessary
			if($previousGroups<0) {
				$application->enqueueMessage(JText::_('Username '.$user['username'].': Error querying the database'), 'error');
				return false;
			}
		}
		
		// Store those previous groups in a global variable
		// Maybe better not to use super global...
		$GLOBALS['previousGroups'] = $previousGroups;
		
		// For debugging, will be removed
		$application->enqueueMessage(JText::_('New:'.$isnew), 'message');
		
        return true;
    }
	
	/*
		Triggered just after a user is saved into Joomla
		Used to create or update a user in Formulize
		Used to update user memberships by comparing previous groups and current groups
	*/
	public function onUserAfterSave($user, $isnew, $success, $msg)
	{
		// Get a reference to current application in order to display message
		$application = JFactory::getApplication();
		
		// Get the userid, unavailable in $user
		$userId = self::getUserId($user['email']);
		// Get current memberships of user
		$currentGroups = self::getGroups($userId);
		
		// Get previous memberships of user
		$previousGroups = $GLOBALS['previousGroups'];
	
		// Create a new blank user for Formulize session
		$userData = array();
		$userData['uid'] = $userId;
		$userData['uname'] = $user['name'];
		$userData['login_name'] = $user['username'];
		$userData['email'] = $user['email'];
		$userData['timezone_offset'] = 0;
		
		// Create a new Formulize user
		$newUser = new FormulizeUser($userData);
		
		// For debugging, will be removed
		$application->enqueueMessage(JText::_('User Id:'.$newUser->get('uid')), 'message');
		
		// Create or update the user in Formulize
		if($isnew) // Create
		{
			$flag = Formulize::createUser($newUser);
			// Display error message if necessary
			if ( !$flag ) {
				$application->enqueueMessage(JText::_('User id:'.$userID.' Error creating new user'), 'error');
			}
			else {
				// Add user to current groups
				foreach($currentGroups as $group) {
					Formulize::addUserToGroup($userId, $group);
				}
			}
		}
		else // Update
		{
			$flag = Formulize::updateUser($userId, $userData);
			// Display error message if necessary
			if ( !$flag ) {
				$application->enqueueMessage(JText::_('User id:'.$userID.' Error updating user'), 'error');
			}
		}
		
		// If necessary, update this user's memberships
		// If necessary, remove user from groups
		foreach($previousGroups as $prevGroup) {
			$found = false;
			foreach($currentGroups as $curGroup){
				if($prevGroup == $curGroup) {
					$found = true;
				}
			}
			if($found == false) {
				// Remove user from group
				$flag = Formulize::removeUserFromGroup($userId, $prevGroup);
				// Display error message if necessary
				if ( !$flag ) {
					$application->enqueueMessage(JText::_('Group id:'.$prevGroup.' Error removing user from group/'), 'error');
				}
				$application->enqueueMessage(JText::_('Removed from:'.$prevGroup), 'message');	
			}
		}
		// If necessary, add user to groups
		foreach($currentGroups as $curGroup) {
			$found = false;
			foreach($previousGroups as $group) {
				if($curGroup == $group) {
					$found = true;
					$application->enqueueMessage(JText::_('Same:'.$curGroup), 'message');
				}
			}
			if($found == false) {
				// Add user to group
				$flag = Formulize::addUserToGroup($userId, $curGroup);
				// Display error message if necessary
				if ( !$flag ) {
					$application->enqueueMessage(JText::_('User id:'.$curGroup.' Error adding user to group/'), 'error');
				}
				$application->enqueueMessage(JText::_('Added to:'.$curGroup), 'message');	
			}
		}
		// For debugging, will be removed
		$name = $formulizeUser->uname;
		$application->enqueueMessage(JText::_('User name:'.$name), 'message');
        return true;
    }
	
	/*
		Triggered just before a user is deleted
		Used to delete a user in Formulize
	*/
	public function onUserBeforeDelete($user)
	{
		// Get a reference to current application in order to display message
		$application = JFactory::getApplication();
		
		// Get the userid, unavailable in $user
		$userID = self::getUserId($user['email']);
		
		// Delete the user in Formulize
		$flag = Formulize::deleteUser($userID);
		// Display error message if necessary
		if ( !$flag ) {
				$application->enqueueMessage(JText::_('User id:'.$userID.' Error deleting user/'), 'error');
		}
	
		// For debugging, will be removed	
		$application->enqueueMessage(JText::_('User id:'.$userID), 'message');
		
        return true;
    }
	
	/*
		Triggered just after a group is saved
		Used to create or rename a group in Formulize
	*/
	public function onUserAfterSaveGroup($context, $group, $isnew)
	{
		// Get a reference to current application in order to display message
		$application = JFactory::getApplication();
		
		// Store the group info in an array 
		$groupData = array();
		$groupData['name'] = $group->title;
		$groupData['groupid'] = $group->id; 
		
		// Create the Formulize group
		$formulizeGroup = new FormulizeGroup($groupData);
		
		// Create or update the group in Formulize
		if($isnew) // Create
		{
			$flag = Formulize::createGroup($formulizeGroup);
			// Display error message if necessary
			if ( !$flag ) {
				$application->enqueueMessage(JText::_('Group id: '.$group->id.' Error creating new group'), 'error');
			}
		}
		else // Rename
		{
			$flag = Formulize::renameGroup($group->id, $group->title);
			// Display error message if necessary
			if ( !$flag ) {
				$application->enqueueMessage(JText::_('Group id:'.$group->id.' Error updating group/'), 'error');
			}
		}
		// For debugging, will be removed
		$application->enqueueMessage(JText::_('New?: '.$isnew), 'message');
		$application->enqueueMessage(JText::_('Name: '.$formulizeGroup->get('name')), 'message');
		$application->enqueueMessage(JText::_('Id: '.$formulizeGroup->get('groupid')), 'message');
	}
	
	/*
		Triggered just after a group is deleted
		Used to delete a group in Formulize
	*/
	public function onUserBeforeDeleteGroup($group)
	{
		// Get a reference to current application in order to display message
		$application = JFactory::getApplication();
		
		// Delete the group in Formulize
		$flag = Formulize::deleteGroup($group['id']);
		// Display error message if necessary
		if ( !$flag ) {
				$application->enqueueMessage(JText::_('Group id:'.$group['id'].' Error deleting group/'), 'error');
		}
	
		// For debugging, will be removed	
		$application->enqueueMessage(JText::_('a1: '.$group['title']), 'message');
		$application->enqueueMessage(JText::_('a2: '.$group['id']), 'message');
		
        return true;
    }
	
	/*
		Used to get a userId from the user's email
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
        if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return -1;
        }  
		// Get the result and return the userId
		$rows = $db->loadObjectList();  
		$userId = $rows[0]->id;
		return $userId;
	}
	
	/*
		Used to get the groups a user is member of from its userId
	*/
	private function getGroups($userId) {
		// Get a reference to current application in order to display message
		$application = JFactory::getApplication();
		
		// Get a reference to the database
		$db = JFactory::getDbo();
		// Get parents of each user group
		$query = $db->getQuery(true);      
        $query->select(array('id', 'parent_id'))
			->from('#__usergroups ');          
        $db->setQuery($query);    
        if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return -1;
        }                         
		$parents = $db->loadObjectList();
		
		// Get the group this user is member of
        $query = $db->getQuery(true);      
        $query->select('group_id')
			->from('#__user_usergroup_map ')
			->where('user_id = ' .  "'". $userId . "'" );            
        $db->setQuery($query);    
        if (!$db->query()) {
			$this->setError($this->_db->getErrorMsg());
			return -1;
        }                         
		$rows = $db->loadObjectList();
		// Create an array with the group memberships
		$groups = array();
		foreach($rows as $row){  
			// Add each group to $groups
			$groups[] = $row->group_id;
			// Add parents and ancestors
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