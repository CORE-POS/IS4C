function createXMLHttpRequest() {
	
	if (window.ActiveXObject) {

		try { xmlHttp=new ActiveXObject("Microsoft.XMLHTTP"); }
		catch(e){}
	}
	else if (window.XMLHttpRequest) {
		xmlHttp = new XMLHttpRequest();
	}
}

function startRequest() {
	createXMLHttpRequest();
	xmlHttp.onreadystatechange = handleStateChange;
	var myRandom=parseInt(Math.random()*99999999);
	xmlHttp.open("GET", "clock.php?rand=" + myRandom, true);
	xmlHttp.send(null);
}

function handleStateChange() {
	if (xmlHttp.readyState == 4) {
		
		if (xmlHttp.status == 200) {

			var xmldata = xmlHttp.responseText;
			var xmlArray = xmldata.split('::');
			var datetimestamp = xmlArray[0];
			var standalone = xmlArray[1];
			var training = xmlArray[2];
			var ccstatus = xmlArray[3];
			var connectionStatus = '';
			var dot = '';

			if ( training == 1 ) {
				connectionStatus = "<FONT size='-1' face='arial' color='#004080'>training</FONT>";
				dot = "<IMG src='graphics/BLUEDOT.GIF'>";
			}
			else if ( standalone == 1 ) {
				connectionStatus = "<FONT size='-1' face='arial' color='#800000'>stand alone</FONT>";
				dot = "<IMG src='graphics/REDDOT.GIF'>";
			}
			else {
				connectionStatus = "";
				dot = "<IMG src='graphics/GREENDOT.GIF'>";
			}
			document.getElementById("connectionStatus").innerHTML = connectionStatus;
			document.getElementById("dot").innerHTML = dot;
			document.getElementById("ccstatus").innerHTML = ccstatus;
			document.getElementById("clock").innerHTML = datetimestamp;
		}
	}
}

function listen() {
	startRequest();
	setTimeout("listen();", 1000);
}

listen();