<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!defined('FPDF_FONTPATH')) {
  define('FPDF_FONTPATH','font/');
}
require($FANNIE_ROOT.'src/fpdf/fpdf.php');

/****Credit for the majority of what is below for barcode generation
 has to go to Olivier for posting the script on the FPDF.org scripts
 webpage.****/

class WFC_Narrow_PDF extends FPDF
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
        if($code{$i}=='1')
            $this->Rect($x+$i*$w,$y,$w,$h,'F');
      }
      //Print text uder barcode
      $this->SetFont('Arial','',9);
      $this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
    }
}

function WFC_Narrow($data,$offset=0){

$pdf=new WFC_Narrow_PDF('P','mm','Letter'); //start new instance of PDF
$pdf->Open(); //open new PDF Document
$pdf->SetTopMargin(40);  //Set top margin of the page
$pdf->SetLeftMargin(4);  //Set left margin of the page
$pdf->SetRightMargin(0);  //Set the right margin of the page
$pdf->AddPage();  //Add a page

//Set increment counters for rows 
$i = 6;  //x location of barcode
$j = 31; //y locaton of barcode
$l = 30; //y location of size and price on label
$k = 8; //x location of date and price on label
$m = 0;  //number of labels created
$n = 18; //y location of description for label
$r = 24; //y location of date for label
$p = 6;  //x location fields on label
$t = 28; //y location of SKU and vendor info
$u = 24; //x locaiton of vendor info for label
$down = 31.0;

//cycle through result array of query
foreach($data as $row){
   //If $m == 32 add a new page and reset all counters..
   if($m == 32){
      $pdf->AddPage();
    $i = 6;  //x location of barcode
    $j = 31; //y locaton of barcode
    $l = 30; //y location of size and price on label
    $k = 8; //x location of date and price on label
    $m = 0;  //number of labels created
    $n = 18; //y location of description for label
    $r = 24; //y location of date for label
    $p = 6;  //x location fields on label
    $t = 28; //y location of SKU and vendor info
    $u = 24; //x locaiton of vendor info for label
   }

   //If $i > 175, start a new line of labels
   if($i > 175){
      $i = 6;
      $j = $j + $down;
      $k = 8;
      $l = $l + $down;
      $n = $n + $down;
      $r = $r + $down;
      $p = 6;
      $u = 24;
      $t = $t + $down;
   }
   $price = $row['normal_price'];
   $desc = strtoupper(substr($row['description'],0,27));
   $brand = ucwords(strtolower(substr($row['brand'],0,13)));
   $pak = $row['units'];
   $size = $row['units'] . "-" . $row['size'];
   $sku = $row['sku'];
   $upc = ltrim($row['upc'],0);
   $check = $pdf->GetCheckDigit($upc);
   $tagdate = date('m/d/y');
   $vendor = substr($row['vendor'],0,7);
   
   //Start laying out a label 
   $pdf->SetFont('Arial','',8);  //Set the font 

   $words = split("[ ,-]",$desc);
   $limit = 13;
   $lineheight = 0;
   $curStr = "";
   $length = 0;
   $lines = 0;
   foreach ($words as $w) {
    if ($length + strlen($w) <= $limit) {
        $curStr .= $w . ' ';
        $length += strlen($w) + 1;
    } else {
        $lines++;
        if ($lines >= 2) {
            break;
        }
        $curStr = trim($curStr) . "\n" . $w . ' ';
        $length = strlen($w)+1;
    }
   }
   $pdf->SetXY($p, $n-3);
   $pdf->MultiCell(100, 3, $curStr);
   
   //$pdf->TEXT($p,$n,$desc);   //Add description to label

   $pdf->TEXT($p,$r,$tagdate);  //Add date to lable
   $pdf->TEXT($p+12,$r,$size);  //Add size to label
   $pdf->SetFont('Arial','B',18); //change font for price
   $pdf->TEXT($k,$l,$price);  //add price

   $newUPC = $upc . $check; //add check digit to upc
   $pdf->UPC_A($i,$j,$upc,7,.23);  //generate barcode and place on label

   //increment counters    
   $i =$i+ 52.7;
   $k = $k + 52.7;
   $m = $m + 1;
   $p = $p + 52.7;
   $u = $u + 52.7;
}

$pdf->Output();  //Output PDF file to screen.

}

?>
