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
if (!class_exists("MainFramePage")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/MainFramePage.php");
if (!function_exists("pDataConnect")) include($_SESSION["INCLUDE_PATH"]."/lib/connect.php");
if (!function_exists("setMember")) include($_SESSION["INCLUDE_PATH"]."/lib/prehkeys.php");
if (!function_exists("changeBothPages")) include($_SESSION["INCLUDE_PATH"]."/gui-base.php");
if (!isset($IS4C_LOCAL)) include($_SESSION["INCLUDE_PATH"]."/lib/LocalStorage/conf.php");

class batchList extends MainFramePage {

	var $temp_result;
	var $db;

	function preprocess(){
		global $IS4C_LOCAL;
		$IS4C_LOCAL->set("away",1);
		$db_a = mDataConnect();

		$query = "select batchID,batchName from batches where datediff(dd,getdate(),startdate) <= 0
			and datediff(dd,getdate(),enddate) >= 0 order by batchName";

		$result = $db_a->query($query);
		$num_rows = $db_a->num_rows($result);

		if ($num_rows <= 0){
			$IS4C_LOCAL->set("boxMsg","no batches found");
			changeBothPages("/gui-modules/input.php","/gui-modules/boxMsg2.php");
			return False;
		}

		$this->temp_result = $result;
		$this->db = $db_a;
		return True;
	} // END preprocess() FUNCTION

	function body_tag() {
		echo "<body onLoad='document.selectform.selectlist.focus()'>";
	}

	function head(){
		global $IS4C_LOCAL;
		if ($IS4C_LOCAL->get("OS") == "win32") {
			?>
			<script type="text/javascript">
			var prevKey = 0;
			var prevPrevKey = 0;
			function processkeypress(e){
				if ( !e ) e = event; 
				var ieKey=e.keyCode;
				if (ieKey==13){
					if ((prevPrevKey == 67 || prevPrevKey == 99) &&
					    (prevKey == 76 || prevKey == 108))
						document.selectform.selectlist.value = '';
					document.selectform.submit(); 
				}
				if (ieKey==27) { 
					document.selectform.selectlist.value = '';
					document.selectform.submit();
				}
				prevPrevKey = prevKey;
				prevKey = ieKey;
			}
			</script>
			<?php
		}
		else {
		?>
		<script type="text/javascript">
		document.onkeydown = keyDown;
		function keyDown() {
			var ieKey=window.event.keyCode;
			if (ieKey==13) document.selectform.submit();
			if (ieKey==27) { 
				top.input.location = '/gui-modules/input.php'; 
				top.main_frame.location = '/gui-modules/pos2.php';
			}
		}
		</script>
		<?php
		}
	} // END head() FUNCTION

	function body_content(){
		$result = $this->temp_result;
		$db = $this->db;

			echo "<div class=\"baseHeight\">"
				."<div class=\"listbox\">"
				."<form name='selectform' method='post' action='/runBatch.php'>"
				."<select onkeypress='processkeypress(event);' name='selectlist' size='15' "
				."onBlur='document.selectform.selectlist.focus()'>";

			while ($row = $db->fetch_array($result)){
				echo "<option value='".$row["batchID"]."'>"
					.$row["batchName"]."\n";
			}
			echo "</select></form></div>"
				."<div class=\"listboxText centerOffset\">"
				."use arrow keys to navigate<p>[esc] to cancel</div>"
				."<div class=\"clear\"></div>";
			echo "</div>";
	} // END body_content() FUNCTION
}

new batchList();

?>
