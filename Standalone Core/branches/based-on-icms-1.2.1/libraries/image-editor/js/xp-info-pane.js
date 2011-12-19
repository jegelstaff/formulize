
   /************************************************************************************************************
   (C) www.dhtmlgoodies.com, October 2005
   
   This is a script from www.dhtmlgoodies.com. You will find this and a lot of other scripts at our website.   
   
   Terms of use:
   You are free to use this script as long as the copyright message is kept intact. However, you may not
   redistribute, sell or repost it without our permission.
   
   Updated:
   
   February, 22nd 2006 - Instead of skipping onclick events when slide is in progress, start sliding in the other direction if mouse is clicked the second time.
   
   Thank you!
   
   www.dhtmlgoodies.com
   Alf Magne Kalleland
   
   ************************************************************************************************************/   

   /* Update LOG
   
   January, 28th - Fixed problem when double clicking on a pane(i.e. expanding and collapsing).
   
   */
   var xpPanel_slideActive = true;   // Slide down/up active?
   var xpPanel_slideSpeed = 15;   // Speed of slide
   
   var dhtmlgoodies_xpPane;
   var dhtmlgoodies_paneIndex;
   
   var savedActivePane = false;
   var savedActiveSub = false;

   var xpPanel_currentDirection = false;
   
   var cookieNames = new Array();
   
   var xpPanel_onlyOneExpandedPane = true; //Enable or Disable my changes. False to perm more then 1 pane open
   var paneIDs = new Array(); //Array to get the contentDiv of All panels in the script
   var permPanels = new Array(); //Array to get All panels that still open together.
   
   var exec_function = new Array();
   var stop_function = new Array();
   
   /*
   These cookie functions are downloaded from
   http://www.mach5.com/support/analyzer/manual/html/General/CookiesJavaScript.htm
   */   
   function Get_Cookie(name) {
      var start = document.cookie.indexOf(name+"=");
      var len = start+name.length+1;
      if ((!start) && (name != document.cookie.substring(0,name.length))) return null;
      if (start == -1) return null;
      var end = document.cookie.indexOf(";",len);
      if (end == -1) end = document.cookie.length;
      return unescape(document.cookie.substring(len,end));
   }
   // This function has been slightly modified
   function Set_Cookie(name,value,expires,path,domain,secure) {
      expires = expires * 60*60*24*1000;
      var today = new Date();
      var expires_date = new Date( today.getTime() + (expires) );
       var cookieString = name + "=" +escape(value) +
          ( (expires) ? ";expires=" + expires_date.toGMTString() : "") +
          ( (path) ? ";path=" + path : "") +
          ( (domain) ? ";domain=" + domain : "") +
          ( (secure) ? ";secure" : "");
       document.cookie = cookieString;
   }

   function cancelXpWidgetEvent()
   {
      return false;   
      
   }
   
   function showHidePaneContent(e,inputObj)
   {
      if(!inputObj)inputObj = this;
      
      var img = inputObj.getElementsByTagName('IMG')[0];
      var numericId = img.id.replace(/[^0-9]/g,'');
      var obj = document.getElementById('paneContent' + numericId);
      if(img.src.toLowerCase().indexOf('up')>=0){
         img.src = img.src.replace('up','down');
         if(xpPanel_slideActive){
            obj.style.display='block';
            xpPanel_currentDirection = (xpPanel_slideSpeed*-1);
            slidePane((xpPanel_slideSpeed*-1), obj.id);
         }else{
            obj.style.display='none';
         }
         if (stop_function[numericId] != ''){
         	eval(stop_function[numericId]);
         }
         if(cookieNames[numericId])Set_Cookie(cookieNames[numericId],'0',100000);
      }else{
         img.src = img.src.replace('down','up');
         if(xpPanel_slideActive){
            if(document.all){
               obj.style.display='block';
               //obj.style.height = '1px';
            }
            xpPanel_currentDirection = xpPanel_slideSpeed;
            slidePane(xpPanel_slideSpeed,obj.id);
         }else{
            obj.style.display='block';
            subDiv = obj.getElementsByTagName('DIV')[0];
            obj.style.height = subDiv.offsetHeight + 'px';
         }
         if (exec_function[numericId] != ''){
         	eval(exec_function[numericId]);
         }
         if(cookieNames[numericId])Set_Cookie(cookieNames[numericId],'1',100000);
      }
      
      //Execute hidePanels function to close all other panels that not set to stay open in init.
      if (xpPanel_onlyOneExpandedPane)hidePanels(numericId);

      return true;   
   }
   
   /**
    * function to corret the height of panels that content changed by ajax.
    * @params: int inputObj - ID of the panel
    */
   function updatePaneHeight(e,inputObj)
   {
      if(!inputObj)inputObj = this;

      var img = inputObj.getElementsByTagName('IMG')[0];
      var numericId = img.id.replace(/[^0-9]/g,'');
      var obj = document.getElementById('paneContent' + numericId);
      subDiv = obj.getElementsByTagName('DIV')[0];
      obj.style.height = subDiv.offsetHeight + 'px';
      
      return true;   
   }
   
   /**
    * function to close all other panels that not set to stay open in init.
    * @params: int numericId - ID of the panel clicked
    */
   function hidePanels(numericId){
      for (i=0;i<=permPanels.length-1;i++){
         contentDiv = paneIDs[i];
         outerContentDiv = (document.getElementById('paneContent'+i))?document.getElementById('paneContent'+i):null;
         img = document.getElementById('showHideButton'+i);
         if(!permPanels[i] && i != numericId){
            outerContentDiv.style.height = '0px';
            contentDiv.style.top = 0 - contentDiv.offsetHeight + 'px';
            if(document.all)outerContentDiv.style.display='none';
            img.src = 'images/arrow_down.gif';
            if (stop_function[i] != ''){
            	eval(stop_function[i]);
            }
         }
      }
   }
   
   function slidePane(slideValue,id)
   {
      if(slideValue!=xpPanel_currentDirection){
         return false;
      }
      var activePane = document.getElementById(id);
      if(activePane==savedActivePane){
         var subDiv = savedActiveSub;
      }else{
         var subDiv = activePane.getElementsByTagName('DIV')[0];
      }
      savedActivePane = activePane;
      savedActiveSub = subDiv;
      
      var height = activePane.offsetHeight;
      var innerHeight = subDiv.offsetHeight;
      height+=slideValue;
      if(height<0)height=0;
      if(height>innerHeight)height = innerHeight;
      
      if(document.all){
         activePane.style.filter = 'alpha(opacity=' + Math.round((height / subDiv.offsetHeight)*100) + ')';
      }else{
         var opacity = (height / subDiv.offsetHeight);
         if(opacity==0)opacity=0.01;
         if(opacity==1)opacity = 0.99;
         activePane.style.opacity = opacity;
      }         
      
               
      if(slideValue<0){         
         activePane.style.height = height + 'px';
         subDiv.style.top = height - subDiv.offsetHeight + 'px';
         if(height>0){
            setTimeout('slidePane(' + slideValue + ',"' + id + '")',10);
         }else{
            if(document.all)activePane.style.display='none';
         }
      }else{         
         subDiv.style.top = height - subDiv.offsetHeight + 'px';
         activePane.style.height = height + 'px';
         if(height<innerHeight){
            setTimeout('slidePane(' + slideValue + ',"' + id + '")',10);            
         }      
      }   
   }
   
   function mouseoverTopbar()
   {
      var img = this.getElementsByTagName('IMG')[0];
      var src = img.src;
      img.src = img.src.replace('.gif','_over.gif');
      
      var span = this.getElementsByTagName('SPAN')[0];
      span.style.color='#428EFF';      
      
   }
   function mouseoutTopbar()
   {
      var img = this.getElementsByTagName('IMG')[0];
      var src = img.src;
      img.src = img.src.replace('_over.gif','.gif');      
      
      var span = this.getElementsByTagName('SPAN')[0];
      span.style.color='';
   }
   
   
   function initDhtmlgoodies_xpPane(panelTitles,panelDisplayed,cookieArray)
   {
      dhtmlgoodies_xpPane = document.getElementById('dhtmlgoodies_xpPane');
      var divs = dhtmlgoodies_xpPane.getElementsByTagName('DIV');
      dhtmlgoodies_paneIndex=0;
      cookieNames = cookieArray;
      permPanels = panelDisplayed;  //Geting the panels with perm to stay open together
      for(var no=0;no<divs.length;no++){
         if(divs[no].className=='dhtmlgoodies_panel'){
            var outerContentDiv = document.createElement('DIV');   
            var contentDiv = divs[no].getElementsByTagName('DIV')[0];
            outerContentDiv.appendChild(contentDiv);
            
            //Get the contentDiv of all panels to use in hidePanels function
            paneIDs[dhtmlgoodies_paneIndex] = contentDiv;
            
            outerContentDiv.id = 'paneContent' + dhtmlgoodies_paneIndex;
            outerContentDiv.className = 'panelContent';
            var topBar = document.createElement('DIV');
            topBar.id = 'xpPaneTopBar' + dhtmlgoodies_paneIndex;
            topBar.onselectstart = cancelXpWidgetEvent;
            var span = document.createElement('SPAN');            
            span.innerHTML = panelTitles[dhtmlgoodies_paneIndex];
            topBar.appendChild(span);
            topBar.onclick = showHidePaneContent;
            if(document.all)topBar.ondblclick = showHidePaneContent;
            topBar.onmouseover = mouseoverTopbar;
            topBar.onmouseout = mouseoutTopbar;
            topBar.style.position = 'relative';

            var img = document.createElement('IMG');
            img.id = 'showHideButton' + dhtmlgoodies_paneIndex;
            img.src = 'images/arrow_up.gif';            
            topBar.appendChild(img);
            
            if(cookieArray[dhtmlgoodies_paneIndex]){
               cookieValue = Get_Cookie(cookieArray[dhtmlgoodies_paneIndex]);
               if(cookieValue)panelDisplayed[dhtmlgoodies_paneIndex] = cookieValue==1?true:false;
               
            }
            
            if(!panelDisplayed[dhtmlgoodies_paneIndex]){
               outerContentDiv.style.height = '0px';
               contentDiv.style.top = 0 - contentDiv.offsetHeight + 'px';
               if(document.all)outerContentDiv.style.display='none';
               img.src = 'images/arrow_down.gif';
            }
                        
            topBar.className='topBar';
            divs[no].appendChild(topBar);            
            divs[no].appendChild(outerContentDiv);   
            dhtmlgoodies_paneIndex++;         
         }         
      }
   }
   
   var ScriptAjaxObjects = new Array();
   
   function cancel_edit(){
   	var ajaxIndex = cropScriptAjaxObjects.length;
   	startProgressBar();
   	cropScriptAjaxObjects[ajaxIndex] = new sack();
   	var url = script_server_file + '?op=cancel&image_path=' + document.getElementById('save_img_path').value;

   	cropScriptAjaxObjects[ajaxIndex].requestFile = url;	// Specifying which file to get
   	cropScriptAjaxObjects[ajaxIndex].onCompletion = function(){
   		eval(cropScriptAjaxObjects[ajaxIndex].response)
   		cropScriptAjaxObjects[ajaxIndex] = false;
   		hideProgressBar();
   	};	// Specify function that will be executed after file has been found
   	cropScriptAjaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
   }
   
   function save_edit(){
   	var ajaxIndex = cropScriptAjaxObjects.length;
   	startProgressBar();
   	cropScriptAjaxObjects[ajaxIndex] = new sack();
   	var url = script_server_file + '?op=save&image_id=' + document.getElementById('save_img_id').value
   	+'&image_temp=' + document.getElementById('save_img_tempname').value
   	+'&image_name=' + document.getElementById('save_img_name').value
   	+'&image_weight=' + document.getElementById('save_img_weight').value
   	+'&image_display=' + document.getElementById('save_img_display').value
   	+'&overwrite=' + document.getElementById('soverwrite').value;

   	cropScriptAjaxObjects[ajaxIndex].requestFile = url;	// Specifying which file to get
   	cropScriptAjaxObjects[ajaxIndex].onCompletion = function(){
   		eval(cropScriptAjaxObjects[ajaxIndex].response)
   		cropScriptAjaxObjects[ajaxIndex] = false;
   		hideProgressBar();
   	};	// Specify function that will be executed after file has been found
   	cropScriptAjaxObjects[ajaxIndex].runAJAX();		// Execute AJAX function
   }
   
function overpanel(value){
	panel = document.getElementById('overpanel');
	input = document.getElementById('soverwrite');
	if (value == 1){
		panel.style.display = 'none';
	}else{
		panel.style.display = 'block';
	}
	input.value = value;
}

function getOpenerUrl(){
	var fullurl = window.opener.location.href;
	var fullurl_pieces = fullurl.split("/");
	var endurl = fullurl_pieces[(fullurl_pieces.length)-1];
	var url = '';
	for (i=0; i<fullurl_pieces.length-1;i++){
		if (i > 0){
			url += '/';
		}
		url += fullurl_pieces[i];
	}
	url += '/'+endurl.split("?")[0];
	
	return url;
}

function progressBar()
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
	progressBarMove();
	hideProgressBar();
	
	var menu = xoopsGetElementById('dhtmlgoodies_xpPane');
	top = (window.innerHeight/2)-(div.clientHeight/2);
	left = ((window.innerWidth/2)+(menu.clientWidth)/2)-(div.clientWidth/2);
	
	div.style.top = top+'px';
	div.style.left = left+'px';
}

function hideProgressBar()
{
	document.getElementById('progressBar').style.visibility = 'hidden';

}

function startProgressBar()
{
	var div = document.getElementById('progressBar').getElementsByTagName('DIV')[0];
	if (!div){
		progressBar();
		var div = document.getElementById('progressBar').getElementsByTagName('DIV')[0];
	}
	div.style.left = '0px';
	document.getElementById('progressBar').style.visibility = 'visible';
}

function progressBarMove()
{
	var div = document.getElementById('progressBar').getElementsByTagName('DIV')[0];
	var left = div.style.left.replace('px','')/1;
	left = left + 1;
	if(left > div.parentNode.clientWidth)left = 0 - div.clientWidth;
	div.style.left = left + 'px';

	setTimeout('progressBarMove()',20);

}

function resizePanels(){
	var menu = xoopsGetElementById('dhtmlgoodies_xpPane');
	var content = xoopsGetElementById('contentarea');
	
	menu.style.height = window.innerHeight+'px';
	content.style.height = window.innerHeight+'px';
	content.style.width = window.innerWidth-menu.clientWidth-1+'px';
}