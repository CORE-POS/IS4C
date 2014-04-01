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

include('../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class BatchFromSearch extends FannieRESTfulPage
{

    protected $header = 'Create Batch From Search Results';
    protected $title = 'Create Batch From Search Results';

    private $upcs = array();

    function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       $this->__routes[] = 'post<createBatch>';
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
          Lookup vendor info for each item then add a shelftags record
        */
        $tagset = FormLib::get('tagset');
        if ($discounttype == 0 && $tagset !== '') {
            $ins = $dbc->prepare('INSERT INTO shelftags (id, upc, description, normal_price, 
                            brand, sku, size, units, vendor, pricePerUnit) VALUES (?, ?, ?, ?,
                            ?, ?, ?, ?, ?, ?)');
            $lookup = $dbc->prepare('SELECT p.description, v.brand, v.sku, v.size, v.units, n.vendorName
                                FROM products AS p LEFT JOIN vendorItems AS v ON p.upc=v.upc
                                LEFT JOIN vendors AS n ON v.vendorID=n.vendorID
                                WHERE p.upc=? ORDER BY v.vendorID');
            $clear = $dbc->prepare('DELETE FROM shelftags WHERE id=? AND upc=?');
            for($i=0; $i<count($upcs);$i++) {
                $upc = $upcs[$i];
                $price = isset($prices[$i]) ? $prices[$i] : 0.00;
                $info = array('description'=>'', 'brand'=>'', 'sku'=>'', 'size'=>'', 'units'=>1,
                            'vendorName'=>'');
                $lookupR = $dbc->execute($lookup, array($upc));
                if ($dbc->num_rows($lookupR) > 0) {
                    $info = $dbc->fetch_row($lookupR);
                }
                $ppo = ($info['size'] !== '') ? PriceLib::pricePerUnit($price, $info['size']) : '';

                $dbc->execute($clear, array($tagset, $upc));
                $dbc->execute($ins, array($tagset, $upc, $info['description'], $price,
                                        $info['brand'], $info['sku'], $info['size'],
                                        $info['units'], $info['vendorName'], $ppo));
            }
        }

        header('Location: newbatch/BatchManagementTool.php?startAt=' . $id);
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
            echo 'Error: no valid data';
            return false;
        } else {
            return true;
        }
    }

    function post_u_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $ret = '<form action="BatchFromSearch.php" method="post">';
        $this->add_script($FANNIE_URL.'src/CalendarControl.js');

        $ret .= '<div style="line-height:2.3em;">';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $types = $dbc->query('SELECT batchTypeID, typeDesc, discType FROM batchType');
        $discTypes = array();
        $ret .= '<select name="batchType" id="batchType" onchange="discountTypeFixup()">';
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
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Name</b>: ';
        $ret .= '<input type="text" size="15" name="batchName" value="'
                . ($name ? $name : 'Batch') . ' '
                . date('M j')
                . '" />';

        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Start</b>: <input type="text" size="12" onfocus="showCalendarControl(this);" value="'
                . date('Y-m-d') . '" name="startDate" />';

        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>End</b>: <input type="text" size="12" onfocus="showCalendarControl(this);" value="'
                . date('Y-m-d') . '" name="endDate" />';

        $ret .= '<br />';
        $owners = $dbc->query('SELECT super_name FROM MasterSuperDepts GROUP BY super_name ORDER BY super_name');
        $ret .= '<b>Owner</b>: <select name="batchOwner" id="batchOwner"><option value=""></option>';
        while($row = $dbc->fetch_row($owners)) {
            $ret .= '<option>' . $row['super_name'] . '</option>';
        }
        $ret .= '<option>IT</option></select>';

        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" name="createBatch" value="Create Batch" />';

        $ret .= '</div>';

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

        $ret .= '<div id="saleTools">';
        $ret .= '<b>Markdown</b>: <input type="text" id="mdPercent" size="4" value="10" onchange="markDown(this.value);" />%
                <input type="submit" value="Go" onclick="markDown($(\'#mdPercent\').val()); return false" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= 'or: $<input type="text" id="mdDollar" size="4" value="0.00" onchange="discount(this.value);" />
                <input type="submit" value="Go" onclick="discount($(\'#mdDollar\').val()); return false" />';
        $ret .= '</div>';

        $ret .= '<div id="priceChangeTools">';
        $ret .= '<input type="submit" value="Use Vendor SRPs" onclick="useSRPs(); return false;" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Markup</b>: <input type="text" id="muPercent" size="4" value="10" onchange="markUp(this.value);" />%
                <input type="submit" value="Go" onclick="markUp($(\'#muPercent\').val()); return false" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Tags</b>: <select name="tagset" id="tagset"><option value="">No Tags</option>';
        $supers = $dbc->query('SELECT superID, super_name FROM MasterSuperDepts GROUP BY superID, super_name ORDER BY superID');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['superID'], $row['super_name']);
        }
        $ret .= '</select>';
        $ret .= '</div>';

        $ret .= '<br /><table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>UPC</th><th>Description</th><th>Retail</th>
                <th id="newPriceHeader">Sale Price</th></tr>';
        $superDetect = array();
        while($row = $dbc->fetch_row($result)) {
            $ret .= sprintf('<tr class="batchItem">
                            <td><input type="hidden" name="upc[]" value="%s" />%s</td>
                            <td>%s</td>
                            <td>$%.2f<input type="hidden" class="currentPrice" value="%.2f" /></td>
                            <td>
                                $<input type="text" size="5" name="price[]" class="itemPrice" value="0.00" />
                                <input type="hidden" class="itemSRP" value="%.2f" />
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
