<?php
/**
 * Display comments
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		data
 * @subpackage	comment
 * @version		SVN: $Id: Renderer.php 22529 2011-09-02 19:55:40Z phoenyx $
 */

/**
 * Display comments
 *
 * @category	ICMS
 * @package		data
 * @subpackage	comment
 */
class icms_data_comment_Renderer {

	private $_tpl;
	private $_comments = NULL;
	private $_useIcons = TRUE;
	private $_doIconCheck = FALSE;
	private $_memberHandler;
	private $_statusText;

	/**
	 * Constructor
	 *
	 * @param   object  &$tpl
	 * @param   boolean $use_icons
	 * @param   boolean $do_iconcheck
	 */
	private function __construct(&$tpl, $use_icons = TRUE, $do_iconcheck = FALSE) {
		$this->_tpl =& $tpl;
		$this->_useIcons = $use_icons;
		$this->_doIconCheck = $do_iconcheck;
		$this->_memberHandler = icms::handler('icms_member');
		$this->_statusText = array(
			XOOPS_COMMENT_PENDING => '<span style="text-decoration: none; font-weight: bold; color: #00ff00;">' . _CM_PENDING . '</span>',
			XOOPS_COMMENT_ACTIVE => '<span style="text-decoration: none; font-weight: bold; color: #ff0000;">' . _CM_ACTIVE . '</span>',
			XOOPS_COMMENT_HIDDEN => '<span style="text-decoration: none; font-weight: bold; color: #0000ff;">' . _CM_HIDDEN . '</span>'
			);
	}

	/**
	 * Access the only instance of this class
	 *
	 * @param   object  $tpl        reference to a {@link Smarty} object
	 * @param   boolean $use_icons
	 * @param   boolean $do_iconcheck
	 * @return
	 */
	static function &instance(&$tpl, $use_icons = TRUE, $do_iconcheck = FALSE) {
		static $instance;
		if (!isset($instance)) {
			$instance = new icms_data_comment_Renderer($tpl, $use_icons, $do_iconcheck);
		}
		return $instance;
	}

	/**
	 * Accessor
	 *
	 * @param   object  &$comments_arr  array of {@link XoopsComment} objects
	 */
	public function setComments(&$comments_arr) {
		if (isset($this->_comments)) {
			unset($this->_comments);
		}
		$this->_comments =& $comments_arr;
	}

	/**
	 * Render the comments in flat view
	 *
	 * @param bool  $admin_view
	 */
	public function renderFlatView($admin_view = FALSE) {
		$count = count($this->_comments);
		for ($i = 0; $i < $count; $i++) {
			if (FALSE != $this->_useIcons) {
				$title = $this->_getTitleIcon($this->_comments[$i]->getVar('com_icon')) . '&nbsp;' . $this->_comments[$i]->getVar('com_title');
			} else {
				$title = $this->_comments[$i]->getVar('com_title');
			}
			$poster = $this->_getPosterArray($this->_comments[$i]->getVar('com_uid'));
			if (FALSE != $admin_view) {
				$text = $this->_comments[$i]->getVar('com_text') . '<div style="text-align:right; margin-top: 2px; margin-bottom: 0px; margin-right: 2px;">' . _CM_STATUS . ': ' . $this->_statusText[$this->_comments[$i]->getVar('com_status')] . '<br />IP: <span style="font-weight: bold;">' . $this->_comments[$i]->getVar('com_ip') . '</span></div>';
			} else {
				// hide comments that are not active
				if (XOOPS_COMMENT_ACTIVE != $this->_comments[$i]->getVar('com_status')) {
					continue;
				} else {
					$text = $this->_comments[$i]->getVar('com_text');
				}
			}
			$this->_tpl->append('comments', array('id' => $this->_comments[$i]->getVar('com_id'), 'title' => $title, 'text' => $text, 'date_posted' => formatTimestamp($this->_comments[$i]->getVar('com_created'), 'm'), 'date_modified' => formatTimestamp($this->_comments[$i]->getVar('com_modified'), 'm'), 'poster' => $poster));
		}
	}

	/**
	 * Render the comments in thread view
	 *
	 * This method calls itself recursively
	 *
	 * @param integer $comment_id   Should be "0" when called by client
	 * @param boolean $admin_view
	 * @param boolean $show_nav
	 */
	public function renderThreadView($comment_id = 0, $admin_view = FALSE, $show_nav = TRUE) {
		// construct comment tree
		$xot = new icms_ipf_Tree($this->_comments, 'com_id', 'com_pid', 'com_rootid');
		$tree =& $xot->getTree();

		if (FALSE != $this->_useIcons) {
			$title = $this->_getTitleIcon($tree[$comment_id]['obj']->getVar('com_icon')) . '&nbsp;' . $tree[$comment_id]['obj']->getVar('com_title');
		} else {
			$title = $tree[$comment_id]['obj']->getVar('com_title');
		}
		if (FALSE != $show_nav && $tree[$comment_id]['obj']->getVar('com_pid') != 0) {
			$this->_tpl->assign('lang_top', _CM_TOP);
			$this->_tpl->assign('lang_parent', _CM_PARENT);
			$this->_tpl->assign('show_threadnav', TRUE);
		} else {
			$this->_tpl->assign('show_threadnav', FALSE);
		}
		if (FALSE != $admin_view) {
			// admins can see all
			$text = $tree[$comment_id]['obj']->getVar('com_text') . '<div style="text-align:right; margin-top: 2px; margin-bottom: 0px; margin-right: 2px;">'
				. _CM_STATUS . ': ' . $this->_statusText[$tree[$comment_id]['obj']->getVar('com_status')]
				. '<br />IP: <span style="font-weight: bold;">' . $tree[$comment_id]['obj']->getVar('com_ip') . '</span></div>';
		} else {
			// hide comments that are not active
			if (XOOPS_COMMENT_ACTIVE != $tree[$comment_id]['obj']->getVar('com_status')) {
				// if there are any child comments, display them as root comments
				if (isset($tree[$comment_id]['child']) && !empty($tree[$comment_id]['child'])) {
					foreach ($tree[$comment_id]['child'] as $child_id) {
						$this->renderThreadView($child_id, $admin_view, FALSE);
					}
				}
				return;
			} else {
				$text = $tree[$comment_id]['obj']->getVar('com_text');
			}
		}
		$replies = array();
		$this->_renderThreadReplies($tree, $comment_id, $replies, '&nbsp;&nbsp;', $admin_view);
		$show_replies = (count($replies) > 0) ? TRUE : FALSE;
		$this->_tpl->append('comments',
			array('pid' => $tree[$comment_id]['obj']->getVar('com_pid'),
				'id' => $tree[$comment_id]['obj']->getVar('com_id'),
				'itemid' => $tree[$comment_id]['obj']->getVar('com_itemid'),
				'rootid' => $tree[$comment_id]['obj']->getVar('com_rootid'),
				'title' => $title,
				'text' => $text,
				'date_posted' => formatTimestamp($tree[$comment_id]['obj']->getVar('com_created'), 'm'),
				'date_modified' => formatTimestamp($tree[$comment_id]['obj']->getVar('com_modified'), 'm'),
				'poster' => $this->_getPosterArray($tree[$comment_id]['obj']->getVar('com_uid')),
				'replies' => $replies,
				'show_replies' => $show_replies
			)
		);
	}

	/**
	 * Render replies to a thread
	 *
	 * @param   array   &$thread
	 * @param   int     $key
	 * @param   array   $replies
	 * @param   string  $prefix
	 * @param   bool    $admin_view
	 * @param   integer $depth
	 * @param   string  $current_prefix
	 */
	private function _renderThreadReplies(&$thread, $key, &$replies, $prefix, $admin_view, $depth = 0, $current_prefix = '') {
		if ($depth > 0) {
			$simple_title = $thread[$key]['obj']->getVar('com_title');
			if (FALSE != $this->_useIcons) {
				$title = $this->_getTitleIcon($thread[$key]['obj']->getVar('com_icon')) . '&nbsp;' . $thread[$key]['obj']->getVar('com_title');
			} else {
				$title = $thread[$key]['obj']->getVar('com_title');
			}
			$title = (FALSE != $admin_view) ? $title . ' ' . $this->_statusText[$thread[$key]['obj']->getVar('com_status')] : $title;
			$replies[] = array(
				'id' => $key,
				'prefix' => $current_prefix,
				'date_posted' => formatTimestamp($thread[$key]['obj']->getVar('com_created'), 'm'),
				'simple_title' => $simple_title,
				'title' => $title,
				'root_id' => $thread[$key]['obj']->getVar('com_rootid'),
				'status' => $this->_statusText[$thread[$key]['obj']->getVar('com_status')],
				'poster' => $this->_getPosterName($thread[$key]['obj']->getVar('com_uid'))
			);
			$current_prefix .= $prefix;
		}
		if (isset($thread[$key]['child']) && !empty($thread[$key]['child'])) {
			$depth++;
			foreach ($thread[$key]['child'] as $childkey) {
				if (!$admin_view && $thread[$childkey]['obj']->getVar('com_status') != XOOPS_COMMENT_ACTIVE) {
					// skip this comment if it is not active and continue on processing its child comments instead
					if (isset($thread[$childkey]['child']) && !empty($thread[$childkey]['child'])) {
						foreach ($thread[$childkey]['child'] as $childchildkey) {
							$this->_renderThreadReplies($thread, $childchildkey, $replies, $prefix, $admin_view, $depth);
						}
					}
				} else {
					$this->_renderThreadReplies($thread, $childkey, $replies, $prefix, $admin_view, $depth, $current_prefix);
				}
			}
		}
	}

	/**
	 * Render comments in nested view
	 *
	 * Danger: Recursive!
	 *
	 * @param integer $comment_id   Always "0" when called by client.
	 * @param boolean $admin_view
	 */
	public function renderNestView($comment_id = 0, $admin_view = FALSE) {
		$xot = new icms_ipf_Tree($this->_comments, 'com_id', 'com_pid', 'com_rootid');
		$tree =& $xot->getTree();
		if (FALSE != $this->_useIcons) {
			$title = $this->_getTitleIcon($tree[$comment_id]['obj']->getVar('com_icon')) . '&nbsp;' . $tree[$comment_id]['obj']->getVar('com_title');
		} else {
			$title = $tree[$comment_id]['obj']->getVar('com_title');
		}
		if (FALSE != $admin_view) {
			$text = $tree[$comment_id]['obj']->getVar('com_text') . '<div style="text-align:right; margin-top: 2px; margin-bottom: 0px; margin-right: 2px;">'
				. _CM_STATUS . ': ' . $this->_statusText[$tree[$comment_id]['obj']->getVar('com_status')]
				. '<br />IP: <span style="font-weight: bold;">' . $tree[$comment_id]['obj']->getVar('com_ip') . '</span></div>';
		} else {
			// skip this comment if it is not active and continue on processing its child comments instead
			if (XOOPS_COMMENT_ACTIVE != $tree[$comment_id]['obj']->getVar('com_status')) {
				// if there are any child comments, display them as root comments
				if (isset($tree[$comment_id]['child']) && !empty($tree[$comment_id]['child'])) {
					foreach ($tree[$comment_id]['child'] as $child_id) {
						$this->renderNestView($child_id, $admin_view);
					}
				}
				return;
			} else {
				$text = $tree[$comment_id]['obj']->getVar('com_text');
			}
		}
		$replies = array();
		$this->_renderNestReplies($tree, $comment_id, $replies, 25, $admin_view);
		$this->_tpl->append('comments',
			array(
				'pid' => $tree[$comment_id]['obj']->getVar('com_pid'),
				'id' => $tree[$comment_id]['obj']->getVar('com_id'),
				'itemid' => $tree[$comment_id]['obj']->getVar('com_itemid'),
				'rootid' => $tree[$comment_id]['obj']->getVar('com_rootid'),
				'title' => $title,
				'text' => $text,
				'date_posted' => formatTimestamp($tree[$comment_id]['obj']->getVar('com_created'), 'm'),
				'date_modified' => formatTimestamp($tree[$comment_id]['obj']->getVar('com_modified'), 'm'),
				'poster' => $this->_getPosterArray($tree[$comment_id]['obj']->getVar('com_uid')),
				'replies' => $replies
			)
		);
	}

	/**
	 * Render replies in nested view
	 *
	 * @param   array   $thread
	 * @param   int     $key
	 * @param   array   $replies
	 * @param   string  $prefix
	 * @param   bool    $admin_view
	 * @param   integer $depth
	 */
	private function _renderNestReplies(&$thread, $key, &$replies, $prefix, $admin_view, $depth = 0) {
		if ($depth > 0) {
			if (FALSE != $this->_useIcons) {
				$title = $this->_getTitleIcon($thread[$key]['obj']->getVar('com_icon')) . '&nbsp;' . $thread[$key]['obj']->getVar('com_title');
			} else {
				$title = $thread[$key]['obj']->getVar('com_title');
			}
			$text = (FALSE != $admin_view) ? $thread[$key]['obj']->getVar('com_text') . '<div style="text-align:right; margin-top: 2px; margin-right: 2px;">' . _CM_STATUS . ': ' . $this->_statusText[$thread[$key]['obj']->getVar('com_status')] . '<br />IP: <span style="font-weight: bold;">' . $thread[$key]['obj']->getVar('com_ip') . '</span></div>' : $thread[$key]['obj']->getVar('com_text');
			$replies[] = array(
				'id' => $key,
				'prefix' => $prefix,
				'pid' => $thread[$key]['obj']->getVar('com_pid'),
				'itemid' => $thread[$key]['obj']->getVar('com_itemid'),
				'rootid' => $thread[$key]['obj']->getVar('com_rootid'),
				'title' => $title,
				'text' => $text,
				'date_posted' => formatTimestamp($thread[$key]['obj']->getVar('com_created'), 'm'),
				'date_modified' => formatTimestamp($thread[$key]['obj']->getVar('com_modified'), 'm'),
				'poster' => $this->_getPosterArray($thread[$key]['obj']->getVar('com_uid'))
			);

			$prefix = $prefix + 25;
		}
		if (isset($thread[$key]['child']) && !empty($thread[$key]['child'])) {
			$depth++;
			foreach ($thread[$key]['child'] as $childkey) {
				if (!$admin_view && $thread[$childkey]['obj']->getVar('com_status') != XOOPS_COMMENT_ACTIVE) {
					// skip this comment if it is not active and continue on processing its child comments instead
					if (isset($thread[$childkey]['child']) && !empty($thread[$childkey]['child'])) {
						foreach ($thread[$childkey]['child'] as $childchildkey) {
							$this->_renderNestReplies($thread, $childchildkey, $replies, $prefix, $admin_view, $depth);
						}
					}
				} else {
					$this->_renderNestReplies($thread, $childkey, $replies, $prefix, $admin_view, $depth);
				}
			}
		}
	}


	/**
	 * Get the name of the poster
	 *
	 * @param   int $poster_id
	 * @return  string
	 */
	private function _getPosterName($poster_id) {
		$poster['id'] = (int) $poster_id;
		if ($poster['id'] > 0) {
			$com_poster =& $this->_memberHandler->getUser($poster_id);
			if (is_object($com_poster)) {
				$poster['uname'] = '<a href="' . ICMS_URL . '/userinfo.php?uid=' . $poster['id'] . '">' . $com_poster->getVar('uname') . '</a>';
				return $poster;
			}
		}
		$poster['id'] = 0; // to cope with deleted user accounts
		$poster['uname'] = $GLOBALS['icmsConfig']['anonymous'];
		return $poster;
	}

	/**
	 * Get an array with info about the poster
	 *
	 * @param   int $poster_id
	 * @return  array
	 */
	private function _getPosterArray($poster_id) {
		$poster['id'] = (int) $poster_id;
		if ($poster['id'] > 0) {
			$com_poster =& $this->_memberHandler->getUser($poster['id']);
			if (is_object($com_poster)) {
				$poster['uname'] = '<a href="' . ICMS_URL . '/userinfo.php?uid=' . $poster['id'] . '">' . $com_poster->getVar('uname') . '</a>';
				$poster_rank = $com_poster->rank();
				$poster['rank_image'] = $poster_rank['image'];
				$poster['rank_title'] = $poster_rank['title'];
				$poster['avatar'] = $com_poster->gravatar();
				$poster['regdate'] = formatTimestamp($com_poster->getVar('user_regdate'), 's');
				$poster['from'] = $com_poster->getVar('user_from');
				$poster['postnum'] = $com_poster->getVar('posts');
				$poster['status'] = $com_poster->isOnline() ? _CM_ONLINE : '';
				return $poster;
			}
		}
		$poster['id'] = 0; // to cope with deleted user accounts
		$poster['uname'] = $GLOBALS['icmsConfig']['anonymous'];
		$poster['rank_title'] = '';
		$poster['avatar'] = ICMS_UPLOAD_URL . '/blank.gif';
		$poster['regdate'] = '';
		$poster['from'] = '';
		$poster['postnum'] = 0;
		$poster['status'] = '';
		return $poster;
	}

	/**
	 * Get the IMG tag for the title icon
	 *
	 * @param   string  $icon_image
	 * @return  string  HTML IMG tag
	 */
	private function _getTitleIcon($icon_image) {
		$icon_image = htmlspecialchars(trim($icon_image));
		if ($icon_image != '') {
			if (FALSE != $this->_doIconCheck) {
				if (!file_exists(ICMS_URL . '/images/subject/' . $icon_image)) {
					return '<img src="' . ICMS_URL . '/images/icons/' . $GLOBALS["icmsConfig"]["language"] . '/no_posticon.gif" alt="" />';
				} else {
					return '<img src="' . ICMS_URL . '/images/subject/' . $icon_image . '" alt="" />';
				}
			} else {
				return '<img src="' . ICMS_URL . '/images/subject/' . $icon_image . '" alt="" />';
			}
		}
		return '<img src="' . ICMS_URL . '/images/icons/' . $GLOBALS["icmsConfig"]["language"] . '/no_posticon.gif" alt="" />';
	}
}
