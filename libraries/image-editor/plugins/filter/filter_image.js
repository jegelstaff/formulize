function selFilter(value,panelId,e){
	var div_arg1 = xoopsGetElementById('darg1');
	var div_arg2 = xoopsGetElementById('darg2');
	var div_arg3 = xoopsGetElementById('darg3');
	
	var filter_desc = xoopsGetElementById('filterdesc');
	
	var targ1 = xoopsGetElementById('targ1');
	var targ2 = xoopsGetElementById('targ2');
	var targ3 = xoopsGetElementById('targ3');
	
	var arg1 = xoopsGetElementById('arg1');
	var arg2 = xoopsGetElementById('arg2');
	var arg3 = xoopsGetElementById('arg3');
	
	var larg1 = xoopsGetElementById('larg1');
	var larg2 = xoopsGetElementById('larg2');
	var larg3 = xoopsGetElementById('larg3');

	filter_desc.style.display = 'none';
	filter_desc.innerHTML = '';
	for (i=1;i<4;i++){
		eval('div_arg'+i).style.display = 'none';
		eval('targ'+i).innerHTML = '';
		eval('larg'+i).innerHTML = '';
		eval('arg'+i).value = '';
	}
	
	if (value != ''){
		for (i=0;i<=Filters.length-1;i++){
			if (Filters[i].value == value){
				var filter = Filters[i];
			}
		}

		filter_desc.style.display = 'block';
		filter_desc.innerHTML = '<img src="images/help.png" alt="'+filter['descr']+'" title="'+filter['descr']+'" />';

		if (filter['args'].length > 0){
			for (i=0;i<=filter['args'].length-1;i++){
				var x = i+1;
				eval('div_arg'+x).style.display = 'block';
				eval('targ'+x).innerHTML = filter['args'][i]['title'];
				eval('larg'+x).innerHTML = '<img src="images/help.png" alt="'+filter['args'][i]['descr']+'" title="'+filter['args'][i]['descr']+'" />';
				eval('arg'+x).value = filter['args'][i]['value'];
			}
		}
	}
	var div = xoopsGetElementById('xpPaneTopBar'+panelId);
	updatePaneHeight(e,div);
}

var filterScriptAjaxObjects = new Array();

function filter_preview(buttonObj){
	filter_startProgressBar();
	buttonObj.style.visibility='hidden';
	var ajaxIndex = filterScriptAjaxObjects.length;
	filterScriptAjaxObjects[ajaxIndex] = new sack();
	var url = filter_script_server_file + '?image_path=' + xoopsGetElementById('fimage_path').value
	+ '&image_url=' + xoopsGetElementById('fimage_url').value
	+ '&filter=' + xoopsGetElementById('filter').value
	+ '&arg1=' + xoopsGetElementById('arg1').value
	+ '&arg2=' + xoopsGetElementById('arg2').value
	+ '&arg3=' + xoopsGetElementById('arg3').value;

	filterScriptAjaxObjects[ajaxIndex].requestFile = url;	// Specifying which file to get
	filterScriptAjaxObjects[ajaxIndex].onCompletion = function(){ filterCompleted(ajaxIndex,buttonObj); };	// Specify function that will be executed after file has been found
	filterScriptAjaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
}

function filter_save(buttonObj)
{
    filter_startProgressBar();
	buttonObj.style.visibility='hidden';
	var ajaxIndex = filterScriptAjaxObjects.length;
	filterScriptAjaxObjects[ajaxIndex] = new sack();
	var url = filter_script_server_file + '?image_path=' + xoopsGetElementById('fimage_path').value
	+ '&image_url=' + xoopsGetElementById('fimage_url').value
	+ '&filter=' + xoopsGetElementById('filter').value
	+ '&arg1=' + xoopsGetElementById('arg1').value
	+ '&arg2=' + xoopsGetElementById('arg2').value
	+ '&arg3=' + xoopsGetElementById('arg3').value
	+ '&save=1';

	filterScriptAjaxObjects[ajaxIndex].requestFile = url;	// Specifying which file to get
	filterScriptAjaxObjects[ajaxIndex].onCompletion = function(){ filterCompleted(ajaxIndex,buttonObj); };	// Specify function that will be executed after file has been found
	filterScriptAjaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
}

function filter_delpreview(){
	var ajaxIndex = filterScriptAjaxObjects.length;
	filterScriptAjaxObjects[ajaxIndex] = new sack();
	var url = filter_script_server_file + '?image_path=' + xoopsGetElementById('fimage_path').value
	+ '&image_url=' + xoopsGetElementById('fimage_url').value
	+ '&delprev=1';

	filterScriptAjaxObjects[ajaxIndex].requestFile = url;	// Specifying which file to get
	filterScriptAjaxObjects[ajaxIndex].onCompletion = function(){ 	
		eval(filterScriptAjaxObjects[ajaxIndex].response)
	    filterScriptAjaxObjects[ajaxIndex] = false;
	    filter_hideProgressBar(); 
	};	// Specify function that will be executed after file has been found
	filterScriptAjaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
}

function filterCompleted(ajaxIndex,buttonObj)
{
	buttonObj.style.visibility='';
	eval(filterScriptAjaxObjects[ajaxIndex].response)
	filterScriptAjaxObjects[ajaxIndex] = false;
	filter_hideProgressBar();
}

function filter_progressBar()
{
	var div = document.getElementById('progressBar');

	var subDiv = document.createElement('DIV');
	div.appendChild(subDiv);
	subDiv.style.position = 'absolute';
	subDiv.className='progressBar_parentBox';
	subDiv.style.left = '0px';
	var progressBarSquare = document.createElement('DIV');
	progressBarSquare.className='progressBar_square';
	subDiv.appendChild(progressBarSquare);
	var progressBarSquare = document.createElement('DIV');
	progressBarSquare.className='progressBar_square';
	subDiv.appendChild(progressBarSquare);
	var progressBarSquare = document.createElement('DIV');
	progressBarSquare.className='progressBar_square';
	subDiv.appendChild(progressBarSquare);
	filter_progressBarMove();
	filter_hideProgressBar();
	
	var menu = xoopsGetElementById('dhtmlgoodies_xpPane');
	top = (window.innerHeight/2)-(div.clientHeight/2);
	left = ((window.innerWidth/2)+(menu.clientWidth)/2)-(div.clientWidth/2);
	
	div.style.top = top+'px';
	div.style.left = left+'px';
}

function filter_hideProgressBar()
{
	document.getElementById('progressBar').style.visibility = 'hidden';

}

function filter_startProgressBar()
{
	var div = document.getElementById('progressBar').getElementsByTagName('DIV')[0];
	div.style.left = '0px';
	document.getElementById('progressBar').style.visibility = 'visible';
}

function filter_progressBarMove()
{
	var div = document.getElementById('progressBar').getElementsByTagName('DIV')[0];
	var left = div.style.left.replace('px','')/1;
	left = left + 1;
	if(left > div.parentNode.clientWidth)left = 0 - div.clientWidth;
	div.style.left = left + 'px';

	setTimeout('filter_progressBarMove()',20);

}