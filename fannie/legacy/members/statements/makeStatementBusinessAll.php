<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');
$TRANS = $FANNIE_TRANS_DB. ($FANNIE_SERVER_DBMS == "MSSQL" ? 'dbo.' : '.');

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

$selAddQ = $sql->prepare("SELECT m.card_no, c.LastName,m.street, '',
           m.city, m.state, m.zip,n.balance,
	   c.FirstName
           FROM 
           meminfo m 
	   LEFT JOIN custdata as c on c.CardNo=m.card_no and c.personNum=1
	   LEFT JOIN {$TRANS}ar_live_balance as n ON m.card_no=n.card_no
	   WHERE c.Type not in ('TERM') and
	   c.memType IN (2,0)
	   and n.balance > 0
	   $cardsClause 
           ORDER BY m.card_no");
$selAddR = $sql->execute($selAddQ, $args);

$selTransQ = $sql->prepare("SELECT card_no, CASE WHEN trans_subtype='MI' THEN -total ELSE 0 END as charges,
	CASE WHEN department=990 then total ELSE 0 END as payments, tdate, trans_num,
	'','',register_no,emp_no,trans_no FROM {$TRANS}dlog_90_view as m WHERE 1=1 $cardsClause
	AND (department=990 OR trans_subtype='MI')
	ORDER BY card_no, tdate, trans_num");
$selTransR = $sql->execute($selTransQ, $args);
$selTransN = $sql->num_rows($selTransR);

$arRows = array();
while($w = $sql->fetch_row($selTransR)){
	if (!isset($arRows[$w['card_no']]))
		$arRows[$w['card_no']] = array();
	$arRows[$w['card_no']][] = $w;
	$date = explode(' ',$w['tdate']);
    $date_id = date('Ymd', strtotime($date[0]));
}
$transP = $sql->prepare('
    SELECT card_no,
        description,
        department,
        emp_no,
        register_no,
        trans_no
    FROM ' . $TRANS . ' dlog_90_view
    WHERE tdate BETWEEN ? AND ?
        AND trans_num=?
        AND card_no=?
        AND trans_type IN (\'I\', \'D\')
    ');         
$details = array();
foreach ($arRows as $card_no => $trans) {
    foreach ($trans as $info) {
        $dt = strtotime($info['tdate']);
        $args = array(
            date('Y-m-d 00:00:00', $dt),
            date('Y-m-d 23:59:59', $dt),
            $info['trans_num'],
            $info['card_no'],
        );
        $r = $sql->execute($transP, $args);
        while($w = $sql->fetch_row($r)){
            $tn = $w['emp_no']."-".$w['register_no']."-".$w['trans_no'];
            if (!isset($details[$w['card_no']]))
                $details[$w['card_no']] = array();
            if (!isset($details[$w['card_no']][$tn]))
                $details[$w['card_no']][$tn] = array();
            $details[$w['card_no']][$tn][] = $w['description'];
        }
    }
}

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

$stateDate = date("d F, Y",mktime(0,0,0,date('n'),0,date('Y')));

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

   $pdf->Cell(10,5,sprintf("Invoice #: %s-%s",$selAddW[0],date("ymd")),0,1,'L');
   $pdf->Cell(10,5,$stateDate,0);
   $pdf->Ln(8);


   //Member address
   $pdf->SetX(15);
   $name = $selAddW['LastName'];
   if (!empty($selAddW['FirstName'])) 
      $name = $selAddW['FirstName'].' '.$name;
   $pdf->Cell(50,10,trim($selAddW[0]).' '.trim($name),0);
   $pdf->Ln(5);
   $pdf->SetX(15);

   if (strstr($selAddW[2],"\n") === False){
	   $pdf->Cell(80,10,$selAddW[2],0);
	   $pdf->Ln(5);
	   $pdf->SetX(15);
   }
   else {
	$pts = explode("\n",$selAddW[2]);
	$pdf->Cell(80,10,$pts[0],0);
	$pdf->Ln(5);
	   $pdf->SetX(15);
	$pdf->Cell(80,10,$pts[1],0);
	$pdf->Ln(5);
	   $pdf->SetX(15);
   }
   $pdf->Cell(90,10,$selAddW[4] . ', ' . $selAddW[5] . '   ' . $selAddW[6],0);
   $pdf->Ln(25);
 
   $txt = "If payment has been made or sent, please ignore this invoice. If you have any questions about this invoice or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884.";
   $pdf->MultiCell(0,5,$txt);
   $pdf->Ln(10);

   $startYear = date('Y');
   $lastMonth = date("n")-1;
   $lastMonth = $lastMonth . '/01/'. $startYear;
   $twoMonth = date("n")-2;
   $twoMonth = $twoMonth . '/01/'.$startYear;

/*
   $span = date("F Y");
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
   $pdf->Cell(20,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",0),0,0,'L');
   $pdf->Cell(35,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');
   $pdf->Ln(20);
*/

   $priorQ = $sql->prepare("SELECT sum(charges) - sum(payments) FROM is4c_trans.ar_history
		WHERE ".$sql->datediff('tdate',$sql->now())." < -90
		AND card_no = ?");
   $priorR = $sql->execute($priorQ, array($selAddW[0]));
   $priorW = $sql->fetch_row($priorR);
   $priorBalance = $priorW[0];

   $pdf->Cell(20,8,'');
   $pdf->SetFillColor(200);
   $pdf->SetFont('Arial','B','12');   
   $pdf->Cell(40,8,'Balance Forward',0,0,'L',1);
   $pdf->SetFont('Arial','','12');   
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$priorBalance),0,0,'L');
   $pdf->Ln(8);
 
   $date2Month = date('F',strtotime($twoMonth));
   $pdf->Cell(0,8,"90-Day Billing History",0,1,'C');
   $pdf->SetFillColor(200);
   $pdf->Cell(20,8,'',0,0,'L');
   $pdf->Cell(60,8,'Date',0,0,'L',1);
   $pdf->Cell(30,8,'Receipt',0,0,'L',1);
   $pdf->Cell(25,8,'',0,0,'L',1);
   $pdf->Cell(25,8,'Amount',0,1,'L',1);
 
   //$selTransQ = "SELECT * FROM ar_history WHERE datediff(mm,now(),tdate) = -1
   //              AND card_no = $selAddW[0]";

   $prevD = "";
   $prevT = "";
   $prev = "";
   if($selTransN == 0){
      $date = '';
      $trans = '';
      $charges = '0.00';
      $payment = '0.00';

      $pdf->Cell(20,8,'',0,0,'L');
      $pdf->Cell(60,8,$date,0,0,'L');
      $pdf->Cell(30,8,$trans,0,0,'L');
      $pdf->Cell(25,8,'',0,0,'L');
      if ($charges != 0)
	      $pdf->Cell(25,8,'$ ' . $charges,0,0,'L');
      elseif ($payments != 0)
	      $pdf->Cell(25,8,'($ ' . $payments.")",0,0,'L');
      $pdf->Ln(5);
   } 

   $gazette = False;
   $first = True;
   $isPayment = False;

   $lineitem="";
   //while($selTransW = $sql->fetch_row($selTransR)){
   foreach($arRows[$selAddW[0]] as $arRow){

	/*
	if ($selTransW[0] != $selAddW[0]){
		$sql->data_seek($selTransR,$rowNum);
		break;
	}
	else $rowNum++;
	*/


	$date = $arRow['tdate'];
	$trans = $arRow['trans_num'];
	$charges = $arRow['charges'];
	$payment =  $arRow['payments'];

	$detail = $details[$selAddW[0]][$trans];

	if (strstr($detail[0],"Gazette Ad"))
		$gazette = True;
	$lineitem = (count($detail)==1) ? $detail[0] : '(multiple items)';
	if ($lineitem == "ARPAYMEN") $lineitem = "Payment Received - Thank You";
    foreach ($detail as $line) {
        if ($line == 'ARPAYMEN') {
            $lineitem = 'Payment Received - Thank You';
        }
    }

      
	$pdf->Cell(20,8,'',0,0,'L');
	$pdf->Cell(60,8,$date,0,0,'L');
	//$pdf->Cell(40,8,date('M-d-Y',$date),0,0,'L');
	$pdf->Cell(55,8,$trans,0,0,'L');
	if ($payment > $charges)
	      $pdf->Cell(25,8,'$ ' . sprintf('%.2f',$payment-$charges),0,0,'L');
	else
	      $pdf->Cell(25,8,'$ ' . sprintf('(%.2f)',abs($payment-$charges)),0,0,'L');
	if ($pdf->GetY() > 265){
		addBackPage($pdf);
		$pdf->AddPage();
	}
	else
	      $pdf->Ln(5);
	if (!empty($lineitem)){
		$pdf->SetFontSize(10);
		$pdf->Cell(30,8,'',0,0,'L');
		$pdf->Cell(60,8,$lineitem,0,0,'L');
		if ($pdf->GetY() > 265){
			addBackPage($pdf);
			$pdf->AddPage();
		}
		else
			$pdf->Ln(5);
		$pdf->SetFontSize(12);
	}

   }
	/*
      if ($selTransW[5] != "" && $selTransW[5] != $prev){
        $lineitem = (empty($lineitem))?$selTransW[5]:'(Multiple items)';
        $prev = $selTransW[5];
      }
      elseif ($selTransW[6] != "" && $selTransW[6] != $prev){
        $lineitem = (empty($lineitem))?$selTransW[6]:'(Multiple items)';
        $prev = $selTransW[6];
      }
      $prevD = $date;
      $prevT = $trans;
	*/
   //}
	/*
	if (!empty($lineitem)){
		$pdf->SetFontSize(10);
		$pdf->Cell(30,8,'',0,0,'L');
		$pdf->Cell(60,8,$lineitem,0,0,'L');
		if ($pdf->GetY() > 265){
			addBackPage($pdf);
			$pdf->AddPage();
		}
		else
			$pdf->Ln(5);
		$pdf->SetFontSize(12);
	}
	*/

   $pdf->Ln(15);
   $pdf->Cell(20,8,'');
   $pdf->SetFillColor(200);
   $pdf->SetFont('Arial','B','14');   
   $pdf->Cell(35,8,'Amount Due',0,0,'L',1);
   $pdf->SetFont('Arial','','14');   
   $pdf->Cell(25,8,'$ ' . sprintf("%.2f",$selAddW[7]),0,0,'L');

   if ($gazette){
	$pdf->Image($FANNIE_ROOT.'legacy/images/WFCLogoCThru1.jpg',75,214,50,25);

	$pdf->SetY(205);
	$pdf->Cell(0,8,'','B',1);
	$pdf->Ln(5);
	
	$pdf->Cell(30,5,'Whole Foods Co-op');
	$pdf->Cell(115,5,'');
	$pdf->Cell(20,5,'Invoice Date:',0,0,'R');
	$pdf->Cell(20,5,date("m/d/Y"),0,1,'L');
	$pdf->Cell(30,5,'610 East 4th Street');
	$pdf->Cell(115,5,'');
	$pdf->Cell(20,5,'Customer Number:',0,0,'R');
	$pdf->Cell(20,5,$selAddW[0],0,1,'L');
	$pdf->Cell(30,5,'Duluth, MN 55805');
	$pdf->Cell(115,5,'');
	$pdf->Cell(20,5,'Invoice Total:',0,0,'R');
	$pdf->Cell(20,5,$selAddW[7],0,1,'L');

	$pdf->Ln(5);
	$pdf->Cell(10,10,trim($selAddW[0]),0);
	$pdf->Ln(5);
	$pdf->Cell(50,10,trim($selAddW[1]),0);
	$pdf->Ln(5);
	$pdf->Cell(80,10,$selAddW[2],0);
	$pdf->Ln(5);
	if($selAddW[3]!= ''){  //if there is an address2 add it
	$pdf->Cell(80,10,$selAddW[3],0);
	$pdf->Ln(5);
	}
	$pdf->Cell(90,10,$selAddW[4] . ', ' . $selAddW[5] . '   ' . $selAddW[6],0);

	$pdf->SetXY(80,240);
	$pdf->SetFontSize(10);
	$pdf->MultiCell(110,6,"( ) Please continue this ad in the next issue.
( ) I would like to make some changes to my ad for the next issue.
( ) I do not wish to continue an ad in the next issue.
( ) I will contact you at a later date with my advertising decision.");
	$pdf->Ln(3);
	
	$pdf->SetFontSize(12);
	$pdf->Cell(0,8,'Please Return This Portion With Your Payment',0,0,'C');

   }

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
	return;
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
