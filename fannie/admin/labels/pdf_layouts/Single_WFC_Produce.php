<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
class Single_WFC_Produce_PDF extends FpdfWithBarcode
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

function Single_WFC_Produce($data,$offset=0){

$pdf=new Single_WFC_Produce_PDF('L', 'mm', array(148, 105)); //start new instance of PDF
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
$left = 25.5; // left margin
$top = 15; // top margin
$bTopOff = 0;

// undo margin if offset is true
if($offset) {
    $top = 32;
    $bTopOff = 17;
}

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
    $pdf->SetXY($full_x,$full_y+5);
    $descY = $full_y+5;
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
        $pdf->SetXY($full_x,$full_y+24);
        $pdf->Cell($width,4,strtoupper($origins[$row['upc']]),0,1,'L');
    } else if ($row['upc'] === '' && isset($row['originName']) && strlen($row['originName']) > 0) {
        $pdf->SetXY($full_x,$full_y+24);
        $pdf->Cell($width,4,strtoupper($row['originName']),0,1,'L');
    }

    $pdf->SetFont($font,'B',30);  //change font size
    $pdf->SetXY($full_x,$full_y+16);
    $pdf->Cell($width-5,8,$price . ($row['scale'] ? '/lb' : '/ea'),0,0,'R');

    /*
        Create Guidelines
    */
    $pdf->SetDrawColor(155,155,155);
    // print top and left guide lines only once
    $y = $full_y;
    $x = $full_x;

    // Vertical
    $pdf->Rect(0, $y-2, $width*3, 0.02, 'DF'); // full line
    if ($full_y > 100) {
        // Bottom Vert
        $pdf->Rect(0, $y+$height-2, $width*3, 0.02, 'DF'); // full line
    }
    //$pdf->Rect(0, $y-2, 10, 0.02, 'DF');
    //$pdf->Rect($x+$width-21, $y-2, 5, 0.02, 'DF');

    // Horizontal 
    $pdf->Rect($x-3.3+2, 0, 0.02, $height*5, 'DF'); // full line
    $pdf->Rect($x+55-3, 0, 0.02, $height*5, 'DF'); // full line
    //$pdf->Rect($x-3.3, $y+$height, 0.02, 5, 'DF');
    //$pdf->Rect($x-3.3, 5, 0.02, 5, 'DF');
    $pdf->SetDrawColor(0xff, 0xff, 0xff);
    // END Guidelines


    // move right by tag width

    // full size
    $full_x += $width;

    // if it's the end of a page, add a new
    // one and reset x/y top left margins
    // otherwise if it's the end of a line,
    // reset x and move y down by tag height
    if ($num % 4 == 0){
    $pdf->AddPage();
        // full size
        $full_x = $left;
        $full_y = $top;

    } elseif ($num % 1 == 0){
        // full size
        $full_x = $left;
        $full_y += $height;
    }

    $num++;
}

    $pdf->Output();  //Output PDF file to screen.
}

