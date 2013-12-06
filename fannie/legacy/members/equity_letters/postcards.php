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
		m.street,'',m.city,m.state,m.zip
		FROM meminfo AS m LEFT JOIN
		custdata AS c ON m.card_no=c.cardno
		AND c.personNum=1
		WHERE cardno IN $cards
		ORDER BY m.card_no,c.personNum");
$selAddR = $sql->execute($selAddQ, $args);

$today = date("F j, Y");

$pdf = new FPDF('L','in',array(3.5,5.0));
$pdf->AddFont('ScalaSans','','ScalaSans.php');

$primary = "";
$pdf->SetAutoPageBreak(True,0);
$pdf->SetFont("ScalaSans","",10);
//Meat of the statement
while($selAddW = $sql->fetch_row($selAddR)){
	$pdf->AddPage();

	$fullname = $selAddW[1]." ".$selAddW[2];

	$pdf->SetXY(2.75,1.45);
	$pdf->Cell(2,0.25,$fullname,"",1,"L");

	$pdf->SetX(2.75);
	$address = str_replace("\n"," ",$selAddW[3]);
	$pdf->Cell(2,0.25,$address,"",1,"L");
	
	$pdf->SetX(2.75);
	$str = $selAddW[5].", ".$selAddW[6]." ".$selAddW[7];
	$pdf->Cell(2,0.25,$str,"",1,"L");
}

$pdf->Output('member postcards.pdf','D');
