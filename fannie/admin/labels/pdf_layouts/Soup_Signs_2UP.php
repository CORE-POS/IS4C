<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class Soup_Signs_2UP_PDF extends FpdfWithBarcode
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

function Soup_Signs_2UP($data,$offset=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new Soup_Signs_2UP_PDF('P','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

    $width = 203.2;
    $height = 100;
    $top = 34;
    $left = 5;
    $guide = 0.3;

    $x = $left;
    $y = $top;

    $pdf->SetTopMargin($top); 
    $pdf->SetLeftMargin($left);
    $pdf->SetRightMargin($left);
    $pdf->SetAutoPageBreak(False);

    $i = 0;
    $j = 0;
    foreach($data as $k => $row){
        $upc = $row['upc'];
        if ($i % 2 == 0 && $i != 0) {
            $pdf->AddPage('P');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 1) {
            $y += $height+$top+5;
        }
        $pdf = generateSoupTag2UpD($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $j);
        $i++;
        $j++;
    }

    $pdf = $pdf->Output();
}

function generateSoupTag2UpD($x, $y, $guide, $width, $height, $pdf, $row, $dbc, $j)
{
    $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
    $upc = $row['upc'];
    $brand = $row['description'];
    $updateDesc = FormLib::get('update_desc');

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
    
    $desc = $row['ingredients'];
    $brand = $itemdesc;
    // I'm not sure how to use updates description, only when the were edited.
    // $brand = $updateDesc[$j];
    $brand = strtolower($brand);
    $brand = ucwords($brand);
    $brand = str_replace("Qt", "", $brand);
    $brand = str_replace("Quart", "", $brand);
    $brand = str_replace("Pt", "", $brand);
    $brand = str_replace("Pint", "", $brand);
    $brand = str_replace("Bbq", "BBQ", $brand);

    /*
        Guide Lines (for dev. only, PLEASE COMMENT OUT before using)
        $pdf->SetFont('Gill','B', 16);
        $pdf->SetXY($x,$y);
        $pdf->Cell($width, $height, "", 1, 1, 'C', true); 
    */

    /*
        Add Brand Text
    */
    if (strlen($brand) < 40) {
        $pdf->SetFont('Gill','B', 20);
        $pdf->SetXY($x,$y+18);
        $pdf->Cell($width, 8, $brand, 0, 1, 'C', true); 
    } else {
        $pdf->SetFont('Gill','B', 20);
        $wrapB = wordwrap($brand, 30, "\n");
        $expB = explode("\n", $wrapB);

        if (count($expB) > 1) {
            $pdf->SetXY($x-1,$y+14);
            $pdf->Cell($width, 4, $expB[0], 0, 1, 'C', true); 

            $pdf->SetXY($x-1,$y+22);
            $pdf->Cell($width, 4, $expB[1], 0, 1, 'C', true); 

            $pdf->SetXY($x-1,$y+30);
            $pdf->Cell($width, 4, $expB[2], 0, 1, 'C', true); 
        } else {
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
    $wrap = wordwrap($desc, 100, "\n");
    $exp = explode("\n", $wrap);

    $y = $y+40;
    $x = $x+10;
    foreach ($exp as $k => $str) {
        $str = preg_replace( "/\r|\n/", "", $str);
        $mod = 4.3 * $k;
        $pdf->SetXY($x+5, $y+$mod);
        $pdf->Cell($width-25, 5, $str, 0, 1, 'C', true);
    }

    return $pdf;
}
