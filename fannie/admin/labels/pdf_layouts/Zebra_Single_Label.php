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

  class Zebra_Single_Label extends FpdfWithBarcode
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

  function Zebra_Single_Label($data, $offset=0, $filename=NULL){
    $hspace = 0.89375;
    $h = 31;
    $w = 53;
    $top = 0;
    $left = 0;
    $space = 1.190625 * 2;

    $pdf=new Zebra_Single_Label('L', 'mm', array(31.75, 57.15));
    $pdf->SetMargins(0, 0);
    $pdf->SetAutoPageBreak('off', 0);
    $pdf->AddPage('L');
    $pdf->SetFont('Arial', '', 10);

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
    $priceLeft = ($w / 2) + ($space);

    /**
    * instantiate variables for printing on barcode from
    * $testQ query result set
    */
    if ($data[0]['scale'] == 0) {$price = $data[0]['normal_price'];}
    elseif ($data[0]['scale'] == 1) {$price = $data[0]['normal_price'] . "/lb";}
    $desc = strtoupper(substr($data[0]['description'],0,27));
    $brand = ucwords(strtolower(substr($data[0]['brand'],0,13)));
    $pak = $data[0]['units'];
    $size = $data[0]['units'] . "-" . $data[0]['size'];
    $sku = $data[0]['sku'];
    $upc = substr($data[0]['upc'],1,12);
    /**
    * determine check digit using barcode.php function
    */
    $check = $pdf->GetCheckDigit($upc);
    /**
    * get tag creation date (today)
    */
    $tagdate = date('m/d/y');
    $vendor = substr($data[0]['vendor'],0,7);

    /**
    * begin creating tag
    */
    $pdf->SetXY($genLeft, $descTop);
    $pdf->Cell($w,4,substr($desc,0,20),0,0,'L');
    $pdf->SetXY($genLeft,$brandTop);
    $pdf->Cell($w/2,4,$brand,0,0,'L');
    $pdf->SetXY($genLeft,$sizeTop);
    $pdf->Cell($w/2,4,$size,0,0,'L');
    $pdf->SetXY($priceLeft+9,$skuTop);
    $pdf->Cell($w/3,4,$tagdate,0,0,'R');
    // $pdf->SetFont('Arial','',10);
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
    $pdf->UPC_A($barLeft, $barTop, $upc, 10);
    /**
    * increment label parameters for next label
    */
    $barLeft =$barLeft + $LeftShift;
    $priceLeft = $priceLeft + $LeftShift;
    $genLeft = $genLeft + $LeftShift;
    $vendLeft = $vendLeft + $LeftShift;
    $labelCount++;

    /**
    * write to PDF
    */
    if(is_null($filename)) {
      $pdf->Output();
    } else {
      $pdf->Output($filename, "F");
    }
  }
