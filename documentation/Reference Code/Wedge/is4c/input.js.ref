function createXMLHttpRequest() {
    if (window.ActiveXObject) {
        try {
            xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
        }
        catch(e){}
    }
    else if (window.XMLHttpRequest) {
        xmlHttp = new XMLHttpRequest();
    }
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
                connectionStatus = "<font size='-1' face='arial' color='#004080'>training</font>";
                dot = "<img src='graphics/BLUEDOT.GIF' alt='Blue dot' />";
            }
            else if ( standalone == 1 ) {
                connectionStatus = "<font size='-1' face='arial' color='#800000'>stand alone</font>";
                dot = "<img src='graphics/REDDOT.GIF' alt='Red dot'>";
            }
            else {
                connectionStatus = "";
                dot = "<img src='graphics/GREENDOT.GIF' alt='Blue dot'>";
            }
            document.getElementById("connectionStatus").innerHTML = connectionStatus;
            document.getElementById("dot").innerHTML = dot;
            document.getElementById("ccstatus").innerHTML = ccstatus;
            document.getElementById("clock").innerHTML = datetimestamp;
        }
    }
}

function startRequest() {
    createXMLHttpRequest();
    xmlHttp.onreadystatechange = handleStateChange;
    var myRandom=parseInt(Math.random()*99999999, 10);
    xmlHttp.open("GET", "clock.php?rand=" + myRandom, true);
    xmlHttp.send(null);
}

function listen() {
    startRequest();
    setTimeout("listen();", 1000);
}

listen();
