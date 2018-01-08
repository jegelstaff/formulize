var resizeScriptAjaxObjects = new Array();

function resize_preview(buttonObj){
	resize_startProgressBar();
	buttonObj.style.visibility='hidden';
	var ajaxIndex = resizeScriptAjaxObjects.length;
	resizeScriptAjaxObjects[ajaxIndex] = new sack();
	var url = resize_script_server_file + '?image_path=' + xoopsGetElementById('fimage_path').value
	+ '&image_url=' + xoopsGetElementById('fimage_url').value
	+ '&width=' + xoopsGetElementById('resize_width').value
	+ '&height=' + xoopsGetElementById('resize_height').value;

	resizeScriptAjaxObjects[ajaxIndex].requestFile = url;	// Specifying which file to get
	resizeScriptAjaxObjects[ajaxIndex].onCompletion = function(){ resizeCompleted(ajaxIndex,buttonObj); };	// Specify function that will be executed after file has been found
	resizeScriptAjaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
}

function resize_save(buttonObj)
{
    resize_startProgressBar();
	buttonObj.style.visibility='hidden';
	var ajaxIndex = resizeScriptAjaxObjects.length;
	resizeScriptAjaxObjects[ajaxIndex] = new sack();
	var url = resize_script_server_file + '?image_path=' + xoopsGetElementById('fimage_path').value
	+ '&image_url=' + xoopsGetElementById('fimage_url').value
	+ '&width=' + xoopsGetElementById('resize_width').value
	+ '&height=' + xoopsGetElementById('resize_height').value
	+ '&save=1';

	resizeScriptAjaxObjects[ajaxIndex].requestFile = url;	// Specifying which file to get
	resizeScriptAjaxObjects[ajaxIndex].onCompletion = function(){ resizeCompleted(ajaxIndex,buttonObj); };	// Specify function that will be executed after file has been found
	resizeScriptAjaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
}

function resize_delpreview(){
	var ajaxIndex = resizeScriptAjaxObjects.length;
	resizeScriptAjaxObjects[ajaxIndex] = new sack();
	var url = resize_script_server_file + '?image_path=' + xoopsGetElementById('fimage_path').value
	+ '&image_url=' + xoopsGetElementById('fimage_url').value
	+ '&delprev=1';

	resizeScriptAjaxObjects[ajaxIndex].requestFile = url;	// Specifying which file to get
	resizeScriptAjaxObjects[ajaxIndex].onCompletion = function(){ 	
		eval(resizeScriptAjaxObjects[ajaxIndex].response)
	    resizeScriptAjaxObjects[ajaxIndex] = false;
	    resize_hideProgressBar(); 
	};	// Specify function that will be executed after file has been found
	resizeScriptAjaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
}

function resizeCompleted(ajaxIndex,buttonObj)
{
	buttonObj.style.visibility='';
	eval(resizeScriptAjaxObjects[ajaxIndex].response)
	resizeScriptAjaxObjects[ajaxIndex] = false;
	resize_hideProgressBar();
}

function resize_progressBar()
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
	resize_progressBarMove();
	resize_hideProgressBar();
	
	var menu = xoopsGetElementById('dhtmlgoodies_xpPane');
	top = (window.innerHeight/2)-(div.clientHeight/2);
	left = ((window.innerWidth/2)+(menu.clientWidth)/2)-(div.clientWidth/2);
	
	div.style.top = top+'px';
	div.style.left = left+'px';
}

function resize_hideProgressBar()
{
	document.getElementById('progressBar').style.visibility = 'hidden';

}

function resize_startProgressBar()
{
	var div = document.getElementById('progressBar').getElementsByTagName('DIV')[0];
	div.style.left = '0px';
	document.getElementById('progressBar').style.visibility = 'visible';
}

function resize_progressBarMove()
{
	var div = document.getElementById('progressBar').getElementsByTagName('DIV')[0];
	var left = div.style.left.replace('px','')/1;
	left = left + 1;
	if(left > div.parentNode.clientWidth)left = 0 - div.clientWidth;
	div.style.left = left + 'px';

	setTimeout('resize_progressBarMove()',20);

}