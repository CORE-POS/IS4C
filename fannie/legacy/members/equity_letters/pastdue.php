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

$selAddQ = $sql->prepare("SELECT m.card_no,c.FirstName,c.LastName,
		m.street,'',m.city,m.state,
		m.zip,n.payments,
		d.end_date
		FROM meminfo AS m LEFT JOIN
		custdata AS c ON m.card_no=c.CardNo
		AND c.personNum=1 LEFT JOIN
		is4c_trans.equity_live_balance AS n
		on m.card_no = n.memnum
		LEFT JOIN memDates AS d ON
		m.card_no=d.card_no
		WHERE CardNo IN $cards
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
   $pdf->Cell(100,10,sprintf("Equity past due $%.2f",$remainingB),0);
   $pdf->Ln(15);

   $pdf->MultiCell(0,5,"Dear ".$firstname.",");
   $pdf->Ln(5);

   $txt = "From the date of joining WFC, you had two years to complete the purchase of the required
$80.00 of Class B equity stock. Our records indicate that the above balance of Class B equity stock
is now overdue. Your membership has become inactive and you can no longer receive Owner
benefits or discounts or participate in the governance of WFC.";
   $pdf->MultiCell(0,5,str_replace("\n"," ",$txt));
   $pdf->Ln(5);

   $txt = "We hope you will choose to reactivate your membership. You can reactivate your membership
at the Customer Service Counter at any time during open hours by paying the entire balance of equity
due and confirming your current contact information.";
   $pdf->MultiCell(0,5,str_replace("\n"," ",$txt));
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

$pdf->Output('equity past due letters.pdf','D');
