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
    $height = 20;
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
        Add PLU
    */
    $pdf->SetXY($x+34,$y+$step);
    $pdf->Cell(25, 4, 'PLU#', 0, 1, 'L', true);

    $pdf->SetFont('Gill','B', $descFontSizeBig);
    $pdf->SetXY($x+48,$y+4);
    $pdf->Cell(8, 8, substr($upc, -3), 0, 1, 'L', true);

    $pdf->SetFont('Gill','B', $descFontSize);
    /*
        Add Barcodes
    */
    $pdf->SetFillColor(0,0,0);
    // PLU Barcode
    $pdf->EAN13($x+42, $y+$step*2,substr($upc, -3),4,.25);  //generate barcode and place on label
    // SKU Barcode
    $pdf->EAN13($x+2, $y+$step*2,$sku,4,.25);  //generate barcode and place on label
    $pdf->SetFillColor(255,255,255);
    $pdf->SetFont('Gill','B', 16.5);

    // cover up numerical part of barcodes with white rectanglges
    $pdf->SetFillColor(255,255,255);
    $pdf->Rect($x+42, $y-1+$step*2, 25, 3, 'F');
    $pdf->Rect($x+2, $y-1+$step*2, 25, 3, 'F');
    $pdf->SetFillColor(255,255,255);

    /*
        Add Price
    */
    $priceText = '$'.$price.'/LB';
    $pdf->SetXY($x+12, $y-0.5+$step*1);
    $pdf->Cell(10, 5, $priceText, 0, 1, 'C', true);

    $pdf->SetFont('Gill','B', 6);
    $pdf->SetXY($x+57.5,$y+1.5);
    $pdf->Cell(10, 1, $mtText, 0, 1, 'R', true);


    return $pdf;
}
