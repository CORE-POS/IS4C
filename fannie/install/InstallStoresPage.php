<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

//ini_set('display_errors','1');
include('../config.php'); 
include('util.php');
include('db.php');
include_once('../classlib2.0/FannieAPI.php');

/**
	@class InstallStoresPage
	Class for the Stores install and config options
*/
class InstallStoresPage extends InstallPage {

	protected $title = 'Fannie: Store Settings';
	protected $header = 'Fannie: Store Settings';

	public $description = "
	Class for the Stores install and config options page.
	";

	// This replaces the __construct() in the parent.
	public function __construct() {

		// To set authentication.
		FanniePage::__construct();

		// Link to a file of CSS by using a function.
		$this->add_css_file("../src/style.css");
		$this->add_css_file("../src/jquery/css/smoothness/jquery-ui-1.8.1.custom.css");
		$this->add_css_file("../src/css/install.css");

		// Link to a file of JS by using a function.
		$this->add_script("../src/jquery/js/jquery.js");
		$this->add_script("../src/jquery/js/jquery-ui-1.8.1.custom.min.js");

	// __construct()
	}

	// If chunks of CSS are going to be added the function has to be
	//  redefined to return them.
	// If this is to override x.css draw_page() needs to load it after the add_css_file
	/**
	  Define any CSS needed
	  @return a CSS string
	function css_content(){
		$css ="";

		return $css;

	//css_content()
	}
	*/

	// If chunks of JS are going to be added the function has to be
	//  redefined to return them.
	/**
	  Define any javascript needed
	  @return a javascript string
	*/
	function javascript_content(){
		$js = "
		function showhide(i,num){
			for (var j=0; j<num; j++){
				if (j == i)
					document.getElementById('storedef'+j).style.display='block';
				else
					document.getElementById('storedef'+j).style.display='none';
			}
		}";

		return $js;
	}

	function body_content(){
		//Should this really be done with global?
		global $FANNIE_NUM_STORES, $FANNIE_STORE_ID, $FANNIE_STORES;
		ob_start();

		echo showInstallTabs('Stores');
		?>

<form action=InstallStoresPage.php method=post>
<h1 class="install"><?php echo $this->header; ?></h1>
<p class="ichunk">As of 11Apr2013 these settings are not widely or well supported.</p>
<?php
if (is_writable('../config.php')){
	echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>
<hr />
<h4 class="install">Stores</h4>
<p class="ichunk" style="margin:0.0em 0em 0.4em 0em;"><b>Store ID</b>: 
<?php
if (!isset($FANNIE_STORE_ID))
	$FANNIE_STORE_ID = 0;
if (isset($_REQUEST['FANNIE_STORE_ID']))
	$FANNIE_STORE_ID = $_REQUEST['FANNIE_STORE_ID'];
confset('FANNIE_STORE_ID',"$FANNIE_STORE_ID");
echo "<input type=text name=FANNIE_STORE_ID value=\"$FANNIE_STORE_ID\" size=3 />";
?>
 &nbsp; By convention store id #0 is HQ.
</p>
<?php
/*
	By convention store id #0 is HQ
	I don't know if the rest of these settings are really
	necessary, but I don't want to remove them yet
	just in case
*/
if($FANNIE_STORE_ID == 0){
?>

<p class="ichunk" style="margin:0.0em 0em 0.4em 0em;"><b>Other Stores</b>: 
<?php
if (!isset($FANNIE_NUM_STORES))
	$FANNIE_NUM_STORES = 0;
if (isset($_REQUEST['FANNIE_NUM_STORES']))
	$FANNIE_NUM_STORES = $_REQUEST['FANNIE_NUM_STORES'];
confset('FANNIE_NUM_STORES',"$FANNIE_NUM_STORES");
echo "<input type=text name=FANNIE_NUM_STORES value=\"$FANNIE_NUM_STORES\" size=3 />";
?>
</p>
<?php
if ($FANNIE_NUM_STORES == 0)
	confset('FANNIE_STORES','array()');
else {
	echo "<select onchange=\"showhide(this.value,$FANNIE_NUM_STORES);\">";
	for($i=0; $i<$FANNIE_NUM_STORES;$i++){
		echo "<option value=$i>Store ".($i+1)."</option>";
	}
	echo "</select><br />";

	$conf = 'array(';
	for($i=0; $i<$FANNIE_NUM_STORES; $i++){
		$style = ($i == 0)?'block':'none';
		echo "<div id=\"storedef{$i}\" style=\"display:$style;\">";
		if (!isset($FANNIE_STORES[$i])) $FANNIE_STORES[$i] = array();
		$conf .= 'array(';

		if (!isset($FANNIE_STORES[$i]['host']))
			$FANNIE_STORES[$i]['host'] = '127.0.0.1';
		if (isset($_REQUEST["STORE_HOST_$i"])){
			$FANNIE_STORES[$i]['host'] = $_REQUEST["STORE_HOST_$i"];
		}
		$conf .= "'host'=>'{$FANNIE_STORES[$i]['host']}',";
		echo "<p class='ichunk2'>";
		echo "Store ".($i+1)." Database Host: <input type=text name=STORE_HOST_$i value=\"{$FANNIE_STORES[$i]['host']}\" /></p>";
		
		if (!isset($FANNIE_STORES[$i]['type']))
			$FANNIE_STORES[$i]['type'] = 'MYSQL';
		if (isset($_REQUEST["STORE_TYPE_$i"]))
			$FANNIE_STORES[$i]['type'] = $_REQUEST["STORE_TYPE_$i"];
		$conf .= "'type'=>'{$FANNIE_STORES[$i]['type']}',";
		echo "<p class='ichunk2'>";
		echo "Store ".($i+1)." Database Type: <select name=STORE_TYPE_$i>";
		if ($FANNIE_STORES[$i]['type'] == 'MYSQL'){
			echo "<option value=MYSQL selected>MySQL</option><option value=MSSQL>SQL Server</option>";
		}
		else {
			echo "<option value=MYSQL>MySQL</option><option selected value=MSSQL>SQL Server</option>";
		}
		echo "</select></p>";

		if (!isset($FANNIE_STORES[$i]['user']))
			$FANNIE_STORES[$i]['user'] = 'root';
		if (isset($_REQUEST["STORE_USER_$i"]))
			$FANNIE_STORES[$i]['user'] = $_REQUEST["STORE_USER_$i"];
		$conf .= "'user'=>'{$FANNIE_STORES[$i]['user']}',";

		echo "<p class='ichunk2'>";
		echo "Store ".($i+1)." Database Username: <input type=text name=STORE_USER_$i value=\"{$FANNIE_STORES[$i]['user']}\" /></p>";

		if (!isset($FANNIE_STORES[$i]['pw']))
			$FANNIE_STORES[$i]['pw'] = '';
		if (isset($_REQUEST["STORE_PW_$i"]))
			$FANNIE_STORES[$i]['pw'] = $_REQUEST["STORE_PW_$i"];
		$conf .= "'pw'=>'{$FANNIE_STORES[$i]['pw']}',";
		echo "<p class='ichunk2'>";
		echo "Store ".($i+1)." Database Password: <input type=password name=STORE_PW_$i value=\"{$FANNIE_STORES[$i]['pw']}\" /></p>";

		if (!isset($FANNIE_STORES[$i]['op']))
			$FANNIE_STORES[$i]['op'] = 'core_op';
		if (isset($_REQUEST["STORE_OP_$i"]))
			$FANNIE_STORES[$i]['op'] = $_REQUEST["STORE_OP_$i"];
		$conf .= "'op'=>'{$FANNIE_STORES[$i]['op']}',";
		echo "<p class='ichunk2'>";
		echo "Store ".($i+1)." Operational DB: <input type=text name=STORE_OP_$i value=\"{$FANNIE_STORES[$i]['op']}\" /></p>";

		if (!isset($FANNIE_STORES[$i]['trans']))
			$FANNIE_STORES[$i]['trans'] = 'core_trans';
		if (isset($_REQUEST["STORE_TRANS_$i"]))
			$FANNIE_STORES[$i]['trans'] = $_REQUEST["STORE_TRANS_$i"];
		$conf .= "'trans'=>'{$FANNIE_STORES[$i]['trans']}'";
		echo "<p class='ichunk2'>";
		echo "Store ".($i+1)." Transaction DB: <input type=text name=STORE_TRANS_$i value=\"{$FANNIE_STORES[$i]['trans']}\" /></p>";

		$conf .= ")";
		echo "</div><!-- /#storedef$i -->";	

		if ($i == $FANNIE_NUM_STORES - 1)
			$conf .= ")";
		else
			$conf .= ",";
	}
	confset('FANNIE_STORES',$conf);
}

?>

<?php
} // endif for HQ only settings
?>

<hr />
<input type=submit value="Re-run" />
</form>

<?php

		return ob_get_clean();

	// body_content
	}

// InstallStoresPage
}

FannieDispatch::conditionalExec(false);

?>
