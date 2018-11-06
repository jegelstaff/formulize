$(document).ready(function () {
	/*enable removal for current relationships (incomplete)*/
	$(".form-draggable").draggable({
		stack: '.form-draggable',
		helper: 'clone',
		appendTo: 'body',
		revert: 'invalid',
		start: function (event, ui) {
			/*tilt effect on drag*/
			$(ui.helper).addClass("form-draggable-helper");
			$(ui.helper).css("width", $(this).width());
		},
	});
	/*
		$(".form-draggable").sortable({
			//Todo: fix duplicate code
			stack: '.form-draggable',
			helper: 'clone',
			revert: 'invalid',
			start: function (event, ui) {
				$(ui.helper).addClass("form-draggable-helper");
				$(ui.helper).css("width", $(this).width());
			},
		});*/

	/*Create a new relationship with a form*/
	$(".form-content").droppable({
		accept: ".addable",
		hoverClass: "form-focus",
		over: function (event, ui) {
			$(this).animate({
				height: $(this).height() + ui.helper.height()
			}, 250);
		},
		out: function (event) {
			$(this).animate({
				height: "0"
			}, 200);
		},
		drop: function (ev, ui) {
			//ui.draggable.remove();
			var item = ui.draggable.clone();
			item.appendTo($(this));
			item.removeClass("addable");
			item.removeClass("tilt");
			item.attr("style", "");
			item.sortable({
				//Todo: fix duplicate code
				stack: '.form-draggable',
				helper: 'clone',
				revert: 'invalid',
				axis: 'y',
				containment: 'parent',
				animation: 200,
				start: function (event, ui) {
					$(ui.helper).addClass("form-draggable-helper");

					$(ui.helper).css("width", $(this).width());
				},
			});
			$("#dialog").dialog("open");
			$(this).animate({
				height: "0"
			}, 200);
		}
	});
	$(".form-content").sortable({
		placeholder: 'sortable-placeholder',
		revert: 'invalid',
	});



});