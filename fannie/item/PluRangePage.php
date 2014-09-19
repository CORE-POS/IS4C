<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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
            $model->description($desc . ' ' . ($i+1));
            $model->save();
        }

        header('Location: ItemEditorPage.php?searchupc=' . $this->start);

        return false;
    }

    public function get_length_number_handler()
    {
        global $FANNIE_OP_DB;
        if ($this->length < 1 || $this->length > 7) {
            echo 'Invalid length: ' . $this->length;
            return false;
        } else if ($this->number < 1) {
            echo 'Invalid range size: ' . $this->number;
            return false;
        } else if ($this->number > 15) {
            echo $this->number . ' is too many; range max is 15';
        }

        $min = '1' . str_repeat('0', $this->length-1);
        $max = str_repeat('9', $this->length);

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $actualMin = "SELECT MIN(upc) AS minimum
                      FROM products AS p
                      WHERE upc BETWEEN ? AND ?
                        AND upc NOT BETWEEN '0000000003000' AND '0000000004999'
                        AND upc NOT BETWEEN '0000000093000' AND '0000000094999'";
        $minP = $dbc->prepare($actualMin);
        $minR = $dbc->execute($minP, array(BarcodeLib::padUPC($min), BarcodeLib::padUPC($max)));
        if ($dbc->num_rows($minR) > 0) {
            $minW = $dbc->fetch_row($minR);
            $min = $minW['minimum'];
        }

        $current = $min;
        $range_start = false;
        $lookup = $dbc->prepare('SELECT upc FROM products WHERE upc=?');
        $count = 0;
        while($current < $max) {
            $check = $dbc->execute($lookup, BarcodeLib::padUPC($current));
            if ($count++ > 9999999) break; // prevent inf. loop
            if ($dbc->num_rows($check) > 0) {
                $current++;
            } else {
                // found an opening; check range
                $range_start = $current;
                for ($i=1; $i<$this->number; $i++) {
                    $check = $dbc->execute($lookup, array(BarcodeLib::padUPC($current + $i)));
                    if ($dbc->num_rows($check) > 0) {
                        // collision
                        $range_start = false;
                        $current = $current + $i + 1;
                        break;
                    }
                }
                if ($range_start !== false) {
                    break;
                }
            }
        }

        if ($range_start === false) {
            echo 'No range found';
            return false;
        } else {
            $this->start_plu = $range_start;
            return true;
        }

    }

    public function get_length_number_view()
    {
        global $FANNIE_OP_DB;
        $ret .= 'Open range found starting at ' . $this->start_plu; 
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<input type="hidden" name="start" value="' . $this->start_plu . '" />';
        $ret .= '<input type="hidden" name="number" value="' . $this->number . '" />';
        $ret .= '<table>';
        $ret .= '<tr><th>Placeholder Desc.</th><td><input type="text" name="description" /></td></tr>';
        $ret .= '<tr><th>Department</th><td><select name="department">';
        $depts = new DepartmentsModel(FannieDB::get($FANNIE_OP_DB));
        foreach($depts->find('dept_no') as $dept) {
            $ret .= sprintf('<option value="%d">%d %s</option>',
                                $dept->dept_no(),
                                $dept->dept_no(),
                                $dept->dept_name());
        }
        $ret .= '</td></tr>';
        $ret .= '<tr><td colspan="2"><input type="submit" value="Reserve PLUs" /></td></tr>';
        $ret .= '</table></form>';

        return $ret;
    }

    public function get_view()
    {
        // Produce ranges as of 19May14
        // 3000 through 4999
        // 93000 through 949999
        $ret = 'Find open PLU range';
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
        $ret .= '<table>';
        $ret .= '<tr><th>PLU Length</th>';
        $ret .= '<td><input type="text" name="length" size="4" value="4" /></td>';
        $ret .= '</tr><tr>';
        $ret .= '<th># needed</th>';
        $ret .= '<td><input type="text" name="number" size="4" value="1" /></td>';
        $ret .= '</tr><tr>';
        $ret .= '<td colspan="2"><input type="submit" name="Find PLUs" /></td>';
        $ret .= '</tr></table></form>';

        return $ret;
    }

}

FannieDispatch::conditionalExec();

