jQuery(function() {
jQuery("span.helptext").hide();
   jQuery("img.helptip").hover(function() {
     jQuery(this).nextAll().each( function() {
    if (jQuery(this).filter('span.helptext').is(":visible")) {
     jQuery(this).filter('span.helptext').toggle("slow");
       return false;
    } else {
	jQuery("span.helptext").fadeOut("slow");
    }
      if (jQuery(this).filter('img.helptip').length) {
       return false;
      }
     jQuery(this).filter('span.helptext').toggle("slow");
    });
   }, function() {return false;});

jQuery('input.checkemall').click(function() {
  if(jQuery(this).is(":checked")) {
   jQuery(this).parents(".grouped").find("input").attr("checked",true);
  } else {
   jQuery(this).parents(".grouped").find("input").attr("checked",false);
  }
 });
});