<?php
/* configuration for your module - Important */
include("_ini_.php");

if (!function_exists("is4c_op_db")) include(FANNIE_ABS_PATH."/lib/sql/db_connect.php");
$sql = is4c_op_db();

if (isset($_GET['action'])){
	switch($_GET['action']){
	case 'delete':
		$plu = $_GET['plu'];
		$delQ = $sql->prepare_statement("DELETE FROM UnfiToPLU WHERE wfc_plu=?");
		$sql->exec_statement($delQ,array($plu));
		break;
	case 'add':
		$plu = str_pad($_GET['plu'],13,'0',STR_PAD_LEFT);
		$sku = $_GET['sku'];
		$insQ = $sql->prepare_statement("INSERT INTO UnfiToPLU (unfi_sku,wfc_plu)
			VALUES (?,?)");
		$insR = $sql->exec_statement($insQ,array($sku,$plu));
		break;
	}
}

$order = "description";
if (isset($_GET['order'])) $order = $_GET['order'];

$dataQ = $sql->prepare_statement("SELECT unfi_sku,wfc_plu,
	CASE WHEN p.description IS NULL THEN '! None found' ELSE p.description
	END as description FROM UnfiToPLU
	AS u LEFT JOIN products AS p ON u.wfc_plu=p.upc
	ORDER BY description");
$dataR = $sql->exec_statement($dataQ);

if (!isset($_GET['excel'])){
	/* html header, including navbar */
	include(FANNIE_ABS_PATH."/display/html/header.php");

	echo "<form action=plu_mapping.php method=get>
	<b>SKU</b>: <input type=text size=6 name=sku />
	<b>PLU</b>: <input type=text size=6 name=plu />
	<input type=hidden name=action value=add />
	<input type=submit value=Add />
	</form>
	<a href=plu_mapping.php?order=$order&excel=yes>Save to Excel</a>";
}
else {
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="PLU_mapping.xls"');
}

echo "<table cellpadding=4 cellspacing=0 border=1>";
echo "<tr>";
if (!isset($_GET['excel'])){
	echo "<th><a href=plu_mapping.php?order=unfi_sku>SKU</a></th>";
	echo "<th><a href=plu_mapping.php?order=wfc_plu>PLU</a></th>";
	echo "<th><a href=plu_mapping.php?order=description>Description</a></th>";
	echo "<th>&nbsp;</th>";
}
else
	echo "<th>SKU</th><th>PLU</th><th>Description</th>";
echo "</tr>";
while($dataW = $sql->fetch_row($dataR)){
	echo "<tr>";
	echo "<td>".$dataW["unfi_sku"]."</td>";
	echo "<td>".$dataW["wfc_plu"]."</td>";
	echo "<td>".$dataW["description"]."</td>";
	if (!isset($_GET['excel'])){
		echo "<td><a href=\"plu_mapping.php?action=delete&plu=".$dataW['wfc_plu'];
		echo "\" onclick=\"return confirm('Delete mapping ";
		echo $dataW["wfc_plu"]."?');\">X</a></td>";
	}
	echo "</tr>";
}
echo "</table>";

if (!isset($_GET['excel'])){
	/* html footer */
	include(FANNIE_ABS_PATH."/display/html/footer.php");
}

?>
