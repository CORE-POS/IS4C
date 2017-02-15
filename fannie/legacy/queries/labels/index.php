<?php 
include('../../../config.php');
require($FANNIE_ROOT.'auth/login.php');

if (!validateUserQuiet('barcodes')){
?>
   <html>
   <head><title>Please log in</title>
   <link href="../../styles.css" rel="stylesheet" type="text/css">
   </head>
   <body>
   <div id=logo><img src='../../members/images/newLogo_small.gif'></div>
   <div id=main> <?php
   echo "Must be logged in to view barcode page...";
   echo "<a href={$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/queries/labels/index.php>Click here</a> to login<p />";
}else{
?>
<html>
<head>
<title>Barcode Label Page</title>
<link href="../../styles.css" rel="stylesheet" type="text/css">
<script type="text/javascript">
function goToPage(the_url){
    var offset = document.getElementById('offset').value;
    var str = "0";
    if (!isNaN(parseInt(offset)))
        str = parseInt(offset);

    var final_url = the_url+"&offset="+str;
    window.top.location = final_url;
}
</script>
</head>
<body>
<div id=logo><img src='../../members/images/newLogo_small.gif'></div>
<div id=main>
<div style="font-size:80%;margin-bottom:3px;">
Offset: <input type=text id=offset value=0 size=2 />
</div>
<table>
<tr class=big bgcolor=brown><td colspan=5 align=center><font color=ffffcc>Barcode page</font></td></tr>
<tr class=medium><td><a href="javascript:goToPage('barcodenew.php?id=4')">Grocery Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=edit.php?id=4>Edit Grocery Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=dumpBarcodes.php?id=4>Clear grocery barcodes</a></td></tr>
<tr class=medium><td><a href="javascript:goToPage('barcodenew.php?id=2')">Cool Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=edit.php?id=2>Edit Cool Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=dumpBarcodes.php?id=2>Clear Cool barcodes</a></td></tr>
<tr class=medium><td><a href="javascript:goToPage('barcodenew.php?id=5')">HBC Barcodes</a>
(<a href="barcodenarrow.php?id=5">Narrow</a>)
</td><td>&nbsp;</td>
<td><a class=small href=edit.php?id=5>Edit HBC Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=dumpBarcodes.php?id=5>Clear HBC barcodes</a></td></tr>
<tr class=medium><td><a href="javascript:goToPage('barcodenew.php?id=9')">Gen Merch Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=edit.php?id=9>Edit Gen Merch Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=dumpBarcodes.php?id=9>Clear Gen Merch barcodes</a></td></tr>
<tr class=medium><td><a href="javascript:goToPage('barcodenew.php?id=8')">Meat Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=edit.php?id=8>Edit Meat Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=dumpBarcodes.php?id=8>Clear Meat barcodes</a></td></tr>
<tr class=medium><td><a href="javascript:goToPage('barcodenew.php?id=3')">Deli Barcodes</a>
 (<a href="javascript:goToPage('barcodenew.php?id=3&windows=yes')">Windows</a>) 
</td><td>&nbsp;</td>
<td><a class=small href=edit.php?id=3>Edit Deli Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=dumpBarcodes.php?id=3>Clear Deli barcodes</a></td></tr>
<tr class=medium><td><a href="javascript:goToPage('barcodenew.php?id=1')">Bulk Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=edit.php?id=1>Edit Bulk Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=dumpBarcodes.php?id=1>Clear Bulk barcodes</a></td></tr>
<tr class=medium><td><a href="javascript:goToPage('barcodenew.php?id=6')">Produce Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=edit.php?id=6>Edit Produce Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=dumpBarcodes.php?id=6>Clear Produce barcodes</a></td></tr>
<tr><td colspan=3>&nbsp;</td></tr>
<tr class=medium><td><a href="javascript:goToPage('barcodenew.php?id=0')">'New' Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=edit.php?id=0>Edit 'New' Barcodes</a></td><td>&nbsp;</td>
<td><a class=small href=dumpBarcodes.php?id=0>Clear 'New' barcodes</a></td></tr>
<tr><td colspan=3>&nbsp;</td></tr>
<tr class=medium><td><a href="sheetoftags.php">Sheet of Barcodes</a></td><td>&nbsp;</td>
<td colspan="2">&nbsp;</td></tr>
</table>
</div>
</body>
</body>
<?php
}

