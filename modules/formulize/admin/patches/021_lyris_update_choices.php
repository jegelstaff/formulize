<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Lyris becomes the standard theme in 8.30. Before the update runs, a webmaster on a site that uses Anari is asked
// whether to switch to Lyris, which screens should take their custom templates along, and which should start using
// Lyris's screen settings, such as opening entries in the drawer (admin/lyris_update_wizard.php). This applies the
// answers, which admin/op.php keeps with formulize_lyrisUpdateChoices(). See include/lyris_update.php.
//
// Template screens get copies of their code and template in Lyris whatever was chosen, and when nobody was asked,
// since they have no defaults to fall back on, and would draw nothing on Lyris without them.
//
// A new site starts on Lyris, and has no screens yet, so it has nothing to do. Running this again changes nothing
// more: a copy is only made where there's none, and a setting is only changed to the value it's being changed to.

function formulize_patch_021_lyris_update_choices($prev_dbversion, $required_dbversion) {

    if ($prev_dbversion >= 36) {
        return true;
    }

    include_once XOOPS_ROOT_PATH . '/modules/formulize/include/lyris_update.php';
    return formulize_lyrisUpdateApply(formulize_lyrisUpdateChoices());
}
