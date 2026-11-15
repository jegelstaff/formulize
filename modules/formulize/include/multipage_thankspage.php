<?php

// Draws the Thank You page: the screen's thanks text, and the link to the done destination. Included by
// displayFormPages(), from multipage_boilerplate.php full screen and directly in elements-only mode (the drawer),
// so the thanks text's PHP runs with the same variables in scope either way. Included, not a function, because
// thanks text written for a site may use any of displayFormPages()'s variables, not only the documented ones.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

    // the link to the done destination, which {thankYouNav} in the thanks text stands for, and $thankYouNav in PHP.
    // It has its own space above, so it is set apart from whatever thanks text comes before it. Centred, it is the
    // version added below thanks text that doesn't place the link itself.
    // In the drawer the done destination is leaving the entry, which the drawer does when the link is clicked
    // (data-formulize-done-link), so there it doesn't submit the calreturnform, which only full screen draws.
    $drawThankYouNav = function($centred) use ($pagesSkipped, $thankYouLinkText, $done_dest, $settings, $elements_only) {
        $nav = "<div id=\"thankYouNavigation\" class=\"fz-stack fz-gap-2 fz-mt-6".($centred ? " fz-text-center" : "")."\">\n";
        if($pagesSkipped) {
            $nav .= "<p>" . _formulize_DMULTI_SKIP . "</p>\n";
        }
        if($thankYouLinkText != "{NOBUTTON}") {
            $nav .= "<div class=\"fz-cluster".($centred ? " fz-justify-center" : "")."\"><a class=\"fz-btn fz-btn--primary fz-btn--lg\" href=\"".htmlspecialchars($done_dest)."\"";
            if($elements_only) {
                $nav .= " data-formulize-done-link=\"1\"";
            } elseif(is_array($settings)) {
                $nav .= " onclick=\"javascript:window.document.calreturnform.submit();return false;\"";
            }
            $nav .= ">" . htmlspecialchars(trans($thankYouLinkText)) . "</a></div>\n"; // plain text, and it can arrive in POST as formulize_buttonText
        }
        return $nav . "</div>";
    };
    $thankYouNav = $drawThankYouNav(false);

		$entry = $entry_id; // legacy support because there used to be a version of entry id just called entry that was in scope at this point.
		$form_id = $fid;

		// thanks text that doesn't place the link itself, as {thankYouNav} or $thankYouNav, gets it below, so users
		// always have a way on
		$thanksSource = is_array($thankstext) ? (isset($thankstext[1]) ? (string) $thankstext[1] : '') : (string) $thankstext;
		$thanksPlacesNav = (strpos($thanksSource, 'thankYouNav') !== false);

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
		if(!$thanksPlacesNav) {
			print $drawThankYouNav(true);
		}

		unset($entry);
