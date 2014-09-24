<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class ProductListPage extends FannieReportTool 
{
    public $description = '[Product List] is a cross between a report and a tool. It lists current item prices and status flags for a department or set of departments but also allows editing.';

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

        if (FormLib::get_form_value('ajax') !== ''){
            $this->ajax_response();
            return False;
        }

        if (FormLib::get_form_value('supertype') !== ''){
            $this->mode = 'list';
            if ( isset($FANNIE_WINDOW_DRESSING) && $FANNIE_WINDOW_DRESSING == True )
                $this->has_menus(True);
            else
                $this->window_dressing = False;
            if (!$this->excel)
                $this->add_script($FANNIE_URL.'src/javascript/jquery.js');  
        }

        return True;
    }

    function javascript_content(){
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
        $p = $dbc->prepare_statement('SELECT originID,shortName FROM originName WHERE local=1 ORDER BY originID');
        $r = $dbc->exec_statement($p);
        while($w = $dbc->fetch_row($r)){
            $local_opts[substr($w['shortName'],0,1)] = array($w['originID'],$w['shortName']);
        }
        if (count($local_opts) == 1) $local_opts['X'] = array(1,'Yes'); // generic local if no origins defined
        ob_start();
        ?>
        var deptObj = <?php echo json_encode($depts); ?>;
        var taxObj = <?php echo json_encode($taxes); ?>;
        var localObj = <?php echo json_encode($local_opts); ?>;
        function edit(upc){
            var desc = $('tr#'+upc+' .td_desc').html();
            var content = "<input type=text class=in_desc value=\""+desc+"\" />";   
            $('tr#'+upc+' .td_desc').html(content);

            var dept = $('tr#'+upc+' .td_dept').html();
            var content = '<select class=in_dept style="width:8em;"><optgroup style="font-size: 90%;">';
            for(dept_no in deptObj){
                content += "<option value=\""+dept_no+"\" "+((dept==deptObj[dept_no])?'selected':'')+">";
                content += deptObj[dept_no]+"</option>";
            }
            content += '</optgroup></select>';
            $('tr#'+upc+' .td_dept').html(content);

            var supplier = $('tr#'+upc+' .td_supplier').html();
            var content = "<input type=text class=in_supplier size=10 value=\""+supplier+"\" />";   
            $('tr#'+upc+' .td_supplier').html(content);

            var cost = $('tr#'+upc+' .td_cost').html();
            var content = "<input type=text class=in_cost size=4 value=\""+cost+"\" />";    
            $('tr#'+upc+' .td_cost').html(content);

            var price = $('tr#'+upc+' .td_price').html();
            var content = "<input type=text class=in_price size=4 value=\""+price+"\" />";  
            $('tr#'+upc+' .td_price').html(content);

            var tax = $('tr#'+upc+' .td_tax').html();
            var content = '<select class=in_tax>';
            for (ch in taxObj){
                var sel = (tax == ch) ? 'selected' : '';
                content += "<option value=\""+ch+":"+taxObj[ch][0]+"\" "+sel+">";
                content += taxObj[ch][1]+"</option>";
            }
            $('tr#'+upc+' .td_tax').html(content);

            var fs = $('tr#'+upc+' .td_fs').html();
            var content = "<input type=checkbox class=in_fs "+((fs=='X')?'checked':'')+" />";
            $('tr#'+upc+' .td_fs').html(content);

            var disc = $('tr#'+upc+' .td_disc').html();
            var content = "<input type=checkbox class=in_disc "+((disc=='X')?'checked':'')+" />";
            $('tr#'+upc+' .td_disc').html(content);

            var wgt = $('tr#'+upc+' .td_wgt').html();
            var content = "<input type=checkbox class=in_wgt "+((wgt=='X')?'checked':'')+" />";
            $('tr#'+upc+' .td_wgt').html(content);

            var local = $('tr#'+upc+' .td_local').html();
            //var content = "<input type=checkbox class=in_local "+((local=='X')?'checked':'')+" />";
            var content = '<select class=in_local>';
            for (ch in localObj){
                var sel = (local == ch) ? 'selected' : '';
                content += "<option value=\""+ch+":"+localObj[ch][0]+"\" "+sel+">";
                content += localObj[ch][1]+"</option>";
            }
            $('tr#'+upc+' .td_local').html(content);

            var lnk = "<img src=\"<?php echo $FANNIE_URL;?>src/img/buttons/b_save.png\" alt=\"Save\" border=0 />";
            $('tr#'+upc+' .td_cmd').html("<a href=\"\" onclick=\"save('"+upc+"');return false;\">"+lnk+"</a>");

            $('tr#'+upc+' input:text').keydown(function(event) {
                if (event.which == 13) {
                    save(upc);
                }
            });
            $('tr#'+upc+' .clickable input:text').click(function(event){
                // do nothing
                event.stopPropagation();
            });
            $('tr#'+upc+' .clickable select').click(function(event){
                // do nothing
                event.stopPropagation();
            });
        }
        function save(upc){
            var desc = $('tr#'+upc+' .in_desc').val();
            $('tr#'+upc+' .td_desc').html(desc);
        
            var dept = $('tr#'+upc+' .in_dept').val();
            $('tr#'+upc+' .td_dept').html(deptObj[dept]);

            var supplier = $('tr#'+upc+' .in_supplier').val();
            $('tr#'+upc+' .td_supplier').html(supplier);

            var cost = $('tr#'+upc+' .in_cost').val();
            $('tr#'+upc+' .td_cost').html(cost);

            var price = $('tr#'+upc+' .in_price').val();
            $('tr#'+upc+' .td_price').html(price);

            var tax = $('tr#'+upc+' .in_tax').val().split(':');
            $('tr#'+upc+' .td_tax').html(tax[0]);
            
            var fs = $('tr#'+upc+' .in_fs').is(':checked') ? 1 : 0;
            $('tr#'+upc+' .td_fs').html((fs==1)?'X':'-');

            var disc = $('tr#'+upc+' .in_disc').is(':checked') ? 1 : 0;
            $('tr#'+upc+' .td_disc').html((disc==1)?'X':'-');

            var wgt = $('tr#'+upc+' .in_wgt').is(':checked') ? 1 : 0;
            $('tr#'+upc+' .td_wgt').html((wgt==1)?'X':'-');

            //var local = $('tr#'+upc+' .in_local').is(':checked') ? 1 : 0;
            //$('tr#'+upc+' .td_local').html((local==1)?'X':'-');
            var local = $('tr#'+upc+' .in_local').val().split(':');
            $('tr#'+upc+' .td_local').html(local[0]);

            var lnk = "<img src=\"<?php echo $FANNIE_URL;?>src/img/buttons/b_edit.png\" alt=\"Edit\" border=0 />";
            var cmd = "<a href=\"\" onclick=\"edit('"+upc+"'); return false;\">"+lnk+"</a>";
            $('tr#'+upc+' .td_cmd').html(cmd);

            var dstr = 'ajax=save&upc='+upc+'&desc='+desc+'&dept='+dept+'&price='+price+'&cost='+cost;
            dstr += '&tax='+tax[1]+'&fs='+fs+'&disc='+disc+'&wgt='+wgt+'&supplier='+supplier+'&local='+local[1];
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
                        if ($(this).find('input:text').length == 0) {
                            edit(upc);
                            $(this).find('input:text').select();
                        }
                    });
                }
            });
        });
        <?php
        }
        return ob_get_clean();
    }

    function ajax_response(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        switch(FormLib::get_form_value('ajax')){
        case 'save':
            $upc = FormLib::get_form_value('upc');
            $upc = BarcodeLib::padUPC($upc);
            $values = array();
            $model = new ProductsModel($dbc);
            $model->upc($upc);
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
                $extraP = $dbc->prepare_statement('UPDATE prodExtra SET distributor=? WHERE upc=?');
                $dbc->exec_statement($extraP, array($supplier,$upc));
            } else {
                $extraP = $dbc->prepare('INSERT INTO prodExtra
                                (upc, variable_pricing, margin, distributor)
                                VALUES
                                (?, 0, 0, ?)');

                $dbc->execute($extraP, array($upc, $supplier));
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

    function list_content(){
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $supertype = FormLib::get_form_value('supertype','dept');
        $manufacturer = FormLib::get_form_value('manufacturer','');
        $mtype = FormLib::get_form_value('mtype','prefix');
        $deptStart = FormLib::get_form_value('deptStart',0);
        $deptEnd = FormLib::get_form_value('deptEnd',0);
        $super = FormLib::get_form_value('deptSub',0);

        $sort = FormLib::get_form_value('sort','Department');   
        $order = 'dept_name';
        if ($sort === 'UPC') $order = 'i.upc';  
        elseif ($sort === 'Description') $order = 'i.description, i.upc';
        elseif ($sort === 'Vendor') $order = 'x.distributor, i.upc';
        elseif ($sort === 'Price') $order = 'i.normal_price, i.upc';
        elseif ($sort === 'Cost') $order = 'i.cost, i.upc';

        $ret = 'Report sorted by '.$sort.'<br />';
        if ($supertype == 'dept' && $super == 0){
            $ret .= 'Department '.$deptStart.' to '.$deptEnd.'<br />';
        }
        else if ($supertype == 'dept'){
            $ret .= 'Sub department '.$super.'<br />';
        }
        else {
            $ret .= _('Manufacturer') . ' ' . $manufacturer . '<br />';
        }
        $ret .= date("F j, Y, g:i a").'<br />'; 
        
        $page_url = sprintf('ProductListPage.php?supertype=%s&deptStart=%s&deptEnd=%s&deptSub=%s&manufacturer=%s&mtype=%s',
                $supertype, $deptStart, $deptEnd, $super, $manufacturer, $mtype);
        if (!$this->excel){
            $ret .= sprintf('<a href="%s&sort=%s&excel=yes">Save to Excel</a> &nbsp; &nbsp; <a href="javascript:history:back();">Back</a><br />',
                $page_url, $sort);
        }

        $query = "SELECT i.upc,i.description,d.dept_name as department,
            i.normal_price,                      
            (CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
                (CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
                        (CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
                        (CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
            (CASE WHEN i.local > 0 AND o.originID IS NULL THEN 'X' 
                  WHEN i.local > 0 AND o.originID IS NOT NULL THEN LEFT(o.shortName,1) ELSE '-' END) as local,
            x.distributor, i.cost
                        FROM products as i LEFT JOIN departments as d ON i.department = d.dept_no
            LEFT JOIN taxrates AS t ON t.id = i.tax
            LEFT JOIN prodExtra as x on i.upc = x.upc
            LEFT JOIN originName AS o ON i.local=o.originID
                        WHERE i.department BETWEEN ? AND ? 
            ORDER BY ".$order;
        $args = array($deptStart, $deptEnd);
        if ($supertype == 'dept' && $super != 0){
            $query = "SELECT i.upc,i.description,d.dept_name as department,
                i.normal_price,                      
                (CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
                (CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
                (CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
                (CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
                (CASE WHEN i.local > 0 AND o.originID IS NULL THEN 'X' 
                      WHEN i.local > 0 AND o.originID IS NOT NULL THEN LEFT(o.shortName,1) ELSE '-' END) as local,
                x.distributor, i.cost
                FROM products as i LEFT JOIN superdepts as s ON i.department = s.dept_ID
                LEFT JOIN taxrates AS t ON t.id = i.tax
                LEFT JOIN departments as d on i.department = d.dept_no
                LEFT JOIN prodExtra as x on i.upc = x.upc
                LEFT JOIN originName AS o ON i.local=o.originID
                WHERE s.superID = ?
                ORDER BY ".$order;
            $args = array($super);
        }
        else if ($supertype == 'manu'){
            $query = "SELECT i.upc,i.description,d.dept_name as department,
                i.normal_price,                      
                (CASE WHEN i.tax = 1 THEN 'X' WHEN i.tax=0 THEN '-' ELSE LEFT(t.description,1) END) as Tax,              
                (CASE WHEN i.foodstamp = 1 THEN 'X' ELSE '-' END) as FS,
                (CASE WHEN i.discount = 0 THEN '-' ELSE 'X'END) as DISC,
                (CASE WHEN i.scale = 1 THEN 'X' ELSE '-' END) as WGHd,
                (CASE WHEN i.local > 0 AND o.originID IS NULL THEN 'X' 
                      WHEN i.local > 0 AND o.originID IS NOT NULL THEN LEFT(o.shortName,1) ELSE '-' END) as local,
                x.distributor, i.cost
                FROM products as i LEFT JOIN departments as d ON i.department = d.dept_no
                LEFT JOIN prodExtra as x on i.upc = x.upc
                LEFT JOIN originName AS o ON i.local=o.originID
                LEFT JOIN taxrates AS t ON t.id = i.tax";
            if ($mtype == 'prefix'){
                $query .= ' WHERE i.upc LIKE ? ';
            }
            else {
                $query .= ' WHERE x.manufacturer LIKE ? ';
            }
            $args = array('%'.$manufacturer.'%');
            $query .= "ORDER BY ".$order; 
        }
        if ($order != "i.upc")
            $query .= ",i.upc";

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep, $args);

        if ($result === False || $dbc->num_rows($result) == 0){
            return 'No data found!';
        }

        $ret .= "<table border=1 cellspacing=0 cellpadding =3><tr>\n"; 
        if (!$this->excel){
            $ret .= sprintf('<tr><th><a href="%s&sort=UPC">UPC</a></th>
                    <th><a href="%s&sort=Description">Description</a></th>
                    <th><a href="%s&sort=Department">Department</a></th>
                    <th><a href="%s&sort=Vendor">' . _('Supplier') . '</a></th>
                    <th style="width:4em;"><a href="%s&sort=Cost">Cost</a></th>
                    <th style="width:4em;"><a href="%s&sort=Price">Price</a></th>',
                    $page_url,$page_url,$page_url,$page_url,$page_url,$page_url);
        }
        else
            $ret .= "<th>UPC</th><th>Description</th><th>Dept</th><th>" . _('Supplier') . "</th><th>Cost</th><th>Price</th>";
        $ret .= "<th style=\"width:5em;\">Tax</th><th>FS</th><th>Disc</th><th>Wg'd</th><th style=\"width:5em;\">Local</th>";
        if (!$this->excel && $this->canEditItems !== False)
            $ret .= '<th>&nbsp;</th>';
        $ret .= "</tr>";

        while($row = $dbc->fetch_row($result)) {
            $ret .= '<tr id="'.$row[0].'">';
            $enc = base64_encode($row[1]);
            if (!$this->excel){
                $ret .= "<td align=center class=\"td_upc\"><a href=ItemEditorPage.php?searchupc=$row[0]>$row[0]</a>"; 
                if ($this->canDeleteItems !== False){
                    $ret .= "<a href=\"\" onclick=\"deleteCheck('$row[0]','$enc'); return false;\">";
                    $ret .= "<img src=\"{$FANNIE_URL}src/img/buttons/trash.png\" border=0 /></a>";
                }
                $ret .= '</td>';
                $ret .= '<input type="hidden" class="hidden_upc" value="'.$row[0].'" />';
            }
            else
                $ret .= "<td align=center>$row[0]</td>";
            $ret .= "<td align=center class=\"td_desc clickable\">{$row['description']}</td>";
            $ret .= "<td align=center class=\"td_dept clickable\">{$row['department']}</td>";
            $ret .= "<td align=center class=\"td_supplier clickable\">{$row['distributor']}</td>";
            $ret .= "<td align=center class=\"td_cost clickable\">".sprintf('%.2f',$row['cost'])."</td>";
            $ret .= "<td align=center class=\"td_price clickable\">{$row['normal_price']}</td>";
            $ret .= "<td align=center class=td_tax>$row[4]</td>";
            $ret .= "<td align=center class=td_fs>$row[5]</td>";
            $ret .= "<td align=center class=td_disc>$row[6]</td>";
            $ret .= "<td align=center class=td_wgt>$row[7]</td>";
            $ret .= "<td align=center class=td_local>$row[8]</td>";
            if (!$this->excel && $this->canEditItems !== False){
                $ret .= "<td align=center class=td_cmd><a href=\"\" 
                    onclick=\"edit('$row[0]'); return false;\">
                    <img src=\"{$FANNIE_URL}src/img/buttons/b_edit.png\" alt=\"Edit\" 
                    border=0 /></a></td>";
            }
            $ret .= "</tr>\n";
        }
        $ret .= '</table>';

        if ($this->excel){
            header('Content-Type: application/ms-excel');
            header('Content-Disposition: attachment; filename="itemList.csv"');
            $array = HtmlToArray($ret);
            $ret = ArrayToCsv($array);
        }

        return $ret;
    }

    function form_content(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $deptQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
        $deptR = $dbc->exec_statement($deptQ);
        $depts = array();
        while ($deptW = $dbc->fetch_array($deptR)){
            $depts[$deptW['dept_no']] = $deptW['dept_name'];
        }
        $superQ = $dbc->prepare_statement("SELECT superID,super_name FROM superDeptNames WHERE 
            superID > 0 ORDER BY superID");
        $superR = $dbc->exec_statement($superQ);
        $supers = array();
        while ($superW = $dbc->fetch_row($superR)){
            $supers[$superW['superID']] = $superW['super_name'];
        }
        ob_start();
        ?>
        <div id=textwlogo> 
        <form method = "get" action="ProductListPage.php">
        <b>Report by</b>:
        <input type=radio name=supertype value=dept checked  id="supertypeD"
            onclick="$('#dept1').show();$('#dept2').show();$('#manu').hide();" /> 
            <label for="supertypeD">Department</label>
        <input type=radio name=supertype value=manu id="supertypeM"
            onclick="$('#dept1').hide();$('#dept2').hide();$('#manu').show();" /> 
            <label for="supertypeM"><?php echo _('Manufacturer'); ?></label>
        <table border="0" cellspacing="0" cellpadding="5">
        <tr class=dept id=dept1>
            <td valign=top><p><b>Buyer<br />(SuperDept)</b></p></td>
            <td><p><select name=deptSub>
            <option value=0></option>
            <?php
            foreach($supers as $id => $name)
                printf('<option value="%d">%s</option>',$id,$name); 
            ?>
            </select></p>
            <i>Selecting a Buyer/SuperDept overrides Department Start/Department End.
            <br />To run reports for a specific department(s) leave Buyer/SuperDept empty or set it to 'blank'</i></td>

        </tr>
        <tr class=dept id=dept2 valign=top> 
            <td > <p><b>Department Start</b></p>
            <p style='margin-top:1.5em;'>
            <b>Department End</b></p></td>
            <td> <p>
            <select id=deptStartSelect onchange="$('#deptStart').val(this.value);">
            <?php
            foreach($depts as $id => $name)
                printf('<option value="%d">%d %s</option>',$id,$id,$name);  
            ?>
            </select>
            <input type=text size= 5 id=deptStart name=deptStart value=1>
            </p>
            <p>
            <select id=deptEndSelect onchange="$('#deptEnd').val(this.value);">
            <?php
            foreach($depts as $id => $name)
                printf('<option value="%d">%d %s</option>',$id,$id,$name);  
            ?>
            </select>
            <input type=text size= 5 id=deptEnd name=deptEnd value=1>
            </p></td>
        </tr>
        <tr class=manu id=manu style="display:none;" valign="top">
            <td><p><b><?php echo _('Manufacturer'); ?></b></p>
            <td><p>
            <input type=text name=manufacturer />
            </p>
            <p>
            <input type=radio name=mtype value=prefix checked />UPC prefix
            <input type=radio name=mtype value=name /><?php echo _('Manufacturer name'); ?>
            </p></td>
        </tr>
        <tr> 
            <td><b>Sort report by?</b></td>
            <td> <select name="sort" size="1">
                <option>Department</option>
                <option>UPC</option>
                <option>Description</option>
            </select> 
            <input type=checkbox name=excel /> <b>Excel</b></td>
            <td>&nbsp;</td>
            <td>&nbsp; </td>
            </tr>
            <td>&nbsp;</td>
            <td>&nbsp; </td>
        </tr>
        <tr> 
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        </table>
        </form>
        </div>
        <?php
        return ob_get_clean();
    }

    function body_content(){
        if ($this->mode == 'form')
            return $this->form_content();
        else if ($this->mode == 'list')
            return $this->list_content();
        else
            return 'Unknown error occurred';
    }
}

FannieDispatch::conditionalExec(false);

