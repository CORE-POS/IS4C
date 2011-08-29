/* ajax request */
function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        ro = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

/* global request object */
var http = createRequestObject();

/* send action to this page 
   tack on more arguments as needed with '&' and '='
*/
function phpSend(action) {
    http.open('get', 'index.php?action='+action);
    http.onreadystatechange = handleResponse;
    http.send(null);
}

/* ajax callback function 
   by convention, results return [actionname]`[data]
   splitting on backtick separates, then switch on action name
   allows different actions to be handled differently
*/
function handleResponse() {
    if(http.readyState == 4){
        var response = http.responseText;
	switch(response[0]){
	default:
		alert(response);
	}
    }
}

function newPoll(){
	var name = document.getElementById('newPollName').value;
	var content = "<div class=pollname>"+name+"</div>";
	content += "<div id=pollquestions></div>";
	content += "<input type=submit onclick=\"addText();\" value=\"Add Text\" /><br />";
	content += "<input type=submit onclick=\"addQuestion();\" value=\"Add Question\" />";
	content += " <select id=pollQtype>";
	content += "<option>Short text</option>";
	content += "<option>Long text</option>";
	content += "<option>Checkboxes</option>";
	content += "<option>Radio</option>";
	content += "</select>";

	document.getElementById('pollNumQuestions').value=0;
	document.getElementById('polldisplay').innerHTML = content;
}

function addText(){
	var id = Number(document.getElementById('pollNumQuestions').value)+1;
	var content = "<div class=pollObj id=pollObj"+id+">";	
	content += "<div id=pollObjText"+id+"></div></div>";	
	document.getElementById('pollquestions').innerHTML += content;	
	editText(id);
	document.getElementById('pollNumQuestions').value = id;
}

function editText(id){
	var val = document.getElementById('pollObjText'+id).innerHTML;
	var content = "<a href=\"\" onclick=\"saveText("+id+"); return false;\">";
	content += "Save</a><br />";	
	content += "<textarea rows=10 cols=30 id=textObjTA"+id+">";
	content += val;
	content += "</textarea>";
	document.getElementById('pollObj'+id).innerHTML = content;
}

function saveText(id){
	var val = document.getElementById('textObjTA'+id).value;
	var content = "<a href=\"\" onclick=\"editText("+id+"); return false;\">Edit</a>";
	content += "<br />";
	content += "<div id=pollObjText"+id+">"+val+"</div>";
	document.getElementById('pollObj'+id).innerHTML=content;
}

function addQuestion(){
	var qtype = document.getElementById('pollQtype').value;
	switch(qtype){
	case 'Short text':
		addShortText(); break;
	}
}

function addShortText(){
	var id = Number(document.getElementById('pollNumQuestions').value)+1;
	var content = "<div class=pollObj id=pollObj"+id+">";	
	content += "<span id=pollObjShort"+id+"></span><span id=req"+id+"></span></div>";	
	document.getElementById('pollquestions').innerHTML += content;	
	editShortText(id);
	document.getElementById('pollNumQuestions').value = id;
}

function editShortText(id){
	var title = document.getElementById('pollObjShort'+id).innerHTML;
	var req = document.getElementById('req'+id).innerHTML;

	var content = "<a href=\"\" onclick=\"saveShortText("+id+"); return false;\">";
	content += "Save</a><br />";
	content += "Question: <input type=text id=pollObjShortT"+id+" value=\""+title+"\" />";
	content += "<br />";
	content += "<input type=checkbox id=req"+id;
	if (req == "*") content += " checked";
	content += " /> Required";

	document.getElementById('pollObj'+id).innerHTML=content;
}
