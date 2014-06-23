<?php
include('../../config.php');
include('prodFunction.php');
include($FANNIE_ROOT.'auth/login.php');
refreshSession();
?>
<html>
<head>

<link href="../styles.css" rel="stylesheet" type="text/css">
<SCRIPT LANGUAGE="JavaScript">

<!-- This script and many more are available free online at -->
<!-- The JavaScript Source!! http://javascript.internet.com -->
<!-- John Munn  (jrmunn@home.com) -->

<!-- Begin
 function putFocus(formInst, elementInst) {
  if (document.forms.length > 0) {
   document.forms[formInst].elements[elementInst].focus();
   document.forms[formInst].elements[elementInst].select();
  }
 }
// The second number in the "onLoad" command in the body
// tag determines the form's focus. Counting starts with '0'
//  End -->
</script>
<script type="text/javascript"
	src="/git/fannie/src/javascript/jquery.js">
</script>
<?php
echo "<script language=JavaScript>";
echo "function delete_popup(upc,description){";
echo "testwindow= window.open (\"listDel.php?upc=\"+upc+\"&description=\"+description, upc+description,\"location=0,status=1,scrollbars=1,width=600,height=200\");";
//echo "testwindow.moveTo(50,50);";
echo "if (!testwindow.opener)\n";
echo "testwindow.opener = self;";
echo "}";
echo "</script>";
?>
</head>
<?php

//    $db = mssql_connect("129.103.2.10","sa");
//    mssql_select_db("WedgePOS",$db);
echo "<BODY onLoad='putFocus(0,0);'>";
echo "<div id=logo><img src='../members/images/newLogo_small.gif'></div>";
echo "<div id=products>";

if(isset($_POST['submit'])){
    $upc = $_POST['upc'];
//    if(isset($_GET['upc'])){
//       $upc=$_GET['upc'];
//    }
    if (isset($_POST['prefix'])){
      itemParse($upc,'no','',true);
    }
    else {
      itemParse($upc);
    }
//    echo $num;

}elseif(isset($_GET['upc'])){
    $upc = $_GET['upc'];
    //echo $upc;
    itemParse($upc);

}else{



//echo $upc;
echo "<head><title>Edit Item</title></head>";
echo "<BODY onLoad='putFocus(0,0);'>";
echo "<form action=productTest.php method=post>";
echo "<input name=upc type=text id=upc> Enter 
<select name=\"ntype\">
<option>UPC</option>
<option>SKU</option>
<option>Brand Prefix</option>
</select> or product name here<br>";

echo "<input name=submit type=submit value=submit>";
echo "</form>";
echo "<a href=/git/fannie/item/handheld.php>Small-screen</a>";
}
echo "</div>";
echo "</body>";
echo "</html>";
?>
