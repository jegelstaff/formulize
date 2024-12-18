let currentOrganize = 'custom';
let tops = [];
let lefts = [];

$(window).load(function() {
	$('.admin-ui').show(250);
	setupDraggableBoxes();
	if(getCookie('currentOrganize') == 'by-name') {
		currentOrganize = 'by-name';
		organizeAlpha();
	}

	$('input[name=organize-toggle]').change(function() {
		var classToSet = $(this).attr('value');
		if(currentOrganize != classToSet) {
			if(classToSet == 'custom') {
				$('input[id^=filter-forms-]').each(function() {
					var appid = $(this).attr('appid');
					var filterText = $(this).val();
					if(filterText) {
						showAllByName(appid);
						filterClickAndDrag(appid, filterText);
					}
				});
				organizeCustom(); // do last
			} else {
				organizeAlpha(); // do first
				$('input[id^=filter-forms-]').each(function() {
					var appid = $(this).attr('appid');
					var filterText = $(this).val();
					if(filterText) {
						showAllClickAndDrag(appid);
						filterByName(appid, filterText);
					}
				});
			}
			currentOrganize = classToSet;
			let date = new Date();
			date.setTime(date.getTime() + (180 * 24 * 60 * 60 * 1000)); // Set the cookie to expire in 180 days
			let expires = "expires=" + date.toUTCString();
			document.cookie = "currentOrganize="+currentOrganize+"; "+expires+"; path=/";
		} else {
			$('input[type=checkbox][class=organize-toggle-'+classToSet+']').each(function() {
				$(this).attr('checked', 'checked');
			});
		}
	});

	$('div[id^=form-details-box] a').click(function(event) {
		event.stopPropagation();
	});

	$('.form-listing-details-close').click(function() {
		var appid = $(this).attr('appid');
		var formid = $(this).attr('formid');
		$('#form-listing-details-'+appid+'-'+formid).hide(250);
		$('div[id^=form-details-box]').removeClass('selected-form');
		return false;
	});

	// Thanks to Shankar Sangoli - https://stackoverflow.com/a/8795791
	$.extend($.expr[':'], {
		'containsi': function(elem, i, match, array)
		{
			return (elem.textContent || elem.innerText || '').toLowerCase()
			.indexOf((match[3] || "").toLowerCase()) >= 0;
		}
	});

	$('input[id^=filter-forms-]').keydown(function(event){
		if(event.keyCode == 13) {
			event.preventDefault();
			var appid = $(this).attr('appid');
			var filterText = $(this).val();
			if(filterText) {
				if(currentOrganize == 'custom') {
					filterClickAndDrag(appid, filterText);
				} else {
					filterByName(appid, filterText);
				}
			} else {
				if(currentOrganize == 'custom') {
					showAllClickAndDrag(appid);
				} else {
					showAllByName(appid);
				}
			}
		}
	});

});

function organizeAlpha() {
	$("div[id^='form-details-box-']").each(function() {
		tops[$(this).attr('appid')+"."+$(this).attr('formid')] = $(this).css('top');
		lefts[$(this).attr('appid')+"."+$(this).attr('formid')] = $(this).css('left');
		$(this).css('top', 0);
		$(this).css('left', 0);
	});
	$( "div[id^=form-details-box]" ).draggable( "destroy" );
	$("div.accordion-box").css('cursor', 'help');
	$('input[type=checkbox][class=organize-toggle-custom]').each(function() {
		$(this).removeAttr('checked');
	});
	$('input[type=checkbox][class=organize-toggle-by-name]').each(function() {
		$(this).attr('checked', 'checked');
	})
	applyCSSAlpha();
}

function organizeCustom() {
	$("div[id^='form-details-box-']").each(function() {
		$(this).css('top', tops[$(this).attr('appid')+"."+$(this).attr('formid')]);
		$(this).css('left', lefts[$(this).attr('appid')+"."+$(this).attr('formid')]);
	});
	$( "div[id^=form-details-box]" ).unbind('click');
	setupDraggableBoxes();
	$("div.accordion-box").css('cursor', 'move');
	$('input[type=checkbox][class=organize-toggle-by-name]').each(function() {
		$(this).removeAttr('checked');
	});
	$('input[type=checkbox][class=organize-toggle-custom]').each(function() {
		$(this).attr('checked', 'checked');
	});
	applyCSSCustom();
}

function setupDraggableBoxes() {
	$( "div[id^=form-details-box]" ).draggable({
		snap: true,
		snapMode: 'outer',
		snapTolerance: 20,
		cursor: 'move',
		stop: function( event, ui ) { setDisplay('savewarning','block'); }
	});
	$('div[id^=form-details-box]').click(function() {
		clickFormDetails($(this));
	});
}

function clickFormDetails(jQFormDetailsBox) {
	var formid = jQFormDetailsBox.attr('formid');
	var appid = jQFormDetailsBox.attr('appid');
	if($('#form-details-box-'+formid).hasClass('selected-form')) {
		$('#form-details-box-'+formid).toggleClass('selected-form');
		$('#form-listing-details-'+appid+'-'+formid).hide(250);
	} else {
		$('div[id^=form-details-box]').removeClass('selected-form');
		$('#form-details-box-'+formid).toggleClass('selected-form');
		$('#form-listing-details-'+appid+'-'+formid).show(250);
		$('div[id^=form-listing-details-]:not(#form-listing-details-'+appid+'-'+formid+')').hide();
	}
	return false;
}

function showAllByName(appid) {
	$('.form-name').parent("[appid="+appid+"]").css('display', 'flex');
	$('.form-name').parent("[appid="+appid+"]").hide(); // needed after possibly switching display setting
	$('.form-name').parent("[appid="+appid+"]").show();
}

function showAllClickAndDrag(appid) {
	$('.form-name').parent("[appid="+appid+"]").css('opacity', 1);
}

function filterByName(appid, filterText) {
	$('.form-name:containsi("'+filterText+'")').parent("[appid="+appid+"]").css('display', 'flex');
	$('.form-name:containsi("'+filterText+'")').parent("[appid="+appid+"]").hide(); // needed after possibly switching display setting
	$('.form-name:containsi("'+filterText+'")').parent("[appid="+appid+"]").show();
	$('.form-name:not(:containsi("'+filterText+'"))').parent("[appid="+appid+"]").hide();
}

function filterClickAndDrag(appid, filterText) {
	$('.form-name:containsi("'+filterText+'")').parent("[appid="+appid+"]").css('opacity', 1);
	$('.form-name:not(:containsi("'+filterText+'"))').parent("[appid="+appid+"]").css('opacity', 0);
}

function applyCSSAlpha() {
	$('div.form-name').removeClass('ui-corner-top');
	$('div.form-name').addClass('ui-corner-left');
	$('div.form-screen-list').removeClass('ui-corner-bottom');
	$('div.form-screen-list').addClass('ui-corner-right');
	$('div.form-screen-list').css('border-left', '0');
	$('div.form-screen-list').css('border-top', '1px solid #94daf2');
	$('div[id^=form-details-box]').css('display', 'flex');
	$('div[id^=form-details-box]').css('flex-direction', 'row');
	$('div[id^=form-details-box]').css('width', 'auto');
	$('div[id^=form-details-box]').css('margin-bottom', '0');
	$('div.form-name').css('width', '340px');
	$('div.form-screen-list').css('width', '100%');
	$('div.form-data').css('display', 'flex');

}

function applyCSSCustom() {
	$('div.form-name').addClass('ui-corner-top');
	$('div.form-name').removeClass('ui-corner-left');
	$('div.form-screen-list').addClass('ui-corner-bottom');
	$('div.form-screen-list').removeClass('ui-corner-right');
	$('div.form-screen-list').css('border-top', '0');
	$('div.form-screen-list').css('border-left', '1px solid #94daf2');
	$('div[id^=form-details-box]').css('display', 'block');
	$('div[id^=form-details-box]').css('width', '340px');
	$('div[id^=form-details-box]').css('margin-bottom', '1.5em');
	$('div.form-name').css('width', 'auto');
	$('div.form-screen-list').css('width', 'auto');
	$('div.form-data').css('display', 'none');
}
