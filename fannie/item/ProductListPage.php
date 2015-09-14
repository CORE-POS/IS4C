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
if (!function_exists('addProductAllLanes')) {
    require('laneUpdates.php');
}
if (!function_exists('login'))
    include($FANNIE_ROOT.'auth/login.php');
if (!function_exists('HtmlToArray')) {
    include_once($FANNIE_ROOT.'src/ReportConvert/HtmlToArray.php');
}
if (!function_exists('ArrayToCsv')) {
    include_once($FANNIE_ROOT.'src/ReportConvert/ArrayToCsv.php');
}

class ProductListPage extends \COREPOS\Fannie\API\FannieReportTool 
{
    public $description = '[Product List] is a cross between a report and a tool. It lists current item prices and status flags for a department or set of departments but also allows editing.';
    public $themed = true;

    protected $title = 'Fannie - Product List';
    protected $header = 'Product List';

    private $mode = 'form';

    private $canDeleteItems = False;
    private $canEditItems = False;

    private $excel = False;

    function preprocess(){
        global $FANNIE_URL, $FANNIE_WINDOW_DRESSING;

        $this->canDeleteItems = validateUserQuiet('delete_items');
        $this->canEditItems = validateUserQuiet('pricechange');

        $this->excel = FormLib::get_form_value('excel',False);

        if ($this->excel) {
            echo $this->list_content();
            return false;
        }

        if (FormLib::get_form_value('ajax') !== ''){
            $this->ajax_response();
            return false;
        }

        if (FormLib::get_form_value('supertype') !== ''){
            $this->mode = 'list';
        }

        return true;
    }

    function javascript_content()
    {
        global $FANNIE_URL, $FANNIE_OP_DB;

        if ($this->excel) return '';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $depts = array();
        $p = $dbc->prepare_statement('SELECT dept_no,dept_name FROM departments ORDER BY dept_no');
        $result = $dbc->exec_statement($p);
        while($w = $dbc->fetch_row($result))
            $depts[$w[0]] = $w[1];
        $taxes = array('-'=>array(0,'NoTax'));
        $p = $dbc->prepare_statement('SELECT id, description FROM taxrates ORDER BY id');
        $result = $dbc->exec_statement($p);
        while($w = $dbc->fetch_row($result)){
            if ($w['id'] == 1)
                $taxes['X'] = array(1,'Regular');
            else
                $taxes[strtoupper(substr($w[1],0,1))] = array($w[0], $w[1]);
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
        function edit(elem){
            var brand = elem.find('.td_brand:first').html();
            var content = "<input type=text class=\"in_brand form-control input-sm\" size=8 value=\""+brand+"\" />";   
            elem.find('.td_brand:first').html(content);

            var desc = elem.find('.td_desc:first').html();
            var content = "<input type=text class=\"in_desc form-control input-sm\" size=10 value=\""+desc+"\" />";   
            elem.find('.td_desc:first').html(content);

            var dept = elem.find('.td_dept:first').text();
            var content = '<select class=\"in_dept form-control input-sm\"><optgroup style="font-size: 90%;">';
            for(dept_no in deptObj){
                content += "<option value=\""+dept_no+"\" "+((dept==deptObj[dept_no])?'selected':'')+">";
                content += deptObj[dept_no]+"</option>";
            }
            content += '</optgroup></select>';
            elem.find('.td_dept:first').html(content);

            var supplier = elem.find('.td_supplier:first').text();
            var content = '<select class=\"in_supplier form-control input-sm\"><optgroup style="font-size: 90%;">';
            for(var i in vendorObj){
                content += "<option "+((supplier==vendorObj[i])?'selected':'')+">";
                content += vendorObj[i]+"</option>";
            }
            content += '</optgroup></select>';
            elem.find('.td_supplier:first').html(content);

            var cost = elem.find('.td_cost:first').html();
            var content = "<input type=text class=\"in_cost form-control input-sm\" size=4 value=\""+cost+"\" />";    
            elem.find('.td_cost:first').html(content);

            var price = elem.find('.td_price:first').html();
            var content = "<input type=text class=\"in_price form-control input-sm\" size=4 value=\""+price+"\" />";  
            elem.find('.td_price:first').html(content);

            var tax = elem.find('.td_tax:first').html();
            var content = '<select class=\"in_tax form-control input-sm\">';
            for (ch in taxObj){
                var sel = (tax == ch) ? 'selected' : '';
                content += "<option value=\""+ch+":"+taxObj[ch][0]+"\" "+sel+">";
                content += taxObj[ch][1]+"</option>";
            }
            elem.find('.td_tax:first').html(content);

            var fs = elem.find('.td_fs:first').html();
            var content = "<input type=checkbox class=in_fs "+((fs=='X')?'checked':'')+" />";
            elem.find('.td_fs:first').html(content);

            var disc = elem.find('.td_disc:first').html();
            var content = "<input type=checkbox class=in_disc "+((disc=='X')?'checked':'')+" />";
            elem.find('.td_disc:first').html(content);

            var wgt = elem.find('.td_wgt:first').html();
            var content = "<input type=checkbox class=in_wgt "+((wgt=='X')?'checked':'')+" />";
            elem.find('.td_wgt:first').html(content);

            var local = elem.find('.td_local:first').html();
            //var content = "<input type=checkbox class=in_local "+((local=='X')?'checked':'')+" />";
            var content = '<select class=\"in_local form-control input-sm\">';
            for (ch in localObj){
                var sel = (local == ch) ? 'selected' : '';
                content += "<option value=\""+ch+":"+localObj[ch][0]+"\" "+sel+">";
                content += localObj[ch][1]+"</option>";
            }
            elem.find('.td_local:first').html(content);

            elem.find('.td_cmd:first .edit-link').hide();
            elem.find('.td_cmd:first .save-link').show();

            elem.find('input:text').keydown(function(event) {
                if (event.which == 13) {
                    save(elem);
                }
            });
            elem.find('.clickable input:text').click(function(event){
                // do nothing
                event.stopPropagation();
            });
            elem.find('.clickable select').click(function(event){
                // do nothing
                event.stopPropagation();
            });
        }
        function save(elem){
            var upc = elem.find('.hidden_upc:first').val();
            var store_id = elem.find('.hidden_store_id:first').val();

            var brand = elem.find('.in_brand:first').val();
            elem.find('.td_brand:first').html(brand);

            var desc = elem.find('.in_desc:first').val();
            elem.find('.td_desc:first').html(desc);
        
            var dept = elem.find('.in_dept:first').val();
            elem.find('.td_dept:first').html(deptObj[dept]);

            var supplier = elem.find('.in_supplier:first').val();
            elem.find('.td_supplier:first').html(supplier);

            mathField(elem.find('.in_cost:first').get(0));
            var cost = elem.find('.in_cost:first').val();
            elem.find('.td_cost:first').html(cost);

            var price = elem.find('.in_price:first').val();
            elem.find('.td_price:first').html(price);

            var tax = elem.find('.in_tax:first').val().split(':');
            elem.find('.td_tax:first').html(tax[0]);
            
            var fs = elem.find('.in_fs:first').is(':checked') ? 1 : 0;
            elem.find('.td_fs:first').html((fs==1)?'X':'-');

            var disc = elem.find('.in_disc:first').is(':checked') ? 1 : 0;
            elem.find('.td_disc:first').html((disc==1)?'X':'-');

            var wgt = elem.find('.in_wgt:first').is(':checked') ? 1 : 0;
            elem.find('.td_wgt:first').html((wgt==1)?'X':'-');

            var local = elem.find('.in_local:first').val().split(':');
            elem.find('.td_local:first').html(local[0]);

            elem.find('.td_cmd:first .edit-link').show();
            elem.find('.td_cmd:first .save-link').hide();

            var dstr = 'ajax=save&upc='+upc+'&desc='+desc+'&dept='+dept+'&price='+price+'&cost='+cost;
            dstr += '&tax='+tax[1]+'&fs='+fs+'&disc='+disc+'&wgt='+wgt+'&supplier='+supplier+'&local='+local[1];
            dstr += '&brand='+encodeURIComponent(brand);
            dstr += '&store_id='+store_id;
            $.ajax({
            url: 'ProductListPage.php',
            data: dstr,
            cache: false,
            type: 'post',
            success: function(data){
            }
            });
        }
        function deleteCheck(upc,desc){
            $.ajax({
            url: 'ProductListPage.php',
            data: 'ajax=deleteCheck&upc='+upc+'&desc='+desc,
            dataType: 'json',
            cache: false,
            type: 'post',
            success: function(data){
                if (data.alertBox && data.upc && data.enc_desc){
                    if (confirm(data.alertBox)){
                        $.ajax({
                        url: 'ProductListPage.php',
                        data: 'ajax=doDelete&upc='+upc+'&desc='+data.enc_desc,
                        cache: false,
                        type: 'post',
                        success: function(data){
                            $('#' + upc).remove();
                        }
                        });
                    }
                }
                else
                    alert('Data error: cannot delete');
            }
            });
        }
        <?php if ($this->canEditItems) { ?>
        $(document).ready(function(){
            $('tr').each(function(){
                if ($(this).find('.hidden_upc').length != 0) {
                    var upc = $(this).find('.hidden_upc').val();
                    $(this).find('.clickable').click(function() {
                        if ($(this).find(':input').length == 0) {
                            edit($(this).closest('tr'));
                            $(this).find(':input').select();
                        }
                    });
                }
            });
        });
        <?php
        }
        return ob_get_clean();
    }

    function ajax_response()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        switch(FormLib::get_form_value('ajax')){
        case 'save':
            $upc = FormLib::get_form_value('upc');
            $store_id = FormLib::get('store_id');
            $upc = BarcodeLib::padUPC($upc);
            $values = array();
            $model = new ProductsModel($dbc);
            $model->upc($upc);
            $model->store_id($store_id);
            $brand = FormLib::get('brand');
            if ($brand !== '') {
                $model->brand($brand);
            }
            $desc = FormLib::get_form_value('desc');
            if ($desc !== '') {
                $model->description($desc);
            }
            $dept = FormLib::get_form_value('dept');
            if ($dept !== '') {
                $model->department($dept);
            }
            $price = rtrim(FormLib::get_form_value('price'),' ');
            if ($price !== '') {
                $model->normal_price($price);
            }
            $cost = rtrim(FormLib::get_form_value('cost'), ' ');
            if ($cost !== '') {
                $model->cost($cost);
            }
            $tax = FormLib::get_form_value('tax');
            if ($tax !== '') {
                $model->tax($tax);
            }
            $fs = FormLib::get_form_value('fs');
            if ($fs !== '') {
                $model->foodstamp($fs);
            }
            $disc = FormLib::get_form_value('disc');
            if ($disc !== '') {
                $model->discount($disc);
            }
            $wgt = FormLib::get_form_value('wgt');
            if ($wgt !== '') {
                $model->scale($wgt);
            }
            $loc = FormLib::get_form_value('local');
            if ($loc !== '') {
                $model->local($loc);
            }
            $supplier = FormLib::get_form_value('supplier');
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

            $chkP = $dbc->prepare('SELECT upc FROM prodExtra WHERE upc=?');
            $chkR = $dbc->execute($chkP, array($upc));
            if ($dbc->num_rows($chkR) > 0) {
                $extraP = $dbc->prepare_statement('UPDATE prodExtra SET manufacturer=?, distributor=? WHERE upc=?');
                $dbc->exec_statement($extraP, array($brand, $supplier,$upc));
            } else {
                $extraP = $dbc->prepare('INSERT INTO prodExtra
                                (upc, variable_pricing, margin, manufacturer, distributor)
                                VALUES
                                (?, 0, 0, ?, ?)');

                $dbc->execute($extraP, array($upc, $brand, $supplier));
            }

            if ($vendorID !== '') {
                $item = new VendorItemsModel($dbc);
                $item->createIfMissing($upc, $vendorID);
                $item->updateCostByUPC($upc, $cost, $vendorID);
            }
            
            updateProductAllLanes($upc);
            break;  
        case 'deleteCheck':
            $upc = FormLib::get_form_value('upc');
            $upc = BarcodeLib::padUPC($upc);
            $encoded_desc = FormLib::get_form_value('desc');
            $desc = base64_decode($encoded_desc);
            $fetchP = $dbc->prepare_statement("select normal_price,
                special_price,t.description,
                case when foodstamp = 1 then 'Yes' else 'No' end as fs,
                case when scale = 1 then 'Yes' else 'No' end as s
                from products as p left join taxrates as t
                on p.tax = t.id
                where upc=? and p.description=?");
            $fetchR = $dbc->exec_statement($fetchP,array($upc, $desc));
            $fetchW = $dbc->fetch_array($fetchR);

            $ret = "Delete item $upc - $desc?\n";
            $ret .= "Normal price: ".rtrim($fetchW[0])."\n";
            $ret .= "Sale price: ".rtrim($fetchW[1])."\n";
            $ret .= "Tax: ".rtrim($fetchW[2])."\n";
            $ret .= "Foodstamp: ".rtrim($fetchW[3])."\n";
            $ret .= "Scale: ".rtrim($fetchW[4])."\n";

            $json = array(
                'alertBox'=>$ret,
                'upc'=>ltrim($upc, '0'),
                'enc_desc'=>$encoded_desc
            );
            echo json_encode($json);
            break;
        case 'doDelete':
            $upc = FormLib::get_form_value('upc');
            $upc = BarcodeLib::padUPC($upc);
            $desc = base64_decode(FormLib::get_form_value('desc'));

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

            $delP = $dbc->prepare_statement("delete from prodExtra where upc=?");
            $delXR = $dbc->exec_statement($delP,array($upc));

            $delP = $dbc->prepare_statement("DELETE FROM upcLike WHERE upc=?");
            $delR = $dbc->exec_statement($delP,array($upc));

            deleteProductAllLanes($upc);
            break;
        default:
            echo 'Unknown Action';
            break;
        }
    }

    function list_content()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $supertype = FormLib::get_form_value('supertype','dept');
        $manufacturer = FormLib::get_form_value('manufacturer','');
        $mtype = FormLib::get_form_value('mtype','prefix');
        $deptStart = FormLib::get_form_value('deptStart',0);
        $deptEnd = FormLib::get_form_value('deptEnd',0);
        $deptMulti = FormLib::get('departments', array());
        $subDepts = FormLib::get('subdepts', array());
        $super = FormLib::get_form_value('deptSub');
        $vendorID = FormLib::get('vendor');
        $upc_list = FormLib::get('u', array());
        $inUse = FormLib::get('inUse', 1);

        $sort = FormLib::get_form_value('sort','Department');   
        $order = 'dept_name';
        if ($sort === 'UPC') $order = 'i.upc';  
        elseif ($sort === 'Description') $order = 'i.description, i.upc';

        $ret = 'Report sorted by '.$sort.'<br />';
        if ($supertype == 'dept' && $super === ''){
            $ret .= 'Department '.$deptStart.' to '.$deptEnd.'<br />';
        } else if ($supertype == 'dept'){
            $ret .= 'Sub department '.$super.'<br />';
        } else if ($supertype == 'manu') {
            $ret .= _('Brand') . ' ' . $manufacturer . '<br />';
        } else if ($supertype == 'vendor') {
            $vendor = new VendorsModel($dbc);
            $vendor->vendorID($vendorID);
            $vendor->load();
            $ret .= 'Vendor ' . $vendor->vendorName() . '<br />';            
        }
        $ret .= date("F j, Y, g:i a").'<br />'; 
        
        $page_url = sprintf('ProductListPage.php?supertype=%s&deptStart=%s&deptEnd=%s&deptSub=%s&manufacturer=%s&mtype=%s&vendor=%d',
                $supertype, $deptStart, $deptEnd, $super, $manufacturer, $mtype, $vendorID);
        if (!$this->excel) {
            $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post" id="excel-form">
                <input type="hidden" name="supertype" value="' . $supertype . '" />
                <input type="hidden" name="deptStart" value="' . $deptStart . '" />
                <input type="hidden" name="deptEnd" value="' . $deptEnd . '" />
                <input type="hidden" name="deptSub" value="' . $super . '" />
                <input type="hidden" name="manufacturer" value="' . $manufacturer . '" />
                <input type="hidden" name="mtype" value="' . $mtype . '" />
                <input type="hidden" name="vendor" value="' . $vendorID . '" />
                <input type="hidden" name="inUse" value="' . $inUse . '" />
                <input type="hidden" name="excel" value="yes" />';
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
            $query .= ' LEFT JOIN superdepts AS s ON i.department=s.dept_ID ';                
        } elseif ($supertype == 'vendor') {
            $query .= ' LEFT JOIN vendors AS z ON z.vendorName=x.distributor ';
        }
        /** build where clause and parameters based on
            the lookup type **/
        $args = array();
        if ($supertype == 'dept' && $super !== '') {
            $query .= ' WHERE s.superID=? ';
            $args = array($super);
            if ($deptStart != 0 && $deptEnd != 0) {
                $query .= ' AND i.department BETWEEN ? AND ? ';
                $args[] = $deptStart;
                $args[] = $deptEnd;
            } elseif (count($deptMulti) > 0) {
                $query .= ' AND i.department IN (';
                foreach ($deptMulti as $d) {
                    $query .= '?,';
                    $args[] = $d;
                }
                $query = substr($query, 0, strlen($query)-1) . ')';
            }
            if (is_array($subDepts) && count($subDepts) > 0) {
                $query .= ' AND i.subdept IN (';
                foreach ($subDepts as $s) {
                    $query .= '?,';
                    $args[] = $s;
                }
                $query = substr($query, 0, strlen($query)-1) . ')';
            }
        } elseif ($supertype == 'manu' && $mtype == 'prefix') {
            $query .= ' WHERE i.upc LIKE ? ';
            $args = array('%' . $manufacturer . '%');
        } elseif ($supertype == 'manu' && $mtype != 'prefix') {
            $query .= ' WHERE (i.brand LIKE ? OR x.manufacturer LIKE ?) ';
            $args = array('%' . $manufacturer . '%','%' . $manufacturer . '%');
        } elseif ($supertype == 'vendor') {
            $query .= ' WHERE (i.default_vendor_id=? OR z.vendorID=?) ';
            $args = array($vendorID, $vendorID);
        } elseif ($supertype == 'upc') {
            $in = '';
            foreach ($upc_list as $u) {
                $in .= '?,';
                $args[] = $u;
            }
            $in = substr($in, 0, strlen($in)-1);
            $query .= ' WHERE i.upc IN (' . $in . ') ';
        } else {
            $query .= ' WHERE i.department BETWEEN ? AND ? ';
            $args = array($deptStart, $deptEnd);
            if (is_array($subDepts) && count($subDepts) > 0) {
                $query .= ' AND i.subdept IN (';
                foreach ($subDepts as $s) {
                    $query .= '?,';
                    $args[] = $s;
                }
            }
        }
        if ($inUse == 1) {
            $query .= ' AND i.inUse=1 ';
        } else {
            $query .= ' AND i.inUse=0 ';
        }
        /** finish building query w/ order clause **/
        $query .= 'ORDER BY ' . $order;
        if ($order != "i.upc") {
            $query .= ",i.upc";
        }

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep, $args);

        if ($result === false || $dbc->num_rows($result) == 0) {
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
        while ($row = $dbc->fetch_row($result)) {
            $ret .= '<tr id="'.$row[0].'">';
            $enc = base64_encode($row[1]);
            if (!$this->excel) {
                $ret .= "<td align=center class=\"td_upc\"><a href=ItemEditorPage.php?searchupc=$row[0]>$row[0]</a>"; 
                if ($multi) {
                    $ret .= ' (' . substr($row['storeName'], 0, 1) . ')';
                }
                if ($this->canDeleteItems !== false) {
                    $ret .= " <a href=\"\" onclick=\"deleteCheck('$row[0]','$enc'); return false;\">";
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
            $ret .= "<td align=center class=\"td_cost clickable\">".sprintf('%.2f',$row['cost'])."</td>";
            $ret .= "<td align=center class=\"td_price clickable\">{$row['normal_price']}</td>";
            $ret .= "<td align=center class=td_tax>{$row['Tax']}</td>";
            $ret .= "<td align=center class=td_fs>{$row['FS']}</td>";
            $ret .= "<td align=center class=td_disc>{$row['DISC']}</td>";
            $ret .= "<td align=center class=td_wgt>{$row['WGHd']}</td>";
            $ret .= "<td align=center class=td_local>{$row['local']}</td>";
            if (!$this->excel && $this->canEditItems !== False){
                $ret .= "<td align=center class=td_cmd><a href=\"\" 
                    class=\"edit-link\"
                    onclick=\"edit(\$(this).closest('tr')); return false;\">"
                    . \COREPOS\Fannie\API\lib\FannieUI::editIcon() . '</a>
                    <a href="" class="save-link collapse"
                    onclick="save($(this).closest(\'tr\')); return false;">'
                    . \COREPOS\Fannie\API\lib\FannieUI::saveIcon() . '</a></td>';
            }
            $ret .= "</tr>\n";
        }
        $ret .= '</tbody></table>';

        if ($this->excel){
            header('Content-Type: application/ms-excel');
            header('Content-Disposition: attachment; filename="itemList.csv"');
            $array = HtmlToArray($ret);
            $ret = ArrayToCsv($array);
        } else {
            $this->add_script('../src/javascript/tablesorter/jquery.tablesorter.min.js');
            $this->add_onload_command("\$('.tablesorter').tablesorter();\n");
        }

        return $ret;
    }

    function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $deptQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
        $deptR = $dbc->exec_statement($deptQ);
        $depts = array();
        while ($deptW = $dbc->fetch_array($deptR)){
            $depts[$deptW['dept_no']] = $deptW['dept_name'];
        }
        $superQ = $dbc->prepare_statement("SELECT superID,super_name FROM superDeptNames 
            ORDER BY superID");
        $superR = $dbc->exec_statement($superQ);
        $supers = array();
        while ($superW = $dbc->fetch_row($superR)){
            $supers[$superW['superID']] = $superW['super_name'];
        }
        $subs = array();
        if (count($depts) > 0) {
            $dept_numbers = array_keys($depts);
            $first = $dept_numbers[0];
            $subsP = $dbc->prepare('
                SELECT subdept_no,
                    subdept_name
                FROM subdepts
                WHERE dept_ID=?
                ORDER BY subdept_no');
            $subsR = $dbc->execute($subsP, array($first));
            while ($subsW = $dbc->fetch_row($subsR)) {
                $subs[$subsW['subdept_no']] = $subsW['subdept_name'];
            }
        }
        ob_start();
        ?>
        <form method="get" action="ProductListPage.php">
        <ul class="nav nav-tabs" role="tablist">
            <li class="active"><a href="#dept-tab" data-toggle="tab"
                onclick="$('#supertype').val('dept');">By Department</a></li>
            <li><a href="#manu-tab" data-toggle="tab"
                onclick="$('#supertype').val('manu');">By Brand</a></li>
            <li><a href="#vendor-tab" data-toggle="tab"
                onclick="$('#supertype').val('vendor');">By Vendor</a></li>
        </ul>
        <input id="supertype" name="supertype" type="hidden" value="dept" />
        <div class="tab-content">
            <p>
            <div class="tab-pane active" id="dept-tab">
                <div class="row form-horizontal">
                    <div class="col-sm-8">
                    <?php echo FormLib::standardDepartmentFields('deptSub'); ?>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="manu-tab">
                <div class="form-group form-inline">
                    <label><?php echo _('Brand'); ?></label>
                    <input type=text name=manufacturer class="form-control" />
                </div>
                <div class="form-group form-inline">
                    <label><input type=radio name=mtype value=prefix checked />
                        UPC prefix</label>
                    <label><input type=radio name=mtype value=name />
                        <?php echo _('Brand name'); ?></label>
                </div>
            </div>
            <div class="tab-pane" id="vendor-tab">
                <div class="form-group form-inline">
                    <label>Vendor</label>
                    <select name="vendor" class="form-control">
                    <?php
                    $vendors = new VendorsModel($dbc);
                    foreach ($vendors->find('vendorName') as $v) {
                        printf('<option value="%d">%s</option>',
                            $v->vendorID(), $v->vendorName());
                    }
                    ?>
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
        <p> 
            <button type=submit name=submit class="btn btn-default btn-core">Submit</button>
            <button type=reset id="reset-btn" class="btn btn-default btn-reset"
                onclick="$('#super-id').val('').trigger('change');">Start Over</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    function body_content()
    {
        if ($this->mode == 'form')
            return $this->form_content();
        else if ($this->mode == 'list')
            return $this->list_content();
        else
            return 'Unknown error occurred';
    }

    function css_content()
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
}

FannieDispatch::conditionalExec();

