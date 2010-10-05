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
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!function_exists("getsubtotals")) include($IS4C_PATH."lib/connect.php");
if (!function_exists("printheaderb")) include($IS4C_PATH."lib/drawscreen.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

function listitems($top_item, $highlight) {
	global $IS4C_LOCAL;

	getsubtotals();
	$LastID = $IS4C_LOCAL->get("LastID");

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

	$IS4C_LOCAL->set("currenttopid",$top_item);
	$IS4C_LOCAL->set("currentid",$highlight);

//------------------Boundary Bottom----------------

	return drawitems($top_item, 11, $highlight);
	$IS4C_LOCAL->set("currentid",$highlight);
}



function printReceiptfooter($readOnly=False) {
	global $IS4C_LOCAL;

	if ($IS4C_LOCAL->get("sc") == 1) {
		return lastpage();
	}
	else {

		if (!$readOnly)
			getsubtotals();
		$last_id = $IS4C_LOCAL->get("LastID");

		if (($last_id - 7) < 0) {
			$top_id = 1;
		}
		else {
			$top_id = $last_id - 7;
		}

		$ret = drawitems($top_id, 7, 0);

		$ret .= "<div class=\"farewellMsg\">"
			.$IS4C_LOCAL->get("farewellMsg1")
			."<br />"
			.$IS4C_LOCAL->get("farewellMsg2")
			."<br />"
			.$IS4C_LOCAL->get("farewellMsg3")
			."</div>";
		return $ret;
	}
}



function drawitems($top_item, $rows, $highlight) {
	global $IS4C_LOCAL;

	$ret = printheaderb();

	$query = "select count(*) as count from localtemptrans";
	$db = tDataConnect();
	$result = $db->query($query);
	$row = $db->fetch_array($result);
	$rowCount = $row["count"];

	$db->close();


	if ($rowCount == 0) {
		$ret .= "<div class=\"centerOffset\">";
		if ($IS4C_LOCAL->get("training") != 1) {
			$ret .= plainmsg($IS4C_LOCAL->get("welcomeMsg1")."<BR>".$IS4C_LOCAL->get("welcomeMsg2"));
		}
		else {
			$ret .= plainmsg($IS4C_LOCAL->get("trainingMsg1")."<BR>".$IS4C_LOCAL->get("trainingMsg2"));
		}
		$ret .= "</div>";
	}
	else {

		$query_range = "select * from screendisplay where trans_id >= ".$top_item." and trans_id <= "
				.($top_item + $rows)." order by trans_id";
		$db_range = tDataConnect();
		$result_range = $db_range->query($query_range);
		$num_rows = $db_range->num_rows($result_range);

		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db_range->fetch_array($result_range);

			$trans_id = $row["trans_id"];
			$description = $row["description"];
			$total = $row["total"];
			$comment = $row["comment"];
			$tf = $row["status"];
			$color = $row["lineColor"];

			
			if ($trans_id == $highlight) {
				if ($color == "004080") {
					$ret .= printitemhilite($description, $comment, $total, $tf);
				}
				else {
					$ret .= printitemcolorhilite($color, $description, $comment, $total, $tf);
				}
			}
			else
				{
				if ($color == "004080") {
					$ret .= printitem($description, $comment, $total, $tf,$trans_id);
				}
				else {
					$ret .= printitemcolor($color, $description, $comment, $total, $tf,$trans_id);
				}				
			}

		}
		$db_range->close();
	}
	return $ret;
}



function lastpage($readOnly=False) {
	global $IS4C_LOCAL;

	if (!$readOnly){
		getsubtotals();
	}
	$last_id = $IS4C_LOCAL->get("LastID");

	if (($last_id - 11) < 0) {
		$top_id = 1;
	}
	else {
		$top_id = $last_id - 11;
	}
	
	if (!$readOnly){
		$IS4C_LOCAL->set("currentid",$last_id);
		$IS4C_LOCAL->set("currenttopid",$top_id);
	}
	return drawitems($top_id, 11, $last_id);
}

?>
