$(document).ready(function() {
	$("#dialog-relationship-options").dialog({ autoOpen: false, modal: true, width: 475, height: 450, close: function(event, ui) {
		}
	});
	$('.relationship-link-options').click(function() {
		editRelationshipOptions($(this).attr('target'));
		return false;
	});
});

$(document).ready(function() {
	$("#dialog-relationship-create-connection").dialog({ autoOpen: false, modal: true, width: 970, height: 550, close: function(event, ui) {
			$("#dialog-relationship-create-connection-content").empty();
		}
	});
	$('.relationship-link-create-connection').click(function() {
		createRelationshipConnections($(this).attr('target'));
		return false;
	});
});

$('select[name=form2]').live('change', function() {
	createRelationshipConnections($('input[type=hidden][name=form1]').val(), [$('input[type=hidden][name=form1]').val(), $('select[name=form2]').val()]);
});

$("#relationship-create-connection-options select[name=forms-pi]").live('change', function() {
	if(typeof form1Id === 'undefined' || form1Id == 0) {
		var form1 = $('input[type=hidden][name=form1]').val();
		var form2 = $('select[name=form2]').val();
	} else {
		var form1 = form1Id;
		var form2 = form2Id;
	}
	si = typeof subformInterfaceFlag === 'undefined' ? '' : '1';
	showRelationshipCreationOptions(form1, form2, $(this).val(), si);
});

function editRelationshipOptions(linkId) {
	$("#dialog-relationship-options-content").empty();
	$("#dialog-relationship-options-content").append("<h1>Loading...</h1>");
	$("#dialog-relationship-options").dialog('open');
	$("#dialog-relationship-options-content").load('/modules/formulize/admin/relationship_options.php?linkId=' + linkId);
}

function createRelationshipConnections(form1Id, formIds=[], subformInterface='') {
	$("#dialog-relationship-create-connection-content").fadeOut(100);
	$("#dialog-relationship-create-connection-content").empty();
	let urlFormIds = formIds.length > 0 ? '&formIds[]=' + formIds.join('&formIds[]=') : '';
	let si = subformInterface ? encodeURIComponent(subformInterface) : 0;
	$("#dialog-relationship-create-connection-content").load('/modules/formulize/admin/relationship_create_connection.php?subformInterface='+si+'&form1Id=' + form1Id + urlFormIds, function() {
		if($("#dialog-relationship-create-connection-content").html()) {
			$("#dialog-relationship-create-connection").dialog('open');
			$("#dialog-relationship-create-connection-content").fadeIn(100);
		}
	});
}


