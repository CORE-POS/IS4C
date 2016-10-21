<?php

include('../db.php');

if (isset($_GET['action'])){
    $out = $_GET['action']."`";
    switch($_GET['action']){
    case 'getitems':
        $lc = $_GET['lc'];
        $out .= "<table cellspacing=2 cellpadding=2>";
        $out .= "<tr><th>UPC</th><th>description</th>";
        $q = $sql->prepare("select p.upc,p.description from products as p, upcLike as u where p.upc = u.upc and u.likeCode = ? order by p.description");
        $r = $sql->execute($q, array($lc));
        while ($w = $sql->fetchRow($r)){
            $out .= "<tr>";
            $out .=  "<td><a href=productTest.php?upc=$w[0]>$w[0]</td>";
            $out .=  "<td>$w[1]</td>";
            $out .=  "</tr>";
        }
        $out .= "</table>";
        break;

    }
    
    echo $out;
    return;
}

?>

<html>
<head>
<style type=text/css>
#items {
    float: left;
    padding-left: 10px;
}
#codetable {
    float: left;
}
a {
    color: #0000ff;
}
</style>
<script type=text/javascript>
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
    http.open('get', 'likeCodes.php?action='+action);
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
        var array = response.split('`');
        switch(array[0]){
    case 'getitems':
        document.getElementById('items').innerHTML = array[1];
        scroll(0,0);
        break;
    default:
        alert(response);
    }
    }
}

</script>
</head>
<body>

<?php

$q = "select * from likeCodes order by likeCode";
$r = $sql->query($q);

echo "<div id=codetable>";
echo "<table cellspacing=2 cellpadding=2 border=1><tr><th>Like code</th><th>description</th></tr>";
while ($row = $sql->fetchRow($r)){
  echo "<tr><td>$row[0]</td><td><a href=\"\" onclick=\"phpSend('getitems&lc=$row[0]'); return false;\">$row[1]</a></td></tr>";
}
echo "</table>";
echo "</div>";
echo "<div id=items>";
echo "</div>";

?>

</body>
</html>
