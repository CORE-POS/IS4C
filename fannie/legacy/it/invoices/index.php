<?php
include('../../../config.php');

require($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT."src/Credentials/invoicing.db.php");

if (isset($_GET["action"])){
	$out = $_GET['action']."`";
	switch($_GET['action']){
	case 'pickDate':
		$date = $_GET['date'];
		$out .= "<b>Invoice Number</b>: ";
		$out .= "<select id=select_num onchange=\"pickNum();\">";
		$out .= "<option value=\"\">Pick an invoice...</option>";
		$invsQ = "select invnum from headers where invoicedate='$date' order by invnum";
		$invsR = $sql->query($invsQ);
		while($invsW = $sql->fetch_row($invsR))
			$out .= "<option>".$invsW[0]."</option>";
		$out .= "</select> ";
		$out .= "<input type=submit value=Go onclick=\"pickNum();\">";
		$out .= "<input type=hidden id=current_date value=\"$date\" />";
		break;
	case 'pickNum':
		$date = $_GET['date'];
		$num = $_GET['num'];
		$csv = False;
		if (isset($_GET['csv'])){
			$csv = True;
			$out = "";
		}
		$out .= getInvoice($num,$date,$csv);
		break;
	case 'upcSearch':
		$upc = $_GET['upc'];
		if (!strstr($upc,"-")){
			$upc = str_pad($upc,13,'0',STR_PAD_LEFT);
			$upc = substr($upc,0,8)."-".substr($upc,8);
			$upc = ltrim($upc,"0");
		}
		
		$searchQ = "select invoicedate,invnum from inv_lines where upc like '%$upc%' order by invoicedate desc";
		$searchR = $sql->query($searchQ);
		$out .= "<b>Invoice Number</b>: ";
		$out .= "<select id=select_inv onchange=\"pickInv();\">";
		$out .= "<option value=\"\">Pick an invoice...</option>";
		while ($searchW = $sql->fetch_row($searchR)){
			$out .= "<option>".$searchW[0]." ".$searchW[1]."</option>";
		}
		$out .= "</select> ";
		$out .= "<input type=submit value=Go onclick=\"pickNum();\">";
		break;
	}
	
	echo $out;
	return;
}

function getDates(){
	global $sql;

	$datesQ = "select invoicedate from headers group by invoicedate order by invoicedate desc limit 50";
	$datesR = $sql->query($datesQ);

	$ret = array();
	$i = 0;
	while ($datesW = $sql->fetch_row($datesR))
		$ret[$i++] = $datesW[0];
	return $ret;
}

function getInvoice($InvoiceNum,$InvoiceDate,$csv=False){
	global $sql;
	$ret = "";

	if (!$csv){
		$ret .= "<a href=index.php?num=$InvoiceNum&date=$InvoiceDate&csv=yes&action=pickNum>";
		$ret .= "Download as CSV</a><br />";
		$ret .= "<table cellpadding=4 cellspacing=0 border=1>";
	}
	else {
		header('Content-Type: application/ms-excel');
		header('Content-Disposition: attachment; filename="'.$InvoiceDate."_".$InvoiceNum.'.csv"');
	}
	$ret .= getHeader($InvoiceNum,$InvoiceDate,$csv);
	$ret .= getDetails($InvoiceNum,$InvoiceDate,$csv);
	$ret .= getFooter($InvoiceNum,$InvoiceDate,$csv);
	if (!$csv){
		$ret .= "</table>";
	}
	return $ret;
}

function getHeader($InvoiceNum,$InvoiceDate,$csv=False){
	global $sql;
	$LINE_START="<tr><td>";
	$SEP="</td><td>";
	$NL="</td</tr>";
	$Q="";
	if ($csv){
		$LINE_START="";
		$SEP=",";
		$NL="\r\n";
		$Q="\"";
	}
	
	$ret = $LINE_START.'RecordType'.$SEP.'InvNum'.$SEP.'OrderNumber'.$SEP.'InvoiceDate'.$SEP.'DBA'.$SEP;
	$ret .= 'CustomerNumber'.$SEP.'CustomerName'.$SEP.'CustAddr1'.$SEP.'CustAddr2'.$SEP.'City'.$SEP.'State'.$SEP;
	$ret .= 'ZipCode'.$SEP.'PhoneNumber'.$SEP.'Whse'.$SEP.'Trip'.$SEP.'Terms'.$SEP.'DCFlag'.$SEP.'TermsMsg'.$SEP.'CorpCustID'.$SEP;
	$ret .= 'CoverSheet'.$SEP.'OrdEntryData'.$SEP.'ChainCode'.$SEP.'ApplyToNum'.$SEP.'PONum'.$NL;

	$headersQ = "select 'Header',InvNum,Type,OrderNumber,InvoiceDate,DBA,CustomerNumber,CustomerName,
		CustAddr1,CustAddr2,City,State,ZipCode,PhoneNumber,Whse,Trip,Terms,DCFlag,TermsMsg,CorpCustID,
		CoverSheet,OrdEntryDate,ChainCode,ApplyToNum,PONum
		FROM headers WHERE InvNum=$InvoiceNum AND InvoiceDate='$InvoiceDate'";
	$headersR = $sql->query($headersQ);
	while ($w = $sql->fetch_row($headersR)){
		$ret .= $LINE_START;
		$ret .= $w[0].$SEP.$w[1].$SEP.$w[2].$SEP.$w[3].$SEP.$w[4].$SEP.$w[5].$SEP;
		$ret .= $w[7].$SEP.$w[8].$SEP.$w[9].$SEP.$w[10].$SEP.$w[11].$SEP.$w[12].$SEP;
		$ret .= $w[13].$SEP.$w[14].$SEP.$w[15].$SEP.$w[16].$SEP.$w[17].$SEP.$w[18].$SEP;
		$ret .= $w[19].$SEP.$w[20].$SEP.$w[21].$SEP.$w[22].$SEP.$w[23].$SEP.$w[24].$NL;
	}

	return $ret;
}

function getDetails($InvoiceNum,$InvoiceDate,$csv=False){
	global $sql;
	$LINE_START="<tr><td>";
	$SEP="</td><td>";
	$NL="</td</tr>";
	$Q="";
	if ($csv){
		$LINE_START="";
		$SEP=",";
		$NL="\r\n";
		$Q="\"";
	}

	$ret = $LINE_START.'RecordType'.$SEP.'InvNum'.$SEP.'Type'.$SEP.'UPC'.$SEP.'ProductID'.$SEP;
	$ret .= 'QuantityShipped'.$SEP.'Unit'.$SEP.'Size'.$SEP.$Q.'Brand'.$Q.$SEP.$Q.'Description'.$Q.$SEP.'RegularSRP'.$SEP;
	$ret .= 'Status'.$SEP.'NetPricePerUnit'.$SEP.'ExtendedPrice'.$SEP.'SaleSRP'.$SEP.'RegPricePerUnit'.$SEP.'CustomerID'.$SEP;
	$ret .= 'PriceOrigin'.$SEP.'QuantityOrdered'.$SEP.'Taxable'.$SEP.'LBFlag'.$SEP.'CWFlag'.$SEP;
	$ret .= 'CNF_RegSRP'.$SEP.'CNF_SpcSRP'.$SEP.'CorpCustID'.$SEP.'CorpProdID'.$SEP.'OrderNumber'.$SEP;
	$ret .= 'InvoiceDate'.$SEP.'Servings'.$NL;

	$detailsQ = "select 'Detail',InvNum,Type,UPC,ProductID,QuantityShipped,Unit,Size,Brand,Description,RegularSRP,
		Status,NetPricePerUnit,ExtendedPrice,SaleSRP,RegPricePerUnit,CustomerID,PriceOrigin,QuantityOrdered,
		Taxable,LBFlag,CWFlag,CNF_RegSRP,CNF_SpcSRP,CorpCustID,CorpProdID,OrderNumber,InvoiceDate,Servings
		FROM inv_lines WHERE InvNum=$InvoiceNum and InvoiceDate='$InvoiceDate'";
	$detailsR = $sql->query($detailsQ);
	while($w = $sql->fetch_row($detailsR)){
		$ret .= $LINE_START;
		$ret .= $w[0].$SEP.$w[1].$SEP.$w[2].$SEP.$w[3].$SEP.$w[4].$SEP.$w[5].$SEP.$w[6].$SEP;
		$ret .= $w[7].$SEP.$Q.$w[8].$Q.$SEP.$Q.$w[9].$Q.$SEP.$w[10].$SEP.$w[11].$SEP.$w[12].$SEP;
		$ret .= $w[13].$SEP.$w[14].$SEP.$w[15].$SEP.$w[16].$SEP.$w[17].$SEP.$w[18].$SEP;
		$ret .= $w[19].$SEP.$w[20].$SEP.$w[21].$SEP.$w[22].$SEP.$w[23].$SEP.$w[24].$SEP;
		$ret .= $w[25].$SEP.$w[26].$SEP.$w[27].$SEP.$w[28].$NL;
	}

	return $ret;
}

function getFooter($InvoiceNum,$InvoiceDate,$csv=False){
	global $sql;
	$LINE_START="<tr><td>";
	$SEP="</td><td>";
	$NL="</td</tr>";
	$Q="";
	if ($csv){
		$LINE_START="";
		$SEP=",";
		$NL="\r\n";
		$Q="\"";
	}

	$ret = $LINE_START.'RecordType'.$SEP.'InvNum'.$SEP.'Type'.$SEP.'NumGroceryItems'.$SEP.'NumRefrigItems'.$SEP;
	$ret .= 'NumRepackItems'.$SEP.'NumFrozenItems'.$SEP.'TotalPcs'.$SEP.'TotalWt'.$SEP.'TotalCubes'.$SEP.'NonSpecial'.$SEP;
	$ret .= 'Special'.$SEP.'GrossTotal'.$SEP.'VolDiscnt'.$SEP.'Discount'.$SEP.'NetTotal'.$SEP.'TotalRetail'.$SEP.'Deposits'.$SEP;
	$ret .= 'Adjustments'.$SEP.'ProfitAmnt'.$SEP.'ProfitPercent'.$SEP.'InvDate'.$SEP.'CorpCustID'.$SEP.'OrderNumber'.$NL;

	$footersQ = "select 'Footer',InvNum,Type,NumGroceryItems,NumRefrigItems,NumRepackItems,NumFrozenItems,
		TotalPcs,TotalWt,TotalCubes,NonSpecial,Special,GrossTotal,VolDiscnt,Discount,NetTotal,TotalRetail,
		Deposits,Adjustments,ProfitAmnt,ProfitPercent,InvDate,CorpCustID,OrderNumber
		FROM footers WHERE InvNum=$InvoiceNum AND InvDate='$InvoiceDate'";
	$footersR = $sql->query($footersQ);
	while($w = $sql->fetch_row($footersR)){
		$ret .= $LINE_START;
		$ret .= $w[0].$SEP.$w[1].$SEP.$w[2].$SEP.$w[3].$SEP.$w[4].$SEP.$w[5].$SEP;
		$ret .= $w[7].$SEP.$w[8].$SEP.$w[9].$SEP.$w[10].$SEP.$w[11].$SEP.$w[12].$SEP;
		$ret .= $w[13].$SEP.$w[14].$SEP.$w[15].$SEP.$w[16].$SEP.$w[17].$SEP.$w[18].$SEP;
		$ret .= $w[19].$SEP.$w[20].$SEP.$w[21].$SEP.$w[22].$SEP.$w[23].$SEP.$w[24].$SEP;
		$ret .= $w[25].$NL;
	}

	return $ret;
}

?>

<html>
<head><title>UNFI Invoices</title>
<script type=text/javascript src=index.js></script>
<style type=text/css>
a {
	color: blue;
}
</style>
</head>
<body>
<div id=datebar>
<b>Invoice Date</b>: 
<select id=date_select onchange="pickDate();">
<option value="">Select a date...</option>
<?php
$dates = getDates();
foreach ($dates as $d)
	echo "<option>$d</option>";
?>
</select>
 <input type=submit value=Go onclick="pickDate();">
 &nbsp;&nbsp;&nbsp;<i>OR</i>&nbsp;&nbsp;&nbsp;
<b>UPC</b>: <input type=text size=13 id=search_upc />
<input type=submit value=Search onclick="upcSearch();">
</div>
<div id=invoicenumber style="margin-top: 15px; margin-bottom: 15px;">

</div>
<div id=invoice>

</div>
</body>
</html>
