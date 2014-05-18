<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/


include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class productlist extends NoInputPage {

	var $temp_result;
	var $temp_num_rows;
	var $boxSize;

	function preprocess(){
		global $CORE_LOCAL;

		$entered = "";
		if (isset($_REQUEST["search"]))
			$entered = strtoupper(trim($_REQUEST["search"]));
		elseif ($CORE_LOCAL->get("pvsearch") != "")
			$entered = strtoupper(trim($CORE_LOCAL->get("pvsearch")));
		else{
			$this->temp_num_rows = 0;
			return True;
		}

		// canceled
		if (empty($entered)){
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}

		// picked an item from the list
		if (is_numeric($entered) && strlen($entered) == 13){
			$CORE_LOCAL->set("msgrepeat",1);
			$CORE_LOCAL->set("strRemembered",$entered);
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}

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

		/* Get all enabled plugins and standard modules of the base. */
		$modules = AutoLoader::ListModules('ProductSearch');
		$results = array();
		$this->boxSize = 1;
		/* Search first with the plugins
         	 *  and then with standard modules.
        	 * Keep only the first instance of each upc.
        	 * Increase the depth of the list from module parameters.
         	*/
		foreach($modules as $mod_name){
			$mod = new $mod_name();
			$mod_results = $mod->search($entered);
			foreach($mod_results as $upc => $record){
				if (!isset($results[$upc]))
					$results[$upc] = $record;
			}
			if ($mod->result_size > $this->boxSize)
				$this->boxSize = $mod->result_size;
		}

		$this->temp_result = $results;
		$this->temp_num_rows = count($results);

		return True;
	} // END preprocess() FUNCTION

	function head_content()
    {
		// Javascript is only really needed if there are results
		if ($this->temp_num_rows != 0) {
            ?>
            <script type="text/javascript" src="../js/selectSubmit.js"></script>
            <?php
		}
	} // END head() FUNCTION

	function body_content(){
		global $CORE_LOCAL;
		$result = $this->temp_result;
		$num_rows = $this->temp_num_rows;

		if ($num_rows == 0) {
			$this->productsearchbox(_("no match found")."<br />"._("next search or enter upc"));
		}
		else {
			$this->add_onload_command("selectSubmit('#search', '#selectform')\n");

			echo "<div class=\"baseHeight\">"
				."<div class=\"listbox\">"
				."<form name=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\""
				." id=\"selectform\">"
				."<select name=\"search\" id=\"search\" "
				."size=".$this->boxSize." onblur=\"\$('#search').focus();\" ondblclick=\"document.forms['selectform'].submit();\">";

			$selected = "selected";
			foreach($result as $row){
				$price = $row["normal_price"];	

				if ($row["scale"] != 0) $Scale = "S";
				else $Scale = " ";

				if (!$price) $price = "unKnown";
				else $price = MiscLib::truncate2($price);

				echo "<option value='".$row["upc"]."' ".$selected.">".$row["upc"]." -- ".$row["description"]
					." ---- [".$price."] ".$Scale."\n";
					
				$selected = "";
			}
			echo "</select>"
				."</form>"
				."</div>"
				."<div class=\"listboxText coloredText centerOffset\">"
				._("clear to cancel")."</div>"
				."<div class=\"clear\"></div>";
			echo "</div>";
		}

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
				onblur="$('#search').focus();" />
			</form>
			press [enter] to cancel
			</div>
		</div>
		<?php
	}

}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new productlist();

?>
