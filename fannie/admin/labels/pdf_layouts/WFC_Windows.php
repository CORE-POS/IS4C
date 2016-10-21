<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}

class WFC_Windows_PDF extends FpdfWithBarcode
{
}

function WFC_Windows($data,$offset=0){

$pdf=new WFC_Windows_PDF('P','mm','Letter'); //start new instance of PDF
$pdf->Open(); //open new PDF Document
$pdf->SetTopMargin(40);  //Set top margin of the page
$pdf->SetLeftMargin(4);  //Set left margin of the page
$pdf->SetRightMargin(0);  //Set the right margin of the page
$pdf->AddPage();  //Add a page

//Set increment counters for rows 
$i = 9;  //x location of barcode
$j = 33; //y locaton of barcode
$l = 28; //y location of size and price on label
$k = 25; //x location of date and price on label
$m = 0;  //number of labels created
$n = 20; //y location of description for label
$r = 24; //y location of brand name for label
$p = 5;  //x location fields on label
$t = 32; //y location of SKU and vendor info
$u = 20; //x locaiton of vendor info for label
$down = 30.5;

    // extra up-shift to print right 
    // out of acrobat
    $diff = 3;
    $j -= $diff;
    $l -= $diff;
    $n -= $diff;
    $r -= $diff;
    $t -= $diff;

for ($ocount=0;$ocount<$offset;$ocount++){
   //If $i > 175, start a new line of labels
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
   //increment counters    
   $i =$i+ 53;
   $k = $k + 53;
   $m = $m + 1;
   $p = $p + 53;
   $u = $u + 53;
}

//cycle through result array of query
foreach($data as $row){
   //If $m == 32 add a new page and reset all counters..
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
    // same deal with the up shift
    $diff = 3;
    $j -= $diff;
    $l -= $diff;
    $n -= $diff;
    $r -= $diff;
    $t -= $diff;
   }

   //If $i > 175, start a new line of labels
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
   $price = $row['normal_price'];
   $desc = strtoupper(substr($row['description'],0,27));
   $brand = ucwords(strtolower(substr($row['brand'],0,13)));
   $pak = $row['units'];
   $size = $row['units'] . "-" . $row['size'];
   $sku = $row['sku'];
   $ppu = $row['pricePerUnit'];
   $upc = ltrim($row['upc'],0);
   $check = $pdf->GetCheckDigit($upc);
   $tagdate = date('m/d/y');
   $vendor = substr($row['vendor'],0,7);
   
   //Start laying out a label 
   $pdf->SetFont('Arial','',8);  //Set the font 
   $pdf->TEXT($p,$n,$desc);   //Add description to label
   $pdf->TEXT($p,$r,$brand);  //Add brand name to label
   $pdf->TEXT($p,$l,$size);  //Add size to label
   $pdf->SetXY($k+7,$t-3);
   $pdf->Cell(15,4,$ppu,0,0,'R'); // ppu right-aligned
   //$pdf->TEXT($k+7,$t,$ppu);
   $pdf->TEXT($i+24,$j+11,$tagdate);
   $pdf->SetFont('Arial','',10);  //change font size
   $pdf->TEXT($p,$t,$sku);  //add UNFI SKU
   $pdf->TEXT($u-2,$t,$vendor);  //add vendor 
   $pdf->SetFont('Arial','B',24); //change font for price
   $pdf->TEXT($k,$l,$price);  //add price

   $newUPC = $upc . $check; //add check digit to upc
   if (strlen($upc) <= 11)
    $pdf->UPC_A($i,$j,$upc,7);  //generate barcode and place on label
   else
    $pdf->EAN13($i,$j,$upc,7);  //generate barcode and place on label

   //increment counters    
   $i =$i+ 53;
   $k = $k + 53;
   $m = $m + 1;
   $p = $p + 53;
   $u = $u + 53;
}

$pdf->Output();  //Output PDF file to screen.

}

