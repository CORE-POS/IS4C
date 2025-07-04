<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class New_WFC_Deli_Narrow_PDF extends FpdfWithBarcode
{
    private $tagdate;
    public function setTagDate($str){
        $this->tagdate = $str;
    }

    public function barcodeText($x, $y, $h, $barcode, $len)
    {
        $this->SetFont('Gill','',8);
        $this->Text($x,$y-$h+(17/$this->k),substr($barcode,-$len).' '.$this->tagdate);
    }

}

function New_WFC_Deli_Narrow($data,$offset=0,$showPrice=0,$showBarcode=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new New_WFC_Deli_Narrow_PDF('L','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

    $width = 68 * 0.75;
    $height = 34;
    $left = 12;  
    $top = 5;
    $guide = 0.3;

    $x = $left+$guide; $y = $top+$guide;

    $pdf->SetTopMargin($top); 
    $pdf->SetLeftMargin($left);
    $pdf->SetRightMargin($left);
    $pdf->SetAutoPageBreak(False);

    $mirrorData = arrayMirrorRowsNewDeliNarrow_24UP($data, 5);

    $i = 0;
    $page = 0;
    foreach($data as $k => $row){
        $upc = $row['upc'];
        if ($i % 30 == 0 && $i != 0) {

            $start = 30 * $page;
            $tagData = array_slice($mirrorData, $start, 30);
            prepNewDeliNarrowMirrorTags($start, $tagData, $pdf, $width, $height, $left, $top, $guide, $dbc);

            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
            $page += 1;
        }
        if ($i == 0) {
            $pdf = generateNewDeliNarrow_24UPTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $showBarcode, $offset);
        } else if ($i % 5 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }
        $pdf = generateNewDeliNarrow_24UPTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $showBarcode, $offset);
        $i++;
    }

    $start = 30 * $page;
    $tagData = array_slice($mirrorData, $start, 30);
    $end = count($mirrorData);
    prepNewDeliNarrowMirrorTags($start, $tagData, $pdf, $width, $height, $left, $top, $guide, $dbc);

    $pdf = $pdf->Output();
}

function prepNewDeliNarrowMirrorTags($start, $data, $pdf, $width, $height, $left, $top, $guide, $dbc)
{
    /*
        Print additional mirror images for back side of tags
    */
    $i = 0;
    $x = $left+$guide; $y = $top+$guide;
    if (count($data) % 5 != 0) {
        for ($j=count($data) % 5; $j<5; $j++) {
            $q = count($data) % 5;
            array_splice($data, -$q, 0, array(''));
        }
    }
    $pdf->AddPage('L');
    foreach($data as $k => $row){
        if ($i % 30 == 0 && $i != 0) {
        }
        if ($i == 0) {
            $pdf = generateNewDeliNarrowMirrorTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        } else if ($i % 5 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }
        $pdf = generateNewDeliNarrowMirrorTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        $i++;
    }
}

function generateNewDeliNarrowMirrorTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
{
    $upc = isset($row['upc']) ? $row['upc'] : '';
    $sku = isset($row['sku']) ? $row['sku'] : '';
    if (strlen($sku) < 1)
        $sku = '(no sku in POS)';
    $desc = isset($row['description']) ? $row['description'] : '';
    $brand = isset($row['brand']) ? $row['brand'] : '';
    $price = isset($row['normal_price']) ? $row['normal_price'] : '';
    $vendor = isset($row['vendor']) ? $row['vendor'] : '';
    $size = isset($row['size']) ? $row['size'] : '';
    $storeID = FormLib::get('store');
    $date = new DateTime();
    $today = $date->format('Y-m-d');

    $parA = array($storeID, $upc);
    $parP = $dbc->prepare("SELECT ROUND(auto_par,1) AS auto_par FROM products WHERE store_id = ? AND upc = ?");
    $parR = $dbc->execute($parP, $parA);
    $parW = $dbc->fetchRow($parR);
    $par = isset($parW['auto_par']) ? $parW['auto_par']*7 : 'n/a';
    if ($par == 0)
        $par = 'n/a';


    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Gill','', 9);

    // prep tag canvas
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 0, 1, 'C', true); 

    /*
        Add UPC Text
    */
    $pdf->SetXY($x+3,$y+3);
    $pdf->Cell(15, 8, $upc, 0, 1, 'L', true); 

    /*
        Add Date Text 
    */
    $pdf->SetXY($x+35,$y+3);
    $pdf->Cell(10, 4, $today, 0, 1, 'R', true); 

    /*
        Add PAR Text 
    */
    if ($storeID != 0) {
        $pdf->SetXY($x+35,$y+7);
        $pdf->Cell(10, 4, 'PAR '.$par, 0, 1, 'R', true); 
    }

    /*
        Add Brand & Description Text
    */
    $pdf->SetXY($x,$y+12);
    $pdf->Cell($width, 5, $brand, 0, 1, 'C', true); 
    $pdf->SetXY($x,$y+18);
    $pdf->Cell($width, 5, $desc, 0, 1, 'C', true); 

    /*
        Add Vendor SKU 
    */
    $pdf->SetXY($x+3,$y+23);
    $pdf->Cell($width, 5, "SKU ".$sku, 0, 1, 'L', true); 

    /*
        Add Vendor Text
    */
    $pdf->SetXY($x+3,$y+27);
    $pdf->Cell($width, 5, $vendor, 0, 1, 'L', true); 

    /*
        Add Size Text
    */
    if ($size > 0) {
        $pdf->SetXY($x+29,$y+27);
        $pdf->Cell('15', 5, $size, 0, 1, 'R', true); 
    }

    $pdf->SetFillColor(255, 255, 255);

    return $pdf;

}

function generateNewDeliNarrow_24UPTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $showPrice, $showBarcode, $offset)
{
    $upc = $row['upc'];
    $desc = $row['description'];
    $brand = $row['brand'];
    $price = $row['normal_price'];

    $updateUpcs = FormLib::get('update_upc');
    $manualDescs = FormLib::get('update_desc');
    $manualBrand = FormLib::get('update_brand');

    $args = array($row['upc']);
    $prep = $dbc->prepare("
        SELECT pu.description, p.scale, p.numflag
        FROM productUser AS pu
            INNER JOIN products AS p ON pu.upc=p.upc
        WHERE pu.upc = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);
    $desc = $row['description'];
    $numflag = $row['numflag'];

    $scaleP = $dbc->prepare("SELECT * FROM scaleItems WHERE plu = ?"); 
    $scaleR = $dbc->execute($scaleP, $args);
    $scaleW = $dbc->fetchRow($scaleR);
    $randoWeight = ($scaleW['weight'] == 0) ? true : false;
    $lb = ($randoWeight && substr($upc, 0, 3) == '002') ? '/lb' : '';

    $MdescKey = array_search($upc, $updateUpcs);
    $Mdesc = $manualDescs[$MdescKey];
    $Mbrand = $manualBrand[$MdescKey];
    $desc = $Mdesc;
    $brand = $Mbrand;
    $brand = strtoupper($brand);

    // get local info
    $localP = $dbc->prepare("SELECT 'true' FROM products WHERE local > 0 AND upc = ?");
    $item['local'] = $dbc->getValue($localP, $upc);

    // prep tag canvas
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 0, 1, 'C', true); 

    // use line break to split str if exists; else wordwrap if 2 lines req.
    $lines = array();
    if (strstr($desc, "\r\n")) {
        $lines = explode ("\r\n", $desc);
    } elseif (strlen($desc) > 24) {
        $wrp = wordwrap($desc, strlen($desc)/1.75, "*", false);
        $lines = explode('*', $wrp);
    } else {
        $lines[0] = $desc;
    }

    /*
        Add Brand Text
    */
    $textWidth = SignsLib::getStrWidthGillSans($brand);
    $tmpFontSize = 14;
    if ($textWidth > 13)
        $tmpFontSize = 13;
    if ($textWidth > 18)
        $tmpFontSize = 12;
    if ($textWidth > 24)
        $tmpFontSize = 11;
    if ($textWidth > 33)
        $tmpFontSize = 9;
    if ($textWidth > 39)
        $tmpFontSize = 8.5;
    if ($textWidth > 46)
        $tmpFontSize = 7;

    $pdf->SetFont('Gill','B', $tmpFontSize);
    $pdf->SetXY($x,$y+5);
    $pdf->Cell($width, 8, $brand, 0, 1, 'C', true); 

    /*
        Add Description Text
    */
    $descWidth = SignsLib::getStrWidthGillSans($lines[0]);
    $descFontSize = 14;
    if (isset($lines[1]) && SignsLib::getStrWidthGillSans($lines[1]) > $descWidth) {
        $descWidth = SignsLib::getStrWidthGillSans($lines[1]);
    }
    if ($descWidth > 38) {
        $descFontSize = 13.5;
    }
    if ($descWidth > 44) {
        $descFontSize = 12;
    }
    if ($descWidth > 50) {
        $descFontSize = 11;
    }
    $pdf->SetFont('Gill','', $descFontSize);

    if (count($lines) > 1) {
        $pdf->SetXY($x,$y+13);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+19);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
    } else {
        $pdf->SetXY($x,$y+16.5);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
    }

    /*
        Add UPC Barcode 
    */

    if ($showBarcode == 1 )  {
        /* UPC / PLU Barcode */
        $pdf->SetFillColor(0,0,0);
        //$pdf->EAN13($x+42, $y-1, substr($upc, -3), 4, 0.25);  //generate barcode and place on label
        $pdf->UPC_A($x+1, $y-1, $upc+"0", 5, 0.5);  //generate barcode and place on label
        $pdf->SetFillColor(255,255,255);

        // cover up 2
        $pdf->SetFillColor(255,255,255);
        $pdf->Rect($x+1, $y-2, 50, 3, 'F');
    }


    /*
        Add Price Text
    */
    $pdf->SetFont('Gill','B', 19);  //Set the font 
    $pdf->SetXY($x,$y+27);
    if ($showPrice == 1 )  {
        $pdf->Cell($width, 5, $price, 0, 1, 'C', true); 
    }

    /* 
        Print Local Star & Text
    */
    if ($item['local']) {
        $localX = 2;
        $localY = 22.5;
        $pdf->Image(__DIR__ . '/noauto/localST.jpg', $x+$localX, $y+$localY+1, 14, 8.5);
        $pdf->SetDrawColor(243, 115, 34);
        //$pdf->Rect($x+$localX, $y+$localY, 15, 9.4, 'D');
        $pdf->SetDrawColor(0, 0, 0);
    }

    /* 
        Print Vegan
    */
    if ($numflag & (1<<2)) {
        $localX = 36;
        $localY = 22.5;
        $pdf->Image(__DIR__ . '/noauto/veganST.jpg', $x+$localX, $y+$localY+1, 14, 8.5);
        $pdf->SetDrawColor(243, 115, 34);
        $pdf->SetDrawColor(0, 0, 0);
    }

    /*
        Add dollar sign using Write() becaus Cell() 
        will cover Local & Vegan bugs with white background 
    */
    if ($showPrice == 1) {
        $pdf->SetFont('Gill','B', 13);  //Set the font 
        $mod = 0;
        $mod += (4 - strlen($price)) * 1;
        $pdf->SetXY($x+14.5+$mod,$y+26);
        $pdf->Write(5, "$", ''); 
    }

    /*
        Add /lb String
    */
    if ($lb != '' && $showPrice == 1) {
        $pdf->SetFont('Gill','B', 10);  //Set the font 
        $pdf->SetXY($x+31+(($mod*-1)*2),$y+27.8);
        $pdf->Write(5, "/lb", ''); 
        $pdf->SetFont('Gill','B', 19);  //Set the font 
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

    $pdf->SetFillColor(255, 255, 255);

    return $pdf;
}

function arrayMirrorRowsNewDeliNarrow_24UP($array, $cols)
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
