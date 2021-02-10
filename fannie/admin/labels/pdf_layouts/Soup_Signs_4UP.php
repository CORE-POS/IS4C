<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class Soup_Signs_4UP extends FpdfWithBarcode
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

function Soup_Signs_4UP($data,$offset=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new Soup_Signs_4UP('L','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

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
        $upc = $row['upc'];
        if ($i % 4 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateSoupTag($x, $y, $guide, $width*2, $height*3, $pdf, $row, $dbc);
        } else if ($i % 2 == 0 && $i != 0) {
            $x = $left*2+$guide -3;
            $y += $height*3+$guide;
        } else {
            $x += $width*2+$guide;
        }
        $pdf = generateSoupTag($x, $y, $guide, $width*2, $height*3, $pdf, $row, $dbc);
        $i++;
    }

    $pdf = $pdf->Output();
}

function generateSoupTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
{
    $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
    $upc = $row['upc'];
    $brand = $row['description'];

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

        $pdf->SetXY($x-1,$y+29);
        $pdf->Cell($width, 4, $expB[0], 0, 1, 'C', true); 

        $pdf->SetXY($x-1,$y+34);
        $pdf->Cell($width, 4, $expB[1], 0, 1, 'C', true); 
    }

    /*
        Add Description Text
    */
    $pdf->SetFont('Gill','', 10);
    $wrap = wordwrap($desc, 68, "\n");
    $exp = explode("\n", $wrap);

    $y = $y+40;
    $x = $x+10;
    foreach ($exp as $k => $str) {
        $str = strtolower($str);
        $str = ucwords($str);
        $str = preg_replace( "/\r|\n/", "", $str);
        $mod = 4.3 * $k;
        $pdf->SetXY($x+5, $y+$mod);
        $pdf->Cell(92, 5, $str, 0, 1, 'C', true);
    }

    /*
        Create Guide-Lines
    */ 
    $pdf->SetFillColor(255, 255, 255);
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
