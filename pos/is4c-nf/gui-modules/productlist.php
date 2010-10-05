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

if (!class_exists("NoInputPage")) include_once($IS4C_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("pDataConnect")) include($IS4C_PATH."lib/connect.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class productlist extends NoInputPage {

	var $temp_result;
	var $temp_num_rows;
	var $temp_db;
	var $boxSize;

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;

		$entered = "";
		if (isset($_REQUEST["search"]))
			$entered = strtoupper(trim($_REQUEST["search"]));
		else{
			$this->temp_num_rows = 0;
			return True;
		}

		// canceled
		if (empty($entered)){
			header("Location: {$IS4C_PATH}gui-modules/pos2.php");
			return False;
		}

		// picked an item from the list
		if (is_numeric($entered) && strlen($entered) == 13){
			$IS4C_LOCAL->set("msgrepeat",1);
			$IS4C_LOCAL->set("strRemembered",$entered);
			header("Location: {$IS4C_PATH}gui-modules/pos2.php");
			return False;
		}

		$IS4C_LOCAL->get("away",1);

		if (is_numeric($entered)) {
			// expand UPC-E to UPC-A
			if (substr($entered, 0, 1) == 0 && strlen($entered) == 7) {
				$p6 = substr($entered, -1);

				if ($p6 == 0) 
					$entered = substr($entered, 0, 3)."00000".substr($entered, 3, 3);
				elseif ($p6 == 1) 
					$entered = substr($entered, 0, 3)."10000".substr($entered, 4, 3);
				elseif ($p6 == 2) 
					$entered = substr($entered, 0, 3)."20000".substr($entered, 4, 3);
				elseif ($p6 == 3) 
					$entered = substr($entered, 0, 4)."00000".substr($entered, 4, 2);
				elseif ($p6 == 4) 
					$entered = substr($entered, 0, 5)."00000".substr($entered, 6, 1);
				else 
					$entered = substr($entered, 0, 6)."0000".$p6;

			}

			// UPCs should be length 13 w/ at least one leading zero
			if (strlen($entered) == 13 && substr($entered, 0, 1) != 0) 
				$entered = "0".substr($entered, 0, 12);
			else 
				$entered = substr("0000000000000".$entered, -13);

			// zero out the price field of scale UPCs
			if (substr($entered, 0, 3) == "002")
				$entered = substr($entered, 0, 8)."00000";
		}

		$query = "select upc, description, normal_price, special_price, advertised, scale from products where "
			."upc = '".$entered."' AND inUse  = '1'";
		$this->boxSize = 3;
		if (!is_numeric($entered)) {
			$query = "select upc, description, normal_price, special_price, "
				."advertised, scale from products where "
				."description like '".$entered."%' "
				."and inUse='1' "
				."order by description";
			$this->boxSize = 15;
		}

		$sql = pDataConnect();

		$this->temp_result = $sql->query($query);
		$this->temp_num_rows = $sql->num_rows($this->temp_result);
		$this->temp_db = $sql;

		return True;
	} // END preprocess() FUNCTION

	function head_content(){
		global $IS4C_LOCAL;
		// Javascript is only really needed if there are results
		if ($this->temp_num_rows != 0){
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#search option:selected').val('');
				}
				$('#selectform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
		}
	} // END head() FUNCTION

	function body_content(){
		global $IS4C_LOCAL;
		$result = $this->temp_result;
		$num_rows = $this->temp_num_rows;
		$db = $this->temp_db;

		if ($num_rows == 0) {
			$this->productsearchbox("no match found<br />next search or enter upc");
		}
		else {
			$this->add_onload_command("\$('#search').keypress(processkeypress);\n");

			echo "<div class=\"baseHeight\">"
				."<div class=\"listbox\">"
				."<form name=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\""
				." id=\"selectform\">"
				."<select name=\"search\" id=\"search\" "
				."size=".$this->boxSize." onblur=\"\$('#search').focus();\">";

			$selected = "selected";
			for ($i = 0; $i < $num_rows; $i++) {
				$row = $db->fetch_array($result);
				$price = $row["normal_price"];	

				if ($row["scale"] != 0) $Scale = "S";
				else $Scale = " ";

				if (!$price) $price = "unKnown";
				else $price = truncate2($price);

				echo "<option value='".$row["upc"]."' ".$selected.">".$row["upc"]." -- ".$row["description"]
					." ---- [".$price."] ".$Scale."\n";
					
				$selected = "";
			}
			echo "</select>"
				."</form>"
				."</div>"
				."<div class=\"listboxText centerOffset\">"
				."[Clear] to Cancel</div>"
				."<div class=\"clear\"></div>";
			echo "</div>";
		}

		if (is_object($db))
			$db->close();
		$IS4C_LOCAL->set("scan","noScan");
		$IS4C_LOCAL->set("beep","noBeep");
		$this->add_onload_command("\$('#search').focus();\n");
	} // END body_content() FUNCTION

	function productsearchbox($strmsg) {
		?>
		<div class="baseHeight">
			<div class="colored centeredDisplay">
			<span class="larger">
			<?php echo $strmsg;?>
			</span>
			<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" autocomplete="off">
			<input type="text" name="search" size="15" id="search"
				onblur="$('#search).focus();" />
			</form>
			press [enter] to cancel
			</div>
		</div>
		<?php
	}

}

new productlist();

?>
