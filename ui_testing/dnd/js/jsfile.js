$(document).ready(function () {
    $(".form-draggable").draggable({
        stack: '.form-draggable', 
        connectToSortable: '.form-content',
        containment:'.form-container',
        helper:'clone',        
    });
    $(".form-content").sortable();
});



