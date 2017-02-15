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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}

class WFC_Standard_PDF extends FpdfWithBarcode
{
}

function WFC_Standard($data,$offset=0){

$pdf=new WFC_Standard_PDF('P','mm','Letter'); //start new instance of PDF
$pdf->Open(); //open new PDF Document
$pdf->SetTopMargin(40);  //Set top margin of the page
$pdf->SetLeftMargin(5);  //Set left margin of the page
$pdf->SetRightMargin(0);  //Set the right margin of the page
$pdf->AddPage();  //Add a page

//Set increment counters for rows 
$bc_x = 9;  //x location of barcode
$bc_y = 31; //y locaton of barcode
$sp_y = 26; //y location of size and price on label
$sp_x = 25; //x location of date and price on label
$count = 0;  //number of labels created
$desc_y = 18; //y location of description for label
$brand_y= 22; //y location of brand name for label
$left_x= 5;  //x location fields on label
$vi_y = 30; //y location of SKU and vendor info
$vi_x = 20; //x locaiton of vendor info for label
$down = 30.5;

for ($ocount=0;$ocount<$offset;$ocount++){
   //If $bc_x > 175, start a new line of labels
   if($bc_x > 175){
      $bc_x = 9;
      $bc_y = $bc_y + $down;
      $sp_x = 25;
      $sp_y = $sp_y + $down;
      $desc_y = $desc_y + $down;
      $brand_y= $brand_y+ $down;
      $left_x= 5;
      $vi_x = 20;
      $vi_y = $vi_y + $down;
   }
   //increment counters    
   $bc_x =$bc_x+ 53;
   $sp_x = $sp_x + 53;
   $count = $count + 1;
   $left_x= $left_x+ 53;
   $vi_x = $vi_x + 53;
}

//cycle through result array of query
foreach($data as $row){
   //If $count == 32 add a new page and reset all counters..
   if($count == 32){
      $pdf->AddPage();
      $bc_x = 9;
      $bc_y = 31;
      $sp_y = 26;
      $sp_x = 25;
      $count = 0;
      $desc_y = 18;
      $brand_y= 22;
      $left_x= 5;  
      $vi_y = 30;
      $vi_x = 20;
   }

   //If $bc_x > 175, start a new line of labels
   if($bc_x > 175){
      $bc_x = 9;
      $bc_y = $bc_y + $down;
      $sp_x = 25;
      $sp_y = $sp_y + $down;
      $desc_y = $desc_y + $down;
      $brand_y= $brand_y+ $down;
      $left_x= 5;
      $vi_x = 20;
      $vi_y = $vi_y + $down;
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
   $pdf->TEXT($left_x,$desc_y,$desc);   //Add description to label
   $pdf->TEXT($left_x,$brand_y,$brand);  //Add brand name to label
   $pdf->TEXT($left_x,$sp_y,$size);  //Add size to label
   $pdf->SetXY($sp_x+7,$vi_y-3);
   $pdf->Cell(15,4,$ppu,0,0,'R'); // ppu right-aligned
   //$pdf->TEXT($sp_x+7,$vi_y,$ppu);
   $pdf->TEXT($bc_x+24,$bc_y+11,$tagdate);
   $pdf->SetFont('Arial','',10);  //change font size
   $pdf->TEXT($left_x,$vi_y,$sku);  //add UNFI SKU
   $pdf->TEXT($vi_x-2,$vi_y,$vendor);  //add vendor 
   $pdf->SetFont('Arial','B',24); //change font for price
   $pdf->TEXT($sp_x,$sp_y,$price);  //add price

   $newUPC = $upc . $check; //add check digit to upc
   if (strlen($upc) <= 11)
    $pdf->UPC_A($bc_x,$bc_y,$upc,7);  //generate barcode and place on label
   else
    $pdf->EAN13($bc_x,$bc_y,$upc,7);  //generate barcode and place on label

   //increment counters    
   $bc_x =$bc_x+ 53;
   $sp_x = $sp_x + 53;
   $count = $count + 1;
   $left_x= $left_x+ 53;
   $vi_x = $vi_x + 53;
}

$pdf->Output();  //Output PDF file to screen.

}

