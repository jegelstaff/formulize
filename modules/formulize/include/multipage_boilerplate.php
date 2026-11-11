<?php

// Set up the javascript that we need for the form-submit functionality to work
// note that validateAndSubmit calls the form validation function again, but obviously it will pass if it passed here.  The validation needs to be called prior to setting the pages, or else you can end up on the wrong page after clicking an ADD button in a subform when you've missed a required field.
// savedPage and savedPrevPage are used to pick up the page and prevpage only when a two step validation, such as checking for uniqueness, returns and calls validateAndSubmit again
?>

<script type='text/javascript'>
var savedPage;
var savedPrevPage;
function submitForm(page, prevpage) {
    if(conditionalCheckIsInProgress()) {
       setTimeout(function() {
            submitForm(page, prevpage);
            }, 1000);
       return false;
    }
    var validate = xoopsFormValidate_formulize_mainform('', window.document.formulize_mainform);
    if(validate) {
        savedPage = 0;
        savedPrevPage = 0;
        multipageSetHiddenFields(page, prevpage);
        if (formulizechanged) {
            validateAndSubmit();
        } else {
            jQuery("input[name^='decue_']").remove();
            // 'rewritePage' will trigger the page to change after the locks have been removed
            removeEntryLocks('rewritePage');
            document.formulize_mainform.deletesubsflag.value=0;
        }
    } else {
        hideSavingGraphic();
        savedPage = page;
        savedPrevPage = prevpage;
    }
}

function multipageSetHiddenFields(page, prevpage) {
	<?php if($screen AND $screen->getVar('finishisdone')) { // user clicked to finish, there is no thanks page, so send them to the done destination ?>
  if(page == <?php print $thanksPage; ?>) {
    window.document.formulize_mainform.ventry.value = '';
    jQuery('form[name=formulize_mainform]').attr('action', <?php print json_encode((string) $done_dest, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>);
  }
	<?php } ?>
  window.document.formulize_mainform.formulize_currentPage.value = page<?php print $screen ? "+'-".$screen->getVar('sid')."'" : ""; ?>;
  window.document.formulize_mainform.formulize_prevPage.value = prevpage<?php print $screen ? "+'-".$screen->getVar('sid')."'" : ""; ?>;
  window.document.formulize_mainform.formulize_doneDest.value = <?php print json_encode((string) $settings['formulize_doneDest'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
  window.document.formulize_mainform.formulize_buttonText.value = <?php print json_encode(isset($settings['formulize_buttonText']) ? (string) $settings['formulize_buttonText'] : "", JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
}

function pageJump(options, prevpage) {
    for (var i=0; i < options.length; i++) {
        if (options[i].selected) {
            submitForm(options[i].value, prevpage);
            return false;
        }
    }
    return false;
}

jQuery('.navtab').click(function(){
    var targetPage = jQuery(this).attr('this-page');
    if (targetPage > 0) {
        submitForm(targetPage, <?php print $currentPage; ?>);
    }
		return false;
});

</script><noscript>
<h1>You do not have javascript enabled in your web browser.  This form will not work with your web browser.  Please contact the webmaster for assistance.</h1>
</noscript>
<?php

if($currentPage == $thanksPage) {

    // have not got into displayForm, so none of the navigation metadata we would normally rely on has been parsed and set yet :(
    // need to deduce the form, entry, and page from

    if(is_array($settings)) {
        print "<form name=calreturnform action=\"".htmlspecialchars($done_dest)."\" method=post>\n";
        writeHiddenSettings($settings, null, array(), array(), $screen);
        if($_POST['go_back_form']) {
            $go_back_page = $_POST['go_back_page'];
            $go_back_entry = intval($_POST['go_back_entry']);
            $go_back_form = intval($_POST['go_back_form']);
        } elseif(isset($GLOBALS['formulize_displayingSubform'])) { // if we land on the thanks page of a subform because all pages fail conditions, then we'll be here and we should set things to go back to the prior screen and page and entry
            $go_back_page = $_POST['formulize_prevPage'];
            $go_back_entry = intval($GLOBALS['formulize_displayingSubform']['originalEntry']);
            $go_back_form = intval($GLOBALS['formulize_displayingSubform']['originalFid']);
        }
        if($go_back_page AND $go_back_entry AND $go_back_form) {
            $goBackParts = explode('-', $go_back_page);
            foreach($goBackParts as $i=>$part) {
                $goBackParts[$i] = intval($part);
            }
            $go_back_page = implode('-', $goBackParts);
            print "<input type='hidden' name='go_back_form' value='".$go_back_form."'>
                <input type='hidden' name='go_back_entry' value='".$go_back_entry."'>
                <input type='hidden' name='go_back_page' value='".$go_back_page."'>";
        }
        print "</form>";
    }

    if($screen AND $screen->getVar('finishisdone')) {
        print "<script type='text/javascript'>window.document.calreturnform.submit();</script>";
        return; // if we've ended up on the thanks page via conditions (last page was not shown) then we should just bail if there is not supposed to be a thanks page
    }

    // the link to the done destination, which {thankYouNav} in the thanks text stands for, and $thankYouNav in PHP
    ob_start();
    print "<div id=\"thankYouNavigation\" class=\"fz-stack fz-gap-2\">\n";
    if($pagesSkipped) {
        print "<p>" . _formulize_DMULTI_SKIP . "</p>\n";
    }
    if($thankYouLinkText != "{NOBUTTON}") {
        print "<div class=\"fz-cluster\"><a class=\"fz-btn fz-btn--primary\" href=\"".htmlspecialchars($done_dest)."\"";
        if(is_array($settings)) {
            print " onclick=\"javascript:window.document.calreturnform.submit();return false;\"";
        }
        print ">" . htmlspecialchars(trans($thankYouLinkText)) . "</a></div>\n"; // plain text, and it can arrive in POST as formulize_buttonText
    }
    print "</div>";
    $thankYouNav = ob_get_clean();

		$entry = $entry_id; // legacy support because there used to be a version of entry id just called entry that was in scope at this point.
		$form_id = $fid;

		// thanks text is stored exactly as the admin typed it, so it is printed or run as it is
		try {
			if(is_array($thankstext)) {
				if($thankstext[0] === "PHP"){
					eval($thankstext[1]);
				} elseif(substr(trim($thankstext[1]), 0, 5)=='<?php') {
					eval(removeOpeningPHPTag($thankstext[1]));
				} else {
					print str_replace("{thankYouNav}", $thankYouNav, $thankstext[1]);
				}
			} else {
				if(substr(trim($thankstext), 0, 5)=='<?php') {
					eval(removeOpeningPHPTag($thankstext));
				} else { // HTML
					print str_replace("{thankYouNav}", $thankYouNav, $thankstext);
				}
			}
		} catch (Throwable $e) {
			error_log("Formulize Error: the thanks text of screen ".(is_object($screen) ? $screen->getVar('sid') : 0)." could not run: ".$e->getMessage());
		}

		unset($entry);

}
