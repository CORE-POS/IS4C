<?php
include('../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

if (isset($_REQUEST['action'])){
	switch($_REQUEST['action']){
	case 'margin':
		MarginFS($_REQUEST['upc'],$_REQUEST['cost'],$_REQUEST['dept']);
		break;
	case 'likecode':
		GetLikeCodeItems($_REQUEST['lc']);
		break;
	}
}

function GetLikecodeItems($lc){
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	$ret = "<table border=0 bgcolor=\"#FFFFCC\">";
	if (is_numeric($lc)){
		$prep = $dbc->prepare_statement("SELECT p.upc,p.description FROM
			products AS p INNER JOIN upcLike AS u ON
			p.upc=u.upc WHERE u.likeCode=?
			ORDER BY p.upc");
		$res = $dbc->exec_statement($prep, array($lc));
		while($row = $dbc->fetch_row($res)){
			$ret .= sprintf("<tr><td><a href=itemMaint.php?upc=%s>%s</a></td>
					<td>%s</td></tr>",$row[0],$row[0],$row[1]);
		}
	}
	$ret .= "</table>";
	
	echo $ret;
}

function MarginFS($upc,$cost,$dept){
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	$prep = $dbc->prepare_statement("SELECT normal_price FROM products WHERE upc=?");
	$price = $dbc->exec_statement($prep,array($upc));
	if ($dbc->num_rows($price) > 0)
		$price = array_pop($dbc->fetch_row($price));
	else
		$price = "None";

	$prep = $dbc->prepare_statement("SELECT margin FROM deptMargin WHERE dept_ID=?");
	$dm = $dbc->exec_statement($prep,array($dept));
	if ($dbc->num_rows($dm) > 0){
		$dm = array_pop($dbc->fetch_row($dm));
	}
	else {
		$dm = "Unknown";
	}

	$ret = "Desired margin on this department is ";
	if ($dm == "Unknown") $ret .= $dm;
	else $ret .= sprintf("%.2f%%",$dm*100);
	$ret .= "<br />";
	
	$actual = 0;
	if ($price != 0)
		$actual = ($price-$cost)/$price;
	if (($actual > $dm && is_numeric($dm)) || !is_numeric($dm) ){
		$ret .= sprintf("<span style=\"color:green;\">Current margin on this item is %.2f%%<br />",
			$actual*100);
	}
	elseif (!is_numeric($price)){
		$ret .= "<span style=\"color:green;\">No price has been saved for this item<br />";
	}
	else {
		$ret .= sprintf("<span style=\"color:red;\">Current margin on this item is %.2f%%</span><br />",
			$actual*100);
		$srp = getSRP($cost,$dm);
		$ret .= sprintf("Suggested price: \$%.2f ",$srp);
		$ret .= sprintf("(<a href=\"\" onclick=\"setPrice(%.2f); return false;\">Use this price</a>)",$srp);
	}

	echo $ret;
}

function getSRP($cost,$margin){
	$srp = sprintf("%.2f",$cost/(1-$margin));
	while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" &&
	       substr($srp,strlen($srp)-1,strlen($srp)) != "9")
		$srp += 0.01;
	return $srp;
}


?>
