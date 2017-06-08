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
    public $themed = true;
    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

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
        $discount = FormLib::get('discount');

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vlookup = new VendorsModel($dbc);
        $model = new StoresModel($dbc);
        $model->hasOwnItems(1);
        $stores = $model->find();
        $model = new ProductsModel($dbc);
        $extra = new ProdExtraModel($dbc);
        for ($i=0; $i<count($upcs); $i++) {
            $upc = BarcodeLib::padUPC($upcs[$i]);
            $model->reset();
            $model->upc($upc);
            
            $model = $this->setArrayValue($model, 'department', $dept, $i);
            $model = $this->setArrayValue($model, 'tax', $tax, $i);
            $model = $this->setArrayValue($model, 'local', $local, $i);
            $model = $this->setArrayValue($model, 'brand', $brand, $i);
            if (isset($vendor[$i])) {
                $model = $this->setVendorByName($model, $vlookup, $vendor[$i]);
            }
            $model = $this->setDiscounts($model, $discount, $i);

            $model = $this->setBinaryFlag($model, $upc, 'fs', 'foodstamp');
            $model = $this->setBinaryFlag($model, $upc, 'scale', 'scale');
            $model = $this->setBinaryFlag($model, $upc, 'inUse', 'inUse');
            $model->modified(date('Y-m-d H:i:s'));

            if ($this->config->get('STORE_MODE') == 'HQ') {
                foreach ($stores as $store) {
                    $model->store_id($store->storeID());
                    $try = $model->save();
                }
            } else {
                $try = $model->save();
            }
            if ($try && count($upcs) <= 10) {
                $model->pushToLanes();
            } elseif (!$try) {
                $this->save_results[] = 'Error saving item '.$upc;    
            }

            if ((isset($vendor[$i]) && $vendor[$i] != '') || (isset($brand[$i]) && $brand[$i] != '')) {
                $this->updateProdExtra($extra, $upc,
                    isset($brand[$i]) ? $brand[$i] : '',
                    isset($vendor[$i]) ? $vendor[$i] : '');
            }

            $this->upcs[] = $upc;
        }

        return true;
    }

    private function setDiscounts($pmodel, $arr, $index)
    {
        if (!isset($arr[$index])) {
            return $pmodel;
        }

        if ($arr[$index] == 1 || $arr[$index] == 2) {
            $pmodel->discount(1);
        } else {
            $pmodel->discount(0);
        }
        if ($arr[$index] == 1 || $arr[$index] == 3) {
            $pmodel->line_item_discountable(1);
        } else {
            $pmodel->line_item_discountable(0);
        }

        return $pmodel;
    }

    private $vcache = array();
    private function setVendorByName($pmodel, $vmodel, $name)
    {
        if (isset($this->vcache[$name])) {
            $pmodel->default_vendor_id($this->vcache[$name]);
        } else {
            $vmodel->reset();
            $vmodel->vendorName($name);
            foreach ($vmodel->find('vendorID') as $obj) {
                $pmodel->default_vendor_id($obj->vendorID());
                $this->vcache[$name] = $obj->vendorID();
                break;
            }
        }

        return $pmodel;
    }

    private function setArrayValue($model, $column, $arr, $index)
    {
        if (isset($arr[$index])) {
            $model->$column($arr[$index]);
        }

        return $model;
    }

    private function setBinaryFlag($model, $upc, $field, $column)
    {
        if (in_array($upc, FormLib::get($field, array()))) {
            $model->$column(1);
        } else {
            $model->$column(0);
        }

        return $model;
    }

    private function updateProdExtra($extra, $upc, $brand, $vendor)
    {
        $extra->upc($upc);
        $extra->distributor($vendor);
        $extra->manufacturer($brand);
        $extra->save();
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

    private function getTaxes($dbc)
    {
        $taxes = array(0 => 'NoTax');
        $taxerates = $dbc->query('SELECT id, description FROM taxrates');
        while($row = $dbc->fetch_row($taxerates)) {
            $taxes[$row['id']] = $row['description'];
        }

        return $taxes;
    } 

    private function getDepts($dbc)
    {
        $depts = array();
        $deptlist = $dbc->query('SELECT dept_no, dept_name FROM departments ORDER BY dept_no');
        while($row = $dbc->fetch_row($deptlist)) {
            $depts[$row['dept_no']] = $row['dept_name'];
        }

        return $depts;
    }

    private function getBrandOpts($dbc)
    {
        $ret = '<option value=""></option>';
        $brands = $dbc->query('
            SELECT brand 
            FROM vendorItems 
            WHERE brand IS NOT NULL AND brand <> \'\' 
            GROUP BY brand 
            
            UNION 

            SELECT brand 
            FROM products 
            WHERE brand IS NOT NULL AND brand <> \'\' 
            GROUP BY brand 
            
            
            ORDER BY brand');
        while($row = $dbc->fetch_row($brands)) {
            $ret .= '<option>' . $row['brand'] . '</option>';
        }

        return $ret;
    }

    private function arrayToOpts($arr, $selected=-999, $id_label=false)
    {
        $opts = '';
        foreach ($arr as $num => $name) {
            if ($id_label === true) {
                $name = $num . ' ' . $name;
            }
            $opts .= sprintf('<option %s value="%d">%s</option>',
                                ($num == $selected ? 'selected' : ''),
                                $num, $name);
        }

        return $opts;
    }

    function post_u_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $ret = '';

        $dbc = FannieDB::get($FANNIE_OP_DB);

        $locales = array(0 => 'No');
        $origin = new OriginsModel($dbc);
        $locales = array_merge($locales, $origin->getLocalOrigins());
        $taxes = $this->getTaxes($dbc);
        $depts = $this->getDepts($dbc);

        $hidden = implode("\n", array_map(function ($i) { return '<input name="u[]" type="hidden" value="' .$i . '" />'; }, $this->u)); 
        $ret .= <<<HTML
<form action="EditFieldFromSearch.php" method="post">
    {$hidden}
    <button type="submit" class="btn btn-default">Edit A Different Field</button>
</form>
<br />
HTML;


        $ret .= '<form action="EditItemsFromSearch.php" method="post">';
        $ret .= '<table class="table small">';
        $ret .= '<tr>
                <th>UPC</th>
                <th>Description</th>
                <th>Brand</th>
                <th>Vendor</th>
                <th>Department</th>
                <th>Tax</th>
                <th>FS</th>
                <th>Scale</th>
                <th>%Disc</th>
                <th>Local</th>
                <th>InUse</th>
                </tr>';
        $ret .= '<tr><th colspan="2">Change All &nbsp;&nbsp;&nbsp;<button type="reset" 
                class="btn btn-default">Reset</button></th>';

        /**
          List known brands from vendorItems as a drop down selection
          rather than free text entry. prodExtra remains an imperfect
          solution but this can at least start normalizing that data
        */
        $ret .= '<td><select class="form-control input-sm" onchange="updateAll(this.value, \'.brandField\');">';
        $ret .= $this->getBrandOpts($dbc);
        $ret .= '</select></td>';

        /**
          See brand above
        */
        $ret .= '<td><select class="form-control input-sm" onchange="updateAll(this.value, \'.vendorField\');">';
        $ret .= '<option value=""></option><option>DIRECT</option>';
        $res = $dbc->query('SELECT vendorName FROM vendors ORDER BY vendorName');
        while ($row = $dbc->fetchRow($res)) {
            $ret .= '<option>' . $row['vendorName'] . '</option>';
        }
        $ret .= '</select></td>';

        $ret .= '<td><select class="form-control input-sm" onchange="updateAll(this.value, \'.deptSelect\');">';
        $ret .= $this->arrayToOpts($depts, -999, true);
        $ret .= '</select></td>';

        $ret .= '<td><select class="form-control input-sm" onchange="updateAll(this.value, \'.taxSelect\');">';
        $ret .= $this->arrayToOpts($taxes);
        $ret .= '</select></td>';

        $ret .= '<td><input type="checkbox" onchange="toggleAll(this, \'.fsCheckBox\');" /></td>';
        $ret .= '<td><input type="checkbox" onchange="toggleAll(this, \'.scaleCheckBox\');" /></td>';
        $ret .= '<td><select class="form-control input-sm" onchange="updateAll(this.value, \'.discSelect\');">';
        $ret .= $this->discountOpts(0, 0);
        $ret .= '</select></td>';

        $ret .= '<td><select class="form-control input-sm" onchange="updateAll(this.value, \'.localSelect\');">';
        $ret .= $this->arrayToOpts($locales);
        $ret .= '</select></td>';
        $ret .= '<td><input type="checkbox" onchange="toggleAll(this, \'.inUseCheckBox\');" /></td>';

        $ret .= '</tr>';

        list($in_sql, $args) = $dbc->safeInClause($this->upcs);
        $query = 'SELECT p.upc, p.description, p.department, d.dept_name,
                    p.tax, p.foodstamp, p.discount, p.scale, p.local,
                    x.manufacturer, x.distributor, p.line_item_discountable,
                    p.inUse
                  FROM products AS p
                  LEFT JOIN departments AS d ON p.department=d.dept_no
                  LEFT JOIN prodExtra AS x ON p.upc=x.upc
                  WHERE p.upc IN (' . $in_sql . ')
                  ORDER BY p.upc';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
        while ($row = $dbc->fetch_row($result)) {
            $deptOpts = $this->arrayToOpts($depts, $row['department'], true);
            $taxOpts = $this->arrayToOpts($taxes, $row['tax']);
            $localOpts = $this->arrayToOpts($locales, $row['local']);
            $ret .= sprintf('<tr>
                            <td>
                                <a href="ItemEditorPage.php?searchupc=%s" target="_edit%s">%s</a>
                                <input type="hidden" class="upcInput" name="upc[]" value="%s" />
                            </td>
                            <td>%s</td>
                            <td><input type="text" name="brand[]" class="brandField form-control input-sm" value="%s" /></td>
                            <td><input type="text" name="vendor[]" class="vendorField form-control input-sm" value="%s" /></td>
                            <td><select name="dept[]" class="deptSelect form-control input-sm">%s</select></td>
                            <td><select name="tax[]" class="taxSelect form-control input-sm">%s</select></td>
                            <td><input type="checkbox" name="fs[]" class="fsCheckBox" value="%s" %s /></td>
                            <td><input type="checkbox" name="scale[]" class="scaleCheckBox" value="%s" %s /></td>
                            <td><select class="form-control input-sm discSelect" name="discount[]">%s</select></td>
                            <td><select name="local[]" class="localSelect form-control input-sm">%s</select></td>
                            <td><input type="checkbox" name="inUse[]" class="inUseCheckBox" value="%s" %s /></td>
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
                            $this->discountOpts($row['discount'], $row['line_item_discountable']),
                            $localOpts,
                            $row['upc'], ($row['inUse'] == 1 ? 'checked' : '')
            );
        }
        $ret .= '</table>';

        $ret .= '<p>';
        $ret .= '<button type="submit" name="save" class="btn btn-default" value="1">Save Changes</button>';
        $ret .= '</form>';

        return $ret;
    }

    private function discountOpts($reg, $line)
    {
        $opts = array('No', 'Yes', 'Trans Only', 'Line Only');
        $index = 0;
        if ($reg == 1 && $line == 1) {
            $index = 1;
        } elseif ($reg == 1 && $line == 0) {
            $index = 2;
        } elseif ($reg == 0 && $line == 1) {
            $index = 3;
        }

        $ret = '';
        foreach ($opts as $key => $val) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($index == $key ? 'selected' : ''), $key, $val);
        }

        return $ret;
    }

    function javascript_content()
    {
        ob_start();
        ?>
function toggleAll(elem, selector) {
    if (elem.checked) {
        $(selector).prop('checked', true);
    } else {
        $(selector).prop('checked', false);
    }
}
function updateAll(val, selector) {
    $(selector).val(val);
}
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            This tool edits some attributes of several products
            at once. The set of products must be built first
            using advanced search.
            </p>
            <p>
            Editing the top row will apply the change to all
            products in the list. Editing individual rows will
            only change the product. Changes are not instantaneous.
            Clicking the save button when finished is required.
            </p>';
    }
    
    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->javascript_content()));
        $this->u = 'foo';
        $phpunit->assertEquals(false, $this->post_u_handler());
        $this->u = '4011';
        $phpunit->assertEquals(true, $this->post_u_handler());
        $phpunit->assertNotEquals(0, strlen($this->post_u_view()));
        $phpunit->assertNotEquals(0, strlen($this->post_save_view()));
    }
}

FannieDispatch::conditionalExec();

