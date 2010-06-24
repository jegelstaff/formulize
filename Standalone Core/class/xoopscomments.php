<?php
/**
* Handles all the comments within ImpressCMS
*
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	LICENSE.txt
* @package	core
* @since	XOOPS
* @author	http://www.xoops.org The XOOPS Project
* @author	modified by UnderDog <underdog@impresscms.org>
* @version	$Id: xoopscomments.php 8768 2009-05-16 22:48:26Z pesianstranger $
*/

if (!defined('ICMS_ROOT_PATH')) {
	exit();
}
include_once ICMS_ROOT_PATH."/class/xoopstree.php";
require_once ICMS_ROOT_PATH.'/class/xoopsobject.php';
icms_loadLanguageFile('core', 'comment');

class XoopsComments extends XoopsObject
{
	var $ctable;
	var $db;

	function XoopsComments($ctable, $id=null)
	{
		$this->ctable = $ctable;
		$this->db =& Database::getInstance();
		$this->XoopsObject();
		$this->initVar('comment_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('item_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('order', XOBJ_DTYPE_INT, null, false);
		$this->initVar('mode', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('subject', XOBJ_DTYPE_TXTBOX, null, false, 255);
		$this->initVar('comment', XOBJ_DTYPE_TXTAREA, null, false, null);
		$this->initVar('ip', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('pid', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('date', XOBJ_DTYPE_INT, null, false);
		$this->initVar('nohtml', XOBJ_DTYPE_INT, 1, false);
		$this->initVar('nosmiley', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('noxcode', XOBJ_DTYPE_INT, 0, false);
		$this->initVar('user_id', XOBJ_DTYPE_INT, null, false);
		$this->initVar('icon', XOBJ_DTYPE_OTHER, null, false);
		$this->initVar('prefix', XOBJ_DTYPE_OTHER, null, false);
		if ( !empty($id) ) {
			if ( is_array($id) ) {
				$this->assignVars($id);
			} else {
				$this->load(intval($id));
			}
		}
	}


  /**
   * Loads all the data from one comment ID
   * @param   int     $id   ID of all the commentdata to load
   */
	function load($id)
	{
		$id = intval($id);
		$sql = "SELECT * FROM ".$this->ctable." WHERE comment_id='".$id."'";
		$arr = $this->db->fetchArray($this->db->query($sql));
		$this->assignVars($arr);
	}


  /**
   * Stores the comment into the database
   * @return   int     $comment_id   ID of the comment that was stored
   */
	function store()
	{
		if ( !$this->cleanVars() ) {
			return false;
		}
		foreach ( $this->cleanVars as $k=>$v ) {
			$$k = $v;
		}
		$isnew = false;
		if ( empty($comment_id ) ) {
			$isnew = true;
			$comment_id = $this->db->genId($this->ctable."_comment_id_seq");
			$sql = sprintf("INSERT INTO %s (comment_id, pid, item_id, date, user_id, ip, subject, comment, nohtml, nosmiley, noxcode, icon) VALUES ('%u', '%u', '%u', '%u', '%u', '%s', '%s', '%s', '%u', '%u', '%u', '%s')", $this->ctable, intval($comment_id), intval($pid), intval($item_id), time(), intval($user_id), $ip, $subject, $comment, intval($nohtml), intval($nosmiley), intval($noxcode), $icon);
		} else {
			$sql = sprintf("UPDATE %s SET subject = '%s', comment = '%s', nohtml = '%u', nosmiley = '%u', noxcode = '%u', icon = '%s'  WHERE comment_id = '%u'", $this->ctable, $subject, $comment, intval($nohtml), intval($nosmiley), intval($noxcode), $icon, intval($comment_id));
		}
		if ( !$result = $this->db->query($sql) ) {
			//echo $sql;
			return false;
		}
		if ( empty($comment_id) ) {
			$comment_id = $this->db->getInsertId();
		}
		if ( $isnew != false ) {
			$sql = sprintf("UPDATE %s SET posts = posts+1 WHERE uid = '%u'", $this->db->prefix("users"), intval($user_id));
			if (!$result = $this->db->query($sql)) {
				echo _CM_COULDNOTUPDATEPOSTS;
			}
		}
		return $comment_id;
	}


  /**
   * Deletes one comment ID
   * @return   mixed
   */
	function delete()
	{
		$sql = sprintf("DELETE FROM %s WHERE comment_id = '%u'", $this->ctable, intval($this->getVar('comment_id')));
		if ( !$result = $this->db->query($sql) ) {
			return false;
		}
		$sql = sprintf("UPDATE %s SET posts = posts-1 WHERE uid = '%u'", $this->db->prefix("users"), intval($this->getVar("user_id")));
		if ( !$result = $this->db->query($sql) ) {
			echo _CM_COULDNOTUPDATEPOSTS;
		}
		$mytree = new XoopsTree($this->ctable, "comment_id", "pid");
		$arr = $mytree->getAllChild($this->getVar("comment_id"), "comment_id");
		$size = count($arr);
		if ( $size > 0 ) {
			for ( $i = 0; $i < $size; $i++ ) {
				$sql = sprintf("DELETE FROM %s WHERE comment_bid = '%u'", $this->ctable, $arr[$i]['comment_id']);
				if ( !$result = $this->db->query($sql) ) {
					echo _CM_COMDELETENG;
				}
				$sql = sprintf("UPDATE %s SET posts = posts-1 WHERE uid = '%u'", $this->db->prefix("users"), $arr[$i]['user_id']);
				if ( !$result = $this->db->query($sql) ) {
					echo _CM_COULDNOTUPDATEPOSTS;
				}
			}
		}
		return ($size + 1);
	}


  /**
   * Gets Comments and comments belonging to that comment in a tree
   * @return   array     $ret   Array of comments in a tree
   */
	function getCommentTree()
	{
		$mytree = new XoopsTree($this->ctable, "comment_id", "pid");
		$ret = array();
		$tarray = $mytree->getChildTreeArray($this->getVar("comment_id"), "comment_id");
		foreach ( $tarray as $ele ) {
			$ret[] = new XoopsComments($this->ctable,$ele);
		}
		return $ret;
	}


  /**
   * Loads one comment ID
   * @param    array      $criteria   Criteria of the WHERE statement to get the comments
   * @param    bool       $asobject   Would we want the comments loaded as an object?
   * @param    string     $orderby    The ordering of the comments
   * @param    int        $limit      Limit the comments by <number> per page
   * @param    int        $start      Start showing the comments at number <number> (for pagination of the comments)
   * @return   array      $ret        Array of comments
   */
	function getAllComments($criteria=array(), $asobject=true, $orderby="comment_id ASC", $limit=0, $start=0)
	{
		$ret = array();
		$where_query = "";
		if ( is_array($criteria) && count($criteria) > 0 ) {
			$where_query = " WHERE";
			foreach ( $criteria as $c ) {
				$where_query .= " $c AND";
			}
			$where_query = substr($where_query, 0, -4);
		}
		if ( !$asobject ) {
			$sql = "SELECT comment_id FROM ".$this->ctable."$where_query ORDER BY $orderby";
			$result = $this->db->query($sql,$limit,$start);
			while ( $myrow = $this->db->fetchArray($result) ) {
				$ret[] = $myrow['comment_id'];
			}
		} else {
			$sql = "SELECT * FROM ".$this->ctable."".$where_query." ORDER BY $orderby";
			$result = $this->db->query($sql,$limit,$start);
			while ( $myrow = $this->db->fetchArray($result) ) {
				$ret[] = new XoopsComments($this->ctable,$myrow);
			}
		}
		//echo $sql;
		return $ret;
	}




	/* Methods below will be moved to maybe another class? */
  /**
   * Prints navigation bar with ways to show the comments (threaded, flat, etc)
   * @param    int        $item_id    Comment ID
   * @param    int        $order      The way the comments were sorted
   */
	function printNavBar($item_id, $mode="flat", $order=1)
	{
		global $icmsConfig, $icmsUser;
		echo "<form method='get' action='".$_SERVER['PHP_SELF']."'><table width='100%' border='0' cellspacing='1' cellpadding='2'><tr><td class='bg1' align='center'><select name='mode'><option value='nocomments'";
		if ( $mode == "nocomments" ) {
			echo " selected='selected'";
		}
		echo ">". _NOCOMMENTS ."</option><option value='flat'";
		if ($mode == 'flat') {
			echo " selected='selected'";
		}
		echo ">". _FLAT ."</option><option value='thread'";
		if ( $mode == "thread" || $mode == "" ) {
			echo " selected='selected'";
		}
		echo ">". _THREADED ."</option></select><select name='order'><option value='0'";
		if ( $order != 1 ) {
			echo " selected='selected'";
		}
		echo ">". _OLDESTFIRST ."</option><option value='1'";
		if ( $order == 1 ) {
			echo " selected='selected'";
		}
		echo ">". _NEWESTFIRST ."</option></select><input type='hidden' name='item_id' value='".intval($item_id)."' /><input type='submit' value='". _CM_REFRESH ."' />";
		if ( $icmsConfig['anonpost'] == 1 || $icmsUser ) {
			if ($mode != "flat" || $mode != "nocomments" || $mode != "thread" ) {
				$mode = "flat";
			}
			echo "&nbsp;<input type='button' onclick='location=\"newcomment.php?item_id=".intval($item_id)."&amp;order=".intval($order)."&amp;mode=".$mode."\"' value='"._CM_POSTCOMMENT."' />";
		}
		echo "</td></tr></table></form>";
	}


  /**
   * Shows the heading ot the comment thread
   */
	function showThreadHead()
	{
		openThread();
	}


  /**
   * Shows the entire comment thread
   * @param    int        $order        The way the comments were sorted
   * @param    string     $mode         The way the comments are shown (flat, threaded, etc)
   * @param    int        $adminview    Turn on the admin view
   * @param    int        $color_num    Color number for the odd even alternate row colors cycle
   */
	function showThreadPost($order, $mode, $adminview=0, $color_num=1)
	{
		global $icmsConfig, $icmsUser;
		$edit_image = "";
		$reply_image = "";
		$delete_image = "";
		$post_date = formatTimestamp($this->getVar("date"),"m");
		if ( $this->getVar("user_id") != 0 ) {
			$poster = new XoopsUser($this->getVar("user_id"));
			if ( !$poster->isActive() ) {
				$poster = 0;
			}
		} else {
			$poster = 0;
		}
		if ( $this->getVar("icon") != null && $this->getVar("icon") != "" ) {
			$subject_image = "<a name='".$this->getVar("comment_id")."' id='".$this->getVar("comment_id")."'></a><img src='".ICMS_URL."/images/subject/".$this->getVar("icon")."' alt='' />";
		} else {
			$subject_image =  "<a name='".$this->getVar("comment_id")."' id='".$this->getVar("comment_id")."'></a><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/no_posticon.gif' alt='' />";
		}
		if ( $adminview ) {
			$ip_image = "<img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/ip.gif' alt='".$this->getVar("ip")."' />";
		} else {
			$ip_image = "<img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/ip.gif' alt='' />";
		}
		if ( $adminview || ($icmsUser && $this->getVar("user_id") == $icmsUser->getVar("uid")) ) {
			$edit_image = "<a href='editcomment.php?comment_id=".$this->getVar("comment_id")."&amp;mode=".$mode."&amp;order=".intval($order)."'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/edit.gif' alt='"._EDIT."' /></a>";
		}
		if ( $icmsConfig['anonpost'] || $icmsUser ) {
			$reply_image = "<a href='replycomment.php?comment_id=".$this->getVar("comment_id")."&amp;mode=".$mode."&amp;order=".intval($order)."'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/reply.gif' alt='"._REPLY."' /></a>";
		}
		if ( $adminview ) {
			$delete_image = "<a href='deletecomment.php?comment_id=".$this->getVar("comment_id")."&amp;mode=".$mode."&amp;order=".intval($order)."'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/delete.gif' alt='"._DELETE."' /></a>";
		}

		if ( $poster ) {
			$text = $this->getVar("comment");
			if ( $poster->getVar("attachsig") ) {
				$text .= "<p><br />_________________<br />". $poster->user_sig()."</p>";
			}
			$reg_date = _CM_JOINED;
			$reg_date .= formatTimestamp($poster->getVar("user_regdate"),"s");
			$posts = _CM_POSTS;
			$posts .= $poster->getVar("posts");
			$user_from = _CM_FROM;
			$user_from .= $poster->getVar("user_from");
			$rank = $poster->rank();
			if ( $rank['image'] != "" ) {
				$rank['image'] = "<img src='".XOOPS_UPLOAD_URL."/".$rank['image']."' alt='' />";
			}
			$avatar_image = "<img src='".XOOPS_UPLOAD_URL."/".$poster->getVar("user_avatar")."' alt='' />";
			if ( $poster->isOnline() ) {
				$online_image = "<span style='color:#ee0000;font-weight:bold;'>"._ONLINE."</span>";
			} else {
				$online_image = "";
			}
			$profile_image = "<a href='".ICMS_URL."/userinfo.php?uid=".$poster->getVar("uid")."'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/profile.gif' alt='"._PROFILE."' /></a>";
			if ( $icmsUser ) {
				$pm_image =  "<a href='javascript:openWithSelfMain(\"".ICMS_URL."/pmlite.php?send2=1&amp;to_userid=".$poster->getVar("uid")."\",\"pmlite\",800,680);'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/pm.gif' alt='".sprintf(_SENDPMTO,$poster->getVar("uname", "E"))."' /></a>";
			} else {
				$pm_image = "";
			}
   			if ( $poster->getVar("user_viewemail") ) {
				$email_image = "<a href='mailto:".$poster->getVar("email", "E")."'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/email.gif' alt='".sprintf(_SENDEMAILTO,$poster->getVar("uname", "E"))."' /></a>";
			} else {
				$email_image = "";
			}
			$posterurl = $poster->getVar("url");
   			if ( $posterurl != "" ) {
				$www_image = "<a href='$posterurl' target='_blank'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/www.gif' alt='"._VISITWEBSITE."' /></a>";
			} else {
				$www_image = "";
			}
   			if ( $poster->getVar("user_icq") != "" ) {
				$icq_image = "<a href='http://wwp.icq.com/scripts/search.dll?to=".$poster->getVar("user_icq", "E")."'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/icq_add.gif' alt='"._ADD."' /></a>";
			} else {
				$icq_image = "";
			}
			if ( $poster->getVar("user_aim") != "" ) {
				$aim_image = "<a href='aim:goim?screenname=".$poster->getVar("user_aim", "E")."&message=Hi+".$poster->getVar("user_aim")."+Are+you+there?'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/aim.gif' alt='aim' /></a>";
			} else {
				$aim_image = "";
			}
   			if ( $poster->getVar("user_yim") != "" ) {
				$yim_image = "<a href='http://edit.yahoo.com/config/send_webmesg?.target=".$poster->getVar("user_yim", "E")."&.src=pg'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/yim.gif' alt='yim' /></a>";
			} else {
				$yim_image = "";
			}
			if ( $poster->getVar("user_msnm") != "" ) {
				$msnm_image = "<a href='".ICMS_URL."/userinfo.php?uid=".$poster->getVar("uid")."'><img src='".ICMS_URL."/images/icons/".$GLOBALS["xoopsConfig"]["language"]."/msnm.gif' alt='msnm' /></a>";
			} else {
				$msnm_image = "";
			}
			showThread($color_num, $subject_image, $this->getVar("subject"), $text, $post_date, $ip_image, $reply_image, $edit_image, $delete_image, $poster->getVar("uname"), $rank['title'], $rank['image'], $avatar_image, $reg_date, $posts, $user_from, $online_image, $profile_image, $pm_image, $email_image, $www_image, $icq_image, $aim_image, $yim_image, $msnm_image);
		} else {
			showThread($color_num, $subject_image, $this->getVar("subject"), $this->getVar("comment"), $post_date, $ip_image, $reply_image, $edit_image, $delete_image, $icmsConfig['anonymous']);
		}
	}


  /**
   * Shows the comment thread footer
   */
	function showThreadFoot()
	{
		closeThread();
	}


  /**
   * Shows the comment tree header
   * @param    string        $width    The width of the table for the treeHead
   */
	function showTreeHead($width="100%")
	{
		echo "<table border='0' class='outer' cellpadding='0' cellspacing='0' align='center' width='$width'><tr class='bg3' align='center'><td colspan='3'>". _CM_REPLIES ."</td></tr><tr class='bg3' align='"._GLOBAL_LEFT."'><td width='60%' class='fg2'>". _CM_TITLE ."</td><td width='20%' class='fg2'>". _CM_POSTER ."</td><td class='fg2'>". _CM_POSTED ."</td></tr>";
	}


  /**
   * Loads one comment ID
   * @param    int        $order        The way the comments were sorted
   * @param    string     $mode         The way the comments are shown (flat, threaded, etc)
   * @param    int        $color_num    Color number for the odd even alternate row colors cycle
   */
	function showTreeItem($order, $mode, $color_num)
	{
		if ( $color_num == 1 ) {
			$bg = 'even';
		} else {
			$bg = 'odd';
		}
		$prefix = str_replace(".", "&nbsp;&nbsp;&nbsp;&nbsp;", $this->getVar("prefix"));
		$date = formatTimestamp($this->getVar("date"),"m");
		if ( $this->getVar("icon") != "" ) {
			$icon = "subject/".$this->getVar("icon", "E");
		} else {
			$icon = "icons/no_posticon.gif";
		}
		echo "<tr class='$bg' align='"._GLOBAL_LEFT."'><td>".$prefix."<img src='".ICMS_URL."/images/".$icon."'>&nbsp;<a href='".$_SERVER['PHP_SELF']."?item_id=".$this->getVar("item_id")."&amp;comment_id=".$this->getVar("comment_id")."&amp;mode=".$mode."&amp;order=".$order."#".$this->getVar("comment_id")."'>".$this->getVar("subject")."</a></td><td><a href='".ICMS_URL."/userinfo.php?uid=".$this->getVar("user_id")."'>".XoopsUser::getUnameFromId($this->getVar("user_id"))."</a></td><td>".$date."</td></tr>";
	}


  /**
   * Shows the comment tree footer
   */
	function showTreeFoot()
	{
		echo "</table><br />";
	}
}
?>