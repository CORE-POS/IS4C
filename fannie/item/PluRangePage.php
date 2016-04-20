<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include_once(dirname(__FILE__).'/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PluRangePage extends FannieRESTfulPage 
{

    protected $header = 'PLU Range';
    protected $title = 'PLU Range';
    private $start_plu = '';
    public $description = '[PLU Range] finds a range of consecutive unused PLU numbers.';
    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<length><number>';
        $this->__routes[] = 'post<start><number>';
        return parent::preprocess();
    }

    public function post_start_number_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB); 

        $dept_no = FormLib::get('department', 0);
        $desc = FormLib::get('description', 'NEW PLU');
        if (empty($desc)) {
            $desc = 'NEW PLU';
        }

        $dept = new DepartmentsModel($dbc);
        $dept->dept_no($dept_no);
        $dept->load();

        $model = new ProductsModel($dbc);
        $model->normal_price(0);
        $model->pricemethod(0);
        $model->quantity(0);
        $model->groupprice(0);
        $model->special_price(0);
        $model->specialpricemethod(0);
        $model->specialquantity(0);
        $model->specialgroupprice(0);
        $model->advertised(0);
        $model->tareweight(0);
        $model->start_date('');
        $model->end_date('');
        $model->discounttype(0);
        $model->wicable(0);
        $model->inUse(1);
        $model->tax($dept->dept_tax());
        $model->foodstamp($dept->dept_fs());
        $model->discount($dept->dept_discount());
        $model->department($dept_no);

        for ($i=0; $i<$this->number; $i++) {
            $upc = BarcodeLib::padUPC($this->start + $i);
            $model->upc($upc);
            $model->store_id(1);
            $model->description($desc . ' ' . ($i+1));
            $model->save();
        }

        header('Location: ItemEditorPage.php?searchupc=' . $this->start);

        return false;
    }

    private function validLengthNumber($length, $number)
    {
        if ($length < 1 || $length > 7) {
            throw new Exception('Invalid length: ' . $length);
        } elseif ($number < 1) {
            throw new Exception('Invalid range size: ' . $number);
        } elseif ($number > 15) {
            throw new Exception($number . ' is too many; range max is 15');
        }

        return true;
    }

    public function get_length_number_handler()
    {
        try {
            $this->validLengthNumber($this->length, $this->number);
            $min = '1' . str_repeat('0', $this->length-1);
            $max = str_repeat('9', $this->length);

            $dbc = FannieDB::get($this->config->get('OP_DB'));
            $actualMin = "SELECT MIN(upc) AS minimum
                          FROM products AS p
                          WHERE upc BETWEEN ? AND ?
                            AND upc NOT BETWEEN '0000000003000' AND '0000000004999'
                            AND upc NOT BETWEEN '0000000093000' AND '0000000094999'";
            if (FormLib::get('type') === 'Scale' && $this->length == 4 && $this->number == 1) {
                $min = '002' . $min . '000000';
                $max = '002' . $max . '000000';
            }
            $minP = $dbc->prepare($actualMin);
            $min = $dbc->getValue($minP, array(BarcodeLib::padUPC($min), BarcodeLib::padUPC($max)));

            $range_start = $this->findOpenRange($dbc, $min, $max);
            if ($range_start === false) {
                throw new Exception('No range found');
            } 

            $this->start_plu = $range_start;
            return true;
        } catch (Exception $ex) {
            echo $ex->getMessage();
            return false;
        }
    }

    private function findOpenRange($dbc, $min, $max) 
    {
        $current = $min;
        $range_start = false;
        $lookup = $dbc->prepare('SELECT upc FROM products WHERE upc=?');
        $count = 0;
        while ($current < $max) {
            $check = $dbc->getValue($lookup, BarcodeLib::padUPC($current));
            if ($count++ > 9999999) break; // prevent inf. loop
            if ($check !== false) {
                $current = $this->nextPlu($current);
            } else {
                // found an opening; check range
                $range_start = $current;
                for ($i=1; $i<$this->number; $i++) {
                    $check = $dbc->getValue($lookup, array(BarcodeLib::padUPC($current + $i)));
                    if ($check !== false) {
                        // collision
                        $range_start = false;
                        $current = $this->nextPlu($current);
                        break;
                    }
                }
                if ($range_start !== false) {
                    break;
                }
            }
        }

        return $range_start;
    }

    private function nextPlu($plu)
    {
        if (substr($plu, -6) === '000000') {
            $plu = substr($plu, 0, strlen($plu)-6);
            $plu++;
            return $plu . '000000';
        } else {
            return $plu + 1;
        }
    }

    public function get_length_number_view()
    {
        global $FANNIE_OP_DB;
        $ret = '<div class="well">Open range found starting at ' . $this->start_plu . '</div>'; 
        $ret .= '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">';
        $ret .= '<input type="hidden" name="start" value="' . $this->start_plu . '" />';
        $ret .= '<input type="hidden" name="number" value="' . $this->number . '" />';
        $ret .= '<div class="form-group">
            <label>Placeholder Desc.</label>
            <input type="text" name="description" class="form-control" required />
            </div>';
        $ret .= '<div class="form-group">
            <label>Department</label>
            <select name="department" class="form-control">';
        $depts = new DepartmentsModel(FannieDB::get($FANNIE_OP_DB));
        foreach ($depts->find('dept_no') as $dept) {
            $ret .= sprintf('<option value="%d">%d %s</option>',
                                $dept->dept_no(),
                                $dept->dept_no(),
                                $dept->dept_name());
        }
        $ret .= '</select></div>';
        $ret .= '<p><button type="submit" class="btn btn-default">Reserve PLUs</button></p>';
        $ret .= '</form>';

        return $ret;
    }

    public function get_view()
    {
        // Produce ranges as of 19May14
        // 3000 through 4999
        // 93000 through 949999
        $ret = '<div class="well">Find open PLU range</div>';
        $ret .= '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="get">';
        $ret .= '<div class="form-group">';
        $ret .= '<label>PLU Length</label>';
        $ret .= '<input type="number" name="length" class="form-control" 
                    required value="4" />';
        $ret .= '</div>';
        $ret .= '<div class="form-group">';
        $ret .= '<label># needed</label>';
        $ret .= '<input type="number" name="number" class="form-control" 
                    required value="1" />';
        $ret .= '</div>
            <div class="form-group">
            <label>PLU Type</label>
            <select name="type" class="form-control">
                <option>Regular</option>
                <option>Scale</option>
            </select></div>
        ';
        $ret .= '<p><button type="submit" class="btn btn-default">Find PLUs</button></p>';
        $ret .= '</form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Find a block of available PLU numbers. The PLU length 
            is the number of digits in the PLU. The number needed
            refers to how many are needed. Setting number needed to
            three will attempt to find three <em>consecutive</em>
            PLU numbers that are not in use.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->length = 10;
        ob_start();
        $phpunit->assertEquals(false, $this->get_length_number_handler());
        ob_get_clean();
        $this->length = 4;
        $this->number = 0;
        ob_start();
        $phpunit->assertEquals(false, $this->get_length_number_handler());
        ob_get_clean();
        $this->number = 99;
        ob_start();
        $phpunit->assertEquals(false, $this->get_length_number_handler());
        ob_get_clean();
        $this->number = 1;
        ob_start();
        $phpunit->assertEquals(true, $this->get_length_number_handler());
        ob_get_clean();
        $phpunit->assertNotEquals(0, strlen($this->get_length_number_view()));
    }

}

FannieDispatch::conditionalExec();

