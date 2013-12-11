// formulize.js
// 

if (typeof xoopsGetElementById != 'function') {
    // the 'xoopsGetElementById' function is included with xoops, so when it is missing, Formulize is embedded in another CMS

    // This block contains javascript used by Formulize when embedded in an external CMS such
    //   as Drupal, Joomla or Wordpress. The integration plugins should include this file.

    jQuery(document).ready(function() {
        // for grouped checkbox elements, this performs the 'check all' behaviour
        jQuery('input.checkemall').click(function() {
            if (jQuery(this).is(":checked")) {
                jQuery(this).parents(".grouped").find("input").attr("checked", true);
            } else {
                jQuery(this).parents(".grouped").find("input").attr("checked", false);
            }
        });
    });
}

jQuery(document).ready(function() {
    // set formulizechanged when the 'check all' checkbox is clicked, or an autocomplete changes
    jQuery('input.checkemall, .formulize_autocomplete').click(function() {
        formulizechanged = 1;
    });
});
