/* When the user clicks on the button, 
toggle between hiding and showing the dropdown content */
function myFunction(myDropdown) {
    closeDropdowns();
    jQuery('#'+myDropdown).toggleClass('show');
}

// Close the dropdown if the user clicks outside of it
jQuery(document).click(function(e) {
    if (e.target.className != 'dropbtn') {
        closeDropdowns();
    }
});

function closeDropdowns() {
    jQuery('.dropdown-content').each(function() {
        if (jQuery(this).hasClass('show')) {
            jQuery(this).removeClass('show');
        }
    });
}
