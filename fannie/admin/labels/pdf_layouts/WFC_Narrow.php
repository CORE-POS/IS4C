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

function WFC_Narrow($data,$offset=0)
{
    $pdf=new WFC_Narrow_PDF('P','mm','Letter'); //start new instance of PDF
    $pdf->Open(); //open new PDF Document
    $pdf->SetTopMargin(40);  //Set top margin of the page
    $pdf->SetLeftMargin(4);  //Set left margin of the page
    $pdf->SetRightMargin(0);  //Set the right margin of the page
    $pdf->SetAutoPageBreak(False); // manage page breaks yourself
    $pdf->AddPage();  //Add a page

    //Set increment counters for rows
    $upcX = 7;  //x location of barcode
    $upcY = 15; //y locaton of barcode
    $priceY = 29; //y location of size and price on label
    $priceX = 8; //x location of date and price on label
    $count = 0;  //number of labels created
    $baseY = 31; // baseline Y location of label
    $baseX = 6;  // baseline X location of label
    $down = 31.0;

    if($offset>0) {
        //increment values in respect to Y
        $delta = 18.0;
        $baseY = $baseY+$delta;
        $upcY = $upcY+$delta;
        $priceY = $priceY+$delta;

        $count = $offset;
    }

    //cycle through result array of query
    foreach($data as $row){
        //If $count == 32 add a new page and reset all counters..
        if($count == 32){
            $pdf->AddPage();
            $upcX = 7;  //x location of barcode
            $upcY = 15; //y locaton of barcode
            $priceY = 29; //y location of size and price on label
            $priceX = 8; //x location of date and price on label
            $count = 0;  //number of labels created
            $baseY = 31; // baseline Y location of label
            $baseX = 6;  // baseline X location of label
        }

        //If $upcX > 175, start a new line of labels
        if($upcX > 175){
          $upcX = 7;
          $upcY = $upcY + $down;
          $priceX = 8;
          $priceY = $priceY + $down;
          $baseY = $baseY + $down;
          $baseX = 6;
        }
        $price = $row['normal_price'];
        $desc = strtoupper(substr($row['description'],0,27));
        $brand = ucwords(strtolower(substr($row['brand'],0,30)));
        $pak = $row['units'];
        $size = $row['units'] . "-" . $row['size'];
        $sku = $row['sku'];
        $upc = ltrim($row['upc'],0);
        $check = $pdf->GetCheckDigit($upc);
        $tagdate = date('m/d/y');
        $vendor = substr($row['vendor'],0,7);

        //Start laying out a label
        $pdf->SetFont('Arial','',8);  //Set the font
        if (strlen($upc) <= 11)
            $pdf->UPC_A($upcX,$upcY,$upc,4,.25);  //generate barcode and place on label
        else
            $pdf->EAN13($upcX,$upcY,$upc,4,.25);  //generate barcode and place on label

        $pdf->SetFont('Arial','B',18); //change font for price
        $pdf->TEXT($priceX,$priceY,$price);  //add price

        $words = preg_split('/[\s,-]+/',$desc);
        $limit = 13;
        $lineheight = 0;
        $curStr = "";
        $length = 0;
        $lines = 0;
        foreach ($words as $word) {
            if ($length + strlen($word) <= $limit) {
                $curStr .= $word . ' ';
                $length += strlen($word) + 1;
            } else {
                $lines++;
                if ($lines >= 2) {
                    break;
                }
                $curStr = trim($curStr) . "\n" . $word . ' ';
                $length = strlen($word)+1;
            }
        }
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY($baseX, $baseY);
        $pdf->MultiCell(100, 3, $curStr);
        $pdf->SetX($baseX);
        $pdf->Cell(0, 3, $tagdate);
        $pdf->SetX($baseX+12);
        $pdf->Cell(0, 3, $size, 0, 1);

        $words = preg_split('/[ ,-]+/',$brand);
        $curStr = "";
        $curCnt = 0;
        $length = 0;
        foreach ($words as $word) {
           if ($curCnt == 0) {
               $curStr .= $word . " ";
               $length += strlen($word)+1;
           } elseif ($curCnt == 1 && ($length + strlen($word + 1)) < 17) {
               $curStr .= $word . " ";
               $length += strlen($word)+1;
           } elseif ($curCnt > 1 && ($length + 1) < 17) {
               $chars = str_split($word);
               foreach ($chars as $char) {
                   $curStr .= strtoupper($char);
                   $length += 2;
                   break;
                }
           }
           $curCnt++;
        }
        $pdf->SetX($baseX);
        $pdf->Cell(0, 3, $curStr);

        //increment counters
        $upcX = $upcX + 52.7;
        $priceX = $priceX + 52.7;
        $count = $count + 1;
        $baseX = $baseX + 52.7;
    }

    $pdf->Output();  //Output PDF file to screen.
}

