// formulize.js
//

if (typeof xoopsGetElementById != 'function') {
    // the 'xoopsGetElementById' function is included with xoops, so when it is missing, Formulize is embedded in another CMS

    // This block contains javascript used by Formulize when embedded in an external CMS such
    //   as Drupal, Joomla or Wordpress. The integration plugins should include this file.

    jQuery(document).ready(function() {
        // for grouped checkbox elements, this performs the 'check all' behaviour
        jQuery('input.checkemall').live('click', function() {
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

    // show hide blocks on a page using a link
    jQuery(".formulize-open-close-link").click(function(){
        var the_link = jQuery(this);
        var hidden_section = jQuery(the_link.attr("linked-block-id"));
        if (hidden_section.is(":visible")) {
            the_link.text(the_link.attr("open-text"));
        } else {
            the_link.text(the_link.attr("close-text"));
        }
        hidden_section.toggle();
    });
});

function fz_check_php_code(custom_code, block_name, icms_url, icms_userid) {
    jQuery.ajax({
        type: "POST",
        url: icms_url+"/modules/formulize/formulize_xhr_responder.php?uid="+icms_userid+"&op=validate_php_code",
        data: {the_code: custom_code},
        success: function(result) {
            if (result.length > 0) {
                alert("The "+block_name+" has an error:\n\n"+result+".");
            }
        },
        async: false
    });
}
