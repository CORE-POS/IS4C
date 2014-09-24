<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$cards = "(";
$args = array();
if (isset($_POST["cardno"])){
	foreach($_POST["cardno"] as $c){
		$cards .= "?,";
        $args[] = $c;
	}
	$cards = rtrim($cards,",");
	$cards .= ")";
}

$cardsClause = " AND m.card_no IN $cards ";
if ($cards == "(") $cardsClause = "";

$selAddQ = $sql->prepare("SELECT m.card_no, a.memName,m.street, '',
           m.City, m.State, m.zip,
	   a.TwoMonthBalance,a.LastMonthCharges,
	   a.LastMonthPayments,a.LastMonthBalance
           FROM is4c_trans.AR_EOM_Summary a LEFT JOIN
           meminfo m ON a.cardno = m.card_no
	   LEFT JOIN custdata as c on c.cardno=a.cardno and c.personnum=1
	   WHERE c.type not in ('TERM') and
	   c.memtype = 2
	   $cardsClause 
	   and (a.LastMonthBalance <> 0 or a.lastMonthCharges <> 0 or a.lastMonthPayments <> 0)
           ORDER BY a.cardno");
$selAddR = $sql->execute($selAddQ, $args);

$arP = $sql->prepare('SELECT card_no, charges, payments, tdate, trans_num
                      FROM is4c_trans.ar_history WHERE card_no=?
                      AND datediff(tdate,curdate()) > -91'); 
$transP = $sql->prepare('SELECT tdate, upc, description
                        FROM is4c_trans.dlog_90_view
                        WHERE tdate BETWEEN ? AND ?
                            AND card_no=?
                            AND trans_num=?
                            AND trans_type IN (\'I\', \'D\')');
/*
$selTransQ = $sql->prepare("SELECT card_no, charges, payments, 
	date_format(date,'%Y-%m-%d'), trans_num,description,dept_name  
	FROM is4c_trans.AR_statementHistory as m WHERE 1=1 $cardsClause
	order by card_no,date desc,trans_num,description,dept_name");
$selTransR = $sql->execute($selTransQ, $args);
$selTransN = $sql->num_rows($selTransR);
*/

$today= date("d-F-Y");
$month = date("n");
$year = date("Y");

if($month != 1){
   $prevMonth = $month- 1;
}else{
   $prevMonth = 12;
   $year = $year - 1;
}
$prevYear = $year;

$prevPrevMonth = $prevMonth - 1;
$prevPrevYear = $year;
if ($prevPrevMonth == 0){
	$prevPrevMonth = 12;
	$prevPrevYear = $year - 1;
}

$stateDate = date("d F, Y",mktime(0,0,0,$month,0,$year));

$pdf = new FPDF();

//Meat of the statement
$rowNum=0;
while($selAddW = $sql->fetch_row($selAddR)){
   $pdf->AddPage();

   $pdf->Ln(5);
   $pdf->Image($FANNIE_ROOT.'legacy/images/letterhead.jpg',10,10,200);
   $pdf->Ln(5);
   $pdf->SetFont('Arial','','12');
   $pdf->Ln(35);

   $pdf->Cell(10,10,$stateDate,0);
   $pdf->Ln(8);


   //Member address
   $pdf->SetX(15);
   $pdf->Cell(50,10,trim($selAddW[0]).' '.trim($selAddW[1]),0);
   $pdf->Ln(5);

   if (strstr($selAddW[2],"\n") === False){
	   $pdf->Cell(80,10,$selAddW[2],0);
	   $pdf->Ln(5);
   }
   else {
	$pts = explode("\n",$selAddW[2]);
	$pdf->Cell(80,10,$pts[0],0);
	$pdf->Ln(5);
	$pdf->Cell(80,10,$pts[1],0);
	$pdf->Ln(5);
   }
   $pdf->Cell(90,10,$selAddW[4] . ', ' . $selAddW[5] . '   ' . $selAddW[6],0);
   $pdf->Ln(25);
 
   $txt = "If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884. See reverse side for WFC IOU Policies. ";
   $pdf->MultiCell(0,5,$txt);
   $pdf->Ln(10);

   $startYear = date('Y');
   $lastMonth = date("n")-1;
   $lastMonth = $lastMonth . '/01/'. $startYear;
   $twoMonth = date("n")-2;
   $twoMonth = $twoMonth . '/01/'.$startYear;

   $span = date("F Y",mktime(0,0,0,$prevMonth,1,$prevYear));
   $dateStart = date('F',strtotime($lastMonth));
   $pdf->Cell(0,8,"Balance summary $span",0,1,'C'); 
   $pdf->SetFillColor(200);
   $pdf->Cell(20,8,'',0,0,'L');
   $pdf->Cell(40,8,'Beginning Balance',0,0,'L',1);
   $pdf->Cell(20,8,'Charges',0,0,'L',1);
   $pdf->Cell(25,8,'Payments',0,0,'L',1);
   $pdf->Cell(35,8,'Ending Balance',0,0,'L',1);
   //$pdf->SetFillColor(255,0,0);
   $pdf->SetFont('Arial','B','14');   
   $pdf->Cell(35,8,'Amount Due',0,1,'L',1);
   $pdf->SetFont('Arial','','12');
   
   $pdf->Cell(20,8,'',0,0,'L');
   $pdf->Cell(40,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');
   $pdf->Cell(20,8,'$ ' . sprintf("%.2f",$selAddW[8]),0,0,'L');
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$selAddW[9]),0,0,'L');
   $pdf->Cell(35,8,'$ ' . sprintf("%.2f",$selAddW[10]),0,0,'L');
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$selAddW[10]),0,0,'L');
   $pdf->Ln(10);
 
   $date2Month = date('F',strtotime($twoMonth));
   $pdf->SetFontSize(10);
   $pdf->Cell(0,5,"Recent Activity (90 days OR 15 transactions)",0,0,'C');
   $pdf->Ln(5);
   $pdf->SetFillColor(200);
   $pdf->Cell(20,5,'',0,0,'L');
   $pdf->Cell(60,5,'Date',0,0,'L',1);
   $pdf->Cell(20,5,'Receipt',0,0,'L',1);
   $pdf->Cell(25,5,'Charges',0,0,'L',1);
   $pdf->Cell(25,5,'Payments',0,0,'L',1);
   $pdf->Ln(5);
 
   $prevD = "";
   $prevT = "";
   $prev = "";

   $selTransR = $sql->execute($arP, array($selAddW['card_no']));
   if ($sql->num_rows($selTransR) == 0) {
      $date = '';
      $trans = '';
      $charges = '0.00';
      $payment = '0.00';

      $pdf->Cell(20,8,'',0,0,'L');
      $pdf->Cell(60,8,$date,0,0,'L');
      $pdf->Cell(20,8,$trans,0,0,'L');
      $pdf->Cell(25,8,'$ ' . $charges,0,0,'L');
      $pdf->Cell(25,8,'$ ' . $payment,0,0,'L');
      $pdf->Ln(5);
   } 

   $lineitem="";
   $count = 0;
   while ($selTransW = $sql->fetch_row($selTransR)) {
        if ($count > 14) continue;

         $date = $selTransW['tdate'];
         $trans = $selTransW['trans_num'];
         $charges = $selTransW['charges'];
         $payment =  $selTransW['payments'];
      
         //list($year, $month, $day) = split("-", $date);
         //$date = date('M-d-Y', mktime(0, 0, 0, $month, $day, $year));

	      $pdf->Cell(20,8,'',0,0,'L');
	      $pdf->Cell(60,8,$date,0,0,'L');
	      //$pdf->Cell(40,8,date('M-d-Y',$date),0,0,'L');
	      $pdf->Cell(20,8,$trans,0,0,'L');
	      $pdf->Cell(25,8,'$ ' . sprintf('%.2f',$charges),0,0,'L');
	      $pdf->Cell(25,8,'$ ' . sprintf('%.2f',$payment),0,0,'L');
		if ($pdf->GetY() > 265){
			addBackPage($pdf);
			$pdf->AddPage();
		} else {
		      $pdf->Ln(3.5);
        }

        $lineitem = '';
        if ($payment > 0) {
            $lineitem = 'Payment - Thank You';
        } else {
            $dt = strtotime($date);
            $args = array(
                date('Y-m-d 00:00:00', $dt),
                date('Y-m-d 23:59:59', $dt),
                $selAddW['card_no'],
                $trans,
            );
            $transR = $sql->execute($transP, $args);
            if ($sql->num_rows($transR) > 1) {
                $lineitem = '(multiple items)';
            } else {
                $transW = $sql->fetch_row($transR);
                $lineitem = $transW['description'];
            }
        }
        $pdf->SetFontSize(8);
        $pdf->Cell(30,8,'',0,0,'L');
        $pdf->Cell(60,8,$lineitem,0,0,'L');
        if ($pdf->GetY() > 265){
            addBackPage($pdf);
            $pdf->AddPage();
        } else {
            $pdf->Ln(3.5);
        }
        $pdf->SetFontSize(10);
        $count++;
   }
   $pdf->SetFontSize(12);
   addBackPage($pdf);
}

/*
$pdf->AddPage();

while($selAdd1W = $sql->fetch_row($selAddR)){
   $cell = $selAdd1W[0] . '  ' . $selAdd1W[1] . ', ' . $selAdd1W[2];
   $pdf->Cell(0,5,$cell,0,1);
}
*/
$pdf->Output('makeStatement.pdf','D');

function addBackPage($pdf){
	$pdf->AddPage();
	$pdf->SetFont('Arial','B',16);
	$pdf->SetTextColor(105);
	//$pdf->Cell(0,10,'',0,1,'C');
	$pdf->Cell(0,10,'IOU POLICY',0,1,'C');
	//$pdf->Ln(5);
	$pdf->SetFont('Arial','',12);
	$pdf->Cell(0,10,'OF WHOLE FOODS COMMUNITY CO-OP, INC.',0,1,'C');
	$pdf->SetFont('Arial','',10);
	$txt = "WFC members may charge purchases to a maximum of $20.00 payable within two (2) weeks from the date incurred. IOU's must be signed by the maker. IOU's may not, under any circumstances, be paid with Food Stamps or EBT card. WFC asks that its members only use  the charge system for emergencies." ;

	$pdf->MultiCell(0,5,$txt);
	$pdf->Ln(5);

	$txt = "-Members with an IOU account credit balance will receive a 
		 reminder of that balance on each purchase receipt.
	 -Members with an IOU debit balance will receive a reminder
	  of that balance on each purchase receipt.

	If WFC is not reimbursed by a member within sixty (60) days from the date of an overdue IOU for the amount of that person's membership may be terminated by the Board and any remaining stock, after reimbursement for all indebtedness owed to WFC, will be converted to non-voting Class B stock.

	If WFC is not reimbursed by a member within sixty (60) days from the date of a bounced check for the amount of that check plus the amount of any administrative fee, that person's membership may be terminated by the Board and any remaining stock, after reimbursement for all indebtedness owed to WFC, will converted to non-voting Class B stock.  

	IOU credit balances over sixty (60) days will be credited to the Member's non-voting Class B stock and the IOU account will be adjusted to zero.   Members may request the return of Class B stock in excess of the amount required by the By-Laws by submitting to the Board a Request to Terminate that excess stock.

	At the discretion of the General Manager, member business and non-profit agency accounts may have higher IOU limits and/or extended payment terms.
	";
	$pdf->MultiCell(0,5,$txt);
	$pdf->Ln(1);

	$txt="Special Orders";

	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(70,5,$txt,0,0);
	$pdf->Ln(5);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(15,20,'',0,0);

	$txt = "Special orders not picked up or paid for within thirty (30) days of the time items are received at WFC will be put out for sale or disposed of at management discretion.  Future special orders from members or from non-members who have not previously promptly paid for and/or picked up special orders, at management discretion, may require prepayment.";
	$pdf->MultiCell(0,5,$txt);
	$pdf->Ln(2);

	$txt="Newsletter Ads";

	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(70,5,$txt,0,0);
	$pdf->Ln(5);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(15,20,'',0,0);

	$txt = "Members may charge the cost of advertising their business in WFC's newsletter under the same IOU payment terms as noted above but on an IOU account separate from the member's IOU account for inventory purchases.   

	Members will be mailed an invoice within ten (10) days of the date of publication for the amount of the advertising charge.  Failure to pay the amount due is then subject to the provisions of this IOU policy.
	";

	$pdf->MultiCell(0,5,$txt);
	$pdf->Ln(0);

	$txt="NOTE";

	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(70,5,$txt,0,0);
	$pdf->Ln(5);
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(15,15,'',0,0);

	$txt = 
	"Memberships with IOUs and/or other credit problems in excess of sixty (60) days may be placed on inactive status by management pending Board action.  Purchases by inactive members will not be recorded and will not count toward eligibility for a patronage rebate.   Purchases by inactive members are not eligible for member discounts or member specials.
	Memberships inactivated or terminated due to credit problems will be eligible for reactivation subject to Board discretion with respect to access to member credit benefits.
	";

	$pdf->MultiCell(0,4,$txt);

	$pdf->SetFont('Arial','B',10);
	$txt = "
	Memberships inactivated or terminated due to credit problems will be eligible for reactivation subject to Board discretion with respect to access to member credit benefits.";
	$pdf->Cell(15,20,'',0,0);
	$pdf->MultiCell(0,5,$txt);

	$pdf->Ln(5);
	$pdf->SetTextColor(0);
	$pdf->SetFont('Arial','',10);
}

?>
