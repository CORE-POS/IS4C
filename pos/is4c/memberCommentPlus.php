<?
if (!function_exists("memDataConnect")) include_once("connect.php");
if (!function_exists("pinghost")) include_once("connect.php");
?>
<html>
<head>
<title>Member Data</title>
<style type='text/css'>
td {}
table {border-bottom: 1px solid #000000; }
.header {text-align: left; background-color: #eeeeee; font-weight: bold; color: #000000;}
.skinny {float: left; margin-right: 12px;}
.details {cursor: pointer; color: #000; text-decoration: underline;}
</style>

<script language="javascript">
function displayoff (){
	document.getElementById('purch').style.visibility="hidden";
	document.getElementById('equity').style.visibility="hidden";
	document.getElementById('hide').style.display="none";
	document.getElementById('show').style.display="inline";
}
function displayon (){
	document.getElementById('purch').style.visibility="visible";
	document.getElementById('equity').style.visibility="visible";
	document.getElementById('hide').style.display="inline";
	document.getElementById('show').style.display="none";
}
</script>

</head>
<body style='background-color: #ffffff; color: #004080' onload='document.forms[0].elements[0].focus();'>

<div style='float: right;'>
<form method=post action=pos.php>
<input type=submit value=Back style='width: 70px;'>
</form>
</div>

<div style='width: 685px;'>
<?

if (!pinghost($_SESSION["memServer"])){
	echo "The membership server is currently unavailable.";
	exit;
}else{

	$memNum=$_SESSION["memberID"];

	if (strlen($memNum)==6){
		//---------------------display comments----------------------------------//
		$query="select Member, Last_Name, First_Name, Last, First, Address, Address2, City, State, Zip,
			Country, Home_Phone, Work_Phone, Signature2, Status, Refund_Requested, convert(char(8),Refund_Requested_On,1),
			Mailing, Newsletter, Mix, convert(char(8),DateJoined,1), Comments,inactiveStatus,signature1
			from MBRData
			where Member='".$memNum."'";
		
		echo "<table cellspacing=0 cellpadding=1 style='width: 680px;'>";
	
		$db = memDataConnect();
	
		$result = sql_query($query, $db);
		$num_rows = sql_num_rows($result);
		for ($i = 0; $i < $num_rows; $i++ ){
			$row = sql_fetch_array($result);
	
			if ($row[23]==0){
				$sign="Extra Name:";
			}else{
				$sign="Signer:";
			}
			echo "<caption style='background-color: #004080; color: #ffffff; font-weight: bold; text-align: left;'>".$sign." ".$row['2']." ".$row['1']." (".$memNum.")</caption>";
			if ($row['13']==0){
				echo "<tr><td style='font-weight: bold;'>Extra Name:</td>";
			}else{
				echo "<tr><td style='font-weight: bold;'>Signer:</td>";
			}
			echo "<td>".$row['4']." ".$row['3']."&nbsp;</td></tr>";
	
			echo "<tr><td style='font-weight: bold; width: 170px;'>Address:</td>";
			echo "<td>".$row['5']."</td></tr>";
			if (strlen($row['6'])>0){
				echo "<tr><td></td><td>".$row['6']."</td></tr>";
			}
			echo "<tr><td></td>";
			echo "<td>".$row['7'].", ".$row['8']." ".$row['9']." ".$row['10']."</td></tr>";
			echo "<tr><td style='font-weight: bold;'>Phone:</td>";
			echo "<td style='width: 325px;'>H: ".$row['11']."&nbsp;</td>";
			echo "<td>W: ".$row['12']."&nbsp;</td></tr>";
	
			echo "<tr><td style='font-weight: bold;'>Comment:</td>";
			echo "<td>".$row['21']."</td></tr>";
			if ($row['17']==1){
				$mailing="Yes";
			}else{
				$mailing="No";
			}
			if ($row['18']==1){
				$newsletter="Yes";
			}else{
				$newsletter="No";
			}
			if ($row['19']==1){
				$mixed="Yes";
			}else{
				$mixed="No";
			}
			$status=$row['14'];
			$refReq=$row['15'];
			$refReqOn=$row['16'];
			$inactStatus=$row['22'];
		}
		echo "</table>";
		//---------------------------MemberStatus/Refunds?---------------------------------------
		echo "<table cellspacing=0 cellpadding=1 style='width: 680px;'>";
		echo "<tr><td style='font-weight: bold; width: 170px;'>Member Status:</td>";

		switch ($inactStatus) {
			case "2":
			case "14":
			case "10":
				echo "<td style=''>".$status."</td>";
				echo "<td>Refund requested on: ".$refReqOn."</td>";
			break;
			default:
				if ($row['14'] == "INACTIVE") { echo "<td colspan=2 style=''>".$row[21]."</td>"; }
				else { echo "<td colspan=2 style''>".$row['14']."</td>"; }
			break;
		}
	
		echo "</table>";
		//--------------Mailing------------------------------------------------------
		echo "<table cellspacing=0 cellpadding=1 style='width: 680px;'>";
		//echo "<tr><td class=header colspan=100%>Mailing</td></tr>";
		echo "<tr><td style='width: 170px; font-weight: bold;'>Mailing:</td><td style='width: 99px;'>".$mailing."</td><td style='width: 145px; font-weight: bold;'>Newsletter:</td><td style='width: 80px;'>".$newsletter."</td><td style='width: 145px; font-weight: bold;'>Mixed:</td><td>".$mixed."</td></tr>";
		echo "</table><br>";
	
		//--------------------------------------coupon info-----------------------------------------------------//
	
		$query="select convert(char(8),U.[Date],1), m.Purchase, PerVisitCoupons, sum(-1*(U.Amount)) as CouponValue
			from
				(select Member,[Date], Register_No, Emp_No, Trans_No, Sum(CouponsUsed) as PerVisitCoupons,
				-1*sum([Total]) as Amount
				from ".$_SESSION["remoteDB"]."CouponUsagePolled
				where member=".$memNum."
				group by Member, [Date], Register_No, Emp_no, Trans_no) u
			left join ".$_SESSION["mServer"].".".$_SESSION["memDatabase"].".dbo.MemPurchases M on M.member=U.member and M.date=U.date
			where U.Member = '".$memNum."'
			group by U.[Date], M.Purchase, U.Amount, PerVisitCoupons
			order by U.[date] desc";
	
		if ($status<>'INACTIVE' and $status<>'VOID'){
			$result = sql_query($query, $db);
			$num_rows = sql_num_rows($result);
			if ($num_rows==0){
				echo "<table cellspacing=0 cellpadding=0 style='width: 680px;'>";
				echo "<tr><td class=header colspan=100%>Coupons</td></tr>";
				echo "<tr><td style='font-weight: bold; width: 170px;'>Coupons Used: </td>";
				echo "<td>0</td>";
				echo "</table>";
			}else{
				echo "<table cellspacing=0 cellpadding=1 style='width: 680px;'>";
				echo "<tr><td class=header colspan=100%>Coupons</td></tr>";
				echo "<tr><td style='font-weight: bold; width: 170'>Date:</td>";
				echo "<td style='font-weight: bold;'>Coupons Used:</td>";
				echo "<td style='font-weight: bold;'>Coupon Value:</td>";
				echo "<td style='font-weight: bold;'>Purchase amount:</td></tr>";
				for ($i = 0; $i < $num_rows; $i++ ){
					$row = sql_fetch_array($result);
					echo "<tr><td>".$row['0']."</td>";
					echo "<td>".$row['2']."</td>";
					echo "<td>".number_format($row['3'],2)."</td>";
					echo "<td>$".number_format($row['1'],2)."</td></tr>";
				}
				echo "</table>";
			}
		}
	
	
		echo "</div>";

		echo "<span style='width: 250px;'>&nbsp;</span>";
		echo "<span onClick=displayon(); class=details id=show>Show Details</span>";
		echo "<span onClick=displayoff(); class=details id=hide style='display: none;'>Hide Details</span>";

		//---------------------------------Stock Payments info-----------------------------------//
	
		echo "<div class=skinny>";
		echo "<table cellpadding=1 cellspacing=0 style='background-color: #fff; border: 1px solid #ddd;'>";
		echo "<tr><td class=header colspan=100%>Stock Payment History</td></tr>";
		echo "<tr><td style='width: 70px; font-weight: bold;'>Date</td>";
		echo "<td style='font-weight: bold;'>Amount</td></tr>";
		$query="select amount,convert(char(8),date,1) from stock where member=".$memNum." order by date";
		$result = sql_query($query, $db);
		$num_rows = sql_num_rows($result);
		$paid=0;
		for ($i = 0; $i < $num_rows; $i++ ){
			$row = sql_fetch_array($result);
			echo "<tr><td>&nbsp;".$row['1']."</td><td>&nbsp;$".number_format($row['0'],2)."</td></tr>";
			$paid+=$row['0'];
	
		}
		if ($status=='VOID'){
			$owed=0;
			$a=0;
			$b=0;
		}elseif ($status=='INACTIVE'){
			$a=0;
			$b=$paid;
			$owed=80-$paid;
		}else{
			$a=10;
			$b=$paid-10;
			$owed=80-$paid;
		}
		for ($i=$num_rows;$i<10;$i++){
			echo "<tr><td colspan=2>&nbsp;</td></tr>";
		}
		echo "<tr><td style='color: #000000; border-top: 1px solid #ddd; background-color: #eee;'>Class A</td><td style='font-weight: bold; color: #000000; border-top: 1px solid #ddd; background-color: #eee;'>&nbsp;$".number_format($a,2)."</td></tr>";
		echo "<tr><td style='color: #000000; border-top: 0px solid #ddd; background-color: #eee;'>Class B</td><td style='font-weight: bold; color: #000000; border-top: 0px solid #ddd; background-color: #eee;'>&nbsp;$".number_format($b,2)."</td></tr>";

		echo "<tr><td style='color: #000000; border-top: 0px solid #ddd; background-color: #eee;'>Paid</td><td style='font-weight: bold; color: #000000; border-top: 0px solid #ddd; background-color: #eee;'>&nbsp;$".number_format($paid,2)."</td></tr>";
		echo "<tr><td style='color: #000000; border-top: 0px solid #ddd; background-color: #eee;'>Owed</td><td style='font-weight: bold; color: #000000; border-top: 0px solid #ddd; background-color: #eee;'>&nbsp;$".number_format($owed,2)."</td></tr>";
		echo "</table>";
		echo "</div>";
	
		//------------------------Member purchases------------------//
		$query="select Member,
			rtrim(convert(char,case when datepart(mm,getdate())>=7 then datepart(yy,getdate()) else datepart(yy,getdate())-1 end))+'-'+
			rtrim(convert(char,case when datepart(mm,getdate())>=7 then datepart(yy,getdate())+1 else datepart(yy,getdate()) end))
			, Sum(Purchase) as TotalFE
			from ".$_SESSION["remoteDB"]."MemPurchasesLogComp
			where Member = '".$memNum."'
			and [Date]> '06/30/'+convert(char,case when datepart(mm,getdate())>=7 then datepart(yy,getdate()) else datepart(yy,getdate())-1 end)
			and [Date]< '07/01/'+convert(char,case when datepart(mm,getdate())>=7 then datepart(yy,getdate())+1 else datepart(yy,getdate()) end)
			group by Member";
	
		echo "<div class=skinny id=purch style='visibility: hidden;'>";
		echo "<table cellpadding=1 cellspacing=0 style='background-color: #fff; border: 1px solid #ddd;'>";
		echo "<tr><td class=header colspan=100%>Fiscal Year Purchases</td></tr>";
		echo "<tr><td style='font-weight: bold;'>Fiscal Year</td><td style='font-weight: bold;' align=right>Purchases&nbsp;</td></tr>";
	
		$result = sql_query($query, $db);
		$num_rows = sql_num_rows($result);
		if ($num_rows>0){
			for ($i = 0; $i < $num_rows; $i++ ){
				$row = sql_fetch_array($result);
				echo "<tr><td>&nbsp;".$row[1]."</td><td align=right>$".number_format($row[2],2)."&nbsp;</td></tr>";
			}
		}else{
			echo "<tr><td>&nbsp;</td><td align=right>&nbsp;</td></tr>";
		}
		$query="Select Member, FY, FYPurchases From ".$_SESSION["memSalesDatabase"].".dbo.MemberPurchasesFY_All Where Member = '".$memNum."' order by FY desc";
		$result = sql_query($query, $db);
		$num_rows = sql_num_rows($result);
		for ($i = 0; $i < $num_rows; $i++ ){
			$row = sql_fetch_array($result);
			echo "<tr><td>&nbsp;".$row[1]."</td><td align=right>$".number_format($row[2],2)."&nbsp;</td></tr>";
		}
		for ($i=$num_rows;$i<13;$i++){
			echo "<tr><td style='' colspan=2>&nbsp;</td></tr>";
		}
		echo "</table>";
		echo "</div>";
	
		//-----------------------Equity-------------------------------//
		echo "<div class=skinny id=equity style='visibility: hidden;'>";
		echo "<table cellpadding=1 cellspacing=0 style='background-color: #fff; border: 1px solid #ddd;'>";
		echo "<tr><td class=header colspan=100%>Equity</td></tr>";
		echo "<tr><td style='font-weight: bold;'>Fiscal Year</td><td style='font-weight: bold; width: 70px;' align=right>Equity&nbsp;</td><td style='font-weight: bold; width: 70px;' align=right>Cash&nbsp;</td><td style='font-weight: bold; width: 55px;' align=right>Check&nbsp;</td></tr>";
	
		$equity=0;
		$cash=0;
		$query="select fy,equity_certificates,cash_rebate,check_no from equity where member='".$memNum."' and equity_certificates<>0 order by fy desc";
		$result = sql_query($query, $db);
		$num_rows = sql_num_rows($result);
		for ($i = 0; $i < $num_rows; $i++ ){
			$row = sql_fetch_array($result);
			echo "<tr><td>&nbsp;".$row[0]."</td><td align=right>$".number_format($row[1],2)."</td><td align=right>$".number_format($row[2],2)."</td><td align=right>".$row[3]."&nbsp;</td></tr>";
			$equity+=$row[1];
			$cash+=$row[2];
		}
		for ($i=$num_rows;$i<13;$i++){
			echo "<tr><td style='' colspan=4>&nbsp;</td></tr>";
		}
		echo "<tr><td style='color: #000000; border-top: 1px solid #ddd; background-color: #eee;'>Total</td><td align=right style='color: #000000; border-top: 1px solid #ddd; background-color: #eee; font-weight: bold;'>$".number_format($equity,2)."</td><td align=right style='color: #000000; border-top: 1px solid #ddd; background-color: #eee; font-weight: bold;'>$".number_format($cash,2)."</td><td style='color: #000000; border-top: 1px solid #ddd; background-color: #eee;'>&nbsp;</td></tr>";
		echo "</table>";
		echo "</div>";
	
	}else{
		echo "No details available.";
		echo "<form method=post action=pos.php>";
		echo "<input type=submit value=Back style='width: 100px;'>";
		echo "</form>";
	}
}
?>


</body>
</html>