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
			var dropContainer = $(".form-content");
			dropContainer.animate({
				height: dropContainer.height() + ui.helper.height()
			}, 250);
			dropContainer.addClass("form-focus");

		},
		stop: function (event, ui) {
			$(".form-content").animate({
				height: "0"
			}, 200);
			$(".form-content").removeClass("form-focus");

<<<<<<< HEAD
			//$("#popup").css("display", "block");
=======
			$("#popup").css("display", "block");
>>>>>>> c3e2d19f2a044d094544f288a7976c7fb6b0c1e7
		}
	});

	/*Create a new relationship with a form*/
	$(".form-content").droppable({
		accept: ".addable",
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
			var type = "none";
<<<<<<< HEAD
			if ($(this).hasClass('onemany')) {
				type = "One to many"
				item.append(relationshipDropDiv);
            }
            else if ($(this).hasClass('oneone')) {
				type = "One to one";			item.append(relationshipDropDiv);
=======
			if ($(this).hasClass('manymany')){
				type = "Many to many"
            }
            else if ($(this).hasClass('onemany')) {
				type = "One to many"
            }
            else if ($(this).hasClass('oneone')) {
				type = "One to one"
>>>>>>> c3e2d19f2a044d094544f288a7976c7fb6b0c1e7
            }
			$("#relationshiptype").text(type);
			$(this).animate({
				height: "0"
			}, 200);
		}
	});
	$(".form-content").sortable({
		placeholder: 'sortable-placeholder',
		revert: 'invalid',
	});

	//basic popup functionality
	var popup = document.getElementById('popup');
	var span = document.getElementsByClassName("close")[0];
	span.onclick = function () {
		popup.style.display = "none";
	}
	window.onclick = function (event) {
		if (event.target == popup) {
			popup.style.display = "none";
		}
	}

<<<<<<< HEAD
	var relationshipDropDiv = ("<div class='form-content' id='droppable'></div>");
=======
>>>>>>> c3e2d19f2a044d094544f288a7976c7fb6b0c1e7
});