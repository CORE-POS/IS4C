<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');
include('barcodepdf.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$cards = "(";
$args = array();
foreach($_POST["cardno"] as $c){
	$cards .= "?,";
    $args[] = $c;
}
$cards = rtrim($cards,",");
$cards .= ")";

$selAddQ = $sql->prepare("SELECT m.card_no,c.firstname,c.lastname,
		m.street,'',m.city,m.state,
		m.zip,c.personnum,
		convert(varchar,d.end_date,101),n.payments
		FROM meminfo AS m LEFT JOIN
		custdata AS c ON m.card_no=c.cardno
		LEFT JOIN equity_live_balance as n
		on m.card_no=n.memnum
		LEFT JOIN memDates AS d
		ON m.card_no=d.card_no
		WHERE cardno IN $cards
		ORDER BY m.card_no,c.personnum"); 
$selAddR = $sql->execute($selAddQ, $args);

$today = date("F j, Y");

$pdf = new BarcodePDF();
$pdf->AddFont('Scala','B','Scala-Bold.php');
$pdf->AddFont('Scala','','Scala.php');

$startX = 15;
$startY = 10;
$x = $startX; 
$y = $startY;
$w = 80;
$left = 97;
$down = 56;

$count = 0;
$primary = "";
$person = 0;
$pdf->SetAutoPageBreak(True,0);
$pdf->AddPage();
//Meat of the statement
while($selAddW = $sql->fetch_row($selAddR)){
	if ($count % 2 == 0 && $count != 0){
		$y += $down;
		$x = $startX;
	}
	if ($count % 10 == 0 && $count != 0){
		addTempBacks();
		$pdf->AddPage();
		$y = $startY;
		$x = $startX;
	}

	$pdf->Image("wheat3.jpg",$x,$y,0,50,"JPG");

	$pdf->SetXY($x,$y+11);
	$pdf->SetFont('Scala','','10');
	//$selAddW[0] = "12345"; /* REMOVE */
	if ($selAddW[10] >= 100){
		$pdf->Cell($w,4,"Member No. $selAddW[0]",0,1,"L");	
	}
	else {
		$pdf->Cell(36,4,"Member No. $selAddW[0]",0);
		$pdf->Cell($w-36,4,"Exp. $selAddW[9]",0,1,'R');
	}

	$fullname = $selAddW[1]." ".$selAddW[2];
	//$fullname = "JOHN SAMPLE"; /* REMOVE */
	if ($selAddW[8] == 1){
		$primary = $fullname;
		$person = 1;
	}

	$pdf->SetX($x);
	$pdf->Cell(25,4,"Membership Name",0);
	$pdf->Cell($w-25,4,$primary,0,1,"R");

	$pdf->SetX($x);
	$pdf->Cell(30,4,"Household Member",0);
	$pdf->Cell($w-30,4,($primary==$fullname)?"":$fullname,0,1,"R");
	
	$pdf->SetX($x);
	$address = str_replace("\n"," ",$selAddW[3]);
	$pdf->Cell(25,4,"Street Address",0);
	$pdf->Cell($w-25,4,$address,0,1,"R");
	
	$pdf->SetX($x);
	$str = $selAddW[5].", ".$selAddW[6]." ".$selAddW[7];
	$pdf->Cell(25,4,"City/State/Zip",0);
	$pdf->Cell($w-25,4,$str,0,1,"R");

	$pdf->SetX($x);
	$pdf->Cell($w,3,"Membership",0,1,"L");
	$pdf->SetX($x);
	$pdf->Cell(20,4,"Signature:",0,0,"L");
	$pdf->Cell($w-20,4,"","B",0);
	$pdf->Ln(5);

	$barcode = "4";
	$barcode .= str_pad($person,3,"0",STR_PAD_LEFT);
	$barcode .= str_pad(9999999-((int)$selAddW[0]),7,STR_PAD_LEFT);

	$str = ($selAddW[10] < 100) ? "Temporary" : "";

	$pdf->SetX($x);
	$pdf->Cell(15,9,$str,0,0,"L");
	//$pdf->UPC_A($x+25,$y+39,$barcode,7,.35,True);
	$pdf->SetX($x+65);
	$pdf->Cell(15,9,$str,0,0,"R");

	$x += $left;
	$tag = $count % 10;
	$row = floor($tag / 2);
	$y = $startY + ($down*$row);
	
	$count += 1;
	$person += 1;
}

if ($pdf->PageNo() % 2 != 0)
	addTempBacks();
$pdf->Output('member cards.pdf','D');

function addTempBacks(){
	global $startX,$startY,$left,$down,$pdf,$w;

	$x = $startX;
	$y = $startY + 3;

	$pdf->AddPage();
	for($count = 0; $count < 10; $count++){
		if ($count % 2 == 0 && $count != 0){
			$y += $down;
			$x = $startX;
		}
		
		$txt = "Membership Card\nWhole Foods Community Co-op, Inc.";
		$pdf->SetFont("Scala","B",10);
		$pdf->SetXY($x,$y);
		$pdf->MultiCell($w,4,$txt,0,"C");
		$pdf->SetFont("Scala","",8);

		$txt = "This card must be shown to receive rights and benefits of membership such as a discount on purchase or the right to vote at member meetings. Membership is not transferrable.";
		$pdf->SetX($x);
		$pdf->MultiCell($w,4,str_replace("\n"," ",$txt),0,"L");

		$txt = "\tThis card will be void and invalid when a member:";
		$pdf->SetX($x);
		$pdf->MultiCell($w,4,$txt,0,"L");

		$txt = "1) Terminates membership OR";
		$pdf->SetX($x);
		$pdf->MultiCell($w,4,$txt,0,"L");

		$txt = "2) Does not comply with membership requirements as outlined in the Bylaws of this association OR";
		$pdf->SetX($x);
		$pdf->MultiCell($w,4,$txt,0,"L");

		$txt = "3) Does not complete the purchase of four shares of Class B Stock ($20/share) within two years of the date of the issue of this card.";
		$pdf->SetX($x);
		$pdf->MultiCell($w,4,$txt,0,"L");
		

		$x += $left;
		$row = floor($count / 2);
		$y = $startY + ($down*$row) + 3;
	}
	
}
