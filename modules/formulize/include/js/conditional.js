// need to be global!
var conditionalHTML = new Array();
var governedElements = new Array();
var relevantElements = new Array();
var oneToOneElements = new Array();

var conditionalCheckInProgress = 0;
var currentlyProcessingHandles = {};
var completedHandlesInCurrentCascade = {};

function callCheckCondition(name, callHistory = []) {
	if(callHistory.length === 0) {
		completedHandlesInCurrentCascade = {};
	}
	if(currentlyProcessingHandles[name] || completedHandlesInCurrentCascade[name]) {
		return;
	}

	// Mark this handle as being processed
	currentlyProcessingHandles[name] = true;

	const checks = [];
  const relevantElementSets = [];
	const relevantElementSetsUseOneToOne = [];
	var oneToOne;
	for(key in governedElements[name]) {
		var markupHandle = governedElements[name][key];
		var oneToOneKey = relevantElements[markupHandle].findIndex(element => element === name);
		oneToOne = false;
		if(oneToOneKey > -1) {
			oneToOne = oneToOneElements[markupHandle][oneToOneKey];
		}
		elementValuesForURL = getRelevantElementValues(markupHandle, oneToOne);
    if(elementValuesForURL in relevantElementSets == false) {
			relevantElementSetsUseOneToOne[elementValuesForURL] = oneToOne;
      relevantElementSets[elementValuesForURL] = new Array();
    }
    relevantElementSets[elementValuesForURL].push(markupHandle);
  }
	for(elementValuesForURL in relevantElementSets) {
		checks.push(checkCondition(relevantElementSets[elementValuesForURL], elementValuesForURL, relevantElementSetsUseOneToOne[elementValuesForURL]));
	}
	var results = jQuery.when.apply(jQuery, checks);
	results.done(function() {
		// if only one operation sent, then results are flat and not in an array. Make an array to standardize what we're working with.
		if(typeof arguments[0] === 'string') {
			arguments[0] = new Array(arguments[0]);
		}
		const deferredCalls = [];
		jQuery.each(arguments, function(index, responseData) {
			if(Array.isArray(responseData) === false || 0 in responseData === false) { return false; }
			try {
				var result = JSON.parse(responseData[0]);
			} catch (e) {
				return false;
			}
			if(result) {
				try {
					elements = result.elements;
					for(key in elements) {
						var handle = elements[key].handle;
						var data = elements[key].data;
						if(typeof data === 'string') {
							data = data.trim();
						}
						if(data && data != '{NOCHANGE}' && (conditionalHTMLHasChanged(handle, data) || (window.document.getElementById('formulize-'+handle) !== null && window.document.getElementById('formulize-'+handle).style.display == 'none'))) {
							jQuery('#formulize-'+handle).empty();
							jQuery('#formulize-'+handle).append(data);
							// unless it is a hidden element, show the table row...
							if(parseInt(String(data).indexOf("input type='hidden'"))!=0) {
								if(window.document.getElementById('formulize-'+handle) !== null) {
									window.document.getElementById('formulize-'+handle).style.display = null; // doesn't need real value, just needs to be not set to 'none'
								}
								ShowHideTableRow(handle,false,0); // because the newly appended row will have full opacity so immediately make it transparent
								ShowHideTableRow(handle,true,1000);
								if (typeof window['formulize_initializeAutocomplete'+handle] === 'function') {
									window['formulize_initializeAutocomplete'+handle]();
								}
								if (typeof window['formulize_conditionalElementUpdate'+partsArray[3]] === 'function') {
									window['formulize_conditionalElementUpdate'+partsArray[3]]();
								}
							}
						} else if( !data && window.document.getElementById('formulize-'+handle) !== null && window.document.getElementById('formulize-'+handle).style.display != 'none') {
							ShowHideTableRow(handle,false,1000,true);
						}
						if(data != '{NOCHANGE}') {
							assignConditionalHTML(handle, data);
							// now check if this element has a value, and governed elements, in which case we need to defer a call to check the goverened elements' conditions
							if(typeof governedElements[handle] !== 'undefined' && callHistory.indexOf(handle) === -1 && elementHasValue(handle)) {
								deferredCalls.push(handle);
							}
							if(typeof governedElements[handle+'[]'] !== 'undefined' && callHistory.indexOf(handle+'[]') === -1 && elementHasValue(handle+'[]')) {
								deferredCalls.push(handle+'[]');
							}
						}
						conditionalCheckInProgress = conditionalCheckInProgress > 0 ? conditionalCheckInProgress - 1 : 0;
					}
				} catch (e) {
					conditionalCheckInProgress = 0;
					return false;
				}
			}
		});

		completedHandlesInCurrentCascade[name] = true;
		delete currentlyProcessingHandles[name];
		var newCallHistory = callHistory.slice();
		newCallHistory.push(name);
		var uniqueDeferredCalls = deferredCalls.filter(function(value, index, self) {
			return self.indexOf(value) === index;
		});
		uniqueDeferredCalls.forEach(function(deferredHandle) {
			callCheckCondition(deferredHandle, newCallHistory);
		});
	});
}

function captureDataAsInDOM(data) {
	jQuery('#conditionalHTMLCapture').empty();
	jQuery('#conditionalHTMLCapture').append(data);
	let capturedHTML = window.document.getElementById('conditionalHTMLCapture').innerHTML.trim();
	jQuery('#conditionalHTMLCapture').empty();
	return capturedHTML;
}

function assignConditionalHTML(handle, data = '') {
	if(!data && jQuery('#formulize-'+handle).length > 0) {
		data = window.document.getElementById('formulize-'+handle).innerHTML.trim();
	}
	conditionalHTML[handle] = '';
	if(data) {
		conditionalHTML[handle] = captureDataAsInDOM(data);
	}
}

function conditionalHTMLHasChanged(handle, data) {
	if(typeof conditionalHTML[handle] === 'undefined') {
		return true;
	}
  return conditionalHTML[handle] != captureDataAsInDOM(data);
}

function checkCondition(relevantElementSet, elementValuesForURL, oneToOne) {
	var oneToOneAdded = false;
	var elementIds = '';
	var elementIdsSep = '';
	for(k in relevantElementSet) {
		conditionalCheckInProgress = conditionalCheckInProgress + 1;
		var markupHandle = relevantElementSet[k];
		partsArray = markupHandle.split('_');
		elementIds = elementIds + elementIdsSep + partsArray[3];
		entryId = partsArray[2]; // assuming all the same!
		fid = partsArray[1]; // assuming all the same!
		if(oneToOne && oneToOneAdded == false && oneToOneElements[markupHandle]['onetoonefrid'] && partsArray[1] != oneToOneElements[markupHandle]['onetoonefid']) {
				elementValuesForURL = elementValuesForURL + '&onetoonekey=1&onetoonefrid='+oneToOneElements[markupHandle]['onetoonefrid']+'&onetoonefid='+oneToOneElements[markupHandle]['onetoonefid']+'&onetooneentries='+oneToOneElements[markupHandle]['onetooneentries']+'&onetoonefids='+oneToOneElements[markupHandle]['onetoonefids'];
				oneToOneAdded = true;
		}
		elementIdsSep = ',';
	}
	return jQuery.post(FORMULIZE.XOOPS_URL+"/modules/formulize/formulize_xhr_responder.php?uid="+FORMULIZE.XOOPS_UID+"&sid="+FORMULIZE.SCREEN_ID+"&op=get_element_row_html&elementId="+elementIds+"&entryId="+entryId+"&fid="+fid+"&frid="+FORMULIZE.FRID+elementValuesForURL);
}

/**
 * Get the current value(s) of a single form element by its handle
 * @param {string} handle - The element handle (e.g., 'de_8_new_153' or 'de_8_new_153[]')
 * @returns {*} - The value (string, array, or undefined if element not found)
 */
function getElementValue(handle) {
	var nameToUse;
	if(handle.indexOf('[]')!=-1) { // grab multiple value elements from a different tag
		nameToUse = '[jquerytag='+handle.substring(0, handle.length-2)+']';
	} else {
		nameToUse = '[name='+handle+']';
	}
	if(jQuery('#subentry-dialog '+nameToUse).length > 0) {
		nameToUse = '#subentry-dialog '+nameToUse;
	}
	if(jQuery(nameToUse).length > 0) {
		var elementType = jQuery(nameToUse).attr('type');
		if(elementType == 'radio') {
			return jQuery(nameToUse+':checked').val();
		} else if(elementType == 'checkbox') {
			var selectedItems = new Array();
			jQuery(nameToUse).map(function() {
				if(jQuery(this).attr('checked')) {
					var foundval = jQuery(this).attr('value');
					selectedItems.push(foundval);
				} else {
					selectedItems.push('');
				}
			});
			return selectedItems;
		} else if(handle.indexOf('[]')!=-1 && elementType == 'hidden') { // multi select auto complete
			var selectedItems = new Array();
			jQuery(nameToUse).map(function() {
				var foundval = jQuery(this).attr('value');
				selectedItems.push(foundval);
			});
			return selectedItems;
		} else {
			return jQuery(nameToUse).val();
		}
	}
	return undefined;
}

/**
 * Check if an element has a non-empty value
 * @param {string} handle - The element handle
 * @returns {boolean} - True if element has a value, false if empty/blank
 */
function elementHasValue(handle) {
	var value = getElementValue(handle);
	if(value === undefined || value === null || value === '') {
		return false;
	}
	if(jQuery.isArray(value)) {
		// For arrays, check if there's at least one non-empty value
		for(var i = 0; i < value.length; i++) {
			if(value[i] !== '' && value[i] !== null && value[i] !== undefined) {
				return true;
			}
		}
		return false;
	}
	return true;
}

function getRelevantElementValues(markupHandle, oneToOne=false) {
	var ret = '';
	var elements = relevantElements[markupHandle];
	for(var key in elements) {
		if(oneToOne && oneToOneElements[markupHandle][key] == false) {
			continue;
		}
		var handle = elements[key];
		var formulize_selectedItems = getElementValue(handle);

		if(formulize_selectedItems !== undefined) {
			if(jQuery.isArray(formulize_selectedItems)) {
				for(var k in formulize_selectedItems) {
					ret = ret + '&'+handle+'='+encodeURIComponent(formulize_selectedItems[k]);
				}
			} else {
				ret = ret + '&'+handle+'='+encodeURIComponent(formulize_selectedItems);
			}
		}
	}
	return ret;
}


function ShowHideTableRow(handle, show, speed, empty = false)
{
    var childCellsSelector = jQuery('#formulize-'+handle).children();
    var ubound = childCellsSelector.length - 1;
    var lastCallback = null;

    childCellsSelector.each(function(i)
    {
        // Only execute the callback on the last element.
        if (ubound == i && empty)
            lastCallback = function() { jQuery('#formulize-'+handle).empty(); window.document.getElementById('formulize-'+handle).style.display = 'none'; }

        if (show)
        {
            if(ubound == i) {
                if(typeof initializeCKEditor === 'function') { initializeCKEditor(handle+'_tarea'); }
            }
            jQuery(this).fadeIn(speed, lastCallback);
        }
        else
        {
            jQuery(this).fadeOut(speed, lastCallback);
        }
    });
}
