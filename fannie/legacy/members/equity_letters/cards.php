<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

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
		m.zip,c.personnum,d.end_date,n.payments
		FROM meminfo AS m LEFT JOIN
		custdata AS c ON m.card_no=c.cardno
		LEFT JOIN equity_live_balance as n
		on m.card_no=n.memnum
		LEFT JOIN memDates AS d ON 
		c.cardno=d.card_no
		WHERE cardno IN $cards
		ORDER BY m.card_no,c.personnum"); 
$selAddR = $sql->execute($selAddQ, $args);

$today = date("F j, Y");

$pdf = new FPDF();
$pdf->AddFont('Scala','B','Scala-Bold.php');
$pdf->AddFont('Scala','','Scala.php');

$startX = 15;
$startY = 15;
$x = $startX; 
$y = $startY;
$w = 80;
$left = 97;
$down = 54;

$count = 0;
$primary = "";
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

	if ($selAddW[10] < 100)
		$pdf->Image("watermark.jpg",$x,$y,$w,50,"JPG");

	$pdf->SetXY($x,$y);
	$pdf->SetFont('Scala','','10');
	if ($selAddW[10] >= 100){
		$pdf->Cell(21,5,"Member No.",0);	
		$pdf->Cell($w-21,5,$selAddW[0],"B",1,"C");
	}
	else {
		$pdf->Cell(21,5,"Member No.",0);
		$pdf->Cell(15,5,$selAddW[0],"B",0,"C");
		$pdf->Cell(15,5,"Exp. Date ",0);
		$pdf->Cell($w-21-15-15,5,$selAddW[9],"B",1,"C");
	}

	$fullname = $selAddW[1]." ".$selAddW[2];
	if ($selAddW[8] == 1)
		$primary = $fullname;

	$pdf->SetX($x);
	$pdf->Cell(25,5,"First Full Name",0);
	$pdf->Cell($w-25,5,$primary,"B",1,"C");

	$pdf->SetX($x);
	$pdf->Cell(30,5,"Second Full Name",0);
	$pdf->Cell($w-30,5,($primary==$fullname)?"":$fullname,"B",1,"C");
	
	$pdf->SetX($x);
	$address = str_replace("\n"," ",$selAddW[3]);
	$pdf->Cell(25,5,"Street Address",0);
	$pdf->Cell($w-25,5,$address,"B",1,"C");
	
	$pdf->SetX($x);
	$str = $selAddW[5].", ".$selAddW[6]." ".$selAddW[7];
	$pdf->Cell(25,5,"City/State/Zip",0);
	$pdf->Cell($w-25,5,$str,"B",1,"C");

	$pdf->Ln(12);
	$pdf->SetX($x);
	$pdf->Cell($w,5,"(Member's Signature)","T",0,"C");
	$pdf->Ln(5);

	$txt = "Whole Foods Community Co-op, Inc.";
	$pdf->SetFont("Scala","B",10);
	$pdf->SetX($x);
	$pdf->MultiCell($w,4,$txt,0,"C");
	$pdf->SetFont("Scala","",10);

	$x += $left;
	$tag = $count % 10;
	$row = floor($tag / 2);
	$y = $startY + ($down*$row);
	
	$count += 1;
}

if ($pdf->PageNo() % 2 != 0)
	addTempBacks();
$pdf->Output('member cards.pdf','D');

function addTempBacks(){
	global $startX,$startY,$left,$down,$pdf,$w;

	$x = $startX;
	$y = $startY;

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

		$txt = "This card must be shown to receive rights and benefits of membership
such as a discount on purchase or the right to vote at member meet-
ings. Membership is not transferrable.";
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
		$y = $startY + ($down*$row);
	}
	
}
