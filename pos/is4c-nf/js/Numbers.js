
var ParentField = null;

function ND_show(pf){
	ParentField = pf;

	if (top.main_frame.document.getElementById('numpad')){
		top.main_frame.document.getElementById('numpad').style.display = 'block';
	}
	else {
		top.main_frame.document.body.innerHTML += drawtable();
	}
}

function ND_hide(){
	top.main_frame.document.getElementById('numpad').style.display='none';
	ParentField = null;
}

function drawtable(){
	var ret = "<table id=numpad><tr>";
	ret += "<td onclick=\"top.doTask('7');\">7</td>";
	ret += "<td onclick=\"top.doTask('8');\">8</td>";
	ret += "<td onclick=\"top.doTask('9');\">9</td>";
	ret += "</tr><tr>";
	ret += "<td onclick=\"top.doTask('4');\">4</td>";
	ret += "<td onclick=\"top.doTask('5');\">5</td>";
	ret += "<td onclick=\"top.doTask('6');\">6</td>";
	ret += "</tr><tr>";
	ret += "<td onclick=\"top.doTask('1');\">1</td>";
	ret += "<td onclick=\"top.doTask('2');\">2</td>";
	ret += "<td onclick=\"top.doTask('3');\">3</td>";
	ret += "</tr><tr>";
	ret += "<td onclick=\"top.doTask('0');\">0</td>";
	ret += "<td onclick=\"top.doTask('.');\">.</td>";
	ret += "<td onclick=\"top.doTask('00');\">00</td>";
	ret += "</tr><tr>";
	ret += "<td onclick=\"top.doTask('*');\">*</td>";
	ret += "<td onclick=\"top.doTask('back');\">&lt;</td>";
	ret += "<td onclick=\"top.doTask('clear');\">[X]</td>";
	ret += "</tr></table>";
	return ret;
}

function doTask(str){
	var val;
	switch(str){
	case 'clear':
		ND_hide();
		break;
	case 'back':
		val = ParentField.value;
		if (val.length > 0){
			val = val.substring(0,val.length-1);
			ParentField.value = val;
		}
		break;
	default:
		val = ParentField.value;
		val += str;
		ParentField.value = val; 
	}
}
