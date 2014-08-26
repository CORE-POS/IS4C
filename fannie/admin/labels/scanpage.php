<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

define('FPDF_FONTPATH','font/');
   require($FANNIE_ROOT.'src/fpdf/fpdf.php');

/****Credit for the majority of what is below for barcode generation
 has to go to Olivier for posting the script on the FPDF.org scripts
 webpage.****/

class ScanPage_PDF extends FPDF
{
   var $tagdate;
   function setTagDate($str){
    $this->tagdate = $str;
   }
  
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
      //Print text under barcode
      $this->SetFont('Arial','',8);
      $this->Text($x,$y-$h+(17/$this->k),substr($barcode,-$len).' '.$this->tagdate);
    }
}

function build_page($upcs){
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $q = "SELECT CASE WHEN u.brand IS NULL THEN x.manufacturer ELSE u.brand END as fixedBrand,
        CASE WHEN u.description IS NULL THEN p.description ELSE u.description END as fixedDesc,
        p.department,d.dept_name,p.upc
        FROM products AS p LEFT JOIN productUser AS u ON p.upc=u.upc
        LEFT JOIN prodExtra AS x ON p.upc=x.upc
        LEFT JOIN departments AS d ON p.department=d.dept_no
        WHERE p.upc IN (";
    $args = array();
    foreach($upcs as $u){
        $q .= '?,';
        $args[] = BarcodeLib::padUPC($u);
    }
    $q = rtrim($q,",").") ORDER BY fixedDesc";
    $p = $dbc->prepare_statement($q);
    $r = $dbc->exec_statement($p,$args);

    $pdf=new WFC_New_PDF('P','mm','Letter'); //start new instance of PDF
    $pdf->Open(); //open new PDF Document
    $pdf->setTagDate(date("m/d/Y"));

    $left = 5; // left margin
    $top = 15; // top margin

    $pdf->SetTopMargin($top);  //Set top margin of the page
    $pdf->SetLeftMargin($left);  //Set left margin of the page
    $pdf->SetRightMargin($left);  //Set the right margin of the page
    $pdf->SetAutoPageBreak(False); // manage page breaks yourself
    $pdf->AddPage();  //Add page #1

    $num = 1; // count tags 
    $x = $left;
    $y = $top;
    //cycle through result array of query
    while($w = $dbc->fetch_row($r)){

       // extract & format data
       $desc = $w['fixedDesc'];
       $brand = $w['fixedBrand'];
       $text = $brand.' '.$desc.' ('.$w['department'].' - '.$w['dept_name'].')';
       $upc = ltrim($w['upc'],0);
       $check = $pdf->GetCheckDigit($upc);
       
       //Start laying out a label 
       $newUPC = $upc . $check; //add check digit to upc
       if (strlen($upc) <= 11)
        $pdf->UPC_A($x,$y,$upc,7);  //generate barcode and place on label
       else
        $pdf->EAN13($x,$y,$upc,7);  //generate barcode and place on label

       // writing data
       // basically just set cursor position
       // then write text w/ Cell
       $pdf->SetFont('Arial','',20);  //Set the font 
       $pdf->Ln(5);
       $pdf->SetXY($x+40,$y-4);
       $pdf->MultiCell(150,10,$text,0,'L');
       $pdf->Ln(1);
       $pdf->SetDrawColor(0,0,0);
       $pdf->SetFillColor(0,0,0);
       $pdf->SetTextColor(0,0,0);
       $pdf->Cell(0,1,'','T');

       // move right by tag width
       $y += 30;

       // if it's the end of a page, add a new
       // one and reset x/y top left margins
       // otherwise if it's the end of a line,
       // reset x and move y down by tag height
       if ($num % 9 == 0){
        $pdf->AddPage();
        $x = $left;
        $y = $top;
       }
       $num++;
    }

    $pdf->Output();  //Output PDF file to screen.
}

if (isset($_REQUEST['likecode']) || isset($_REQUEST['upc'])){
    $upcs = array();        
    if (isset($_REQUEST['likecode'])){
        $q = $dbc->prepare_statement("SELECT upc FROM upcLike WHERE likeCode=?");
        $r = $dbc->exec_statement($q,array($_REQUEST['likecode']));
        while($w = $dbc->fetch_row($r)){
            $upcs[] = $w[0];
        }
    }
    else{
        $upcs = preg_split('/\D/',$_REQUEST['upc'],0,PREG_SPLIT_NO_EMPTY);
    }
    build_page($upcs);
}
else {
    printf('<h1>Make a scan page</h1>
        <form method="get" action="%s">
        <b>Likecode</b> <input type="text" size="5" name="likecode" />
        <p />
        <h2>OR</h2>
        <b>UPCs</b> <input type="text" name="upc" />
        <p />
        <input type="submit" name="Generate Page" />
        </form>',$_SERVER['PHP_SELF']);
}

?>
