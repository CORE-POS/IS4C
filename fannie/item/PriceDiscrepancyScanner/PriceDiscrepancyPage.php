<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PriceDiscrepancyPage extends FannieRESTfulPage {

    protected $header = 'Price Discrepancies';
    protected $title = 'Price Discrepancies';

    public $description = '[Price Discrepancies] scan for and correct price
        discrepancies between 2 stores.';
    public $themed = true;

    public function get_view()
    {
        
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $item = array();

        $prep = $dbc->prepare("SELECT 
                upc, 
                p.normal_price - (SELECT normal_price FROM products WHERE store_id=2 AND upc=p.upc) AS discrepancy,
                description,
                normal_price,
                size,
                brand
            FROM products AS p 
            WHERE store_id=1 
                AND inUse=1 
                AND (p.normal_price - (SELECT normal_price FROM products WHERE store_id=2 AND upc=p.upc) ) != 0
                AND department NOT BETWEEN 508 AND 998
                AND department NOT BETWEEN 225 AND 234
                AND department NOT BETWEEN 61 AND 78
                AND department != 150
                AND department != 208
                AND department != 235
                AND department != 500
                AND (p.inUse - (SELECT inUse FROM products WHERE store_id=2 AND upc=p.upc) ) = 0
        ;");
        $result = $dbc->execute($prep);
        
        while ($row = $dbc->fetch_row($result)) {
            $item[$row['upc']]['isdiscrep'] = 1;
            $item[$row['upc']]['desc'] = $row['description'];
            $item[$row['upc']]['hprice'] = $row['normal_price'];
            $item[$row['upc']]['dprice'] = $row['normal_price'] - $row['discrepancy'];
            $item[$row['upc']]['size'] = $row['size'];
            $item[$row['upc']]['brand'] = $row['brand'];
        }

        $ret = "";
        $ret .= count($item) . ' items found with mis-matched prices.<br>';
        $ret .= '<table class="table">';
        $ret .= '
            <th>Brand</th>
            <th>Description</th>
            <th>Size</th>
            <th>Hillside<br><i>click to use<br>this price</i></th>
            <th>Denfeld<br><i>click to use<br>this price</i>d</th>
            <th>UPC</th>
            <th>Track Changes Made</th>
        ';
        foreach ($item as $key => $row) {
            $ret .= '<tr><td>' . $row['brand'] . '</td>';
            $ret .= '<td>' . $row['desc'] . '</td>';
            $ret .= '<td>' . $row['size'] . '</td>';
            
            //$ret .= '<td>' . $row['hprice'] . '</td>';
            //store_id is reversed - if you opt to use the price from store_id=1, you will be updating the price for store_id=2
            $thisPrice = $row['hprice'];
            $ret .= '<td><button class="btn btn-active" type="button" onclick="editPrice(this,\''. $key .'\',\''. $thisPrice . '\',\'2\'); return false; window.location.reload();">' . $row['hprice'] . '</button></td>';
            
            //$ret .= '<td>' . $row['dprice'] . '</td>';
            //$ret .= '<td><button class="btn btn-active" type="button" onclick="editPrice("{$key}",{$row["dprice"]} , 1); return false; window.location.reload();">' . $row['dprice'] . '</button></td>';
            $thisPrice = $row['dprice'];
            $ret .= '<td><button class="btn btn-active" type="button" onclick="editPrice(this,\''. $key .'\',\''. $thisPrice . '\',\'1\'); return false; window.location.reload();">' . $row['dprice'] . '</button></td>';
            
            $ret .= '<td><a href="../ItemEditorPage.php?searchupc=' . $key . '" target="_blank">' . $key . '</a></td>';
            $ret .= '<td><a href="http://key/scancoord/item/TrackChangeNew.php?upc=' . $key . '" target="_blank">See Changes</a></td>';
        }
        $ret .= '</table>';
        
        return $ret;
    }
    
    public function javascriptContent()
    {
        ob_start();
        ?>
function editPrice(button, upc, price, store_id)
{
    
    $.ajax({
        type: 'post',
        url: 'priceUpdate.php',
        dataType: 'json',
        data: 'upc='+upc+'&price='+price+'&store_id='+store_id,
        success: function(resp) {
            $(button).closest('tr').hide();
        }
    });
    
}
        <?php
        return ob_get_clean();
    }
    
    public function helpContent()
    {
        return '<p>
            No help content currently available.
            </p>';
    }

}

FannieDispatch::conditionalExec();

