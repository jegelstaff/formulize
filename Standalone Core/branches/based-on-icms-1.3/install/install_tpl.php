<?php
/**
* Installer tables creation page
*
* See the enclosed file license.txt for licensing information.
* If you did not receive this file, get it at http://www.fsf.org/copyleft/gpl.html
*
* @copyright    The ImpressCMS project http://www.impresscms.org/
* @license      http://www.fsf.org/copyleft/gpl.html GNU General Public License (GPL)
* @package		installer
* @since        1.0
* @author		Haruki Setoyama  <haruki@planewave.org>
* @author 		Kazumi Ono <webmaster@myweb.ne.jp>
* @author		Skalpa Keo <skalpa@xoops.org>
* @version		$Id: install_tpl.php 22529 2011-09-02 19:55:40Z phoenyx $
*/
/**
 *
 */
	defined( 'XOOPS_INSTALL' ) or die();
	if (isset($_COOKIE['xo_install_lang'])) {
		$icmsConfig['language'] = $icmsConfig['language'] = $_COOKIE['xo_install_lang'];
	}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<title><?php echo sprintf(XOOPS_INSTALL_WIZARD, XOOPS_VERSION); ?>(<?php echo ($wizard->currentPage+1) . '/' . count($wizard->pages); ?>)</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo _INSTALL_CHARSET ?>" />
<?php
if (defined('_ADM_USE_RTL') && _ADM_USE_RTL) {
 echo '<link rel="stylesheet" type="text/css" media="all" href="style_rtl.css" />';
} else {
 echo '<link rel="stylesheet" type="text/css" media="all" href="style.css" />';
 echo '<link rel="stylesheet" type="text/css" media="all" href="style.css" title="darkstyle" />';
 echo '<link rel="stylesheet" type="text/css" media="all" href="stylelight.css" title="lightstyle" />';
}
?>

<script type="text/javascript" src="../libraries/jquery/jquery.js"></script>
<script type="text/javascript" src="stylesheetToggle.js"></script>
<script type="text/javascript" src="jquery.scrollTo.js"></script>
<script type="text/javascript">
     $(document).ready(function() {
	$.stylesheetInit();
	$('#toggler').bind('click',function(e) {
	 $.stylesheetToggle();
	 return false;
	}
	);
        $('#help_button').click(function() {
         if ($('div.xoform-help').is(":hidden"))
         {
          $('div.xoform-help').slideDown("slow");
         } else {
          $('div.xoform-help').slideUp("slow");
         }
        });
	$('#pagedown').click(function() {
	 $.scrollTo('max', 1500);
	});
     });
</script>
</head>
<?php
if (defined('_ADM_USE_RTL') && _ADM_USE_RTL) {
echo '<body dir="rtl">';
} else {
echo '<body>';
}
?>
<div id="wrapper">
<div id="header">
<div id="logo"><img src="img/logo.png" alt="ImpressCMS" /></div>
<div id="info"><?php echo sprintf(XOOPS_INSTALL_WIZARD, XOOPS_VERSION)."<br />".INSTALL_STEP; ?>&nbsp;<?php echo ($wizard->currentPage+1) . INSTALL_OUTOF . count($wizard->pages); ?></div>
</div>

<div id="page_top">&nbsp;</div>

<div id="page">
	<form action='<?php echo $_SERVER['PHP_SELF']; ?>' method='post'>
	<div id="leftside">
			<h3><?php echo INSTALL_H3_STEPS; ?></h3>
			<ul>
			<?php foreach ( $wizard->pages as $k => $page) {
				$class = '';
				if ($k == $wizard->currentPage )	$class = ' class="current"';
				elseif ($k > $wizard->currentPage )	$class = ' class="disabled"';
				if (empty( $class )) {
					$li = '<a href="' . $wizard->pageURI($page) . '">' . $wizard->pagesNames[$k] . '</a>';
				} else {
					$li = $wizard->pagesNames[$k];
				}
				echo "<li$class>$li</li>\n";
			} ?>
			</ul>
	<div class="clear">&nbsp;</div>
	</div>
	<div id="rightside">
			<div class="page" id="<?php echo $wizard->currentPageName; ?>">
				<?php if ($pageHasHelp) { ?>
						<button type="button" onclick="javascript:void(0);" id="help_button" title="<?php echo SHOW_HIDE_HELP; ?>">
						<img src="img/help2.png" alt="<?php echo SHOW_HIDE_HELP; ?>"  title="<?php echo SHOW_HIDE_HELP; ?>" />
						</button>
				<?php } ?>
						<button type="button" onclick="javascript:void(0);" id="pagedown">
						<img src="img/down.png" alt="<?php echo SHOW_HIDE_HELP; ?>"  title="<?php echo SHOW_HIDE_HELP; ?>" />
						</button>
						<button type="button" onclick="javascript:void(0);" id="toggler" >
						<img src="img/toggler.png" alt="<?php echo SHOW_HIDE_HELP; ?>"/>
						</button>
				<h2><?php echo $wizard->pagesTitles[ $wizard->currentPage ]; ?></h2>
				<?php echo $content; ?>
			</div>
			<div id="buttons">
				<?php if ($wizard->currentPage != 0  && ( $wizard->currentPage != 11 )) { ?>
				<button type="button" title="<?php echo BUTTON_PREVIOUS; ?>" onclick="location.href='<?php echo $wizard->pageURI('-1'); ?>'" class="prev">
					<img src="img/left-arr.png" alt="<?php echo BUTTON_PREVIOUS; ?>"  title="<?php echo BUTTON_PREVIOUS; ?>" width="16" />
				</button>
				<?php } ?>
				<?php if ($wizard->currentPage == 11) { ?>
				<button  id="hmo" title="<?php echo BUTTON_SHOW_SITE; ?>" type="button" onclick="location.href='<?php echo $wizard->pageURI('11'); ?>?success=true'" class="finish">
					<img src="img/Home.png" alt="<?php echo BUTTON_SHOW_SITE; ?>" title="<?php echo BUTTON_SHOW_SITE; ?>" width="32" />
				</button>
				<?php } ?>
				<?php if ($wizard->pages[$wizard->currentPage] == $wizard->secondlastpage) { ?>
					<?php if (@$pageHasForm) { ?>
					<button type="submit">
					<?php } else { ?>
					<button type="button"  title="<?php echo BUTTON_NEXT; ?>"  accesskey="n" onclick="location.href='<?php echo $wizard->pageURI('+1'); ?>'" class="next">
					<?php } ?>
					<?php if ($_POST['mod'] != 1) { ?>
						<img src="img/right-arr.png" alt="<?php echo BUTTON_NEXT; ?>" width="16" />
					<?php } else { ?>
						<?php echo BUTTON_FINISH; ?>
					<?php } ?>
					</button>
				<?php } else if ($wizard->pages[$wizard->currentPage] != $wizard->lastpage) { ?>
					<?php if (@$pageHasForm) { ?>
					<button type="submit"  title="<?php echo BUTTON_NEXT; ?>" >
					<?php } else { ?>
					<button type="button"  title="<?php echo BUTTON_NEXT; ?>"  accesskey="n" onclick="location.href='<?php echo $wizard->pageURI('+1'); ?>'" class="next">
					<?php } ?>
						<img src="img/right-arr.png" alt="<?php echo BUTTON_NEXT; ?>"width="16" />
					</button>
				<?php } ?>
			</div>
	<div class="clear">&nbsp;</div>
	</div>
	</form>
<div class="clear">&nbsp;</div>
</div>
<div id="page_bot">&nbsp;</div>

<div id="footer">
	<?php echo INSTALL_COPYRIGHT; ?>
</div>
</div>
</body>
</html>