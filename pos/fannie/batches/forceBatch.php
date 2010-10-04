<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
?>




<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Fannie - Item Maintenance</title>
<link rel="STYLESHEET" href="../src/style.css" type="text/css">
</head>
<body bgcolor="FFFFFF">
<div align="center">
<table width="750" border="0" cellpadding="0" cellspacing="0">
 <tr>
  <td colspan="3" width="750">
   <table width="750" border="0" cellpadding="0" cellspacing="0">
    <tr>
     <td width="750" colspan="3">
     <span style="font-size:28px;color:330066">
     Fannie
     </span>
     </td>
    </tr>
    <tr>
     <td width="330">
     <span style="font-size:20px;color:666666">
     IS4C maintenance & reporting
     </span>
     </td>
     <td width="400" align="right">
      <table width="400" border="0" cellpadding="0" cellspacing="0">
       <tr>
        <td align="right" height="20">
        <span style="color:AAAAAA">
<!--- tak
        search for product&nbsp;
        </span>
        <input type="text" onFocus="this.style.background='FFFFFF';" onBlur="this.style.background='F9F9F9';">&nbsp;
        <input type="submit" style="padding:0;background-color:330066;border:0;color:FFFFFF;width:25;font-weight:bold" value="GO">
tak ---->
        </td>
       </tr>
      </table>
     </td>
     <td width="20"></td>
    </tr>
   </table>
  </td>
 </tr>
 <tr>
  <td colspan="3" height="15" bgcolor="FFFFFF"></td>
 </tr>
 <tr>
  <td colspan="3" height="1" bgcolor="CCCCCC"></td>
 </tr>
 <tr>
  <td colspan="3" height="10" bgcolor="FFFFFF"></td>
 </tr>
 <tr>
  <td width="170" bgcolor="FFFFFF" valign="top">
  <span style="font-size:6px"><br></span>
  <div align="center">
 	<?php
		include("../src/navbar.html");
	?>
  <span style="font-size:6px"><br></span>
  </div>
  </td>
  <td width="1" bgcolor="CCCCCC"></td>
  <td width="579" valign="top">
  <span style="font-size:6px"><br></span>
  <div align="center">
   <table width="549" border="0" cellpadding="0" cellspacing="0">
    <tr>
     <td colspan="4" height="1" bgcolor="AAAAAA"></td>
     <td width="5" height="1" bgcolor="FFFFFF"></td>
    </tr>
    <tr>
     <td width="1" bgcolor="AAAAAA"></td>
     <td rowspan="2" colspan="2" width="542" height="27" bgcolor="F9F9F9" style="color:330066;font-size:15px">&nbsp;&nbsp;Item Maintenance</td>
     <td width="1" bgcolor="AAAAAA"></td>
     <td width="5" height="4" bgcolor="FFFFFF"></td>
    </tr>
    <tr>
     <td width="1" bgcolor="AAAAAA"></td>
     <td width="1" bgcolor="AAAAAA"></td>
     <td width="5" bgcolor="F0F0F0" height="23"></td>
    </tr>
    <tr>
     <td width="1" bgcolor="AAAAAA"></td>
     <td colspan="2" height="1" bgcolor="AAAAAA"></td>
     <td width="1" bgcolor="AAAAAA"></td>
     <td width="5" bgcolor="F0F0F0"></td>
    </tr>
    <tr>
     <td width="1" bgcolor="AAAAAA"></td>
     <td colspan="2" bgcolor="FFFFFF">
      <table width="542" border="0" cellpadding="17" cellspacing="0">
       <tr>
        <td style="color:999999;line-height:1.6em">
        <div align="justify">

<?
// include($_SERVER["DOCUMENT_ROOT"].'/src/funct1Mem.php');

$batchID = $_GET['batchID'];

$forceQ="UPDATE is4c_op.products as p,
	is4c_op.batches as b,
	is4c_op.batchList as l
	SET p.start_date = b.startDate,
	p.end_date = b.endDate,
	p.special_price = l.salePrice,
	p.discounttype = b.discounttype 
	WHERE l.upc = p.upc
	AND b.batchID = l.batchID
	AND b.batchID = $batchID";

$forceR = mysql_query($forceQ);

//$batchUpQ = "EXEC batchUpdate";
//$batchUpR = mysql_query($batchUpQ);

echo "<h2Batch $batchID has been forced</h2></br></br>";
echo "<p>Back to the <a href='index.php'>batch list</a></p>";

?>

        </div>
        </td>
       </tr>
      </table>
     </td>
     <td width="1" bgcolor="AAAAAA"></td>
     <td width="5" bgcolor="F0F0F0"></td>
    </tr>
    <tr>
     <td width="1" bgcolor="AAAAAA"></td>
     <td colspan="2" height="1" bgcolor="AAAAAA"></td>
     <td width="1" bgcolor="AAAAAA"></td>
     <td width="5" bgcolor="F0F0F0"></td>
    </tr>
    <tr>
     <td width="1" height="5" bgcolor="FFFFFF"></td>
     <td width="4" height="5" bgcolor="FFFFFF"></td>
     <td width="538" height="5" bgcolor="F0F0F0"></td>
     <td width="1" height="5" bgcolor="F0F0F0"></td>
     <td width="5" height="5" bgcolor="F0F0F0"></td>
    </tr>
   </table>
  <span style="font-size:6px"><br></span>
  </div>
  </td>
 </tr>
 <tr>
  <td colspan="3" height="10" bgcolor="FFFFFF"></td>
 </tr>
 <tr>
  <td colspan="3" height="1" bgcolor="CCCCCC"></td>
 </tr>
 <tr>
  <td colspan="3" height="5" bgcolor="FFFFFF"></td>
 </tr>
 <tr>
  <td colspan="3" bgcolor="FFFFFF" align="right">
  Copyleft &#0169; 2007, <a href="http://www.designslife.com" style="font-size:12px;color:AAAAAA">designslife</a>. All Rights Reserved.
  </td>
 </tr>
</table>
</div>
</body>
</html>
