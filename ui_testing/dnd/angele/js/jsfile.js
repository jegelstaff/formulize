$(document).ready(function () {
	/*funtions*/
	function addForm(label) {
		var subformCount = 2;
		var $formrel = [];
		for (j = 0; j < subformCount; j++) {
			$formrel[j] = addSubform("form" + j, true);
		}

		$leader = $('<div class="form-content"><p><i class="fas fa-file-alt"></i>Form ' + label + '</p></div>');

		$container = $('<div class="container">').append($formrel);
		$leader.append($container).append($drop);
		$('<div class="parent">').append($leader).appendTo($('#form-container'));
	}

	function addSubform(label, haschildren) {
		var $html;
		if (haschildren) {
			$html = $('<ul class="form-listings"><li><span class="list"><i class="fas fa-plus arrow"></i>' + label + '</ul>');
			$subsub = $('<ul class="hidelist"><i class="fas fa-minus arrow"></i>form2</ul></li>');
			$subsub.appendTo($html);
		} else {
			$html = $('<ul class="form-listings"><li><span class="list">' + label + '</ul>');
		}
		$html.append($drop);

		var toggler = document.getElementsByClassName("list");
		var i;
		for (i = 0; i < toggler.length; i++) {
			(toggler[i]).addEventListener("click", function () {
				addTogglers(this)
			});
		}

		return $html;
	}

	function openPopup(addContainer) {
		var popup = document.getElementById('RelationshipPopup');
		popup.style.display = "block";

		var submit = document.getElementsByClassName("popup-submit")[0];
		submit.onclick = function () {
			popup.style.display = "none";

			var $newForm = addSubform("NEWFORM", false);

			addContainer.append($newForm);
		}

		var close = popup.getElementsByClassName("close")[0];
		close.onclick = function () {
			popup.style.display = "none";
		}

	}

	/*Create forms*/
	var $drop = "<div class='drop-container'>Add new relationship</div>";
	var $drop2 = "<div class='drop-container2'>Add new form</div>";
	var formCount = 2;
	for (i = 0; i < formCount; i++) {
		addForm(i);
	}
	$('#form-container').append($drop2);

	var toggler = document.getElementsByClassName("list");
	var i;
	for (i = 0; i < toggler.length; i++) {
		(toggler[i]).addEventListener("click", function () {
			addTogglers(this)
		});
	}

	function addTogglers(root) {

		parent = root.parentElement.parentElement;
		parent.querySelector(".hidelist").classList.toggle("active");
		root.classList.toggle("list-down");
		parent.querySelector(".arrow").classList.toggle("arrow-down");
		$(root).find('i').toggleClass('fa-minus').toggleClass('fa-plus');
	}
	/*Create a new relationship with a form*/
	$(".sidebar-form").draggable({
		helper: 'clone',
		appendTo: 'body',
		containment: "document",
		cursor: 'grabbing',
		start: function (event, ui) {
			$(ui.helper).addClass("sidebar-form-dragged");
			$(".drop-container").addClass("visible");
			$(".drop-container2").addClass("visible");
		},
		revert: function () {
			$(".drop-container").removeClass("visible");
			$(".drop-container2").removeClass("visible");
		},
	});
	$(".drop-container").droppable({
		accept: ".sidebar-form",
		hoverClass: "drop-highlight",
		drop: function (e, ui) {
			var $container = $(this).parent().find(".container");
			openPopup($container);
		}
	});

	$(".drop-container2").droppable({
		accept: ".sidebar-form",
		hoverClass: "drop-highlight",
		drop: function (e, ui) {
			addForm("New");
		}
	})


});