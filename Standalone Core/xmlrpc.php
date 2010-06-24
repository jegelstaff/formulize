<?php
/**
* @copyright    http://www.xoops.org/ The XOOPS Project
* @copyright    XOOPS_copyrights.txt
* @copyright    http://www.impresscms.org/ The ImpressCMS Project
* @license      http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package      core
* @since        XOOPS
* @author       http://www.xoops.org The XOOPS Project
* @author       Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
* @version      $Id: xmlrpc.php 9345 2009-09-06 15:53:41Z m0nty $
*/
define('XOOPS_XMLRPC', 1);
include './mainfile.php';
error_reporting(0);
include_once ICMS_ROOT_PATH.'/class/xml/rpc/xmlrpctag.php';
include_once ICMS_ROOT_PATH.'/class/xml/rpc/xmlrpcparser.php';

global $xoopsErrorHandler;
$xoopsErrorHandler->activate(false);

$response = new XoopsXmlRpcResponse();
$parser = new XoopsXmlRpcParser(rawurlencode($GLOBALS['HTTP_RAW_POST_DATA']));
if(!$parser->parse())
{
    $response->add(new XoopsXmlRpcFault(102));
}
else
{
    $module_handler = xoops_gethandler('module');
    $module =& $module_handler->getByDirname('news');
    if(!is_object($module))
    {
        $response->add(new XoopsXmlRpcFault(110));
    }
    else
    {
        $methods = explode('.', $parser->getMethodName());
        switch($methods[0])
        {
            case 'blogger':
                include_once ICMS_ROOT_PATH.'/class/xml/rpc/bloggerapi.php';
                $rpc_api = new BloggerApi($parser->getParam(), $response, $module);
            break;
            case 'metaWeblog':
                include_once ICMS_ROOT_PATH.'/class/xml/rpc/metaweblogapi.php';
                $rpc_api = new MetaWeblogApi($parser->getParam(), $response, $module);
            break;
            case 'mt':
                include_once ICMS_ROOT_PATH.'/class/xml/rpc/movabletypeapi.php';
                $rpc_api = new MovableTypeApi($parser->getParam(), $response, $module);
            break;
            case 'xoops':
                default:
                    include_once ICMS_ROOT_PATH.'/class/xml/rpc/xoopsapi.php';
                    $rpc_api = new XoopsApi($parser->getParam(), $response, $module);
            break;
        }
        $method = $methods[1];
        if(!method_exists($rpc_api, $method))
        {
            $response->add(new XoopsXmlRpcFault(107));
        }
        else
        {
            $rpc_api->$method();
        }
    }
}
$payload =& $response->render();
//$fp = fopen(ICMS_CACHE_PATH.'/xmllog.txt', 'w');
//fwrite($fp, $payload);
//fclose($fp);
header('Server: XOOPS XML-RPC Server');
header('Content-type: text/xml');
header('Content-Length: '.strlen($payload));
echo $payload;
?>