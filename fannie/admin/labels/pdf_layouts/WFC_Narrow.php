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
if (!class_exists('FpdfWithBarcode', false)) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}

class WFC_Narrow_PDF extends FpdfWithBarcode
{
}

function WFC_Narrow($data,$offset=0){

$pdf=new WFC_Narrow_PDF('P','mm','Letter'); //start new instance of PDF
$pdf->Open(); //open new PDF Document
$pdf->SetTopMargin(40);  //Set top margin of the page
$pdf->SetLeftMargin(4);  //Set left margin of the page
$pdf->SetRightMargin(0);  //Set the right margin of the page
$pdf->AddPage();  //Add a page

//Set increment counters for rows 
$i = 7;  //x location of barcode
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
    $i = 7;  //x location of barcode
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
      $i = 7;
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

   $words = preg_split('/[ ,-]+/',$desc);
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
   if (strlen($upc) <= 11)
    $pdf->UPC_A($i,$j,$upc,7,.25);  //generate barcode and place on label
   else
    $pdf->EAN13($i,$j,$upc,7,.25);  //generate barcode and place on label

   //increment counters    
   $i =$i+ 52.7;
   $k = $k + 52.7;
   $m = $m + 1;
   $p = $p + 52.7;
   $u = $u + 52.7;
}

$pdf->Output();  //Output PDF file to screen.

}

