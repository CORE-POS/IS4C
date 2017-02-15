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

use COREPOS\Fannie\API\lib\Store;

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
    public $themed = true;

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
            echo sprintf('%.4f', $row['totalPrice']==0 ? 0 : ($row['totalPrice'] - $row['totalCost']) / $row['totalPrice'] * 100);
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
        $store = Store::getIdByIp();

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
                      LEFT JOIN $summary AS q ON p.upc=q.upc AND p.store_id=q.storeID
                  WHERE department=?
                    AND p.store_id=?
                    AND cost <> 0";
        $m_args[] = $this->deptID;
        $m_args[] = $store;
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
                      LEFT JOIN $summary AS q ON p.upc=q.upc AND p.store_id=q.storeID
                  WHERE m.superID=?
                    AND p.store_id=?
                    AND cost <> 0";
        $store = Store::getIdByIp();
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($this->upc, $this->newprice, $this->upc, $this->newprice, $this->newprice, $this->newprice, $this->superID, $store));
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
                      LEFT JOIN $summary AS q ON p.upc=q.upc AND p.store_id=q.storeID
                  WHERE m.superID=?
                    AND p.store_id=?
                    AND cost <> 0";
        $m_args[] = $this->superID;
        $m_args[] = Store::getIdByIp();
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
        $b->discountType(0);
        $b->priority(0);
        $b->owner($owner);
        $id = $b->save();

        // maintain @deprecated table if present
        if ($dbc->tableExists('batchowner')) {
            $insQ = $dbc->prepare("insert batchowner values (?,?)");
            $insR = $dbc->execute($insQ,array($id,$owner));
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
            $tag = new ShelftagsModel($dbc);
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
                $ppo = ($info['size'] !== '') ? \COREPOS\Fannie\API\lib\PriceLib::pricePerUnit($price, $info['size']) : '';

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

        echo $FANNIE_URL . 'batches/newbatch/BatchManagementTool.php?startAt=' . $id;

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
        list($in_sql, $args) = $dbc->safeInClause($this->upcs);
        $deptQ = "SELECT department
                  FROM products 
                  WHERE upc in ({$in_sql})
                  GROUP BY department";
        $deptP = $dbc->prepare($deptQ);
        $deptR = $dbc->execute($deptP, $args);
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
        $this->add_script($FANNIE_URL.'src/javascript/tablesorter/jquery.tablesorter.js');
        $this->add_css_file($FANNIE_URL.'src/javascript/tablesorter/themes/blue/style.css');
        $ret = '';
        $store = Store::getIdByIp(1);

        // list super depts & starting margins
        list($in_sql, $args) = $dbc->safeInClause($this->depts);
        $superQ = "SELECT m.superID, super_name,
                    SUM(cost) AS totalCost,
                    SUM(CASE WHEN p.cost = 0 THEN 0 ELSE p.normal_price END) as totalPrice
                   FROM MasterSuperDepts AS m
                   LEFT JOIN products AS p ON m.dept_ID=p.department
                   WHERE m.dept_ID IN ({$in_sql})
                    AND p.store_id=?
                   GROUP BY m.superID, super_name";
        $args[] = $store;
        $superP = $dbc->prepare($superQ);
        $superR = $dbc->execute($superP, $args);
        $ret .= '<div class="col-sm-2 pull-right">';
        $ret .= '<div class="fluid-container form-group"><strong>Overall Margins</strong>';
        $ret .= '<table class="table table-bordered small">';
        $first_superID = 0;
        $batchName = 'priceChange '.date('Y-m-d');
        while ($superW = $dbc->fetch_row($superR)) {
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
                        LEFT JOIN $summary AS q ON p.upc=q.upc AND p.store_id=q.storeID
                    WHERE department IN ({$in_sql})
                        AND cost <> 0
                        AND p.store_id=?
                    GROUP BY department, dept_name";
        $marginP = $dbc->prepare($marginQ);
        $marginR = $dbc->execute($marginP, $args);
        while($marginW = $dbc->fetch_row($marginR)) {
            $ret .= sprintf('<tr><td>%d %s</td><td id="dmargin%d">%.4f%%</td></tr>',
                            $marginW['department'], $marginW['dept_name'], $marginW['department'],
                            $marginW['weightedMargin']*100
            );
        }
        $ret .= '</table></div>';
        $ret .= '<div class="fluid-contaienr">
                <strong>Create Batch</strong>'; 
        $ret .= '<div class="form-group">
            <label>Name</label>
            <input type="text" id="batchName" class="form-control input-sm" 
                value="' . $batchName . '" />
            </div>';
        $ret .= '<div class="form-group">
            <label>Tags</label>
            <select id="shelftagSet" class="form-control input-sm"><option value="-1">None</option>';
        $tagR = $dbc->query('SELECT superID, super_name FROM MasterSuperDepts GROUP BY superID, super_name ORDER BY superID');
        while($tagW = $dbc->fetch_row($tagR)) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                            $first_superID == $tagW['superID'] ? 'selected' : '',
                            $tagW['superID'], $tagW['super_name']);
        }
        $ret .= '</select></div>';
        $ret .= '<p><button type="submit" onclick="marginTool.createBatch(); return false;" class="btn btn-default">Create Batch</button></p>';
        $ret .= '</div></div>';

        // list the actual items
        $ret .= '<div class="col-sm-10">';
        $ret .= '<form onsubmit="return false;" method="post">';
        $ret .= '<table id="maintable" class="table tablesorter"><thead>';
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

        list($in_sql, $args) = $dbc->safeInClause($this->upcs);
        $query = 'SELECT p.upc, p.description, p.department, p.cost,
                    p.normal_price, m.superID, q.percentageStoreSales,
                    q.percentageSuperDeptSales, q.percentageDeptSales
                  FROM products AS p
                      LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                      LEFT JOIN ' . $FANNIE_ARCHIVE_DB . $dbc->sep() . 'productSummaryLastQuarter AS q
                        ON p.upc=q.upc AND p.store_id=q.storeID
                  WHERE p.upc IN (' . $in_sql . ')
                    AND p.store_id=?
                  ORDER BY p.upc';
        $args[] = $store;
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
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
                                <input type="text" size="5" name="price[]" class="newprice form-control input-sm"
                                value="%.2f" onchange="marginTool.reCalc(\'%s\', this.value, %f, %d, %d);" />
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
        $ret .= '</div>';

        $this->add_onload_command("\$('#maintable').tablesorter({sortList: [[0,0]], widgets: ['zebra']});");
        $this->addScript('marginTool.js');

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            This tool lists the selected item\'s cost,
            price, and margins as well as each item\'s
            percentage of overall store, super department,
            and department sales. Entering a new price
            will calculate the new item margin. The percentages
            are used for a weighted, contribution to margin
            calculation to predict how changes to an individual
            item will impact overal margin at a department
            or super department level.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->u = 'foo';
        $phpunit->assertEquals(false, $this->post_u_handler());
        $this->u = '4011';
        $phpunit->assertEquals(true, $this->post_u_handler());
        $phpunit->assertNotEquals(0, strlen($this->post_u_view()));

        $this->upc = '0000000004011';
        $this->deptID = 1;
        $this->newprice = 1;
        ob_start();
        $phpunit->assertEquals(false, $this->get_upc_deptID_newprice_handler());
        ob_get_clean();

        $this->upcs = '["' . $this->upc . '"]';
        $this->newprices = '["' . $this->newprice . '"]';
        ob_start();
        $phpunit->assertEquals(false, $this->post_upcs_deptID_newprices_handler());
        ob_get_clean();

        $this->superID = 1;
        ob_start();
        $phpunit->assertEquals(false, $this->get_upc_superID_newprice_handler());
        ob_get_clean();

        $this->upcs = '["' . $this->upc . '"]';
        $this->newprices = '["' . $this->newprice . '"]';
        ob_start();
        $phpunit->assertEquals(false, $this->post_upcs_superID_newprices_handler());
        ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

