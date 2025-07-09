<?php
error_reporting(0);
ini_set('display_errors', 0);
$rootPath = substr(realpath(dirname(__FILE__)), 0, -4);
$mcpServerName = str_replace('.', '_', $_SERVER['HTTP_HOST']);
$fileDisplayName = $mcpServerName."_example_config.json";
$filePath = $rootPath.'/modules/formulize/temp/'.$fileDisplayName;
$protocol = (443 == $_SERVER["SERVER_PORT"] OR (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) AND $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? "https://" : "http://";
file_put_contents($filePath, '{
  "mcpServers": {
    "'.$mcpServerName.'": {
      "command": "npx",
      "args": [
        "-y",
        "formulize-mcp"
      ],
      "env": {
        "FORMULIZE_URL": "'.$protocol.$_SERVER['HTTP_HOST'].'",
        "FORMULIZE_API_KEY": "< your api key from: '.$protocol.$_SERVER['HTTP_HOST'].'/modules/formulize/admin/ui.php?page=managekeys >",
        "FORMULIZE_SERVER_NAME": "'.$mcpServerName.'"
      }
    }
  }
}');
header('Content-Description: File Transfer');
header('Content-Disposition: attachment; filename="'.$fileDisplayName.'"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . filesize($filePath));
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
readfile($filePath);
unlink($filePath);
exit();
