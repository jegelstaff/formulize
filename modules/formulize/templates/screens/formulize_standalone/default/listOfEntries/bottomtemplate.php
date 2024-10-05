<?php

// include Javascript necessary for the locked columns feature
// also show any message text from a custom button the user clicked

if($messageText) {
    $messageText = "alert('$messageText');";
}

?>

<script type='text/javascript'>

<?php print $messageText; ?>

var floatingContents = new Array();

function toggleColumnInFloat(column) {
	jQuery('.column'+column).map(function () {
		var columnAddress = jQuery(this).attr('id').split('_');
		var row = columnAddress[1];
		if(floatingContents[column] == true) {
			jQuery('#floatingcelladdress_'+row+' #cellcontents_'+row+'_'+column).remove();
			jQuery('#celladdress_'+row+'_'+column).css('display', 'table-cell');
			jQuery(this).removeClass('now-scrolling');
		} else {
			jQuery('#floatingcelladdress_'+row).append(jQuery(this).html());
			var paddingTop = getPaddingNumber(jQuery(this),'padding-top');
			var paddingBottom = getPaddingNumber(jQuery(this),'padding-bottom');
			var paddingLeft = getPaddingNumber(jQuery(this),'padding-left');
			var paddingRight = getPaddingNumber(jQuery(this),'padding-right');
			jQuery('#floatingcelladdress_'+row+' #cellcontents_'+row+'_'+column).css('width', (parseInt(jQuery(this).width())+parseInt(paddingLeft)+parseInt(paddingRight)));
			jQuery('#floatingcelladdress_'+row+' #cellcontents_'+row+'_'+column).css('height', (parseInt(jQuery(this).height())+parseInt(paddingTop)+parseInt(paddingBottom)));
			jQuery(this).addClass('now-scrolling');
		}
	});
	if(floatingContents[column] == true) {
		floatingContents[column] = false;
		jQuery(this).removeClass("heading-locked").addClass("heading-unlocked");
	} else {
		floatingContents[column] = true;
	}
}

function getPaddingNumber(element,paddingType) {
	var value = element.css(paddingType).replace(/[A-Za-z$-]/g, "");;
	return value;
}

function setScrollDisplay(element) {
	if(element.scrollLeft() > 0) {
		var maxWidth = 0;
		jQuery(".now-scrolling").css('display', 'none');
		jQuery(".floating-column").css('display', 'table-cell');
	} else {
		jQuery(".floating-column").css('display', 'none');
		jQuery(".now-scrolling").css('display', 'table-cell');
	}
}

jQuery(window).load(function() {
    
	jQuery('.lockcolumn').live("click", function() {
		var lockData = jQuery(this).attr('id').split('_');
		var column = lockData[1];
		if(floatingContents[column] == true) {
            jQuery(this).removeClass("heading-locked").addClass("heading-unlocked");
            jQuery('td#celladdress_h1_'+column+' #lockcolumn_'+column).removeClass("heading-locked").addClass("heading-unlocked");
			var curColumnsArray = jQuery('#formulize_lockedColumns').val().split(',');
			var curColumnsHTML = '';
			for (var i=0; i < curColumnsArray.length; i++) {
				if(curColumnsArray[i] != column) {
					if(curColumnsHTML != '') {
						curColumnsHTML = curColumnsHTML+',';
					}
					curColumnsHTML = curColumnsHTML+curColumnsArray[i];
				}
			}
			jQuery('#formulize_lockedColumns').val(curColumnsHTML);
		} else {
			jQuery(this).removeClass("heading-unlocked").addClass("heading-locked");
			var curColumnsHTML = jQuery('#formulize_lockedColumns').val();
			jQuery('#formulize_lockedColumns').val(curColumnsHTML+','+column);
		}
		toggleColumnInFloat(column);
		return false;
	});

    <?php
    foreach($lockedColumns as $thisColumn) {
        if(is_numeric($thisColumn)) {
            print "toggleColumnInFloat(".intval($thisColumn).");
            ";
        }
    }
    ?>

	jQuery('#resbox').scroll(function () {
		setScrollDisplay(jQuery('#resbox'));
	});
	jQuery(window).scroll(function () {
		setScrollDisplay(jQuery(window));
	});

});

jQuery(window).scroll(function () {
    jQuery('.floating-column').css('margin-top', ((window.pageYOffset)*-1));
});

</script>