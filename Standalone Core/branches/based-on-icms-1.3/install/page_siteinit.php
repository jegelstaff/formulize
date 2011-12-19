<?php
/**
 * Installer initial site configuration page
 *
 * See the enclosed file license.txt for licensing information.
 * If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
 *
 * @copyright    The XOOPS project http://www.xoops.org/
 * @license      http://www.fsf.org/copyleft/gpl.html GNU General Public License (GPL)
 * @package		installer
 * @since        Xoops 2.3.0
 * @author		Haruki Setoyama  <haruki@planewave.org>
 * @author 		Kazumi Ono <webmaster@myweb.ne.jp>
 * @author		Skalpa Keo <skalpa@xoops.org>
 * @version		$Id: page_siteinit.php 20098 2010-09-07 16:19:19Z skenow $
 */
/**
 *
 */
require_once 'common.inc.php';
if (!defined( 'XOOPS_INSTALL' ) )	exit();

$wizard->setPage( 'siteinit' );
$pageHasForm = true;
$pageHasHelp = false;

$vars =& $_SESSION['siteconfig'];

$error =& $_SESSION['error'];


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$vars['adminsalt'] = icms_core_Password::createSalt();
	$vars['adminname'] = $_POST['adminname'];
	$vars['adminlogin_name'] = $_POST['adminlogin_name'];
	$vars['adminmail'] = $_POST['adminmail'];
	$vars['adminpass'] = $_POST['adminpass'];
	$vars['adminpass2'] = $_POST['adminpass2'];
	$error = '';

	if (!preg_match( "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i", $vars['adminmail'] )) {
		$error = ERR_INVALID_EMAIL;
	} elseif (@empty( $vars['adminlogin_name'] ) || @empty( $vars['adminname'] )  || @empty( $vars['adminlogin_name'] ) || @empty( $vars['adminpass'] ) || @empty( $vars['adminmail']) || empty( $vars['adminsalt'])) {
		$error = ERR_REQUIRED;
	} elseif ($vars['adminpass'] != $vars['adminpass2']) {
		$error = ERR_PASSWORD_MATCH;
	}
	if ($error) {
		$wizard->redirectToPage( '+0' );
		return 200;
	} else {
		$wizard->redirectToPage( '+1' );
		return 302;
	}
}

ob_start();
?>
<?php if (!empty( $error ) ) echo '<div class="x2-note error">' . $error . "</div>\n"; ?>

<script
	type="text/javascript"
	src="../libraries/jquery/password_strength_plugin.js"></script>
<script type="text/javascript">
                $(document).ready( function() {
                    $.fn.shortPass = "<?php echo _CORE_PASSLEVEL1;?>";
                    $.fn.badPass = "<?php echo _CORE_PASSLEVEL2;?>";
                    $.fn.goodPass = "<?php echo _CORE_PASSLEVEL3;?>";
                    $.fn.strongPass = "<?php echo _CORE_PASSLEVEL4;?>";
                    $.fn.samePassword = "Username and Password identical.";
                    $.fn.resultStyle = "";
				$(".password_adv").passStrength({
					shortPass: 		"top_shortPass",
					badPass:		"top_badPass",
					goodPass:		"top_goodPass",
					strongPass:		"top_strongPass",
					baseStyle:		"top_testresult",
					userid:			"#adminlogin_name",
					messageloc:		0

				});
			});
	</script>
<fieldset>
<h3><?php echo LEGEND_ADMIN_ACCOUNT; ?></h3>
<div class="blokinit">
<div class="dbconn_line"><label for="adminname"><?php echo ADMIN_DISPLAY_LABEL; ?></label>
<div class="clear">&nbsp;</div>
<input type="text" name="adminname" id="adminname" maxlength="25"
	value="<?php echo htmlspecialchars( $vars['adminname'], ENT_QUOTES ); ?>" />
</div>
<div class="dbconn_line"><label for="adminlogin_name"><?php echo ADMIN_LOGIN_LABEL; ?></label>
<div class="clear">&nbsp;</div>
<input class="adminlogin_name" type="text" name="adminlogin_name"
	id="adminlogin_name" maxlength="25"
	value="<?php echo htmlspecialchars( $vars['adminlogin_name'], ENT_QUOTES ); ?>" />
</div>
<div class="dbconn_line"><label for="adminmail"><?php echo ADMIN_EMAIL_LABEL; ?></label>
<div class="clear">&nbsp;</div>
<input type="text" name="adminmail" id="adminmail" maxlength="255"
	value="<?php echo htmlspecialchars( $vars['adminmail'], ENT_QUOTES ); ?>" />
</div>
<div class="dbconn_line"><label for="adminpass"><?php echo ADMIN_PASS_LABEL; ?></label>
<div class="clear">&nbsp;</div>
<input class="password_adv" type="password" name="adminpass"
	id="adminpass" maxlength="255" value="" /></div>
<div class="dbconn_line"><label for="adminpass2"><?php echo ADMIN_CONFIRMPASS_LABEL; ?></label>
<div class="clear">&nbsp;</div>
<input type="password" name="adminpass2" id="adminpass2" maxlength="255"
	value="" /></div>
</div>
</fieldset>
<?php
$content = ob_get_contents();
ob_end_clean();
$error = '';
include 'install_tpl.php';
?>