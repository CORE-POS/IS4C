<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$numbers = array("zero","one","two","three","four","five","six","seven",
		"eight","nine","ten","eleven","twelve","thirteen","fourteen",
		"fifteen","sixteen","seventeen","eighteen","nineteen","twenty");

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

   $pdf->MultiCell(0,5,"Thank you for fulfilling your WFC equity requirement!");
   $pdf->Ln(5);

	$pdf->Write(5,"This letter certifies that you are the owner of twenty");
	$pdf->Write(5," shares (at $5.00/share) of stock in WFC. Your investment represents");
	$pdf->Write(5," four Class A/voting shares and");
	$pdf->Write(5,str_replace("\n"," "," sixteen Class B/equity shares. WFC uses electronic records to document stock purchases and
no longer issues paper stock certificates. Please keep your stock purchase receipts and this letter
as proof of your ownership."));
	$pdf->Write(5,"\n");
	$pdf->Ln(5);

   $txt = "Don't forget to use your Owner card each time you shop. This card qualifies
you for discounts at local businesses participating in our Community Cooperation Program, 
identifies you at WFC's checkouts, and properly allocates your purchases. Please remember 
to keep WFC updated with any changes in your address or phone number.";
   $pdf->SetFont("Scala","","12");
   $pdf->Write(5,str_replace("\n"," ",$txt)."\n");
   $pdf->Ln(5);

   $txt = "If you have any questions, please do not hesitate to ask. I can be reached at the number above or
at mms@wholefoods.coop";
   $pdf->MultiCell(0,5,$txt);
   $pdf->Ln(10);

   $pdf->MultiCell(0,5,"Sincerely yours,");
   $pdf->MultiCell(0,5,"WHOLE FOODS COMMUNITY CO-OP, INC.");
   $pdf->Ln(10);

   $pdf->MultiCell(0,5,"Amanda Borgren");
   $pdf->MultiCell(0,5,"Owner Services");

}

$pdf->Output('member welcome letters.pdf','D');
