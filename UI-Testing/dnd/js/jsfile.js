$(document).ready(function(){
$( ".form-header" ).draggable({    
    stack: ".form",
    revert : function(event, ui) {
            //return to original position after dropping
        $(this).data("uiDraggable").originalPosition = {
                top : 0,
                left : 0
            };
            return !event;
        
    }   
    
});
});