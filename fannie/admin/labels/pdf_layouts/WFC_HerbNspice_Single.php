<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FpdfLib')) {
    include(dirname(__FILE__) . '/FpdfLib.php');
}

class WFC_HerbNspice_Single_PDF extends FpdfWithBarcode
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

function WFC_HerbNspice_Single($data,$offset=0,$showPrice=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new WFC_HerbNspice_Single_PDF('P','mm',array(105, 148));
    $pdf->AddPage();
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);

    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

    $width = 96;
    $height = 58;
    $left = 10;
    $top = 10;
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
        if ($i % 2 == 0 && $i != 0) {
            $pdf->AddPage('P');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateHerbNsingleSpiceLabel($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo);
        }
        $pdf = generateHerbNsingleSpiceLabel($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo);
        $x = $left+$guide;
        $y += $height+$guide+5;
        $i++;
        $tagNo++;
    }

    $pdf = $pdf->Output();
}

function generateHerbNsingleSpiceLabel($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo)
{
    $x += 5;
    $pdf->SetFont('Gill','', 16);
    if (!defined('FPDF_FONTPATH')) {
        define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    }
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

    $scaleA = array($row['upc']);
    $scaleP = $dbc->prepare("
        SELECT linkedPLU, sku
        FROM scaleItems AS s 
            LEFT JOIN products AS p ON p.upc=s.linkedPLU
            LEFT JOIN vendorItems AS v ON v.upc=p.upc AND v.vendorID=p.default_vendor_id
        WHERE plu = ?");
    $scaleR = $dbc->execute($scaleP, $scaleA);
    $scaleW = $dbc->fetchRow($scaleR);
    $sku = (is_array($scaleW) && $scaleW['sku'] > 1) ? $scaleW['sku'] : $sku;

    $pScaleA = array($row['upc']);
    $pScaleP = $dbc->prepare("SELECT scale FROM products WHERE upc = ? LIMIT 1");
    $pScale = $dbc->getValue($pScaleP, $pScaleA);

    $basicP = $dbc->prepare("SELECT
        CASE WHEN pr.priceRuleTypeID = 6 OR pr.priceRuleTypeID = 12 THEN 1 ELSE 0 END
        FROM products AS p
            LEFT JOIN PriceRules AS pr ON p.price_rule_id=pr.priceRuleID
        WHERE upc = ?;");
    $organicLocalP = $dbc->prepare("SELECT 'true' FROM products WHERE numflag & (1<<16) != 0 AND upc = ? AND local > 0");
    $organicP = $dbc->prepare("SELECT 'true' FROM products WHERE numflag & (1<<16) != 0 AND upc = ?");
    $localP = $dbc->prepare("SELECT 'true' FROM products WHERE local > 0 AND upc = ?");

    $item = array();
    $item['basic'] = $dbc->getValue($basicP, $row['upc']);
    $item['organicLocal'] = $dbc->getValue($organicLocalP, $row['upc']);
    $item['organic'] = $dbc->getValue($organicP, $row['upc']);
    $item['local'] = $dbc->getValue($localP, $row['upc']);

    $updateUpcs = FormLib::get('update_upc');
    $manualDescs = FormLib::get('update_desc');

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
    $desc = str_replace("\r", "", $desc);
    $par = $row['auto_par'];
    $backText = $row['long_text'];

    // get scale info
    $prep = $dbc->prepare("
        SELECT plu
        FROM scaleItems
        WHERE plu = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);
    $scale = (is_array($row) && $row['plu'] > 0) ? 1 : 2;

    // prep tag canvas
    $pdf->SetDrawColor(200,200,200);
    $pdf->SetXY($x,$y);
    $pdf->Cell($width-20, $height, '', 1, 1, 'C', true);
    $pdf->SetXY($x+$width,$y);
    $pdf->Cell($width-20, $height, '', 0, 1, 'C', true);
    $pdf->SetDrawColor(0,0,0);

    if ($item['organicLocal']) {
        $pdf->Image(__DIR__ . '/noauto/og-loc.png', $x-2, $y, 80, 15);
        $rgb = array(151,201,61);
    } elseif ($item['organic']) {
        $pdf->Image(__DIR__ . '/noauto/og.png', $x-2, $y, 80, 15);
        $rgb = array(151,201,61);
    } elseif ($item['local']) {
        //$pdf->Image(__DIR__ . '/noauto/conv-loc.png', $x-2, $y, 80, 15);
        $pdf->Image(__DIR__ . '/noauto/red-conv-loc.png', $x-2, $y, 80, 15);
        //$rgb = array(65,72,80);
        $rgb = array(151,22,8); // alternate frontier red
    } else {
        //$pdf->Image(__DIR__ . '/noauto/conv.png', $x-2, $y, 80, 15);
        $pdf->Image(__DIR__ . '/noauto/red-conv.png', $x-2, $y, 80, 15);
        //$rgb = array(65,72,80);
        $rgb = array(151,22,8); // alternate frontier red
    }

    /*
        Add Description Text
        This is the new / bigger size description text
    */

    $length = strlen($desc);
    $chrLimit = 10;
    $desc = str_replace('Organic', '', $desc);

    if (strlen($desc) < 26) {
        // Short Descriptions
        $fontSize = 24;
        $wrapSize = 15;
    } else {
        // Long Descriptions
        $fontSize = 20;
        $wrapSize = 18;
    }

    $lines = array();
    $pdf->SetFont('Gill','B', $fontSize);

    if (strstr($desc, "\r\n")) {
        $lines = explode ("\r\n", $desc);
    } else {
        $wrp = wordwrap($desc, $wrapSize, "*", false);
        $lines = explode('*', $wrp);
    }

    $tmpBaseY = 20;
    if (count($lines) == 1) $tmpBaseY = 26;
    foreach ($lines as $i => $line) {
        $j = $tmpBaseY + (10 * $i);
        $pdf->SetXY($x+1, $y+$j);
        $pdf->Cell($width-22, 5, $line, 0, 1, 'C', true);
    }


    $pdf->SetFont('Gill','B', 16);

    /*
        Add PLU
    */
    $pdf->SetXY($width-48,$y+40);
    $pdf->Cell(25, 8, 'PLU#', 0, 1, 'L', true);

    $pdf->SetFont('Gill','B', $descFontSizeBig);
    $pdf->SetXY($width-31,$y+40);
    $pdf->Cell(8, 8, substr($upc, -3), 0, 1, 'L', true);

    $pdf->SetFont('Gill','B', $descFontSize);
    /*
        Add Barcodes
    */
    $pdf->SetXY($width-50,$y+48);

    $pdf->SetFillColor(0,0,0);
    // PLU Barcode
    $pdf->EAN13($width-40.5, $y+49,substr($upc, -3),4,.25);  //generate barcode and place on label
    // SKU Barcode
    //$pdf->EAN13($x+5, $y+51,$sku,4,.25);  //generate barcode and place on label
    if (strlen($sku) < 9) {
        // if len of str too long, don't print as it will not fit
        if (class_exists('Image_Barcode2')) {
            $img = Image_Barcode2::draw($sku, 'code128', 'png', false, 20, 1, false);
            $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
            imagepng($img, $file);
            $pdf->Image($file, $x-2, $y+47);
            unlink($file);
        }
    }

    $pdf->SetFillColor(255,255,255);
    $pdf->SetFont('Gill','B', 16.5);

    /*
        Add Price
    */
    $vrbg = ($pScale == 0) ? '/EA' : '/LB';
    $priceText = '$'.$price.$vrbg;
    $pdf->SetXY($x+12, $y+41.5);
    $pdf->Cell(10, 5, $priceText, 0, 1, 'C', true);

    /*
        Add Vendor Text
    */
    $pdf->setTextColor(255,255,255);
    $pdf->setFillColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetFont('Gill','B', 6);
    $pdf->SetXY($width-22,$y+5.4);
    $pdf->Cell(10, 2, $vendor, 0, 1, 'R', true);
    $pdf->SetXY($width-22,$y+7.4);
    $pdf->Cell(10, 2, $sku, 0, 1, 'R', true);
    // Add Movement
    $pdf->SetXY($width-22,$y+3.4);
    $pdf->Cell(10, 2, $mtText, 0, 1, 'R', true);

    $pdf->setFillColor(255,255,255);
    $pdf->setTextColor(0,0,0);

    /*
        Add Back Label Info
        breakup $backText into lines
    */

    $bLines = array();
    $backText = strip_tags($backText);
    $backText = str_replace(["\r", "\n"], " ", $backText);

    $fontSize = 12; 
    $wrpSize =  40;
    if ($upc == 668) $fontSize = 8;
    if ($upc == 668) $wrpSize = 56;

    $wrp = wordwrap($backText, $wrpSize, "*", false);
    $bLines = explode('*', $wrp);
    $pdf->SetFont('Gill','', $fontSize);
    $tmpBaseY = 6;
    foreach ($bLines as $i => $line) {
        $j = $tmpBaseY + (5 * $i);
        $pdf->SetXY(105, $y+$j);
        $pdf->Cell(74, 5, $line, 0, 1, 'C', true);
    }

    $pdf->SetFont('Gill','B', $descFontSize);


    /*
        Adding Colorful Lines 
    */
    // all non-og labels 65,72,80 = grey
    $pdf->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    // organic labels (151,210,61)

    // top border (for back label only)
    $pdf->Rect($x+94, $y+2.59, $width-16, 2, 'DF');

    // left border
    $pdf->Rect($x-1, $y+2.59, 2, 55, 'DF');
    $pdf->Rect($x+94, $y+2.59, 2, 55, 'DF');

    // bottom border
    $pdf->Rect($x-1, $y+57, $width-18, 2, 'DF');
    $pdf->Rect($x+94, $y+57, $width-18, 2, 'DF');

    // right border
    $pdf->Rect($x+$width-21, $y+2.59, 2, 55, 'DF');
    $pdf->Rect($x+$width-21+97, $y+4, 2, 55, 'DF');

    $pdf->SetFillColor(255,255,255);
    // cover up numerical part of barcodes
    //$pdf->SetFillColor(255,0,0); 
    $pdf->Rect($x+1.5, $y+46, 44, 6.1, 'F');
    $pdf->Rect($x+1.5, $y+52, 44, 2, 'F');

    //$pdf->SetFillColor(255,255,255);

    $pdf->Rect($width-40.5, $y+48.9, 25, 2.1, 'F');

    // Redo Back Border for Single Signs
    //$pdf->Rect($x-1, $y+70, $width-18, 56, 'DF');
    //$pdf->Rect($x-0.75, $y+70.25, $width-16, 53.5, 'DF');

    return $pdf;
}
