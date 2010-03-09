<?php
/*******************************************************************************

   Copyright 2001, 2004 Wedge Community Co-op

   This file is part of IS4C.

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
// session_start();
if (!function_exists("pDataConnect")) include("connect.php");
if (!function_exists("tDataConnect")) include("connect.php");
if (!function_exists("printheaderb")) include("drawscreen.php");
if (!function_exists("setMember")) include("prehkeys.php");
if (!function_exists("gohome")) include("maindisplay.php");

$_SESSION["away"] = 1;
$away = 0;

if ($_SESSION["idSearch"] && strlen($_SESSION["idSearch"]) > 0) {
	
	$entered = $_SESSION["idSearch"];
	$_SESSION["idSearch"] = "";

}
else {

   	$entered = strtoupper(trim($_POST["search"]));
   	$entered = str_replace("'", "''", $entered);

}

if (substr($entered, -2) == "ID") $entered = substr($entered, 0, strlen($entered) - 2);

if (!$entered || strlen($entered) < 1) {
   	$_SESSION["mirequested"] = 0;
   	$away = 1;
   	gohome();
}
else {
   	$memberID = $entered;
   	$db_a = pDataConnect();

	if (!is_numeric($entered)) {
		$query = "select * from custdata where LastName like '".$entered."%' order by LastName, FirstName";
   	} else {
   	 	$query = "select * from custdata where CardNo = '".$entered."' order by personNum";
   	}

   	$result = sql_query($query, $db_a);
   	$num_rows = sql_num_rows($result);

      if ($num_rows < 1) {
           	echo "<BODY onLoad='document.searchform.search.focus();'>";
           	printheaderb();
           	membersearchbox("no match found<BR>next search or member number");

	} elseif ($num_rows > 1 || !is_numeric($entered)) {
	
           	
		echo "<HTMT><HEAD>\n";

		echo ""
			."<SCRIPT type=\"text/javascript\">\n"
			."document.onkeydown = keyDown;\n"
			."function keyDown(e) {\n"
			."if ( !e ) { e = event; };\n"
			."var ieKey=e.keyCode;\n"
			."if (ieKey==13) { document.selectform.submit();};\n"
			."if (ieKey==27) { window.top.location = 'pos.php';};\n"
			."}\n"
			."</SCRIPT>\n";

            echo "</HEAD>\n"
            ."<BODY onLoad='document.selectform.selectlist.focus();'>";
            
           	printheaderb();
          	echo "<TABLE>\n"
               ."<TR><TD height='295' align='center' valign='center'>\n"
               ."<FORM name='selectform' method='post' action='memid.php'>\n"
               ."<SELECT  name='selectlist' size='15' onBlur='document.selectform.selectlist.focus();'>\n";

		if (!is_numeric($entered) && $_SESSION["memlistNonMember"] == 1) {
//             	echo "<OPTION value='3::1' selected> 3 "
//        		."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Customer\n";
			$selectFlag = 1;
		} else {
			$selectFlag = 0;
		}

           	for ($i = 0; $i < $num_rows; $i++) {
               	$row = sql_fetch_array($result);
			if( $i == 0 && $selectFlag == 0) {
				$selected = "selected";
			} else {
				$selected = "";
			}
               	echo "<OPTION value='".$row["CardNo"]."::".$row["personNum"]."::".$row["id"]."' ".$selected.">"
				.$row["CardNo"]." ".$row["LastName"].", ".$row["FirstName"]."\n";
           	}

           	echo "</SELECT>\n</FORM>\n</TD>\n"
               ."<TD height='295' width='40'></TD>\n"
               ."<TD height='295' valign='center'>\n"
               ."<FONT face='arial' size='+1' color='#004080'>use arrow keys to navigate<p>[esc] to cancel</FONT>\n"
               ."</TD></TR></TABLE>\n";

       } else {
			$row = sql_fetch_array($result);
//			setMember($row["CardNo"], $row["personNum"]);
			setMember($row["id"]);
			gohome();
       }
}




if ($away == 1) {
   $away = 0;
   printfooterb();
}
else { printfooter();
}
?>




</BODY>
</HTML>





