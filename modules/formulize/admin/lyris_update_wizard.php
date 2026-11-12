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

// The 8.30 update's questions about Lyris, on the update page, for a webmaster on a site that uses Anari (see
// include/lyris_update.php). The Update Formulize button opens them in a dialog, and the last step submits the update
// with the answers. Nothing changes until then, and Cancel changes nothing, so on a site that hasn't been updated yet,
// the questions can be opened and closed as often as you like.
//
// Included by admin/op.php. It runs before the update, so it can't use a Smarty template from the database, since a
// new template only gets there when the update runs. The admin UI runs jQuery 1.4.2 and jQuery UI 1.8.2.

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

include_once XOOPS_ROOT_PATH . '/modules/formulize/include/lyris_update.php';

$lyrisScenario = formulize_lyrisUpdateScenario();
$lyrisThemeChoices = formulize_lyrisUpdateThemeChoices();
$lyrisChoiceDescriptions = array(
    'switch' => _AM_FORMULIZE_LYRIS_CHOICE_SWITCH_DESC,
    'try' => _AM_FORMULIZE_LYRIS_CHOICE_TRY_DESC,
    'keep' => $lyrisScenario == 'site' ? _AM_FORMULIZE_LYRIS_CHOICE_KEEP_DESC : _AM_FORMULIZE_LYRIS_CHOICE_KEEP_ADMIN_DESC,
    'admin' => _AM_FORMULIZE_LYRIS_CHOICE_ADMIN_DESC,
);
$lyrisLooks = formulize_lyrisUpdateLooks();
$lyrisSiteLook = formulize_getAppearanceSiteLook(FORMULIZE_LYRIS_UPDATE_TO);
// the site's own logo and browser icon, to bring along to Lyris, with the addresses to show them at
$lyrisImageUrls = array();
$lyrisRootPath = str_replace('\\', '/', XOOPS_ROOT_PATH);
foreach (formulize_lyrisUpdateImagesToCarry() as $setting => $imagePath) {
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

// the sample screens both themes have, to compare them by
$lyrisPreviewScreens = array_intersect_key(formulize_getAppearancePreviewScreens(FORMULIZE_LYRIS_UPDATE_FROM), formulize_getAppearancePreviewScreens(FORMULIZE_LYRIS_UPDATE_TO));
$lyrisPreviewScreen = isset($lyrisPreviewScreens['list']) ? 'list' : (string) key($lyrisPreviewScreens);
$lyrisPreviewUrl = XOOPS_URL . '/modules/formulize/appearance_preview.php';

// the screens with templates to copy, by where they are
$lyrisTemplateGroups = array();
foreach ($lyrisScreens as $sid => $screenInfo) {
    if (isset($lyrisTemplates[$sid])) {
        $lyrisTemplateGroups[$screenInfo['where']][$sid] = $screenInfo['title'];
    }
}

$lyrisFormAction = XOOPS_URL . '/modules/formulize/admin/ui.php?op=patchDB';
?>
<p><?php echo $lyrisScenario == 'site' ? _AM_FORMULIZE_LYRIS_INTRO : _AM_FORMULIZE_LYRIS_INTRO_ADMIN; ?></p>
<p><input type="button" id="formulize-lyris-open" value="<?php echo htmlspecialchars(_AM_FORMULIZE_LYRIS_UPDATE_BUTTON); ?>"></p>

<div id="formulize-lyris-wizard" title="<?php echo htmlspecialchars(_AM_FORMULIZE_LYRIS_TITLE); ?>" style="display: none;">
<form id="formulize-lyris-form" action="<?php echo htmlspecialchars($lyrisFormAction); ?>" method="post">
    <?php echo $GLOBALS['xoopsSecurity']->getTokenHTML('formulize_update_token'); ?>
    <input type="hidden" name="patch40" value="1">

    <div class="lyris-step" data-step="theme">
        <h3><?php echo _AM_FORMULIZE_LYRIS_STEP_THEME; ?></h3>
        <p><?php echo _AM_FORMULIZE_LYRIS_STEP_THEME_INTRO; ?></p>
        <?php if ($lyrisPreviewScreens) { ?>
        <div class="lyris-previews">
            <?php foreach (array(FORMULIZE_LYRIS_UPDATE_FROM, FORMULIZE_LYRIS_UPDATE_TO) as $previewTheme) {
                $caption = $previewTheme == FORMULIZE_LYRIS_UPDATE_FROM
                    ? ($lyrisScenario == 'site' ? _AM_FORMULIZE_LYRIS_PREVIEW_ANARI : _AM_FORMULIZE_LYRIS_PREVIEW_ANARI_ADMIN)
                    : _AM_FORMULIZE_LYRIS_PREVIEW_LYRIS;
                ?>
            <div class="lyris-preview">
                <div class="lyris-preview-frame"><iframe tabindex="-1" title="<?php echo htmlspecialchars(strip_tags($caption)); ?>" data-theme="<?php echo htmlspecialchars($previewTheme); ?>"></iframe></div>
                <p class="lyris-preview-caption"><?php echo $caption; ?></p>
            </div>
            <?php } ?>
        </div>
        <?php if (count($lyrisPreviewScreens) > 1) { ?>
        <p class="lyris-preview-switch"><?php echo _AM_FORMULIZE_LYRIS_PREVIEW_SHOW; ?>
            <?php $first = true; foreach ($lyrisPreviewScreens as $previewKey => $previewName) { ?>
                <?php echo $first ? '' : ' &middot; '; $first = false; ?><a href="#" class="lyris-preview-screen<?php echo $previewKey == $lyrisPreviewScreen ? ' lyris-current' : ''; ?>" data-screen="<?php echo htmlspecialchars($previewKey); ?>"><?php echo htmlspecialchars($previewName); ?></a>
            <?php } ?>
        </p>
        <?php } ?>
        <?php } ?>
        <fieldset class="lyris-choices">
            <legend><?php echo _AM_FORMULIZE_LYRIS_CHOICE_LEGEND; ?></legend>
            <?php foreach ($lyrisThemeChoices as $choice => $label) { ?>
            <label class="lyris-choice"><input type="radio" name="lyris_theme_choice" value="<?php echo htmlspecialchars($choice); ?>">
                <strong><?php echo $label; ?></strong><br><span class="lyris-description"><?php echo $lyrisChoiceDescriptions[$choice]; ?></span></label>
            <?php } ?>
        </fieldset>
        <p class="lyris-pick-one" style="display: none;"><?php echo _AM_FORMULIZE_LYRIS_CHOICE_REQUIRED; ?></p>
    </div>

    <?php if (count($lyrisLooks) > 1 OR $lyrisImageUrls) { ?>
    <div class="lyris-step" data-step="look">
        <h3><?php echo _AM_FORMULIZE_LYRIS_STEP_LOOK; ?></h3>
        <?php if (count($lyrisLooks) > 1) { ?>
        <p><?php echo _AM_FORMULIZE_LYRIS_STEP_LOOK_INTRO; ?></p>
        <div class="lyris-previews">
            <div class="lyris-preview lyris-preview-wide">
                <div class="lyris-preview-frame"><iframe tabindex="-1" title="<?php echo htmlspecialchars(_AM_FORMULIZE_LYRIS_PREVIEW_LYRIS); ?>" data-theme="<?php echo htmlspecialchars(FORMULIZE_LYRIS_UPDATE_TO); ?>" data-look="1"></iframe></div>
            </div>
        </div>
        <fieldset class="lyris-choices">
            <legend><?php echo _AM_FORMULIZE_LYRIS_LOOK_LEGEND; ?></legend>
            <?php foreach ($lyrisLooks as $lookKey => $lookName) { ?>
            <label class="lyris-choice"><input type="radio" name="lyris_look" value="<?php echo htmlspecialchars($lookKey); ?>"<?php echo $lookKey === $lyrisSiteLook ? ' checked="checked"' : ''; ?> data-name="<?php echo $lookName; ?>"> <?php echo $lookName; ?></label>
            <?php } ?>
        </fieldset>
        <?php } ?>
        <?php if ($lyrisImageUrls) { ?>
        <p><label><input type="checkbox" name="lyris_use_images" value="1" checked="checked"> <?php echo $lyrisImagesLabel; ?></label></p>
        <p class="lyris-images">
            <?php if (isset($lyrisImageUrls['appearance_logo'])) { ?><img class="lyris-image-logo" src="<?php echo htmlspecialchars($lyrisImageUrls['appearance_logo']); ?>" alt="<?php echo htmlspecialchars(_AM_FORMULIZE_LYRIS_USE_LOGO_ALT); ?>"><?php } ?>
            <?php if (isset($lyrisImageUrls['appearance_favicon'])) { ?><img class="lyris-image-icon" src="<?php echo htmlspecialchars($lyrisImageUrls['appearance_favicon']); ?>" alt="<?php echo htmlspecialchars(_AM_FORMULIZE_LYRIS_USE_ICON_ALT); ?>"><?php } ?>
        </p>
        <?php } ?>
        <p class="lyris-description"><?php echo _AM_FORMULIZE_LYRIS_STEP_LOOK_LATER; ?></p>
    </div>
    <?php } ?>

    <?php if ($lyrisSettings) { ?>
    <div class="lyris-step" data-step="settings">
        <h3><?php echo _AM_FORMULIZE_LYRIS_STEP_SETTINGS; ?></h3>
        <p><?php echo _AM_FORMULIZE_LYRIS_STEP_SETTINGS_INTRO; ?></p>
        <?php foreach ($lyrisSettings as $type => $offered) { ?>
        <table class="lyris-settings">
            <thead><tr>
                <th><?php echo htmlspecialchars(isset($lyrisScreenTypes[$type]) ? $lyrisScreenTypes[$type]['name'] : $type); ?></th>
                <?php foreach ($offered['columns'] as $property => $label) { ?>
                <th><label><input type="checkbox" class="lyris-setting-all" data-column="<?php echo htmlspecialchars($type . '-' . $property); ?>"> <?php echo $label; ?></label></th>
                <?php } ?>
            </tr></thead>
            <tbody>
            <?php foreach ($offered['screens'] as $sid => $properties) { ?>
                <tr>
                    <td><?php echo $lyrisScreens[$sid]['title']; ?><br><span class="lyris-description"><?php echo $lyrisScreens[$sid]['where']; ?></span></td>
                    <?php foreach (array_keys($offered['columns']) as $property) { ?>
                    <td><?php if (in_array($property, $properties)) { ?><input type="checkbox" class="lyris-setting" data-column="<?php echo htmlspecialchars($type . '-' . $property); ?>" name="lyris_screen_settings[<?php echo intval($sid); ?>][]" value="<?php echo htmlspecialchars($property); ?>" title="<?php echo htmlspecialchars(strip_tags($offered['columns'][$property])); ?>"><?php } ?></td>
                    <?php } ?>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php } ?>
        <p class="lyris-description"><?php echo _AM_FORMULIZE_LYRIS_STEP_SETTINGS_LATER; ?></p>
    </div>
    <?php } ?>

    <?php if ($lyrisTemplateGroups) { ?>
    <div class="lyris-step" data-step="templates">
        <h3><?php echo _AM_FORMULIZE_LYRIS_STEP_TEMPLATES; ?></h3>
        <p><?php echo _AM_FORMULIZE_LYRIS_STEP_TEMPLATES_INTRO; ?></p>
        <p><a href="#" class="lyris-templates-select" data-select="1"><?php echo _AM_FORMULIZE_LYRIS_SELECT_ALL; ?></a> &middot; <a href="#" class="lyris-templates-select" data-select="0"><?php echo _AM_FORMULIZE_LYRIS_SELECT_NONE; ?></a></p>
        <?php foreach ($lyrisTemplateGroups as $where => $groupScreens) { ?>
        <fieldset class="lyris-template-group">
            <legend><?php echo $where; ?></legend>
            <?php foreach ($groupScreens as $sid => $title) { ?>
            <label class="lyris-template"><input type="checkbox" class="lyris-template-screen" name="lyris_copy_templates[]" value="<?php echo intval($sid); ?>" checked="checked"> <?php echo $title; ?></label>
            <?php } ?>
        </fieldset>
        <?php } ?>
        <p class="lyris-description"><?php echo _AM_FORMULIZE_LYRIS_STEP_TEMPLATES_LATER; ?></p>
    </div>
    <?php } ?>

    <div class="lyris-step" data-step="summary">
        <h3><?php echo _AM_FORMULIZE_LYRIS_STEP_SUMMARY; ?></h3>
        <p><?php echo _AM_FORMULIZE_LYRIS_STEP_SUMMARY_INTRO; ?></p>
        <ul class="lyris-summary"></ul>
        <h4><?php echo _AM_FORMULIZE_LYRIS_AFTER_TITLE; ?></h4>
        <?php foreach (array_keys($lyrisThemeChoices) as $choice) { ?>
        <div class="lyris-after" data-choice="<?php echo htmlspecialchars($choice); ?>"><?php echo constant('_AM_FORMULIZE_LYRIS_AFTER_' . strtoupper($choice)); ?></div>
        <?php } ?>
        <p class="lyris-description"><?php echo _AM_FORMULIZE_LYRIS_STEP_SUMMARY_BACKUP; ?></p>
    </div>
</form>
</div>

<style>
    #formulize-lyris-wizard { font-size: 13px; line-height: 1.45; }
    #formulize-lyris-wizard h3 { margin: 0 0 0.6em; }
    #formulize-lyris-wizard .lyris-previews { display: flex; gap: 16px; margin: 0.8em 0; }
    #formulize-lyris-wizard .lyris-preview { flex: 1 1 0; min-width: 0; }
    #formulize-lyris-wizard .lyris-preview-frame { position: relative; overflow: hidden; height: 260px; border: 1px solid #c8c8c8; border-radius: 4px; background: #fff; }
    #formulize-lyris-wizard .lyris-preview-frame iframe { position: absolute; top: 0; left: 0; width: 1200px; height: 900px; border: 0; transform-origin: 0 0; pointer-events: none; }
    #formulize-lyris-wizard .lyris-preview-caption { margin: 0.4em 0 0; text-align: center; }
    #formulize-lyris-wizard .lyris-preview-switch { text-align: center; }
    #formulize-lyris-wizard .lyris-preview-switch .lyris-current { font-weight: bold; text-decoration: none; }
    #formulize-lyris-wizard .lyris-choices { border: 0; padding: 0; margin: 1em 0; }
    #formulize-lyris-wizard .lyris-choices legend { font-weight: bold; padding: 0; margin-bottom: 0.4em; }
    #formulize-lyris-wizard .lyris-choice { display: block; margin: 0 0 0.6em; cursor: pointer; }
    #formulize-lyris-wizard .lyris-description { color: #555; }
    #formulize-lyris-wizard .lyris-pick-one { color: #9a3b00; }
    #formulize-lyris-wizard .lyris-images img { vertical-align: middle; margin-right: 24px; }
    #formulize-lyris-wizard .lyris-images .lyris-image-logo { max-height: 60px; max-width: 280px; }
    #formulize-lyris-wizard .lyris-images .lyris-image-icon { width: 32px; height: 32px; }
    #formulize-lyris-wizard table.lyris-settings { width: 100%; border-collapse: collapse; margin: 0 0 1.2em; }
    #formulize-lyris-wizard table.lyris-settings th, #formulize-lyris-wizard table.lyris-settings td { border-bottom: 1px solid #e2e2e2; padding: 6px 8px; text-align: left; vertical-align: top; }
    #formulize-lyris-wizard table.lyris-settings th label { font-weight: bold; cursor: pointer; }
    #formulize-lyris-wizard .lyris-template-group { border: 1px solid #e2e2e2; border-radius: 4px; margin: 0 0 0.8em; padding: 0.4em 0.8em; }
    #formulize-lyris-wizard .lyris-template-group legend { font-weight: bold; padding: 0 0.3em; }
    #formulize-lyris-wizard .lyris-template { display: block; cursor: pointer; }
    #formulize-lyris-wizard .lyris-summary li { margin-bottom: 0.3em; }
    #formulize-lyris-wizard .lyris-after { margin-bottom: 1em; }
    @media (max-width: 700px) {
        #formulize-lyris-wizard .lyris-previews { flex-direction: column; }
    }
</style>

<script type="text/javascript">
jQuery(document).ready(function() {
    var $ = jQuery;
    var text = <?php echo json_encode(formulize_scriptText('_AM_FORMULIZE_JS_LYRIS_')); ?>;
    var previewUrl = <?php echo json_encode($lyrisPreviewUrl); ?>;
    var previewScreen = <?php echo json_encode($lyrisPreviewScreen); ?>;
    // the previews of Lyris show the site's own logo while it's being brought along
    var logoTheme = <?php echo json_encode(isset($lyrisImageUrls['appearance_logo']) ? FORMULIZE_LYRIS_UPDATE_FROM : ''); ?>;
    var imagesSummary = <?php echo json_encode(isset($lyrisImageUrls['appearance_logo']) ? (isset($lyrisImageUrls['appearance_favicon']) ? 'SUMMARY_LOGO_AND_ICON' : 'SUMMARY_LOGO') : 'SUMMARY_ICON'); ?>;
    var templateScreenCount = <?php echo intval(count($lyrisTemplateScreens)); ?>;
    var wizard = $('#formulize-lyris-wizard');
    var stepOrder = ['theme', 'look', 'settings', 'templates', 'summary'];
    var current = 'theme';

    function fill(words, value) {
        return String(words).replace('%s', value);
    }
    function choice() {
        return wizard.find("input[name='lyris_theme_choice']:checked").val() || '';
    }
    function usingLyris() {
        return choice() !== '' && choice() !== 'keep';
    }
    function useImages() {
        var box = wizard.find("input[name='lyris_use_images']");
        return box.length > 0 && box.is(':checked');
    }
    function chosenLook() {
        return wizard.find("input[name='lyris_look']:checked").val() || '';
    }
    function stepApplies(step) {
        if (wizard.find(".lyris-step[data-step='" + step + "']").length == 0) {
            return false;
        }
        return step == 'look' ? usingLyris() : true;
    }
    function steps() {
        var applies = [];
        for (var i = 0; i < stepOrder.length; i++) {
            if (stepApplies(stepOrder[i])) {
                applies.push(stepOrder[i]);
            }
        }
        return applies;
    }

    // each preview is a page drawn at a desktop width, and shrunk to fit
    function previewSource(frame) {
        var theme = frame.attr('data-theme');
        var url = previewUrl + '?theme=' + encodeURIComponent(theme) + '&screen=' + encodeURIComponent(previewScreen);
        if (frame.attr('data-look') && chosenLook()) {
            url += '&look=' + encodeURIComponent(chosenLook());
        }
        if (theme != logoTheme && logoTheme !== '' && (current == 'theme' || useImages())) {
            url += '&logofrom=' + encodeURIComponent(logoTheme);
        }
        return url;
    }
    function drawPreviews() {
        wizard.find(".lyris-step[data-step='" + current + "'] .lyris-preview-frame iframe").each(function() {
            var frame = $(this);
            var source = previewSource(frame);
            if (frame.attr('src') != source) {
                frame.attr('src', source);
            }
            var box = frame.parent();
            var scale = box.width() / 1200;
            frame.css({'transform': 'scale(' + scale + ')', '-webkit-transform': 'scale(' + scale + ')'});
            box.css('height', Math.round(900 * scale) + 'px');
        });
    }

    function summarize() {
        var list = wizard.find('.lyris-summary').empty();
        list.append($('<li></li>').html(text['SUMMARY_' + choice().toUpperCase()]));
        if (usingLyris()) {
            var look = wizard.find("input[name='lyris_look']:checked");
            if (look.length && wizard.find("input[name='lyris_look']").length > 1) {
                list.append($('<li></li>').html(fill(text.SUMMARY_LOOK, look.attr('data-name'))));
            }
            if (useImages()) {
                list.append($('<li></li>').html(text[imagesSummary]));
            }
        }
        if (wizard.find('.lyris-setting').length) {
            var screens = {};
            var count = 0;
            wizard.find('.lyris-setting:checked').each(function() {
                var name = $(this).attr('name');
                if (!screens[name]) {
                    screens[name] = true;
                    count++;
                }
            });
            list.append($('<li></li>').html(count ? fill(count == 1 ? text.SUMMARY_SETTINGS_ONE : text.SUMMARY_SETTINGS, count) : text.SUMMARY_SETTINGS_NONE));
        }
        if (wizard.find('.lyris-template-screen').length) {
            var templates = wizard.find('.lyris-template-screen:checked').length;
            list.append($('<li></li>').html(templates ? fill(templates == 1 ? text.SUMMARY_TEMPLATES_ONE : text.SUMMARY_TEMPLATES, templates) : text.SUMMARY_TEMPLATES_NONE));
        }
        if (templateScreenCount) {
            list.append($('<li></li>').html(fill(templateScreenCount == 1 ? text.SUMMARY_TEMPLATE_SCREENS_ONE : text.SUMMARY_TEMPLATE_SCREENS, templateScreenCount)));
        }
        wizard.find('.lyris-after').hide();
        wizard.find(".lyris-after[data-choice='" + choice() + "']").show();
    }

    function show(step) {
        current = step;
        wizard.find('.lyris-step').hide();
        wizard.find(".lyris-step[data-step='" + step + "']").show();
        if (step == 'summary') {
            summarize();
        }
        var order = steps();
        var at = $.inArray(step, order);
        // jQuery UI 1.8 floats the buttons right, so the first one is on the right: the step's main button there,
        // then Back, then Cancel
        var buttons = {};
        if (step == 'summary') {
            buttons[text.UPDATE] = function() {
                $(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button').attr('disabled', 'disabled');
                wizard.find('form').submit();
            };
        } else {
            buttons[text.NEXT] = function() {
                if (current == 'theme' && choice() === '') {
                    wizard.find('.lyris-pick-one').show();
                    return;
                }
                show(steps()[$.inArray(current, steps()) + 1]);
            };
        }
        if (at > 0) {
            buttons[text.BACK] = function() {
                show(steps()[$.inArray(current, steps()) - 1]);
            };
        }
        buttons[text.CANCEL] = function() {
            wizard.dialog('close');
        };
        wizard.dialog('option', 'buttons', buttons);
        wizard.scrollTop(0);
        drawPreviews();
    }

    wizard.dialog({
        autoOpen: false,
        modal: true,
        width: Math.min(1000, $(window).width() - 40),
        height: Math.min(780, $(window).height() - 40),
        resizable: false
    });

    $('#formulize-lyris-open').click(function() {
        wizard.dialog('open');
        show('theme');
    });

    // Lyris's settings start checked for screens when the whole site switches, and unchecked otherwise
    wizard.find("input[name='lyris_theme_choice']").bind('click', function() {
        wizard.find('.lyris-pick-one').hide();
        var on = (choice() == 'switch');
        wizard.find('.lyris-setting, .lyris-setting-all').each(function() {
            this.checked = on;
        });
    });

    wizard.find('.lyris-setting-all').bind('click', function() {
        var on = this.checked;
        wizard.find(".lyris-setting[data-column='" + $(this).attr('data-column') + "']").each(function() {
            this.checked = on;
        });
    });

    wizard.find('.lyris-templates-select').bind('click', function() {
        var on = ($(this).attr('data-select') == '1');
        wizard.find('.lyris-template-screen').each(function() {
            this.checked = on;
        });
        return false;
    });

    wizard.find('.lyris-preview-screen').bind('click', function() {
        previewScreen = $(this).attr('data-screen');
        wizard.find('.lyris-preview-screen').removeClass('lyris-current');
        $(this).addClass('lyris-current');
        drawPreviews();
        return false;
    });

    wizard.find("input[name='lyris_look'], input[name='lyris_use_images']").bind('click', function() {
        drawPreviews();
    });
});
</script>
