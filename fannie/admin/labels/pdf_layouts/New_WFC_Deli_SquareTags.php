<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class New_WFC_Deli_SquareTags_PDF extends FpdfWithBarcode
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

    static public function stringToLines($string) {
        $length = strlen($string);
        $lines = array();
        // return 1 to 4 lines based on $desc size
        if ($length < 21) {
            $lines[] = $string;
        } else if ($length < 38) {
            $wrp = wordwrap($string, 19, "*", false);
            $lines = explode('*', $wrp);
        } else if ($length < 56) {
            $wrp = wordwrap($string, 19, "*", false);
            $lines = explode('*', $wrp);
        } else {
            $wrp = wordwrap($string, 19, "*", false);
            $lines = explode('*', $wrp);
        }

        return $lines;
    }
}

function New_WFC_Deli_SquareTags ($data,$offset=0)
{
    $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
    $pdf = new New_WFC_Deli_SquareTags_PDF('L','mm','Letter');
    $pdf->AddPage();
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    //define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    //$pdf->AddFont('ArialMT', '', 'Arial.php');
    //$pdf->AddFont('ArialMT', 'B', 'ArialBold.php');
    //$pdf->SetFont('ArialMT','', 16);

    define('FPDF_FONTPATH', __DIR__. '/../../../modules/plugins2.0/CoopDealsSigns/noauto/fonts/');
    $pdf->AddFont('Gill', '', 'GillSansMTPro-Medium.php');
    $pdf->AddFont('Gill', 'B', 'GillSansMTPro-Heavy.php');
    $pdf->SetFont('Gill','B', 16);

    $width = 68;
    $height = 68;
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
        if ($i % 12 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateSquareTagsTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        } else if ($i % 4 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }
        $pdf = generateSquareTagsTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        $i++;
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
    $data = arrayMirrorRowsSquareTags($data, 4);
    $pdf->AddPage('L');
    foreach($data as $k => $row){
        if ($i % 12 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateMirrorTagSquareTags12($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        } else if ($i % 4 == 0 && $i != 0) {
            $x = $left+$guide;
            $y += $height+$guide;
        } else {
            $x += $width+$guide;
        }
        $pdf = generateMirrorTagSquareTags12($x, $y, $guide, $width, $height, $pdf, $row, $dbc);
        $i++;
    }

    $pdf = $pdf->Output();
}

function generateMirrorTagSquareTags12($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
{
    $upc = isset($row['upc']) ? $row['upc'] : '';
    $desc = isset($row['description']) ? $row['description'] : '';
    $size = isset($row['size']) ? $row['size'] : '';
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Gill','', 22);  //Set the font 

    $args = array($upc);
    $prep = $dbc->prepare("
        SELECT text 
        FROM scaleItems
        WHERE plu = ?");
    $res = $dbc->execute($prep, $args);
    $array = $dbc->fetchRow($res);
    $ingr = $array ? $array['text'] : '';

    $lines = New_WFC_Deli_SquareTags_PDF::stringToLines($desc);
    if (strstr($desc, "\r\n")) {
        $lines = explode ("\r\n", $desc);
    }

    $ingr = strtolower($ingr);
    $ingr = explode('contains', $ingr);
    $allergens = (isset($ingr[1])) ? ucfirst($ingr[1]) : '';
    $allergens = str_replace("\r\n", "", $allergens);
    $allergens = str_replace("\r", "", $allergens);
    $allergens = str_replace("\n", "", $allergens);
    $allergens = str_replace("\t", "", $allergens);
    $allergens = str_replace("\0", "", $allergens);
    $allergens = str_replace("\x0B", "", $allergens);
    $allergens = str_replace(":", "", $allergens);
    $allergens = "*".$allergens;

    // prep tag canvas
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 0, 1, 'C', true); 

    /*
        Add UPC Text
    */
    $pdf->SetXY($x,$y+4);
    $pdf->Cell($width, 8, substr($upc,3,4), 0, 1, 'C', true); 

    /*
        Add Description Text
    */
    $pdf->SetFont('Gill','', 12);  //Set the font 
    $lineCount = count($lines);
    $temp_y = $y;
    $y = $y+15;
    foreach ($lines as $k => $line)
        $lines[$k] = strtoupper($line);
    if ($lineCount == 2) {
        $pdf->SetXY($x,$y+12);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+19);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
    } elseif ($lineCount == 3) {
        $pdf->SetXY($x,$y+8);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+15);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+22);
        $pdf->Cell($width, 5, $lines[2], 0, 1, 'C', true); 
    } elseif ($lineCount == 4) {
        $pdf->SetXY($x,$y+4);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+11);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+18);
        $pdf->Cell($width, 5, $lines[2], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+25);
        $pdf->Cell($width, 5, $lines[3], 0, 1, 'C', true); 
    } else {
        $pdf->SetXY($x,$y+15);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
    }
    $y = $temp_y;

    /*
        Add Allergens 
    */
    if ($allergens != '*') {
        $pdf->SetXY($x,$y+45);
        $pdf->Cell($width, 5, $allergens, 0, 1, 'C', true); 
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

function generateSquareTagsTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
{
    $upc = $row['upc'];
    $desc = $row['description'];
    $showPrice = FormLib::get('showPrice', false);

    $args = array($row['upc']);
    $prep = $dbc->prepare("
        SELECT pu.description, p.scale, p.numflag
        FROM productUser AS pu
            INNER JOIN products AS p ON pu.upc=p.upc
        WHERE pu.upc = ?");
    $res = $dbc->execute($prep, $args);
    $desc = $dbc->fetchRow($res);
    $desc = $desc['description'];
    $price = $row['normal_price'];
    $numflag = $row['numflag'];

    $scaleP = $dbc->prepare("SELECT * FROM scaleItems WHERE plu = ?"); 
    $scaleR = $dbc->execute($scaleP, $args);
    $scaleW = $dbc->fetchRow($scaleR);
    $randoWeight = ($scaleW['bycount'] == 0) ? true : false;
    $lb = ($randoWeight) ? '/lb' : '';

    $updateUpcs = FormLib::get('update_upc');
    $manualDescs = FormLib::get('update_desc');
    $MdescKey = array_search($upc, $updateUpcs);
    $Mdesc = $manualDescs[$MdescKey];
    $desc = $Mdesc;

    // prep tag canvas
    $pdf->SetXY($x,$y);
    $pdf->Cell($width, $height, '', 0, 1, 'C', true); 

    $lines = New_WFC_Deli_SquareTags_PDF::stringToLines($desc);
    if (strstr($desc, "\r\n")) {
        $lines = explode ("\r\n", $desc);
    }

    /*
        Add Top Image Branding
    */
        $localX = 0;
        $localY = 0;
        //$pdf->Image(__DIR__ . '/noauto/VeganBug2.jpg', $x+$localX, $y+$localY+1, 14, 8);
        //$pdf->Image(__DIR__ . '/noauto/veganST.jpg', $x+$localX, $y+$localY+34, 14, 8.5);
        $pdf->Image(__DIR__ . '/noauto/RailDeliTop.jpg', $x, $y, $width, 17);
        $pdf->SetDrawColor(243, 115, 34);
        //$pdf->Rect($x+$localX, $y+$localY, 15, 9.4, 'D');
        $pdf->SetDrawColor(0, 0, 0);

    /*
        Add Top Vegan Bug 
    */
    if ($numflag & (1<<2)) {
        $localX = 53;
        $localY = 23.5;
        //$pdf->Image(__DIR__ . '/noauto/VeganBug2.jpg', $x+$localX, $y+$localY+1, 14, 8);
        $pdf->Image(__DIR__ . '/noauto/veganST.jpg', $x+$localX, $y+$localY+34, 14, 8.5);
        $pdf->SetDrawColor(243, 115, 34);
        //$pdf->Rect($x+$localX, $y+$localY, 15, 9.4, 'D');
        $pdf->SetDrawColor(0, 0, 0);
    }

    /*
        Add Description Text
    */
    $pdf->SetFont('Gill','', 16);  //Set the font 
    $lineCount = count($lines);
    $temp_y = $y;
    $y = $y+15;
    if ($lineCount == 2) {
        $pdf->SetXY($x,$y+12);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+19);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
    } elseif ($lineCount == 3) {
        $pdf->SetXY($x,$y+8);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+15);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+22);
        $pdf->Cell($width, 5, $lines[2], 0, 1, 'C', true); 
    } elseif ($lineCount == 4) {
        $pdf->SetXY($x,$y+4);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+11);
        $pdf->Cell($width, 5, $lines[1], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+18);
        $pdf->Cell($width, 5, $lines[2], 0, 1, 'C', true); 
        $pdf->SetXY($x, $y+25);
        $pdf->Cell($width, 5, $lines[3], 0, 1, 'C', true); 
    } else {
        $pdf->SetXY($x,$y+15);
        $pdf->Cell($width, 5, $lines[0], 0, 1, 'C', true); 
    }
    $y = $temp_y;

    /*
        Add Price
    */
    if ($showPrice == 1) {
        $pdf->SetFont('Gill', 'B', 26); 
        $pdf->SetXY($x,$y+47);
        $pdf->Cell($width, 5, "$".$price.$lb, 0, 1, 'C', true); 
        $pdf->SetFont('Gill', 'B', 16); 
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

function arrayMirrorRowsSquareTags($array, $cols)
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
