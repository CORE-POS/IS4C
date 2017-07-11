<?php
include('../../config.php');

if (!isset($_GET['upc']) && !isset($_GET['upcs'])){
?>
<form action=barcode.php method=get>
Get barcodes for UPC: <input type=text name=upc />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit value="Generate PDF" />
</form>
<hr />
<form action=barcode.php method=get>
Get barcodes for several UPCs 
<textarea rows="10" cols="15" name="upcs"></textarea>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit value="Generate PDF" />
</form>
<?php
return;
}

define('FPDF_FONTPATH','font/');
require($FANNIE_ROOT.'src/fpdf/fpdf.php');

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
    elseif(!$this->TestCheckDigit($barcode)) {
        $this->Error('This is an Incorrect check digit' . $barcode);
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
        if($code{$i}=='1')
            $this->Rect($x+$i*$w,$y,$w,$h,'F');
    }
    //Print text uder barcode
    $this->SetFont('Arial','',9);
    $this->Text($x+6,$y+$h+11/$this->k,substr($barcode,-$len));
}
}

//echo $query;

$pdf=new PDF();
$pdf->Open();
$pdf->SetTopMargin(20);
$pdf->SetLeftMargin(4);
$pdf->SetRightMargin(0);
$pdf->AddPage();
$i = 9;
$j = 33;
$l = 28;
$k = 25;
$m = 0;
$n = 20;
$r = 24;
$p = 5;
$t = 32;
$u = 20;
$down = 31;

$upcs = isset($_GET['upcs']) ? explode("\n", $_GET['upcs']) : array();
while($m < 32){
   if($m == 32){
      $pdf->AddPage();
      $i = 9;
      $j = 33;
      $l = 28;
      $k = 25;
      $m = 0;
      $n = 20;
      $p = 5;
      $q = 24;
      $r = 24;
      $t = 32;
      $u = 20;
   }
   if($i > 175){
      $i = 9;
      $j = $j + $down;
      $k = 25;
      $l = $l + $down;
      $n = $n + $down;
      $r = $r + $down;
      $p = 5;
      $u = 20;
      $t = $t + $down;
   }
   $upc = "49999900009";
   if (isset($_GET['upc'])) $upc = $_GET['upc'];
   if (isset($upcs[$m])) $upc = $upcs[$m];
 
   $check = $pdf->GetCheckDigit($upc);
   
   $pdf->SetFont('Arial','',8);

   $newUPC = $upc . $check;
   //echo $newUPC . "<br>";
      //echo "<br>" . $row['upc'] . "check: " . $check . "new: " . $newUPC;;
     $pdf->UPC_A($i,$j,$upc,7);
   
   $i =$i+ 53;
   $k = $k + 53;
   $m = $m + 1;
   $p = $p + 53;
   $u = $u + 53;
}

$pdf->Output();

