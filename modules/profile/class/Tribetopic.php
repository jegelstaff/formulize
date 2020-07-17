<?php
/**
 * Class representing the profile tribetopic object
 *
 * @copyright	GNU General Public License (GPL)
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @since		1.3
 * @author		phoenyx
 * @package		profile
 * @version		$Id: Tribetopic.php 22245 2011-08-15 12:56:38Z phoenyx $
 */

defined("ICMS_ROOT_PATH") or die("ICMS root path not defined");

class mod_profile_Tribetopic extends icms_ipf_seo_Object {
	/**
	 * Constructor
	 *
	 * @param mod_profile_TribetopicHandler $handler object handler
	 */
	public function __construct(&$handler) {
		icms_ipf_Object::__construct($handler);

		$this->quickInitVar('topic_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('tribes_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('poster_uid', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('post_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('title', XOBJ_DTYPE_TXTBOX, true);
		$this->quickInitVar('closed', XOBJ_DTYPE_INT, true, false, false, 0);
		$this->quickInitVar('replies', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('views', XOBJ_DTYPE_INT, false);
		$this->quickInitVar('last_post_id', XOBJ_DTYPE_INT, true);
		$this->quickInitVar('last_post_time', XOBJ_DTYPE_LTIME, false);
		
		$this->setControl('poster_uid', 'user');
		$this->setControl('closed', 'yesno');

		$this->hideFieldFromForm(array('tribes_id', 'post_uid', 'post_id', 'replies', 'views', 'last_post_id', 'last_post_time'));

		$this->initiateSEO();
	}

	/**
	 * increment views counter
	 *
	 * @return bool true if incrementing was successfull
	 */
	public function incrementViews() {
		$this->setVar('views', $this->getVar('views') + 1);
		return $this->store(true);
	}

	/**
	 * toggle closed status of the topic
	 *
	 * @return bool true if toggleing was successfull
	 */
	public function toggleClose() {
		$this->setVar('closed', !$this->getVar('closed'));
		return $this->store(true);
	}

	/**
	 * Check to see wether the current user can edit or delete this tribe
	 *
	 * @global bool $profile_isAdmin true if current user is admin of profile module
	 * @return bool true if he can, false if not
	 */
	public function userCanEditAndDelete() {
		global $profile_isAdmin;

		if (!is_object(icms::$user)) return false;
		if ($profile_isAdmin) return true;
		return $this->getVar('poster_uid', 'e') == icms::$user->getVar('uid');
	}

	/**
	 * Overridding IcmsPersistable::toArray() method to add a few info
	 *
	 * @return array of tribetopic info
	 */
	public function toArray() {
		$ret = parent::toArray();
		$ret['last_post_time'] = formatTimestamp($this->getVar('last_post_time', 'e'), 'm');
		$ret['poster_uname'] = icms_member_user_Handler::getUserLink($this->getVar('poster_uid'));
		$ret['userCanEditAndDelete'] = $this->userCanEditAndDelete();
		$ret['itemLink'] = str_replace($this->handler->_itemname.'.php?', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&', $ret['itemLink']);
		$ret['itemUrl'] = str_replace($this->handler->_itemname.'.php?', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&', $ret['itemUrl']);
		$ret['editItemLink'] = str_replace($this->handler->_itemname.'.php?op=mod', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&amp;op=modtribetopic', $this->getEditItemLink(false, true, true));
		$ret['deleteItemLink'] = str_replace($this->handler->_itemname.'.php?op=del', 'tribes.php?tribes_id='.$this->getVar('tribes_id').'&amp;op=deltribetopic', $this->getDeleteItemLink(false, true, true));
		// build last post link
		$module = icms::handler("icms_module")->getByDirname(basename(dirname(dirname(__FILE__))), TRUE);
		$start = '';
		if ($this->getVar('replies') + 1 > $module->config['tribepostsperpage']) {
			$start = '&start='.(($this->getVar('replies') + 1) - (($this->getVar('replies') + 1) % $module->config['tribepostsperpage']));
		}
		$ret['lastItemLink'] = '<a href="'.$ret['itemUrl'].$start.'#post'.$this->getVar('last_post_id').'"><img src="'.$this->handler->_moduleUrl.'images/comments.gif" title="'._MD_PROFILE_TRIBETOPIC_SHOW_LAST_POST.'" style="vertical-align:middle;" /></a>';
		// build toggle close item link
		if ($this->getVar('closed')) {
			// link to reopen the topic
			$ret['closedIcon'] = '<img src="'.$this->handler->_moduleUrl.'images/lock.gif" title="'._MD_PROFILE_TRIBETOPIC_CLOSE.'" style="vertical-align:middle;" />';
			$ret['toggleCloseLink'] = '<a href="'.$ret['itemUrl'].'&amp;op=toggleclose"><img src="'.$this->handler->_moduleUrl.'images/unlock.gif" title="'._MD_PROFILE_TRIBETOPIC_REOPEN.'" style="vertical-align:middle;" /></a>';
		} else {
			// link to close the topic
			$ret['toggleCloseLink'] = '<a href="'.$ret['itemUrl'].'&amp;op=toggleclose"><img src="'.$this->handler->_moduleUrl.'images/lock.gif" title="'._MD_PROFILE_TRIBETOPIC_CLOSE.'" style="vertical-align:middle;" /></a>';
		}
		
		return $ret;
	}
}
?>