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

if (!function_exists("getsubtotals")) include("connect.php");
if (!function_exists("printheaderb")) include("drawscreen.php");



function listitems($top_item, $highlight) {
	getsubtotals();
	$LastID = $_SESSION["LastID"];

//----------------Boundary Top ------------------

	if ($highlight < 1) {
		$highlight = 1;
		$top_item = 1;
	}
	
	if ($highlight > $LastID) {
		$highlight = $LastID;
	}

	if ($highlight < $top_item) {
		$top_item = $highlight;
	}

	if ($highlight > ($top_item + 11)) {
		$top_item = ($highlight - 11);
	}

	$_SESSION["currenttopid"] = $top_item;
	$_SESSION["currentid"] = $highlight;

//------------------Boundary Bottom----------------

	drawitems($top_item, 11, $highlight);
	$_SESSION["currentid"] = $highlight;
}



function printReceiptfooter() {


	if ($_SESSION["sc"] == 1) {
		lastpage();
	}
	else {

		getsubtotals();
		$last_id = $_SESSION["LastID"];

		if (($last_id - 7) < 0) {
			$top_id = 1;
		}
		else {
			$top_id = $last_id - 7;
		}

		drawitems($top_id, 7, 0);

		echo "<TR><TD colspan='4' height='20'></TD></TR>"
	    	."<TR><TD colspan='4'><FONT size='+1' face='arial' color='#004080'><CENTER>"
	    	.$_SESSION["farewellMsg1"]."</CENTER></FONT></TD></TR>"
	    	."<TR><TD colspan='4'><FONT size='+1' face='arial' color='#004080'><CENTER>"
	    	.$_SESSION["farewellMsg2"]."</CENTER></FONT></TD></TR>"
	    	."<TR><TD colspan='4'><FONT size='+1' face='arial' color='#004080'><CENTER>"
	    	.$_SESSION["farewellMsg3"]."</CENTER></FONT></TD></TR>";
	}
}



function drawitems($top_item, $rows, $highlight) {

	printheaderb();

	$query = "select count(*) as count from localtemptrans";
	$db = tDataConnect();
	$result = sql_query($query, $db);
	$row = sql_fetch_array($result);
	$rowCount = $row["count"];

	sql_close($db);



	if ($rowCount == 0) {
		if ($_SESSION["training"] != 1) {
			plainmsg($_SESSION["welcomeMsg1"]."<BR>".$_SESSION["welcomeMsg2"]."<BR>".$_SESSION["welcomeMsg3"]);
		}
		else {
			plainmsg($_SESSION["trainingMsg1"]."<BR>".$_SESSION["trainingMsg2"]);
		}
	}
	else {

		$query_range = "select * from screendisplay where trans_id >= ".$top_item." and trans_id <= "
				.($top_item + $rows)." order by trans_id";
		$db_range = tDataConnect();
		$result_range = sql_query($query_range, $db_range);
		$num_rows = sql_num_rows($result_range);

		echo "<TR><TD width='600' colspan='3'>&nbsp;</TD></TR>\n";

		for ($i = 0; $i < $num_rows; $i++) {
			$row = sql_fetch_array($result_range);

			$trans_id = $row["trans_id"];
			$description = $row["description"];
			$total = $row["total"];
			$comment = $row["comment"];
			$tf = $row["status"];
			$color = $row["lineColor"];

			
			if ($trans_id == $highlight) {
				if ($color == "") {
					printitemhilite($description, $comment, $total, $tf);
				}
				else {
					printitemcolorhilite($color, $description, $comment, $total, $tf);
				}
			}
			else
				{
				if ($color == "") {
					printitem($description, $comment, $total, $tf);
				}
				else {
					printitemcolor($color, $description, $comment, $total, $tf);
				}				
			}

		}
		sql_close($db_range);
	}

}



function lastpage() {

	getsubtotals();
	$last_id = $_SESSION["LastID"];



	if (($last_id - 11) < 0) {
		$top_id = 1;
	}
	else {
		$top_id = $last_id - 11;
	}
	
	drawitems($top_id, 11, $last_id);
	
	$_SESSION["currentid"] = $last_id;
	$_SESSION["currenttopid"] = $top_id;
}

?>