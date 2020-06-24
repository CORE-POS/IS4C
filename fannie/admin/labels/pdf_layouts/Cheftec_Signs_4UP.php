<?php
use COREPOS\Fannie\API\FanniePlugin;
if (!class_exists('FpdfWithBarcode')) {
    include(dirname(__FILE__) . '/../FpdfWithBarcode.php');
}
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (!class_exists('CTDB')) {
    include(__DIR__ . '/CTDB.php');
}

class Cheftec_Signs_4UP_PDF extends FpdfWithBarcode { }

function Cheftec_Signs_4UP($data,$offset=0)
{
    $pdf = new Cheftec_Signs_4UP_PDF('L','mm','Letter');
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
        if ($i % 4 == 0 && $i != 0) {
            $pdf->AddPage('L');
            $x = $left;
            $y = $top;
            $i = 0;
        }
        if ($i == 0) {
            $pdf = generateChefTag($x, $y, $guide, $width*2, $height*3, $pdf, $row, $dbc);
        } else if ($i % 2 == 0 && $i != 0) {
            $x = $left*2+$guide -3;
            $y += $height*3+$guide;
        } else {
            $x += $width*2+$guide;
        }
        $pdf = generateChefTag($x, $y, $guide, $width*2, $height*3, $pdf, $row, $dbc);
        $i++;
    }

    $pdf = $pdf->Output();
}

function generateChefTag($x, $y, $guide, $width, $height, $pdf, $row, $dbc)
{

    $dbc = CTDB::get();
    $recipeID = $row['recipeID'];

    $res = $dbc->query("
        SELECT 
            r.RecipeID,
            r.RecipeName,
            r.PLU,
            i.itemID,
            v.ItemName
        FROM DataDir.dbo.Recipe AS r
        LEFT JOIN DataDir.dbo.RecpItems AS i ON r.RecipeID=i.RecipeID
        LEFT JOIN DataDir.dbo.Inv AS v ON i.ItemID=v.ItemID
        WHERE r.RecipeID = '$recipeID';
    ");
    $cols = array();
    $excludeRows = array('SMART CYCLE 12 OZ','----- END of SUB Recipe--------------','----- SUB Recipe --------------','----- Other Ingredients ------------------');
    while ($row = $dbc->fetchRow($res)) {
        foreach($row as $k => $v) {
            if (ctype_alpha($k)) {
                if (!in_array($k, $cols)) {
                    $cols[] = $k;
                }
                if (in_array($row['ItemName'], $excludeRows)) 
                    continue;
                $data[$row['RecipeID']]['name'] = $row['RecipeName'];
                $data[$row['RecipeID']]['PLU'] = $row['PLU'];
                if (!in_array($row['ItemName'], $data[$row['RecipeID']]['ingr'])) {
                    $data[$row['RecipeID']]['ingr'][] = $row['ItemName'];
                }
            }
        }
        $ingredients = '';
        foreach ($data as $recipeID => $row) {
            $name = $row['name']; 
            foreach ($row['ingr'] as $ing) {
                $ingredients .= "$ing, ";
            }
        }
        $ingredients = rtrim($ingredients, ", ");
    }
    $brand = $name;
    $desc = $ingredients;
    
    $prefix = (substr(strtolower($row['text']), 0, 11) == 'ingredients') ? '' : 'Ingredients: ';
    $desc = $prefix.$desc;
    $brand = strtolower($brand);
    $brand = ucwords($brand);

    /*
        Add Brand Text
    */
    $pdf->SetFont('Gill','B', 16);
    $pdf->SetXY($x,$y+30);
    $pdf->Cell($width, 8, $brand, 0, 1, 'C', true); 

    /*
        Add Description Text
    */
    $pdf->SetFont('Gill','', 10);
    $wrap = wordwrap($desc, 68, "\n");
    $exp = explode("\n", $wrap);

    //$x = 5; $y = 8;
    $y = $y+40;
    $x = $x+10;
    foreach ($exp as $k => $str) {
        $str = strtolower($str);
        $str = ucwords($str);
        $str = preg_replace( "/\r|\n/", "", $str);
        $mod = 4.3 * $k;
        $pdf->SetXY($x+5, $y+$mod);
        $pdf->Cell(110, 5, $str, 0, 1, 'C', true);
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
