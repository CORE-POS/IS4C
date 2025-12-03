<?php
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FpdfLib')) {
    include(dirname(__FILE__) . '/FpdfLib.php');
}

class WFC_HerbNspice_Flat_PDF extends FpdfWithBarcode
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

function WFC_HerbNspice_Flat($data,$offset=0,$showPrice=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new WFC_HerbNspice_Flat_PDF('L','mm','Letter');
    $pdf->AddPage();
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);

    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

    $width = 68;
    //$height = 15;
    $height = 13;
    $left = 3;  
    $top = 5;
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
        if ($i % 40 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateHerbNspiceFlatLabel($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo);
        } else if ($i % 4 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }

        $pdf = generateHerbNspiceFlatLabel($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo);
        $i++;
        $tagNo++;
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
    $data = arrayMirrorRowsHerbsFlat($data, 4);
    $pdf->AddPage('L');
    foreach($data as $k => $row){
        if ($i % 40 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateHerbFlatMirror($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        } else if ($i % 4 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }
        $pdf = generateHerbFlatMirror($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        $i++;
    }

    $pdf = $pdf->Output();
}

function generateHerbNspiceFlatLabel($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo)
{
    $pdf->SetFont('Gill','', 16);
    define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $signage = new COREPOS\Fannie\API\item\FannieSignage(array());
    $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
    $upc = $row['upc'];
    $sku = $row['sku'];
    $brand = strToUpper($row['brand']);
    $price = $row['normal_price'];
    $vendor = $row['vendor'];
    $vendor = FpdfLib::parseVendorName($vendor, 10);
    $vendor = strtoupper($vendor);
    $vendor = str_replace(" ", ". ", $vendor);
    $vendor = "$vendor.";

    $descFontSize = 26;
    $descFontSizeBig = 24;
    $rgb = array();

    $mtMod = $store == 1 ? 3 : 7;
    $signage = new COREPOS\Fannie\API\item\FannieSignage(array());
    $mtP = $dbc->prepare('SELECT p.auto_par
        FROM MovementTags AS m 
            INNER JOIN products AS p ON m.upc=p.upc AND m.storeID=p.store_id
        WHERE m.upc=? AND m.storeID=?');
    $updateMT = $dbc->prepare('
        UPDATE MovementTags
        SET lastPar=?,
            modified=' . $dbc->now() . '
        WHERE upc=?
            AND storeID=?');
    $mtText = $dbc->getValue($mtP, array($upc, $store));
    $mtText *= $mtMod;
    $mtText = round($mtText, 1);
    $dbc->execute($updateMT, array(($mtText*$mtMod), $upc, $store));

    $args = array($row['upc']);
    $prep = $dbc->prepare("
        SELECT pu.description, p.scale, p.auto_par, pu.long_text
        FROM productUser AS pu
            INNER JOIN products AS p ON pu.upc=p.upc
        WHERE pu.upc = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);

    $MdescKey = array_search($upc, $updateUpcs);
    $Mdesc = $manualDescs[$MdescKey];

    $desc = $Mdesc;
    $desc = str_replace("\n", "", $desc);
    $par = $row['auto_par'];

    // get scale info
    $prep = $dbc->prepare("
        SELECT plu
        FROM scaleItems
        WHERE plu = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);
    $scale = ($row['plu'] > 0) ? 1 : 2;

    // Prep Tag Canvas
    $pdf->SetDrawColor(200,200,200);
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 1, 1, 'C', true);
    $pdf->SetDrawColor(0,0,0);

    $step = 6;

    /*
        Add Barcodes
    */
    $pdf->SetFillColor(0,0,0);
    if (strlen($sku) < 9 && is_numeric($sku)) {
        // if len of str too long, don't print as it will not fit
        if (class_exists('Image_Barcode2')) {
            $img = Image_Barcode2::draw($sku, 'code128', 'png', false, 20, 1, false);
            $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
            imagepng($img, $file);
            $pdf->Image($file, $x-1, $y-1+$step*2);
            unlink($file);
        }
    }
    $pdf->SetFillColor(255,255,255);
    $pdf->SetFont('Gill','B', 16.5);


    // cover up 1
    // top
    //$pdf->SetFillColor(0,255,0);
    //$pdf->Rect($x+19, $y+-4+$step*2, 10, 2, 'F');
    // bottom
    $pdf->SetFillColor(255,255,255);
    $pdf->Rect($x+2, $y+1+$step*2, $width-25, 7, 'F');

    /* UPC / PLU Barcode */
    $pdf->SetFillColor(0,0,0);
    $pdf->EAN13($x+42, $y-5+$step*2, substr($upc, -3), 4, 0.25);  //generate barcode and place on label
    $pdf->SetFillColor(255,255,255);

    // cover up 2
    //$pdf->SetFillColor(255,000,000);
    $pdf->SetFillColor(255,255,255);
    $pdf->Rect($x+42, $y-6+$step*2, 25, 3, 'F');
    $pdf->SetFillColor(255,255,255);


    /*
        Add PLU
    */
    $pdf->SetFont('Gill','', 14);
    $pdf->SetXY($x+38,$y-3.5+$step);
    $pdf->Cell(25, 4, 'PLU', 0, 1, 'L', true);

    $pdf->SetFont('Gill','B', $descFontSizeBig);
    $pdf->SetXY($x+48,$y+1);
    $pdf->Cell(8, 8, substr($upc, -3), 0, 1, 'L', true);

    $pdf->SetFont('Gill','B', $descFontSize);

    /*
        Add Vendor Info
    */
    $pdf->SetFont('Gill','', 8.5);
    $pdf->SetXY($x+1, $y-10+$step*3);
    $pdf->Cell(18, 2, $vendor, 0, 1, 'L', true);

    /*
        Add SKU if numeric
    */
    if (is_numeric($sku)) {
        $pdf->SetXY($x+18, $y-10+$step*3);
        $pdf->Cell(18, 2, $sku, 0, 1, 'L', true);
    }
    $pdf->SetFont('Gill','B', 16.5);

    /*
        Add Price
    */
    $priceText = '$'.$price.'/LB';
    $pdf->SetXY($x+12, $y-4+$step*1);
    $pdf->Cell(10, 5, $priceText, 0, 1, 'C', true);

    /*
        Add Auto Par
    $pdf->SetFont('Gill','B', 6);
    $pdf->SetXY($x+67,$y+1.5);
    $pdf->Cell(1, 1, $mtText, 0, 1, 'R', true);
    */


    return $pdf;
}


function arrayMirrorRowsHerbsFlat($array, $cols)
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

function generateHerbFlatMirror($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
{
    $y +=2;
    $upc = isset($row['upc']) ? $row['upc'] : '';
    $desc = isset($row['description']) ? $row['description'] : '';
    $brand = isset($row['brand']) ? $row['brand'] : '';

    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('Gill','', 9);

    // prep tag canvas
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 0, 1, 'C', true); 

    /*
        Add UPC Text
    */
    $pdf->SetXY($x,$y-1);
    $pdf->Cell(15, 8, $upc, 0, 1, 'L', true); 

    /*
        Add Brand & Description Text
    */
    $pdf->SetXY($x,$y+4);
    $pdf->Cell($width, 5, $brand, 0, 1, 'L', true); 
    $pdf->SetXY($x,$y+8);
    $pdf->Cell($width, 5, $desc, 0, 1, 'L', true); 

    /*
        Add Vendor Text
    */
    $pdf->SetXY($x,$y+25);
    $pdf->Cell($width, 5, $vendor, 0, 1, 'L', true); 

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

    $pdf->SetFillColor(100, 100, 100);

    return $pdf;

}
