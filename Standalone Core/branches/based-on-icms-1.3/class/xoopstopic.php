<?php
/**
 * Old class for generating news topics
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: xoopstopic.php 20475 2010-12-04 22:56:11Z skenow $
 */

if (!defined('ICMS_ROOT_PATH')) {
	exit();
}

class XoopsTopic
{
	var $table;
	var $topic_id;
	var $topic_pid;
	var $topic_title;
	var $topic_imgurl;
	var $prefix; // only used in topic tree
	var $use_permission=false;
	var $mid; // module id used for setting permission

	/**
	 * Constructor
	 *
	 * @param   string   $table      the table with all the topics
	 * @param   int      $topicid    the current topicid
	 **/
	function XoopsTopic($table, $topicid=0)
	{
		$this->db =& icms_db_Factory::instance();
		$this->table = $table;
		if ( is_array($topicid) ) {
			$this->makeTopic($topicid);
		} elseif ( $topicid != 0 ) {
			$this->getTopic( (int) ($topicid));
		} else {
			$this->topic_id = $topicid;
		}
	}

	/**
	 * Sets topic title
	 *
	 * @param   string   $value      Value of the topic title
	 **/
	function setTopicTitle($value)
	{
		$this->topic_title = $value;
	}

	/**
	 * Sets topic Imageurl
	 * @param   string   $value      Value of the image url
	 **/
	function setTopicImgurl($value)
	{
		$this->topic_imgurl = $value;
	}

	/**
	 * Sets topic Parentid
	 * @param   string   $value      Value of the topic Parentid
	 **/
	function setTopicPid($value)
	{
		$this->topic_pid = $value;
	}

	/**
	 * Gets Topic
	 * @param   int      $topicid    The entire topic
	 **/
	function getTopic($topicid)
	{
		$topicid = (int) ($topicid);
		$sql = "SELECT * FROM ".$this->table." WHERE topic_id='".$topicid."'";
		$array = $this->db->fetchArray($this->db->query($sql));
		$this->makeTopic($array);
	}

	/**
	 * Makes Topic
	 *
	 * @param   array    $array      The passed array with topic fields
	 **/
	function makeTopic($array)
	{
		foreach($array as $key=>$value){
			$this->$key = $value;
		}
	}

	/**
	 * usePermission
	 *
	 * @param   int      $mid        The ModuleID from which permission is needed
	 **/
	function usePermission($mid)
	{
		$this->mid = $mid;
		$this->use_permission = true;
	}

	/**
	 * Save the information to the DataBase
	 * @return  bool               Was the information successfully saved into the database
	 **/
	function store()
	{
		$myts =& icms_core_Textsanitizer::getInstance();
		$title = "";
		$imgurl = "";
		if ( isset($this->topic_title) && $this->topic_title != "" ) {
			$title = $myts->addSlashes($this->topic_title);
		}
		if ( isset($this->topic_imgurl) && $this->topic_imgurl != "" ) {
			$imgurl = $myts->addSlashes($this->topic_imgurl);
		}
		if ( !isset($this->topic_pid) || !is_numeric($this->topic_pid) ) {
			$this->topic_pid = 0;
		}
		if ( empty($this->topic_id) ) {
			$this->topic_id = $this->db->genId($this->table."_topic_id_seq");
			$sql = sprintf("INSERT INTO %s (topic_id, topic_pid, topic_imgurl, topic_title) VALUES ('%u', '%u', '%s', '%s')", $this->table, (int) ($this->topic_id), (int) ($this->topic_pid), $imgurl, $title);
		} else {
			$sql = sprintf("UPDATE %s SET topic_pid = '%u', topic_imgurl = '%s', topic_title = '%s' WHERE topic_id = '%u'", $this->table, (int) ($this->topic_pid), $imgurl, $title, (int) ($this->topic_id));
		}
		if ( !$result = $this->db->query($sql) ) {
			ErrorHandler::show('0022');
		}
		if ( $this->use_permission == true ) {
			if ( empty($this->topic_id) ) {
				$this->topic_id = $this->db->getInsertId();
			}
			$xt = new icms_view_Tree($this->table, "topic_id", "topic_pid");
			$parent_topics = $xt->getAllParentId($this->topic_id);
			if ( !empty($this->m_groups) && is_array($this->m_groups) ){
				foreach ( $this->m_groups as $m_g ) {
					$moderate_topics = XoopsPerms::getPermitted($this->mid, "ModInTopic", $m_g);
					$add = true;
					// only grant this permission when the group has this permission in all parent topics of the created topic
					foreach($parent_topics as $p_topic){
						if ( !in_array($p_topic, $moderate_topics) ) {
							$add = false;
							continue;
						}
					}
					if ( $add == true ) {
						$xp = new XoopsPerms();
						$xp->setModuleId($this->mid);
						$xp->setName("ModInTopic");
						$xp->setItemId($this->topic_id);
						$xp->store();
						$xp->addGroup($m_g);
					}
				}
			}
			if ( !empty($this->s_groups) && is_array($this->s_groups) ){
				foreach ( $s_groups as $s_g ) {
					$submit_topics = XoopsPerms::getPermitted($this->mid, "SubmitInTopic", $s_g);
					$add = true;
					foreach($parent_topics as $p_topic){
						if ( !in_array($p_topic, $submit_topics) ) {
							$add = false;
							continue;
						}
					}
					if ( $add == true ) {
						$xp = new XoopsPerms();
						$xp->setModuleId($this->mid);
						$xp->setName("SubmitInTopic");
						$xp->setItemId($this->topic_id);
						$xp->store();
						$xp->addGroup($s_g);
					}
				}
			}
			if ( !empty($this->r_groups) && is_array($this->r_groups) ){
				foreach ( $r_groups as $r_g ) {
					$read_topics = XoopsPerms::getPermitted($this->mid, "ReadInTopic", $r_g);
					$add = true;
					foreach($parent_topics as $p_topic){
						if ( !in_array($p_topic, $read_topics) ) {
							$add = false;
							continue;
						}
					}
					if ( $add == true ) {
						$xp = new XoopsPerms();
						$xp->setModuleId($this->mid);
						$xp->setName("ReadInTopic");
						$xp->setItemId($this->topic_id);
						$xp->store();
						$xp->addGroup($r_g);
					}
				}
			}
		}
		return true;
	}

	/**
	 * Deletes the topic from the database
	 **/
	function delete()
	{
		$sql = sprintf("DELETE FROM %s WHERE topic_id = '%u'", $this->table, (int) ($this->topic_id));
		$this->db->query($sql);
	}

	/**
	 * Returns the topic_id
	 * @return  int
	 **/
	function topic_id()
	{
		return $this->topic_id;
	}

	/**
	 * Returns the topic parentid
	 * @return  int
	 **/
	function topic_pid()
	{
		return $this->topic_pid;
	}

	/**
	 * Returns topic_title in a certain format
	 * @param   string   $format
	 * @return  string   $title
	 **/
	function topic_title($format="S")
	{
		$myts =& icms_core_Textsanitizer::getInstance();
		switch($format){
			case "S":
				$title = $myts->htmlSpecialChars($this->topic_title);
				break;
			case "E":
				$title = $myts->htmlSpecialChars($this->topic_title);
				break;
			case "P":
				$title = $myts->makeTboxData4Preview($this->topic_title);
				break;
			case "F":
				$title = $myts->makeTboxData4PreviewInForm($this->topic_title);
				break;
		}
		return $title;
	}

	/**
	 * Returns the topic_imgurl in a certain format
	 * @param   string   $format
	 * @return  string   $imgurl
	 **/
	function topic_imgurl($format="S")
	{
		$myts =& icms_core_Textsanitizer::getInstance();
		switch($format){
			case "S":
				$imgurl= $myts->htmlSpecialChars($this->topic_imgurl);
				break;
			case "E":
				$imgurl = $myts->htmlSpecialChars($this->topic_imgurl);
				break;
			case "P":
				$imgurl = $myts->makeTboxData4Preview($this->topic_imgurl);
				break;
			case "F":
				$imgurl = $myts->makeTboxData4PreviewInForm($this->topic_imgurl);
				break;
		}
		return $imgurl;
	}

	/**
	 * prefix
	 * @return  string
	 **/
	function prefix()
	{
		if ( isset($this->prefix) ) {
			return $this->prefix;
		}
	}

	/**
	 * Gets first child topics (first children in a tree)
	 * @return  array    $ret      The first children
	 **/
	function getFirstChildTopics()
	{
		$ret = array();
		$xt = new icms_view_Tree($this->table, "topic_id", "topic_pid");
		$topic_arr = $xt->getFirstChild($this->topic_id, "topic_title");
		if ( is_array($topic_arr) && count($topic_arr) ) {
			foreach($topic_arr as $topic){
				$ret[] = new XoopsTopic($this->table, $topic);
			}
		}
		return $ret;
	}

	/**
	 * Get all child topics (all children in a tree)
	 * @return  array    $ret      All first children
	 **/
	function getAllChildTopics()
	{
		$ret = array();
		$xt = new icms_view_Tree($this->table, "topic_id", "topic_pid");
		$topic_arr = $xt->getAllChild($this->topic_id, "topic_title");
		if ( is_array($topic_arr) && count($topic_arr) ) {
			foreach($topic_arr as $topic){
				$ret[] = new XoopsTopic($this->table, $topic);
			}
		}
		return $ret;
	}

	/**
	 * Gets child Topics in a tree array
	 * @return  array    $ret      The tree array
	 **/
	function getChildTopicsTreeArray()
	{
		$ret = array();
		$xt = new icms_view_Tree($this->table, "topic_id", "topic_pid");
		$topic_arr = $xt->getChildTreeArray($this->topic_id, "topic_title");
		if ( is_array($topic_arr) && count($topic_arr) ) {
			foreach($topic_arr as $topic){
				$ret[] = new XoopsTopic($this->table, $topic);
			}
		}
		return $ret;
	}

	/**
	 * Make a selection box out of the topics
	 *
	 * @param    string  $none       what is the text value for "none selected"
	 * @param    string  $seltopic   what is the selected topic
	 * @param    string  $selname    what is the name of the selectbox
	 * @param    string  $onchange   what is the onchange event
	 **/
	function makeTopicSelBox($none=0, $seltopic=-1, $selname="", $onchange="")
	{
		$xt = new icms_view_Tree($this->table, "topic_id", "topic_pid");
		if ( $seltopic != -1 ) {
			$xt->makeMySelBox("topic_title", "topic_title", $seltopic, $none, $selname, $onchange);
		} elseif ( !empty($this->topic_id) ) {
			$xt->makeMySelBox("topic_title", "topic_title", $this->topic_id, $none, $selname, $onchange);
		} else {
			$xt->makeMySelBox("topic_title", "topic_title", 0, $none, $selname, $onchange);
		}
	}

	/**
	 * generates nicely formatted linked path from the root id to a given id
	 *
	 * @param   string   $funcURL    the func url that's a parameter for the getNicePathFromId function
	 * @return  string   $ret        the formatted linked path
	 **/
	function getNiceTopicPathFromId($funcURL)
	{
		$xt = new icms_view_Tree($this->table, "topic_id", "topic_pid");
		$ret = $xt->getNicePathFromId($this->topic_id, "topic_title", $funcURL);
		return $ret;
	}

	/**
	 * Get all the ID's for the child topics
	 * @return  array    $ret        All the child topics in an array
	 **/
	function getAllChildTopicsId()
	{
		$xt = new icms_view_Tree($this->table, "topic_id", "topic_pid");
		$ret = $xt->getAllChildId($this->topic_id, "topic_title");
		return $ret;
	}

	/**
	 * Gets list of topics
	 * @return  array    $ret        Array of topic id's, topic parentid's and topic titles
	 **/
	function getTopicsList()
	{
		$result = $this->db->query('SELECT topic_id, topic_pid, topic_title FROM '.$this->table);
		$ret = array();
		$myts =& icms_core_Textsanitizer::getInstance();
		while ($myrow = $this->db->fetchArray($result)) {
			$ret[$myrow['topic_id']] = array('title' => $myts->htmlspecialchars($myrow['topic_title']), 'pid' => $myrow['topic_pid']);
		}
		return $ret;
	}

	/**
	 * Does the topic exist
	 *
	 * @param   string   $pid        The parentid of the topic
	 * @param   string   $title      The title of the topic
	 * @return  bool
	 **/
	function topicExists($pid, $title) {
		$sql = "SELECT COUNT(*) from ".$this->table." WHERE topic_pid = ". (int) ($pid)." AND topic_title = '".trim($title)."'";
		$rs = $this->db->query($sql);
		list($count) = $this->db->fetchRow($rs);
		if ($count > 0) {
			return true;
		} else {
			return false;
		}
	}
}
?>