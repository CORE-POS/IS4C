<?php

include('../../config.php');

include('../db.php');

define('FPDF_FONTPATH','font/');
require('../../src/fpdf/fpdf.php');
class PDF extends FPDF
{
   function EAN13($x,$y,$barcode,$h=16,$w=.35)
   {
        $this->Barcode($x,$y,$barcode,$h,$w,13);
   }

   function UPC_A($x,$y,$barcode,$h=16,$w=.35)
   {
      $this->Barcode($x,$y,$barcode,$h,$w,12);
    }

    function GetCheckDigit($barcode)
   {
       //Compute the check digit
      $sum=0;
      for($i=1;$i<=11;$i+=2)
        $sum+=3*(isset($barcode[$i])?$barcode[$i]:0);
      for($i=0;$i<=10;$i+=2)
        $sum+=(isset($barcode[$i])?$barcode[$i]:0);
      $r=$sum%10;
      if($r>0)
        $r=10-$r;
      return $r;
   }

   function TestCheckDigit($barcode)
   {
      //Test validity of check digit
      $sum=0;
      for($i=1;$i<=11;$i+=2)
        $sum+=3*$barcode{$i};
      for($i=0;$i<=10;$i+=2)
        $sum+=$barcode{$i};
      return ($sum+$barcode{12})%10==0;
   }

   function Barcode($x,$y,$barcode,$h,$w,$len)
   {
      //Padding
      $barcode=str_pad($barcode,$len-1,'0',STR_PAD_LEFT);
      if($len==12)
        $barcode='0'.$barcode;
      //Add or control the check digit
      if(strlen($barcode)==12)
        $barcode.=$this->GetCheckDigit($barcode);
      elseif(!$this->TestCheckDigit($barcode)){
        //$this->Error('This is an Incorrect check digit' . $barcode);
        //echo $x.$y.$barcode."\n";
      }
      //Convert digits to bars
      $codes=array(
        'A'=>array(
            '0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
            '5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011'),
        'B'=>array(
            '0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
            '5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111'),
        'C'=>array(
            '0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
            '5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100')
        );
      $parities=array(
        '0'=>array('A','A','A','A','A','A'),
        '1'=>array('A','A','B','A','B','B'),
        '2'=>array('A','A','B','B','A','B'),
        '3'=>array('A','A','B','B','B','A'),
        '4'=>array('A','B','A','A','B','B'),
        '5'=>array('A','B','B','A','A','B'),
        '6'=>array('A','B','B','B','A','A'),
        '7'=>array('A','B','A','B','A','B'),
        '8'=>array('A','B','A','B','B','A'),
        '9'=>array('A','B','B','A','B','A')
        );
      $code='101';
      $p=$parities[$barcode{0}];
      for($i=1;$i<=6;$i++)
        $code.=$codes[$p[$i-1]][$barcode{$i}];
      $code.='01010';
      for($i=7;$i<=12;$i++)
        $code.=$codes['C'][$barcode{$i}];
      $code.='101';
      //Draw bars
      for($i=0;$i<strlen($code);$i++)
      {
        if ( ($i < 3) ||
             ($i >= 45 && $i <= 50) ||
             ($i > strlen($code)-4) ){
            if($code{$i}=='1')
                $this->Rect($x+$i*$w,$y,$w,$h+2,'F');
        }
        else {
            if($code{$i}=='1')
                $this->Rect($x+$i*$w,$y,$w,$h,'F');
        }
      }
      //Print text uder barcode
      $this->SetFont('Arial','',6);
      //$this->Text($x+12,$y+$h+2,substr($barcode,-$len));
      $this->Text($x+6,$y+$h+2,substr($barcode,0,6));
      $this->Text($x+24,$y+$h+2,substr($barcode,6,6));
    }
}

if (isset($_REQUEST['upcs'])){
    $upcs = $_REQUEST['upcs'];

    $pdf=new PDF(); //start new instance of PDF
    $pdf->Open(); //open new PDF Document
    $pdf->SetTopMargin(5);  //Set top margin of the page
    $pdf->SetLeftMargin(4);  //Set left margin of the page
    $pdf->SetRightMargin(0);  //Set the right margin of the page
    $pdf->AddPage();

    $count = 0;
    $prep = $sql->prepare("SELECT description FROM products WHERE upc='$upc'");
    foreach($upcs as $upc){
        if ($count == 13*5){
            $pdf->AddPage();
            $count = 0;
        }
        $x = $pdf->GetX();
        $y = $pdf->GetY();
        if ($count % 5 == 0){
            $x = 4;
            $row = $count/5;    
            $y = 5+($row*20);
            $pdf->SetXY($x,$y);
        }
        $pdf->SetFont('Arial','',8); //change font for price
        $res = $sql->execute($prep, $upc);
        $row = $sql->fetch_row($res);
        $desc = $row['description'];
        if (strlen($desc)>20)
            $desc = substr($desc,0,20)."\n".substr($desc,20);

        $pdf->Cell(40,20,'','TB',0,'C');
        $pdf->SetXY($x,$y);
        $pdf->MultiCell(40,3,$desc,0,'C');
        $pdf->UPC_A($x+2,$y+7,ltrim($upc,'0'),10,.38);

        $pdf->SetXY($x+40,$y);
        $count++;

    }    

    $pdf->Output();
    return;
}

$subQ = "select superID,super_name from superDeptNames
    where superID NOT IN (0,7)
    group by superID,super_name
    order by superID";
$subR = $sql->query($subQ);

$sub = isset($_REQUEST['sub'])?$_REQUEST['sub']:"";

echo "<form action=UNFIsales.php method=get>
<b>Sales from yesterday</b> 
<select name=sub>";
while($subW = $sql->fetch_row($subR)){
    if ($sub == $subW[0])
        printf("<option selected value=%d>%s</option>",$subW[0],$subW[1]);
    else
        printf("<option value=%d>%s</option>",$subW[0],$subW[1]);
}
echo "</select>
<input type=submit value=Submit />
</form>
<hr />";

if ($sub != ""){
    echo "<form action=UNFIsales.php method=post>
    <table cellspacing=0 cellpadding=4 border=1>
    <tr><th>UPC</th><th>Desc</th><th>Qty</th><th>Include</th></tr>";

    $itemQ = $sql->prepare("SELECT d.upc,p.description,sum(d.quantity)
        FROM is4c_trans.dlog_15 as d INNER JOIN products AS p
        ON d.upc = p.upc INNER JOIN vendorItems AS u
        ON p.upc = u.upc AND u.vendorID=1 LEFT JOIN departments as t
        ON d.department = t.dept_no
        LEFT JOIN superdepts AS s ON s.dept_ID=t.dept_no
        WHERE s.superID = ? AND
        ".$sql->datediff($sql->now(),'tdate')." = 1
        AND trans_type='I' and trans_status <> 'M'
        GROUP BY d.upc,p.description
        ORDER BY SUM(d.quantity) DESC");
    $itemsR = $sql->execute($itemQ, array($sub));
    while($itemsW = $sql->fetch_row($itemsR)){
        printf("<tr><td>%s</td><td>%s</td><td>%.2f</td>
            <td><input type=checkbox value=%s name=upcs[] /></td></tr>",
            $itemsW[0],$itemsW[1],$itemsW[2],$itemsW[0]);
    }
    echo "</table>
    <input type=submit value=\"Generate Order Barcodes\" />
    </form>";
}

