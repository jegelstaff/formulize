$(document).ready(function () {
	
		$('.branch').click(function () {
			$(this).children().toggleClass('fa-folder-open-o');
			$(this).next().slideToggle();

		});
}