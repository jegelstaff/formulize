<?php
/**
 * The Renderer functions of the Error logger
 *
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Core
 * @subpackage	Logger
 * @author	modified by UnderDog <underdog@impresscms.org>
 * @version	$Id: Logger_render.php 22546 2011-09-03 12:47:59Z phoenyx $
 */

defined('ICMS_ROOT_PATH') or die();

$ret = '';

if ($mode == 'popup') {
	$dump = $this->dump('');
	$content = '
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=' . _CHARSET . '" />
	<meta http-equiv="content-language" content="' . _LANGCODE . '" />
	<title>' . $icmsConfig['sitename'] . '</title>
	<link rel="stylesheet" type="text/css" media="all" href="' . xoops_getcss($icmsConfig['theme_set']) . '" />
</head>
<body>' . $dump . '
	<div style="text-align:center;">
		<input class="formButton" value="' . _CLOSE . '" type="button" onclick="javascript:window.close();" />
	</div>
</body>
</html>';
	$ret .= '
<script type="text/javascript">
	debug_window = openWithSelfMain("about:blank", "popup", 680, 450, true);
	debug_window.document.clear();
';
	$lines = preg_split("/(\r\n|\r|\n)( *)/", $content);
	foreach ($lines as $line) {
		$ret .= "\n" . 'debug_window.document.writeln("' . str_replace(array('"', '</'), array('\"', '<\/'), $line) . '");';
	}
	$ret .= '
	debug_window.focus();
	debug_window.document.close();
</script>';
}

if (empty( $mode )) {
	$ret .= "\n<div id=\"xo-logger-output\">\n<div id='xo-logger-tabs'>\n";
	$ret .= "<a href='javascript:xoSetLoggerView(\"none\")'>" . _NONE . "</a> | \n";
	$ret .= "<a href='javascript:xoSetLoggerView(\"\")'>" . _ALL . "</a> | \n";
	$count = count( $this->errors );
	$ret .= "<a href='javascript:xoSetLoggerView(\"errors\")'>" . _ERRORS . " (" . icms_conv_nr2local($count) . ")</a>\n";
	$count = count( $this->queries );
	$ret .= "<a href='javascript:xoSetLoggerView(\"queries\")'>" . _QUERIES . " (" . icms_conv_nr2local($count) . ")</a>\n";
	$count = count( $this->blocks );
	$ret .= "<a href='javascript:xoSetLoggerView(\"blocks\")'>" . _BLOCKS . " (" . icms_conv_nr2local($count) . ")</a>\n";
	$count = count( $this->extra );
	$ret .= "<a href='javascript:xoSetLoggerView(\"extra\")'>" . _EXTRA . " (" . icms_conv_nr2local($count) . ")</a>\n";
	$count = count( $this->logstart );
	$ret .= "<a href='javascript:xoSetLoggerView(\"timers\")'>" . _TIMERS . " (" . icms_conv_nr2local($count) . ")</a>\n";
	$count = count($this->deprecated);
	$ret .= "<a href='javascript:xoSetLoggerView(\"deprecated\")'>" . _CORE_DEPRECATED . " (" . icms_conv_nr2local($count) . ")</a>\n";
	$ret .= "</div>\n";
}

if (empty($mode) || $mode == 'errors') {
	$types = array(
		E_USER_NOTICE => _NOTICE,
		E_USER_WARNING => _WARNING,
		E_USER_ERROR => _ERROR,
		E_NOTICE => _NOTICE,
		E_WARNING => _WARNING,
		E_STRICT => _STRICT,
	);
	$class = 'even';
	$ret .= '<table id="xo-logger-errors" class="outer"><tr><th>' . _ERRORS . '</th></tr>';
	foreach ( $this->errors as $error) {
		$ret .= "\n<tr><td class='$class'>";
		$ret .= isset( $types[ $error['errno'] ] ) ? $types[ $error['errno'] ] : 'Unknown';
		$ret .= sprintf( ": %s in file %s line %s<br />\n", $error['errstr'], $error['errfile'], $error['errline'] );
		$ret .= "</td></tr>";
		$class = ($class == 'odd') ? 'even' : 'odd';
	}
	$ret .= "\n</table>\n";
}

if (empty($mode) || $mode == 'queries') {	
	$class = 'even';
	$ret .= '<table id="xo-logger-queries" class="outer"><tr><th>' . _QUERIES . '</th></tr>';
	$sqlmessages ='';
	foreach ($this->queries as $q) {
		if (isset($q['error'])) {
			$sqlmessages .= '<tr class="' . $class . '"><td><span style="color:#ff0000;">' . htmlentities($q['sql']) . '<br /><strong>' . _ERR_NR . '</strong> ' . $q['errno'] . '<br /><strong>' . _ERR_MSG . '</strong> ' . $q['error'] . '</span></td></tr>';
		} else {
			$sqlmessages .= '<tr class="' . $class . '"><td>' . htmlentities($q['sql']) . '</td></tr>';
		}
		$class = ($class == 'odd') ? 'even' : 'odd';
	}
	$ret .= str_replace(XOOPS_DB_PREFIX . '_', '', $sqlmessages);
	$ret .= '<tr class="foot"><td>' . _TOTAL . ' <span style="color:#ff0000;">' . icms_conv_nr2local(count($this->queries)) . '</span> ' . _QUERIES . '</td></tr></table>';
}

if (empty($mode) || $mode == 'blocks') {
	$class = 'even';
	$ret .= '<table id="xo-logger-blocks" class="outer"><tr><th colspan="2">' . _BLOCKS . '</th></tr>';
	foreach ($this->blocks as $b) {
		if ($b['cached']) {
			$ret .= '<tr><td class="' . $class . '"><strong>' . htmlspecialchars($b['name']) . ':</strong> ' . _CACHED . ' : ' . icms_conv_nr2local(sprintf(_REGENERATES, (int) ($b['cachetime']))) . '</td></tr>';
		} else {
			$ret .= '<tr><td class="' . $class . '"><strong>' . htmlspecialchars($b['name']) . ':</strong> ' . _NOCACHE . '</td></tr>';
		}
		$class = ($class == 'odd') ? 'even' : 'odd';
	}
	$ret .= '<tr class="foot"><td>' . _TOTAL . ' <span style="color:#ff0000;">' . icms_conv_nr2local(count($this->blocks)) . '</span> ' . _BLOCK . '</td></tr></table>';
}

if (empty($mode) || $mode == 'extra') {
	$class = 'even';
	$ret .= '<table id="xo-logger-extra" class="outer"><tr><th colspan="2">' . _EXTRA . '</th></tr>';
	foreach ($this->extra as $ex) {
		$ret .= '<tr><td class="' . $class . '"><strong>' . htmlspecialchars($ex['name']) . ':</strong> ' . htmlspecialchars($ex['msg']) . '</td></tr>';
		$class = ($class == 'odd') ? 'even' : 'odd';
	}
	$ret .= '</table>';
}

if (empty($mode) || $mode == 'timers') {
	$class = 'even';
	$ret .= '<table id="xo-logger-timers" class="outer"><tr><th colspan="2">' . _TIMERS . '</th></tr>';
	foreach ( $this->logstart as $k => $v) {
		$ret .= '<tr><td class="' . $class.'"><strong>' . htmlspecialchars($k) . '</strong> ' . sprintf(_TOOKXLONG, '<span style="color:#ff0000;">' . icms_conv_nr2local(sprintf( "%.03f", $this->dumpTime($k) )) . '</span>') . '</td></tr>';
		$class = ($class == 'odd') ? 'even' : 'odd';
	}
	$ret .= '</table>';
}

if (empty($mode) || $mode == 'deprecated') {
	$class = 'even';
	$ret .= '<table id="xo-logger-deprecated" class="outer"><tr><th colspan="2">' . _CORE_DEPRECATED . '</th></tr>';
	foreach ( $this->deprecated as $dep) {
		$ret .= '<tr><td class="' . $class.'">' . $dep . '</td></tr>';
		$class = ($class == 'odd') ? 'even' : 'odd';
	}
	$ret .= '</table>';
}

if (empty( $mode )) {
	$ret .= <<<EOT
</div>
<script type="text/javascript">
	function xoLogCreateCookie(name,value,days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+value+expires+"; path=/";
	}
	function xoLogReadCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for (var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
		}
		return null;
	}
	function xoLogEraseCookie(name) {
		createCookie(name,"",-1);
	}
	function xoSetLoggerView( name) {
		var log = document.getElementById( "xo-logger-output" );
		if (!log ) return;
		var i, elt;
		for ( i=0; i!=log.childNodes.length; i++) {
			elt = log.childNodes[i];
			if (elt.tagName && elt.tagName.toLowerCase() != 'script' && elt.id != "xo-logger-tabs") {
				elt.style.display = ( !name || elt.id == "xo-logger-" + name ) ? "block" : "none";
			}
		}
		xoLogCreateCookie( 'XOLOGGERVIEW', name, 1 );
	}
	xoSetLoggerView( xoLogReadCookie( 'XOLOGGERVIEW' ) );
</script>

EOT;
}

