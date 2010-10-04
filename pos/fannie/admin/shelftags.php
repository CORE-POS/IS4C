<?php

if (isset($_POST['submitted']) || isset($_GET['batchID']) ){
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
  
  
  /**------------------------------------------------------------
   *        Start creation of PDF Document here
   *------------------------------------------------------------*/
  $dArray = ""; // variable inits to avoid warnings
  $date1 = ""; // when it's batch barcodes
  $date2 = "";
  if(isset($_POST['submit'])){
          foreach ($_POST AS $key => $value) {
                  $$key = $value;
          }
  }else{
        foreach ($_GET AS $key => $value) {
            $$key = $value;
        }
  }
  
  $_SESSION['deptArray'] = 0;
  
  if(isset($_POST['allDepts']) && $_POST['allDepts'] == 1) {
  //	$_SESSION['deptArray'] = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,40";
          $dArray = "1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,40";
  } else {
          $allDepts = 0;
  }
  
  if(isset($_POST['dept']) && is_array($_POST['dept'])) {
  //	$_SESSION['deptArray'] = implode(",",$_POST['dept']);
          $dArray = implode(",",$_POST['dept']);
  }
    
  /**
   * connect to mysql server and then 
   * set to database with UNFI table ($data) in it
   * other vendors could be added here, as well. 
   * NOTE: upc in UNFI is without check digit to match standard in 
   * products.
   */
  
  include('../src/mysql_connect.php');
  
  /** 
   * $testQ query creates select for barcode labels for items
   */ 
   
  
  $testQ = "select if(u.brand IS NULL,'',substring(u.brand,1,12)) as brand,  
                  if(u.sku IS NULL,'', u.sku) as sku,  
                  if(u.size IS NULL,'',u.size) as size,  
                  if(u.upc IS NULL,'',u.upc) as upc,  
                  if(u.units IS NULL,'',u.units) as units,  
                  if(u.cost IS NULL,'',u.cost) as cost,  
                  if(p.description IS NULL, substring(u.description,1,23),substring(p.description,1,23)) as description,   
                  right(p.upc,12) as pid,   
                  if(u.upc IS NULL, 'Misc', 'UNFI') as vendor, 
                  ROUND(normal_price,2) AS normal_price,
                  p.scale AS scale
          from products as p left outer join UNFI as u  on p.upc = u.upc  
          WHERE p.department IN($dArray)  
          AND date(modified) BETWEEN '$date1' AND '$date2'
          ORDER BY department";
  if (isset($_GET['batchID'])){
	$batchIDList = '';
	foreach($_GET['batchID'] as $x)
		$batchIDList .= $x.',';
	$batchIDList = substr($batchIDList,0,strlen($batchIDList)-1);
	$testQ = "select substring(brand,1,12) as brand,sku,b.size,b.upc,units,0,
		substring(b.description,1,23) as description,right(b.upc,12) as pid,vendor,
		b.normal_price,p.scale FROM batchBarcodes as b LEFT JOIN products AS p
		ON b.upc=p.upc WHERE batchID in ($batchIDList) and b.description <> ''
		ORDER BY batchID";
  }
  
  $result = $dbc->query($testQ);
  if (!$result) {
     $message = 'Whole query: ' . $testQ;
     die($message);
  }
  
  /**
   * begin to create PDF file using fpdf functions
   */

  if (!isset($_GET['narrow'])){
	$hspace = 0.79375;
	$h = 29.36875;
	$top = 12.7 + 2.5;
	$left = 4.85 + 1.25;
	$space = 1.190625 * 2;
  
	$pdf=new PDF('P', 'mm', 'Letter');
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
	   
	while($row = $dbc->fetch_row($result)){
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
  else {
		$pdf=new PDF(); //start new instance of PDF
		$pdf->Open(); //open new PDF Document
		$pdf->SetTopMargin(40);  //Set top margin of the page
		$pdf->SetLeftMargin(4);  //Set left margin of the page
		$pdf->SetRightMargin(0);  //Set the right margin of the page
		$pdf->AddPage();  //Add a page

		//Set increment counters for rows 
		$i = 2;  //x location of barcode
		$j = 35; //y locaton of barcode
		$l = 34; //y location of size and price on label
		$k = 4; //x location of date and price on label
		$m = 0;  //number of labels created
		$n = 22; //y location of description for label
		$r = 28; //y location of date for label
		$p = 2;  //x location fields on label
		$t = 32; //y location of SKU and vendor info
		$u = 20; //x locaiton of vendor info for label
		$down = 30.6;

		//cycle through result array of query
		while($row = $dbc->fetch_array($result)){
		   //If $m == 32 add a new page and reset all counters..
		   if($m == 32){
		      $pdf->AddPage();
		      $i = 2;
		      $j = 35;
		      $l = 34;
		      $k = 4;
		      $m = 0;
		      $n = 22;
		      $p = 2;  
		      $q = 24;
		      $r = 28;
		      $t = 32;
		      $u = 20;
		   }

		   //If $i > 175, start a new line of labels
		   if($i > 175){
		      $i = 2;
		      $j = $j + $down;
		      $k = 4;
		      $l = $l + $down;
		      $n = $n + $down;
		      $r = $r + $down;
		      $p = 2;
		      $u = 20;
		      $t = $t + $down;
		   }
		   $price = $row['normal_price'];
		   $desc = strtoupper(substr($row['description'],0,27));
		   $brand = ucwords(strtolower(substr($row['brand'],0,13)));
		   $pak = $row['units'];
		   $size = $row['units'] . "-" . $row['size'];
		   $sku = $row['sku'];
		   $upc = $row['pid'];
		   $check = $pdf->GetCheckDigit($upc);
		   $tagdate = date('m/d/y');
		   $vendor = substr($row['vendor'],0,7);
		   
		   //Start laying out a label 
		   $pdf->SetFont('Arial','',8);  //Set the font 

		   $words = split("[ ,-]",$desc);
		   $limit = 13;
		   $lineheight = 0;
		   $curStr = "";
		   foreach($words as $w){
			if (strlen($curStr." ".$w) <= $limit)
				$curStr .= " ".$w;
			else {
				$pdf->TEXT($p,$n+$lineheight,$curStr);
				$curStr = "";
				$lineheight += 3;
			}
	   }
	   $pdf->TEXT($p,$n+$lineheight,$curStr);
	   
	   //$pdf->TEXT($p,$n,$desc);   //Add description to label

	   $pdf->TEXT($p,$r,$tagdate);  //Add date to lable
	   $pdf->TEXT($p+12,$r,$size);  //Add size to label
	   $pdf->SetFont('Arial','B',18); //change font for price
	   $pdf->TEXT($k,$l,$price);  //add price

	   $newUPC = $upc . $check; //add check digit to upc
	   $pdf->UPC_A($i,$j,$upc,7,.23);  //generate barcode and place on label

	   //increment counters    
	   $i =$i+ 52.7;
	   $k = $k + 52.7;
	   $m = $m + 1;
	   $p = $p + 52.7;
	   $u = $u + 52.7;
	}

	$pdf->Output();  //Output PDF file to screen.
  }
        
} else { // Show the form.
  
  $page_title = 'Fannie - Administration Module';
  $header = 'Shelftag Generator';
  include ('../src/header.html');
  echo '<link href="../style.css" rel="stylesheet" type="text/css" />
  <script src="../src/CalendarControl.js" language="javascript"></script>
  <script src="../src/putfocus.js" language="javascript"></script>
  </head>
  <body onLoad="putFocus(0,0);">
  <link href="../style.css" rel="stylesheet" type="text/css">
  <script src="../src/CalendarControl.js" language="javascript"></script>
  
  <form method="post" action="shelftags.php" target="_blank">
          
  <h2>Shelftag Generator</h2>
  
  <table border="0" cellspacing="3" cellpadding="3">
          <tr> 
                  <th align="center"> <p><b>Select dept.*</b></p></th>
          </tr>
          <tr>';
               include('../src/departments.php');
			echo '</tr>
  </table>
  <table border="0" cellspacing="3" cellpadding="3">
  <tr>
          <td align="right">
                  <p><b>Date Start</b> </p>
          <p><b>End</b></p>
          </td>
          <td>			
                  <p><input type=text size=10 name=date1 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
                  <p><input type=text size=10 name=date2 onfocus="showCalendarControl(this);">&nbsp;&nbsp;*</p>
          </td>
          <td colspan=2>
                  <p>Date format is YYYY-MM-DD</br>(e.g. 2004-04-01 = April 1, 2004)</p>
          </td>
  </tr>
  <tr> 
          <td>&nbsp;</td>
          <td> <input type=submit name=submit value="Submit"> </td>
          <td> <input type=reset name=reset value="Start Over"> </td>
          <input type="hidden" name="submitted" value="TRUE">
  </tr>
  </table>	
  </form>';
  
  include('../src/footer.html');
}

?>
