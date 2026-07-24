<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Notice-only patch. Makes no database or file changes at all — its sole purpose is to put an
// unmissable alert in front of the admin running the update, because the enhanced XSS protections
// turned on by patch 007 can visibly change how stored HTML markup is displayed.
// Gated on the same dbversion as patch 007 (13), so the warning appears exactly once, on the same
// update run that provisions the setting.

function formulize_patch_008_xss_protection_notice($prev_dbversion, $required_dbversion) {

    if ($prev_dbversion >= 13) {
        return true; // already shown (same dbversion batch as patch 007)
    }

    $message = "Starting with Formulize 8.2, protections against XSS attacks have been enhanced. "
        . "These enhancements are on by default. These might break parts of your website.\n\n"
        . "If you have HTML markup stored in entries in forms, and you rely on that markup being used "
        . "in your site in various ways, you should audit the relevant pages to make sure they still work.\n\n"
        . "You can disable the enhanced protections by going to Settings > Advanced > Debugging. "
        . "If the protections are disabled, and if you have enabled Logging under Settings > System > Logging, "
        . "then all the HTML markup substitutions that would have been made will be logged instead. "
        . "You can review the substitutions in the Log Viewer to determine what changes you should make.";

    // Also written to the update log, since the alert is dismissed and gone.
    echo '<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES)) . '</p>';
    echo '<script>alert(' . json_encode($message) . ');</script>';

    return true;
}
