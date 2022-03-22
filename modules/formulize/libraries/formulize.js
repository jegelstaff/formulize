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

    // focus on first element
    jQuery('#formulizeform input, #formulizeform select, #formulizeform textarea, .form-container input, .form-container select, .form-container textarea').first().focus();
    
    // make radio buttons respond to keyboard entry
    jQuery('#formulizeform input[type="radio"], .form-container input[type="radio"]').keypress(function(e) {
        var key = String.fromCharCode(e.keyCode).toLowerCase();
        jQuery('input[name="'+jQuery(this).attr('name')+'"]').each(function() {
            checkText = jQuery('label[for='+jQuery(this).attr('id')+']').text();
            if(key == checkText || key == checkText.charAt(0).toLowerCase()) {
                jQuery(this).prop('checked', true).focus().trigger('change');
            }
        })
    });

    // make radio buttons uncheckable
    jQuery("input[type=radio]").each(function() {
        jQuery(this).data('checkedstatus', jQuery(this).prop("checked"));
    });
    jQuery("input[type=radio]").click(function () {
        if (jQuery(this).data('checkedstatus')) {
            jQuery(this).prop("checked", false).change();
            jQuery(this).data('checkedstatus', false);
        } else {
            jQuery(this).prop("checked", true).change();
            jQuery(this).data('checkedstatus', true);
        }
        jQuery(this).siblings('input[type="radio"]').data('checkedstatus', false);
    });
    
});
