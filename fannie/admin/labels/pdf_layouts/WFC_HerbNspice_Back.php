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

class WFC_HerbNspice_Back_PDF extends FpdfWithBarcode
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

function WFC_HerbNspice_Back($data,$offset=0,$showPrice=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new WFC_HerbNspice_Back_PDF('P','mm',array(105, 148));
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
            $pdf = generateHerbNsingleBackLabel($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo);
        }
        $pdf = generateHerbNsingleBackLabel($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo);
        $x = $left+$guide;
        $y += $height+$guide+5;
        $i++;
        $tagNo++;
    }

    $pdf = $pdf->Output();
}

function generateHerbNsingleBackLabel($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo)
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


    if ($item['organicLocal']) {
        $rgb = array(151,201,61);
    } elseif ($item['organic']) {
        $rgb = array(151,201,61);
    } elseif ($item['local']) {
        $rgb = array(151,22,8); // alternate frontier red
    } else {
        $rgb = array(151,22,8); // alternate frontier red
    }

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
    //    $pdf->SetXY($x+1, $y+$j);
    //    $pdf->Cell($width-22, 5, $line, 0, 1, 'C', true);
        $j = $tmpBaseY + (5 * $i);
        $pdf->SetXY($x+1, $y+$j);
        $pdf->Cell($width-22, 5, $line, 0, 1, 'C', true);
    }

    $pdf->SetFont('Gill','B', $descFontSize);


    /*
        Adding Colorful Lines 
    */
    // all non-og labels 65,72,80 = grey
    $pdf->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);
    $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
    // organic labels (151,210,61)

    $pdf->Rect($x-1, $y+0.45, $width-17.90, 2.1, 'F');

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



    return $pdf;
}
