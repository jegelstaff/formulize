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

function fz_check_php_code(custom_code) {
	let validateCode = new Promise(function(resolve, reject) {
    jQuery.ajax({
        type: "POST",
        url: window.icms_url+"/modules/formulize/formulize_xhr_responder.php?uid="+window.icms_userid+"&op=validate_php_code",
        data: {the_code: custom_code},
        success: function(result) {
							resolve({
								valid: result.length > 0 ? false : true,
								result
							})
        	},
				error: function() {
					reject({
						valid: false,
						result: 'The validation request to the server failed.'
					})
				}
    });
	});
	return validateCode;
}

function getCookie(name) {
	let cookieArr = document.cookie.split(";");
	for(let i = 0; i < cookieArr.length; i++) {
		let cookiePair = cookieArr[i].split("=");
		if(name == cookiePair[0].trim()) {
			return decodeURIComponent(cookiePair[1]);
		}
	}
	return null;
}

/**
 * On a list or map screen's settings, choosing the element for a column row sets the row's search interface to the
 * one that element uses by default, and makes the date oriented interfaces available only for columns that hold
 * dates, and the time range only for columns that hold times. Rows are only changed when someone chooses a column,
 * so a screen's existing settings stay as they are.
 *
 * defaults: handle => {type: stored search type, dates: bool, times: bool}, from formulize_columnSearchDefaults()
 * dateOnlyTypes: the stored search types that only work on dates, from formulizeHandler::dateOnlySearchTypes()
 * timeOnlyTypes: the stored search types that only work on times, from formulizeHandler::timeOnlySearchTypes()
 *
 * Written for jQuery 1.4.2, which the admin UI runs.
 */
function formulizeBindColumnSearchDefaults(defaults, dateOnlyTypes, timeOnlyTypes) {
	timeOnlyTypes = timeOnlyTypes || [];
	jQuery('table.advanceview select[id^=cols-]').live('change', function() {
		var column = defaults[jQuery(this).val()];
		var typeSelect = jQuery(this).closest('tr').find('select[name^=search-type]');
		if(!column || typeSelect.length == 0) {
			return;
		}
		typeSelect.find('option').each(function() {
			var type = jQuery(this).attr('value');
			if((jQuery.inArray(type, dateOnlyTypes) != -1 && !column.dates) || (jQuery.inArray(type, timeOnlyTypes) != -1 && !column.times)) {
				jQuery(this).attr('disabled', 'disabled');
			} else {
				jQuery(this).removeAttr('disabled');
			}
		});
		typeSelect.val(column.type);
	});
}
