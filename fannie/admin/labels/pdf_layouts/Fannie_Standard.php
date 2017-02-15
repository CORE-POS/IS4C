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

  class Fannie_Standard_PDF extends FpdfWithBarcode
  {
    function barcodeText($x, $y, $h, $barcode, $len)
    {
      $this->SetFont('Arial','',9);
      if (filter_input(INPUT_GET, 'narrow') !== null)
          $this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
      else
          $this->Text($x+6,$y+$h+11/$this->k,substr($barcode,-$len));
    }
  }
  
  /**------------------------------------------------------------
   *       End barcode creation class 
   *-------------------------------------------------------------*/
  
  
  /**
   * begin to create PDF file using fpdf functions
   */

  function Fannie_Standard($data,$offset=0){
    $hspace = 0.79375;
    $h = 29.36875;
    $top = 12.7 + 2.5;
    $left = 4.85 + 1.25;
    $space = 1.190625 * 2;
  
    $pdf=new Fannie_Standard_PDF('P', 'mm', 'Letter');
    $pdf->SetMargins($left ,$top + $hspace);
    $pdf->SetAutoPageBreak('off',0);
    $pdf->AddPage('P');
    $pdf->SetFont('Arial','',10);
  
    /**
    * set up location variable starts
    */

    $barLeft = $left + 4;
    $descTop = $top + $hspace;
    $barTop = $descTop + 16;
    $priceTop = $descTop + 4;
    $labelCount = 0;
    $brandTop = $descTop + 4;
    $sizeTop = $descTop + 8;
    $genLeft = $left;
    $skuTop = $descTop + 12;
    $vendLeft = $left + 13;
    $down = 30.95625;
    $LeftShift = 51.990625;
    $w = 49.609375;
    $priceLeft = ($w / 2) + ($space);
    // $priceLeft = 24.85
    /**
       * increment through items in query
       */
       
    foreach($data as $row){
    /**
    * check to see if we have made 32 labels.
    * if we have start a new page....
    */

        if($labelCount == 32){
            $pdf->AddPage('P');
            $descTop = $top + $hspace;
            $barLeft = $left + 4;
            $barTop = $descTop + 16;
            $priceTop = $descTop + 4;
            $priceLeft = ($w / 2) + ($space);
            $labelCount = 0;
            $brandTop = $descTop + 4;
            $sizeTop = $descTop + 8;
            $genLeft = $left;
            $skuTop = $descTop + 12;
              $vendLeft = $left + 13;
        }
      
        /** 
        * check to see if we have reached the right most label
        * if we have reset all left hands back to initial values
        */
        if($barLeft > 175){
            $barLeft = $left + 4;
            $barTop = $barTop + $down;
            $priceLeft = ($w / 2) + ($space);
            $priceTop = $priceTop + $down;
            $descTop = $descTop + $down;
            $brandTop = $brandTop + $down;
            $sizeTop = $sizeTop + $down;
            $genLeft = $left;
            $vendLeft = $left + 13;
            $skuTop = $skuTop + $down;
        }
      
        /**
        * instantiate variables for printing on barcode from 
        * $testQ query result set
        */
        if ($row['scale'] == 0) {$price = $row['normal_price'];}
        elseif ($row['scale'] == 1) {$price = $row['normal_price'] . "/lb";}
        $desc = strtoupper(substr($row['description'],0,27));
        $brand = ucwords(strtolower(substr($row['brand'],0,13)));
        $pak = $row['units'];
        $size = $row['units'] . "-" . $row['size'];
        $sku = ($row['sku'] == $row['upc']) ? "" : $row['sku'];
        $upc = ltrim($row['upc'],0);
        /** 
        * determine check digit using barcode.php function
        */
        $check = $pdf->GetCheckDigit($upc);
        /**
        * get tag creation date (today)
        */
        $tagdate = date('m/d/y');
        $vendor = substr($row['vendor'],0,7);

        /**
        * begin creating tag
        */
        $pdf->SetFont('Arial','',10);
        $pdf->SetXY($genLeft, $descTop);
        $pdf->Cell($w,4,substr($desc,0,20),0,0,'L');
        $pdf->SetXY($genLeft,$brandTop);
        $pdf->Cell($w/2,4,$brand,0,0,'L');
        $pdf->SetXY($genLeft,$sizeTop);
        $pdf->Cell($w/2,4,$size,0,0,'L');
        $pdf->SetXY($priceLeft+9,$skuTop);
        $pdf->Cell($w/3,4,$tagdate,0,0,'R');
        $pdf->SetXY($genLeft,$skuTop);
        $pdf->Cell($w/3,4,$sku,0,0,'L');
        $pdf->SetXY($vendLeft,$skuTop);
        $pdf->Cell($w/3,4,$vendor,0,0,'C');
        $pdf->SetFont('Arial','B',20);
        $pdf->SetXY($priceLeft,$priceTop);
        $pdf->Cell($w/2,8,$price,0,0,'R');
        /** 
        * add check digit to pid from testQ
        */
        $newUPC = $upc . $check;
        if (strlen($upc) <= 11)
            $pdf->UPC_A($barLeft,$barTop,$upc,7);
        else
            $pdf->EAN13($barLeft,$barTop,$upc,7);
        /**
        * increment label parameters for next label
        */
        $barLeft =$barLeft + $LeftShift;
        $priceLeft = $priceLeft + $LeftShift;
        $genLeft = $genLeft + $LeftShift;
        $vendLeft = $vendLeft + $LeftShift;
        $labelCount++;
    }
      
    /**
    * write to PDF
    */
    $pdf->Output();
  }

