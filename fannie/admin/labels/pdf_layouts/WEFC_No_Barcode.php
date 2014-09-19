<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op
    Copyright 2013 West End Food Co-op, Toronto

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

class WEFC_No_Barcode_PDF extends FPDF {

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
      $this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
    }

// WEFC_No_Barcode_FPDF()
}

/* Based on No_Barcode.
 * 2-up on 8.5x11" stock.
 * 9 rows, each ~1 1/8" (~30mm) high.
 * What is the top margin for?
*/
function WEFC_No_Barcode($data,$offset=0) {

    global $FANNIE_OP_DB;
    global $FANNIE_COOP_ID;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $pdf=new WEFC_No_Barcode_PDF('P','mm','Letter'); //start new instance of PDF
    $pdf->SetTitle("WEFC No Barcode Shelf Labels",1); // Title, UTF-8 EL+
    // See $SRC/fpdf/font
    $pdf->AddFont('Scala','','Scala.php');
    $pdf->AddFont('Scala','B','Scala-Bold.php');
    $pdf->AddFont('ScalaSans','','ScalaSans.php');
    $pdf->AddFont('ScalaSans','B','ScalaSans-Bold.php');
    /*
    helveticab.php
    helvetica.php
    Scala-Bold.php
    Scala-Bold.z
    Scala-Italic.php
    Scala-Italic.z
    Scala.php
    ScalaSans-Bold.php
    ScalaSans-Bold.z
    ScalaSans-Italic.php
    ScalaSans-Italic.z
    ScalaSans.php
    ScalaSans.z
    Scala.z
    */

    $pdf->Open();
    // Set initial cursor position.
    //  Later X,Y settings are absolute, NOT relative to these.
    $pdf->SetTopMargin(15);
    $pdf->SetLeftMargin(3);
    $pdf->SetRightMargin(0);
    // Manage page breaks yourself
    $pdf->SetAutoPageBreak(False);
    // Start the first page
    $pdf->AddPage();


    /* x axis is horizontal, y is vertical
     * x=0,y=0 is top-left
    */
    // depth (height) of label, y-offset to the next label.
    //  i.e. the distance to the same element on the next label down
    $down = 30.5;
    //  i.e. the distance to the same element on the next label across
    // width of label, x-offset to the next label.
    $across = 103.0;

    // Distance from the edge of the paper to the first printable character.
    // This varies by printer.
    $printerMargin = 3;
    /* You may not need to change anything in the rest of this block
     *  once down, across and printerMargin above are defined.
    */
    $pageWidth = (8.5 * 25.4)-(2*$printerMargin); //209.9 For 8.5"
    $pageDepth = (11.0 * 25.4)-(2*$printerMargin); //273.4 For 11"
    $leftMargin = $pdf->GetX();
    $left = $leftMargin;
    $topMargin = $pdf->GetY();
    $top = $topMargin;
    $labelsPerRow = 1;
        while ( (($labelsPerRow+1) * $across) <= ($pageWidth-$leftMargin) )
            $labelsPerRow++;
    $rowsPerPage = 1;
        while ( (($rowsPerPage+1) * $down) <= ($pageDepth-$topMargin) )
            $rowsPerPage++;
    $labelsPerPage = $rowsPerPage*$labelsPerRow;
    // Right-most x of a field.  Larger offset implies need for a new line.
    $maxLeft = $left + (($labelsPerRow - 1)*$across);
    // Bottom-most y of a field.  Larger offset implies need for a new page.
    $maxTop = $top + (($rowsPerPage - 1)*$down);
    // End of definitions you may not need to change.

    /* Each '.' in the diagram below is starting place of a cell, i.e.
     *  the bottom-left, or base-line of the text.
     *  Letters are built to the right and up from this point.
     *  Text placed at 0,0 is not visible, but at 0,5 is at least partly visible.
     * Give it a name and assign its left=x=horizontal and top=y=vertical
     *  coordinates as offsets
     *  relative to the upper-left corner of the page as 0,0.
     * Not all of these may actually be used.
     * They are incremented to establish coordinates for the cell
     *  in subsequent labels in the row and in succeeding rows.
     * All initial left=x-coordinates are relative to the left edge of the page, not SetLeftMargin()
     * All initial top=y-coordinates are relative to the top edge of the page, not SetTopMargin()
     * These are in addition to the margins required by the printer.
     *
     * To specify a differnt style of label:
     * - draw a diagram
     * - change the values in the coords array to the starting point for each element
    */
    /*
    +-------------------------------------------+
     .Brand - Flags                    .Vendor  |
     .Description                               |
                                                                                            |
                PRICE                        .Pkg     |
     .    PRICE / lb                   .  Order#|
     .PPU                              .   d/m/y|
    +-------------------------------------------+
    */
    $coords = array();
    $coords['brand'] = array('left'=>$left, 'top'=>$top);
    $coords['desc'] = array('left'=>$left, 'top'=>$top+7);
    $coords['price'] = array('left'=>$left, 'top'=>$top+17);
    $coords['ppu'] = array('left'=>$left, 'top'=>$top+23);

    $coords['vendor'] = array('left'=>$left+68, 'top'=>$top+0);
    $coords['pkg'] = array('left'=>$left+68, 'top'=>$top+13);
    $coords['order'] = array('left'=>$left+68, 'top'=>$top+18);
    $coords['today'] = array('left'=>$left+68, 'top'=>$top+23);

    /* Find the name of the top, left cell in the label,
     *  lowest left and lowest top values.
     * You don't need to change this.
    */
    $firstCell = ""; $lastCell = "";
    $fi = 99999;
    $fj = 0; $fk = 0;
    foreach(array_keys($coords) as $key) {
        $fj = $coords["$key"]['left'] + $coords["$key"]['top'];
        if ( $fj < $fi ) {
            $fi = $fj;
            $firstCell = $key;
        }
        if ( $fj > $fk ) {
            $fk = $fj;
            $lastCell = $key;
        }
    }

    /* 'o If not starting to print on the first label of the page
     * move the cursor to the starting point.
     $labelsPerRow
     $rowsPerPage
    */
    if ( $offset > 0 ) {
        $offsetRows=0;
        $offsetCols=0;
        $offsetRows = $offset / $labelsPerRow;
        $offsetRows = (int)$offsetRows;
        $offsetCols = $offset % $labelsPerRow;
        foreach(array_keys($coords) as $key) {
            $coords["$key"]['top'] += ($down*$offsetRows);
            $coords["$key"]['left'] += ($across*$offsetCols);
        }
    }

    // Make a local array of Product Flags
    $productFlags = array();
    $pQ = "SELECT bit_number, description FROM prodFlags";
    $pR = $dbc->exec_statement($pQ,array());
    if ( $pR ) {
        while($pf = $dbc->fetch_row($pR)){
            $productFlags[$pf['bit_number']] = $pf['description'];
        }
    } else {
        $dbc->logger("Failed: $pQ");
    }

    /* 'h Page heading
    $pdf->SetFont('Arial','',10);
    // Is this placed at the initial settings of LeftMargin and TopMargin?
    //    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
    $pdf->SetXY($leftMargin,($topMargin-5));
    $pdf->Cell(0,5, "offset: $offset offsetRows: $offsetRows offsetCols: $offsetCols ", 0);
    //$pdf->Cell(0,5, "rowsPerPage: $rowsPerPage maxTop: $maxTop ", 0);
    //$pdf->Cell(0,5, "firstCell: $firstCell fi: $fi lastCell: $lastCell fk: $fk ",0);
    //$pdf->Cell(0,5,"Top Left of Page maxLeft: $maxLeft maxLeft2: $maxLeft2 maxTop: $maxTop maxTop2: $maxTop2 ",1);
    //$pdf->Cell(0,0,"Top Left of Page leftMargin: $leftMargin from earlier GetX  topMargin: $topMargin from earlier GetY",1);
    */

    // Cycle through result array of query
    // There is one row for each label
    foreach($data as $row) {

        // If there's not room for this label in the row
        //  start another row.
        if($coords["$firstCell"]['left'] > $maxLeft){
            foreach(array_keys($coords) as $key) {
                $coords["$key"]['top'] += $down;
                $coords["$key"]['left'] -= ($across*$labelsPerRow);
            }
            // If there's not room on the page for the new row,
            //  start another page, at the top.
            if($coords["$firstCell"]['top'] > $maxTop) {
                $pdf->AddPage();
                foreach(array_keys($coords) as $key) {
                    $coords["$key"]['top'] -= ($down*$rowsPerPage);
                }
            }
        }

        // Prepare the data.
        $scale = $row['scale'];
        $price = '$'.$row['normal_price'];
            $price .= ($scale==1)?" / lb":"";
        // Why is it in caps to begin with?
        $desc = ucwords(strtolower($row['description']));
        // Remove "(BULK)", remove 1st char if "^[PB][A-Z][a-z]"
        $brand = $row['brand'];
        $pkg = $row['size'];
        // units is #/case, we don't want to display that.
        //$size = $row['units'] . "-" . $row['size'];
        $sku = $row['sku'];
        $ppu = $row['pricePerUnit'];
        $upc = ltrim($row['upc'],0);
        //$check = $pdf->GetCheckDigit($upc);
        $tagdate = date('jMy');
        $vendor = $row['vendor'];
        // A string of Product Flags.
        $flagSet = "";
        $numflag = (int)$row['numflag'];
        if ( $numflag !== 0 ) {
            $flags = array();
            for($fpt=0;$fpt<30;$fpt++) {
                if ( (1<<$fpt) & $numflag ) {
                    $bit_number = $fpt + 1;
                    $flags[] = $productFlags[$bit_number];
                }
            }
            $flagSet = ' - ' . implode(' ',$flags);
        }
        $orderCode = '';
        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' ) {
         $oQ = "SELECT order_code, description
                        FROM products_$FANNIE_COOP_ID WHERE upc = ?";
         $oV = array($row['upc']);
         $oR = $dbc->exec_statement($oQ,$oV);
         if ( $oR ) {
            while ( $oRow = $dbc->fetch_row($oR) ) {
                // Override the one from products.
                if ( $oRow['description'] != '' )
                    $desc = $oRow['description'];
                if ( ctype_digit($oRow['order_code']) ) {
                    $orderCode = 'ORDER #'.$oRow['order_code'];
                    break;
                }
            }
         } else {
            $dbc->logger("Failed: $oQ with {$oV[0]}");
         }
         if ( $orderCode == '' && $upc != '' )
            $orderCode = "UPC $upc";
        }
        
        // Further massaging.
        $brand .= $flagSet;
        $maxBrandWidth = 90; // If Vendor on same line: 65
        $pdf->SetFont('Arial','',10);
        $i=0;
        while ( $i<10 && $pdf->GetStringWidth($brand) > $maxBrandWidth && strlen($brand) > 20 ) {
            $brand = substr($brand,0,-1);
            $i++;
        }

        $maxDescWidth = 90;
        $pdf->SetFont('ScalaSans','B',18);
        $i=0;
        while ( $i<10 && $pdf->GetStringWidth($desc) > $maxDescWidth && strlen($desc) > 20 ) {
            $desc = substr($desc,0,-1);
            $i++;
        }

        /* Start putting out a label 
        * For each element:
        *  1. Define the font if different than the current one.
        *  2. Move the cursor to the x,y starting point,
        *      unless it continues after the previous element.
        *     The elements can be put out in any order.
        *  3. Set attributes such as border, text alignment
        *  4. Stream the text. The command may include #2 and #3.
        */

        $pdf->SetFont('Arial','',10);
        $cell = 'brand';
        // A line above
        $pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']-4);
        $pdf->Cell(($across-3),0," ",'T',0,'L');
        $pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
        //    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
        $pdf->Cell(0,0,"$brand",'',0,'L');

        $cell = 'desc';
        $pdf->SetFont('ScalaSans','B',18);
        $pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
        //    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
        $pdf->Cell(0,0,"$desc",0,0,'L');

        $cell = 'price';
        $pdf->SetFont('Arial','B',30);
        $pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
        //    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
        $pdf->Cell(67,0,"$price",0,0,'C');

        $cell = 'ppu';
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
        //    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
        $pdf->Cell(0,0,"$ppu",0,0,'L');

        /*
        $pdf->SetFont('Arial','',10);
        $cell = 'vendor';
        $pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
        //    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
        $pdf->Cell(0,0,"$vendor",0,0,'L');
        */

        $cell = 'pkg';
        $pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
        //    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
        $pdf->Cell(30,0,"$pkg",0,0,'R');

        $cell = 'order';
        $pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
        //    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
        $pdf->Cell(30,0,"$orderCode",0,0,'R');

        $pdf->SetFont('Arial','',8);
        $cell = 'today';
        $pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
        //    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
        $pdf->Cell(30,0,"$tagdate",0,0,'R');

        // Increment the cursor coordinates for each cell to the next label to the right.
        //  The need to move down the page is handled later.
        foreach(array_keys($coords) as $key) {
            $coords["$key"]['left'] += $across;
        }

    // each label
    }

    $pdf->Output();  //Output PDF file to browser PDF handler.

// WEFC_No_Barcode()
}

?>
