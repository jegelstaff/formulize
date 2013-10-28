<?php
/**
 * XML Parser Sax Parser
 *
 * Longer description about this page
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	LICENSE.txt
 * @package	XML
 * @since	XOOPS
 * @author	http://www.xoops.org The XOOPS Project
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: xmlrpcparser.php 19118 2010-03-27 17:46:23Z skenow $
 */

if (!defined('ICMS_ROOT_PATH')) {
	die("ImpressCMS root path not defined");
}
require_once ICMS_LIBRARIES_PATH . '/xml/saxparser.php';
require_once ICMS_LIBRARIES_PATH . '/xml/xmltaghandler.php';

/**
 * Class RSS Parser
 *
 * This class offers methods to parse RSS Files
 *
 * @link      http://www.xoops.org/ Latest release of this class
 * @package   XOOPS
 * @copyright Copyright (c) 2001 xoops.org. All rights reserved.
 * @author    Kazumi Ono <onokazu@xoops.org>
 * @version   1.6 ($Date: 2007-11-01 12:57:08 -0400 (jeu., 01 nov. 2007) $) $Revision: 1136 $
 * @access    public
 */

class XoopsXmlRpcParser extends SaxParser
{

	/**
	 *
	 *
	 *
	 *
	 * @access private
	 * @var    array
	 */
	var $_param;

	/**
	 *
	 *
	 *
	 *
	 * @access private
	 * @var    string
	 */
	var $_methodName;

	/**
	 *
	 *
	 *
	 *
	 * @access private
	 * @var    array
	 */
	var $_tempName;

	/**
	 *
	 *
	 *
	 *
	 * @access private
	 * @var    array
	 */
	var $_tempValue;

	/**
	 *
	 *
	 *
	 *
	 * @access private
	 * @var    array
	 */
	var $_tempMember;

	/**
	 *
	 *
	 *
	 *
	 * @access private
	 * @var    array
	 */
	var $_tempStruct;

	/**
	 *
	 *
	 *
	 *
	 * @access private
	 * @var    array
	 */
	var $_tempArray;

	/**
	 *
	 *
	 *
	 *
	 * @access private
	 * @var    array
	 */
	var $_workingLevel = array();

	/**
	 * Constructor of the class
	 *
	 *
	 *
	 *
	 * @access
	 * @author
	 * @see
	 */
	function XoopsXmlRpcParser(&$input)
	{
		$this->SaxParser($input);
		$this->addTagHandler(new RpcMethodNameHandler());
		$this->addTagHandler(new RpcIntHandler());
		$this->addTagHandler(new RpcDoubleHandler());
		$this->addTagHandler(new RpcBooleanHandler());
		$this->addTagHandler(new RpcStringHandler());
		$this->addTagHandler(new RpcDateTimeHandler());
		$this->addTagHandler(new RpcBase64Handler());
		$this->addTagHandler(new RpcNameHandler());
		$this->addTagHandler(new RpcValueHandler());
		$this->addTagHandler(new RpcMemberHandler());
		$this->addTagHandler(new RpcStructHandler());
		$this->addTagHandler(new RpcArrayHandler());
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function setTempName($name)
	{
		$this->_tempName[$this->getWorkingLevel()] = $name;
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getTempName()
	{
		return $this->_tempName[$this->getWorkingLevel()];
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function setTempValue($value)
	{
		if (is_array($value)) {
			settype($this->_tempValue, 'array');
			foreach ($value as $k => $v) {
				$this->_tempValue[$k] = $v;
			}
		} elseif (is_string($value)) {
			if (isset($this->_tempValue)) {
				if (is_string($this->_tempValue)) {
					$this->_tempValue .= $value;
				}
			} else {
				$this->_tempValue = $value;
			}
		} else {
			$this->_tempValue = $value;
		}
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getTempValue()
	{
		return $this->_tempValue;
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function resetTempValue()
	{
		unset($this->_tempValue);
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function setTempMember($name, $value)
	{
		$this->_tempMember[$this->getWorkingLevel()][$name] = $value;
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getTempMember()
	{
		return $this->_tempMember[$this->getWorkingLevel()];
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function resetTempMember()
	{
		$this->_tempMember[$this->getCurrentLevel()] = array();
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function setWorkingLevel()
	{
		array_push($this->_workingLevel, $this->getCurrentLevel());
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getWorkingLevel()
	{
		return $this->_workingLevel[count($this->_workingLevel) - 1];
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function releaseWorkingLevel()
	{
		array_pop($this->_workingLevel);
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function setTempStruct($member)
	{
		$key = key($member);
		$this->_tempStruct[$this->getWorkingLevel()][$key] = $member[$key];
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getTempStruct()
	{
		return $this->_tempStruct[$this->getWorkingLevel()];
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function resetTempStruct()
	{
		$this->_tempStruct[$this->getCurrentLevel()] = array();
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function setTempArray($value)
	{
		$this->_tempArray[$this->getWorkingLevel()][] = $value;
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getTempArray()
	{
		return $this->_tempArray[$this->getWorkingLevel()];
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function resetTempArray()
	{
		$this->_tempArray[$this->getCurrentLevel()] = array();
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function setMethodName($methodName)
	{
		$this->_methodName = $methodName;
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getMethodName()
	{
		return $this->_methodName;
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function setParam($value)
	{
		$this->_param[] = $value;
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function &getParam()
	{
		return $this->_param;
	}
}

class RpcMethodNameHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'methodName';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleCharacterData(&$parser, &$data)
	{
		$parser->setMethodName($data);
	}
}

class RpcIntHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return array('int', 'i4');
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleCharacterData(&$parser, &$data)
	{
		$parser->setTempValue( (int) ($data));
	}
}

class RpcDoubleHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'double';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleCharacterData(&$parser, &$data)
	{
		$data = (float)$data;
		$parser->setTempValue($data);
	}
}

class RpcBooleanHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'boolean';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleCharacterData(&$parser, &$data)
	{
		$data = (boolean)$data;
		$parser->setTempValue($data);
	}
}

class RpcStringHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'string';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleCharacterData(&$parser, &$data)
	{
		$parser->setTempValue(strval($data));
	}
}

class RpcDateTimeHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'dateTime.iso8601';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleCharacterData(&$parser, &$data)
	{
		$matches = array();
		if (!preg_match("/^([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})$/", $data, $matches)) {
			$parser->setTempValue(time());
		} else {
			$parser->setTempValue(gmmktime($matches[4], $matches[5], $matches[6], $matches[2], $matches[3], $matches[1]));
		}
	}
}

class RpcBase64Handler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'base64';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleCharacterData(&$parser, &$data)
	{
		$parser->setTempValue(base64_decode($data));
	}
}

class RpcNameHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'name';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleCharacterData(&$parser, &$data)
	{
		switch ($parser->getParentTag()) {
			case 'member':
				$parser->setTempName($data);
				break;
			default:
				break;
		}
	}
}

class RpcValueHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'value';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleCharacterData(&$parser, &$data)
	{
		switch ($parser->getParentTag()) {
			case 'member':
				$parser->setTempValue($data);
				break;
			case 'data':
			case 'array':
				$parser->setTempValue($data);
				break;
			default:
				break;
		}
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleBeginElement(&$parser, &$attributes)
	{
		//$parser->resetTempValue();
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleEndElement(&$parser)
	{
		switch ($parser->getCurrentTag()) {
			case 'member':
				$parser->setTempMember($parser->getTempName(), $parser->getTempValue());
				break;
			case 'array':
			case 'data':
				$parser->setTempArray($parser->getTempValue());
				break;
			default:
				$parser->setParam($parser->getTempValue());
				break;
		}
		$parser->resetTempValue();
	}
}

class RpcMemberHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'member';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleBeginElement(&$parser, &$attributes)
	{
		$parser->setWorkingLevel();
		$parser->resetTempMember();
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleEndElement(&$parser)
	{
		$member =& $parser->getTempMember();
		$parser->releaseWorkingLevel();
		$parser->setTempStruct($member);
	}
}

class RpcArrayHandler extends XmlTagHandler
{

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'array';
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleBeginElement(&$parser, &$attributes)
	{
		$parser->setWorkingLevel();
		$parser->resetTempArray();
	}

	/**
	 * This Method starts the parsing of the specified RDF File. The File can be a local or a remote File.
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleEndElement(&$parser)
	{
		$parser->setTempValue($parser->getTempArray());
		$parser->releaseWorkingLevel();
	}
}

class RpcStructHandler extends XmlTagHandler
{

	/**
	 *
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function getName()
	{
		return 'struct';
	}

	/**
	 *
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleBeginElement(&$parser, &$attributes)
	{
		$parser->setWorkingLevel();
		$parser->resetTempStruct();
	}

	/**
	 *
	 *
	 * @access
	 * @author
	 * @param
	 * @return
	 * @see
	 */
	function handleEndElement(&$parser)
	{
		$parser->setTempValue($parser->getTempStruct());
		$parser->releaseWorkingLevel();
	}
}
?>