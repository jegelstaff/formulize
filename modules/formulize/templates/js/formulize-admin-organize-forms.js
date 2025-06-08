let currentOrganize = getCookie('currentOrganize');
currentOrganize = currentOrganize != 'by-name' && currentOrganize != 'custom' ? 'by-name' : currentOrganize;
let tops = [];
let lefts = [];

$(window).load(function() {
	$('.admin-ui').show(250);
	setupDraggableBoxes();
	if(currentOrganize == 'by-name') {;
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

// thanks to https://stackoverflow.com/questions/11611765/jquery-ui-draggable-snap-event for insight into getting the snapElements from the draggable data!
let snapNeighbours = [];
function setupDraggableBoxes() {
	$( "div[id^=form-details-box]" ).draggable({
		snap: true,
		snapMode: 'outer',
		snapTolerance: 20,
		cursor: 'move',
		stop: function( event, ui ) {
			setDisplay('savewarning','block');
			setupConnectionUI($(this).data("draggable").snapElements, $(this)); // "ui-draggable" in jQuery 1.9+
		}
	});
	$('div[id^=form-details-box]').click(function(event) {
		if (!$(event.target).closest('.create-connection-icon').length) {
		clickFormDetails($(this));
		}
	});
}

function setupConnectionUI(snapElements, snapElement) {
	let form1Id = snapElement.attr('formid');
	let appid = snapElement.attr('appid');
	removeFormFromNeighbourhood(appid, form1Id);
	$.each(snapElements, function(index, element) {
		if(element.snapping) {
			let thisFormId = $(element.item).attr('formid');
			recordNewNeighbours(appid, form1Id, thisFormId);
		}
	});
	let form2Ids = gatherAllConnectedBoxes(appid, form1Id, []);
	if(form2Ids.length > 0) {
		snapElement.append("<div class='create-connection-icon'><a href='' onclick='createRelationshipConnections("+form1Id+", ["+form2Ids.join(',')+"]);return false;'>Create Connection</div>");
		$('div.create-connection-icon').show("scale",{}, 400);
		setTimeout(function() {
			$('div.create-connection-icon').css('opacity', 0);
			setTimeout(function() {
				$('div.create-connection-icon').remove();
			}, 400);
		}, 4000);
	}
}

function removeFormFromNeighbourhood(appid, form1Id) {
	if(snapNeighbours[appid] === undefined) {
		snapNeighbours[appid] = [];
	}
	snapNeighbours[appid][form1Id] = [];
	snapNeighbours[appid].forEach(function (item, index) {
		if(index != form1Id) {
			const i = snapNeighbours[appid][index].indexOf(form1Id);
			if (i > -1) {
  			snapNeighbours[appid][index].splice(i, 1);
			}
		}
	});
}

function recordNewNeighbours(appid, form1Id, thisFormId) {
	snapNeighbours[appid][form1Id].push(thisFormId);
	if(snapNeighbours[appid][thisFormId] === undefined) {
		snapNeighbours[appid][thisFormId] = [];
	}
	if(snapNeighbours[appid][thisFormId].indexOf(form1Id) === -1) {
		snapNeighbours[appid][thisFormId].push(form1Id);
	}
}

// only figures out adjacent boxes that you've snapped to on this page load :(
// because jQuery only tracks snap events live, doesn't deduce them based on adjacent starting positions
// but that's better than nothing, since often forms would be organized together in the same session.
// at load time, we could run some math and figure this all out (setup snapNeighbours) based on the top and left offsets.
function gatherAllConnectedBoxes(appid, form1Id, form2Ids) {
	if(typeof snapNeighbours[appid][form1Id] !== 'undefined') {
		for (const formId of snapNeighbours[appid][form1Id]) {
			if(form2Ids.indexOf(formId) === -1) {
				form2Ids.push(formId);
				form2Ids = gatherAllConnectedBoxes(appid, formId, form2Ids);
			}
		}
	}
	return form2Ids;
}

function clickFormDetails(jQFormDetailsBox) {
	var formid = jQFormDetailsBox.attr('formid');
	var appid = jQFormDetailsBox.attr('appid');
	// clicked on the same form again, so close
	if($('#form-details-box-'+appid+'-'+formid).hasClass('selected-form')) {
		$('#form-listing-details-'+appid+'-'+formid).hide(250, "linear");
		setTimeout(function() {
			$('#form-details-box-'+appid+'-'+formid).removeClass('selected-form');
			$('#form-details-box-'+appid+'-'+formid).removeClass('open-accordion-listing');
			$('#form-listing-details-'+appid+'-'+formid).removeClass('open-accordion-listing');
			$('#form-listing-details-'+appid+'-'+formid).removeClass('current-organize-custom');
		}, 250);

	// clicked on a form first time, so open
	} else {
		$('div[id^=form-details-box]').removeClass('selected-form');
		$('div[id^=form-details-box-]').removeClass('open-accordion-listing');
		$('div[id^=form-listing-details-]').removeClass('open-accordion-listing');
		$('div[id^=form-listing-details-]').removeClass('current-organize-custom');
		$('#form-details-box-'+appid+'-'+formid).addClass('selected-form');
		if(currentOrganize != 'custom') {
			$('#form-details-box-'+appid+'-'+formid).addClass('open-accordion-listing');
			$('#form-listing-details-'+appid+'-'+formid).addClass('open-accordion-listing');
		} else {
			$('#form-listing-details-'+appid+'-'+formid).addClass('current-organize-custom');
		}
		$('div[id^=form-listing-details-]:not(#form-listing-details-'+appid+'-'+formid+')').hide();
		$('#form-listing-details-'+appid+'-'+formid).show(250, "linear");
	}
	return false;
}

function showAllByName(appid) {
	$('.form-name').closest("[appid="+appid+"]").css('display', 'flex');
	$('.form-name').closest("[appid="+appid+"]").hide(); // needed after possibly switching display setting
	$('.form-name').closest("[appid="+appid+"]").show();
}

function showAllClickAndDrag(appid) {
	$('.form-name').closest("[appid="+appid+"]").css('opacity', 1);
}

function filterByName(appid, filterText) {
	$('.form-name-text:containsi("'+filterText+'")').closest("[appid="+appid+"]").css('display', 'flex');
	$('.form-name-text:containsi("'+filterText+'")').closest("[appid="+appid+"]").hide(); // needed after possibly switching display setting
	$('.form-name-text:containsi("'+filterText+'")').closest("[appid="+appid+"]").show();
	$('.form-name-text:not(:containsi("'+filterText+'"))').closest("[appid="+appid+"]").hide();
}

function filterClickAndDrag(appid, filterText) {
	$('.form-name-text:containsi("'+filterText+'")').closest("[appid="+appid+"]").css('opacity', 1);
	$('.form-name-text:not(:containsi("'+filterText+'"))').closest("[appid="+appid+"]").css('opacity', 0);
}

function applyCSSAlpha() {
	$('div.form-name').removeClass('ui-corner-top');
	$('div.form-name').addClass('ui-corner-left');
	$('div.form-screen-list-outer').css('display', 'flex');
	$('div.form-screen-list').removeClass('ui-corner-bottom');
	$('div.form-screen-list').addClass('ui-corner-right');
	$('div.form-screen-list').css('border-left', '0');
	$('div.form-screen-list').css('border-top', '1px solid #94daf2');
	$('div[id^=form-details-box]').css('display', 'flex');
	$('div[id^=form-details-box]').css('flex-direction', 'column');
	$('div[id^=form-details-box]').css('width', 'auto');
	$('div[id^=form-details-box]').css('margin-bottom', '0');
	$('div.form-name').css('width', '340px');
	$('div.form-screen-list').css('width', '100%');
	$('div.form-data').css('display', 'flex');
	$('.organize-toggle').css('z-index', '2');

}

function applyCSSCustom() {
	$('div.form-name').addClass('ui-corner-top');
	$('div.form-name').removeClass('ui-corner-left');
	$('div.form-screen-list-outer').css('display', 'block');
	$('div.form-screen-list').addClass('ui-corner-bottom');
	$('div.form-screen-list').removeClass('ui-corner-right');
	$('div.form-screen-list').css('border-top', '0');
	$('div.form-screen-list').css('border-left', '1px solid #94daf2');
	$('div[id^=form-details-box]').css('display', 'flex');
	$('div[id^=form-details-box]').css('flex-direction', 'row');
	$('div[id^=form-details-box]').css('display', 'block');
	$('div[id^=form-details-box]').css('width', '340px');
	$('div[id^=form-details-box]').css('margin-bottom', '1.5em');
	$('div.form-name').css('width', 'auto');
	$('div.form-screen-list').css('width', 'auto');
	$('div.form-data').css('display', 'none');
	$('.organize-toggle').css('z-index', '1');

}
