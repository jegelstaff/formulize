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
		}
	});
	$('.relationship-link-create-connection').click(function() {
		createRelationshipConnections($(this).attr('target'));
		return false;
	});
});

$('select[name=form2]').live('change', function() {
	showRelationshipCreationOptions($('input[type=hidden][name=form1]').val(), $('select[name=form2]').val());
});

$("#relationship-create-connection-options select[name=forms-pi]").live('change', function() {
	if(typeof form1Id === 'undefined' || form1Id == 0) {
		var form1 = $('input[type=hidden][name=form1]').val();
		var form2 = $('select[name=form2]').val();
	} else {
		var form1 = form1Id;
		var form2 = form2Id;
	}
	showRelationshipCreationOptions(form1, form2, $(this).val());
});

function editRelationshipOptions(linkId) {
	$("#dialog-relationship-options-content").empty();
	$("#dialog-relationship-options-content").append("<h1>Loading...</h1>");
	$("#dialog-relationship-options").dialog('open');
	$("#dialog-relationship-options-content").load('/modules/formulize/admin/relationship_options.php?linkId=' + linkId);
}

function createRelationshipConnections(form1Id, form2Ids=[]) {
	$("#dialog-relationship-create-connection-content").empty();
	$("#dialog-relationship-create-connection-content").append("<h1>Loading...</h1>");
	let urlForm2Ids = form2Ids.length > 0 ? '&form2Ids[]=' + form2Ids.join('&form2Ids[]=') : '';
	$("#dialog-relationship-create-connection-content").load('/modules/formulize/admin/relationship_create_connection.php?form1Id=' + form1Id + urlForm2Ids, function() {
		if($("#dialog-relationship-create-connection-content").html()) {
			$("#dialog-relationship-create-connection").dialog('open');
		}
	});
}


