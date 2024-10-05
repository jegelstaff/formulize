// elementId is the hidden element we're interacting with - can be a series of elements with [] which would be the case if multiple is set
// value is the value we're setting
// change is a flag to indicate if we trigger a change on the element when we do this
// multiple indicates if this is a multi-select autocomplete
function setAutocompleteValue(elementId, value, change, multiple) {
  if(multiple) {
		var targetElementId = 'last_selected_'+elementId;
	} else {
		var targetElementId = elementId;
	}
	jQuery('#'+targetElementId).val(value);
	if(change && !multiple) {
		jQuery('#'+elementId).trigger('change');
	}
	formulizechanged=1;
}

function removeFromMultiValueAutocomplete(value, elementId) {
	jQuery('#'+elementId+'_defaults input[value="'+value+'"]').remove();
	jQuery('.auto_multi_'+elementId+'[target="'+value+'"]').remove();
	triggerChangeOnMultiValueAutocomplete(elementId);
}

// if there are no items selected (such as if the user just deleted the last one), make a fake one just to trigger the change event
function triggerChangeOnMultiValueAutocomplete(elementId) {
	var triggerElements = jQuery('[name="'+elementId+'[]"]');
	if(triggerElements.length == 0) {
		jQuery('#'+elementId+'_defaults').append("<input type='hidden' name='"+elementId+"[]' jquerytag='"+elementId+"' id='"+elementId+"_0509' value='' />");
	}
	jQuery('[name="'+elementId+'[]"]').first().trigger('change');
	jQuery('#'+elementId+'_0509').remove();
	formulizechanged=1;
}
