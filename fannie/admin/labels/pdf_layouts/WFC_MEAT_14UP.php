<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('Store')) {
    include(__DIR__ . '/../../classlib2.0/lib/Store.php');
}

class WFC_MEAT_14UP_PDF extends FpdfWithBarcode
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

}

function WFC_MEAT_14UP($data,$offset=0,$showPrice=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new WFC_MEAT_14UP_PDF('L','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);

    define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

    $width = 136;
    $height = 29;
    $left = 3;
    $top = 3;
    $guide = 0.3;

    $x = $left+$guide; $y = $top+$guide;

    $pdf->SetTopMargin($top);
    $pdf->SetLeftMargin($left);
    $pdf->SetRightMargin($left);
    $pdf->SetAutoPageBreak(False);

    $i = 0;
    $tagNo = 0;
    foreach($data as $k => $row){
        $upc = $row['upc'];
        if ($i % 14 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateMeat_24UPTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo);
            //can't get UPC_A to work, get FPDF error: Could not include font metric file
            //if (strlen($upc) <= 11)
            //    $pdf->UPC_A($x,$y,$upc,7);  //generate barcode and place on label
            //else
            //    $pdf->EAN13($x,$y,$upc,7);  //generate barcode and place on label
        } else if ($i % 2 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }
        $pdf = generateMeat_24UPTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo);
        $i++;
        $tagNo++;
    }

    /*
        Print additional mirror images for back side of tags
    */
    $i = 0;
    $x = $left+$guide; $y = $top+$guide;
    if (count($data) % 2 != 0) {
        for ($j=count($data) % 2; $j<2; $j++) {
            $data[] = '';
        }
    }
    $data = arrayMirrorRowsMeat_24UP($data, 2);
    $pdf->AddPage('L');
    foreach($data as $k => $row){
        if ($i % 14 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateMirrorMeatTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        } else if ($i % 2 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }
        $pdf = generateMirrorMeatTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        $i++;
    }

    $pdf = $pdf->Output();
}


function generateMirrorMeatTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
{
    $upc = isset($row['upc']) ? $row['upc'] : '';
    $desc = isset($row['description']) ? $row['description'] : '';
    $brand = isset($row['brand']) ? $row['brand'] : '';
    $price = isset($row['normal_price']) ? $row['normal_price'] : '';
    $vendor = isset($row['vendor']) ? $row['vendor'] : '';
    $size = isset($row['size']) ? $row['size'] : '';
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Gill','', 9);

    $args = array($row['upc']);
    $prep = $dbc->prepare("
        SELECT pu.description, p.scale, p.auto_par, v.sku
        FROM productUser AS pu
            INNER JOIN products AS p ON pu.upc=p.upc
            INNER JOIN vendorItems AS v ON p.default_vendor_id=v.vendorID AND p.upc=v.upc
        WHERE pu.upc = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);
    $desc = $row['description'];
    $desc = str_replace("\n", "", $desc);
    $desc = str_replace("\r", "", $desc);
    $par = $row['auto_par'];
    $sku = $row['sku'];
    $date = new DateTime();

    // prep tag canvas
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 0, 1, 'C', true);

    /*
        Add UPC & SKU Text
    */
    $pdf->SetXY($x,$y+3);
    $pdf->Cell($width, 8, $upc, 0, 1, 'L', true);

    /*
        Add Brand & Description Text
    */
    $pdf->SetXY($x,$y+9);
    $pdf->Cell($width, 5, $brand, 0, 1, 'L', true);
    $pdf->SetXY($x,$y+14);
    $pdf->Cell($width, 5, $desc, 0, 1, 'L', true);


    /*
        Add Vendor Text
    */
    $pdf->SetXY($x,$y+19);
    $pdf->Cell($width, 5, $vendor.', '.$sku, 0, 1, 'L', true);

    /*
        Add Size Text
    */
    if ($size > 0) {
        $pdf->SetXY($x,$y+24);
        $pdf->Cell(100, 5, $size, 0, 1, 'L', true);
    }

    /*
        Add Date Text
    */
    if ($size > 0) {
        $pdf->SetXY($x+118,$y+24);
        $pdf->Cell(100, 5, $date->format('Y-m-d'), 0, 1, 'L', true);
    }

    /*
        Add Movement Text
    */
    //add border
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetXY($x+124,$y+1.5);
    $pdf->Cell(10.5, 6, '', 1, 0, 'C', true);
    $pdf->SetFillColor(255, 255, 255);

    $store = FormLib::get('store');
    $movement = ($store == 1) ? $par * 3 : $par * 7;
    if ($scale == 1) {
        $priceText .= "/LB";
        $pxMod -= 3;
    }
    $pdf->SetFont('Gill', '', 12);
    $pdf->SetXY($x+124,$y+2);
    $pdf->Cell(10, 5, round($movement,1), 0, 1, 'C', true);
    $pdf->SetFont('Gill','B', 16);


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

function generateMeat_24UPTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo)
{
    $upc = $row['upc'];
    $brand = strToUpper($row['brand']);
    $price = $row['normal_price'];
    $descFontSize = 20;
    
    $updateUpcs = FormLib::get('update_upc');
    $manualDescs = FormLib::get('update_desc');

    $args = array($row['upc']);
    $prep = $dbc->prepare("
        SELECT pu.description, p.scale, p.auto_par
        FROM productUser AS pu
            INNER JOIN products AS p ON pu.upc=p.upc
        WHERE pu.upc = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);

    $MdescKey = array_search($upc, $updateUpcs);
    $Mdesc = $manualDescs[$MdescKey];

    $desc = $Mdesc;
    $desc = str_replace("\n", "", $desc);
    $desc = str_replace("\r", "", $desc);
    $par = $row['auto_par'];

    // get scale info
    $prep = $dbc->prepare("
        SELECT plu
        FROM scaleItems
        WHERE plu = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);
    $scale = ($row['plu'] > 0) ? 1 : 2;

    // prep tag canvas
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 0, 1, 'C', true);

    /*
        Add Brand Text
    */
    $pdf->SetFont('Gill','', 14);
    $strlen = (strlen($brand));
    $pdf->SetXY($x,$y+15);
    $pdf->Cell($width, 8, $brand, 0, 1, 'C', true);

    /*
        Add Description Text
    */
    $length = strlen($desc);
    if ($length > 26) {
        $descFontSize = 18;
    }
    if ($length > 35) {
        $descFontSize = 16;
    }
    if ($length > 45) {
        $descFontSize = 14;
    }
    $pdf->SetFont('Gill','B', $descFontSize);
    $pdf->SetXY($x,$y+6);
    $pdf->Cell($width, 5, $desc, 0, 1, 'C', true);
    $pdf->SetFont('Gill','B', 16);

    /*
        Add Price Text
    */
    $priceText = '$'.$price;
    $pxMod = 118;
    if ($scale == 1) {
        $priceText .= "/LB";
        $pxMod -= 3;
    }
    $pdf->SetFont('Gill','B', 16);
    $pdf->SetXY($x+$pxMod,$y+22);
    if ($showPrice == 1 )
        $pdf->Cell(10, 5, $priceText, 0, 1, 'C', true);


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

function arrayMirrorRowsMeat_24UP($array, $cols)
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
