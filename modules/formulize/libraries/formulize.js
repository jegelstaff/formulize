// formulize.js
// 
// This file contains javascript used by Formulize screens when embedded in an external CMS such
//   as Drupal, Joomla or Wordpress. The integration plugins should include this file.
//

jQuery(document).ready(function() {
    // for grouped checkbox elements, this performs the 'check all' behaviour
    jQuery('input.checkemall').click(function() {
        if (jQuery(this).is(":checked")) {
            jQuery(this).parents(".grouped").find("input").attr("checked", true);
        } else {
            jQuery(this).parents(".grouped").find("input").attr("checked", false);
        }
    });
}
