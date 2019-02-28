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

class WFC_Deli_Dark_PDF extends FpdfWithBarcode
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

function WFC_Deli_Dark($data,$offset=0){

$pdf=new WFC_Deli_Dark_PDF('P','mm','Letter'); //start new instance of PDF
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
$height = 41; // tag height in mm
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

foreach($data as $row){
/*
    if (strlen(ltrim($row['upc'], '0')) <= 4) continue;
    elseif (substr($row['upc'], -6) == '000000') continue;
*/
   // extract & format data
   $price = $row['normal_price'];
   //$desc = strtoupper(substr($row['description'],0,27));
   $brand = ucwords(strtolower($row['brand']));
   $pak = $row['units'];
   $size = $row['units'] . "-" . $row['size'];
   $sku = $row['sku'];
   $ppu = $row['pricePerUnit'];
   $upc = ltrim($row['upc'],0);
   $check = $pdf->GetCheckDigit($upc);
   $vendor = substr($row['vendor'],0,7);

   //get fancy description
   global $FANNIE_OP_DB;
   $dbc = FannieDB::get($FANNIE_OP_DB);

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

   // writing data
   // basically just set cursor position
   // then write text w/ Cell
   $desc2 = "";
   if (strlen($desc) > 25) {
        $temp = $desc;
        // find the end of the current word
        $arr = str_split($temp);
        $splitpos = 0;
        for ($i=0; $i<15; $i++){
            next($arr);
        }
        while ($curstr = next($arr)) {
            if (ctype_space($curstr)) {
                $splitpos = key($arr);
            } else {
                // do nothing
            }
        }
        $descFontSize = 14;
        if ($splitpos > 25) 
            $descFontSize = 12;

        $desc = substr($desc,0,$splitpos);
        $desc2 = substr($temp,$splitpos);;
   }
   $brandFontSize = 18;
   if (strlen($brand) > 18) 
        $brandFontSize = 16;
    
   if ($desc == '') {
        $desc = $temp;
        $desc2 = '';
   }
   $pdf->SetFont($font,'B',$brandFontSize);  //Set the brand font
   $pdf->SetXY($x,$y);
   $pdf->Cell($width,5,'',0,1,'C',true);
   $pdf->SetX($x);
   $pdf->Cell($width,7,$brand,0,1,'C',true);
   $pdf->SetX($x);
   $pdf->Cell($width,1,'',0,1,'C',true);
   $pdf->SetX($x);
   $pdf->SetFont($font,'',$descFontSize);  //Set the description font 
   $pdf->Cell($width,5,$desc,0,1,'C',true);
   $pdf->SetX($x);
   $pdf->Cell($width,7,$desc2,0,1,'C',true);

   $pdf->SetFont($font,'B',20);  //change font size
   $pdf->SetXY($x,$y+24);
   $pdf->Cell($width,12,$price,0,0,'C',true);
   $pdf->SetXY($x,$y+36);
   $pdf->Cell($width,12,'',0,0,'C',true);

   // add guide-lines
   $pdf->SetFillColor(155, 155, 155);
   // horizontal lines
   $pdf->SetXY($x+5,$y+34);
   $pdf->Cell(5,0.1,'',0,0,'C',true);
   $pdf->SetXY($x+60,$y+34);
   $pdf->Cell(5,0.1,'',0,0,'C',true);
   $pdf->SetXY($x,$y);
   $pdf->Cell(5,0.1,'',0,0,'C',true);
   $pdf->SetXY($x+60,$y);
   $pdf->Cell(5,0.1,'',0,0,'C',true);
   // vertical lines
   $pdf->SetXY($x,$y);
   $pdf->Cell(0.1,5,'',0,0,'C',true);
   $pdf->SetXY($x,$y+64);
   $pdf->Cell(0.1,5,'',0,0,'C',true);

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

