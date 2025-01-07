$(document).ready(function() {
	$("#dialog-relationship-options").dialog({ autoOpen: false, modal: true, width: 950, height: 450, close: function(event, ui) {
		}
	});
	$('.relationship-link-options').click(function() {
		editRelationshipOptions($(this).attr('target'));
		return false;
	});
});

function editRelationshipOptions(linkId) {
	$("#dialog-relationship-options-content").empty();
	$("#dialog-relationship-options-content").append("<h1>Loading...</h1>");
	$("#dialog-relationship-options").dialog('open');
	$("#dialog-relationship-options-content").load('/modules/formulize/admin/relationship_options.php?linkId=' + linkId);
}


