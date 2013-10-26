<?php
/**
 * Installer database configuration page
 *
 * See the enclosed file license.txt for licensing information.
 * If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
 *
 * @copyright	http://www.xoops.org/ The XOOPS Project
 * @copyright	XOOPS_copyrights.txt
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		installer
 * @since		XOOPS
 * @author		http://www.xoops.org/ The XOOPS Project
 * @author	   Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		$Id: page_dbsettings.php 20098 2010-09-07 16:19:19Z skenow $
 */
/**
 *
 */
require_once 'common.inc.php';
if (! defined ( 'XOOPS_INSTALL' ))
exit ();

$wizard->setPage ( 'dbsettings' );
$pageHasForm = true;
$pageHasHelp = true;

$vars = & $_SESSION ['settings'];

$func_connect = empty ( $vars ['DB_PCONNECT'] ) ? "mysql_connect" : "mysql_pconnect";
if (! ($link = @$func_connect ( $vars ['DB_HOST'], $vars ['DB_USER'], $vars ['DB_PASS'], true ))) {
	$error = ERR_NO_DBCONNECTION;
	$wizard->redirectToPage ( '-1', $error );
	exit ();
}

// Load config values from mainfile.php constants if 1st invocation, or reload has been asked
if (! isset ( $vars ['DB_NAME'] ) || false !== @strpos ( $_SERVER ['HTTP_CACHE_CONTROL'], 'max-age=0' )) {
	$keys = array ('DB_NAME', 'DB_CHARSET', 'DB_COLLATION', 'DB_PREFIX', 'DB_SALT' );
	foreach ( $keys as $k) {
		$vars [$k] = defined ( "XOOPS_$k" ) ? constant ( "XOOPS_$k" ) : '';
	}
}

function getDbCharsets($link) {
	static $charsets = array ( );
	if ($charsets)
	return $charsets;

	$charsets ["utf8"] = "UTF-8 Unicode";
	$ut8_available = false;
	if ($result = mysql_query ( "SHOW CHARSET", $link )) {
		while ($row = mysql_fetch_assoc ( $result )) {
			$charsets [$row ["Charset"]] = $row ["Description"];
			if ($row ["Charset"] == "utf8") {
				$ut8_available = true;
			}
		}
	}
	if (! $ut8_available) {
		unset ( $charsets ["utf8"] );
	}

	return $charsets;
}

function getDbCollations($link, $charset) {
	static $collations = array ( );

	// ALTERED BY FREEFORM SOLUTIONS TO CORRECT A BUG IN THE DETECTION OF THE DEFAULT COLLATION FOR THE CHARSET
	if ($result = mysql_query ( "SHOW COLLATION LIKE '" . mysql_real_escape_string ( $charset ) . "_%'", $link )) {
		while ($row = mysql_fetch_assoc ( $result )) {
			if(substr($row["Collation"], 0, strlen($charset)+1) == $charset."_") {
				$collations [$charset] [$row ["Collation"]] = $row ["Default"] ? 1 : 0;
			}
		}
	}

	return $collations [$charset];
}

function validateDbCharset($link, &$charset, &$collation) {
	$error = null;

	if (empty ( $charset )) {
		$collation = "";
	}
	if (version_compare ( mysql_get_server_info ( $link ), "4.1.0", "lt" )) {
		$charset = $collation = "";
	}
	if (empty ( $charset ) && empty ( $collation )) {
		return $error;
	}

	$charsets = getDbCharsets ( $link );
	if (! isset ( $charsets [$charset] )) {
		$error = sprintf ( ERR_INVALID_DBCHARSET, $charset );
	} else {
		$collations = getDbCollations ( $link, $charset );
		if (! isset ( $collations [$collation] )) {
			$error = sprintf ( ERR_INVALID_DBCOLLATION, $collation );
		}
	}

	return $error;
}

function xoFormFieldCollation($name, $value, $label, $help = '', $link, $charset) {
	if (version_compare ( mysql_get_server_info ( $link ), "4.1.0", "lt" )) {
		return "";
	}
	if (empty ( $charset ) || ! $collations = getDbCollations ( $link, $charset )) {
		return "";
	}

	$label = htmlspecialchars ( $label );
	$name = htmlspecialchars ( $name, ENT_QUOTES );
	$value = htmlspecialchars ( $value, ENT_QUOTES );

	$field = "<label for='$name'>$label</label>\n";
	if ($help) {
		$field .= '<div class="xoform-help">' . $help . "</div><div class='clear'>&nbsp;</div>\n";
	}
	$field .= "<select name='$name' id='$name'\">";

	$collation_default = "";
	$options = "";
	foreach ( $collations as $key => $isDefault) {
		if ($isDefault) {
			$collation_default = $key;
			continue;
		}
		$options .= "<option value='{$key}'" . (($value == $key) ? " selected='selected'" : "") . ">{$key}</option>";
	}
	if ($collation_default) {
		$field .= "<option value='{$collation_default}'" . (($value == $collation_default || empty ( $value )) ? " 'selected'" : "") . ">{$collation_default} (Default)</option>";
	}
	$field .= $options;
	$field .= "</select>";

	return $field;
}

function xoFormBlockCollation($name, $value, $label, $help = '', $link, $charset) {
	$block = '<div id="' . $name . '_div">';
	$block .= xoFormFieldCollation ( $name, $value, $label, $help, $link, $charset );
	$block .= '</div>';

	return $block;
}

if ($_SERVER ['REQUEST_METHOD'] == 'GET' && isset ( $_GET ['charset'] ) && @$_GET ['action'] == 'updateCollation') {
	echo xoFormFieldCollation ( 'DB_COLLATION', $vars ['DB_COLLATION'], DB_COLLATION_LABEL, DB_COLLATION_HELP, $link, $_GET ['charset'] );
	exit ();
}

if ($_SERVER ['REQUEST_METHOD'] == 'POST') {
	$params = array ('DB_NAME', 'DB_CHARSET', 'DB_COLLATION', 'DB_PREFIX', 'DB_SALT' );
	foreach ( $params as $name) {
		$vars [$name] = isset ( $_POST [$name] ) ? $_POST [$name] : "";
	}
}

$error = '';
if ($_SERVER ['REQUEST_METHOD'] == 'POST' && ! empty ( $vars ['DB_NAME'] )) {
	$error = validateDbCharset ( $link, $vars ['DB_CHARSET'], $vars ['DB_COLLATION'] );
	$db_exist = false;
	if (empty ( $error )) {
		if (! @mysql_select_db ( $vars ['DB_NAME'], $link )) {
			// Database not here: try to create it
			$result = mysql_query ( "CREATE DATABASE `" . $vars ['DB_NAME'] . '`' );
			if (! $result) {
				$error = ERR_NO_DATABASE;
			} else {
				$error = sprintf ( DATABASE_CREATED, $vars ['DB_NAME'] );
				$db_exist = true;
			}
		} else {
			$db_exist = true;
		}
		if ($db_exist && $vars ['DB_CHARSET']) {
			$sql = "ALTER DATABASE `" . $vars ['DB_NAME'] . "` DEFAULT CHARACTER SET " . mysql_real_escape_string ( $vars ['DB_CHARSET'] ) . ($vars ['DB_COLLATION'] ? " COLLATE " . mysql_real_escape_string ( $vars ['DB_COLLATION'] ) : "");
			if (! mysql_query ( $sql )) {
				$error = ERR_CHARSET_NOT_SET .'<br />'. $sql;
			}
		}
	}
	if (empty ( $error )) {
		$wizard->redirectToPage ( '+1' );
		exit ();
	}
}

if (@empty ( $vars ['DB_NAME'] )) {
	// Fill with default values
	$vars = array_merge ( $vars, array ('DB_NAME' => '', 'DB_CHARSET' => 'utf8', 'DB_COLLATION' => '', 'DB_PREFIX' => 'i' . substr ( md5 ( time () ), 0, 8 ), 'DB_SALT' => icms_core_Password::createSalt() ) );
}

function xoFormField($name, $value, $label, $maxlength, $help = '') {
	$label = htmlspecialchars ( $label );
	$name = htmlspecialchars ( $name, ENT_QUOTES );
	$value = htmlspecialchars ( $value, ENT_QUOTES );
	$maxlength = (int) ( $maxlength );

	$field = "<div class='dbconn_line'><label for='$name'>$label</label>\n";
	if ($help) {
		$field .= '<div class="xoform-help">' . $help . "</div><div class='clear'>&nbsp;</div>\n";
	}
	$field .= "<input type='text' name='$name' id='$name' value='$value' /></div>";

	return $field;
}

function xoFormFieldCharset($name, $value, $label, $help = '', $link) {
	if (version_compare ( mysql_get_server_info ( $link ), "4.1.0", "lt" )) {
		return "";
	}
	if (! $chars = getDbCharsets ( $link )) {
		return "";
	}

	$charsets = array ( );
	if (isset ( $chars ["utf8"] )) {
		$charsets ["utf8"] = $chars ["utf8"];
		unset ( $chars ["utf8"] );
	}
	ksort ( $chars );
	$charsets = array_merge ( $charsets, $chars );

	$label = htmlspecialchars ( $label );
	$name = htmlspecialchars ( $name, ENT_QUOTES );
	$value = htmlspecialchars ( $value, ENT_QUOTES );

	$field = "<div class='dbconn_line'><label for='$name'>$label</label>\n";
	if ($help) {
		$field .= '<div class="xoform-help">' . $help . "</div><div class='clear'>&nbsp;</div>\n";
	}
	$field .= "<select name='$name' id='$name' onchange=\"setFormFieldCollation('DB_COLLATION_div', this.value)\">";
	$field .= "<option value=''>None</option>";
	foreach ( $charsets as $key => $desc) {
		$field .= "<option value='{$key}'" . (($value == $key) ? " selected='selected'" : "") . ">{$key} - {$desc}</option>";
	}
	$field .= "</select></div>";

	return $field;
}

ob_start ();
?>

<?php
if (! empty ( $error ))
echo '<div class="x2-note error">' . $error . "</div>\n";
?>
<script type="text/javascript">
function setFormFieldCollation(id, val) {
    if (val == '') {
        $(id).style.display='display';
    } else {
        $(id).style.display='display';
    }
    new Ajax.Updater(
        id, '<?php
								echo $_SERVER ['PHP_SELF'];
								?>',
        { method:'get',parameters:'action=updateCollation&charset='+val }
    );
}
</script>
<div class="blokSQL">
<fieldset>
<h3><?php echo LEGEND_DATABASE;?></h3>
								<?php
								echo xoFormField ( 'DB_NAME', $vars ['DB_NAME'], DB_NAME_LABEL, 255, DB_NAME_HELP );
								?> <?php
								echo xoFormField ( 'DB_PREFIX', $vars ['DB_PREFIX'], DB_PREFIX_LABEL, 10, DB_PREFIX_HELP );
								?> <?php
								echo xoFormField ( 'DB_SALT', $vars ['DB_SALT'], DB_SALT_LABEL, 255, DB_SALT_HELP );
								?> <?php
								echo xoFormFieldCharset ( 'DB_CHARSET', $vars ['DB_CHARSET'], DB_CHARSET_LABEL, DB_CHARSET_HELP, $link );
								?> <?php
								echo xoFormBlockCollation ( 'DB_COLLATION', $vars ['DB_COLLATION'], DB_COLLATION_LABEL, DB_COLLATION_HELP, $link, $vars ['DB_CHARSET'] );
								?></fieldset>
</div>
								<?php
								$content = ob_get_contents ();
								ob_end_clean ();
								include 'install_tpl.php';
?>