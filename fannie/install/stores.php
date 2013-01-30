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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	*  6Dec2012 Eric Lee Add FANNIE_COOP_ID

*/
ini_set('display_errors','1');
?>
<?php 
include('../config.php'); 
include('util.php');
?>
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="auth.php">Authentication</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="mem.php">Members</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Stores
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="update.php">Updates</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="plugins.php">Plugins</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="sample_data/extra_data.php">Sample Data</a>
<form action=stores.php method=post>
<h1>Fannie install checks</h1>
<?php
// path detection
$FILEPATH = rtrim($_SERVER['SCRIPT_FILENAME'],'stores.php');
$FILEPATH = rtrim($FILEPATH,'/');
$FILEPATH = rtrim($FILEPATH,'install');
$FANNIE_ROOT = $FILEPATH;

if (is_writable($FILEPATH.'config.php')){
	echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>

<hr />
<b>Co-op</b>
<br />Use this to identify code that is specific to your co-op.
<br />Particularly important if you plan to contribute to the CORE IT code base.
<br />Try to use a code that will not be confused with any other, e.g. "WEFC_Toronto" instead of "WEFC".
<br />Co-op ID: 
<?php
if (!isset($FANNIE_COOP_ID)) $FANNIE_COOP_ID = '';
if (isset($_REQUEST['FANNIE_COOP_ID'])) $FANNIE_COOP_ID=$_REQUEST['FANNIE_COOP_ID'];
confset('FANNIE_COOP_ID',"'$FANNIE_COOP_ID'");
printf("<input type=\"text\" name=\"FANNIE_COOP_ID\" value=\"%s\" />",$FANNIE_COOP_ID);
?>

<hr />
<b>Stores</b>
<p style="margin:0.0em 0em 0.4em 0em;"><b>Store ID</b>: 
<?php
if (!isset($FANNIE_STORE_ID)) $FANNIE_STORE_ID = 0;
if (isset($_REQUEST['FANNIE_STORE_ID'])) $FANNIE_STORE_ID = $_REQUEST['FANNIE_STORE_ID'];
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

<b>Other Stores</b>: 
<?php
if (!isset($FANNIE_NUM_STORES)) $FANNIE_NUM_STORES = 0;
if (isset($_REQUEST['FANNIE_NUM_STORES'])) $FANNIE_NUM_STORES = $_REQUEST['FANNIE_NUM_STORES'];
confset('FANNIE_NUM_STORES',"$FANNIE_NUM_STORES");
echo "<input type=text name=FANNIE_NUM_STORES value=\"$FANNIE_NUM_STORES\" size=3 />";
?>
<br />
<?php
if ($FANNIE_NUM_STORES == 0) confset('FANNIE_STORES','array()');
else {
?>
<script type=text/javascript>
function showhide(i,num){
	for (var j=0; j<num; j++){
		if (j == i)
			document.getElementById('storedef'+j).style.display='block';
		else
			document.getElementById('storedef'+j).style.display='none';
	}
}
</script>
<?php
echo "<select onchange=\"showhide(this.value,$FANNIE_NUM_STORES);\">";
for($i=0; $i<$FANNIE_NUM_STORES;$i++){
	echo "<option value=$i>Store ".($i+1)."</option>";
}
echo "</select><br />";

$conf = 'array(';
for($i=0; $i<$FANNIE_NUM_STORES; $i++){
	$style = ($i == 0)?'block':'none';
	echo "<div id=\"store\" style=\"display:$style;\">";
	if (!isset($FANNIE_STORES[$i])) $FANNIE_STORES[$i] = array();
	$conf .= 'array(';

	if (!isset($FANNIE_STORES[$i]['host'])) $FANNIE_STORES[$i]['host'] = '127.0.0.1';
	if (isset($_REQUEST["STORE_HOST_$i"])){ $FANNIE_STORES[$i]['host'] = $_REQUEST["STORE_HOST_$i"]; }
	$conf .= "'host'=>'{$FANNIE_STORES[$i]['host']}',";
	echo "Store ".($i+1)." Database Host: <input type=text name=STORE_HOST_$i value=\"{$FANNIE_STORES[$i]['host']}\" /><br />";
	
	if (!isset($FANNIE_STORES[$i]['type'])) $FANNIE_STORES[$i]['type'] = 'MYSQL';
	if (isset($_REQUEST["STORE_TYPE_$i"])) $FANNIE_STORES[$i]['type'] = $_REQUEST["STORE_TYPE_$i"];
	$conf .= "'type'=>'{$FANNIE_STORES[$i]['type']}',";
	echo "Store ".($i+1)." Database Type: <select name=STORE_TYPE_$i>";
	if ($FANNIE_STORES[$i]['type'] == 'MYSQL'){
		echo "<option value=MYSQL selected>MySQL</option><option value=MSSQL>SQL Server</option>";
	}
	else {
		echo "<option value=MYSQL>MySQL</option><option selected value=MSSQL>SQL Server</option>";
	}
	echo "</select><br />";

	if (!isset($FANNIE_STORES[$i]['user'])) $FANNIE_STORES[$i]['user'] = 'root';
	if (isset($_REQUEST["STORE_USER_$i"])) $FANNIE_STORES[$i]['user'] = $_REQUEST["STORE_USER_$i"];
	$conf .= "'user'=>'{$FANNIE_STORES[$i]['user']}',";
	echo "Store ".($i+1)." Database Username: <input type=text name=STORE_USER_$i value=\"{$FANNIE_STORES[$i]['user']}\" /><br />";

	if (!isset($FANNIE_STORES[$i]['pw'])) $FANNIE_STORES[$i]['pw'] = '';
	if (isset($_REQUEST["STORE_PW_$i"])) $FANNIE_STORES[$i]['pw'] = $_REQUEST["STORE_PW_$i"];
	$conf .= "'pw'=>'{$FANNIE_STORES[$i]['pw']}',";
	echo "Store ".($i+1)." Database Password: <input type=password name=STORE_PW_$i value=\"{$FANNIE_STORES[$i]['pw']}\" /><br />";

	if (!isset($FANNIE_STORES[$i]['op'])) $FANNIE_STORES[$i]['op'] = 'core_op';
	if (isset($_REQUEST["STORE_OP_$i"])) $FANNIE_STORES[$i]['op'] = $_REQUEST["STORE_OP_$i"];
	$conf .= "'op'=>'{$FANNIE_STORES[$i]['op']}',";
	echo "Store ".($i+1)." Operational DB: <input type=text name=STORE_OP_$i value=\"{$FANNIE_STORES[$i]['op']}\" /><br />";

	if (!isset($FANNIE_STORES[$i]['trans'])) $FANNIE_STORES[$i]['trans'] = 'core_trans';
	if (isset($_REQUEST["STORE_TRANS_$i"])) $FANNIE_STORES[$i]['trans'] = $_REQUEST["STORE_TRANS_$i"];
	$conf .= "'trans'=>'{$FANNIE_STORES[$i]['trans']}'";
	echo "Store ".($i+1)." Transaction DB: <input type=text name=STORE_TRANS_$i value=\"{$FANNIE_STORES[$i]['trans']}\" /><br />";

	$conf .= ")";
	echo "</div>";	

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
