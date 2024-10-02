<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class New_Deli_Soup_4UpL_PDF extends FpdfWithBarcode
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

function New_Deli_Soup_4UpL($data,$offset=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new New_Deli_Soup_4UpL_PDF('L','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

    $width = 68;
    $height = 36;
    $left = 3;  
    $top = 3;
    $guide = 0.3;

    $x = $left+$guide; $y = $top+$guide;

    $pdf->SetTopMargin($top); 
    $pdf->SetLeftMargin($left);
    $pdf->SetRightMargin($left);
    $pdf->SetAutoPageBreak(False);

    ////*
    ///    Create Guide-Lines
    ///*/ 
    ///$pdf->SetFillColor(155, 155, 155);
    ///// vertical 
    ///$pdf->SetXY(53.5, 0); 
    ///$pdf->Cell($guide, 5, '', 0, 1, 'C', true);

    ///// horizontal
    ///$pdf->SetXY(0, 107); 
    ///$pdf->Cell(5, $guide, '', 0, 1, 'C', true);

    ///$pdf->SetXY(270, 107); 
    ///$pdf->Cell(5, $guide, '', 0, 1, 'C', true);

    ///$pdf->SetFillColor(255, 255, 255);

    $i = 0;
    foreach($data as $k => $row){
        $upc = $row['upc'];

        /*
            Create Guide-Lines
        */ 
        $pdf->SetFillColor(155, 155, 155);
        // vertical 
        $pdf->SetXY(137.5, 0); 
        $pdf->Cell($guide, 10, '', 0, 1, 'C', true);

        $pdf->SetXY(137.5, 210); 
        $pdf->Cell($guide, 10, '', 0, 1, 'C', true);

        // horizontal
        $pdf->SetXY(0, 107); 
        $pdf->Cell(10, $guide, '', 0, 1, 'C', true);

        $pdf->SetXY(270, 107); 
        $pdf->Cell(10, $guide, '', 0, 1, 'C', true);

        $pdf->SetFillColor(255, 255, 255);
        if ($i % 4 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generatedDeliSoup4UpLTag($x, $y, $guide, $width*2, $height*3, $pdf, $row, $dbc, $i);
        } else if ($i % 2 == 0 && $i != 0) {
            $x = $left*2+$guide -3;
            $y += $height*3+$guide;
        } else {
            $x += $width*2+$guide;
        }
        $pdf = generatedDeliSoup4UpLTag($x, $y, $guide, $width*2, $height*3, $pdf, $row, $dbc, $i);
        $i++;
    }

    $pdf = $pdf->Output();
}

function generatedDeliSoup4UpLTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $i)
{
    $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
    $upc = $row['upc'];
    $brand = $row['description'];

    $manualIngrs = FormLib::get('ingredients');

    $Mingr = $manualIngrs[$i];

    $args = array($upc, $store);
    $row = array();
    $prep = $dbc->prepare("
        SELECT ingredients 
        FROM ScaleIngredients 
        WHERE upc = ?
            AND storeID = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);

    $args = array($upc);
    $prep = $dbc->prepare("
        SELECT itemdesc 
        FROM scaleItems 
        WHERE plu = ?");
    $res = $dbc->execute($prep, $args);
    $rowB = $dbc->fetchRow($res);
    $itemdesc = $rowB['itemdesc'];
    
    $desc = (isset($row['ingredients'])) ? $row['ingredients'] : '';
    if ($Mingr) $desc = $Mingr;
    $brand = (strlen($itemdesc) > 1) ? $itemdesc : $brand;
    $brand = strtolower($brand);
    $brand = ucwords($brand);
    $brand = str_replace("Qt", "", $brand);
    $brand = str_replace("Quart", "", $brand);
    $brand = str_replace("Pt", "", $brand);
    $brand = str_replace("Pint", "", $brand);

    /*
        Add Brand Text
    */
    if (strlen($brand) < 40) {
        $pdf->SetFont('Gill','B', 16);
        $pdf->SetXY($x,$y+30);
        $pdf->Cell($width, 8, $brand, 0, 1, 'C', true); 
    } else {
        $pdf->SetFont('Gill','B', 12);
        $wrapB = wordwrap($brand, 50, "\n");
        $expB = explode("\n", $wrapB);

        if (count($expB) > 1) {
            $pdf->SetXY($x-1,$y+29);
            $pdf->Cell($width, 4, $expB[0], 0, 1, 'C', true); 

            $pdf->SetXY($x-1,$y+34);
            $pdf->Cell($width, 4, $expB[1], 0, 1, 'C', true); 
        } else {
            $pdf->SetXY($x-1,$y+34);
            $pdf->Cell($width, 4, $expB[0], 0, 1, 'C', true); 
        }
    }

    /*
        Add Description Text
    */
    $pdf->SetFont('Gill','', 10);
    $utf8degree = chr(194) . chr(176);
    $iso85591degree = chr(176);
    $desc = str_replace($utf8degree, $iso85591degree, $desc);
    $desc = str_replace("Reheat to a temperature of 165 degrees for 15 seconds", "", $desc);
    $wrap = wordwrap($desc, 68, "\n");
    $exp = explode("\n", $wrap);

    $y = $y+40;
    $x = $x+10;
    foreach ($exp as $k => $str) {
        /* Leave capitalization as entered by user
        $str = strtolower($str);
        $str = ucwords($str);
        $str = str_replace("*=organic", "*=Organic", $str);
         */
        $str = preg_replace( "/\r|\n/", "", $str);
        $mod = 4.3 * $k;
        $pdf->SetXY($x+5, $y+$mod);
        $pdf->Cell(92, 5, $str, 0, 1, 'C', true);
    }

    /*
        Add Top Image Branding
    */
        $localX = 0;
        $localY = 0;
        //$pdf->Image(__DIR__ . '/noauto/VeganBug2.jpg', $x+$localX, $y+$localY+1, 14, 8);
        //$pdf->Image(__DIR__ . '/noauto/veganST.jpg', $x+$localX, $y+$localY+34, 14, 8.5);
        //$pdf->Image(__DIR__ . '/noauto/RailDeliTop.jpg', $x-13.5, $y-43.5, $width+2, 35);
        $pdf->Image(__DIR__ . '/noauto/4UpDeliTop.jpg', $x-8.5, $y-40, $width-6, 28);
        $pdf->SetDrawColor(243, 115, 34);
        //$pdf->Rect($x+$localX, $y+$localY, 15, 9.4, 'D');
        $pdf->SetDrawColor(0, 0, 0);


    return $pdf;
}
