$(document).ready(function () {

	/*Create a new relationship with a form*/
	$(".form").draggable({
		helper: 'clone',
		appendTo: 'body',
		containment: "document",
		handle: ".fa-grip-vertical",
		cursor: 'grabbing',
		refreshPositions: true,
		start: function (event, ui) {
			$(ui.helper).removeClass();
			$(ui.helper).addClass("formDragged");

			$(".addNewRel").css("display", "block");
			//$(".drop-container").addClass("visible");
			//$(".drop-container2").addClass("visible");
		},
		revert: function () {
			$(".addNewRel").css("display", "none");
			//$(".drop-container").removeClass("visible");
			//$(".drop-container2").removeClass("visible");
		}
	});
	
	$('.removeRel').droppable({
		accept: ".tree li",

		over: function (event, ui) {
			console.log('You are over item with id ' + $(this).attr('id'));
		},
		drop: function (event, ui) {
			console.log(event.target.id);
		},
	});

	toDrop($(".tree li"));
	refreshCounters();
	setRelationships(); //temp
	addSort($('.tree'));
	
});
$(document).on('click', '.branch', function () {
	toggleContainer(this);
});

var $addForm = '<li class="addNewRel"><span><i class="fa fa-plus"></i> Add new form </span></li>';
var timeOut;

/*toggle, open, and close relationship containers*/
function toggleContainer(container) {
	$(container).find('.caret').toggleClass('caret-down');
	$(container).next().slideToggle();
}

function openContainer(container) {
	$(container).find('.caret').addClass('caret-down');
	$(container).next().slideDown();
}

function closeContainer(container) {
	$(container).find('.caret').removeClass('caret-down');
	$(container).next().slideUp();
	window.clearTimeout(timeOut);
}

function closeAll() {
	closeContainer($('.branch'));
}

function openAll() {
	openContainer($('.branch'));
}

function refreshCounters() {
	$('.counter').each(function () {
		var count = $(this).parent().siblings('.tree').children().length - 1;
		$(this).text(count);
	});
}

function setRelationships() {
	$('.relType').each(function () {
		$(this).text("1 : N");
	});
}

/*Create a new relationship*/
function openPopup(addContainer) {
	var popup = document.getElementById('RelationshipPopup');
	popup.style.display = "block";

	var submit = document.getElementsByClassName("popup-submit")[0];
	submit.onclick = function () {
		popup.style.display = "none";


		var $newForm = addSubform("NEWFORM", false);

		$newForm.insertBefore(addContainer.children('.addNewRel').first());
		refreshCounters();

	}

	var close = popup.getElementsByClassName("close")[0];
	close.onclick = function () {
		popup.style.display = "none";
	}

}

function addRel(relName, forms) {
	var $subForms = [];
	forms.forEach(function (form) {
		$subForms = $(addSubform(form, false));
	});

	$branch = '<span class="branch">';

	$leader = $('<li id="rel1"><span class="branch"><i class="fas fa-grip-vertical"></i> ' + relName + ' <span class="counter">1</span><span><i class="fas fa-chevron-up caret"></i></span></span>')

	//$leader = $('<div class="form-content"><p><i class="fas fa-file-alt"></i>' + relName + '</p></div>');
	$subFormContainer = $('<ul class="tree">');
	addSort($subFormContainer);
	$subForms.appendTo($subFormContainer);
	$leader.append($subFormContainer).append($addForm).appendTo($('#root'));
}

function addSubform(label, haschildren) {
	var $html;
	var relType = "1 : 1";
	if (haschildren) {
		$html = $('<ul class="form-listings"><li><span class="list"><i class="fas fa-chevron-up arrow"></i>' + label + '</ul>');
		$subsub = $('<ul class="hidelist"><i class="fas fa-chevron-down arrow"></i>form2</ul></li>');
		$subsub.appendTo($html);
	} else {
		$html = $('<li class="leaf ui-droppable"><span><i class="fas fa-grip-vertical"></i> File-1 <span class="relType">' + relType + '</span><i class="fas fa-info-circle"></i></span></li>');
	}
	//$html.append('<li class="addNewRel"><span><i class="fa fa-plus"></i> Add new relationship</span></li>');

	return $html;
}

function addSort($toSort){
	$toSort.sortable({
		handle: ".fa-grip-vertical",
		placeholder: 'shadow',
		sort: function (event, ui) {
			$(".shadow").height(ui.item.height());
			$(".removeRel").css("display", "block");
		},
		stop: function () {
			$(".removeRel").css("display", "none");
			//$(".drop-container").removeClass("visible");
			//$(".drop-container2").removeClass("visible");
		}
	});
}

function toDrop($toDrop){
	$toDrop.droppable({
		accept: ".form",
		//hoverClass: "drop-highlight",
		start: function (event, ui) {
			this.item.height(this.item.height());
		},
		drop: function (event, ui) {
			var $container = $(this).parent();
			openPopup($container);
		},
		over: function (event, ui) {
			//console.log('You are over item with id ' + $(this).attr('id'));
			branch = $(this).children('.branch'); //droppable area

			timeOut = window.setTimeout(function () {
				openContainer(branch);
			}, 200);
		},
		out: function (event, ui) {
			branch = $(this).children('.branch'); //droppable area

			//Close container when out for 1s *kinda buggy looking
			timeOut = window.setTimeout(function () {
				closeContainer(branch);
			}, 750);
		}
	});
}