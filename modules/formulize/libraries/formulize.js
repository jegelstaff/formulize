// formulize.js
//

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
    jQuery("#formulizeform input[type=radio]").each(function() {
        jQuery(this).data('checkedstatus', jQuery(this).prop("checked"));
    });
    jQuery("#formulizeform input[type=radio]").click(function () {
        if (jQuery(this).data('checkedstatus')) {
            // if unchecking, trigger change event
            jQuery(this).prop("checked", false).change();
            jQuery(this).data('checkedstatus', false);
        } else {
            jQuery(this).prop("checked", true);
            jQuery(this).data('checkedstatus', true);
        }
        jQuery(this).siblings('#formulizeform input[type="radio"]').data('checkedstatus', false);
    });

});
