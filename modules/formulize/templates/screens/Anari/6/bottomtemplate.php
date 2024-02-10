<?php

print "
        <div id='formulize-list-of-entries-footer'>
            <div>$numberOfEntries</div><div>$pageNavControls</div>
        </div>    
    </div>
</div>

";

// include Javascript necessary for the locked columns feature
// include Javascript necessary for showing more action buttons
// also show any message text from a custom button the user clicked

if($messageText) {
    $messageText = "alert('$messageText');";
}

?>

<script type='text/javascript'>

<?php print $messageText; ?>


function showMoreActionButtons() {
    jQuery('#more-action-buttons').toggle(300);
}

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

function setSearchRowTop() {
    var headingHeight = jQuery('td[id=celladdress_h1_0]').innerHeight();
    var topValue = headingHeight+1;
    jQuery('td[id^=celladdress_1_]').css('top',topValue+'px');
}

var toggleSearchesOnFirst = <?php print $toggleSearchesOnFirst; ?>;
function toggleSearches() {
	if(jQuery('#cellcontents_1_0').css('display') == 'none' || toggleSearchesOnFirst) {
		// style searches open
		jQuery('td[id^="celladdress_1_"]').css('padding','24px');
        jQuery('td[id^="celladdress_1_"]').css('padding-left','0.3em');
		jQuery('.search-toggle-link').css('transform', 'rotate(180deg)');
		
	} else {
		// style searches closed
		jQuery('td[id^="celladdress_1_"]').css('padding','0.3em');	
		jQuery('td[id^="celladdress_1_"]').css('padding-top','0');	
		jQuery('.search-toggle-link').css('transform', 'none');
	}
	// toggle on/off except if they should remain open at first
	if(!toggleSearchesOnFirst) {
		jQuery('div[id^="cellcontents_1_"]').toggle();
		jQuery('#celladdress_1_margin .header-info-link').toggle();
	}
	toggleSearchesOnFirst = false;
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
    
    if(jQuery('.search-toggle-link').length) {
		toggleSearches();
		jQuery('#celladdress_1_margin').css('max-width', '20px');
		jQuery('td[id^="celladdress_1_"]').css('transition', 'padding 0.5s');
		jQuery('#celladdress_1_margin').parent().click(function (event) {
			if(event.target.id.includes('celladdress_1_')) {
				toggleSearches();
			}
		});
	}
    
});

jQuery(window).scroll(function () {
    jQuery('.floating-column').css('margin-top', ((window.pageYOffset)*-1));
});

jQuery(document).ready(function() {
    setSearchRowTop();
    setTimeout(setSearchRowTop,500);
});

jQuery(window).resize(function () {
    setSearchRowTop();
});

</script>