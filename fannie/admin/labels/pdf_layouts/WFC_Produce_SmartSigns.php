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

class WFC_Produce_SmartSigns_PDF extends FpdfWithBarcode
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

function WFC_Produce_SmartSigns($data,$offset=0,$showPrice=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new WFC_Produce_SmartSigns_PDF('L','mm','Letter');
    $pdf->AddPage();
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);

    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

    $width = 96;
    $height = 62;
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
        $lc = false;
        $upc = $row['upc'];
        if (isset($row['likeCode']))
            $lc = $row['likeCode'];
        if ($i % 9 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateWFC_Produce_SmartSigns_label($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo, $lc);
        }  else if ($i % 3 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide-15;
        }
        $pdf = generateWFC_Produce_SmartSigns_label($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo, $lc);
        $i++;
        $tagNo++;
    }

    $pdf = $pdf->Output();
}

function generateWFC_Produce_SmartSigns_label($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $offset, $tagNo, $lc)
{
    $pdf->SetFont('Gill','', 16);
    if (!defined('FPDF_FONTPATH')) {
        define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    }
    $signage = new COREPOS\Fannie\API\item\FannieSignage(array());
    $upc = $row['upc'];
    $price = $row['normal_price'];

    $descFontSize = 26;
    $descFontSizeBig = 24;
    $rgb = array();

    /*
    $lc = false;
    */
    $likeCodes = FormLib::get('lc', false);
    $lcIndex = -1;
    if ($lc !== false) {
        $lcIndex = array_search($lc, $likeCodes);
    }
    $formOrigin = false;
    $overOrigins = FormLib::get('origin', false);
    if (isset($overOrigins[$lcIndex])) {
        $formOrigin = $overOrigins[$lcIndex];
    }
    $formPrice = FormLib::get('price', false);
    if (isset($rPrice[$lcIndex])) {
        $formPrice = $formPrice[$lcIndex];
    }

    $formPage = FormLib::get('form_page', false);
    $formScale = FormLib::get('scale', null);
    if ($formPage != 'ManualSignsPage') {
        // Use Like Code Batch Page index
        if (isset($formScale[$lcIndex])) {
            $formScale = $formScale[$lcIndex];
        }
    } else {
        // Use Manual Signs Page index
        if (isset($formScale[$tagNo])) {
            $formScale = $formScale[$tagNo];
        }
    }

    $formDesc = FormLib::get('desc', false);
    if (isset($formDesc[$lcIndex])) {
        $formDesc = $formDesc[$lcIndex];
    }
    $formOrganic = FormLib::get('brand', false); // [sic]
    if (isset($formOrganic[$tagNo])) {
        $formOrganic = trim($formOrganic[$tagNo]);
    }
    $formLocal = FormLib::get('local', false);
    if (isset($formLocal[$lcIndex])) {
        $formLocal = $formLocal[$lcIndex];
    }
    /*
        Manual Signs Form Data
    */
    $formDescription = FormLib::get('description', false);
    if (isset($formDescription[$tagNo])) {
        $formDescription = $formDescription[$tagNo];
    }


    if ($lc !== false) {
        $lcP = $dbc->prepare("SELECT likeCode, likeCodeDesc, upc, normal_price FROM likeCodeView WHERE likeCode = ? LIMIT 1");
        $lcR = $dbc->execute($lcP, array($lc));
        $lcW = $dbc->fetchRow($lcR);
        //$lcDesc = $lcW['likeCodeDesc'];
    }


    $originNames = array();
    $onP = $dbc->prepare("SELECT originID, fullName FROM originName");
    $onR = $dbc->execute($onP);
    while ($crow = $dbc->fetchRow($onR)) {
        $originNames[$crow['originID']] = $crow['fullName'];
    }

    $originP = $dbc->prepare("SELECT upc, origin FROM upcLike AS u left join likeCodes AS l ON u.likeCode=l.likeCode
        WHERE upc = ? and origin <> '' AND origin IS NOT NULL");
    $originR = $dbc->execute($originP, array($upc));
    while ($originW = $dbc->fetchRow($originR)) {
        $origin = $originW['origin'];
    }

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

    if (isset($formOrganic)) {
        if ($formOrganic != false) {
            if (strToLower($formOrganic) == 'organic') {
                $item['organic'] = true;
            } elseif (strToLower($formOrganic) == 'local organic') {
                $item['organicLocal'] = true;
            } elseif (strToLower($formOrganic) == 'organic local') {
                $item['organicLocal'] = true;
            } elseif (strToLower($formOrganic) == 'local') {
                $item['local'] = true;
            } else {
                $item['organic'] = false;
            }
        }
    }
    
    if ($lc != false) {
        $prep = $dbc->prepare("select organic from likeCodes where likeCode = ?");
        $lcOrganic = $dbc->getValue($prep, array($lc));
        $item['organic'] = $lcOrganic;

        //$prep = $dbc->prepare("select local from products p inner join upcLike u on u.upc=p.upc where u.likeCode = ? GROUP BY p.upc");
        //$lcLocal = $dbc->getValue($prep, array($lc));
        //$item['local'] = $lcLocal;

        //if ($item['local'] > 0 && $item['organic']) {
        //    $item['organicLocal'] = true;
        //}
    }
    if ($formLocal != false) {
        if ($formLocal == 1 || $formLocal == 2) {
            $item['local'] = true;
        }
        if (in_array($formLocal, array(1,2)) && $lcOrganic) {
            $item['organicLocal'] = true;
        }
    }

    // Form Data 
    $updateUpcs = FormLib::get('update_upc');
    $manualDescs = FormLib::get('update_desc');
    $manualOrigins = FormLib::get('update_origin');
    $customOrigins = FormLib::get('custom_origin');
    $originID = $manualOrigins[array_search($upc, $updateUpcs)];

    $mOrigin = $originNames[$originID];
    $cOrigin = $customOrigins[array_search($upc, $updateUpcs)];

    if (!isset($origin) && isset($row['originName'])) {
        $origin = $row['originName'];
    }

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

    if (isset($formDesc)) {
        if ($formDesc != false) {
            $Mdesc = $formDesc;
        }
    }
    if (isset($formDescription)) {
        if ($formDescription != false) {
            $Mdesc = $formDescription;
        }
    }

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
        $pdf->Image(__DIR__ . '/noauto/og-loc.png', $x-2, $y, 80, 15);
        $rgb = array(151,201,61);
    } elseif ($item['organic']) {
        $pdf->Image(__DIR__ . '/noauto/og.png', $x-2, $y, 80, 15);
        $rgb = array(151,201,61);
    } elseif ($item['local']) {
        $pdf->Image(__DIR__ . '/noauto/conv-loc.png', $x-2, $y, 80, 15);
        $rgb = array(72,72,79);
    } else {
        $pdf->Image(__DIR__ . '/noauto/conv.png', $x-2, $y, 80, 15);
        $rgb = array(72,72,79);
    }

    /*
        Add Description Text
        This is the new / bigger size description text
    */

    $desc = str_replace('Organic', '', $desc);

    if (strlen($desc) < 26) {
        // Short Descriptions
        $fontSize = 19;
        $wrapSize = 24;
    } else {
        // Long Descriptions
        $fontSize = 15;
        $wrapSize = 28;
    }

    $lines = array();
    $pdf->SetFont('Gill','', $fontSize);

    if (strstr($desc, "\r\n")) {
        $lines = explode ("\r\n", $desc);
    } else {
        $wrp = wordwrap($desc, $wrapSize, "*", false);
        $lines = explode('*', $wrp);
    }

    $tmpBaseY = 20;
    if (count($lines) == 1) $tmpBaseY = 23;
    foreach ($lines as $i => $line) {
        $j = $tmpBaseY + (10 * $i);
        $pdf->SetXY($x+1, $y+$j);
        $pdf->Cell($width-22, 5, $line, 0, 1, 'C', true);
    }


    $pdf->SetFont('Gill','B', 16);


    $pdf->SetFillColor(255,255,255);

    /*
        Add Price
    */
    $pdf->SetFont('Gill','B', 46);
    $vrbg = ($pScale == 0) ? '/ea' : '/lb';
    if (isset($formScale)) {
        $vrbg = ($formScale == 0) ? '/ea' : '/lb';
    }
    $priceText = '$'.$price.$vrbg;
    $pdf->SetXY($x, $y+38);
    $pdf->Cell($width-22, 10, $priceText, 0, 1, 'C', true);
    //$pdf->Cell($width-22, 10, $lc, 0, 1, 'C', true);

    /*
        Origins Text
    */
    if (isset($origin) || strlen($mOrigin) > 0 || strlen($cOrigin) > 0 || strlen($formOrigin) > 0) {
        if (isset($origin)) 
            $printOrigin = $origin;
        if (strlen($mOrigin) > 0)
            $printOrigin = $mOrigin;
        if (strlen($cOrigin) > 0)
            $printOrigin = $cOrigin;
        $pdf->SetFont('Gill','', 10);
        $pdf->SetXY($x, $y+52);
        if ($formOrigin != false) {
            $pdf->Cell($width-22, 4, 'Product of ' . $formOrigin, 0, 1, 'C', true);
        } else {
            $pdf->Cell($width-22, 4, 'Product of ' . $printOrigin, 0, 1, 'C', true);
        }
    }

    /*
        Guide Lines
    */
    $pdf->SetDrawColor(200,200,200);
    $pdf->SetFillColor(200,200,200);

    // print top and left guide lines only once
    if ($y < 20) {
        //top border
        //$pdf->Rect(0, $y-2, $width*3, 0.02, 'DF'); // full line
        $pdf->Rect(0, $y-2, 10, 0.02, 'DF');
        $pdf->Rect($x+$width-21, $y-2, 5, 0.02, 'DF');
    }
    if ($x < 20) {
        // left border
        //$pdf->Rect($x-3.3, 0, 0.02, $height*3+25, 'DF'); // full line
        $pdf->Rect($x-3.3, $y+$height-5, 0.02, 5, 'DF');
        $pdf->Rect($x-3.3, 5, 0.02, 5, 'DF');
    }

    // bottom border
    //$pdf->Rect(0, $y+60, $width*3, 0.02, 'DF'); // full line
    $pdf->Rect(0, $y+60, 10, 0.02, 'DF');
    $pdf->Rect($x+$width-21, $y+60, 5, 0.02, 'DF');

    // right border
    //$pdf->Rect($x+$width-18, 0, 0.02, $height*3+25, 'DF'); // full line
    $pdf->Rect($x+$width-18, 5, 0.02, 5, 'DF');
    $pdf->Rect($x+$width-18, $y+$height-5, 0.02, 5, 'DF');

    $pdf->SetFillColor(255,255,255);

    return $pdf;
}
