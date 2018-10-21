$(document).ready(function () {
	/*enable removal for current relationships (incomplete)*/
	$(".form-draggable").draggable({
		stack: '.form-draggable',
		helper: 'clone',
		revert: function (event, ui) {
			/*Animate form release*/
			$(this).data("uiDraggable").originalPosition = {
				top: $(this).position().top,
				left: $(this).position().left
			};
			return !event;
		},
		/*tilt effect on drag*/
		start: function (event, ui) {
			$(ui.helper).addClass("form-draggable-helper");
		},
	});

	/*Create a new relationship with a form*/
	$(".form-content").droppable({
		accept: ".addable",
		hoverClass: "form-focus",
		drop: function (ev, ui) {
			//ui.draggable.remove();
			var item = ui.draggable.clone();
			item.appendTo($(this));
			item.removeClass("addable");
			item.removeClass("tilt");
			item.attr("style", "");
			item.draggable({
				//Todo: fix duplicate code
				stack: '.form-draggable',
				helper: 'clone',
				revert: function (event, ui) { /*Animate form release*/
					$(this).data("uiDraggable").originalPosition = {
						top: $(this).position().top,
						left: $(this).position().left
					};
					return !event;
				},
				start: function (event, ui) {
					$(ui.helper).addClass("form-draggable-helper");
				},
			});
			$("#dialog").dialog("open");
		}
	});

	$(".form-content").sortable();
});