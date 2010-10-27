<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$numbers = array("zero","one","two","three","four","five","six","seven",
		"eight","nine","ten","eleven","twelve","thirteen","fourteen",
		"fifteen","sixteen","seventeen","eighteen","nineteen","twenty");

$cards = "(";
foreach($_POST["cardno"] as $c){
	$cards .= $c.",";
}
$cards = rtrim($cards,",");
$cards .= ")";

$selAddQ = "SELECT m.card_no,c.firstname,c.lastname,
		m.street,'',m.city,m.state,
		m.zip,n.payments,
		convert(varchar,d.end_date,101)
		FROM meminfo AS m LEFT JOIN
		custdata AS c ON m.card_no=c.cardno
		AND c.personnum=1 LEFT JOIN
		newBalanceStockToday_test AS n
		on m.card_no = n.memnum
		LEFT JOIN memDates AS d ON m.card_no=d.card_no
		WHERE cardno IN $cards
		ORDER BY m.card_no"; 
$selAddR = $sql->query($selAddQ);

$today = date("F j, Y");

$pdf = new FPDF();

$pdf->AddFont('Scala','B','Scala-Bold.php');
$pdf->AddFont('Scala','','Scala.php');

//Meat of the statement
while($selAddW = $sql->fetch_row($selAddR)){
   $pdf->AddPage();
   $pdf->SetFont('Scala','B','14');
   $pdf->Cell(20,10,'Whole Foods Community Co-op',0);
   $pdf->Image($FANNIE_ROOT.'legacy/images/WFCLogoCThru1.jpg',130,10,50,25);
   $pdf->Ln(5);
   $pdf->SetFont('Scala','','12');
   $pdf->Cell(20,10,'610 East Fourth Street',0);
   $pdf->Ln(5);
   $pdf->Cell(20,10,'Duluth, MN  55805',0);
   $pdf->Ln(5);
   $pdf->Cell(20,10,'218-728-0884',0);
   $pdf->Ln(5);
   $pdf->Cell(20,10,'218-728-0490/fax',0);
   $pdf->Ln(18);

   $pdf->Cell(10,10,$today,0);
   $pdf->Ln(15);

   $firstname = ucwords(strtolower($selAddW[1]));
   $lastname = ucwords(strtolower($selAddW[2]));
   $fullname = $firstname." ".$lastname;
   $equity = $selAddW[8];
   $classA = 20;
   $classB = $equity - 20;
   $remainingB = 100 - $equity;
   $endDate = $selAddW[9];

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
   $pdf->Ln(10);

   $pdf->Cell(30,10,"",0);
   $pdf->Cell(100,10,sprintf("Remaining equity payment of $%.2f is due by %s.",$remainingB,$endDate),0);
   $pdf->Ln(15);

   $pdf->MultiCell(0,5,"Dear ".$firstname.",");
   $pdf->Ln(5);

   $txt = "This is a reminder regarding the balance of your required equity. From the date of joining
WFC, you have two years to complete the purchase of the required $80.00 of Class B equity stock. Our
records indicate that the above balance of Class B equity stock is due. If your receipts differ, please
advise me immediately.";
   $pdf->MultiCell(0,5,str_replace("\n"," ",$txt));
   $pdf->Ln(5);

   $txt = "We hope you will choose to continue your membership. However, if we do not receive your
payment by the due date, your membership will become inactive and you will not be eligible for 
Owner discounts and benefits or to participate in the governance of WFC.";
   $pdf->MultiCell(0,5,$txt);
   $pdf->Ln(5);

   $txt = "Owners with restricted income may apply to the Fran Skinner Memorial Matching Fund
for assistance with the purchase of Class B stock. Information on the Matching Fund is available on
our web site (www.wholefoods.coop) and in the store.";
   $pdf->MultiCell(0,5,$txt);
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

$pdf->Output('equity reminder letters.pdf','D');
