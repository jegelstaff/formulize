function showDiv(type,id){
	divs = document.getElementsByTagName('div');
	for (i=0; i<divs.length;i++){
		if (/opt_divs/.test(divs[i].className)){
			divs[i].style.display = 'none';
		}
	}
	if (!id)id = '';
	document.getElementById(type+id).style.display = 'block';
}

function overpanel(id,value){
	panel = document.getElementById(id+'overpanel');
	if (value == 1){
		panel.style.display = 'none';
	}else{
		panel.style.display = 'block';
	}
}

function actField(value,id){
	var field = document.getElementById(id);
	if (value == 'file'){
		field.disabled = false;
	}else{
		field.value = '';
		field.disabled = true;
	}
}

function addItem(itemurl, name, target, cat, url, type) {
	var win = opener;
	var campo = win.document.getElementById(target);
	var opcoes = win.document.getElementById('img_cat_'+cat);
	var imagem = win.document.getElementById(target+'_img');
	if (!type){
		if(opcoes){
			for(x=0; x<campo.options.length; x++){
				if(campo.options[x].value == itemurl){
					campo.options[x].selected = true;
					imagem.src = url+itemurl;
					var found = true;
				}
			}
			if(!found){
				var newOption = win.document.createElement("option");
				opcoes.appendChild(newOption);
				newOption.text = name;
				newOption.value = itemurl;
				newOption.selected = true;
				imagem.src = url+itemurl;
			}
		}
	}else{
		win.document.getElementById(target).value=itemurl;
		if(target == "src") {
			if(win.document.getElementById('title')) window.opener.document.getElementById('title').value=name;
			if(win.document.getElementById('alt')) window.opener.document.getElementById('alt').value=name;
			if(win.XoopsimagemanagerDialog.showPreviewImage) window.opener.XoopsimagemanagerDialog.showPreviewImage(itemurl);
		}
	}
	window.close();
	return;
}

function appendCode(addCode,target) {
	var targetDom = window.opener.xoopsGetElementById(target);
	if (targetDom.createTextRange && targetDom.caretPos){
		var caretPos = targetDom.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1)
		== ' ' ? addCode + ' ' : addCode;
	} else if (targetDom.getSelection && targetDom.caretPos){
		var caretPos = targetDom.caretPos;
		caretPos.text = caretPos.text.charat(caretPos.text.length - 1)
		== ' ' ? addCode + ' ' : addCode;
	} else {
		targetDom.value = targetDom.value + addCode;
	}
	window.close();
	return;
}

function selFilter(id,value){
	var div_arg1 = xoopsGetElementById(id+'_arg1');
	var div_arg2 = xoopsGetElementById(id+'_arg2');
	var div_arg3 = xoopsGetElementById(id+'_arg3');
	
	var filter_desc = xoopsGetElementById(id+'_filterdesc');
	
	var targ1 = xoopsGetElementById(id+'_targ1');
	var targ2 = xoopsGetElementById(id+'_targ2');
	var targ3 = xoopsGetElementById(id+'_targ3');
	
	var arg1 = xoopsGetElementById(id+'arg1');
	var arg2 = xoopsGetElementById(id+'arg2');
	var arg3 = xoopsGetElementById(id+'arg3');
	
	var larg1 = xoopsGetElementById(id+'_larg1');
	var larg2 = xoopsGetElementById(id+'_larg2');
	var larg3 = xoopsGetElementById(id+'_larg3');

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
		filter_desc.innerHTML = filter['descr'];

		if (filter['args'].length > 0){
			for (i=0;i<=filter['args'].length-1;i++){
				var x = i+1;
				eval('div_arg'+x).style.display = 'block';
				eval('targ'+x).innerHTML = filter['args'][i]['title'];
				eval('larg'+x).innerHTML = filter['args'][i]['descr'];
				eval('arg'+x).value = filter['args'][i]['value'];
			}
		}
	}
}

function filter_preview(id,xurl,img,width,height){
	var filter = xoopsGetElementById('filter'+id).value;
	var arg1 = xoopsGetElementById(id+'arg1').value;
	var arg2 = xoopsGetElementById(id+'arg2').value;
	var arg3 = xoopsGetElementById(id+'arg3').value;
	
	for (i=0;i<=Filters.length-1;i++){
		if (Filters[i].value == filter){
			var filterdata = Filters[i];
		}
	}
	
	var url = xurl+"/modules/system/admin/images/preview.php?file="+img+"&filter="+filter;
	
	if (filterdata['args'].length > 0){
		for (i=0;i<=filterdata['args'].length-1;i++){
			var x = i+1;
			url = url+'&arg'+x+'='+eval('arg'+x);
		}
	}

	window.open(url,'preview_image','width='+(width+20)+',height='+(height+20)+',resizable=yes');
}