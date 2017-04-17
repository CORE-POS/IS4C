<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class NutriFacts extends FannieRESTfulPage
{
    protected $header = 'NutriFacts Image Builder';
    protected $title = 'NutriFacts Image Builder';

    public $description = '[NutriFacts Image Builder] generates Nutrition Facts Image';
    public $has_unit_tests = true;

    private $daily_values = array(
        'total_fat' => 65,
        'sat_fat' => 20,
        'cholest' => 300,
        'sodium' => 2400,
        'total_carb' => 300,
        'fiber' => 25,
        'protein' => 50,
    );

    protected function get_id_handler()
    {
        $upc = BarcodeLib::padUPC($this->id);
        $req = new NutriFactReqItemsModel($this->connection);
        $req->upc($upc);
        $req->load();

        $json = json_decode($req->toJSON(), true);
        $json['opts'] = array();

        $opt = new NutriFactOptItemsModel($this->connection);
        $opt->upc($upc);
        foreach ($opt->find('nutriFactOptItemID') as $obj) {
            $json['opts'][] = json_decode($obj->toJSON(), true);
        }

        $prod = new ProductsModel($this->connection);
        $prod->upc($upc);
        foreach ($prod->find() as $obj) {
            $json['description'] = $obj->description();
            break;
        }

        echo json_encode($json);

        return false;
    }

    private function saveForm()
    {
        $req = new NutriFactReqItemsModel($this->connection);
        $req->upc(BarcodeLib::padUPC($this->form->upc));
        $req->servingSize($this->form->serving_size);
        $req->calories($this->normalizeVal($this->form->calories, ''));
        $req->fatCalories($this->normalizeVal($this->form->fat_calories, ''));
        $req->totalFat($this->normalizeVal($this->form->total_fat, 'g'));
        $req->saturatedFat($this->normalizeVal($this->form->sat_fat, 'g'));
        $req->transFat($this->normalizeVal($this->form->trans_fat, 'g'));
        $req->cholesterol($this->normalizeVal($this->form->cholest, 'mg'));
        $req->sodium($this->normalizeVal($this->form->sodium, 'mg'));
        $req->totalCarbs($this->normalizeVal($this->form->total_carb, 'g'));
        $req->fiber($this->normalizeVal($this->form->fiber, 'g'));
        $req->fiber($this->normalizeVal($this->form->fiber, 'g'));
        $req->sugar($this->normalizeVal($this->form->sugar, 'g'));
        $req->protein($this->normalizeVal($this->form->protein, 'g'));
        $req->save();
        
        $opt = new NutriFactOptItemsModel($this->connection);
        $opt->upc($req->upc());
        foreach ($opt->find() as $obj) {
            $opt->delete();
        }
        for ($i=0; $i<count($this->form->nutrient); $i++) {
            if ($this->form->nutrient[$i] === '') {
                continue;
            }
            $opt->name($this->form->nutrient[$i]);
            if ($this->form->percent[$i] === '') {
                $opt->percentDV(0);
            } else {
                $opt->percentDV($this->form->percent[$i]);
            }
            $opt->save();
        }
    }
    
    protected function post_handler()
    {
        $this->saveForm();
        $pdf = new FPDF('P', 'in', array(3.5, 1.25));
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $pdf->SetDrawColor(0, 0, 0);
        $edge = 0.02;
        $pdf->Rect($edge, $edge, 3.5-(2*$edge), 1.25-(2*$edge));

        $space = 0.18;
        $offset = 1;
        for ($i=0; $i<6; $i++) {
            $pdf->Line($offset, $space+($i*$space), 3.5-(3*$edge), $space+($i*$space));
            if ($i === 0 || $i === 5) {
                $pdf->Line($offset, 0.01+$space+($i*$space), 3.5-(3*$edge), 0.01+$space+($i*$space));
            }
        }

        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetXY(1.5*$edge, 3.5*$edge);
        $pdf->MultiCell(1, 0.19, "Nutrition\nFacts:");

        $pdf->SetFont('Arial', '', 7);
        $pdf->SetXY(1.5*$edge, 24*$edge);
        $pdf->Cell(0.5, 0.10, 'Serv. Size');
        $pdf->Cell(1, 0.10, $this->form->serving_size);
        $pdf->Ln(0.20);

        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetX(1.5*$edge);
        $pdf->Cell(0.5, 0.10, 'Calories');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->calories, ''));
        $pdf->Ln();
        $pdf->SetX(3.5*$edge);
        $pdf->Cell(0.45, 0.10, 'Fat Cal.');
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->fat_calories, ''));
        $pdf->Ln(0.28);

        $pdf->SetFont('Arial', '', 4);
        $pdf->MultiCell(0.85, 0.08, "*Percent Daily Values (DV) are\nbased on a 2,000 calorie diet");

        $columns = array(1.01, 1.9, 2.20, 3);
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->SetXY($columns[0], 2*$edge);
        $pdf->Cell($columns[0], 0.10, 'Amount/serving');
        $pdf->SetXY($columns[1], 2*$edge);
        $pdf->Cell($columns[1], 0.10, '%DV*');
        $pdf->SetXY($columns[2], 2*$edge);
        $pdf->Cell($columns[2], 0.10, 'Amount/serving');
        $pdf->SetXY($columns[3], 2*$edge);
        $pdf->Cell($columns[3], 0.10, '%DV*');

        $mid_col = 0.45;
        $pdf->SetXY($columns[0], (3*$edge)+($space*1));
        $pdf->Cell($columns[0], 0.10, 'Total Fat');
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetXY($columns[0]+$mid_col, (3*$edge)+($space*1));
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->total_fat));
        $pdf->SetXY($columns[1], (3*$edge)+($space*1));
        $pdf->Cell(1, 0.10, sprintf('%d%%', round(100*$this->numericVal($this->form->total_fat) / $this->daily_values['total_fat'])));
        $pdf->SetXY($columns[0], (3*$edge)+($space*2));
        $pdf->Cell($columns[0], 0.10, ' Sat. Fat');
        $pdf->SetXY($columns[0]+$mid_col, (3*$edge)+($space*2));
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->sat_fat));
        $pdf->SetXY($columns[1], (3*$edge)+($space*2));
        $pdf->Cell(1, 0.10, sprintf('%d%%', round(100*$this->numericVal($this->form->sat_fat) / $this->daily_values['sat_fat'])));
        $pdf->SetXY($columns[0], (3*$edge)+($space*3));
        $pdf->Cell($columns[0], 0.10, ' Trans Fat');
        $pdf->SetXY($columns[0]+$mid_col, (3*$edge)+($space*3));
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->trans_fat));
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->SetXY($columns[0], (3*$edge)+($space*4));
        $pdf->Cell($columns[0], 0.10, 'Cholest.');
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetXY($columns[0]+$mid_col, (3*$edge)+($space*4));
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->cholest, 'mg'));
        $pdf->SetXY($columns[1], (3*$edge)+($space*4));
        $pdf->Cell(1, 0.10, sprintf('%d%%', round(100*$this->numericVal($this->form->cholest) / $this->daily_values['cholest'])));
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->SetXY($columns[0], (3*$edge)+($space*5));
        $pdf->Cell($columns[0], 0.10, 'Sodium');
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetXY($columns[0]+$mid_col, (3*$edge)+($space*5));
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->sodium, 'mg'));
        $pdf->SetXY($columns[1], (3*$edge)+($space*5));
        $pdf->Cell(1, 0.10, sprintf('%d%%', round(100*$this->numericVal($this->form->sodium) / $this->daily_values['sodium'])));

        $pdf->SetFont('Arial', 'B', 6);
        $pdf->SetXY($columns[2], (3*$edge)+($space*1));
        $pdf->Cell($columns[0], 0.10, 'Total Carb.');
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetXY($columns[2]+$mid_col, (3*$edge)+($space*1));
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->total_carb));
        $pdf->SetXY($columns[3], (3*$edge)+($space*1));
        $pdf->Cell(1, 0.10, sprintf('%d%%', round(100*$this->numericVal($this->form->total_carb) / $this->daily_values['total_carb'])));
        $pdf->SetXY($columns[2], (3*$edge)+($space*2));
        $pdf->Cell($columns[0], 0.10, ' Fiber');
        $pdf->SetXY($columns[2]+$mid_col, (3*$edge)+($space*2));
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->fiber));
        $pdf->SetXY($columns[3], (3*$edge)+($space*2));
        $pdf->Cell(1, 0.10, sprintf('%d%%', round(100*$this->numericVal($this->form->fiber) / $this->daily_values['fiber'])));
        $pdf->SetXY($columns[2], (3*$edge)+($space*4));
        $pdf->Cell($columns[0], 0.10, ' Sugar');
        $pdf->SetXY($columns[2]+$mid_col, (3*$edge)+($space*4));
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->sugar));
        $pdf->SetFont('Arial', 'B', 6);
        $pdf->SetXY($columns[2], (3*$edge)+($space*5));
        $pdf->Cell($columns[0], 0.10, 'Protein');
        $pdf->SetFont('Arial', '', 6);
        $pdf->SetXY($columns[2]+$mid_col, (3*$edge)+($space*5));
        $pdf->Cell(1, 0.10, $this->normalizeVal($this->form->protein));
        $pdf->SetXY($columns[3], (3*$edge)+($space*5));
        $pdf->Cell(1, 0.10, sprintf('%d%%', round(100*$this->numericVal($this->form->protein) / $this->daily_values['protein'])));

        $bottom = '';
        for ($i=0; $i<count($this->form->nutrient); $i++) {
            if ($this->form->nutrient[$i] === '') {
                continue;
            }
            $bottom .= $this->form->nutrient[$i] . ' ';
            if ($this->form->percent[$i] === '') {
                $bottom .= '0% ';
            } else {
                $bottom .= $this->form->percent[$i] . '% ' . chr(183) . ' ';;
            }
        }
        $bottom = rtrim($bottom, chr(183) . ' ');
        $pdf->SetXY($columns[0], (2*$edge)+($space*6));
        $pdf->Cell(2.45, 0.10, $bottom, 0, 0, 'C');

        $outfile = tempnam(sys_get_temp_dir(), 'nfp');
        $outimg = tempnam(sys_get_temp_dir(), 'nfi');

        $pdf->Output($outfile, 'F');
        exec("convert -density 300 \"$outfile\" \"$outimg.png\"");
        unlink($outfile);
        header('Content-Type: image/png');
        header('Content-disposition: attachment; filename=nutritionfacts.png');
        echo file_get_contents($outimg . '.png');
        unlink($outimg . '.png');

        return false;
    }

    private function normalizeVal($val, $units='g')
    {
        if ($val === '') {
            return 0 . $units;
        }

        return is_numeric($val) ? $val . $units : $val;
    }

    private function numericVal($val, $units='g')
    {
        if ($val === '') {
            return 0;
        }
        if (is_numeric($val)) {
            return $val;
        } else {
            $val = trim($val, $units);
            return trim($val);
        }
    }

    protected function get_view()
    {
        $this->addScript('nutrifacts.js');
        $ret = <<<HTML
<form method="post" target="_blank">
<div class="form-group form-inline">
    <label>UPC</label>
    <input type="text" name="upc" class="form-control input-sm" required />
</div>
<div class="form-group form-inline">
    <span id="item-name" />
</div>
<table class="table table-bordered">
<tr>
    <th>Serv. Size</th>
    <td><input type="text" class="form-control input-sm" name="serving_size" /></td>
    <th>Calories</th>
    <td><input type="number" class="form-control input-sm" name="calories" /></td>
    <th>Fat Calories</th>
    <td><input type="number" class="form-control input-sm" name="fat_calories" /></td>
</tr>
<tr>
    <th>Total Fat</th>
    <td><input type="text" class="form-control input-sm" name="total_fat" /></td>
    <th>Sat. Fat</th>
    <td><input type="text" class="form-control input-sm" name="sat_fat" /></td>
    <th>Trans Fat</th>
    <td><input type="text" class="form-control input-sm" name="trans_fat" /></td>
</tr>
<tr>
    <th>Cholest.</th>
    <td><input type="text" class="form-control input-sm" name="cholest" /></td>
    <th>Sodium</th>
    <td><input type="text" class="form-control input-sm" name="sodium" /></td>
    <th>Total Carb</th>
    <td><input type="text" class="form-control input-sm" name="total_carb" /></td>
</tr>
<tr>
    <th>Fiber</th>
    <td><input type="text" class="form-control input-sm" name="fiber" /></td>
    <th>Sugar</th>
    <td><input type="text" class="form-control input-sm" name="sugar" /></td>
    <th>Protein</th>
    <td><input type="text" class="form-control input-sm" name="protein" /></td>
</tr>
HTML;
        for ($i=1; $i<=4; $i++) {
            $ret .= sprintf('<tr><th>Nutrient #%d</th>
                <td colspan="3"><input type="text" class="nutrient-in form-control input-sm" name="nutrient[]" /></td>
                <th>%%Pct</th>
                <td><input type="text" class="form-control input-sm dv-in" name="percent[]" /></td>
                </tr>', $i);
        }
        $ret .= '</table>
            <p>
                <button type="submit" class="btn btn-default">Get Image</button>
            </p>
            </form>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertEquals('0g', $this->normalizeVal(''));
        $phpunit->assertEquals('1g', $this->normalizeVal('1'));
        $phpunit->assertEquals(0, $this->numericVal(''));
        $phpunit->assertEquals(1, $this->numericVal('1'));
        $phpunit->assertEquals(1, $this->numericVal('1 g'));
    }

    public function helpContent()
    {
        return '<p>Enter as many values as necessary to generate an image of a
nutrition facts box. Consider this fairly alpha</p>'; 
    }
}

FannieDispatch::conditionalExec();

