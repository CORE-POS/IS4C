<?php
/**
 * fpdf is the pdf creation class doc
 * manual and tutorial can be found in fpdf dir
 */
require('../src/fpdf/fpdf.php');

/**
 * prodFuction contains several product related functions
 */
// require('prodFunction.php');

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
define('FPDF_FONTPATH','font/');

class PDF extends FPDF
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
	$this->Text($x+6,$y+$h+11/$this->k,substr($barcode,-$len));
  }

}

/**------------------------------------------------------------
 *       End barcode creation class 
 *-------------------------------------------------------------*/


/**------------------------------------------------------------
 *        Start creation of PDF Document here
 *------------------------------------------------------------*/

/**
 * connect to mysql server and then 
 * set to database with UNFI table ($data) in it
 * other vendors could be added here, as well. 
 * NOTE: upc in UNFI is without check digit to match standard in 
 * products.
 */

$data = 'is4c_op';

$db = mysql_connect('localhost','root');
mysql_select_db($data,$db);

/** 
 * $testQ query creates select for barcode labels for items
 * in $dept department.
 */ 
 
$dept = 2;

$testQ = "select   if(u.brand IS NULL,'',substring(u.brand,1,12)) as brand,  if(u.sku IS NULL,'', u.sku) as sku,  if(u.size IS NULL,'',u.size) as size,  if(u.upc IS NULL,'',u.upc) as upc,  if(u.units IS NULL,'',u.units) as units,  if(u.cost IS NULL,'',u.cost) as cost,  if(u.description IS NULL, substring(p.description,1,23),substring(u.description,1,23)) as description,   right(p.upc,12) as pid,   if(u.upc IS NULL, 'Misc', 'UNFI') as vendor, normal_price  from is4c_op.products as p left outer join is4c_op.UNFI as u  on p.upc = u.upc  WHERE p.department IN(2,3,4,5,6,7,8,10,13) AND datediff(now(),p.modified) < 14 ORDER BY department";

$result = mysql_query($testQ);
if (!$result) {
   $message  = 'Invalid query: ' . mysql_error() . "\n";
   $message .= 'Whole query: ' . $query;
   die($message);
}

/**
 * begin to create PDF file using fpdf functions
 */

$pdf=new PDF();
$pdf->Open();
$pdf->SetTopMargin(20);
$pdf->SetLeftMargin(4);
$pdf->SetRightMargin(0);
$pdf->AddPage();

/**
 * set up location variable starts
 */
 
$barLeft = 9;
$barTop = 33;
$priceTop = 28;
$priceLeft = 25;
$labelCount = 0;
$descTop = 20;
$brandTop = 24;
$genLeft = 5;
$skuTop = 32;
$vendLeft = 18;
$down = 31;

/**
 * increment through items in query
 */
 
while($row = mysql_fetch_array($result)){
   /**
    * check to see if we have made 32 labels.
    * if we have start a new page....
    */
    
   if($labelCount == 32){
      $pdf->AddPage();
      $barLeft = 9;
      $barTop = 33;
      $priceTop = 28;
      $priceLeft = 25;
      $labelCount = 0;
      $descTop = 20;
      $genLeft = 5;
      $brandTop = 24;
      $skuTop = 32;
      $vendLeft = 18;
   }

   /** 
    * check to see if we have reached the right most label
    * if we have reset all left hands back to initial values
    */
   if($barLeft > 175){
      $barLeft = 9;
      $barTop = $barTop + $down;
      $priceLeft = 25;
      $priceTop = $priceTop + $down;
      $descTop = $descTop + $down;
      $brandTop = $brandTop + $down;
      $genLeft = 5;
      $vendLeft = 18;
      $skuTop = $skuTop + $down;
   }

/**
 * instantiate variables for printing on barcode from 
 * $testQ query result set
 */
   $price = $row['normal_price'];
   $desc = strtoupper(substr($row['description'],0,27));
   $brand = ucwords(strtolower(substr($row['brand'],0,13)));
   $pak = $row['units'];
   $size = $row['units'] . "-" . $row['size'];
   $sku = $row['sku'];
   $upc = $row['pid'];
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
   $pdf->TEXT($genLeft,$descTop,$desc);
   $pdf->TEXT($genLeft,$brandTop,$brand);
   $pdf->TEXT($genLeft,$priceTop,$size);
   $pdf->TEXT($priceLeft+9,$skuTop,$tagdate);
   $pdf->SetFont('Arial','',10);
   $pdf->TEXT($genLeft,$skuTop,$sku);
   $pdf->TEXT($vendLeft,$skuTop,$vendor);
   $pdf->SetFont('Arial','B',24);
   $pdf->TEXT($priceLeft,$priceTop,$price);
/** 
 * add check digit to pid from testQ
 */
   $newUPC = $upc . $check;
   $pdf->UPC_A($barLeft,$barTop,$upc,7);
/**
 * increment label parameters for next label
 */
   $barLeft =$barLeft + 53;
   $priceLeft = $priceLeft + 53;
   $labelCount = $labelCount + 1;
   $genLeft = $genLeft + 53;
   $vendLeft = $vendLeft + 53;
}

/**
 * write to PDF
 */
$pdf->Output();

?>

