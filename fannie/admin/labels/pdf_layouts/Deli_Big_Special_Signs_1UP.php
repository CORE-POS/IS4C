<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('FpdfLig')) {
    include(__DIR__ . '/FpdfLib.php');
}

class Deli_Big_Special_Signs_1UP extends FpdfWithBarcode
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

function Deli_Big_Special_Signs_1UP($data,$offset=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new Deli_Big_Special_Signs_1UP('L','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'I', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

    $width = 176;
    $height = 120;
    $left = 3;  
    $top = 3;

    $x = $left; $y = $top;

    $pdf->SetTopMargin($top); 
    $pdf->SetLeftMargin($left);
    $pdf->SetRightMargin($left);
    $pdf->SetAutoPageBreak(False);

    $i = 0;
    foreach($data as $k => $row){
        $upc = $row['upc'];
        if ($i == 1) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateDeliSpecialSign($x, $y, $width*2, $height*3, $pdf, $row, $dbc);
        } else {
            $x = $left*2;
            $y += $height*2;
        }
        $pdf = generateDeliSpecialSign($x, $y, $width*2, $height*3, $pdf, $row, $dbc);
        $i++;
    }

    $pdf = $pdf->Output();
}

function generateDeliSpecialSign($x, $y, $width, $height, $pdf, $row, $dbc)
{
    $upc = $row['upc'];
    $brand = $row['description'];

    $args = array($row['upc']);
    $row = array();
    $prep = $dbc->prepare("
        SELECT text
        FROM scaleItems
        WHERE plu = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);

    $desc = $row['text'];
    $brand = strtolower($brand);
    $brand = ucwords($brand);
    $brand = str_replace("Qt", "", $brand);
    $brand = str_replace("Quart", "", $brand);
    $brand = str_replace("Pt", "", $brand);
    $brand = str_replace("Pint", "", $brand);

    // Get full brand description
    $args = array($upc);
    $prep = $dbc->prepare("SELECT * FROM productUser
        WHERE upc = ?");
    $res = $dbc->execute($prep, $args);
    $row = $dbc->fetchRow($res);
    $brand = $row['description'];

    /*
        Add Brand Text
    */
    $pdf->SetFont('Gill','B', 28);
    $pdf->SetXY($x+10,$y+55);
    $pdf->Cell($width-100, 8, $brand, 0, 1, 'C', true); 

    /*
        Add Description Text
    */
    $pdf->SetFont('Gill','', 14);
    $desc = ucwords($desc);
    $desc = FpdfLib::strtolower_inpara($desc);
    // remove str organic, which only exists within list of ingredients
    $desc = str_replace("organic", "", $desc);
    $desc = str_replace("*=Organic", "", $desc);
    $desc = str_replace("*= Organic", "", $desc);
    $desc .= "\r\n*=Organic";
    // relocate "contains" statment to bottom of sign
    $desc = FpdfLib::simplify_contains($desc);

    // remove empty lines 
    $desc = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $desc);

    $wrap = wordwrap($desc, 120, "\n");
    $exp = explode("\n", $wrap);
   
    $y = $y+69;
    $x = $x+5;
    $i = 0;
    foreach ($exp as $k => $str) {
        $mod = 5.5 * $i;
        if (strpos($str, "Contains") != false) {
            $pdf->SetFont('Gill','I', 10);
        }  elseif (strpos($str, ',') == false && strpos($str, ':') == false) {
            $pdf->SetFont('Gill','B', 14);
            $y += 2.4;
        } else {
            $pdf->SetFont('Gill','', 14);
        }
        $str = preg_replace( "/\r|\n/", "", $str);
        $pdf->SetXY($x+5, $y+$mod);
        $pdf->Cell(250, 5, $str, 0, 1, 'C', true);
        $i++;
    }

    return $pdf;
}

