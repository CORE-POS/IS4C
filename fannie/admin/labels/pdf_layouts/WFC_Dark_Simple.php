<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class WFC_Dark_Simple_PDF extends FpdfWithBarcode
{
    private $tagdate;
    public function setTagDate($str){
        $this->tagdate = $str;
    }

    public function barcodeText($x, $y, $h, $barcode, $len)
    {
        $this->SetFont('Arial','',8);
        $this->Text($x,$y-$h+(17/$this->k),substr($barcode,-$len).' '.$this->tagdate);
    }

    static public function stringToLines($string) {
        $length = strlen($string);
        $lines = array();
        // return 1 to 4 lines based on $desc size
        if ($length < 22) {
            $lines[] = $string;
        } else if ($length < 40) {
            $wrp = wordwrap($string, 22, "*", false);
            $lines = explode('*', $wrp);
        } else if ($length < 60) {
            $wrp = wordwrap($string, 22, "*", false);
            $lines = explode('*', $wrp);
        } else {
            $wrp = wordwrap($string, 22, "*", false);
            $lines = explode('*', $wrp);
        }

        return $lines;
    }
}

function WFC_Dark_Simple ($data,$offset=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new WFC_Dark_Simple_PDF('L','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);

    define('FPDF_FONTPATH', __DIR__ . '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill', 'B', 16); 

    $width = 68;
    $height = 34;
    $left = 3;  
    $top = 3;
    $guide = 0.3;

    $x = $left+$guide; $y = $top+$guide;

    $pdf->SetTopMargin($top); 
    $pdf->SetLeftMargin($left);
    $pdf->SetRightMargin($left);
    $pdf->SetAutoPageBreak(False);

    $i = 0;
    foreach($data as $k => $row){
        if ($i % 24 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateSimpleTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        } else if ($i % 4 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }
        $pdf = generateSimpleTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        $i++;
    }

    /*
        Print additional mirror images for back side of tags
    */
    $i = 0;
    $x = $left+$guide; $y = $top+$guide;
    if (count($data) % 4 != 0) {
        for ($j=count($data) % 4; $j<4; $j++) {
            $data[] = '';
        }
    }
    $data = arrayMirrorRowsSimple($data, 4);
    $pdf->AddPage('L');
    foreach($data as $k => $row){
        if ($i % 24 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateMirrorTagSimple($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        } else if ($i % 4 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }
        $pdf = generateMirrorTagSimple($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        $i++;
    }

    $pdf = $pdf->Output();
}

function generateMirrorTagSimple($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
{
    $upc = $row['upc'];
    $desc = $row['description'];
    $brand = $row['brand'];
    $price = $row['normal_price'];
    $vendor = $row['vendor'];
    $size = $row['size'];
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Gill','', 9);

    // prep tag canvas
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 0, 1, 'C', true); 

    /*
        Add UPC Text
    */
    $pdf->SetXY($x,$y+4);
    $pdf->Cell($width, 8, $upc, 0, 1, 'C', true); 

    /*
        Add Brand & Description Text
    */
    $pdf->SetXY($x,$y+12);
    $pdf->Cell($width, 5, $brand, 0, 1, 'C', true); 
    $pdf->SetXY($x,$y+18);
    $pdf->Cell($width, 5, $desc, 0, 1, 'C', true); 

    /*
        Add Vendor Text
    */
    $pdf->SetXY($x,$y+27);
    $pdf->Cell($width, 5, $vendor, 0, 1, 'L', true); 

    /*
        Add Size Text
    */
    if ($size > 0) {
        $pdf->SetXY($x+52,$y+27);
        $pdf->Cell('15', 5, $size, 0, 1, 'R', true); 
    }


    /*
        Create Guide-Lines
    */ 
    $pdf->SetFillColor(155, 155, 155);
    // vertical 
    $pdf->SetXY($width+$x, $y);
    $pdf->Cell($guide, $height+$guide, '', 0, 1, 'C', true);

    $pdf->SetXY($x-$guide, $y-$guide); 
    $pdf->Cell($guide, $height+$guide, '', 0, 1, 'C', true);

    // horizontal
    $pdf->SetXY($x, $y-$guide); 
    $pdf->Cell($width+$guide, $guide, '', 0, 1, 'C', true);

    $pdf->SetXY($x, $y+$height); 
    $pdf->Cell($width+$guide, $guide, '', 0, 1, 'C', true);

    $pdf->SetFillColor(0, 0, 0);

    return $pdf;

}

function generateSimpleTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
{
    $desc = $row['description'];
    $args = array($row['upc']);
    $prep = $dbc->prepare("
        SELECT pu.description, p.scale
        FROM productUser AS pu
            INNER JOIN products AS p ON pu.upc=p.upc
        WHERE pu.upc = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);
    $desc = $row['description'];

    // prep tag canvas
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 0, 1, 'C', true); 

    $lines = WFC_Dark_Simple_PDF::stringToLines($desc);
    if (strstr($desc, "\r\n")) {
        $lines = explode ("\r\n", $desc);
    }

    /*
        Add Description Text
    */
    $pdf->SetFont('Gill','B', 16);  //Set the font 
    $lineCount = count($lines);
    if ($lineCount == 2) {
        $pdf->SetXY($x,$y+12);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+19);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
    } elseif ($lineCount == 3) {
        $pdf->SetXY($x,$y+8);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+15);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+22);
        $pdf->Cell($width, 5, $lines[2], 0, 1, 'C', true); 
    } elseif ($lineCount == 4) {
        $pdf->SetXY($x,$y+4);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+11);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+18);
        $pdf->Cell($width, 5, $lines[2], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+25);
        $pdf->Cell($width, 5, $lines[3], 0, 1, 'C', true); 
    } else {
        $pdf->SetXY($x,$y+15);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
    }

    /*
        Create Guide-Lines
    */ 
    $pdf->SetFillColor(155, 155, 155);
    // vertical 
    $pdf->SetXY($width+$x, $y);
    $pdf->Cell($guide, $height+$guide, '', 0, 1, 'C', true);

    $pdf->SetXY($x-$guide, $y-$guide); 
    $pdf->Cell($guide, $height+$guide, '', 0, 1, 'C', true);

    // horizontal
    $pdf->SetXY($x, $y-$guide); 
    $pdf->Cell($width+$guide, $guide, '', 0, 1, 'C', true);

    $pdf->SetXY($x, $y+$height); 
    $pdf->Cell($width+$guide, $guide, '', 0, 1, 'C', true);

    $pdf->SetFillColor(0, 0, 0);

    return $pdf;
}

function arrayMirrorRowsSimple($array, $cols)
{
    $newArray = array();
    $chunks = array_chunk($array, $cols);
    foreach ($chunks as $chunk) {
        $chunk = array_reverse($chunk);
        foreach ($chunk as $v) {
            $newArray[] = $v;
        }
    }

    return $newArray;
}
