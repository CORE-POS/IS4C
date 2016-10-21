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

$selAddQ = $sql->prepare("SELECT m.card_no, a.memName,m.street, '',
           m.City, m.State, m.zip,
       a.TwoMonthBalance,a.LastMonthCharges,
       a.LastMonthPayments,a.LastMonthBalance
           FROM {$TRANS}AR_EOM_Summary a LEFT JOIN
           meminfo m ON a.cardno = m.card_no
       LEFT JOIN custdata as c on c.CardNo=a.cardno and c.personNum=1
       WHERE c.type not in ('TERM') and
       c.memtype <> 9 and a.twoMonthBalance > 1
       and c.Balance <> 0
       and c.memType <> 2
       $cardsClause
       and a.lastMonthPayments < a.twoMonthBalance
           ORDER BY a.cardno");
$selAddR = $sql->execute($selAddQ, $args);

   $selTransQ = $sql->prepare("SELECT card_no, CASE WHEN trans_subtype='MI' THEN -total ELSE 0 END as charges,
    CASE WHEN department=990 then total ELSE 0 END as payments, tdate, trans_num,
    '','',register_no,emp_no,trans_no FROM {$TRANS}dlog_90_view as m WHERE 1=1 $cardsClause
    AND (department=990 OR trans_subtype='MI')
    ORDER BY card_no, tdate, trans_num");
$selTransR = $sql->execute($selTransQ, $args);
$selTransN = $sql->num_rows($selTransR);

$arRows = array();
$trans_clause = "";
$t_args = array();
while($w = $sql->fetch_row($selTransR)){
    if (!isset($arRows[$w['card_no']]))
        $arRows[$w['card_no']] = array();
    $arRows[$w['card_no']][] = $w;
    $date = explode(' ',$w['tdate']);
    $date_id = date('Ymd', strtotime($date[0]));
    $t_args[] = $date_id;
    $t_args[] = $w['register_no'];
    $t_args[] = $w['emp_no'];
    $t_args[] = $w['trans_no'];
    $trans_clause .= " (date_id=? AND register_no=? AND emp_no=? AND trans_no=?) OR ";
}
$trans_clause = substr($trans_clause,0,strlen($trans_clause)-3);
$q = $sql->prepare("SELECT card_no,description,department,emp_no,register_no,trans_no 
    FROM {$TRANS}transarchive
    WHERE trans_type IN ('I','D') and emp_no <> 9999
    AND register_no <> 99 AND trans_status <> 'X'
    AND upc <> 'DISCOUNT'
    AND ($trans_clause)");
$details = array();
if ($trans_clause != '') {
    $r = $sql->execute($q, $t_args);
    while($w = $sql->fetch_row($r)){
        $tn = $w['emp_no']."-".$w['register_no']."-".$w['trans_no'];
        if (!isset($details[$w['card_no']]))
            $details[$w['card_no']] = array();
        if (!isset($details[$w['card_no']][$tn]))
            $details[$w['card_no']][$tn] = array();
        $details[$w['card_no']][$tn][] = $w['description'];
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

$stateDate = date("d F, Y",mktime(0,0,0,$month,0,$year));

$pdf = new FPDF();

//Meat of the statement
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
 
   $txt = "If payment has been made or sent, please ignore this statement. If you have any questions about this statement or would like to make arrangements to pay your balance, please write or call the Finance Department at the above address or (218) 728-0884. See reverse side for WFC IOU Policies. ";
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
   $pdf->Ln(20);
 
   $date2Month = date('F',strtotime($twoMonth));
   $pdf->Cell(0,8,"Recent 90 Day History",0,1,'C');
   $pdf->SetFillColor(200);
   $pdf->Cell(20,8,'',0,0,'L');
   $pdf->Cell(60,8,'Date',0,0,'L',1);
   $pdf->Cell(20,8,'Receipt',0,0,'L',1);
   $pdf->Cell(25,8,'Charges',0,0,'L',1);
   $pdf->Cell(25,8,'Payments',0,1,'L',1);
 
   //$selTransQ = "SELECT * FROM ar_history WHERE datediff(mm,now(),tdate) = -1
   //              AND card_no = $selAddW[0]";
    /*
   $selTransQ = "SELECT card_no, charges, payments, 
        convert(varchar(50),date,101), trans_num,description,dept_name  
        FROM AR_statementHistory WHERE card_no = $selAddW[0] 
        order by date,trans_num,description,dept_name";
   
   $selTransR = $sql->query($selTransQ);
   $selTransN = $sql->num_rows($selTransR);
    */

    /*
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
      $pdf->Cell(20,8,$trans,0,0,'L');
      $pdf->Cell(25,8,'$ ' . $charges,0,0,'L');
      $pdf->Cell(25,8,'$ ' . $payment,0,0,'L');
      $pdf->Ln(5);
   } 
    */

   $lineitem="";
   //while($selTransW = $sql->fetch_row($selTransR)){
   foreach($arRows[$selAddW[0]] as $arRow){
    
    $date = $arRow['tdate'];
    $trans = $arRow['trans_num'];
    $charges = $arRow['charges'];
    $payment =  $arRow['payments'];

    $detail = $details[$selAddW[0]][$trans];

    if (strstr($detail[0],"Gazette Ad"))
        $gazette = True;
    $lineitem = (count($detail)==1) ? $detail[0] : '(multiple items)';
    if ($lineitem == "ARPAYMEN") $lineitem = "Payment Received - Thank You";

    $pdf->Cell(20,8,'',0,0,'L');
    $pdf->Cell(60,8,$date,0,0,'L');
    //$pdf->Cell(40,8,date('M-d-Y',$date),0,0,'L');
    $pdf->Cell(20,8,$trans,0,0,'L');
    $pdf->Cell(25,8,'$ ' . sprintf('%.2f',$charges),0,0,'L');
    $pdf->Cell(25,8,'$ ' . sprintf('%.2f',$payment),0,0,'L');
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
    /*
      if($selTransN != 0){
     $date = $selTransW[3];
         $trans = $selTransW[4];
         $charges = $selTransW[1];
         $payment =  $selTransW[2];
      
         //list($year, $month, $day) = split("-", $date);
         //$date = date('M-d-Y', mktime(0, 0, 0, $month, $day, $year));
     }
      if ($date != $prevD || $trans != $prevT){
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
        $lineitem = "";
        $prev = "";

          $pdf->Cell(20,8,'',0,0,'L');
          $pdf->Cell(60,8,$date,0,0,'L');
          //$pdf->Cell(40,8,date('M-d-Y',$date),0,0,'L');
          $pdf->Cell(20,8,$trans,0,0,'L');
          $pdf->Cell(25,8,'$ ' . sprintf('%.2f',$charges),0,0,'L');
          $pdf->Cell(25,8,'$ ' . sprintf('%.2f',$payment),0,0,'L');
        if ($pdf->GetY() > 265){
            addBackPage($pdf);
            $pdf->AddPage();
        }
        else
              $pdf->Ln(5);

      }
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
   }
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

