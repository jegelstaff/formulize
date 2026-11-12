<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                    Copyright (c) Formulize Incorporated                   ##
###############################################################################
##  This program is free software; you can redistribute it and/or modify     ##
##  it under the terms of the GNU General Public License as published by     ##
##  the Free Software Foundation; either version 2 of the License, or        ##
##  (at your option) any later version.                                      ##
###############################################################################

// The questions about Lyris (see include/lyris_update.php), in a dialog. They're asked in two places:
//
// - The 8.30 update, on the update page, for a webmaster on a site that uses any theme but Lyris, which for most
//   sites is Anari. Included by admin/op.php. The
//   Update Formulize button opens the dialog, which starts by asking whether to switch, and the last step submits
//   the update with the answers.
// - Appearance > Settings, when the site's theme is changed to Lyris for the first time. Included by
//   admin/lyris_switch_wizard.php, which sets $lyrisWizardContext to 'switch'. Lyris was chosen already, so the
//   dialog starts with its looks. Saving the settings opens it, and the last step sends the answers to
//   admin/save/lyris_switch_save.php, which switches the theme, and then saves the settings.
//
// Nothing changes until the last step, and Cancel changes nothing, so the questions can be opened and closed as often
// as you like.
//
// The update draws this before it runs, so it can't use a Smarty template from the database, since a new template
// only gets there when the update runs. It's built with Formulize UI, inside .formulize-admin-ui, which gets it the
// fixes it needs inside the admin (formulize-admin.css). The admin UI runs jQuery 1.4.2, so the script here uses
// plain JavaScript.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/lyris_update.php';

// switching on Appearance > Settings, or else the update
$lyrisSwitching = (isset($lyrisWizardContext) AND $lyrisWizardContext == 'switch');
// the theme the site is moving from, and its name, for the words that name it
$lyrisFromTheme = formulize_lyrisUpdateFromTheme();
$lyrisFromName = htmlspecialchars($lyrisFromTheme);
// when switching, the theme was chosen already, so there are no choices about it, and switching is the answer
$lyrisThemeChoices = $lyrisSwitching ? array() : formulize_lyrisUpdateThemeChoices();
$lyrisChoiceDescriptions = array(
    'switch' => _AM_FORMULIZE_LYRIS_CHOICE_SWITCH_DESC,
    'try' => sprintf(_AM_FORMULIZE_LYRIS_CHOICE_TRY_DESC, $lyrisFromName),
    'keep' => _AM_FORMULIZE_LYRIS_CHOICE_KEEP_DESC,
);
// every look Lyris has on this site, the ones it comes with and any added since
$lyrisLooks = formulize_lyrisUpdateLooks();
$lyrisSiteLook = formulize_getAppearanceSiteLook(FORMULIZE_LYRIS_UPDATE_TO);
// the site's own logo and browser icon, to bring along to Lyris, with the addresses to show them at
$lyrisImageUrls = array();
$lyrisRootPath = str_replace('\\', '/', XOOPS_ROOT_PATH);
foreach (formulize_lyrisUpdateImagesToCarry($lyrisFromTheme) as $setting => $imagePath) {
    $imagePath = str_replace('\\', '/', $imagePath);
    if (strpos($imagePath, $lyrisRootPath . '/') === 0) {
        $lyrisImageUrls[$setting] = XOOPS_URL . substr($imagePath, strlen($lyrisRootPath));
    }
}
if (isset($lyrisImageUrls['appearance_logo']) AND isset($lyrisImageUrls['appearance_favicon'])) {
    $lyrisImagesLabel = _AM_FORMULIZE_LYRIS_USE_LOGO_AND_ICON;
} else {
    $lyrisImagesLabel = isset($lyrisImageUrls['appearance_logo']) ? _AM_FORMULIZE_LYRIS_USE_LOGO : _AM_FORMULIZE_LYRIS_USE_ICON;
}
$lyrisSettings = formulize_lyrisUpdateSettingsOffered();
$lyrisTemplates = formulize_lyrisUpdateTemplatesOffered();
$lyrisTemplateScreens = formulize_lyrisUpdateTemplateScreensToCopy();
$lyrisScreens = formulize_lyrisUpdateScreens();
$lyrisScreenTypes = formulize_getScreenTypes();

// The sample screens to show. In the update, the ones the site's theme and Lyris both have, so they can be compared
// side by side. A theme has samples when it has an appearance_preview folder, as Anari does, and a theme copied from
// Anari usually still has Anari's, which are drawn with its own stylesheets. When the site's theme has none, or when
// switching, where there's no comparing, they're Lyris's own, and Lyris is shown on its own.
$lyrisPreviewScreens = formulize_getAppearancePreviewScreens(FORMULIZE_LYRIS_UPDATE_TO);
$lyrisFromPreviews = false;
if (!$lyrisSwitching AND isset(formulize_getAppearanceThemes()[$lyrisFromTheme])) {
    $lyrisSharedPreviews = array_intersect_key(formulize_getAppearancePreviewScreens($lyrisFromTheme), $lyrisPreviewScreens);
    if ($lyrisSharedPreviews) {
        $lyrisPreviewScreens = $lyrisSharedPreviews;
        $lyrisFromPreviews = true;
    }
}
$lyrisPreviewScreen = isset($lyrisPreviewScreens['list']) ? 'list' : (string) key($lyrisPreviewScreens);
$lyrisPreviewUrl = XOOPS_URL . '/modules/formulize/appearance_preview.php';

// the screens with templates to copy, by where they are
$lyrisTemplateGroups = array();
foreach ($lyrisScreens as $sid => $screenInfo) {
    if (isset($lyrisTemplates[$sid])) {
        $lyrisTemplateGroups[$screenInfo['where']][$sid] = $screenInfo['title'];
    }
}

$lyrisFormAction = XOOPS_URL . ($lyrisSwitching ? '/modules/formulize/admin/save.php' : '/modules/formulize/admin/ui.php?op=patchDB');
// the steps there are, in order, which decides the one that's first
$lyrisSteps = array_keys(array_filter(array(
    'theme' => !$lyrisSwitching,
    'look' => count($lyrisLooks) > 1,
    'settings' => (bool) $lyrisSettings,
    'templates' => (bool) $lyrisTemplateGroups,
    'summary' => true,
)));
$lyrisFirstStep = $lyrisSteps[0];
// when switching, there's nothing to ask when there's only the summary, and the theme simply changes
if ($lyrisSwitching AND $lyrisFirstStep == 'summary') {
    return;
}
// the previews on the update's first step, by theme, with their captions
$lyrisPreviewCaptions = array();
if (!$lyrisSwitching) {
    if ($lyrisFromPreviews) {
        $lyrisPreviewCaptions[$lyrisFromTheme] = sprintf(_AM_FORMULIZE_LYRIS_PREVIEW_CURRENT, $lyrisFromName);
    }
    $lyrisPreviewCaptions[FORMULIZE_LYRIS_UPDATE_TO] = _AM_FORMULIZE_LYRIS_PREVIEW_LYRIS;
}
// the samples are shown in turn, the list first, until one is chosen
if (isset($lyrisPreviewScreens['list'])) {
    $lyrisPreviewScreens = array('list' => $lyrisPreviewScreens['list']) + $lyrisPreviewScreens;
}

/**
 * A preview of one theme: a page for each sample screen, drawn at a desktop width and shrunk to fit by the script,
 * with only the one being shown visible, so changing between them is immediate.
 *
 * @param string $theme the theme to show
 * @param string $title what the preview is, for screen readers
 * @param boolean $withLook whether it shows the look chosen in the wizard
 * @return string HTML
 */
$lyrisPreviewFrame = function ($theme, $title, $withLook = false) use ($lyrisPreviewScreens, $lyrisPreviewScreen) {
    $html = '<div class="fz-card fz-card--flush fz-overflow-hidden formulize-lyris__frame">';
    foreach (array_keys($lyrisPreviewScreens) as $screen) {
        $html .= '<iframe tabindex="-1" title="' . htmlspecialchars(strip_tags($title)) . '" data-theme="' . htmlspecialchars($theme) . '" data-screen="' . htmlspecialchars($screen) . '"'
            . ($withLook ? ' data-look="1"' : '') . ($screen == $lyrisPreviewScreen ? '' : ' hidden') . '></iframe>';
    }
    return $html . '</div>';
};

/**
 * The switch between the samples, beside a step's heading: one button for each, the one being shown as the main
 * button.
 *
 * @return string HTML, or '' when there's only one sample
 */
$lyrisPreviewSwitch = function () use ($lyrisPreviewScreens, $lyrisPreviewScreen) {
    if (count($lyrisPreviewScreens) < 2) {
        return '';
    }
    $html = '<div class="fz-cluster" role="group" aria-label="' . htmlspecialchars(strip_tags(_AM_FORMULIZE_LYRIS_PREVIEW_SHOW)) . '">'
        . '<span class="fz-text-sm fz-text-muted">' . _AM_FORMULIZE_LYRIS_PREVIEW_SHOW . '</span>';
    foreach ($lyrisPreviewScreens as $screen => $name) {
        $current = ($screen == $lyrisPreviewScreen);
        $html .= '<button type="button" class="fz-btn formulize-lyris-preview-screen' . ($current ? ' fz-btn--primary' : '') . '" data-screen="' . htmlspecialchars($screen) . '" aria-pressed="' . ($current ? 'true' : 'false') . '">' . htmlspecialchars($name) . '</button>';
    }
    return $html . '</div>';
};

/**
 * When switching, a line at the top of the first step, saying why the questions are being asked.
 *
 * @param string $step the step being drawn
 * @return string HTML, or '' for the update, and for the other steps
 */
$lyrisSwitchIntro = function ($step) use ($lyrisSwitching, $lyrisFirstStep) {
    return ($lyrisSwitching AND $step == $lyrisFirstStep) ? '<div class="fz-callout"><p>' . _AM_FORMULIZE_LYRIS_SWITCH_INTRO . '</p></div>' : '';
};
?>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo htmlspecialchars(XOOPS_URL . formulize_uiStylesheetPath()); ?>">
<script type="text/javascript" src="<?php echo htmlspecialchars(XOOPS_URL . formulize_uiScriptPath()); ?>"></script>
<style>
/* The wizard's own layout. The classes are formulize-, since they are Formulize's own; everything else is Formulize UI. */
.formulize-lyris { font-family: var(--fz-font-sans); font-size: var(--fz-text-sm); line-height: var(--fz-leading-normal); color: var(--fz-color-text); max-width: 48rem; }
/* the dialog is as big as the screen allows, so the previews can be; its buttons stay at the bottom */
#formulize-lyris-form { display: flex; flex-direction: column; min-height: 100%; }
#formulize-lyris-form > .formulize-lyris-step { flex: 1 0 auto; }
/* each preview is a page drawn at a desktop width, and shrunk to fit its card by the script */
.formulize-lyris__frame { position: relative; overflow: hidden; height: 15rem; background: var(--fz-color-surface); }
.formulize-lyris__frame iframe { position: absolute; top: 0; left: 0; width: 1200px; height: 900px; border: 0; transform-origin: 0 0; pointer-events: none; }
.formulize-lyris__frame iframe[hidden] { display: none; }
/* the look carousel: its arrows are in the preview, at either side, over the page drawn there */
.formulize-lyris__carousel { position: relative; }
.formulize-lyris__arrow { position: absolute; top: 50%; z-index: 1; width: 3.5rem; height: 3.5rem; padding: 0; transform: translateY(-50%); border-radius: var(--fz-radius-full); background: var(--fz-color-surface); }
.formulize-lyris__arrow--previous { left: calc(var(--fz-spacing) * 3); }
.formulize-lyris__arrow--next { right: calc(var(--fz-spacing) * 3); }
/* a chevron looks off centre in its circle when it's centred there, so each is moved towards the way it points */
.formulize-lyris__arrow--previous svg { transform: translateX(-2px); }
.formulize-lyris__arrow--next svg { transform: translateX(2px); }
/* a look's name, in the row under the carousel, is a button with its radio inside, hidden but still focusable */
.formulize-lyris__look-name { cursor: pointer; }
.formulize-lyris__look-name:focus-within { box-shadow: var(--fz-focus-ring); }
.formulize-lyris__logo { max-height: 3.75rem; max-width: 17.5rem; }
.formulize-lyris__icon { width: 2rem; height: 2rem; }
</style>

<div class="formulize-lyris formulize-admin-ui fz-stack fz-gap-3">
    <?php if (!$lyrisSwitching) { ?>
    <p><?php echo _AM_FORMULIZE_LYRIS_INTRO; ?></p>
    <div class="fz-cluster">
        <button type="button" class="fz-btn fz-btn--primary" id="formulize-lyris-open" commandfor="formulize-lyris-wizard" command="show-modal"><?php echo _AM_FORMULIZE_LYRIS_UPDATE_BUTTON; ?></button>
    </div>
    <?php } ?>

    <dialog class="fz-dialog fz-dialog--wide" id="formulize-lyris-wizard" aria-labelledby="formulize-lyris-title" style="width: min(90rem, calc(100vw - var(--fz-spacing) * 8)); height: calc(100vh - var(--fz-spacing) * 8); height: calc(100dvh - var(--fz-spacing) * 8);">
    <form id="formulize-lyris-form" action="<?php echo htmlspecialchars($lyrisFormAction); ?>" method="post">
        <?php if ($lyrisSwitching) { ?>
        <?php echo $GLOBALS['xoopsSecurity']->getTokenHTML('formulize_lyris_switch_token'); ?>
        <input type="hidden" name="formulize_admin_handler" value="lyris_switch">
        <?php } else { ?>
        <?php echo $GLOBALS['xoopsSecurity']->getTokenHTML('formulize_update_token'); ?>
        <input type="hidden" name="patch40" value="1">
        <?php } ?>

        <div class="fz-dialog__header">
            <h2 class="fz-dialog__title" id="formulize-lyris-title"><?php echo $lyrisSwitching ? _AM_FORMULIZE_LYRIS_SWITCH_TITLE : _AM_FORMULIZE_LYRIS_TITLE; ?></h2>
            <span class="fz-text-sm fz-text-muted" id="formulize-lyris-progress"></span>
        </div>

        <?php if (!$lyrisSwitching) { ?>
        <div class="fz-dialog__body formulize-lyris-step" data-step="theme">
            <div class="fz-stack fz-gap-1">
                <h3 class="fz-text-lg fz-font-semibold"><?php echo _AM_FORMULIZE_LYRIS_STEP_THEME; ?></h3>
                <p class="fz-text-muted"><?php echo sprintf(_AM_FORMULIZE_LYRIS_STEP_THEME_INTRO, $lyrisFromName); ?></p>
            </div>
            <?php if ($lyrisPreviewScreens) { ?>
            <?php echo $lyrisPreviewSwitch(); ?>
            <div class="fz-switcher">
                <?php foreach ($lyrisPreviewCaptions as $previewTheme => $caption) { ?>
                <figure class="fz-stack fz-gap-2 fz-m-0">
                    <?php echo $lyrisPreviewFrame($previewTheme, $caption); ?>
                    <figcaption class="fz-text-sm fz-text-muted fz-text-center"><?php echo $caption; ?></figcaption>
                </figure>
                <?php } ?>
            </div>
            <?php if (!$lyrisFromPreviews) { ?>
            <p class="fz-text-sm fz-text-muted"><?php echo sprintf(_AM_FORMULIZE_LYRIS_PREVIEW_NONE, $lyrisFromName); ?></p>
            <?php } ?>
            <?php } ?>
            <fieldset class="fz-field">
                <legend class="fz-field__label"><?php echo _AM_FORMULIZE_LYRIS_CHOICE_LEGEND; ?></legend>
                <div class="fz-auto-grid fz-auto-grid--max-3">
                    <?php foreach ($lyrisThemeChoices as $choice => $label) { ?>
                    <div class="fz-card">
                        <label class="fz-choice"><input type="radio" class="fz-radio" name="lyris_theme_choice" value="<?php echo htmlspecialchars($choice); ?>">
                            <span><span class="fz-font-medium"><?php echo $label; ?></span><br><span class="fz-text-muted"><?php echo $lyrisChoiceDescriptions[$choice]; ?></span></span></label>
                    </div>
                    <?php } ?>
                </div>
            </fieldset>
            <div class="fz-callout fz-callout--warning formulize-lyris-pick-one" role="status" hidden><p><?php echo _AM_FORMULIZE_LYRIS_CHOICE_REQUIRED; ?></p></div>
        </div>
        <?php } ?>

        <?php if (count($lyrisLooks) > 1) { ?>
        <div class="fz-dialog__body formulize-lyris-step" data-step="look" hidden>
            <?php echo $lyrisSwitchIntro('look'); ?>
            <div class="fz-stack fz-gap-1">
                <h3 class="fz-text-lg fz-font-semibold"><?php echo _AM_FORMULIZE_LYRIS_STEP_LOOK; ?></h3>
                <p class="fz-text-muted"><?php echo _AM_FORMULIZE_LYRIS_STEP_LOOK_INTRO; ?></p>
            </div>
            <?php echo $lyrisPreviewSwitch(); ?>
            <div class="formulize-lyris__carousel">
                <?php echo $lyrisPreviewFrame(FORMULIZE_LYRIS_UPDATE_TO, _AM_FORMULIZE_LYRIS_PREVIEW_LYRIS, true); ?>
                <button type="button" class="fz-btn fz-btn--icon fz-btn--lg fz-shadow-md formulize-lyris__arrow formulize-lyris__arrow--previous formulize-lyris-look-move" data-move="-1" aria-label="<?php echo htmlspecialchars(_AM_FORMULIZE_LYRIS_LOOK_PREVIOUS); ?>"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
                <button type="button" class="fz-btn fz-btn--icon fz-btn--lg fz-shadow-md formulize-lyris__arrow formulize-lyris__arrow--next formulize-lyris-look-move" data-move="1" aria-label="<?php echo htmlspecialchars(_AM_FORMULIZE_LYRIS_LOOK_NEXT); ?>"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
            </div>
            <fieldset class="fz-field">
                <legend class="fz-sr-only"><?php echo _AM_FORMULIZE_LYRIS_LOOK_LEGEND; ?></legend>
                <div class="fz-cluster fz-justify-center">
                    <?php foreach ($lyrisLooks as $lookKey => $look) { $isSiteLook = ($lookKey === $lyrisSiteLook); ?>
                    <label class="fz-btn fz-btn--sm formulize-lyris__look-name<?php echo $isSiteLook ? ' fz-btn--primary' : ''; ?>"><input type="radio" class="fz-sr-only" name="lyris_look" value="<?php echo htmlspecialchars($lookKey); ?>"<?php echo $isSiteLook ? ' checked' : ''; ?> data-name="<?php echo $look['name']; ?>" data-description="<?php echo $look['description']; ?>"><?php echo $look['name']; ?></label>
                    <?php } ?>
                </div>
            </fieldset>
            <p class="fz-text-muted fz-text-center formulize-lyris-look-description" aria-live="polite"><?php echo isset($lyrisLooks[$lyrisSiteLook]) ? $lyrisLooks[$lyrisSiteLook]['description'] : ''; ?></p>
            <?php if ($lyrisImageUrls) { ?>
            <div class="fz-cluster fz-gap-4">
                <p><?php echo $lyrisImagesLabel; ?></p>
                <div class="fz-cluster fz-gap-6">
                    <?php if (isset($lyrisImageUrls['appearance_logo'])) { ?><img class="formulize-lyris__logo" src="<?php echo htmlspecialchars($lyrisImageUrls['appearance_logo']); ?>" alt="<?php echo htmlspecialchars(_AM_FORMULIZE_LYRIS_USE_LOGO_ALT); ?>"><?php } ?>
                    <?php if (isset($lyrisImageUrls['appearance_favicon'])) { ?><img class="formulize-lyris__icon" src="<?php echo htmlspecialchars($lyrisImageUrls['appearance_favicon']); ?>" alt="<?php echo htmlspecialchars(_AM_FORMULIZE_LYRIS_USE_ICON_ALT); ?>"><?php } ?>
                </div>
            </div>
            <?php } ?>
            <p class="fz-text-muted"><?php echo _AM_FORMULIZE_LYRIS_STEP_LOOK_LATER; ?></p>
        </div>
        <?php } ?>

        <?php if ($lyrisSettings) { ?>
        <div class="fz-dialog__body formulize-lyris-step" data-step="settings" hidden>
            <?php echo $lyrisSwitchIntro('settings'); ?>
            <div class="fz-stack fz-gap-2">
                <h3 class="fz-text-lg fz-font-semibold"><?php echo _AM_FORMULIZE_LYRIS_STEP_SETTINGS; ?></h3>
                <p class="fz-text-muted"><?php echo _AM_FORMULIZE_LYRIS_STEP_SETTINGS_INTRO; ?></p>
            </div>
            <?php foreach ($lyrisSettings as $type => $offered) { ?>
            <div class="fz-card fz-card--flush fz-overflow-x-auto">
                <table class="fz-table fz-table--multiline">
                    <thead><tr>
                        <th scope="col"><?php echo htmlspecialchars(isset($lyrisScreenTypes[$type]) ? $lyrisScreenTypes[$type]['name'] : $type); ?></th>
                        <?php foreach ($offered['columns'] as $property => $label) { ?>
                        <th scope="col"><label class="fz-choice"><input type="checkbox" class="fz-checkbox formulize-lyris-setting-all" data-column="<?php echo htmlspecialchars($type . '-' . $property); ?>"<?php echo $lyrisSwitching ? ' checked' : ''; ?>> <?php echo $label; ?></label></th>
                        <?php } ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($offered['screens'] as $sid => $properties) { ?>
                        <tr>
                            <td><span class="fz-font-medium"><?php echo $lyrisScreens[$sid]['title']; ?></span><br><span class="fz-text-muted"><?php echo $lyrisScreens[$sid]['where']; ?></span></td>
                            <?php foreach ($offered['columns'] as $property => $label) { ?>
                            <td><?php if (in_array($property, $properties)) { ?><input type="checkbox" class="fz-checkbox formulize-lyris-setting" data-column="<?php echo htmlspecialchars($type . '-' . $property); ?>" name="lyris_screen_settings[<?php echo intval($sid); ?>][]" value="<?php echo htmlspecialchars($property); ?>"<?php echo $lyrisSwitching ? ' checked' : ''; ?> aria-label="<?php echo htmlspecialchars(strip_tags($lyrisScreens[$sid]['title'] . ': ' . $label)); ?>"><?php } ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>
            <p class="fz-text-muted"><?php echo _AM_FORMULIZE_LYRIS_STEP_SETTINGS_LATER; ?></p>
        </div>
        <?php } ?>

        <?php if ($lyrisTemplateGroups) { ?>
        <div class="fz-dialog__body formulize-lyris-step" data-step="templates" hidden>
            <?php echo $lyrisSwitchIntro('templates'); ?>
            <div class="fz-stack fz-gap-2">
                <h3 class="fz-text-lg fz-font-semibold"><?php echo _AM_FORMULIZE_LYRIS_STEP_TEMPLATES; ?></h3>
                <p class="fz-text-muted"><?php echo _AM_FORMULIZE_LYRIS_STEP_TEMPLATES_INTRO; ?></p>
            </div>
            <div class="fz-cluster">
                <button type="button" class="fz-btn fz-btn--sm formulize-lyris-templates-select" data-select="1"><?php echo _AM_FORMULIZE_LYRIS_SELECT_ALL; ?></button>
                <button type="button" class="fz-btn fz-btn--sm formulize-lyris-templates-select" data-select="0"><?php echo _AM_FORMULIZE_LYRIS_SELECT_NONE; ?></button>
            </div>
            <div class="fz-auto-grid fz-auto-grid--wide">
                <?php foreach ($lyrisTemplateGroups as $where => $groupScreens) { ?>
                <fieldset class="fz-card fz-field">
                    <legend class="fz-field__label"><?php echo $where; ?></legend>
                    <div class="fz-choice-group">
                        <?php foreach ($groupScreens as $sid => $title) { ?>
                        <label class="fz-choice"><input type="checkbox" class="fz-checkbox formulize-lyris-template-screen" name="lyris_copy_templates[]" value="<?php echo intval($sid); ?>" checked> <?php echo $title; ?></label>
                        <?php } ?>
                    </div>
                </fieldset>
                <?php } ?>
            </div>
            <p class="fz-text-muted"><?php echo _AM_FORMULIZE_LYRIS_STEP_TEMPLATES_LATER; ?></p>
        </div>
        <?php } ?>

        <div class="fz-dialog__body formulize-lyris-step" data-step="summary" hidden>
            <div class="fz-stack fz-gap-2">
                <h3 class="fz-text-lg fz-font-semibold"><?php echo $lyrisSwitching ? _AM_FORMULIZE_LYRIS_SWITCH_SUMMARY : _AM_FORMULIZE_LYRIS_STEP_SUMMARY; ?></h3>
                <p class="fz-text-muted formulize-lyris-summary-intro"><?php echo $lyrisSwitching ? _AM_FORMULIZE_LYRIS_SWITCH_SUMMARY_INTRO : _AM_FORMULIZE_LYRIS_STEP_SUMMARY_INTRO; ?></p>
                <p class="formulize-lyris-summary-keep" hidden><?php echo _AM_FORMULIZE_LYRIS_STEP_SUMMARY_KEEP; ?></p>
            </div>
            <ul class="fz-list-none fz-stack fz-gap-2 formulize-lyris-summary"></ul>
            <?php foreach ($lyrisSwitching ? array('switch') : array_keys($lyrisThemeChoices) as $choice) { ?>
            <div class="fz-callout formulize-lyris-after" data-choice="<?php echo htmlspecialchars($choice); ?>" hidden>
                <p class="fz-callout__title"><?php echo $lyrisSwitching ? _AM_FORMULIZE_LYRIS_SWITCH_AFTER_TITLE : _AM_FORMULIZE_LYRIS_AFTER_TITLE; ?></p>
                <p><?php echo sprintf(constant('_AM_FORMULIZE_LYRIS_AFTER_' . strtoupper($choice)), $lyrisFromName); ?></p>
            </div>
            <?php } ?>
            <div class="fz-callout fz-callout--warning"><p><?php echo $lyrisSwitching ? _AM_FORMULIZE_LYRIS_SWITCH_SUMMARY_BACKUP : _AM_FORMULIZE_LYRIS_STEP_SUMMARY_BACKUP; ?></p></div>
            <?php if ($lyrisSwitching) { ?>
            <div class="fz-callout fz-callout--danger formulize-lyris-problems" role="alert" hidden>
                <p class="fz-callout__title"><?php echo _AM_FORMULIZE_LYRIS_SWITCH_PROBLEMS; ?></p>
                <div class="formulize-lyris-problems__list"></div>
            </div>
            <?php } ?>
        </div>

        <div class="fz-dialog__footer">
            <button type="button" class="fz-btn" commandfor="formulize-lyris-wizard" command="close"><?php echo _AM_FORMULIZE_JS_LYRIS_CANCEL; ?></button>
            <?php if ($lyrisSwitching) { ?>
            <button type="button" class="fz-btn fz-btn--ghost" id="formulize-lyris-plain"><?php echo _AM_FORMULIZE_LYRIS_SWITCH_PLAIN; ?></button>
            <?php } ?>
            <span class="fz-spacer"></span>
            <button type="button" class="fz-btn" id="formulize-lyris-back" hidden><?php echo _AM_FORMULIZE_JS_LYRIS_BACK; ?></button>
            <button type="button" class="fz-btn fz-btn--primary" id="formulize-lyris-next"><?php echo _AM_FORMULIZE_JS_LYRIS_NEXT; ?></button>
            <button type="submit" class="fz-btn fz-btn--primary" id="formulize-lyris-update" hidden><?php echo $lyrisSwitching ? _AM_FORMULIZE_LYRIS_SWITCH_BUTTON : _AM_FORMULIZE_JS_LYRIS_UPDATE; ?></button>
        </div>
    </form>
    </dialog>
</div>

<script type="text/javascript">
(function() {
    var text = <?php echo json_encode(formulize_scriptText('_AM_FORMULIZE_JS_LYRIS_'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var previewUrl = <?php echo json_encode($lyrisPreviewUrl); ?>;
    var previewScreen = <?php echo json_encode($lyrisPreviewScreen); ?>;
    // the previews of Lyris show the site's own logo while it's being brought along, from a theme with Appearance
    // settings, since the previews read it from those (and reading them would give another theme an appearance folder)
    var logoTheme = <?php echo json_encode((isset($lyrisImageUrls['appearance_logo']) AND formulize_themeSupportsAppearance($lyrisFromTheme)) ? $lyrisFromTheme : ''); ?>;
    var hasImages = <?php echo $lyrisImageUrls ? 'true' : 'false'; ?>;
    var imagesSummary =<?php echo json_encode(isset($lyrisImageUrls['appearance_logo']) ? (isset($lyrisImageUrls['appearance_favicon']) ? 'SUMMARY_LOGO_AND_ICON' : 'SUMMARY_LOGO') : 'SUMMARY_ICON'); ?>;
    var templateScreenCount = <?php echo intval(count($lyrisTemplateScreens)); ?>;
    // the theme the site is moving from, which some of the words name
    var fromName = <?php echo json_encode($lyrisFromName); ?>;
    // switching on Appearance > Settings, where Lyris was chosen already, or else the update
    var switching = <?php echo $lyrisSwitching ? 'true' : 'false'; ?>;
    var firstStep = <?php echo json_encode($lyrisFirstStep); ?>;
    var wizard = document.getElementById('formulize-lyris-wizard');
    var form = document.getElementById('formulize-lyris-form');
    var stepOrder = ['theme', 'look', 'settings', 'templates', 'summary'];
    var current = firstStep;

    function all(selector) {
        return Array.prototype.slice.call(wizard.querySelectorAll(selector));
    }
    function fill(words) {
        var values = Array.prototype.slice.call(arguments, 1);
        return String(words).replace(/%s/g, function() { return values.length ? values.shift() : ''; });
    }
    function checkedValue(name) {
        var input = wizard.querySelector('input[name="' + name + '"]:checked');
        return input ? input.value : '';
    }
    function choice() {
        return switching ? 'switch' : checkedValue('lyris_theme_choice');
    }
    function usingLyris() {
        return choice() !== '' && choice() !== 'keep';
    }
    // the site's own logo and icon come along whenever Lyris is chosen
    function useImages() {
        return hasImages && usingLyris();
    }
    function stepElement(step) {
        return wizard.querySelector('.formulize-lyris-step[data-step="' + step + '"]');
    }
    function steps() {
        return stepOrder.filter(function(step) {
            // the look, the screen settings and the templates are only asked about when Lyris is chosen
            return stepElement(step) && (step == 'theme' || step == 'summary' || usingLyris());
        });
    }

    function previewSource(frame) {
        var theme = frame.getAttribute('data-theme');
        var url = previewUrl + '?theme=' + encodeURIComponent(theme) + '&screen=' + encodeURIComponent(frame.getAttribute('data-screen'));
        if (frame.hasAttribute('data-look') && checkedValue('lyris_look')) {
            url += '&look=' + encodeURIComponent(checkedValue('lyris_look'));
        }
        if (logoTheme !== '' && theme != logoTheme && (current == 'theme' || useImages())) {
            url += '&logofrom=' + encodeURIComponent(logoTheme);
        }
        return url;
    }
    // as wide as its card, and no taller than 60% of the window, so what is under it can be seen too; centred in
    // its card when the height is what limits it
    function drawPreviews() {
        all('.formulize-lyris-step[data-step="' + current + '"] .formulize-lyris__frame iframe').forEach(function(frame) {
            var source = previewSource(frame);
            if (frame.getAttribute('src') != source) {
                frame.setAttribute('src', source);
            }
            frame.hidden = (frame.getAttribute('data-screen') != previewScreen);
            var box = frame.parentNode;
            var scale = Math.min(box.clientWidth / 1200, (window.innerHeight * 0.6) / 900);
            frame.style.transform = 'scale(' + scale + ')';
            frame.style.left = Math.max(0, Math.round((box.clientWidth - 1200 * scale) / 2)) + 'px';
            box.style.height = Math.round(900 * scale) + 'px';
        });
    }

    // the switch between the samples: the one shown is the main button, in every step that has previews
    function showPreviewScreen(screen) {
        previewScreen = screen;
        all('.formulize-lyris-preview-screen').forEach(function(button) {
            var on = (button.getAttribute('data-screen') == screen);
            button.setAttribute('aria-pressed', on ? 'true' : 'false');
            button.classList.toggle('fz-btn--primary', on);
        });
        drawPreviews();
    }

    // the look carousel: the chosen look's name is the main button, and its description is under the names
    function showLook() {
        all('input[name="lyris_look"]').forEach(function(radio) {
            radio.parentNode.classList.toggle('fz-btn--primary', radio.checked);
            if (radio.checked) {
                wizard.querySelector('.formulize-lyris-look-description').textContent = radio.getAttribute('data-description');
            }
        });
        drawPreviews();
    }
    function moveLook(by) {
        var radios = all('input[name="lyris_look"]');
        var at = radios.findIndex(function(radio) { return radio.checked; });
        radios[(at + by + radios.length) % radios.length].checked = true;
        showLook();
    }

    function summaryItem(list, words) {
        var item = document.createElement('li');
        item.innerHTML = words;
        list.appendChild(item);
    }
    function summarize() {
        var list = wizard.querySelector('.formulize-lyris-summary');
        list.innerHTML = '';
        all('.formulize-lyris-after').forEach(function(after) {
            after.hidden = (after.getAttribute('data-choice') != choice());
        });
        // keeping the theme changes nothing, so there's nothing to list
        wizard.querySelector('.formulize-lyris-summary-intro').hidden = !usingLyris();
        wizard.querySelector('.formulize-lyris-summary-keep').hidden = usingLyris();
        list.hidden = !usingLyris();
        if (!usingLyris()) {
            return;
        }
        summaryItem(list, fill(text['SUMMARY_' + choice().toUpperCase()], fromName));
        if (usingLyris()) {
            var look = wizard.querySelector('input[name="lyris_look"]:checked');
            if (look && all('input[name="lyris_look"]').length > 1) {
                summaryItem(list, fill(text.SUMMARY_LOOK, look.getAttribute('data-name')));
            }
            if (useImages()) {
                summaryItem(list, text[imagesSummary]);
            }
        }
        if (all('.formulize-lyris-setting').length) {
            var screens = {};
            var count = 0;
            all('.formulize-lyris-setting:checked').forEach(function(box) {
                if (!screens[box.name]) {
                    screens[box.name] = true;
                    count++;
                }
            });
            summaryItem(list, count ? fill(count == 1 ? text.SUMMARY_SETTINGS_ONE : text.SUMMARY_SETTINGS, count) : text.SUMMARY_SETTINGS_NONE);
        }
        if (all('.formulize-lyris-template-screen').length) {
            var templates = all('.formulize-lyris-template-screen:checked').length;
            summaryItem(list, templates ? fill(templates == 1 ? text.SUMMARY_TEMPLATES_ONE : text.SUMMARY_TEMPLATES, templates) : text.SUMMARY_TEMPLATES_NONE);
        }
        if (templateScreenCount) {
            summaryItem(list, fill(templateScreenCount == 1 ? text.SUMMARY_TEMPLATE_SCREENS_ONE : text.SUMMARY_TEMPLATE_SCREENS, templateScreenCount));
        }
    }

    function show(step) {
        current = step;
        var order = steps();
        var at = order.indexOf(step);
        all('.formulize-lyris-step').forEach(function(element) {
            element.hidden = (element.getAttribute('data-step') != step);
        });
        if (step == 'summary') {
            summarize();
        }
        document.getElementById('formulize-lyris-progress').textContent = fill(text.STEP, at + 1, order.length);
        document.getElementById('formulize-lyris-back').hidden = (at < 1);
        document.getElementById('formulize-lyris-next').hidden = (step == 'summary');
        document.getElementById('formulize-lyris-update').hidden = (step != 'summary');
        wizard.scrollTop = 0;
        drawPreviews();
    }

    // the update's button opens the dialog by itself (commandfor), so the questions start again from the first one
    if (document.getElementById('formulize-lyris-open')) {
        document.getElementById('formulize-lyris-open').addEventListener('click', function() {
            setTimeout(function() {
                show(firstStep);
            }, 0);
        });
    }
    document.getElementById('formulize-lyris-next').addEventListener('click', function() {
        if (current == 'theme' && choice() === '') {
            wizard.querySelector('.formulize-lyris-pick-one').hidden = false;
            return;
        }
        show(steps()[steps().indexOf(current) + 1]);
    });
    document.getElementById('formulize-lyris-back').addEventListener('click', function() {
        show(steps()[steps().indexOf(current) - 1]);
    });
    // once the update is on its way, there's no going back: every button is off, and Esc doesn't close the dialog
    var updating = false;
    function working(on) {
        updating = on;
        all('.fz-dialog__footer button').forEach(function(button) {
            button.disabled = on;
        });
    }
    form.addEventListener('submit', function(event) {
        working(true);
        if (switching) {
            event.preventDefault();
            sendSwitch();
        }
    });
    wizard.addEventListener('cancel', function(event) {
        if (updating) {
            event.preventDefault();
        }
    });

    // Switching on Appearance > Settings: saving the settings with Lyris as the site's theme opens the questions
    // instead. The answers go to the switch's save handler, which switches the theme when it has done the rest, and
    // says nothing when it all worked. Then the settings are saved as they would have been, with the theme already
    // changed. Switching without the changes saves the settings as they are, which switches the theme the plain way.
    var settingsForm = document.getElementById('formulize-config-settings-form');
    var siteTheme = settingsForm ? settingsForm.querySelector('[name="theme_set"]') : null;
    var lyris = <?php echo json_encode(FORMULIZE_LYRIS_UPDATE_TO); ?>;
    var settingsConfirmed = false;
    function saveSettings() {
        settingsConfirmed = true;
        if (settingsForm.requestSubmit) {
            settingsForm.requestSubmit();
        } else {
            settingsForm.submit();
        }
    }
    function showProblems(said) {
        var problems = wizard.querySelector('.formulize-lyris-problems');
        problems.querySelector('.formulize-lyris-problems__list').innerHTML = said;
        problems.hidden = false;
        problems.scrollIntoView({ block: 'nearest' });
        working(false);
    }
    function sendSwitch() {
        wizard.querySelector('.formulize-lyris-problems').hidden = true;
        // sent as the admin's other saves are, so the form's token is still good if something has to be tried again
        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function(response) {
            if (!response.ok) {
                throw new Error(response.status);
            }
            return response.text();
        }).then(function(said) {
            if (said.replace(/^\s+|\s+$/g, '') === '') {
                saveSettings();
            } else {
                showProblems(said);
            }
        }).catch(function() {
            showProblems('<p>' + text.SWITCH_UNREACHABLE + '</p>');
        });
    }
    if (switching && siteTheme) {
        settingsForm.addEventListener('submit', function(event) {
            if (settingsConfirmed || siteTheme.value !== lyris) {
                return;
            }
            event.preventDefault();
            wizard.showModal();
            show(firstStep);
        });
        document.getElementById('formulize-lyris-plain').addEventListener('click', function() {
            working(true);
            saveSettings();
        });
    }

    // Lyris's settings start checked for screens when the whole site switches, and unchecked otherwise
    all('input[name="lyris_theme_choice"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            wizard.querySelector('.formulize-lyris-pick-one').hidden = true;
            var on = (choice() == 'switch');
            all('.formulize-lyris-setting, .formulize-lyris-setting-all').forEach(function(box) {
                box.checked = on;
            });
        });
    });
    all('.formulize-lyris-setting-all').forEach(function(columnBox) {
        columnBox.addEventListener('change', function() {
            all('.formulize-lyris-setting[data-column="' + columnBox.getAttribute('data-column') + '"]').forEach(function(box) {
                box.checked = columnBox.checked;
            });
        });
    });
    all('.formulize-lyris-templates-select').forEach(function(button) {
        button.addEventListener('click', function() {
            var on = (button.getAttribute('data-select') == '1');
            all('.formulize-lyris-template-screen').forEach(function(box) {
                box.checked = on;
            });
        });
    });
    all('.formulize-lyris-preview-screen').forEach(function(button) {
        button.addEventListener('click', function() {
            showPreviewScreen(button.getAttribute('data-screen'));
        });
    });
    all('.formulize-lyris-look-move').forEach(function(button) {
        button.addEventListener('click', function() {
            moveLook(parseInt(button.getAttribute('data-move'), 10));
        });
    });
    all('input[name="lyris_look"]').forEach(function(input) {
        input.addEventListener('change', showLook);
    });
    window.addEventListener('resize', function() {
        if (wizard.open) {
            drawPreviews();
        }
    });
})();
</script>
