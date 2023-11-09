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
class WFC_Produce_Single_PDF extends FpdfWithBarcode
{
    private $tagdate;
    function setTagDate($str){
        $this->tagdate = $str;
    }

    function barcodeText($x, $y, $h, $barcode, $len)
    {
        if ($h != 4) {
            $this->SetFont('Arial','',8);
            $this->Text($x,$y-$h+(17/$this->k),substr($barcode,-$len).' '.$this->tagdate);
        } else {
            $this->SetFont('Arial','',9);
            $this->Text($x,$y+$h+11/$this->k,substr($barcode,-$len));
        }
    }
}

function WFC_Produce_Single($data,$offset=0){

$pdf=new WFC_Produce_Single_PDF('L','mm',array(25.4, 53.975)); //start new instance of PDF
$pdf->Open(); //open new PDF Document
$pdf->setTagDate(date("m/d/Y"));
$dbc = FannieDB::get(FannieConfig::config('OP_DB'));

$font = 'Arial';
if (FanniePlugin::isEnabled('CoopDealsSigns')) {
    $font = 'Gill';
    define('FPDF_FONTPATH', dirname(__FILE__) . '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
}

$width = 52; // tag width in mm
$height = 31; // tag height in mm
$left = 2.5; // left margin
$top = 0; // top margin
$bTopOff = 0;

$pdf->SetTopMargin($top);  //Set top margin of the page
$pdf->SetLeftMargin($left);  //Set left margin of the page
$pdf->SetRightMargin($left);  //Set the right margin of the page
$pdf->SetAutoPageBreak(False); // manage page breaks yourself
$pdf->AddPage();  //Add page #1

$num = 1; // count tags
// full size tag settings
$full_x = $left;
$full_y = $top;

// half size tag settings
$upcX = 7;  //x location of barcode
$upcY = $top; //y locaton of barcode
$priceY = 14 + $top; //y location of size and price on label
$priceX = 8; //x location of date and price on label
$count = 0;  //number of labels created
$baseY = 31 + $bTopOff; // baseline Y location of label
$baseX = 6;  // baseline X location of label
$down = 31.0;

$organicFlag = (1 << (17 - 1));
$upcs = array_map(function ($i) { return $i['upc']; }, $data);
list($inStr, $args) = $dbc->safeInClause($upcs);
$prep = $dbc->prepare("SELECT upc, description FROM productUser WHERE upc IN ({$inStr}) AND description <> '' AND description IS NOT NULL");
$res = $dbc->execute($prep, $args);
$names = array();
while ($row = $dbc->fetchRow($res)) {
    $names[$row['upc']] = $row['description'];
}
$prep = $dbc->prepare("SELECT upc, origin FROM upcLike AS u left join likeCodes AS l ON u.likeCode=l.likeCode
    WHERE upc IN ({$inStr}) and origin <> '' AND origin IS NOT NULL");
$res = $dbc->execute($prep, $args);
$origins = array();
while ($row = $dbc->fetchRow($res)) {
    $origins[$row['upc']] = $row['origin'];
}

//cycle through result array of query
foreach($data as $row) {
    // extract & format data

    $price = $row['normal_price'];
    $desc = $row['description'];
    if (!isset($row['signCount']) && isset($names[$row['upc']])) {
        $desc = $names[$row['upc']];
    }

    // writing data
    // basically just set cursor position
    // then write text w/ Cell
    $pdf->SetXY($full_x,$full_y+2);
    $descY = $full_y+2;
    $maxLines = 2;
    if (($row['numflag'] & $organicFlag) != 0) {
        $pdf->SetFont($font,'B',10);  //Set the font
        $pdf->Cell($width,4,'ORGANIC',0,1,'L');
        $descY += 4;
        $maxLines = 1;
    } else if ($row['upc'] === '' && strtolower(trim($row['brand'])) == 'organic') {
        $pdf->SetFont($font,'B',10);  //Set the font
        $pdf->Cell($width,4,'ORGANIC',0,1,'L');
        $descY += 4;
        $maxLines = 1;
    }
    $trySize = 12;
    $pdf->SetFillColor(0xff, 0xff, 0xff);
    while ($trySize > 0) {
        $pdf->SetXY($full_x,$descY);
        $pdf->SetFont($font,'',$trySize);  //Set the font
        $pdf->MultiCell($width,5,$desc,0,'L');
        $newY = $pdf->GetY();
        if ($newY > (5*$maxLines + $descY)) {
            $trySize--;
            $pdf->Rect($full_x, $descY, $width, ($newY - $descY), 'F');
        } else {
            break;
        }
    }
    if ($trySize == 0) {
        $pdf->SetXY($full_x,$descY);
        $pdf->SetFont($font,'',12);  //Set the font
        $pdf->MultiCell($width,5,$desc,0,'L');
    }
    $pdf->SetFont($font,'',8);  //Set the font
    if (isset($origins[$row['upc']])) {
        $pdf->SetXY($full_x,$full_y+20);
        $pdf->Cell($width,4,strtoupper($origins[$row['upc']]),0,1,'L');
    } else if ($row['upc'] === '' && isset($row['originName']) && strlen($row['originName']) > 0) {
        $pdf->SetXY($full_x,$full_y+20);
        $pdf->Cell($width,4,strtoupper($row['originName']),0,1,'L');
    }

    $pdf->SetFont($font,'B',30);  //change font size
    $pdf->SetXY($full_x,$full_y+12);
    $pdf->Cell($width-5,8,$price . ($row['scale'] ? '/lb' : '/ea'),0,0,'R');

    // move right by tag width

    // full size
    $full_x += $width;

    // if it's the end of a page, add a new
    // one and reset x/y top left margins
    // otherwise if it's the end of a line,
    // reset x and move y down by tag height
    if ($num < count($data)) {
        $pdf->AddPage();
    }
    // full size
    $full_x = $left;
    $full_y = $top;

    $num++;
}

    $pdf->Output();  //Output PDF file to screen.
}

