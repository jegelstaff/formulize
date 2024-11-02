
	/*funtions*/
	function addRel(relName, forms) {
		var $formrel = [];
		forms.forEach(function(form) {
			console.log("here:"+form);
            $formrel = $(addSubform(form, true));
        });

		$leader = $('<div class="form-content"><p><i class="fas fa-file-alt"></i>' + relName + '</p></div>');

		$container = $('<div class="container">').append($formrel);
		$leader.append($container).append("<div class='drop-container'>Add new form</div>");
		$('<div class="parent">').append($leader).appendTo($('#form-container'));
		
	}

	function addSubform(label, haschildren) {
		var $html;
		if (haschildren) {
			$html = $('<ul class="form-listings"><li><span class="list"><i class="fas fa-chevron-up arrow"></i>' + label + '</ul>');
			$subsub = $('<ul class="hidelist"><i class="fas fa-chevron-down arrow"></i>form2</ul></li>');
			$subsub.appendTo($html);
		} else {
			$html = $('<ul class="form-listings"><li><span class="list">' + label + '</ul>');
		}
		$html.append("<div class='drop-container'>Add new form</div>");

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

	function addTogglers(root) {

		parent = root.parentElement.parentElement;
		parent.querySelector(".hidelist").classList.toggle("active");
		root.classList.toggle("list-down");
		parent.querySelector(".arrow").classList.toggle("arrow-down");
		$(root).find('i').toggleClass('fa-chevron-down').toggleClass('fa-chevron-up');
	}
    
$(document).ready(function () {

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
			addRel("New");
		}
	});
});

