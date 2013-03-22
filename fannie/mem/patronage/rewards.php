<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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
include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$page_title = "Fannie :: Patronage Tools";
$header = "Calculate Rewards";

include($FANNIE_ROOT.'src/header.html');

if (isset($_REQUEST['rewardsubmit'])){

	$upcs = "";
	$list = preg_split("/\D+/",$_REQUEST['upcs'],-1,PREG_SPLIT_NO_EMPTY);
	$args = array();
	foreach($list as $l){
		$upcs .= '?,';
		$args[] = str_pad($l,13,'0',STR_PAD_LEFT);
	}
	if ($upcs != ""){
		$upcs = rtrim($upcs,",");
		$upcs = "OR (trans_subtype='IC' AND upc IN ($upcs))";
	}

	$fetchQ = sprintf("SELECT card_no,SUM(total) as total
		FROM %s%sdlog_patronage
		WHERE trans_type='MA' %s 
		GROUP BY card_no",$FANNIE_TRANS_DB,$dbc->sep(),$upcs);
	$prep = $dbc->prepare_statement($fetchQ);
	$fetchR = $dbc->exec_statement($prep,$args);

	$upP = $dbc->prepare_statement("UPDATE patronage_workingcopy
		SET rewards=? WHERE cardno=?");
	while($fetchW = $dbc->fetch_row($fetchR)){
		if ($fetchW['total']==0) continue;
		$dbc->exec_statement($upP,array($fetchW['total'],$fetchW['card_no']));
	}
	
	echo '<i>Rewards loaded</i>';
}
else {
	echo '<blockquote><i>';
	echo 'Step three: calculate additonal member rewards. This currently includes
	the virtual coupon and any custom coupon UPCs specified here';
	echo '</i></blockquote>';
	echo '<form action="rewards.php" method="get">';
	echo '<b>UPC(s)</b>: ';
	echo '<input type="text" name="upcs" />';
	echo '<br /><br />';
	echo '<input type="submit" name="rewardsubmit" value="Calculate Rewards" />';
	echo '</form>';
}

echo '<br /><br />';
echo '<a href="index.php">Patronage Menu</a>';

include($FANNIE_ROOT.'src/footer.html');

?>
