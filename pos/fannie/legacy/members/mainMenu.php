<?php 
include('../../config.php');

include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
include 'functMem.php';
include 'header.html';

?>
<body bgcolor="#66CC99" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="660" height="111" border="0" cellpadding="0" cellspacing="0" bgcolor="#66cc99">
  <tr>
    <td colspan="2"><h1><img src="../images/newLogo_small1.gif" /></h1></td>
    <td colspan="9" valign="middle"><font size="+3" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">PI Killer</font></td>
  </tr>
  <tr>
    <td colspan="11" bgcolor="#006633">
      <a href="memGen.php"><img src="../images/general.gif" width="72" height="16" border="0" /></a>
      <a href="memEquit.php"><img src="../images/equity.gif" width="72" height="16" border="0" /></a>
      <a href="memAR.php"><img src="../images/AR.gif" width="72" height="16" border="0" /></a>
      <a href="memControl.php"><img src="../images/control.gif" width="72" height="16" border="0" /></a>
      <a href="memDetail.php"><img src="../images/detail.gif" width="72" height="16" border="0" /></a></td>
  </tr>
  <tr>
    <td colspan="9">
<img src="../images/memDown.gif" alt="" name="Members" border="0" id="Members" onload="MM_nbGroup('init','group1','Members','images/memUp.gif',1)" />
<a href="">
<img src="../images/repUp.gif" alt="" name="Reports" width="81" height="62" border="0" id="Reports" /></a>
<a href="" target="_top">
<img name="Items" src="../images/itemsUp.gif" border="0" alt="Items" onLoad="" /></a>
<a href=""> 
<img name="Reference" src="../images/refUp.gif" border="0" alt="Reference" onLoad="" /></a></td>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2" align="center" valign="top">&nbsp;</td>
    <td width="60" align="center" valign="top">&nbsp;</td>
    <td colspan="6" align="center" valign="top">&nbsp;</td>
    <td colspan="2" align="center" valign="top" bgcolor="#66CC99">&nbsp;</td>
  </tr>
  <tr>
<form name="memNum" id="memNum" method="post" action="memGen.php">
    <td width="1" align="right">&nbsp;</td>
    <td width="47" align="right" valign="middle"><font size="2" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">Owner
    #:</font></td>
    <td>
      		<font size="2"> <font face="Papyrus, Verdana, Arial, Helvetica, sans-serif">
      		<input name="memNum" type="text" id="memNum" size="5" maxlength="8" />
      		</font></font>
    </td>
    <td width="82" valign="middle"><font size="2" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">Last Name</font></td>
    <td colspan="5">
	
      <? //  <div align="left"><font size="2"><font size="2"><font face="Papyrus, Verdana, Arial, Helvetica, sans-serif"> ?>
        </font></font><font size="2"><font size="2"><font face="Papyrus, Verdana, Arial, Helvetica, sans-serif">
        <input name="lastName" type="text" id="lastName3" size="25" maxlength="50" />
      </font></font></font><font face="Papyrus, Verdana, Arial, Helvetica, sans-serif">      </font></font> </div>
    </td>
    
	
	<td width="75" valign="middle"><font size="2" face="Papyrus, Verdana, Arial, Helvetica, sans-serif">First
    Name:</font></td><td>
      <font size="2"> <font face="Papyrus, Verdana, Arial, Helvetica, sans-serif">
      <input name="firstName" type="text" id="firstName" size="20" maxlength="50" /></td>
      <td><input type="submit" name="submit" value="submit">
	</font></font>
</form></td>
  </tr>
</table>

<p align="left">&nbsp;</p>
</body>
</html>
