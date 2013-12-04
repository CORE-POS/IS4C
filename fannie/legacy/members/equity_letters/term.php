<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$numbers = array("zero","one","two","three","four","five","six","seven",
		"eight","nine","ten","eleven","twelve","thirteen","fourteen",
		"fifteen","sixteen","seventeen","eighteen","nineteen","twenty");

$args = array();
foreach($_REQUEST["cardno"] as $c){
	$cards .= "?,";
    $args[] = $c;
}
$cards = rtrim($cards,",");
$cards .= ")";

$selAddQ = $sql->prepare("SELECT m.card_no,c.firstname,c.lastname,
		m.street,'',m.city,m.state,
		m.zip
		FROM meminfo AS m LEFT JOIN
		custdata AS c ON m.card_no=c.cardno
		AND c.personnum=1
		WHERE cardno IN $cards
		ORDER BY m.card_no");
$selAddR = $sql->execute($selAddQ, $args);

$today = date("F j, Y");

$pdf = new FPDF();
$pdf->AddFont('Scala','B','Scala-Bold.php');
$pdf->AddFont('Scala','','Scala.php');

//Meat of the statement
while($selAddW = $sql->fetch_row($selAddR)){
   $pdf->AddPage();

   $pdf->Ln(5);
   $pdf->Image($FANNIE_ROOT.'legacy/images/letterhead.jpg',10,10,200);
   $pdf->Ln(5);
   $pdf->SetFont('Scala','','12');
   $pdf->Ln(35);

   $pdf->Cell(10,10,$today,0);
   $pdf->Ln(15);

   $firstname = ucwords(strtolower($selAddW[1]));
   $lastname = ucwords(strtolower($selAddW[2]));
   $fullname = $firstname." ".$lastname;
	/*
   $equity = $selAddW[8];
   $classA = 20;
   $classB = $equity - 20;
   $remainingB = 100 - $equity;
   $endDate = $selAddW[9];
	*/

   //Member address
   $pdf->Cell(10,10,trim($fullname),0);
   $pdf->Ln(5);

   if (strstr($selAddW[3],"\n") === False){
	$pdf->Cell(80,10,$selAddW[3],0);
	$pdf->Ln(5);
   }
   else {
	$pts = explode("\n",$selAddW[3]);
	$pdf->Cell(80,10,$pts[0],0);
	$pdf->Ln(5);
	$pdf->Cell(80,10,$pts[1],0);
	$pdf->Ln(5);
   }
   $pdf->Cell(90,10,$selAddW[5] . ', ' . $selAddW[6] . '   ' . $selAddW[7],0);
   $pdf->Ln(15);

   $pdf->MultiCell(0,5,"Dear ".$firstname.",");
   $pdf->Ln(5);

   $txt = "We have received your Application to Terminate your membership at WFC. The
Board reviews termination requests annually in ";
   $pdf->SetFont("Scala","","12");
   $pdf->Write(5,str_replace("\n"," ",$txt));
   $pdf->SetFont("Scala","B","12");
   $pdf->Write(5,"February");
   $pdf->SetFont("Scala","","12");
   $txt = ". Refunds, less any indebtedness owed to WFC, are authorized for payment in
the order received subject to the financial health of WFC and receipt of additional stock
from new members. Your stock will be refunded as soon as possible based on these criteria.";
   $pdf->Write(5,str_replace("\n"," ",$txt)."\n");
   $pdf->Ln(5);

   $txt = "Submission of an Application to Terminate immediately inactivates your owner
benefits and discounts and your right to participate in governance of WFC. Please keep us
advised of any changes in your mailing address.";
   $pdf->MultiCell(0,5,$txt);
   $pdf->Ln(5);

   $txt = "If you have any questions, please do not hesitate to ask. I can be reached at the
number above or at mms@wholefoods.coop. Thank you.";
   $pdf->MultiCell(0,5,$txt);
   $pdf->Ln(5);

   $pdf->MultiCell(0,5,"Thank you for your support of WFC");
   $pdf->Ln(10);

   $pdf->MultiCell(0,5,"Sincerely yours,");
   $pdf->MultiCell(0,5,"WHOLE FOODS COMMUNITY CO-OP, INC.");
   $pdf->Ln(10);

   $pdf->MultiCell(0,5,"Amanda Borgren");
   $pdf->MultiCell(0,5,"Owner Services");

}

$pdf->Output('member term letters.pdf','D');
