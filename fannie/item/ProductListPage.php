<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
if (!function_exists('login'))
    include($FANNIE_ROOT.'auth/login.php');

class ProductListPage extends \COREPOS\Fannie\API\FannieReportTool 
{
    public $description = '[Product List] is a cross between a report and a tool. It lists current item prices and status flags for a department or set of departments but also allows editing.';
    protected $title = 'Fannie - Product List';
    protected $header = 'Product List';

    private $mode = 'form';

    private $canDeleteItems = false;
    private $canEditItems = false;

    private $excel = false;

    function preprocess()
    {
        $this->canDeleteItems = validateUserQuiet('delete_items');
        $this->canEditItems = validateUserQuiet('pricechange');

        $this->excel = FormLib::get('excel', false);

        if ($this->excel) {
            echo $this->list_content();
            return false;
        }

        if (FormLib::get('ajax') !== ''){
            $this->ajax_response();
            return false;
        }

        if (FormLib::get('supertype') !== ''){
            $this->mode = 'list';
        }

        $this->addScript('productList.js');
        if ($this->canEditItems) {
            $this->addOnloadCommand('productList.enableEditing()');
        }

        return true;
    }

    function javascript_content()
    {
        global $FANNIE_OP_DB;

        if ($this->excel) return '';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $depts = array();
        $prep = $dbc->prepare('SELECT dept_no,dept_name FROM departments ORDER BY dept_no');
        $result = $dbc->execute($prep);
        while($row = $dbc->fetchRow($result))
            $depts[$row[0]] = $row[1];
        $taxes = array('-'=>array(0,'NoTax'));
        $prep = $dbc->prepare('SELECT id, description FROM taxrates ORDER BY id');
        $result = $dbc->execute($prep);
        while($row = $dbc->fetchRow($result)){
            if ($row['id'] == 1)
                $taxes['X'] = array(1,'Regular');
            else
                $taxes[strtoupper(substr($row[1],0,1))] = array($row[0], $row[1]);
        }
        $local_opts = array('-'=>array(0,'No'));
        $origins = new OriginsModel($dbc);
        $local_origins = $origins->getLocalOrigins();
        foreach ($local_origins as $originID => $shortName) {
            $local_opts[substr($shortName,0,1)] = array($originID,$shortName);
        }
        if (count($local_opts) == 1) $local_opts['X'] = array(1,'Yes'); // generic local if no origins defined
        $vendors = array('', 'DIRECT');
        $vModel = new VendorsModel($dbc);
        foreach ($vModel->find('vendorName') as $v) {
            $vendors[] = $v->vendorName();
        }
        ob_start();
        ?>
        var deptObj = <?php echo json_encode($depts); ?>;
        var taxObj = <?php echo json_encode($taxes); ?>;
        var localObj = <?php echo json_encode($local_opts); ?>;
        var vendorObj = <?php echo json_encode($vendors); ?>;
        <?php

        return ob_get_clean();
    }

    private function ajax_response()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        switch(FormLib::get('ajax')){
        case 'save':
            $upc = FormLib::get('upc');
            $store_id = FormLib::get('store_id');
            $upc = BarcodeLib::padUPC($upc);
            $form = new COREPOS\common\mvc\FormValueContainer();
            $this->saveItem($dbc, $upc, $store_id, $form);
            break;  
        case 'deleteCheck':
            $upc = FormLib::get('upc');
            $upc = BarcodeLib::padUPC($upc);
            $encoded_desc = FormLib::get('desc');
            $desc = base64_decode($encoded_desc);
            $json = $this->getItemInfo($dbc, $upc, $desc);
            echo json_encode($json);
            break;
        case 'doDelete':
            $upc = FormLib::get('upc');
            $upc = BarcodeLib::padUPC($upc);
            $this->deleteItem($dbc, $upc);
            break;
        default:
            echo 'Unknown Action';
            break;
        }
    }

    private $field_map = array(
        'brand' => 'brand',
        'desc' => 'description',
        'dept' => 'department',
        'price' => 'normal_price',
        'cost' => 'cost',
        'tax' => 'tax',
        'fs' => 'foodstam',
        'disc' => 'discount',
        'wgt' => 'scale',
        'local' => 'local',
    );

    private function saveItem($dbc, $upc, $store_id, $form)
    {
        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->store_id($store_id);
        foreach ($this->field_map as $field => $col) {
            try {
                $model->$col($form->{$field});
            } catch (Exception $ex) { }
        }

        $supplier = FormLib::get('supplier');
        /**
          Normalize free-form supplier text
          Look up corresponding vendor ID
        */
        $vendorID = '';
        $vendors = new VendorsModel($dbc);
        $vendors->vendorName($supplier);
        foreach ($vendors->find() as $obj) {
            $vendorID = $obj->vendorID();
            break;
        }
        if ($vendorID !== '') {
            $model->default_vendor_id($vendorID);
        }

        $model->save();

        $this->saveExtra($dbc, $upc, $form);

        if ($vendorID !== '') {
            $item = new VendorItemsModel($dbc);
            $item->createIfMissing($upc, $vendorID);
            $item->updateCostByUPC($upc, $cost, $vendorID);
        }
        
        COREPOS\Fannie\API\data\ItemSync::sync($upc);
    }

    private function saveExtra($dbc, $upc, $form)
    {
        try {
            $brand = $this->form->brand;
            $supplier = $this->form->supplier;

            $chkP = $dbc->prepare('SELECT upc FROM prodExtra WHERE upc=?');
            $chkR = $dbc->execute($chkP, array($upc));
            if ($dbc->numRows($chkR) > 0) {
                $extraP = $dbc->prepare('UPDATE prodExtra SET manufacturer=?, distributor=? WHERE upc=?');
                $dbc->execute($extraP, array($brand, $supplier,$upc));
            } else {
                $extraP = $dbc->prepare('INSERT INTO prodExtra
                                (upc, variable_pricing, margin, manufacturer, distributor)
                                VALUES
                                (?, 0, 0, ?, ?)');
                $dbc->execute($extraP, array($upc, $brand, $supplier));
            }
        } catch (Exception $ex) { }
    }

    private function getItemInfo($dbc, $upc, $desc)
    {
        $fetchP = $dbc->prepare("select normal_price,
            special_price,t.description,
            case when foodstamp = 1 then 'Yes' else 'No' end as fs,
            case when scale = 1 then 'Yes' else 'No' end as s
            from products as p left join taxrates as t
            on p.tax = t.id
            where upc=? and p.description=?");
        $fetchR = $dbc->execute($fetchP,array($upc, $desc));
        $fetchW = $dbc->fetchRow($fetchR);

        $ret = "Delete item $upc - $desc?\n";
        $ret .= "Normal price: ".rtrim($fetchW[0])."\n";
        $ret .= "Sale price: ".rtrim($fetchW[1])."\n";
        $ret .= "Tax: ".rtrim($fetchW[2])."\n";
        $ret .= "Foodstamp: ".rtrim($fetchW[3])."\n";
        $ret .= "Scale: ".rtrim($fetchW[4])."\n";

        return array(
            'alertBox'=>$ret,
            'upc'=>ltrim($upc, '0'),
            'enc_desc'=>$encoded_desc
        );
    }

    private function deleteItem($dbc, $upc)
    {
        $desc = base64_decode(FormLib::get('desc'));

        $update = new ProdUpdateModel($dbc);
        $update->upc($upc);
        $update->logUpdate(ProdUpdateModel::UPDATE_DELETE);
        
        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $model->delete();

        $model = new ProductUserModel($dbc);
        $model->upc($upc);
        $model->delete();

        $model = new ScaleItemsModel($dbc);
        $model->plu($upc);
        $model->delete();

        $delP = $dbc->prepare("delete from prodExtra where upc=?");
        $delXR = $dbc->execute($delP,array($upc));

        $delP = $dbc->prepare("DELETE FROM upcLike WHERE upc=?");
        $delR = $dbc->execute($delP,array($upc));

        COREPOS\Fannie\API\data\ItemSync::remove($upc);
    }

    private function list_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $supertype = FormLib::get('supertype','dept');
        $manufacturer = FormLib::get('manufacturer','');
        $mtype = FormLib::get('mtype','prefix');
        $deptStart = FormLib::get('deptStart',0);
        $deptEnd = FormLib::get('deptEnd',0);
        $deptMulti = FormLib::get('departments', array());
        $subDepts = FormLib::get('subdepts', array());
        $super = FormLib::get('deptSub');
        $vendorID = FormLib::get('vendor');
        $upc_list = FormLib::get('u', array());
        $inUse = FormLib::get('inUse', 1);
        $store = FormLib::get('store', 0);

        $sort = FormLib::get('sort','Department');   
        $order = 'dept_name';
        if ($sort === 'UPC') $order = 'i.upc';  
        elseif ($sort === 'Description') $order = 'i.description, i.upc';

        $ret = 'Report sorted by '.$sort.'<br />';
        if ($supertype == 'dept' && $super === ''){
            $ret .= 'Department '.$deptStart.' to '.$deptEnd.'<br />';
        } elseif ($supertype == 'dept'){
            $ret .= 'Sub department '.$super.'<br />';
        } elseif ($supertype == 'manu') {
            $ret .= _('Brand') . ' ' . $manufacturer . '<br />';
        } elseif ($supertype == 'vendor') {
            $vendor = new VendorsModel($dbc);
            $vendor->vendorID($vendorID);
            $vendor->load();
            $ret .= 'Vendor ' . $vendor->vendorName() . '<br />';            
        }
        $ret .= date("F j, Y, g:i a").'<br />'; 
        
        if (!$this->excel) {
            $ret .= '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post" id="excel-form">
                <input type="hidden" name="supertype" value="' . $supertype . '" />
                <input type="hidden" name="deptStart" value="' . $deptStart . '" />
                <input type="hidden" name="deptEnd" value="' . $deptEnd . '" />
                <input type="hidden" name="deptSub" value="' . $super . '" />
                <input type="hidden" name="manufacturer" value="' . $manufacturer . '" />
                <input type="hidden" name="mtype" value="' . $mtype . '" />
                <input type="hidden" name="vendor" value="' . $vendorID . '" />
                <input type="hidden" name="inUse" value="' . $inUse . '" />
                <input type="hidden" name="store" value="' . $store . '" />
                <input type="hidden" name="excel" value="yes" />';
            if (is_array($deptMulti)) {
                foreach ($deptMulti as $d) {
                    $ret .= '<input type="hidden" name="departments[]" value="' . $d . '" />';
                }
            }
            if (is_array($subDepts)) {
                foreach ($subDepts as $s) {
                    $ret .= '<input type="hidden" name="subdepts[]" value="' . $s . '" />';
                }
            }
            if (is_array($upc_list)) {
                foreach ($upc_list as $u) {
                    $ret .= '<input type="hidden" name="u[]" value="' . $u . '" />';
                }
            }
            $ret .= '</form>';
            $ret .= sprintf('<a href="" onclick="$(\'#excel-form\').submit();return false;">Save to Excel</a> 
                &nbsp; &nbsp; <a href="%s">Back</a><br />',
                basename(__FILE__));
        }

        /** base select clause and joins **/
        $query = "
            SELECT i.upc,
                i.description,
                i.brand,
                d.dept_name as department,
                i.normal_price,
                (CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
                (CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
                (CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
                (CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
                (CASE WHEN i.local > 0 AND o.originID IS NULL THEN 'X' 
                  WHEN i.local > 0 AND o.originID IS NOT NULL THEN LEFT(o.shortName,1) ELSE '-' END) as local,
                COALESCE(v.vendorName, x.distributor) AS distributor,
                i.cost,
                i.store_id,
                l.description AS storeName
            FROM products as i 
                LEFT JOIN departments as d ON i.department = d.dept_no
                LEFT JOIN taxrates AS t ON t.id = i.tax
                LEFT JOIN prodExtra as x on i.upc = x.upc
                LEFT JOIN vendors AS v ON i.default_vendor_id=v.vendorID
                LEFT JOIN Stores AS l ON i.store_id=l.storeID
                LEFT JOIN origins AS o ON i.local=o.originID";
        /** add extra joins if this lookup requires them **/
        if ($supertype == 'dept' && $super !== '') {
            if ($super >= 0) {
                $query .= ' LEFT JOIN superdepts AS s ON i.department=s.dept_ID ';                
            } elseif ($super == -2) {
                $query .= ' LEFT JOIN MasterSuperDepts AS s ON i.department=s.dept_ID ';                
            }
        } elseif ($supertype == 'vendor') {
            $query .= ' LEFT JOIN vendors AS z ON z.vendorName=x.distributor ';
        }
        /** build where clause and parameters based on
            the lookup type **/
        $query .= ' WHERE 1=1 ';
        $args = array();
        if ($supertype == 'dept') {
            // superdept requriements, if any
            if ($super !== '' && $super >= 0) {
                $query .= ' AND s.superID=? ';
                $args = array($super);
            } elseif ($super == -2) {
                $query .= ' AND s.superID <> 0 ';
            }

            // department requirements
            if ($deptStart != 0 && $deptEnd != 0) {
                $query .= ' AND i.department BETWEEN ? AND ? ';
                $args[] = $deptStart;
                $args[] = $deptEnd;
            } elseif (count($deptMulti) > 0) {
                list($inStr, $args) = $dbc->safeInClause($deptMulti, $args);
                $query .= ' AND i.department IN (' . $inStr . ') ';
            }

            // subdept requirements, if any
            if (is_array($subDepts) && count($subDepts) > 0) {
                list($inStr, $args) = $dbc->safeInClause($subDepts, $args);
                $query .= ' AND i.subdept IN (' . $inStr . ') ';
            }
        } elseif ($supertype == 'manu' && $mtype == 'prefix') {
            $query .= ' AND i.upc LIKE ? ';
            $args = array('%' . $manufacturer . '%');
        } elseif ($supertype == 'manu' && $mtype != 'prefix') {
            $query .= ' AND (i.brand LIKE ? OR x.manufacturer LIKE ?) ';
            $args = array('%' . $manufacturer . '%','%' . $manufacturer . '%');
        } elseif ($supertype == 'vendor') {
            $query .= ' AND (i.default_vendor_id=? OR z.vendorID=?) ';
            $args = array($vendorID, $vendorID);
        } elseif ($supertype == 'upc') {
            list($inStr, $args) = $dbc->safeInClause($upc_list, $args);
            $query .= ' AND i.upc IN (' . $inStr . ') ';
        } else {
            return 'Unknown search method';
        }
        $query .= ' AND i.inUse=' . ($inUse==1 ? 1 : 0) . ' ';
        if ($store > 0) {
            $query .= ' AND i.store_id=? ';
            $args[] = $store;
        }
        /** finish building query w/ order clause **/
        $query .= 'ORDER BY ' . $order;
        if ($order != "i.upc") {
            $query .= ",i.upc";
        }

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        if ($result === false) {
            return 'Search failed. Please see the system administrator and/or the log.';
        } elseif ($dbc->num_rows($result) == 0) {
            return 'No data found!';
        }

        $ret .= '<table class="table table-striped table-bordered tablesorter small">
            <thead>
            <tr>';
        $ret .= "<th>UPC</th><th>Brand</th><th>Description</th><th>Dept</th><th>" . _('Vendor') . "</th><th>Cost</th><th>Price</th>";
        $ret .= "<th>Tax</th><th>FS</th><th>Disc</th><th>Wg'd</th><th>Local</th>";
        if (!$this->excel && $this->canEditItems !== false) {
            $ret .= '<th>&nbsp;</th>';
        }
        $ret .= "</tr></thead><tbody>";

        $multi = ($this->config->get('STORE_MODE') == 'HQ') ? true : false;
        while ($row = $dbc->fetchRow($result)) {
            $ret .= $this->rowToTable($row, $multi);
        }
        $ret .= '</tbody></table>';

        if ($this->excel){
            header('Content-Type: application/ms-excel');
            header('Content-Disposition: attachment; filename="itemList.csv"');
            $array = \COREPOS\Fannie\API\data\DataConvert::htmlToArray($ret);
            $ret = \COREPOS\Fannie\API\data\DataConvert::arrayToCsv($array);
        } else {
            $this->add_script('../src/javascript/tablesorter/jquery.tablesorter.min.js');
            $this->add_onload_command("\$('.tablesorter').tablesorter();\n");
        }

        return $ret;
    }

    private function rowToTable($row, $multi)
    {
        $ret = '<tr id="'.$row[0].'">';
        $enc = base64_encode($row[1]);
        if (!$this->excel) {
            $ret .= "<td align=center class=\"td_upc\"><a href=ItemEditorPage.php?searchupc=$row[0]>$row[0]</a>"; 
            if ($multi) {
                $ret .= ' (' . substr($row['storeName'], 0, 1) . ')';
            }
            if ($this->canDeleteItems !== false) {
                $ret .= " <a href=\"\" onclick=\"productList.deleteCheck('$row[0]','$enc'); return false;\">";
                $ret .= \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</a>';
            }
            $ret .= '</td>';
            $ret .= '<input type="hidden" class="hidden_upc" value="'.$row[0].'" />';
            $ret .= '<input type="hidden" class="hidden_store_id" value="'.$row['store_id'].'" />';
        } else {
            $ret .= "<td align=center>$row[0]</td>";
        }
        $ret .= "<td align=center class=\"td_brand clickable\">{$row['brand']}</td>";
        $ret .= "<td align=center class=\"td_desc clickable\">{$row['description']}</td>";
        $ret .= "<td align=center class=\"td_dept clickable\">{$row['department']}</td>";
        $ret .= "<td align=center class=\"td_supplier clickable\">{$row['distributor']}</td>";
        $ret .= "<td align=center class=\"td_cost clickable\">".sprintf('%.3f',$row['cost'])."</td>";
        $ret .= "<td align=center class=\"td_price clickable\">{$row['normal_price']}</td>";
        $ret .= "<td align=center class=td_tax>{$row['Tax']}</td>";
        $ret .= "<td align=center class=td_fs>{$row['FS']}</td>";
        $ret .= "<td align=center class=td_disc>{$row['DISC']}</td>";
        $ret .= "<td align=center class=td_wgt>{$row['WGHd']}</td>";
        $ret .= "<td align=center class=td_local>{$row['local']}</td>";
        if (!$this->excel && $this->canEditItems !== false){
            $ret .= "<td align=center class=td_cmd><a href=\"\" 
                class=\"edit-link\"
                onclick=\"productList.edit(\$(this).closest('tr')); return false;\">"
                . \COREPOS\Fannie\API\lib\FannieUI::editIcon() . '</a>
                <a href="" class="save-link collapse"
                onclick="productList.save($(this).closest(\'tr\')); return false;">'
                . \COREPOS\Fannie\API\lib\FannieUI::saveIcon() . '</a></td>';
        }
        $ret .= "</tr>\n";

        return $ret;
    }

    private function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $deptFields = FormLib::standardDepartmentFields('deptSub'); 
        $brand = _('Brand');
        $brandName = _('Brand name');
        $vendors = new VendorsModel($dbc);
        $vOpts = $vendors->toOptions();
        $stores = '';
        if ($this->config->get('STORE_MODE') == 'HQ') { 
            $picker = FormLib::storePicker();
            $stores = '<div class="form-group form-inline">
                <label>Store</label> '
                . $picker['html']
                . '</div>';
        }

        return <<<HTML
<form method="get" action="ProductListPage.php">
    <ul class="nav nav-tabs" role="tablist">
        <li class="active"><a href="#dept-tab" data-toggle="tab"
            onclick="\$('#supertype').val('dept');">By Department</a></li>
        <li><a href="#manu-tab" data-toggle="tab"
            onclick="\$('#supertype').val('manu');">By Brand</a></li>
        <li><a href="#vendor-tab" data-toggle="tab"
            onclick="\$('#supertype').val('vendor');">By Vendor</a></li>
    </ul>
    <input id="supertype" name="supertype" type="hidden" value="dept" />
    <div class="tab-content">
        <p>
            <div class="tab-pane active" id="dept-tab">
                <div class="row form-horizontal">
                    <div class="col-sm-8">
                    {$deptFields}
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="manu-tab">
                <div class="form-group form-inline">
                    <label>{$brand}</label>
                    <input type=text name=manufacturer class="form-control" />
                </div>
                <div class="form-group form-inline">
                    <label><input type=radio name=mtype value=prefix checked />
                        UPC prefix</label>
                    <label><input type=radio name=mtype value=name />
                        {$brandName}</label>
                </div>
            </div>
            <div class="tab-pane" id="vendor-tab">
                <div class="form-group form-inline">
                    <label>Vendor</label>
                    <select name="vendor" class="form-control">
                    {$vOpts}
                    </select>
                </div>
            </div>
        </p>
        </div>
        <div class="form-group form-inline">
            <label>Sort by</label>
            <select name="sort" class="form-control">
                <option>Department</option>
                <option>UPC</option>
                <option>Description</option>
            </select> 
            <label>
                <input type=checkbox name="inUse" value="1" checked />
                In Use
            </label>
            |
            <label>
                <input type=checkbox name=excel />
                Excel
            </label>
        </div>
        {$stores}
        <p> 
            <button type=submit name=submit class="btn btn-default btn-core">Submit</button>
            <button type=reset id="reset-btn" class="btn btn-default btn-reset"
                onclick="\$('#super-id').val('').trigger('change');">Start Over</button>
        </p>
        </form>
HTML;
    }

    public function body_content()
    {
        if ($this->mode == 'form')
            return $this->form_content();
        elseif ($this->mode == 'list')
            return $this->list_content();
        else
            return 'Unknown error occurred';
    }

    public function css_content()
    {
        if (!$this->excel) {
            return '
                .tablesorter thead th {
                    cursor: hand;
                    cursor: pointer;
                }';
        }
    }

    function helpContent()
    {
        $ret = '<p>This tool lists basic attributes for a set of product. The list
            can be downloaded as a spreadsheet. The web list also provides
            editing.</p>';
        if ($this->mode == 'form') {
            $ret .= '<p>Products can be selected by department, super department,
                brand name, or brand UPC prefix.</p>';
        } elseif ($this->mode == 'list') {
            $ret .= '<p>Use column headers to sort the list. Click the pencil icon
                to edit a row. You can also click description, department, vendor,
                cost, or price to begin editing. If editing is not available, you
                probably need to log in. To save, click the disk icon or press
                enter.</p>';
        }

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $phpunit->assertNotEquals(0, strlen($this->css_content()));
        $phpunit->assertNotEquals(0, strlen($this->javascript_content()));
    }
}

FannieDispatch::conditionalExec();

