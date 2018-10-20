$(document).ready(function () {
	$(".form-draggable").draggable({
		stack: '.form-draggable',
		helper: 'clone',
	});
	/*
	$(".addable").draggable({
		stack: '.form-draggable',
		connectToSortable: '.form-content',
		containment: '.form-container',
		helper: 'clone',
		stop: function () {
			// When dragging stops, revert the draggable to its
			// original starting position.
			$(".form-draggable").removeClass("addable");
		},
	});
	*/
	$(".form-content").droppable({
    accept: ".addable",
    hoverClass: "form-focus",
    drop: function(ev, ui) {
        //ui.draggable.remove();
        var item = ui.draggable.clone();
        item.appendTo($(this));
		item.removeClass("addable");
		item.removeClass("tilt");
		item.attr("style", "");
		item.draggable({
			stack: '.form-draggable',
			helper: 'clone',
		});
    }
	});
	
	$(".form-content").sortable();
});

