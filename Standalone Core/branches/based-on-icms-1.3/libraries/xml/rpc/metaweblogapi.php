<?php
/**
 * XML Parser Meta Weblog Api
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XML
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: metaweblogapi.php 19118 2010-03-27 17:46:23Z skenow $
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}
require_once ICMS_LIBRARIES_PATH . '/xml/rpc/xmlrpcapi.php';

class MetaWeblogApi extends XoopsXmlRpcApi
{
	function MetaWeblogApi(&$params, &$response, &$module)
	{
		$this->XoopsXmlRpcApi($params, $response, $module);
		$this->_setXoopsTagMap('storyid', 'postid');
		$this->_setXoopsTagMap('published', 'dateCreated');
		$this->_setXoopsTagMap('uid', 'userid');
		//$this->_setXoopsTagMap('hometext', 'description');
	}

	function newPost()
	{
		if (!$this->_checkUser($this->params[1], $this->params[2])) {

			$this->response->add(new XoopsXmlRpcFault(104));
		} else {
			if (!$fields =& $this->_getPostFields(null, $this->params[0])) {
				$this->response->add(new XoopsXmlRpcFault(106));
			} else {
				$missing = array();
				$post = array();
				foreach ($fields as $tag => $detail) {
					$maptag = $this->_getXoopsTagMap($tag);
					if (!isset($this->params[3][$maptag])) {
						$data = $this->_getTagCdata($this->params[3]['description'], $maptag, true);
						if (trim($data) == ''){
							if ($detail['required']) {
								$missing[] = $maptag;
							}
						} else {
							$post[$tag] = $data;
						}
					} else {
						$post[$tag] = $this->params[3][$maptag];
					}
				}
				if (count($missing) > 0) {
					$msg = '';
					foreach ($missing as $m) {
						$msg .= '<'.$m.'> ';echo $m;
					}
					$this->response->add(new XoopsXmlRpcFault(109, $msg));
				} else {
					$newparams = array();
					$newparams[0] = $this->params[0];
					$newparams[1] = $this->params[1];
					$newparams[2] = $this->params[2];
					foreach ($post as $key => $value) {
						$newparams[3][$key] =& $value;
						unset($value);
					}
					$newparams[3]['xoops_text'] = $this->params[3]['description'];
					if (isset($this->params[3]['categories']) && is_array($this->params[3]['categories'])) {
						foreach ($this->params[3]['categories'] as $k => $v) {
							$newparams[3]['categories'][$k] = $v;
						}
					}
					$newparams[4] = $this->params[4];
					$xoopsapi =& $this->_getXoopsApi($newparams);
					$xoopsapi->_setUser($this->user, $this->isadmin);
					$xoopsapi->newPost();
				}
			}
		}
	}

	function editPost()
	{
		if (!$this->_checkUser($this->params[1], $this->params[2])) {
			$this->response->add(new XoopsXmlRpcFault(104));
		} else {
			if (!$fields =& $this->_getPostFields($this->params[0])) {
			} else {
				$missing = array();
				$post = array();
				foreach ($fields as $tag => $detail) {
					$maptag = $this->_getXoopsTagMap($tag);
					if (!isset($this->params[3][$maptag])) {
						$data = $this->_getTagCdata($this->params[3]['description'], $maptag, true);
						if (trim($data) == ''){
							if ($detail['required']) {
								$missing[] = $tag;
							}
						} else {
							$post[$tag] = $data;
						}
					} else {
						$post[$tag] =& $this->params[3][$maptag];
					}
				}
				if (count($missing) > 0) {
					$msg = '';
					foreach ($missing as $m) {
						$msg .= '<'.$m.'> ';
					}
					$this->response->add(new XoopsXmlRpcFault(109, $msg));
				} else {
					$newparams = array();
					$newparams[0] = $this->params[0];
					$newparams[1] = $this->params[1];
					$newparams[2] = $this->params[2];
					foreach ($post as $key => $value) {
						$newparams[3][$key] =& $value;
						unset($value);
					}
					if (isset($this->params[3]['categories']) && is_array($this->params[3]['categories'])) {
						foreach ($this->params[3]['categories'] as $k => $v) {
							$newparams[3]['categories'][$k] = $v;
						}
					}
					$newparams[3]['xoops_text'] = $this->params[3]['description'];
					$newparams[4] = $this->params[4];
					$xoopsapi =& $this->_getXoopsApi($newparams);
					$xoopsapi->_setUser($this->user, $this->isadmin);
					$xoopsapi->editPost();
				}
			}
		}
	}

	function getPost()
	{
		if (!$this->_checkUser($this->params[1], $this->params[2])) {
			$this->response->add(new XoopsXmlRpcFault(104));
		} else {
			$xoopsapi =& $this->_getXoopsApi($this->params);
			$xoopsapi->_setUser($this->user, $this->isadmin);
			$ret =& $xoopsapi->getPost(false);
			if (is_array($ret)) {
				$struct = new XoopsXmlRpcStruct();
				$content = '';
				foreach ($ret as $key => $value) {
					$maptag = $this->_getXoopsTagMap($key);
					switch($maptag) {
						case 'userid':
							$struct->add('userid', new XoopsXmlRpcString($value));
							break;
						case 'dateCreated':
							$struct->add('dateCreated', new XoopsXmlRpcDatetime($value));
							break;
						case 'postid':
							$struct->add('postid', new XoopsXmlRpcString($value));
							$struct->add('link', new XoopsXmlRpcString(ICMS_URL.'/modules/xoopssections/item.php?item='.$value));
							$struct->add('permaLink', new XoopsXmlRpcString(ICMS_URL.'/modules/xoopssections/item.php?item='.$value));
							break;
						case 'title':
							$struct->add('title', new XoopsXmlRpcString($value));
							break;
						default :
							$content .= '<'.$key.'>'.trim($value).'</'.$key.'>';
							break;
					}
				}
				$struct->add('description', new XoopsXmlRpcString($content));
				$this->response->add($struct);
			} else {
				$this->response->add(new XoopsXmlRpcFault(106));
			}
		}
	}

	function getRecentPosts()
	{
		if (!$this->_checkUser($this->params[1], $this->params[2])) {
			$this->response->add(new XoopsXmlRpcFault(104));
		} else {
			$xoopsapi =& $this->_getXoopsApi($this->params);
			$xoopsapi->_setUser($this->user, $this->isadmin);
			$ret =& $xoopsapi->getRecentPosts(false);
			if (is_array($ret)) {
				$arr = new XoopsXmlRpcArray();
				$count = count($ret);
				if ($count == 0) {
					$this->response->add(new XoopsXmlRpcFault(106, 'Found 0 Entries'));
				} else {
					for ($i = 0; $i < $count; $i++) {
						$struct = new XoopsXmlRpcStruct();
						$content = '';
						foreach($ret[$i] as $key => $value) {
							$maptag = $this->_getXoopsTagMap($key);
							switch($maptag) {
								case 'userid':
									$struct->add('userid', new XoopsXmlRpcString($value));
									break;
								case 'dateCreated':
									$struct->add('dateCreated', new XoopsXmlRpcDatetime($value));
									break;
								case 'postid':
									$struct->add('postid', new XoopsXmlRpcString($value));
									$struct->add('link', new XoopsXmlRpcString(ICMS_URL.'/modules/news/article.php?item_id='.$value));
									$struct->add('permaLink', new XoopsXmlRpcString(ICMS_URL.'/modules/news/article.php?item_id='.$value));
									break;
								case 'title':
									$struct->add('title', new XoopsXmlRpcString($value));
									break;
								default :
									$content .= '<'.$key.'>'.trim($value).'</'.$key.'>';
									break;
							}
						}
						$struct->add('description', new XoopsXmlRpcString($content));
						$arr->add($struct);
						unset($struct);
					}
					$this->response->add($arr);
				}
			} else {
				$this->response->add(new XoopsXmlRpcFault(106));
			}
		}
	}

	function getCategories()
	{
		if (!$this->_checkUser($this->params[1], $this->params[2])) {
			$this->response->add(new XoopsXmlRpcFault(104));
		} else {
			$xoopsapi =& $this->_getXoopsApi($this->params);
			$xoopsapi->_setUser($this->user, $this->isadmin);
			$ret =& $xoopsapi->getCategories(false);
			if (is_array($ret)) {
				$arr = new XoopsXmlRpcArray();
				foreach ($ret as $id => $detail) {
					$struct = new XoopsXmlRpcStruct();
					$struct->add('description', new XoopsXmlRpcString($detail));
					$struct->add('htmlUrl', new XoopsXmlRpcString(ICMS_URL.'/modules/news/index.php?storytopic='.$id));
					$struct->add('rssUrl', new XoopsXmlRpcString(''));
					$catstruct = new XoopsXmlRpcStruct();
					$catstruct->add($detail['title'], $struct);
					$arr->add($catstruct);
					unset($struct);
					unset($catstruct);
				}
				$this->response->add($arr);
			} else {
				$this->response->add(new XoopsXmlRpcFault(106));
			}
		}
	}
}
?>