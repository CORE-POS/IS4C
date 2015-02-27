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

include(dirname(__FILE__). '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BatchFromSearch extends FannieRESTfulPage
{

    protected $header = 'Create Batch From Search Results';
    protected $title = 'Create Batch From Search Results';

    public $description = '[Batch From Search] takes a set of advanced search results and
    creates a sale or price change batch. Must be accessed via Advanced Search.';
    public $themed = true;

    private $upcs = array();

    function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       $this->__routes[] = 'post<createBatch>';
       $this->__routes[] = 'post<redoSRPs>';
       return parent::preprocess();
    }

    function post_createBatch_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $type = FormLib::get('batchType');
        $name = FormLib::get('batchName');
        $startdate = FormLib::get('startDate');
        $enddate = FormLib::get('endDate');
        $owner = FormLib::get('batchOwner');
        $priority = 0;

        $upcs = FormLib::get('upc', array());
        $prices = FormLib::get('price', array());

        $infoQ = $dbc->prepare_statement("select discType from batchType where batchTypeID=?");
        $infoR = $dbc->exec_statement($infoQ,array($type));
        if ($dbc->num_rows($infoR) == 0) {
            echo 'Invalid Batch Type ' . $type;
            return false;
        }
        $infoW = $dbc->fetch_array($infoR);
        $discounttype = $infoW['discType'];

        // make sure item data is present before creating batch
        if (!is_array($upcs) || !is_array($prices) || count($upcs) != count($prices) || count($upcs) == 0) {
            echo 'Invalid item data';
            return false;
        }

        $b = new BatchesModel($dbc);
        $b->startDate($startdate);
        $b->endDate($enddate);
        $b->batchName($name);
        $b->batchType($type);
        $b->discounttype($discounttype);
        $b->priority($priority);
        $b->owner($owner);
        $id = $b->save();

        if ($dbc->tableExists('batchowner')) {
            $insQ = $dbc->prepare_statement("insert batchowner values (?,?)");
            $insR = $dbc->exec_statement($insQ,array($id,$owner));
        }

        // add items to batch
        for($i=0; $i<count($upcs); $i++) {
            $upc = $upcs[$i];
            $price = isset($prices[$i]) ? $prices[$i] : 0.00;
            $bl = new BatchListModel($dbc);
            $bl->upc(BarcodeLib::padUPC($upc));
            $bl->batchID($id);
            $bl->salePrice($price);
            $bl->active(0);
            $bl->pricemethod(0);
            $bl->quantity(0);
            $bl->save();
        }

        /**
          If tags were requested and it's price change batch, make them
          Lookup vendor info for each item then add a shelftag record
        */
        $tagset = FormLib::get('tagset');
        if ($discounttype == 0 && $tagset !== '') {
            $vendorID = FormLib::get('preferredVendor', 0);
            $lookup = $dbc->prepare('
                SELECT p.description, 
                    v.brand, 
                    v.sku, 
                    v.size, 
                    v.units, 
                    n.vendorName
                FROM products AS p 
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc
                    LEFT JOIN vendors AS n ON v.vendorID=n.vendorID
                WHERE p.upc=? 
                ORDER BY CASE WHEN v.vendorID=? THEN -999 ELSE v.vendorID END'
            );
            $tag = new ShelftagsModel($dbc);
            for($i=0; $i<count($upcs);$i++) {
                $upc = $upcs[$i];
                $price = isset($prices[$i]) ? $prices[$i] : 0.00;
                $info = array('description'=>'', 'brand'=>'', 'sku'=>'', 'size'=>'', 'units'=>1,
                            'vendorName'=>'');
                $lookupR = $dbc->execute($lookup, array($upc, $vendorID));
                if ($dbc->num_rows($lookupR) > 0) {
                    $info = $dbc->fetch_row($lookupR);
                }
                $ppo = ($info['size'] !== '') ? \COREPOS\Fannie\API\lib\PriceLib::pricePerUnit($price, $info['size']) : '';

                $tag->id($tagset);
                $tag->upc($upc);
                $tag->description($info['description']);
                $tag->normal_price($price);
                $tag->brand($info['brand']);
                $tag->sku($info['sku']);
                $tag->size($info['size']);
                $tag->units($info['units']);
                $tag->vendor($info['vendorName']);
                $tag->pricePerUnit($ppo);
                $tag->save();
            }
        }

        header('Location: newbatch/BatchManagementTool.php?startAt=' . $id);
        return false;
    }

    function post_redoSRPs_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upcs = FormLib::get('upc', array());
        $vendorID = FormLib::get('preferredVendor', 0);

        for ($i=0; $i<count($upcs); $i++) {
            $upcs[$i] = BarcodeLib::padUPC($upcs[$i]);
        }
        $params = $this->arrayToParams($upcs);

        $query = '
            SELECT p.upc,
                CASE WHEN v.srp IS NULL THEN 0 ELSE v.srp END as newSRP
            FROM products AS p
                LEFT JOIN vendorSRPs AS v ON p.upc=v.upc
            WHERE p.upc IN (' . $params['in'] . ')
            ORDER BY p.upc,
                CASE WHEN v.vendorID=? THEN -999 ELSE v.vendorID END';
        $prep = $dbc->prepare($query);
        $params['args'][] = $vendorID;
        $result = $dbc->execute($prep, $params['args']);

        $prevUPC = 'notUPC';
        $results = array();
        while ($row = $dbc->fetch_row($result)) {
            if ($row['upc'] == $prevUPC) {
                continue;
            }
            $results[] = array(
                'upc' => $row['upc'],
                'srp' => $row['newSRP'],
            );
            $prevUPC = $row['upc'];
        }

        echo json_encode($results);

        return false;
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
            echo '<div class="alert alert-danger">Error: no valid data</div>';
            return false;
        } else {
            return true;
        }
    }

    function post_u_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $ret = '<form action="BatchFromSearch.php" method="post">';

        $ret .= '<div class="form-group form-inline">';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $types = $dbc->query('SELECT batchTypeID, typeDesc, discType FROM batchType');
        $discTypes = array();
        $ret .= '<select name="batchType" id="batchType" class="form-control"
            onchange="discountTypeFixup()">';
        while($row = $dbc->fetch_row($types)) {
            $ret .= sprintf('<option value="%d">%s</option>',
                            $row['batchTypeID'], $row['typeDesc']
            );
            $discTypes[] = $row;
        }
        $ret .= '</select>';
        foreach($discTypes as $row) {
            $ret .= sprintf('<input type="hidden" id="discType%d" value="%d" />',
                            $row['batchTypeID'], $row['discType']
            );
        }

        $name = FannieAuth::checkLogin();
        $ret .= '
                <label>Name</label>: ';
        $ret .= '<input type="text" class="form-control" name="batchName" value="'
                . ($name ? $name : 'Batch') . ' '
                . date('M j')
                . '" />';

        $ret .= '
                <label>Start</label>: <input type="text" class="form-control date-field" id="startDate" value="'
                . date('Y-m-d') . '" name="startDate" />
                ';

        $ret .= '
                <label>End</label>: <input type="text" class="form-control date-field" id="endDate" value="'
                . date('Y-m-d') . '" name="endDate" />
                </div>';

        $owners = $dbc->query('SELECT super_name FROM MasterSuperDepts GROUP BY super_name ORDER BY super_name');
        $ret .= '<div class="form-group form-inline">
            <label>Owner</label>: <select name="batchOwner" class="form-control" id="batchOwner"><option value=""></option>';
        while($row = $dbc->fetch_row($owners)) {
            $ret .= '<option>' . $row['super_name'] . '</option>';
        }
        $ret .= '<option>IT</option></select>
                <button type="submit" name="createBatch" value="1"
                    class="btn btn-default">Create Batch</button>
                </div>';

        $ret .= '<hr />';

        $info = $this->arrayToParams($this->upcs);
        $query = 'SELECT p.upc, p.description, p.normal_price, m.superID,
                MAX(CASE WHEN v.srp IS NULL THEN 0.00 ELSE v.srp END) as srp
                FROM products AS p
                LEFT JOIN vendorSRPs AS v ON p.upc=v.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                WHERE p.upc IN ( ' . $info['in'] . ')
                GROUP BY p.upc, p.description, p.normal_price, m.superID
                ORDER BY p.upc';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $info['args']);

        $ret .= '<div id="saleTools" class="form-group form-inline">';
        $ret .= '<label>Markdown</label>
                <div class="input-group">
                    <input type="text" id="mdPercent" class="form-control" value="10" onchange="markDown(this.value);" />
                    <span class="input-group-addon">%</span>
                </div>
                <button type="submit" class="btn btn-default" onclick="markDown($(\'#mdPercent\').val()); return false">Go</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<label>or</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" id="mdDollar" class="form-control" value="0.00" onchange="discount(this.value);" />
                </div>
                <button type="submit" class="btn btn-default" onclick="discount($(\'#mdDollar\').val()); return false">Go</button>';
        $ret .= '</div>';

        $ret .= '<div id="priceChangeTools" class="form-group form-inline">';
        $ret .= '<button type="submit" class="btn btn-default" onclick="useSRPs(); return false;">Use Vendor SRPs</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<select name="preferredVendor" class="form-control" onchange="reCalcSRPs();">
            <option value="0">Auto Choose Vendor</option>';
        $vendors = new VendorsModel($dbc);
        foreach ($vendors->find('vendorName') as $vendor) {
            $ret .= sprintf('<option value="%d">%s</option>',
                        $vendor->vendorID(), $vendor->vendorName());
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<label>Markup</label>
                <div class="input-group">
                    <input type="text" id="muPercent" class="form-control" value="10" onchange="markUp(this.value);" />
                    <span class="input-group-addon">%</span>
                </div>
                <button type="submit" class="btn btn-default" onclick="markUp($(\'#muPercent\').val()); return false">Go</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<label>Tags</label> <select name="tagset" class="form-control" id="tagset"><option value="">No Tags</option>';
        $supers = $dbc->query('SELECT superID, super_name FROM MasterSuperDepts GROUP BY superID, super_name ORDER BY superID');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['superID'], $row['super_name']);
        }
        $ret .= '</select>';
        $ret .= '</div>';

        $ret .= '<table class="table">';
        $ret .= '<tr><th>UPC</th><th>Description</th><th>Retail</th>
                <th id="newPriceHeader">Sale Price</th></tr>';
        $superDetect = array();
        while($row = $dbc->fetch_row($result)) {
            $ret .= sprintf('<tr class="batchItem">
                            <td><input type="hidden" name="upc[]" class="itemUPC" value="%s" />%s</td>
                            <td>%s</td>
                            <td>$%.2f<input type="hidden" class="currentPrice" value="%.2f" /></td>
                            <td><div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="text" name="price[]" class="itemPrice form-control" value="0.00" />
                                <input type="hidden" class="itemSRP" value="%.2f" />
                            </div>
                            </td>
                            </tr>',
                            $row['upc'], $row['upc'],
                            $row['description'],
                            $row['normal_price'], $row['normal_price'],
                            $row['srp']
            );

            if (!isset($superDetect[$row['superID']])) {
                $superDetect[$row['superID']] = 0;
            }
            $superDetect[$row['superID']]++;
        }
        $ret .= '</table>';

        $ret .= '</form>';

        // auto-detect likely owner & tag set by super department
        $tagPage = array_search(max($superDetect), $superDetect);
        if ($tagPage !== false) {
            $this->add_onload_command("\$('#tagset').val($tagPage);\n");
            $this->add_onload_command("\$('#batchOwner').val(\$('#tagset option:selected').text());\n");
        }
        // show sale or price change tools as appropriate
        $this->add_onload_command('discountTypeFixup();');
        // don't let enter key on these fields trigger form submission 
        $this->add_onload_command("\$('#mdPercent').bind('keypress', noEnter);\n");
        $this->add_onload_command("\$('#mdDollar').bind('keypress', noEnter);\n");
        $this->add_onload_command("\$('#muPercent').bind('keypress', noEnter);\n");

        return $ret;
    }

    function javascript_content()
    {
        ob_start();
        ?>
function discountTypeFixup() {
    var bt_id = $('#batchType').val();
    var dt_id = $('#discType'+bt_id).val();
    if (dt_id == 0) {
        $('#newPriceHeader').html('New Price');
        $('#saleTools').hide();
        $('#priceChangeTools').show();
    } else {
        $('#newPriceHeader').html('Sale Price');
        $('#saleTools').show();
        $('#priceChangeTools').hide();
    }
}
function useSRPs() {
    $('tr.batchItem').each(function(){
        var srp = $(this).find('.itemSRP').val();
        $(this).find('.itemPrice').val(fixupPrice(srp));
    });
}
function reCalcSRPs() {
    var info = $('form').serialize(); 
    info += '&redoSRPs=1';
    $.ajax({
        type: 'post',
        dataType: 'json',
        data: info,
        success: function(resp) {
            for (var i=0; i<resp.length; i++) {
                var item = resp[i];
                $('tr.batchItem').each(function(){
                    var upc = $(this).find('.itemUPC').val(); 
                    if (upc == item.upc) {
                        $(this).find('.itemSRP').val(item.srp);

                        return false;
                    }
                });
            }
        }
    });
}
function discount(amt) {
    $('tr.batchItem').each(function(){
        var price = $(this).find('.currentPrice').val();
        price = price - amt;
        $(this).find('.itemPrice').val(fixupPrice(price));
    });
}
function markDown(amt) {
    if (Math.abs(amt) >= 1) amt = amt / 100;
    $('tr.batchItem').each(function(){
        var price = $(this).find('.currentPrice').val();
        price = price * (1 - amt);
        $(this).find('.itemPrice').val(fixupPrice(price));
    });
}
function markUp(amt) {
    markDown(-1 * amt);
}
function fixupPrice(val) {
    var bt_id = $('#batchType').val();
    var dt_id = $('#discType'+bt_id).val();
    val = Math.round(val*100);
    if (dt_id == 0) {
        while(lastDigit(val) != 5 && lastDigit(val) != 9)
            val++;
    } else {
        while(lastDigit(val) != 9)
            val++;
    }
    return val / 100;
}
function lastDigit(val) {
    return val - (10 * Math.floor(val/10));
}
function noEnter(e) {
    if (e.keyCode == 13) {
        $(this).trigger('change');
        return false;
    }
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
