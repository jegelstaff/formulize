<?php
/**
 * Old class for generating news items
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	core
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: xoopsstory.php 20119 2010-09-09 17:55:46Z phoenyx $
 */

if(!defined('ICMS_ROOT_PATH'))
{
	exit();
}
include_once ICMS_ROOT_PATH.'/class/xoopstopic.php';
include_once ICMS_ROOT_PATH.'/kernel/user.php';

class XoopsStory
{
	var $table;
	var $storyid;
	var $topicid;
	var $uid;
	var $title;
	var $hometext;
	var $bodytext='';
	var $counter;
	var $created;
	var $published;
	var $expired;
	var $hostname;
	var $nohtml=0;
	var $nosmiley=0;
	var $ihome=0;
	var $notifypub=0;
	var $type;
	var $approved;
	var $topicdisplay;
	var $topicalign;
	var $db;
	var $topicstable;
	var $comments;

	/**
	 * Contstructor
	 *
	 * @param   int      $storyid
	 **/
	function Story($storyid=-1)
	{
		$this->db = icms_db_Factory::instance();
		$this->table = '';
		$this->topicstable = '';
		if(is_array($storyid))
		{
			$this->makeStory($storyid);
		}
		elseif($storyid != -1)
		{
			$this->getStory( (int) ($storyid));
		}
	}

	/**
	 * Sets current storyid
	 *
	 * @param   int      $value
	 **/
	function setStoryId($value)
	{
		$this->storyid = (int) ($value);
	}

	/**
	 * Sets current topicid
	 *
	 * @param   int      $value
	 **/
	function setTopicId($value)
	{
		$this->topicid = (int) ($value);
	}

	/**
	 * Sets current userid
	 *
	 * @param   int      $value
	 **/
	function setUid($value)
	{
		$this->uid = (int) ($value);
	}

	/**
	 * Sets current title
	 *
	 * @param   string   $value
	 **/
	function setTitle($value)
	{
		$this->title = $value;
	}

	/**
	 * Sets current hometext (intro text)
	 *
	 * @param   string   $value
	 **/
	function setHometext($value)
	{
		$this->hometext = $value;
	}

	/**
	 * Sets current body (body text)
	 *
	 * @param   string   $value
	 **/
	function setBodytext($value)
	{
		$this->bodytext = $value;
	}

	/**
	 * Sets current date published
	 *
	 * @param   int      $value
	 **/
	function setPublished($value)
	{
		$this->published = (int) ($value);
	}

	/**
	 * Sets current date expired
	 *
	 * @param   int      $value
	 **/
	function setExpired($value)
	{
		$this->expired = (int) ($value);
	}

	/**
	 * Sets current hostname
	 *
	 * @param   string      $value
	 **/
	function setHostname($value)
	{
		$this->hostname = $value;
	}

	/**
	 * Sets value of nohtml
	 *
	 * @param   int      $value
	 **/
	function setNohtml($value=0)
	{
		$this->nohtml = $value;
	}

	/**
	 * Sets value of nosmiley
	 *
	 * @param   int      $value
	 **/
	function setNosmiley($value=0)
	{
		$this->nosmiley = $value;
	}

	/**
	 * Sets current value of ihome
	 *
	 * @param   string      $value
	 **/
	function setIhome($value)
	{
		$this->ihome = $value;
	}

	/**
	 * Sets current value of notifypub
	 *
	 * @param   string      $value
	 **/
	function setNotifyPub($value)
	{
		$this->notifypub = $value;
	}

	/**
	 * Sets type
	 *
	 * @param   string      $value
	 **/
	function setType($value)
	{
		$this->type = $value;
	}

	/**
	 * Sets current value of approved
	 *
	 * @param   int        $value
	 **/
	function setApproved($value)
	{
		$this->approved = (int) ($value);
	}

	/**
	 * Sets current value of topicdisplay
	 *
	 * @param   string      $value
	 **/
	function setTopicdisplay($value)
	{
		$this->topicdisplay = $value;
	}

	/**
	 * Sets current value of topicalign
	 *
	 * @param   string      $value
	 **/
	function setTopicalign($value)
	{
		$this->topicalign = $value;
	}

	/**
	 * Sets current value of comments
	 *
	 * @param   int      $value
	 **/
	function setComments($value)
	{
		$this->comments = (int) ($value);
	}

	/**
	 * Stores the story. Don't set to published when not approved
	 *
	 * @param   bool      $approved
	 **/
	function store($approved=false)
	{
		//$newpost = 0;
		$myts = icms_core_Textsanitizer::getInstance();
		$title = $myts->censorString($this->title);
		$hometext = $myts->censorString($this->hometext);
		$bodytext = $myts->censorString($this->bodytext);
		$title = $myts->addSlashes($title);
		$hometext = $myts->displayTarea($hometext);
		$bodytext = $myts->displayTarea($bodytext);
		if(!isset($this->nohtml) || $this->nohtml != 1)
		{
			$this->nohtml = 0;
		}
		if(!isset($this->nosmiley) || $this->nosmiley != 1)
		{
			$this->nosmiley = 0;
		}
		if(!isset($this->notifypub) || $this->notifypub != 1)
		{
			$this->notifypub = 0;
		}
		if(!isset($this->topicdisplay) || $this->topicdisplay != 0)
		{
			$this->topicdisplay = 1;
		}
		$expired = !empty($this->expired) ? $this->expired : 0;
		if(!isset($this->storyid))
		{
			//$newpost = 1;
			$newstoryid = $this->db->genId($this->table."_storyid_seq");
			$created = time();
			$published = ($this->approved) ? $this->published : 0;

			$sql = sprintf("INSERT INTO %s (storyid, uid, title, created, published, expired, hostname, nohtml, nosmiley, hometext, bodytext, counter, topicid, ihome, notifypub, story_type, topicdisplay, topicalign, comments) VALUES ('%u', '%u', '%s', '%u', '%u', '%u', '%s', '%u', '%u', '%s', '%s', '%u', '%u', '%u', '%u', '%s', '%u', '%s', '%u')", $this->table, (int) ($newstoryid), (int) ($this->uid), $title, (int) ($created), (int) ($published), (int) ($expired), $this->hostname, (int) ($this->nohtml), (int) ($this->nosmiley), $hometext, $bodytext, 0, (int) ($this->topicid), (int) ($this->ihome), (int) ($this->notifypub), $this->type, (int) ($this->topicdisplay), $this->topicalign, (int) ($this->comments));
		}
		else
		{
			if($this->approved)
			{
				$sql = sprintf("UPDATE %s SET title = '%s', published = '%u', expired = '%u', nohtml = '%u', nosmiley = '%u', hometext = '%s', bodytext = '%s', topicid = '%u', ihome = '%u', topicdisplay = '%u', topicalign = '%s', comments = '%u' WHERE storyid = '%u'", $this->table, $title, (int) ($this->published), (int) ($expired), (int) ($this->nohtml), (int) ($this->nosmiley), $hometext, $bodytext, (int) ($this->topicid), (int) ($this->ihome), (int) ($this->topicdisplay), $this->topicalign, (int) ($this->comments), (int) ($this->storyid));
			}
			else
			{
				$sql = sprintf("UPDATE %s SET title = '%s', expired = '%u', nohtml = '%u', nosmiley = '%u', hometext = '%s', bodytext = '%s', topicid = '%u', ihome = '%u', topicdisplay = '%u', topicalign = '%s', comments = '%u' WHERE storyid = '%u'", $this->table, $title, (int) ($expired), (int) ($this->nohtml), (int) ($this->nosmiley), $hometext, $bodytext, (int) ($this->topicid), (int) ($this->ihome), (int) ($this->topicdisplay), (int) ($this->topicalign), (int) ($this->comments), (int) ($this->storyid));
			}
			$newstoryid = $this->storyid;
		}
		if(!$result = $this->db->query($sql))
		{
			return false;
		}
		if(empty($newstoryid))
		{
			$newstoryid = $this->db->getInsertId();
			$this->storyid = $newstoryid;
		}
		return $newstoryid;
	}

	/**
	 * Gets story by ID
	 *
	 * @param   int      $storyid
	 **/
	function getStory($storyid)
	{
		$storyid = (int) ($storyid);
		$sql = "SELECT * FROM ".$this->table." WHERE storyid='".$storyid."'";
		$array = $this->db->fetchArray($this->db->query($sql));
		$this->makeStory($array);
	}

	/**
	 * Makes the story
	 *
	 * @param   array      $array
	 **/
	function makeStory($array)
	{
		foreach($array as $key=>$value)
		{
			$this->$key = $value;
		}
	}

	/**
	 * Deletes the story by ID
	 *
	 * @return   bool
	 **/
	function delete()
	{
		$sql = sprintf("DELETE FROM %s WHERE storyid = '%u'", $this->table, (int) ($this->storyid));
		if(!$result = $this->db->query($sql))
		{
			return false;
		}
		return true;
	}

	/**
	 * Updates the counter
	 *
	 * @param   bool
	 **/
	function updateCounter()
	{
		$sql = sprintf("UPDATE %s SET counter = counter+1 WHERE storyid = '%u'", $this->table, (int) ($this->storyid));
		if(!$result = $this->db->queryF($sql))
		{
			return false;
		}
		return true;
	}

	/**
	 * Updates the number of comments
	 *
	 * @param   bool
	 **/
	function updateComments($total)
	{
		$sql = sprintf("UPDATE %s SET comments = '%u' WHERE storyid = '%u'", $this->table, (int) ($total), (int) ($this->storyid));
		if(!$result = $this->db->queryF($sql))
		{
			return false;
		}
		return true;
	}

	/**
	 * Returns the current topicid
	 *
	 * @return   int
	 **/
	function topicid()
	{
		return $this->topicid;
	}

	/**
	 * Returns the current topic (@link XoopsTopic) object
	 *
	 * @param   object
	 **/
	function topic()
	{
		return new XoopsTopic($this->topicstable, $this->topicid);
	}

	function uid()
	{
		return $this->uid;
	}

	/**
	 * Returns the current username from (@link icms_member_user_Object)
	 *
	 * @return   string
	 **/
	function uname()
	{
		return icms_member_user_Object::getUnameFromId($this->uid);
	}

	/**
	 * Returns the title in a certain format
	 *
	 * @param    string    $format
	 * @return   string    $title
	 **/
	function title($format='Show')
	{
		$myts = icms_core_Textsanitizer::getInstance();
		$smiley = 1;
		if($this->nosmiley())
		{
			$smiley = 0;
		}
		switch($format)
		{
			case 'Show':
				$title = $myts->htmlSpecialChars($this->title, $smiley);
				break;
			case 'Edit':
				$title = $myts->htmlSpecialChars($this->title);
				break;
			case 'Preview':
				$title = $myts->makeTboxData4Preview($this->title, $smiley);
				break;
			case 'InForm':
				$title = $myts->makeTboxData4PreviewInForm($this->title);
				break;
		}
		return $title;
	}

	/**
	 * Returns the hometext in a certain format
	 *
	 * @param    string    $format
	 * @return   string    $hometext
	 **/
	function hometext($format='Show')
	{
		$myts = icms_core_Textsanitizer::getInstance();
		$html = 1;
		$smiley = 1;
		$xcodes = 1;
		if($this->nohtml())
		{
			$html = 0;
		}
		if($this->nosmiley())
		{
			$smiley = 0;
		}
		switch($format)
		{
			case 'Show':
				$hometext = $myts->displayTarea($this->hometext, $html, $smiley, $xcodes);
				break;
			case 'Edit':
				$hometext = $myts->displayTarea($this->hometext);
				break;
			case 'Preview':
				$hometext = $myts->previewTarea($this->hometext, $html, $smiley, $xcodes);
				break;
			case 'InForm':
				$hometext = $myts->makeTareaData4PreviewInForm($this->hometext);
				break;
		}
		return $hometext;
	}

	/**
	 * Returns the bodytext in a certain format
	 *
	 * @param    string    $format
	 * @return   string    $bodytext
	 **/
	function bodytext($format='Show')
	{
		$myts =& icms_core_Textsanitizer::getInstance();
		$html = 1;
		$smiley = 1;
		$xcodes = 1;
		if($this->nohtml())
		{
			$html = 0;
		}
		if($this->nosmiley())
		{
			$smiley = 0;
		}
		switch($format)
		{
			case 'Show':
				$bodytext = $myts->displayTarea($this->bodytext, $html, $smiley, $xcodes);
				break;
			case 'Edit':
				$bodytext = $myts-previewTarea($this->bodytext);
				break;
			case 'Preview':
				$bodytext = $myts->previewTarea($this->bodytext, $html, $smiley, $xcodes);
				break;
			case 'InForm':
				$bodytext = $myts->makeTareaData4PreviewInForm($this->bodytext);
				break;
		}
		return $bodytext;
	}

	/**
	 * Returns the counter
	 *
	 * @return   int
	 **/
	function counter()
	{
		return $this->counter;
	}

	/**
	 * Returns date created
	 *
	 * @return   int
	 **/
	function created()
	{
		return $this->created;
	}

	/**
	 * Returns date published
	 *
	 * @return   int
	 **/
	function published()
	{
		return $this->published;
	}

	/**
	 * Returns date expired
	 *
	 * @return   int
	 **/
	function expired()
	{
		return $this->expired;
	}

	/**
	 * Returns hostname
	 *
	 * @return   string
	 **/
	function hostname()
	{
		return $this->hostname;
	}

	/**
	 * Returns storyid
	 *
	 * @return   int
	 **/
	function storyid()
	{
		return $this->storyid;
	}

	/**
	 * Returns value for nohtml
	 *
	 * @return   int
	 **/
	function nohtml()
	{
		return $this->nohtml;
	}

	/**
	 * Returns value for nosmiley
	 *
	 * @return   int
	 **/
	function nosmiley()
	{
		return $this->nosmiley;
	}

	/**
	 * Returns value for notifypub
	 *
	 * @return   int
	 **/
	function notifypub()
	{
		return $this->notifypub;
	}

	/**
	 * Returns the type
	 *
	 * @return   string
	 **/
	function type()
	{
		return $this->type;
	}

	/**
	 * Returns value for ihome
	 *
	 * @return   string
	 **/
	function ihome()
	{
		return $this->ihome;
	}

	/**
	 * Returns value for topicdisplay
	 *
	 * @return   string
	 **/
	function topicdisplay()
	{
		return $this->topicdisplay;
	}

	/**
	 * Returns value for topicalign
	 *
	 * @param    bool      $astext   Align the topic as text
	 * @return   string
	 **/
	function topicalign($astext=true)
	{
		if($astext)
		{
			if($this->topicalign == 'R')
			{
				$ret = 'right';
			}
			else
			{
				$ret = 'left';
			}
			return $ret;
		}
		return $this->topicalign;
	}

	/**
	 * Returns the number of comments
	 *
	 * @return   int
	 **/
	function comments()
	{
		return $this->comments;
	}
}
?>