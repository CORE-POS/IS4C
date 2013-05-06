<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

define('FPDF_FONTPATH','font/');
require($FANNIE_ROOT.'src/fpdf/fpdf.php');

/****Credit for the majority of what is below for barcode generation
 has to go to Olivier for posting the script on the FPDF.org scripts
 webpage.****/

class WEFC_Standard_PDF extends FPDF {

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

// WEFC_Standard_FPDF()
}

// These have to be declared outside functions.
$axis = "both";
$left = 3;
$top = 15;
$down = 30.5; // depth (height) of label, y-offset to the next label.
$across = 105.0; // width of label, x-offset to the next label.

$coords = array();
$coords['desc'] = array(0,0);

$left_brand=0; $left_desc=0; $left_price=0; $left_ppu=0; $left_vendor=0; $left_pkg=0; $left_order=0; $left_today=0;
$top_brand=0; $top_desc=0; $top_price=0; $top_ppu=0; $top_vendor=0; $top_pkg=0; $top_order=0; $top_today=0;

/*
if ( $axis == "left" || $axis == "both" )
	$left_brand = $left;
if ( $axis == "top" || $axis == "both" )
	$top_brand = $top;
*/

function cursorIncrement($direction) {

	global $left, $top, $down, $across;
	global $left_brand;
	global $left_desc, $left_price, $left_ppu, $left_vendor, $left_pkg, $left_order, $left_today;
	global $top_brand, $top_desc, $top_price, $top_ppu, $top_vendor, $top_pkg, $top_order, $top_today;

	if ( preg_match("/^(right|both)$/",$direction) ) {
		$left_brand += $across;
		$left_desc += $across;
		$left_ppu += $across;  $left_vendor += $across;  $left_pkg += $across;  $left_order += $across;  $left_today += $across;
	}

	if ( preg_match("/^(down|both)$/",$direction) ) {
		$top_brand += $down;
		$top_desc += $down;
		$top_price += $down;  $top_ppu += $down;  $top_vendor += $down;  $top_pkg += $down;  $top_order += $down;  $top_today += $down;
	}


/*
$left_price = $left;
$top_price = $top + 19;

$left_ppu = $left;
$top_ppu = $top + 23;

$left_vendor = $left + 68;
$top_vendor = $top;

$left_pkg = $left + 68;
$top_pkg = $top + 13;

$left_order = $left + 68;
$top_order = $top + 18;

$left_today = $left + 68;
$top_today = $top + 23;
*/

	return;
}

/* Based on No_Barcode.
 * 2-up on 8.5x11" stock.
 * 9 rows, each ~1 1/8" (~30mm) high.
 * What is the top margin for?
*/
function WEFC_Standard($data,$offset=0) {

	global $dbc;
	global $FANNIE_COOP_ID;

	global $coords;
	global $left, $top, $down, $across;
	global $left_brand;
	global $left_desc, $left_price, $left_ppu, $left_vendor, $left_pkg, $left_order, $left_today;
	global $top_brand, $top_desc, $top_price, $top_ppu, $top_vendor, $top_pkg, $top_order, $top_today;

	$pdf=new WEFC_Standard_PDF('P','mm','Letter'); //start new instance of PDF
	$pdf->SetTitle("WEFC Standard Shelf Labels",1); // Title, UTF-8 EL+
	// See $SRC/fpdf/font
	//$pdf->AddFont('Scala','');
	//$pdf->AddFont('Scala','B');
	//$pdf->AddFont('ScalaSans','B');
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

/*Set increment-counters for rows 
 * x axis is horizontal, y is vertical
 * x=0,y=0 is top-left
 * Note: These vars are local. Not same as e.g. $this->k
*/
$m = 0;  //number of labels created

$i = 9;  //x location of barcode
$j = 31; //y location of barcode

$l = 35; //y location of size and price on label
$k = 25; //x location of date and price on label

$n = 18; //y location of description for label
$r = 31; //y location of brand name for label

// "margin" for all cells that are flush-left or
//   centered or right-justified from the left margin.
$p = 5;  //x location fields on label

$t = 39; //y location of SKU and vendor info
$u = 20; //x location of vendor info for label

/* Each '.' in the diagram is starting place of a cell, i.e.
 *  the bottom-left, or base-line of the text.
 *  Letters are built to the right and up from this point.
 *  Text placed at 0,0 is not visible, but at 0,5 is at least partly visible.
 * Give it a name and assign its left=x and top=y coordinates as offsets
 *  relative to the upper-left corner of the page as 0,0.
 * Not all of these may actually be used.
 * They are incremented to establish coordinates for the cell
 *  in subsequent labels in the row and succeeding rows.
 * All initial left=x-coordinates are relative to the left edge of the page, not SetLeftMargin()
 * All initial top=y-coordinates are relative to the top edge of the page, not SetTopMargin()
 * These are in addition to the margins required by the printer.
*/

$axis = "both";
$left = 3;
$top = 15;

$down = 30.5; // depth (height) of label, y-offset to the next label.
$across = 105.0; // width of label, x-offset to the next label.
$pageWidth = 210;
$pageDepth = 275;
$leftMargin = $pdf->GetX();
$topMargin = $pdf->GetY();
$labelsPerRow = 0;
	while ( ($labelsPerRow * $across) <= ($pageWidth-$leftMargin) )
		$labelsPerRow++;
$rowsPerPage = 0;
	while ( ($rowsPerPage * $down) <= ($pageDepth-$topMargin) )
		$rowsPerPage++;
$labelsPerPage = $rowsPerPage*$labelsPerRow;
// Right-most x of a field.  Larger offset implies need for a new line.
//$maxLeft = 125;
$maxLeft = $left + (($labelsPerRow - 1)*$across);
// Bottom-most y of a field.  Larger offset implies need for a new page.
$maxTop = 230;
$maxTop2 = $top + (($rowsPerPage - 1)*$down); // 259

// 'c As though in a function to initialize for a new page.
//$coords['brand'] = array($left,$top);
//initializeCoords();
//function initializeCoords() {
//}
	$coords['brand'] = array('left'=>$left, 'top'=>$top);
	$coords['desc'] = array('left'=>$left, 'top'=>$top+7);
	$coords['price'] = array('left'=>$left, 'top'=>$top+18);
	$coords['ppu'] = array('left'=>$left, 'top'=>$top+23);

	$coords['vendor'] = array('left'=>$left+68, 'top'=>$top+0);
	$coords['pkg'] = array('left'=>$left+68, 'top'=>$top+13);
	$coords['order'] = array('left'=>$left+68, 'top'=>$top+18);
	$coords['today'] = array('left'=>$left+68, 'top'=>$top+23);

$left_vendor = $left + 68;
$top_vendor = $top;

$left_pkg = $left + 68;
$top_pkg = $top + 13;

$left_order = $left + 68;
$top_order = $top + 18;

$left_today = $left + 68;
$top_today = $top + 23;

if ( $axis == "left" || $axis == "both" ) {
	$left_brand = $left;
	$left_desc = $left;
	$left_price = $left;
	$left_ppu = $left;
	$coords['brand'][0] = $left;
	$coords['desc'][0] = $left;
	$coords['price'][0] = $left;
	$coords['ppu'][0] = $left;
}
if ( $axis == "top" || $axis == "both" ) {
	$top_brand = $top;
	$top_desc = $top + 7;
	$top_price = $top + 19;
	$top_ppu = $top + 23;
}




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

/* If not starting to print on the first label of the page
 * move the cursor to the starting point.
*/
for ($ocount=0;$ocount<$offset;$ocount++){
   //If $i > 175, start a new line of labels
   if($i > $maxLeft){
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
   //increment coordinates for label in row.
   $m = $m + 1;
   $i =$i+ $across;
   $k = $k + $across;
   $p = $p + $across;
   $u = $u + $across;
}

	/* Page heading
	$pdf->SetFont('Arial','',10);
	// Is this placed at the initial settings of LeftMargin and TopMargin?
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->SetXY($leftMargin,($topMargin-5));
	$pdf->Cell(0,5,"Top Left of Page maxLeft: $maxLeft maxLeft2: $maxLeft2 maxTop: $maxTop maxTop2: $maxTop2 ",1);
	//$pdf->Cell(0,0,"Top Left of Page leftMargin: $leftMargin from earlier GetX  topMargin: $topMargin from earlier GetY",1);
	*/

//cycle through result array of query
foreach($data as $row) {

	//If $m == 32 add a new page and reset all counters..
	//if($m == $labelsPerPage){
	if(False && $coords['brand']['top'] > $maxTop){
		$pdf->AddPage();
		$m = 0;
		//initializeCoords();
		foreach(array_keys($coords) as $key) {
			$coords["$key"]['top'] -= ($down*$rowsPerPage);
			//$coords["$key"]['left'] -= ($across*$labelsPerRow);
		}
		$i = 9;
		$j = 31;
		$l = 35;
		$k = 25;
		$n = 18;
		$r = 31;
		$p = 5;  
		$t = 39;
		$u = 20;
	}

	// If there's not room for this label in the row
	//  start another row.
	// 'brand' s/b $firstCell
	if($coords['brand']['left'] > $maxLeft){
		//cursorReset("left");
		//cursorIncrement("down");
		foreach(array_keys($coords) as $key) {
			$coords["$key"]['top'] += $down;
			$coords["$key"]['left'] -= ($across*$labelsPerRow);
		}
		// If there's not room on the page for the new row,
		//  start another page, at the top.
		if($coords['brand']['top'] > $maxTop) {
			$pdf->AddPage();
			foreach(array_keys($coords) as $key) {
				$coords["$key"]['top'] -= ($down*$rowsPerPage);
			}
		}

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
	$maxBrandWidth = 65;
	$pdf->SetFont('Arial','',11);
	$i=0;
	while ( $i<10 && $pdf->GetStringWidth($brand) > $maxBrandWidth && strlen($brand) > 20 ) {
		$brand = substr($brand,0,-1);
		$i++;
	}

	$maxDescWidth = 90;
	$pdf->SetFont('Arial','B',16);
	$i=0;
	while ( $i<10 && $pdf->GetStringWidth($desc) > $maxDescWidth && strlen($desc) > 20 ) {
		$desc = substr($desc,0,-1);
		$i++;
	}

	/* Start putting out a label 
	* For each element:
	*  1. Define the font if different than the current one.
	*  2. Move the cursor to the x,y starting point,
	*      ?unless it continues after the previous element.
	*     The elements can be put out in any order.
	*  3. Set attributes such as border, text alignment
	*  4. Stream the text. The command may include #2 and #3.
	*/

	if (False) {
	// Description, multiple lines, broken by the program.
	//  No_Barcode can only take two lines.
	//  Might use GetStringWidth() to trap/truncate
	//    SetFont(Name,Bold,size-in-points)
	$pdf->SetFont('Arial','B',12);  //Set the font 
	$pdf->SetXY($p,$n);
	//    MultiCell(width, line-height,content,no-border,centered)
	$pdf->MultiCell(41,5,$desc,0,'C');

	// Brand, package, price-per-unit, date-tag-made
	// Same font, several cursor moves.
	$pdf->SetFont('Arial','',8);  //Set the font 
	//    TEXT(x,y,content)
	$pdf->TEXT($p,$r,$brand);
	$pdf->TEXT($p,$l,$size);
	$pdf->SetXY($k+7,$t-3);
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->Cell(0,0,$ppu,0,0,'R'); // right-aligned
	$pdf->TEXT($i+24,$j+11,$tagdate);
	//$pdf->TEXT($i+24,$j+11,$orderCode);

	// SKU and Vendor Name
	$pdf->SetFont('Arial','',10);
	$pdf->TEXT($p,$t,$sku);
	$pdf->TEXT($u-2,$t,$vendor);

	// Price
	$pdf->SetFont('Arial','B',24); //change font for price
	$pdf->TEXT($k,$l,$price);  //add price
	// No Barcode
	}

	$pdf->SetFont('Arial','',11);
	$cell = 'brand';
	// A line above
	$pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']-4);
	$pdf->Cell(0,0," ",'T',0,'L');
	$pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->Cell(0,0,"$brand",'',0,'L');
	//$pdf->Cell(0,5,"$brand",'T',0,'L');

	$pdf->SetFont('Arial','B',16);
	$pdf->SetXY($coords['desc']['left'],$coords['desc']['top']);
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->Cell(0,0,"$desc",0,0,'L');

	$pdf->SetFont('Arial','B',30);
	$pdf->SetXY($coords['price']['left'],$coords['price']['top']);
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->Cell(67,0,"$price",0,0,'C');

	$pdf->SetFont('Arial','',8);
	$pdf->SetXY($coords['ppu']['left'],$coords['ppu']['top']);
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->Cell(0,0,"$ppu",0,0,'L');

	$pdf->SetFont('Arial','',10);
	$cell = 'vendor';
	$pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->Cell(0,0,"$vendor",0,0,'L');

	$cell = 'pkg';
	$pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->Cell(0,0,"$pkg",0,0,'L');

	$cell = 'order';
	$pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->Cell(30,0,"$orderCode",0,0,'R');

	$pdf->SetFont('Arial','',8);
	$cell = 'today';
	$pdf->SetXY($coords["$cell"]['left'],$coords["$cell"]['top']);
	//    Cell(width, line-height,content,no-border,cursor-position-after,text-align)
	$pdf->Cell(30,0,"$tagdate",0,0,'R');

	//increment x-coordinates for the next label in the row.
	$m = $m + 1;
	$i = $i + $across;
	$k = $k + $across;
	$p = $p + $across;
	$u = $u + $across;

	// 'z
	cursorIncrement("right");
	//cursorIncrement2("right");
	foreach(array_keys($coords) as $key) {
		$coords["$key"]['left'] += $across;
	}

//break;
// each label
}

$pdf->Output();  //Output PDF file to browser PDF handler.

// WEFC_Standard()
}

?>
