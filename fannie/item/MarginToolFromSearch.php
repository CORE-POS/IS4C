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

class MarginToolFromSearch extends FannieRESTfulPage
{
    protected $header = 'Margin Search Results';
    protected $title = 'Margin Search Results';

    public $description = '[Margin Preview] takes a set of advanced search results and shows the effect on
    margin of various price changes. Must be accessed via Advanced Search.';

    protected $window_dressing = false;

    protected $upcs = array();
    private $save_results = array();
    private $depts = array();
    private $superdepts = array();

    public function readinessCheck()
    {
        global $FANNIE_ARCHIVE_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
        if (!$dbc->tableExists('productSummaryLastQuarter')) {
            $this->error_text = _("You are missing an important table") . " ($FANNIE_ARCHIVE_DB.productSummaryLastQuarter). ";
            $this->error_text .= " Visit the <a href=\"{$FANNIE_URL}install\">Install Page</a> to create it.";
            return false;
        } else {
            $testQ = 'SELECT upc FROM productSummaryLastQuarter';
            $testQ = $dbc->addSelectLimit($testQ, 1);
            $testR = $dbc->query($testQ);
            if ($dbc->num_rows($testR) == 0) {
                $this->error_text = _('The product sales summary is missing. Run the Summarize Product Sales task.');
                return false;
            }
        }

        return true;
    }

    function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       $this->__routes[] = 'get<upc><deptID><newprice>';
       $this->__routes[] = 'get<upc><superID><newprice>';
       $this->__routes[] = 'post<upcs><deptID><newprices>';
       $this->__routes[] = 'post<upcs><superID><newprices>';
       $this->__routes[] = 'post<newbatch><tags><upcs><newprices>';
       return parent::preprocess();
    }

    // ajax callback: recalculate department margin
    // using new price for the given upc
    function get_upc_deptID_newprice_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $query = "SELECT SUM(p.cost) AS totalCost,
                    SUM(CASE WHEN upc=? THEN ? ELSE normal_price END) as totalPrice
                  FROM products AS p
                  WHERE department=?
                    AND cost <> 0";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($this->upc, $this->newprice, $this->deptID));
        if ($dbc->num_rows($result) == 0) {
            echo "0";
        } else {
            $row = $dbc->fetch_row($result);
            echo sprintf('%.4f', ($row['totalPrice'] - $row['totalCost']) / $row['totalPrice'] * 100);
        }

        return false;
    }

    // ajax calllback: recalculate department margin
    // this plural version takes JSON arrays of UPCs and 
    // new prices. Recalculating the all applicable items 
    // seems easier then keeping track of every edit
    function post_upcs_deptID_newprices_handler()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $this->upcs = json_decode($this->upcs);
        $this->newprices = json_decode($this->newprices);
        // validate input arrays
        if (!is_array($this->upcs) || !is_array($this->newprices) || count($this->upcs) != count($this->newprices) || count($this->upcs) == 0) {
            echo '0';
            return false;
        }

        $priceCalc = "SUM(CASE";
        $args = array();
        for($i=0; $i<count($this->upcs); $i++) {
            $priceCalc .= ' WHEN upc=? THEN ? ';
            $args[] = $this->upcs[$i];
            $args[] = $this->newprices[$i];
        }
        $priceCalc .= ' ELSE normal_price END)';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $summary = $FANNIE_ARCHIVE_DB . $dbc->sep() . 'productSummaryLastQuarter';
        // calculate margin using newprice for the specific upc,
        // normal_price for the other upcs, and avoid divided by
        // zero errors in both cases
        $marginSQL = 'CASE ';
        $m_args = array();
        for($i=0; $i<count($this->upcs); $i++) {
            $marginSQL .= ' WHEN p.upc=? THEN
                CASE WHEN ?=0 THEN 0 ELSE (?-cost)/? END ';
            $m_args[] = $this->upcs[$i]; 
            // not a typo; price is used three times
            $m_args[] = $this->newprices[$i]; 
            $m_args[] = $this->newprices[$i]; 
            $m_args[] = $this->newprices[$i]; 
        }
        $marginSQL .= ' ELSE
                            CASE WHEN normal_price=0 THEN 0 ELSE (normal_price-cost)/normal_price END
                        END';
        $query = "SELECT SUM(q.percentageDeptSales * ($marginSQL)) 
                    / SUM(q.percentageDeptSales) as weightedMargin
                  FROM products AS p
                  LEFT JOIN $summary AS q ON p.upc=q.upc
                  WHERE department=?
                    AND cost <> 0";
        $m_args[] = $this->deptID;
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $m_args);
        if ($dbc->num_rows($result) == 0) {
            echo "0";
        } else {
            $row = $dbc->fetch_row($result);
            //echo sprintf('%.4f', ($row['totalPrice'] - $row['totalCost']) / $row['totalPrice'] * 100);
            echo sprintf('%.4f', $row['weightedMargin'] * 100);
        }

        return false;
    }

    // ajax callback: recalculate superdept margin
    // using new price for the given upc
    function get_upc_superID_newprice_handler()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $summary = $FANNIE_ARCHIVE_DB . $dbc->sep() . 'productSummaryLastQuarter';
        // calculate margin using newprice for the specific upc,
        // normal_price for the other upcs, and avoid divided by
        // zero errors in both cases
        $marginSQL = 'CASE WHEN p.upc=? 
                        THEN
                            CASE WHEN ?=0 THEN 0 ELSE (?-cost)/? END
                        ELSE
                            CASE WHEN normal_price=0 THEN 0 ELSE (normal_price-cost)/normal_price END
                        END';
        $query = "SELECT SUM(p.cost) AS totalCost,
                    SUM(CASE WHEN p.upc=? THEN ? ELSE normal_price END) as totalPrice,
                    SUM(q.percentageSuperDeptSales * ($marginSQL)) 
                       / SUM(q.percentageSuperDeptSales) as weightedMargin
                  FROM products AS p
                  INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                  LEFT JOIN $summary AS q ON p.upc=q.upc
                  WHERE m.superID=?
                    AND cost <> 0";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($this->upc, $this->newprice, $this->upc, $this->newprice, $this->newprice, $this->newprice, $this->superID));
        if ($dbc->num_rows($result) == 0) {
            echo "0";
        } else {
            $row = $dbc->fetch_row($result);
            echo sprintf('%.4f', $row['weightedMargin'] * 100);
        }

        return false;
    }

    // ajax calllback: recalculate superdept margin
    // this plural version takes JSON arrays of UPCs and 
    // new prices. Recalculating the all applicable items 
    // seems easier then keeping track of every edit
    function post_upcs_superID_newprices_handler()
    {
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $this->upcs = json_decode($this->upcs);
        $this->newprices = json_decode($this->newprices);
        // validate input arrays
        if (!is_array($this->upcs) || !is_array($this->newprices) || count($this->upcs) != count($this->newprices) || count($this->upcs) == 0) {
            echo '0';
            return false;
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $summary = $FANNIE_ARCHIVE_DB . $dbc->sep() . 'productSummaryLastQuarter';
        // calculate margin using newprice for the specific upc,
        // normal_price for the other upcs, and avoid divided by
        // zero errors in both cases
        $marginSQL = 'CASE ';
        $m_args = array();
        for($i=0; $i<count($this->upcs); $i++) {
            $marginSQL .= ' WHEN p.upc=? THEN
                CASE WHEN ?=0 THEN 0 ELSE (?-cost)/? END ';
            $m_args[] = $this->upcs[$i]; 
            // not a typo; price is used three times
            $m_args[] = $this->newprices[$i]; 
            $m_args[] = $this->newprices[$i]; 
            $m_args[] = $this->newprices[$i]; 
        }
        $marginSQL .= ' ELSE 
                            CASE WHEN normal_price=0 THEN 0 ELSE (normal_price-cost)/normal_price END
                        END';
        $query = "SELECT SUM(q.percentageSuperDeptSales * ($marginSQL)) 
                   / SUM(q.percentageSuperDeptSales) as weightedMargin
                  FROM products AS p
                  LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                  LEFT JOIN $summary AS q ON p.upc=q.upc
                  WHERE m.superID=?
                    AND cost <> 0";
        $m_args[] = $this->superID;
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $m_args);
        if ($dbc->num_rows($result) == 0) {
            echo "0";
        } else {
            $row = $dbc->fetch_row($result);
            echo sprintf('%.4f', $row['weightedMargin'] * 100);
        }

        return false;
    }

    // ajax callback to create a new pricechange batch
    function post_newbatch_tags_upcs_newprices_handler()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $this->upcs = json_decode($this->upcs);
        $this->newprices = json_decode($this->newprices);
        $dbc = FannieDB::get($FANNIE_OP_DB);

        // try to lookup batch owner
        // failure is ok
        $owner = '';
        $ownerP = $dbc->prepare('SELECT super_name FROM superDeptNames WHERE superID=?');
        $ownerR = $dbc->execute($ownerP, array($this->tags));
        if ($dbc->num_rows($ownerR) > 0) {
            $ownerW = $dbc->fetch_row($ownerR);
            $owner = $ownerW['super_name'];
        }

        // try to lookup correct discount type
        // failure is ok
        $btype = 0;
        $btypeR = $dbc->query('SELECT batchTypeID FROM batchType WHERE discType=0 ORDER BY batchTypeID');
        if ($dbc->num_rows($btypeR) > 0) {
            $btypeW = $dbc->fetch_row($btypeR);
            $btype = $btypeW['batchTypeID'];
        }

        // create batch. date 'yesterday' ensures it doesn't
        // run automatically unless the user re-schedules it
        $b = new BatchesModel($dbc);
        $b->batchName($this->newbatch);
        $b->startDate(date('Y-m-d', strtotime('yesterday')));
        $b->endDate(date('Y-m-d', strtotime('yesterday')));
        $b->batchType($btype);
        $b->discounttype(0);
        $b->priority(0);
        $b->owner($owner);
        $id = $b->save();

        // maintain @deprecated table if present
        if ($dbc->tableExists('batchowner')) {
            $insQ = $dbc->prepare_statement("insert batchowner values (?,?)");
            $insR = $dbc->exec_statement($insQ,array($id,$owner));
        }

        // add items to batch
        for($i=0; $i<count($this->upcs); $i++) {
            $upc = $this->upcs[$i];
            if (!isset($this->newprices[$i])) {
                continue; // should not happen
            }
            $price = $this->newprices[$i];
            $bl = new BatchListModel($dbc);
            $bl->upc(BarcodeLib::padUPC($upc));
            $bl->batchID($id);
            $bl->salePrice($price);
            $bl->active(0);
            $bl->pricemethod(0);
            $bl->quantity(0);
            $bl->save();
        }

        // did not select "none" for tags
        // so create some shelftags
        if ($this->tags != -1) {
            $lookup = $dbc->prepare('SELECT p.description, v.brand, v.sku, v.size, v.units, n.vendorName
                                FROM products AS p LEFT JOIN vendorItems AS v ON p.upc=v.upc
                                LEFT JOIN vendors AS n ON v.vendorID=n.vendorID
                                WHERE p.upc=? ORDER BY v.vendorID');
            $tag = new ShelfTagModel($dbc);
            for($i=0; $i<count($this->upcs);$i++) {
                $upc = $this->upcs[$i];
                if (!isset($this->newprices[$i])) {
                    continue; // should not happen
                }
                $price = $this->newprices[$i];
                $info = array('description'=>'', 'brand'=>'', 'sku'=>'', 'size'=>'', 'units'=>1,
                            'vendorName'=>'');
                $lookupR = $dbc->execute($lookup, array($upc));
                if ($dbc->num_rows($lookupR) > 0) {
                    $info = $dbc->fetch_row($lookupR);
                }
                $ppo = ($info['size'] !== '') ? PriceLib::pricePerUnit($price, $info['size']) : '';

                $tag->id($this->tags);
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

        echo $FANNIE_URL . 'newbatch/BatchManagementTool.php?startAt=' . $id;

        return false;
    }

    function post_u_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (!is_array($this->u)) {
            $this->u = array($this->u);
        }
        foreach($this->u as $postdata) {
            if (is_numeric($postdata)) {
                $this->upcs[] = BarcodeLib::padUPC($postdata);
            }
        }

        // get applicable department(s)
        $info = $this->arrayToParams($this->upcs);
        $deptQ = "SELECT department
                  FROM products 
                  WHERE upc in ({$info['in']})
                  GROUP BY department";
        $deptP = $dbc->prepare($deptQ);
        $deptR = $dbc->execute($deptP, $info['args']);
        while($deptW = $dbc->fetch_row($deptR)) {
            $this->depts[] = $deptW['department'];
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
        global $FANNIE_OP_DB, $FANNIE_URL, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $this->add_script($FANNIE_URL.'/src/javascript/jquery.js');
        $this->add_script($FANNIE_URL.'src/javascript/tablesorter/jquery.tablesorter.js');
        $this->add_css_file($FANNIE_URL.'/src/style.css');
        $this->add_css_file($FANNIE_URL.'src/javascript/tablesorter/themes/blue/style.css');
        $ret = '';

        // list super depts & starting margins
        $info = $this->arrayToParams($this->depts);
        $superQ = "SELECT m.superID, super_name,
                    SUM(cost) AS totalCost,
                    SUM(CASE WHEN p.cost = 0 THEN 0 ELSE p.normal_price END) as totalPrice
                   FROM MasterSuperDepts AS m
                   LEFT JOIN products AS p ON m.dept_ID=p.department
                   WHERE m.dept_ID IN ({$info['in']})
                   GROUP BY m.superID, super_name";
        $superP = $dbc->prepare($superQ);
        $superR = $dbc->execute($superP, $info['args']);
        $ret .= '<div style="position: fixed; top: 1em; right: 1em;">';
        $ret .= '<fieldset><legend>Overall Margins</legend>';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $first_superID = 0;
        $batchName = 'priceChange '.date('Y-m-d');
        while($superW = $dbc->fetch_row($superR)) {
            $this->upc = 'n/a';
            $this->newprice = 0;
            $this->superID = $superW['superID'];
            // use ajax callback to calculate margin for full superdept
            ob_start();
            $this->get_upc_superID_newprice_handler();
            $margin = ob_get_clean();
            $ret .= sprintf('<tr><td>%d %s</td><td id="smargin%d">%.4f%%</td></tr>',
                            $superW['superID'], $superW['super_name'], $superW['superID'], $margin
            );
            if ($first_superID === 0) {
                $first_superID = $superW['superID'];
                $batchName = $superW['super_name'] . ' ' . $batchName;
            }
        }
        $ret .= '<tr><td colspan="2" style="height:8px;font-size:0;">&nbsp;</td></tr>';

        // list depts and starting margins
        $summary = $FANNIE_ARCHIVE_DB . $dbc->sep() . 'productSummaryLastQuarter';
        $marginQ = "SELECT department, dept_name,
                      SUM(q.percentageDeptSales * (CASE WHEN normal_price=0 THEN 0 ELSE (normal_price-cost)/normal_price END)) 
                       / SUM(q.percentageDeptSales) as weightedMargin
                    FROM products AS p
                    LEFT JOIN departments AS d ON p.department=d.dept_no
                    LEFT JOIN $summary AS q ON p.upc=q.upc
                    WHERE department IN ({$info['in']})
                        AND cost <> 0
                    GROUP BY department, dept_name";
        $marginP = $dbc->prepare($marginQ);
        $marginR = $dbc->execute($marginP, $info['args']);
        while($marginW = $dbc->fetch_row($marginR)) {
            $ret .= sprintf('<tr><td>%d %s</td><td id="dmargin%d">%.4f%%</td></tr>',
                            $marginW['department'], $marginW['dept_name'], $marginW['department'],
                            $marginW['weightedMargin']*100
            );
        }
        $ret .= '</table></fieldset>';
        $ret .= '<fieldset><legend>Create Batch</legend><table>'; 
        $ret .= '<tr><th>Name</th><td><input type="text" id="batchName" size="15" value="' . $batchName . '" /></td></tr>';
        $ret .= '<tr><th>Tags</th><td><select id="shelftagSet"><option value="-1">None</option>';
        $tagR = $dbc->query('SELECT superID, super_name FROM MasterSuperDepts GROUP BY superID, super_name ORDER BY superID');
        while($tagW = $dbc->fetch_row($tagR)) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                            $first_superID == $tagW['superID'] ? 'selected' : '',
                            $tagW['superID'], $tagW['super_name']);
        }
        $ret .= '</select></td></tr>';
        $ret .= '<tr><td colspan="2"><input type="submit" onclick="createBatch(); return false;" value="Create Batch" /></td></tr>';
        $ret .= '</table></fieldset></div>';

        // list the actual items
        $ret .= '<form onsubmit="return false;" method="post">';
        $ret .= '<table id="maintable" class="tablesorter" style="width:80%;"><thead>';
        $ret .= '<tr>
                <th>UPC</th>
                <th>Description</th>
                <th>% Store</th>
                <th>% Super</th>
                <th>% Dept</th>
                <th>Cost</th>
                <th>Current Price</th>
                <th>Margin</th>
                <th>New Price</th>
                </tr></thead><tbody>';

        $info = $this->arrayToParams($this->upcs);
        $query = 'SELECT p.upc, p.description, p.department, p.cost,
                    p.normal_price, m.superID, q.percentageStoreSales,
                    q.percentageSuperDeptSales, q.percentageDeptSales
                  FROM products AS p
                  LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                  LEFT JOIN ' . $FANNIE_ARCHIVE_DB . $dbc->sep() . 'productSummaryLastQuarter AS q
                    ON p.upc=q.upc
                  WHERE p.upc IN (' . $info['in'] . ')
                  ORDER BY p.upc';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $info['args']);
        while($row = $dbc->fetch_row($result)) {
            $ret .= sprintf('<tr class="itemrow" id="row%s">
                            <td>%s</td>
                            <td>%s</td>
                            <td>%.5f%%</td>
                            <td>%.4f%%</td>
                            <td>%.4f%%</td>
                            <td>%.2f</td>
                            <td class="currentprice">%.2f</td>
                            <td id="margin%s">%.4f%%</td>
                            <td class="dept%d super%d">
                                <input type="text" size="5" name="price[]" class="newprice"
                                value="%.2f" onchange="reCalc(\'%s\', this.value, %f, %d, %d);" />
                                <input type="hidden" name="upc[]" class="itemupc" value="%s" />
                            </td>
                            </tr>',
                            $row['upc'],
                            $row['upc'],
                            $row['description'],
                            $row['percentageStoreSales'] * 100,
                            $row['percentageSuperDeptSales'] * 100,
                            $row['percentageDeptSales'] * 100,
                            $row['cost'],
                            $row['normal_price'],
                            $row['upc'], (($row['normal_price'] - $row['cost']) / $row['normal_price']) * 100,
                            $row['department'], $row['superID'],
                            $row['normal_price'], $row['upc'], $row['cost'], $row['department'], $row['superID'], 
                            $row['upc']
            );
        }
        $ret .= '</tbody></table>';

        $ret .= '</form>';

        $this->add_onload_command("\$('#maintable').tablesorter({sortList: [[0,0]], widgets: ['zebra']});");

        return $ret;
    }

    function javascript_content()
    {
        ob_start();
        ?>
function createBatch() {
    var cArray = Array();
    var pArray = Array();
    var uArray = Array();
    $('.itemrow').each(function(){
        cArray.push($(this).find('.currentprice').html());
        pArray.push($(this).find('.newprice').val());
        uArray.push($(this).find('.itemupc').val());
    });

    var changeUPC = Array();
    var changePrice = Array();
    for(var i=0; i<pArray.length; i++) {
        if (pArray[i] != cArray[i]) {
            changePrice.push(pArray[i]);
            changeUPC.push(uArray[i]);
        }
    }

    if (changePrice.length == 0) {
        alert('No prices have been changed!');
        return;
    }

    var prices = JSON.stringify(changePrice);
    var upcs = JSON.stringify(changeUPC);
    var tags = $('#shelftagSet').val();
    var batchName = $('#batchName').val();
    var dstr = 'newbatch='+batchName+'&tags='+tags+'&upcs='+upcs+'&newprices='+prices;
    $.ajax({
        url: 'MarginToolFromSearch.php',
        type: 'post',
        data: dstr,
        success: function(resp) {
            location = resp;
        }
    });
}
function reCalc(upc, price, cost, deptID, superID) {
    var newprice = Number(price);
    if (cost == 0 || isNaN(newprice)) {
        return false;
    }

    var curprice = Number($('#row'+upc).find('.currentprice').html());
    if (curprice == newprice) {
        $('#row'+upc).css('font-weight', 'normal');
        $('#row'+upc+' td').each(function() {
            $(this).css('background-color', '');
        });
    } else {
        $('#row'+upc).css('font-weight', 'bold');
        $('#row'+upc+' td').each(function() {
            $(this).css('background-color', '#ffc');
        });
    }

    var itemMargin = (price - cost) / price * 100;
    itemMargin = Math.round(itemMargin * 10000) / 10000;
    $('#margin'+upc).html(itemMargin+"%");

    // get all prices for items in the department
    // currently being displayed (and editable)
    var pArray = Array();
    var uArray = Array();
    $('.dept'+deptID).each(function(){
        pArray.push($(this).find('.newprice').val());
        uArray.push($(this).find('.itemupc').val());
    });
    var prices = JSON.stringify(pArray);
    var upcs = JSON.stringify(uArray);

    $.ajax({
        url: 'MarginToolFromSearch.php',
        type: 'post',
        data: 'upcs='+upcs+'&deptID='+deptID+'&newprices='+prices,
        success: function(resp) {
            $('#dmargin'+deptID).html(resp+"%");
        }
    });

    // get all prices for items in the superdepartment
    // currently being displayed (and editable)
    var pArray = Array();
    var uArray = Array();
    $('.super'+superID).each(function(){
        pArray.push($(this).find('.newprice').val());
        uArray.push($(this).find('.itemupc').val());
    });
    var prices = JSON.stringify(pArray);
    var upcs = JSON.stringify(uArray);

    $.ajax({
        url: 'MarginToolFromSearch.php',
        type: 'post',
        data: 'upcs='+upcs+'&superID='+superID+'&newprices='+prices,
        success: function(resp) {
            $('#smargin'+superID).html(resp+"%");
        }
    });
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

