<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class EditItemsFromSearch extends FannieRESTfulPage
{
    protected $header = 'Edit Search Results';
    protected $title = 'Edit Search Results';

    public $description = '[Edit Search Results] takes a set of advanced search items and allows
    editing some fields on all items simultaneously. Must be accessed via Advanced Search.';

    protected $window_dressing = false;

    private $upcs = array();
    private $save_results = array();

    function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       $this->__routes[] = 'post<save>';
       return parent::preprocess();
    }

    function post_save_handler()
    {
        global $FANNIE_OP_DB;

        $upcs = FormLib::get('upc', array());
        if (!is_array($upcs) || empty($upcs)) {
            echo 'Error: invalid data';
            return false;
        }
        $dept = FormLib::get('dept');
        $tax = FormLib::get('tax');
        $local = FormLib::get('local');
        $brand = FormLib::get('brand');
        $vendor = FormLib::get('vendor');

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vlookup = new VendorsModel($dbc);
        for($i=0; $i<count($upcs); $i++) {
            $model = new ProductsModel($dbc);
            $upc = BarcodeLib::padUPC($upcs[$i]);
            $model->upc($upc);
            
            if (isset($dept[$i])) {
                $model->department($dept[$i]);
            }
            if (isset($tax[$i])) {
                $model->tax($tax[$i]);
            }
            if (isset($local[$i])) {
                $model->local($local[$i]);
            }
            if (isset($brand[$i])) {
                $model->brand($brand[$i]);
            }
            if (isset($vendor[$i])) {
                $vlookup->reset();
                $vlookup->vendorName($vendor[$i]);
                foreach ($vlookup->find('vendorID') as $obj) {
                    $model->default_vendor_id($obj->vendorID());
                    break;
                }
            }

            if (in_array($upc, FormLib::get('fs', array()))) {
                $model->foodstamp(1);
            } else {
                $model->foodstamp(0);
            }
            if (in_array($upc, FormLib::get('disc', array()))) {
                $model->discount(1);
            } else {
                $model->discount(0);
            }
            if (in_array($upc, FormLib::get('scale', array()))) {
                $model->scale(1);
            } else {
                $model->scale(0);
            }
            $model->modified(date('Y-m-d H:i:s'));

            $try = $model->save();
            if ($try) {
                $model->pushToLanes();
            } else {
                $this->save_results[] = 'Error saving item '.$upc;    
            }

            if ((isset($vendor[$i]) && $vendor[$i] != '') || (isset($brand[$i]) && $brand[$i] != '')) {
                $extra = new ProdExtraModel($dbc);
                $extra->upc($upc);
                if (isset($vendor[$i]) && $vendor[$i] != '') {
                    $extra->distributor($vendor[$i]);
                }
                if (isset($brand[$i]) && $brand[$i] != '') {
                    $extra->manufacturer($brand[$i]);
                }
                $extra->save();
            }

            $this->upcs[] = $upc;
        }

        return true;
    }

    function post_save_view()
    {
        $ret = '';
        if (!empty($this->save_results)) {
            $ret .= '<ul style="color:red;">';
            foreach($this->save_results as $msg) {
                $ret .= '<li>' . $msg . '</li>';
            }
            $ret .= '</ul>';
        } else {
            $ret .= '<ul style="color:green;"><li>Saved!</li></ul>';
        }

        return $ret . $this->post_u_view();
    }

    function post_u_handler()
    {
        if (!is_array($this->u)) {
            $this->u = array($this->u);
        }
        foreach($this->u as $postdata) {
            if (is_numeric($postdata)) {
                $this->upcs[] = BarcodeLib::padUPC($postdata);
            }
        }

        if (empty($this->upcs)) {
            echo 'Error: no valid data';
            return false;
        } else {
            return true;
        }
    }

    function post_u_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $this->add_script($FANNIE_URL.'/src/javascript/jquery.js');
        $this->add_css_file($FANNIE_URL.'/src/style.css');
        $ret = '';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $taxes = array(0 => 'NoTax');
        $taxerates = $dbc->query('SELECT id, description FROM taxrates');
        while($row = $dbc->fetch_row($taxerates)) {
            $taxes[$row['id']] = $row['description'];
        }

        $locales = array(0 => 'No');
        $origins = $dbc->query('SELECT originID, shortName FROM originName WHERE local=1');
        while($row = $dbc->fetch_row($origins)) {
            $locales[$row['originID']] = $row['shortName'];
        }

        $depts = array();
        $deptlist = $dbc->query('SELECT dept_no, dept_name FROM departments ORDER BY dept_no');
        while($row = $dbc->fetch_row($deptlist)) {
            $depts[$row['dept_no']] = $row['dept_name'];
        }

        $ret .= '<form action="EditItemsFromSearch.php" method="post">';
        $ret .= '<table cellpadding="4" cellspacing="0" border="1">';
        $ret .= '<tr>
                <th>UPC</th>
                <th>Description</th>
                <th>Brand</th>
                <th>Vendor</th>
                <th>Department</th>
                <th>Tax</th>
                <th>FS</th>
                <th>Scale</th>
                <th>Discountable</th>
                <th>Local</th>
                </tr>';
        $ret .= '<tr><th colspan="2">Change All &nbsp;&nbsp;&nbsp;<input type="reset" /></th>';

        /**
          List known brands from vendorItems as a drop down selection
          rather than free text entry. prodExtra remains an imperfect
          solution but this can at least start normalizing that data
        */
        $ret .= '<td><select onchange="updateAll(this.value, \'.brandField\');">';
        $ret .= '<option value=""></option>';
        $brands = $dbc->query('SELECT brand FROM vendorItems 
                        WHERE brand IS NOT NULL AND brand <> \'\' 
                        GROUP BY brand ORDER BY brand');
        while($row = $dbc->fetch_row($brands)) {
            $ret .= '<option>' . $row['brand'] . '</option>';
        }
        $ret .= '</select></td>';

        /**
          See brand above
        */
        $ret .= '<td><select onchange="updateAll(this.value, \'.vendorField\');">';
        $ret .= '<option value=""></option><option>DIRECT</option>';
        $vendors = $dbc->query('SELECT vendorName FROM vendors
                        GROUP BY vendorName ORDER BY vendorName');
        while($row = $dbc->fetch_row($vendors)) {
            $ret .= '<option>' . $row['vendorName'] . '</option>';
        }
        $ret .= '</select></td>';

        $ret .= '<td><select onchange="updateAll(this.value, \'.deptSelect\');">';
        foreach($depts as $num => $name) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $num, $num, $name);
        }
        $ret .= '</select></td>';

        $ret .= '<td><select onchange="updateAll(this.value, \'.taxSelect\');">';
        foreach($taxes as $num => $name) {
            $ret .= sprintf('<option value="%d">%s</option>', $num, $name);
        }
        $ret .= '</select></td>';

        $ret .= '<td><input type="checkbox" onchange="toggleAll(this, \'.fsCheckBox\');" /></td>';
        $ret .= '<td><input type="checkbox" onchange="toggleAll(this, \'.scaleCheckBox\');" /></td>';
        $ret .= '<td><input type="checkbox" onchange="toggleAll(this, \'.discCheckBox\');" /></td>';

        $ret .= '<td><select onchange="updateAll(this.value, \'.localSelect\');">';
        foreach($locales as $num => $name) {
            $ret .= sprintf('<option value="%d">%s</option>', $num, $name);
        }
        $ret .= '</select></td>';

        $ret .= '</tr>';

        $info = $this->arrayToParams($this->upcs);
        $query = 'SELECT p.upc, p.description, p.department, d.dept_name,
                    p.tax, p.foodstamp, p.discount, p.scale, p.local,
                    x.manufacturer, x.distributor
                  FROM products AS p
                  LEFT JOIN departments AS d ON p.department=d.dept_no
                  LEFT JOIN prodExtra AS x ON p.upc=x.upc
                  WHERE p.upc IN (' . $info['in'] . ')
                  ORDER BY p.upc';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $info['args']);
        while($row = $dbc->fetch_row($result)) {
            $deptOpts = '';
            foreach($depts as $num => $name) {
                $deptOpts .= sprintf('<option %s value="%d">%d %s</option>',
                                    ($num == $row['department'] ? 'selected' : ''),
                                    $num, $num, $name);
            }
            $taxOpts = '';
            foreach($taxes as $num => $name) {
                $taxOpts .= sprintf('<option %s value="%d">%s</option>',
                                    ($num == $row['tax'] ? 'selected' : ''),
                                    $num, $name);
            }
            $localOpts = '';
            foreach($locales as $num => $name) {
                $localOpts .= sprintf('<option %s value="%d">%s</option>',
                                    ($num == $row['local'] ? 'selected' : ''),
                                    $num, $name);
            }
            $ret .= sprintf('<tr>
                            <td>
                                <a href="ItemEditorPage.php?searchupc=%s" target="_edit%s">%s</a>
                                <input type="hidden" class="upcInput" name="upc[]" value="%s" />
                            </td>
                            <td>%s</td>
                            <td><input type="text" name="brand[]" class="brandField" value="%s" /></td>
                            <td><input type="text" name="vendor[]" class="vendorField" value="%s" /></td>
                            <td><select name="dept[]" class="deptSelect">%s</select></td>
                            <td><select name="tax[]" class="taxSelect">%s</select></td>
                            <td><input type="checkbox" name="fs[]" class="fsCheckBox" value="%s" %s /></td>
                            <td><input type="checkbox" name="scale[]" class="scaleCheckBox" value="%s" %s /></td>
                            <td><input type="checkbox" name="disc[]" class="discCheckBox" value="%s" %s /></td>
                            <td><select name="local[]" class="localSelect">%s</select></td>
                            </tr>',
                            $row['upc'], $row['upc'], $row['upc'],
                            $row['upc'],
                            $row['description'],
                            $row['manufacturer'],
                            $row['distributor'],
                            $deptOpts,
                            $taxOpts,
                            $row['upc'], ($row['foodstamp'] == 1 ? 'checked' : ''),
                            $row['upc'], ($row['scale'] == 1 ? 'checked' : ''),
                            $row['upc'], ($row['discount'] == 1 ? 'checked' : ''),
                            $localOpts
            );
        }
        $ret .= '</table>';

        $ret .= '<br />';
        $ret .= '<input type="submit" name="save" value="Save Changes" />';
        $ret .= '</form>';

        return $ret;
    }

    function javascript_content()
    {
        ob_start();
        ?>
function toggleAll(elem, selector) {
    if (elem.checked) {
        $(selector).attr('checked', true);
    } else {
        $(selector).attr('checked', false);
    }
}
function updateAll(val, selector) {
    $(selector).val(val);
}
        <?php
        return ob_get_clean();
    }

    private function arrayToParams($arr) {
        $str = '';
        $args = array();
        foreach($arr as $entry) {
            $str .= '?,';
            $args[] = $entry;
        }
        $str = substr($str, 0, strlen($str)-1);

        return array('in'=>$str, 'args'=>$args);
    }
}

FannieDispatch::conditionalExec();

