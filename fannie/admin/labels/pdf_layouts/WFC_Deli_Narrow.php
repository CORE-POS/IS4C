<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
/*
    Using layouts
    1. Make a file, e.g. New_Layout.php
    2. Make a PDF class New_Layout_PDF extending FPDF
       (just copy an existing one to get the UPC/EAN/Barcode
        functions)
    3. Make a function New_Layout($data)
       Data is an array database rows containing:
        normal_price
        description
        brand
        units
        size
        sku
        pricePerUnit
        upc
        vendor
        scale
    4. In your function, build the PDF. Look at 
       existings ones for some hints and/or FPDF
       documentation

    Name matching is important
*/

class WFC_Deli_Narrow_PDF extends FpdfWithBarcode
{
    private $tagdate;
    function setTagDate($str){
        $this->tagdate = $str;
    }

    function barcodeText($x, $y, $h, $barcode, $len)
    {
        $this->SetFont('Arial','',8);
        $this->Text($x,$y-$h+(17/$this->k),substr($barcode,-$len).' '.$this->tagdate);
    }
}

function WFC_Deli_Narrow($data,$offset=0){

$pdf=new WFC_Deli_Narrow_PDF('P','mm','Letter'); //start new instance of PDF
$pdf->Open(); //open new PDF Document
$pdf->setTagDate(date("m/d/Y"));
$pdf->SetFillColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$dbc = FannieDB::get(FannieConfig::config('OP_DB'));

$font = 'Arial';
if (FanniePlugin::isEnabled('CoopDealsSigns')) {
    $font = 'Gill';
    define('FPDF_FONTPATH', dirname(__FILE__) . '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
}

$width = 69; // tag width in mm
$height = 39; // tag height in mm
$left = 1; // left margin
$top = 10; // top margin

// undo margin if offset is true
if($offset) {
    $top = 32;
}

$pdf->SetTopMargin($top);  //Set top margin of the page
$pdf->SetLeftMargin($left);  //Set left margin of the page
$pdf->SetRightMargin($left);  //Set the right margin of the page
$pdf->SetAutoPageBreak(False); // manage page breaks yourself
$pdf->AddPage('L');  //Add page #1

$num = 1; // count tags 
$x = $left;
$y = $top;
//cycle through result array of query

/*
for ($ocount=0;$ocount<$offset;$ocount++){
    // move right by tag width
    $x += $width;

    if ($num % 32 == 0){
        $pdf->AddPage();
        $x = $left;
        $y = $top;
    }
    else if ($num % 4 == 0){
        $x = $left;
        $y += $height;
    }

    $num++;
}
*/
$str = '';
$tagcount = count($data);
if ($tagcount < 20) {
    for ($tagcount; $tagcount < 20; $tagcount++) {
        $data[]['upc'] = "0000000000000";
    }
}
$i = 0;
foreach($data as $k => $row){
    $i++;
    if ($i > $tagcount+1) {
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetTextColor(0, 0, 0);
    } 
   $price = isset($row['normal_price']) ? $row['normal_price'] : 0;
   //$desc = strtoupper(substr($row['description'],0,27));
   //$desc = str_replace("\n", "", $desc);
   //$desc = str_replace("\r", "", $desc);
   $brand = ucwords(strtolower(isset($row['brand']) ? $row['brand'] : ''));
   $pak = isset($row['units']) ? $row['units'] : 1;
   $size = $pak . "-" . (isset($row['size']) ? $row['size'] : '');
   $sku = isset($row['sku']) ? $row['sku'] : '';
   $ppu = isset($row['pricePerUnit']) ? $row['pricePerUnit'] : '';
   $upc = ltrim($row['upc'],0);
   $check = $pdf->GetCheckDigit($upc);
   $vendor = substr(isset($row['vendor']) ? $row['vendor'] : '',0,7);

   //get fancy description
   $args = array($row['upc']);
   $prep = $dbc->prepare("
        SELECT pu.description, p.scale
        FROM productUser AS pu
            INNER JOIN products AS p ON pu.upc=p.upc
        WHERE pu.upc = ?");
   $res = $dbc->execute($prep, $args);
   $row = $dbc->fetchRow($res);
   $desc = $row['description'];
       
   $desc = str_replace("\n", "", $desc);
   $desc = str_replace("\r", "", $desc);
   $scale = $row['scale'];
   $price = ($scale == 0) ? "$".$price : "";
   if ($scale != 0) continue;

   // writing data
   // basically just set cursor position
   // then write text w/ Cell
   $wrp = wordwrap($desc, 25, "*", false);
   $dscripts = explode('*', $wrp);
   $descFontSize = 13;
   if (count($dscripts) == 1) {
       $descHeight = 10;
   } else {
       $descHeight = 5;
   }
    
   if (strlen($brand) >25) {
        $brandFontSize = 12;
   } elseif (strlen($brand) > 20) {
        $brandFontSize = 14;
   } elseif (strlen($brand) > 18) {
        $brandFontSize = 16;
   } else {
       $brandFontSize = 18;
   }
    
   $pdf->SetFont($font,'B',$brandFontSize);  //Set the font 
   $pdf->SetXY($x,$y);
   $pdf->Cell($width,3,'',0,1,'C',true);
   $pdf->SetX($x);
   $pdf->Cell($width,7,$brand,0,1,'C',true);
   $pdf->SetX($x);
   $pdf->Cell($width,1,'',0,1,'C',true);
   $pdf->SetX($x);

   $pdf->SetFont($font,'B',$descFontSize);  //Set the font 
   foreach ($dscripts as $i => $desc) {
       $pdf->Cell($width,$descHeight,$desc,0,1,'C',true);
       $pdf->SetX($x);
   }
   //$pdf->Cell($width,5,$desc,0,1,'C',true);
   //$pdf->SetX($x);
   //$pdf->Cell($width,4,$desc2,0,1,'C',true);

   $pdf->SetFont($font,'B',18);  //change font size
   $pdf->SetXY($x,$y+20);
   $pdf->Cell($width,10,$price,0,0,'C',true);
   $pdf->SetXY($x,$y+30);
   $pdf->Cell($width,12,'',0,0,'C',true);

   // add guide-lines
   $pdf->SetFillColor(155, 155, 155);
   // horizontal lines
   $pdf->SetXY($x+5,$y+30);
   $pdf->Cell(5,0.2,'',0,0,'C',true);
   $pdf->SetXY($x+60,$y+30);
   $pdf->Cell(5,0.2,'',0,0,'C',true);
   $pdf->SetXY($x,$y);
   $pdf->Cell(5,0.2,'',0,0,'C',true);
   $pdf->SetXY($x+60,$y);
   $pdf->Cell(5,0.2,'',0,0,'C',true);
   // vertical lines
   $pdf->SetXY($x,$y);
   $pdf->Cell(0.2,5,'',0,0,'C',true);
   $pdf->SetXY($x,$y+64);
   $pdf->Cell(0.2,5,'',0,0,'C',true);

   $pdf->SetFillColor(0, 0, 0);

   // move right by tag width
   $x += $width;

   // if it's the end of a page, add a new
   // one and reset x/y top left margins
   // otherwise if it's the end of a line,
   // reset x and move y down by tag height
   if ($num % 20 == 0){
    $pdf->AddPage('L');
    $x = $left;
    $y = $top;
   }
   else if ($num % 4 == 0){
    $x = $left;
    $y += $height;
   }

   $num++;
}

    $pdf->Output();  //Output PDF file to screen.
}

