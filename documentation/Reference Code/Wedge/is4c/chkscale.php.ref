<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head></head>
    <body>
        <script type="text/javascript">
            var xmlHttp;
            var anotherXmlHttp;
            var xmldata = '';

            function createXMLHttpRequest() {
                if (window.ActiveXObject) {
                    xmlHttp = new ActiveXObject("Microsft.XMLHTTP");
                }
                else if (window.XMLHttpRequest) {
                    xmlHttp = new XMLHttpRequest();
                }
            }

            function createAnotherXMLHttpRequest() {
                if (window.ActiveXObject) {
                    anotherXmlHttp = new ActiveXObject("Microsft.XMLHTTP");
                }
                else if (window.XMLHttpRequest) {
                    anotherXmlHttp = new XMLHttpRequest();                
                }
            }

            function startRequest(url) {
                createXMLHttpRequest();                                                                            
                xmlHttp.onreadystatechange = handleStateChange;
                var myRandom=parseInt(Math.random()*99999999);
                xmlHttp.open("GET", url+ "?rand=" + myRandom, true);
                xmlHttp.send(null);
            }

            function startAnotherRequest(url) {
                createAnotherXMLHttpRequest();
                anotherXmlHttp.onreadystatechange = handleAnotherStateChange;
                anotherXmlHttp.open("GET", url, true);
                anotherXmlHttp.setRequestHeader("Content-Type", "text/xml");
                anotherXmlHttp.send(null);
            }

            function handleStateChange() {
                if (xmlHttp.readyState == 4) {
                    if (xmlHttp.status == 200) {
                        if (xmldata != 'ok') {
                            xmldata = xmlHttp.responseText;
                            startAnotherRequest("scale.php?reginput="+xmldata);
                            xmldata = "";
                        }
                    }
                }
            }

            function handleAnotherStateChange() {
                if (anotherXmlHttp.readyState == 4) {
                    if (anotherXmlHttp.status == 200) {
                        var anotherXmldata = anotherXmlHttp.responseText;
                        var scaledata = anotherXmldata.split("::");
                        var weight = scaledata[0];
                        var delayed = scaledata[1];
                        document.getElementById("weight").innerHTML = weight;

                        if (delayed == 1) {
                            delayed = 0;
                            window.top.frames[1].document.forms[0].submit();
                        }
                    }
                }
            }

            function listen() {
                startRequest("rs232/scale");
                setTimeout("listen();", 70);
            }
        <?php
            if ($_SESSION['OS'] == 'linux' && !$_SESSION["browserOnly"])
            {
                echo 'listen();';
            }
        ?>
        </script>
        <table border='0' cellspacing='0' cellpadding='0'>
            <tr>
                <td bgcolor='#004080'>
                    <font face='arial' color='white' size='-1' align='center' valign='center' height='12'>
                        <b><center>weight</center></b>
                    </font>
                </td>
            </tr>
            <tr>
                <td bgcolor='#eeeeee' height='44' width='118' align='center' valign='center'>
                    <b><font face='arial' size=+1 color='black' id='weight'></font></b>
                </td>
            </tr>
        </table>
    </body>
</html>

