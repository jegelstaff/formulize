$(document).ready(function () {
	var saveCounter = 0;
	var saveTarget = 0;
	var redirect = "";
	var newhandle = "";
	var formdata = new Array();
	/*Save changes*/
	$(".savebutton").click(function () {
		console.log("Save");
		if (validateRequired()) {
			runSaveEvent();
		}
	});

	function runSaveEvent() {
		//$(".admin-ui").fadeTo(1, 0.5);
		var formulize_formlist = $(".formulize-admin-form");
		saveCounter = 0;
		saveTarget = 1;//tenp
		redirect = "";
		formdata = new Array();
		for (i = 0; i < formulize_formlist.length; i++) {
			if (typeof (formulize_formlist[i]) == 'object') { 
				formdata[saveTarget] = formulize_formlist[i];
				saveTarget = saveTarget + 1;
			}
		}
		if (saveTarget > 0) {
			sendFormData(formdata[0]); // send the first form's data 
		}
	}
	
	
	function sendFormData(thisformdata) {
		$.post("../../../modules/formulize/admin/save.php", $(thisformdata).serialize(), function (data) {
		console.log($(thisformdata).serialize() + ", data:"+data);

			
		});
	}

	function validateRequired() {
		/*var requiredok = true;
		$(".required_formulize_element").each(function () {
			if (($(this).val().length) == 0) {
				requiredok = false;
			}
		});
		return requiredok;*/
		return true;
	}
	
	function debug(data){
		return '<pre>'+$data+'</pre>';
	}
	
	/*Create a new relationship with a form*/
	$(".form").draggable({
		helper: 'clone',
		appendTo: 'body',
		containment: "document",
		handle: ".fa-grip-vertical",
		cursor: 'grabbing',
		refreshPositions: true,
		start: function (event, ui) {
			$(ui.helper).removeClass();
			$(ui.helper).addClass("formDragged");

			$(".addNewRel").css("display", "block");
			//$(".drop-container").addClass("visible");
			//$(".drop-container2").addClass("visible");
		},
		revert: function () {
			$(".addNewRel").css("display", "none");
			//$(".drop-container").removeClass("visible");
			//$(".drop-container2").removeClass("visible");
		}
	});
	
	$('.removeRel').droppable({
		accept: ".ui-sortable-helper",

		over: function (event, ui) {
			console.log($(this).attr('class'));
			//test
		},
		drop: function (event, ui) {
			console.log(ui.sender);		
			//remove relationships 
		},
	});
	
	refreshCounters();
	setRelationships(); //temp
	addSort($('#root')); 
	toDrop($('.addNewRel')); 
	
});
$(document).on('click', '.branch', function () {
	toggleContainer(this);
});


function addSort($toSort){
	$toSort.sortable({
		handle: ".fa-grip-vertical",
		placeholder: 'shadow',
		sort: function (event, ui) {
			$(".shadow").height(ui.item.height());
			$(".removeRel").css("display", "block");
		},
		stop: function (event, ui) {
			$(".removeRel").css("display", "none");
			//$(".drop-container").removeClass("visible");
			//$(".drop-container2").removeClass("visible");
		},
		receive: function (event, ui) { // add this handler
        	ui.item.remove(); // remove original item
    	}
	});
}

function toDrop($ele){
	$ele.droppable({
		accept: ".form",
		//hoverClass: "drop-highlight",
		start: function (event, ui) {
			this.item.height(this.item.height());
		},
		drop: function (event, ui) {
			var $container = $(this).children('.tree'); 
			var formId = ui.draggable.attr("id");
			openPopup($container, formId);
			$(this).css("opacity", "1");
		},
		over: function (event, ui) {
			//console.log('You are over item with id ' + $(this).attr('id'));
			branch = $(this).children('.branch'); //droppable area
			
			$(this).css("opacity", "0.6");
			openContainer(branch);

		},
		out: function (event, ui) {
			branch = $(this).children('.branch'); //droppable area
			$(this).css("opacity", "1");

			// *kinda buggy looking			
			closeContainer(branch);
		}
	});
}

/*toggle, open, and close relationship containers*/
function toggleContainer(container) {
	$(container).find('.caret').toggleClass('caret-down');
	$(container).next().slideToggle();
}

function openContainer(container) {
	$(container).find('.caret').addClass('caret-down');
	$(container).next().slideDown();
}

function closeContainer(container) {
	$(container).find('.caret').removeClass('caret-down');
	$(container).next().slideUp();
}

function closeAll() {
	closeContainer($('.branch'));
}

function openAll() {
	openContainer($('.branch'));
}

function refreshCounters() {
	$('.counter').each(function () {
		var count = $(this).parent().siblings('.tree').children().length - 1;
		$(this).text(count);
	});
}

function setRelationships() {
	$('.relType').each(function () {
		$(this).text("1 : N");
	});
}

/*Create a new relationship*/
function openPopup(addContainer, formId) {
	var popup = document.getElementById('RelationshipPopup');
	popup.style.display = "block";

	var submit = document.getElementsByClassName("popup-submit")[0];
	submit.onclick = function () {
		popup.style.display = "none";
		
		var data = [formId,'test','One to One'];

		var $newForm = addSubform(data);//temp

		//addContainer.append($newForm);
		$newForm.insertBefore(addContainer.children('.addNewRel').first());
		refreshCounters();

	}

	var close = popup.getElementsByClassName("close")[0];
	close.onclick = function () {
		popup.style.display = "none";
	}

}
var $addForm = '<li class="addNewRel"><span><i class="fa fa-plus"></i> Add new relationship</span></li>';
function addRel(relId, relName, forms) {
	var subForms = [];
	forms.forEach(function (form) {
		subForms.push(addSubform(form));
	});

	$leader = $('<li id="'+relId+'"><span class="branch"><i class="fas fa-grip-vertical"></i> ' + relName + ' <span><i class="fas fa-chevron-up caret"></i></span><span class="counter">1</span></span>');
	
	$subFormContainer = $('<ul class="tree">');
	addSort($subFormContainer);
	toDrop($leader);
	
	//$subForms.appendTo($subFormContainer);
	$subFormContainer.append(subForms);
	
	$subFormContainer.append($addForm);
	
	$leader.append($subFormContainer).appendTo($('#root'));
}

function addSubform(subform) {
	var $html;
		$html = $('<li class="leaf ui-droppable"><span><i class="fas fa-grip-vertical"></i> '+subform[0]+' + '+subform[1]+' <span class="relType"> ' + subform[2] + '</span></span></li>');
	
	//$html.append('<li class="addNewRel"><span><i class="fa fa-plus"></i> Add new relationship</span></li>');

	return $html;
}

//Server calls
//TEMP
/*
  var saveCounter = 0;
  var saveTarget = 0;
  var redirect = "";
  var newhandle = "";
  var formdata = new Array();
  
  $("input").change(function() {
    setDisplay('savewarning','block');
    });
  $("input[type=text]").keydown(function() {
    setDisplay('savewarning','block');
    });
  $("select").change(function() {
    setDisplay('savewarning','block');
    });
  $("textarea").keydown(function() {
    setDisplay('savewarning','block');
    });

  $(".savebutton").click(function() {
		console.log("Save");
    if(validateRequired()) {
      runSaveEvent();
		console.log("HA");
    }
  });

  function runSaveEvent() {
    $(".admin-ui").fadeTo(1,0.5);
    var formulize_formlist = $(".formulize-admin-form");
    saveCounter = 0;
    saveTarget = 0;
    redirect = "";
    formdata = new Array();
    for(i=0;i<formulize_formlist.length;i++) {
      if(typeof(formulize_formlist[i]) == 'object') { // for some crazy reason, non-form stuff can be pulled in by getElementsByName with that param...I hate javascript
        formdata[saveTarget] = formulize_formlist[i];
        saveTarget = saveTarget + 1;
      }
    }
    if(saveTarget > 0) {
      sendFormData(formdata[0]); // send the first form's data 
    }
  }
  
  function sendFormData(thisformdata, ele_id) {
    if(!ele_id) { ele_id = 0 }
    $.post("save.php?ele_id="+ele_id, $(thisformdata).serialize(), function(data) {
      saveCounter = saveCounter + 1;
      if(data) {
        if(data.substr(0,10)=="") {
          redirect = data;
        } else if(data.substr(0,13)=="") {
          eval(data);
        } else {
          alert(data);
        }
      }
      if(saveCounter >= saveTarget) { // if we've received a response for all the forms...
        setDisplay('savewarning','none');
        $(".savebutton").blur();
        if(newhandle) {
          $("[name=original_handle]").val(newhandle);
        }
        if(redirect) {
          eval(redirect);
        } else {
          $(".admin-ui").fadeTo(1,1);
        }
      } else { // if there's still forms to do, then send the next one...must do sequentially to avoid race conditions
        sendFormData(formdata[saveCounter], ele_id);
      }
    });
  }
  
  function reloadWithScrollPosition(url) {
    if(url) {
      $("[name=scrollposition]").attr('action', url);
    }
    window.document.scrollposition.scrollx.value = $(window).scrollTop();
    var tabs_selected = "";
     
    tabs_selected = $("#tabs").tabs("option","selected");
    window.document.scrollposition.tabs_selected.value = tabs_selected;
    tabs_selected = tabs_selected+1;
        var accordion_active = "";
    if(pagehasaccordion["accordion-"+tabs_selected]) {
            accordion_active = $("#accordion-"+tabs_selected).accordion( "option", "active" );
    }
    window.document.scrollposition.accordion_active.value = accordion_active;
    window.document.scrollposition.submit();
  }

  function validateRequired() {
    var requiredok = true;
    $(".required_formulize_element").each(function () {
      if(($(this).val().length) == 0) {
        requiredok = false;
      }
    });
    return requiredok;
  }

  $().ajaxError(function () {
    alert("There was an error when saving your data.  Please try again.");
  });
  
  $(window).load(function () {
    $(window).scrollTop(0);
  });

  function setDisplay( elementId, styleDisplay ) {
    var element = window.document.getElementById( elementId );
    if( element ) {
      element.style.display = styleDisplay;
    }
  }

  $(document).ready(
    $('.code-textarea').each(function() {
        if (this.type !== 'textarea' || SELENIUM_DEBUG == 'ON') {
            return true; // continue
        }
        CodeMirror.fromTextArea(this, {
            lineNumbers: true,
            matchBrackets: true,
            mode: "application/x-httpd-php",
            indentUnit: 4,
            indentWithTabs: true,
            enterMode: "keep",
            tabMode: "shift",
            lineWrapping: true,
            onChange: function(instance) { 
                setDisplay('savewarning','block');
                instance.save(); // Call this to update the textarea value for the ajax post
            }
        });
    })
  );*/
