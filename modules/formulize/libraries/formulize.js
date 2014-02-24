// formulize.js

if (typeof xoopsGetElementById != 'function') {
    // the 'xoopsGetElementById' function is included with xoops, so when it is missing, Formulize is embedded in another CMS

    // This block contains javascript used by Formulize when embedded in an external CMS such
    //   as Drupal, Joomla or Wordpress. The integration plugins should include this file.

    jQuery(document).ready(function() {
        // for grouped checkbox elements, this performs the 'check all' behaviour
        jQuery('input.checkemall').click(function() {
            if (jQuery(this).is(":checked")) {
                jQuery(this).parents(".grouped").find("input").attr("checked", true);
            } else {
                jQuery(this).parents(".grouped").find("input").attr("checked", false);
            }
        });
    });
}

jQuery(document).ready(function() {
    // set formulizechanged when the 'check all' checkbox is clicked, or an autocomplete changes
    jQuery('input.checkemall, .formulize_autocomplete').click(function() {
        formulizechanged = 1;
    });
});

function adminEdit(thisElement) {
    var editButtons = document.getElementsByClassName('formulize_adminEditButton');
    //console.log(editButtons);
    /*
    for (var i = 0; i<editButtons.length; i++) {
        editButtons[i].hidden = 'true';
    }
    */
    //console.log(editButtons);

    var url_link = '../formulize/admin/renderhtml.php?ele_name=';
    url_link = url_link.concat(thisElement.name);

    $.ajax({
       url: url_link,
       dataType: 'json',
       success: function(data){
            // No need to return anything
       }
    });    

}

