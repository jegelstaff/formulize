// need to be global!
var conditionalHTML = new Array();
var governedElements = new Array();
var relevantElements = new Array();
var oneToOneElements = new Array();

var conditionalCheckInProgress = 0;

function callCheckCondition(name) {
	const checks = [];
    const relevantElementSets = [];
	for(key in governedElements[name]) {
		var markupHandle = governedElements[name][key];
		elementValuesForURL = getRelevantElementValues(relevantElements[markupHandle]);
        if(elementValuesForURL in relevantElementSets == false) {
            relevantElementSets[elementValuesForURL] = new Array();
        }
        relevantElementSets[elementValuesForURL].push(markupHandle);
    }
    for(elementValuesForURL in relevantElementSets) {
        checks.push(checkCondition(relevantElementSets[elementValuesForURL], elementValuesForURL));
    }
    var results = jQuery.when.apply(jQuery, checks);
    results.done(function(){
        // if only one operation sent, then results are flat and not in an array. Make an array to standardize what we're working with.
        if(typeof arguments[0] === 'string') {
            arguments[0] = new Array(arguments[0]);
		}
        jQuery.each(arguments, function(index, responseData){
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
						}
						conditionalCheckInProgress = conditionalCheckInProgress - 1;
					}
                } catch (e) {
                    return false;
                }
            }
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
  return conditionalHTML[handle] != captureDataAsInDOM(data);
}

function checkCondition(relevantElementSet, elementValuesForURL) {
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
        if(oneToOneAdded == false && oneToOneElements[markupHandle]['onetoonefrid'] && partsArray[1] != oneToOneElements[markupHandle]['onetoonefid']) {
            elementValuesForURL = elementValuesForURL + '&onetoonekey=1&onetoonefrid='+oneToOneElements[markupHandle]['onetoonefrid']+'&onetoonefid='+oneToOneElements[markupHandle]['onetoonefid']+'&onetooneentries='+oneToOneElements[markupHandle]['onetooneentries']+'&onetoonefids='+oneToOneElements[markupHandle]['onetoonefids'];
            oneToOneAdded = true;
        }
        elementIdsSep = ',';
    }
	return jQuery.post(FORMULIZE.XOOPS_URL+"/modules/formulize/formulize_xhr_responder.php?uid="+FORMULIZE.XOOPS_UID+"&op=get_element_row_html&elementId="+elementIds+"&entryId="+entryId+"&fid="+fid+elementValuesForURL);
}

function getRelevantElementValues(elements) {
	var ret = '';
	for(key in elements) {
		var handle = elements[key];
		if(handle.indexOf('[]')!=-1) { // grab multiple value elements from a different tag
			nameToUse = '[jquerytag='+handle.substring(0, handle.length-2)+']';
		} else {
			nameToUse = '[name='+handle+']';
		}
        if(jQuery('#subentry-dialog '+nameToUse).length > 0) {
            nameToUse = '#subentry-dialog '+nameToUse;
        }
        if(jQuery(nameToUse).length > 0) {
		elementType = jQuery(nameToUse).attr('type');
		if(elementType == 'radio') {
			formulize_selectedItems = jQuery(nameToUse+':checked').val();
		} else if(elementType == 'checkbox') {
			formulize_selectedItems = new Array();
			jQuery(nameToUse).map(function() { // need to check each one individually, because val isn't working right?!
				if(jQuery(this).attr('checked')) {
					foundval = jQuery(this).attr('value');
					formulize_selectedItems.push(foundval);
				} else {
					formulize_selectedItems.push('');
				}
			});
		} else {
			formulize_selectedItems = jQuery(nameToUse).val();
		}
		if(jQuery.isArray(formulize_selectedItems)) {
			for(key in formulize_selectedItems) {
				ret = ret + '&'+handle+'='+encodeURIComponent(formulize_selectedItems[key]);
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
