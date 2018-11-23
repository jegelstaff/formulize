$(document).ready(function () {
	
	var $container = "<div class='drop'>xxx</div>";
	
	//Create forms
	for (i = 0; i < 2; i++) { //for each individual relationship
		var $formrel = [];
		for (j = 0; j < 3; j++) {
			var $append;
			var $html;
			if (j == 0) {
				$append = '<i class="fas fa-file-alt">&nbsp<b>Form'+i+'</b></i>';
				$html = $('<ul id="#form-listings">').append($append);
			} else {
				$append = '<i class="fas">&nbspForm2</i>';
				$html = $('<ul id="#form-listings"><li><span class="list">').append($append).append('<ul class="hidelist"><li>├ Form2</li><li>└ Form3</li></ul></li></ul>');
			}
			//todo: add drop container (after form3)
			$formrel[j] = $html;
		}
		$formrel[3 + 1]= $container;

		$('<div class="parent">').append($formrel).append($container).appendTo($('#form-container'));
					//todo: add drop container (after last formrel)
		

	}

	var toggler = document.getElementsByClassName("list");
	var i;

	for (i = 0; i < toggler.length; i++) {
		toggler[i].addEventListener("click", function () {			this.parentElement.parentElement.querySelector(".hidelist").classList.toggle("active");
			this.classList.toggle("list-down");
		});
	}
});