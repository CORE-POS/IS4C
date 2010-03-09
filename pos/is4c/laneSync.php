<%

function memChargeAdj() {

	// $conn = pDataConnect();
	
	$pInsert = "insert memChargeAdj select ".$_SESSION["memberID"]." as CardNo, ".$_SESSION["memChargeTotal"]." as Balance";
	$pUpdate = "update custdata set Balance = Balance + ".$_SESSION["memChargeTotal"]." where CardNo = '".$_SESSION["memberID"]."'";

	mssql_query($pUpdate, $conn);
	mssql_query($pInsert, $conn);

}

%>