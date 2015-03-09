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

  /**
   * fpdf is the pdf creation class doc
   * manual and tutorial can be found in fpdf dir
   */
require($FANNIE_ROOT.'src/fpdf/fpdf.php');
  
  /**-------------------------------------------------------- 
   *            begin  barcode creation class from 
   *--------------------------------------------------------*/
  
  /*******************************************************************************
  * Software: barcode                                                            *
  * Author:   Olivier PLATHEY                                                    *
  * License:  Freeware                                                           *
  * URL: www.fpdf.org                                                            *
  * You may use, modify and redistribute this software as you wish.              *
  *******************************************************************************/
  if (!defined('FPDF_FONTPATH')) {
      define('FPDF_FONTPATH','font/');
  }
  
  class Fannie_Standard_PDF extends FPDF
  {
    function EAN13($x,$y,$barcode,$h=16,$w=.35)
    {
          $this->Barcode($x,$y,$barcode,$h,$w,12);
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
                  $sum+=3*$barcode{$i};
          for($i=0;$i<=10;$i+=2)
                  $sum+=$barcode{$i};
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
      GLOBAL $genLeft;
      GLOBAL $descTop;
          //Padding
          //$barcode=str_pad($barcode,$len-1,'0',STR_PAD_LEFT);
      //$barcode = $barcode . $check;
          /*if($len==12)
                  $barcode='0'.$barcode;
      */
          //Add or control the check digit
          if(strlen($barcode)==12)
                  $barcode.=$this->GetCheckDigit($barcode);
          elseif(!$this->TestCheckDigit($barcode))
      {
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
          //$this->SetXY($genLeft,$descTop + 24);
          //$this->Cell(49.609375,4,substr($barcode,-$len),0,0,'C');
      if (isset($_GET['narrow']))
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
        $sku = $row['sku'];
        $upc = substr($row['upc'],1,12);
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
        $pdf->UPC_A($barLeft,$barTop,$upc,7);
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
?>
