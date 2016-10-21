
var LD_ParentField = null;

function LD_show(pf){
	LD_ParentField = pf;

	if (top.main_frame.document.getElementById('letterpad')){
		top.main_frame.document.getElementById('letterpad').style.display = 'block';
	}
	else {
		top.main_frame.document.body.innerHTML += LD_drawtable();
	}
}

function LD_hide(){
	top.main_frame.document.getElementById('letterpad').style.display='none';
	LD_ParentField = null;
}

function LD_drawtable(){
	var ret = "<table id=letterpad><tr>";
	ret += "<td onclick=\"top.LD_doTask('q');\">Q</td>";
	ret += "<td onclick=\"top.LD_doTask('w');\">W</td>";
	ret += "<td onclick=\"top.LD_doTask('e');\">E</td>";
	ret += "<td onclick=\"top.LD_doTask('r');\">R</td>";
	ret += "<td onclick=\"top.LD_doTask('t');\">T</td>";
	ret += "<td onclick=\"top.LD_doTask('y');\">Y</td>";
	ret += "<td onclick=\"top.LD_doTask('u');\">U</td>";
	ret += "<td onclick=\"top.LD_doTask('i');\">I</td>";
	ret += "<td onclick=\"top.LD_doTask('o');\">O</td>";
	ret += "<td onclick=\"top.LD_doTask('p');\">P</td>";
	ret += "</tr><tr>";
	ret += "<td onclick=\"top.LD_doTask('a');\">A</td>";
	ret += "<td onclick=\"top.LD_doTask('s');\">S</td>";
	ret += "<td onclick=\"top.LD_doTask('d');\">D</td>";
	ret += "<td onclick=\"top.LD_doTask('f');\">F</td>";
	ret += "<td onclick=\"top.LD_doTask('g');\">G</td>";
	ret += "<td onclick=\"top.LD_doTask('h');\">H</td>";
	ret += "<td onclick=\"top.LD_doTask('j');\">J</td>";
	ret += "<td onclick=\"top.LD_doTask('k');\">K</td>";
	ret += "<td onclick=\"top.LD_doTask('l');\">L</td>";
	ret += "<td onclick=\"top.LD_doTask('back');\">&lt;</td>";
	ret += "</tr><tr>";
	ret += "<td onclick=\"top.LD_doTask('z');\">Z</td>";
	ret += "<td onclick=\"top.LD_doTask('x');\">X</td>";
	ret += "<td onclick=\"top.LD_doTask('c');\">C</td>";
	ret += "<td onclick=\"top.LD_doTask('v');\">V</td>";
	ret += "<td onclick=\"top.LD_doTask('b');\">B</td>";
	ret += "<td onclick=\"top.LD_doTask('n');\">N</td>";
	ret += "<td onclick=\"top.LD_doTask('m');\">M</td>";
	ret += "<td colspan=2 onclick=\"top.LD_doTask(' ');\">&nbsp;</td>";
	ret += "<td onclick=\"top.LD_doTask('clear');\">[X]</td>";
	ret += "</tr><tr>";
	ret += "</tr></table>";
	return ret;
}

function LD_doTask(str){
	var val;
	switch(str){
	case 'clear':
		LD_hide();
		break;
	case 'back':
		val = LD_ParentField.value;
		if (val.length > 0){
			val = val.substring(0,val.length-1);
			LD_ParentField.value = val;
		}
		break;
	default:
		val = LD_ParentField.value;
		val += str;
		LD_ParentField.value = val; 
	}
}
