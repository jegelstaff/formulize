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
            jQuery("#formulizeform").animate({opacity:0.4}, 200, "linear");
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
  if(page == <?php print $thanksPage; ?>) {
    window.document.formulize_mainform.ventry.value = ''; 
    jQuery('form[name=formulize]').attr('action', '<?php print $done_dest; ?>');
  }
  window.document.formulize_mainform.formulize_currentPage.value = page<?php print $screen ? "+'-".$screen->getVar('sid')."'" : ""; ?>;
  window.document.formulize_mainform.formulize_prevPage.value = prevpage<?php print $screen ? "+'-".$screen->getVar('sid')."'" : ""; ?>;
  window.document.formulize_mainform.formulize_doneDest.value = '<?php print $settings['formulize_doneDest']; ?>';
  window.document.formulize_mainform.formulize_buttonText.value = '<?php print $settings['formulize_buttonText']; ?>';
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

</script><noscript>
<h1>You do not have javascript enabled in your web browser.  This form will not work with your web browser.  Please contact the webmaster for assistance.</h1>
</noscript>
<?php

if($currentPage == $thanksPage) {

    if(is_array($settings)) {
        print "<form name=calreturnform action=\"$done_dest\" method=post>\n";
        writeHiddenSettings($settings, null, array(), array(), $screen);
        if($_POST['go_back_form']) {
            $goBackParts = explode('-', $_POST['go_back_page']);
            foreach($goBackParts as $i=>$part) {
                $goBackParts[$i] = intval($part);
            }
            $go_back_page = implode('-', $goBackParts);
            print "<input type='hidden' name='go_back_form' value='".intval($_POST['go_back_form'])."'>
            <input type='hidden' name='go_back_entry' value='".intval($_POST['go_back_entry'])."'>
            <input type='hidden' name='go_back_page' value='".$go_back_page."'>";
        }
        print "</form>";
    }

    if($screen AND $screen->getVar('finishisdone')) {
        print "<script type='text/javascript'>window.document.calreturnform.submit();</script>";
        return; // if we've ended up on the thanks page via conditions (last page was not shown) then we should just bail if there is not supposed to be a thanks page
    }

        ob_start();
    print "<br><hr><br><div id=\"thankYouNavigation\"><p><center>\n";
    if($pagesSkipped) {
        print _formulize_DMULTI_SKIP . "</p><p>\n";
    }
    
    if($button_text != "{NOBUTTON}") {
        print "<a href='$done_dest'";
        if(is_array($settings)) {
            print " onclick=\"javascript:window.document.calreturnform.submit();return false;\"";
        }
        print ">" . $button_text . "</a>\n";
    }
    print "</center></p></div>";
        $thankYouNav = ob_get_clean();
        
        $entry_id = $entry;
        
		if(is_array($thankstext)) {
            $thankstext[1] = undoAllHTMLChars($thankstext[1]);
			if($thankstext[0] === "PHP"){
                eval($thankstext[1]);
            } elseif(substr($thankstext[1], 0, 5)=='<?php') {
				eval(substr($thankstext[1],5)); // strip out opening PHP tag
			} else {
				print str_replace("{thankYouNav}", $thankYouNav, $thankstext[1]);
			}
        } else {
            $thankstext = undoAllHTMLChars($thankstext);
            if(substr($thankstext, 0, 5)=='<?php') {
                eval(substr($thankstext,5)); // strip out opening PHP tag
            } else { // HTML
                print str_replace("{thankYouNav}", $thankYouNav, $thankstext);
            }
        }

} 

if($currentPage == 1 AND $pages[1][0] !== "HTML" AND $pages[1][0] !== "PHP" AND !$_POST['goto_sfid']) { // only show intro text on first page if there's actually a form there
  print undoAllHTMLChars($introtext);
}

// display an HTML or PHP page if that's what this page is...
if($currentPage != $thanksPage AND ($pages[$currentPage][0] === "HTML" OR $pages[$currentPage][0] === "PHP")) {
    // PHP
    if($pages[$currentPage][0] === "PHP") {
        eval($pages[$currentPage][1]);
    // HTML
    } else {
        print undoAllHTMLChars($pages[$currentPage][1]);
    }

    // put in the form that passes the entry, page we're going to and page we were on
    include_once XOOPS_ROOT_PATH . "/modules/formulize/include/functions.php";
    ?>

    
    <form name=formulize id=formulize_mainform action=<?php print getCurrentURL(); ?> method=post>
    <input type=hidden name=entry<?php print $fid; ?> id=entry<?php print $fid; ?> value=<?php print $entry ?>>
    <input type=hidden name=formulize_currentPage id=formulize_currentPage value="">
    <input type=hidden name=formulize_prevPage id=formulize_prevPage value="">
    writeHiddenSettings($settings, null, array(), array(), $screen);
    </form>

    <script type="text/javascript">
        function validateAndSubmit() {
            window.document.formulize_mainform.submit();
        }
    </script>

    <?php

}