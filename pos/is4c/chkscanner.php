<html>

<head></head>

<body>
<script type="text/javascript">

var xmlHttp;
var xmlmessage = "";

function createXMLHttpRequest() {

	if (window.ActiveXObject) {
		xmlHttp = new ActiveXObject("Microsft.XMLHTTP");
	}
	else if (window.XMLHttpRequest) {
		xmlHttp = new XMLHttpRequest();
	}
}

function startRequest() {
	createXMLHttpRequest();
			
	xmlHttp.onreadystatechange = handleStateChange;
	var myRandom=parseInt(Math.random()*99999999);
	xmlHttp.open("GET", "rs232/scanner?rand=" + myRandom, true);
	xmlHttp.send(null);
}

function handleStateChange() {
	if (xmlHttp.readyState == 4) {
				
		if (xmlHttp.status == 200) {

			var xmldata = xmlHttp.responseText;

			if (xmldata.length >= 9) {
				var inputVal = window.top.input.document.form.reginput.value;
				window.top.input.document.form.reginput.value = "";
				window.top.main_frame.document.form1.input.value =inputVal+xmldata;	
				window.top.main_frame.document.form1.submit();
				var myRandom=parseInt(Math.random()*99999999);
				xmlHttp.open("GET", "clearscanner.php?rand=" + myRandom, true);
				xmlHttp.send(null);
				xmldata = "";
			}
		}
	}
}

function listen() {
	startRequest();
	setTimeout("listen();", 70);

}

listen();


</script>
</body>
</html>
