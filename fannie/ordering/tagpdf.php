<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/tmp_dir.php');

if (isset($_REQUEST['toids'])){
	define('FPDF_FONTPATH','font/');
	include($FANNIE_ROOT.'src/fpdf/fpdf.php');
	include($FANNIE_ROOT.'src/barcodepdf.php');

	$pdf=new WFC_Standard_PDF('P','mm','Letter'); //start new instance of PDF
	$pdf->Open(); //open new PDF Document

	$count = 0;
	$x = 0;
	$y = 0;
	$date = date("m/d/Y");
	foreach($_REQUEST['toids'] as $toid){
		if ($count % 4 == 0){ 
			$pdf->AddPage();
			$pdf->SetDrawColor(0,0,0);
			$pdf->Line(108,0,108,279);
			$pdf->Line(0,135,215,135);
		}

		$x = $count % 2 == 0 ? 5 : 115;
		$y = ($count/2) % 2 == 0 ? 10 : 145;
		$pdf->SetXY($x,$y);

		$tmp = explode(":",$toid);
		$tid = $tmp[0];
		$oid = $tmp[1];

		$q = "SELECT ItemQtty,total,regPrice,p.card_no,description,department,
			CASE WHEN p.card_no=0 THEN t.last_name ELSE c.LastName END as name,
			CASE WHEN p.card_no=0 THEN t.first_name ELSE c.FirstName END as fname,
			CASE WHEN p.card_no=0 THEN t.phone ELSE m.phone END as phone,
			discounttype,quantity
			FROM PendingSpecialOrder AS p
			LEFT JOIN custdata AS c ON p.card_no=c.CardNo AND personNum=p.voided
			LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
			LEFT JOIN SpecialOrderContact AS t ON t.card_no=p.order_id
			WHERE trans_id=$tid AND p.order_id=$oid";
		$r = $dbc->query($q);
		$w = $dbc->fetch_row($r);

		// flag item as "printed"
		$q2 = "UPDATE PendingSpecialOrder SET charflag='P'
			WHERE trans_id=$tid AND order_id=$oid";
		$r2 = $dbc->query($q2);

		$q3 = "SELECT trans_id FROM PendingSpecialOrder WHERE
			trans_id > 0 AND order_id=$oid ORDER BY trans_id";
		$r3 = $dbc->query($q3);
		$o_count = 0;
		$rel_id = 1;
		while($w3 = $dbc->fetch_row($r3)){
			$o_count++;
			if ($w3['trans_id'] == $tid)
				$rel_id = $o_count;
		}

		$pdf->SetFont('Arial','','12');
		$pdf->Text($x+85,$y,"$rel_id / $o_count");

		$pdf->SetFont('Arial','B','24');
		$pdf->Cell(100,10,$w['name'],0,1,'C');
		$pdf->SetFont('Arial','','12');
		$pdf->SetX($x);
		$pdf->Cell(100,8,$w['fname'],0,1,'C');
		$pdf->SetX($x);
		if ($w['card_no'] != 0){
			$pdf->Cell(100,8,"Owner #".$w['card_no'],0,1,'C');
			$pdf->SetX($x);
		}

		$pdf->SetFont('Arial','','16');
		$pdf->Cell(100,9,$w['description'],0,1,'C');
		$pdf->SetX($x);
		$pdf->Cell(100,9,"Cases: ".$w['ItemQtty'].' - '.$w['quantity'],0,1,'C');
		$pdf->SetX($x);
		$pdf->SetFont('Arial','B','16');
		$pdf->Cell(100,9,sprintf("Total: \$%.2f",$w['total']),0,1,'C');
		$pdf->SetFont('Arial','','12');
		$pdf->SetX($x);
		if ($w['discounttype'] == 1 || $w['discounttype'] == 2){
			$pdf->Cell(100,9,'Sale Price',0,1,'C');
			$pdf->SetX($x);

		}
		elseif ($w['regPrice']-$w['total'] > 0){
			$percent = round(100 * (($w['regPrice']-$w['total'])/$w['regPrice']));
			$pdf->Cell(100,9,sprintf("Owner Savings: \$%.2f (%d%%)",
					$w['regPrice'] - $w['total'],$percent),0,1,'C');
			$pdf->SetX($x);
		}
		$pdf->Cell(100,6,"Tag Date: ".$date,0,1,'C');
		$pdf->SetX($x);
		$pdf->Cell(100,6,"Dept #".$w['department'],0,1,'C');
		$pdf->SetX($x);
		$pdf->Cell(100,6,"Ph: ".$w['phone'],0,1,'C');
		$pdf->SetXY($x,$y+85);
		$pdf->Cell(160,10,"Notes: _________________________________");	
		$pdf->SetX($x);
		
		$upc = "454".str_pad($oid,6,'0',STR_PAD_LEFT).str_pad($tid,2,'0',STR_PAD_LEFT);
		//$chk = $pdf->GetCheckDigit($upc);

		$pdf->UPC_A($x+30,$y+95,$upc);
		
		$count++;
	}

	$pdf->Output();
	exit;
}

$page_title = "Fannie :: Special Orders";
$header = "Special Orders";
include($FANNIE_ROOT.'src/header.html');

if (!isset($_REQUEST['oids'])){
	echo "<i>No order(s) selected</i><br />";
}
else {
	?>
	<script type="text/javascript">
	function toggleChecked(status){
		$(".cbox").each( function() {
			$(this).attr("checked",status);
		});
	}
	</script>
	<?php
	echo '<form action="tagpdf.php" method="get">';
	echo '<input type="checkbox" id="sa" onclick="toggleChecked(this.checked);" />';
	echo '<label for="sa"><b>Select All</b></label>';
	echo '<table cellspacing="0" cellpadding="4" border="1">';
	include($FANNIE_ROOT.'auth/login.php');
	$username = checkLogin();
	$cachepath = sys_get_temp_dir()."/ordercache/";
	if (file_exists("{$cachepath}{$username}.prints")){
		$prints = unserialize(file_get_contents("{$cachepath}{$username}.prints"));
		foreach($prints as $oid=>$data){
			if (!in_array($oid,$_REQUEST['oids']))
				$_REQUEST['oids'][] = $oid;
		}
	}
	foreach($_REQUEST['oids'] as $oid){
		$q = sprintf("SELECT min(datetime) as orderDate,sum(total) as value,
			count(*)-1 as items,
			CASE WHEN MAX(p.card_no)=0 THEN MAX(t.last_name) ELSE MAX(c.LastName) END as name
			FROM PendingSpecialOrder AS p
			LEFT JOIN custdata AS c ON c.CardNo=p.card_no AND personNum=p.voided
			LEFT JOIN SpecialOrderContact AS t ON t.card_no=p.order_id	
			WHERE p.order_id=%d",$oid);
		$r = $dbc->query($q);
		$w = $dbc->fetch_row($r);
		printf('<tr><td colspan="2">Order #%d (%s, %s)</td><td>Amt: $%.2f</td>
			<td>Items: %d</td><td>&nbsp;</td></tr>',
			$oid,$w['orderDate'],$w['name'],$w['value'],$w['items']);

		$q = sprintf("SELECT description,department,quantity,ItemQtty,total,trans_id
			FROM PendingSpecialOrder WHERE order_id=%d AND trans_id > 0",
			$oid);
		$r = $dbc->query($q);
		while($w = $dbc->fetch_row($r)){
			printf('<tr><td>&nbsp;</td><td>%s (%d)</td><td>%d x %d</td>
				<td>$%.2f</td>
				<td><input type="checkbox" class="cbox" name="toids[]" value="%d:%d" /></td>
				</tr>',
				$w['description'],$w['department'],$w['ItemQtty'],$w['quantity'],
				$w['total'],$w['trans_id'],$oid);
		}
	}
	echo '</table>';
	echo '<br />';
	echo '<input type="submit" value="Print Tags" />';
	echo '</form>';
}

include($FANNIE_ROOT.'src/footer.html');
?>
