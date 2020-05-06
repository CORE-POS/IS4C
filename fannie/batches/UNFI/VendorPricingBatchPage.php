<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

use \COREPOS\Fannie\API\item\Margin;
use \COREPOS\Fannie\API\item\PriceRounder;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class VendorPricingBatchPage extends FannieRESTfulPage
{
    protected $title = "Fannie - Create Price Change Batch";
    protected $header = "Create Price Change Batch";

    public $description = '[Vendor Price Change] creates a price change batch for a given
    vendor and edits it based on catalog cost information.';
    public $themed = true;

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;

    private $mode = 'start';

    public function css_content()
    {
        return '
        span.grey {
            color: grey;
        }
        tr.green td.sub {
            background:#ccffcc;
        }
        tr.greenb td.sub {
            background:#ddffcc;
        }
        tr.red td.sub {
            background:#F7BABA;
        }
        tr.white td.sub {
            background:#ffffff;
        }
        th.thead, td.thead {
            background: #fff4d6;
        }
        tr.yellow td.sub {
            background:#ffff96;
        }
        tr.selection td.sub {
            background:#add8e6;
        }
        td.srp {
            text-decoration: underline;
        }
        .adj-cost, .price, .cmargin {
            border: 5px solid red;
            background: red;
            background-color: red;
            color: gray;
        }
        ';
    }

    public function get_id_view()
    {
        $this->addScript($this->config->get('URL') . 'src/javascript/jquery.floatThead.min.js');
        $this->addScript('pricing-batch.js');
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);

        $superID = FormLib::get('super', -1);
        $queueID = FormLib::get('queueID');
        $vendorID = $this->id;
        $filter = FormLib::get_form_value('filter') == 'Yes' ? True : False;

        /* lookup vendor and superdept names to build a batch name */
        $sname = "All";
        if ($superID >= 0) {
            $smodel = new SuperDeptNamesModel($dbc);
            $smodel->superID($superID);
            $smodel->load();
            $sname = $smodel->super_name();
        }
        $vendor = new VendorsModel($dbc);
        $vendor->vendorID($vendorID);
        $vendor->load();

        $batchName = $sname." ".$vendor->vendorName()." PC ".date('m/d/y');

        /* find a price change batch type */
        $types = new BatchTypeModel($dbc);
        $types->discType(0);
        $bType = 0;
        foreach ($types->find() as $obj) {
            $bType = $obj->batchTypeID();
            break;
        }

        /* get the ID of the current batch. Create it if needed. */
        $bidQ = $dbc->prepare("
            SELECT batchID
            FROM batches
            WHERE batchName=?
                AND batchType=?
                AND discounttype=0
            ORDER BY batchID DESC");
        $bidR = $dbc->execute($bidQ,array($batchName,$bType));
        $batchID = 0;
        if ($dbc->numRows($bidR) == 0) {
            $b = new BatchesModel($dbc);
            $b->batchName($batchName);
            $b->startDate('1900-01-01');
            $b->endDate('1900-01-01');
            $b->batchType($bType);
            $b->discountType(0);
            $b->priority(0);
            $batchID = $b->save();
            $bu = new BatchUpdateModel($dbc);
            $bu->batchID($batchID);
            $bu->logUpdate($bu::UPDATE_CREATE);
            if ($this->config->get('STORE_MODE') === 'HQ') {
                StoreBatchMapModel::initBatch($batchID);
            }
        } else {
            $bidW = $dbc->fetchRow($bidR);
            $batchID = $bidW['batchID'];
        }

        $ret = sprintf('<b>Batch</b>:
                    <a href="%sbatches/newbatch/BatchManagementTool.php?startAt=%d">%s</a>',
                    $this->config->URL,
                    $batchID,
                    $batchName);
        $ret .= sprintf("<input type=hidden id=vendorID value=%d />
            <input type=hidden id=batchID value=%d />
            <input type=hidden id=queueID value=%d />
            <input type=hidden id=superID value=%d />",
            $vendorID,$batchID,$queueID,$superID);
        $ret .= '<br/><b>View: </b>: 
            <button class="btn btn-danger btn-xs btn-filter active" data-filter-type="red">Red</button> 
            | <button class="btn btn-warning btn-xs btn-filter active" data-filter-type="yellow">Yellow</button> 
            | <button class="btn btn-success btn-xs btn-filter active" data-filter-type="green">Green</button> 
            | <button class="btn btn-default btn-xs btn-filter active" data-filter-type="white">White</button> 
            | <button class="btn btn-default btn-xs multi-filter active" data-filter-type="multiple">
                <span class="glyphicon glyphicon-exclamation-sign" title="View only rows containing multiple SKUs"> </span>
            </button> 
            <br/><br/>';

        $batchUPCs = array();
        $batchList = new BatchListModel($dbc);
        $batchList->batchID($batchID);
        foreach ($batchList->find() as $obj) {
            $batchUPCs[$obj->upc()] = true;
        }

        $costSQL = Margin::adjustedCostSQL('p.cost', 'b.discountRate', 'b.shippingMarkup');
        $marginSQL = Margin::toMarginSQL($costSQL, 'p.normal_price');
        $marginCase = '
            CASE
                WHEN g.margin IS NOT NULL AND g.margin <> 0 THEN g.margin
                WHEN s.margin IS NOT NULL AND s.margin <> 0 THEN s.margin
                ELSE d.margin
            END';
        $srpSQL = Margin::toPriceSQL($costSQL, $marginCase);

        /*
        //  Scan both stores to find a list of items that are inUse.
        $itemsInUse = array();
        $query = $dbc->prepare("SELECT upc FROM products WHERE inUse = 1");
        $result = $dbc->execute($query);
        while ($row = $dbc->fetchRow($result)) {
            $itemsInUse[$row['upc']] = 1;
        }
        */

        $aliasP = $dbc->prepare("
            SELECT v.srp,
                v.vendorDept,
                a.multiplier
            FROM VendorAliases AS a
                INNER JOIN vendorItems AS v ON a.sku=v.sku AND a.vendorID=v.vendorID
            WHERE a.upc=?");

        $query = "SELECT p.upc,
            p.description,
            p.brand,
            p.cost,
            b.shippingMarkup,
            b.discountRate,
            p.normal_price,
            " . Margin::toMarginSQL($costSQL, 'p.normal_price') . " AS current_margin,
            " . Margin::toMarginSQL($costSQL, 'v.srp') . " AS desired_margin,
            " . $costSQL . " AS adjusted_cost,
            v.srp,
            " . $srpSQL . " AS rawSRP,
            v.vendorDept,
            p.price_rule_id AS variable_pricing,
            prt.priceRuleTypeID,
            prt.description AS prtDesc,
            " . $marginCase . " AS margin,
            CASE WHEN a.sku IS NULL THEN 0 ELSE 1 END as alias,
            CASE WHEN l.upc IS NULL THEN 0 ELSE 1 END AS likecoded,
            c.difference,
            c.date,
            r.reviewed,
            v.sku
            FROM products AS p
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN VendorAliases AS a ON p.upc=a.upc AND p.default_vendor_id=a.vendorID
                INNER JOIN vendors as b ON v.vendorID=b.vendorID
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN vendorDepartments AS s ON v.vendorDept=s.deptID AND v.vendorID=s.vendorID
                LEFT JOIN VendorSpecificMargins AS g ON p.department=g.deptID AND v.vendorID=g.vendorID
                LEFT JOIN upcLike AS l ON v.upc=l.upc 
                LEFT JOIN productCostChanges AS c ON p.upc=c.upc 
                LEFT JOIN prodReview AS r ON p.upc=r.upc
                LEFT JOIN PriceRules AS pr ON p.price_rule_id=pr.priceRuleID
                LEFT JOIN PriceRuleTypes AS prt ON pr.priceRuleTypeID=prt.priceRuleTypeID
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
        WHERE v.cost > 0
                AND v.vendorID=?
                AND m.SuperID IN (1, 3, 4, 5, 8, 9, 13, 17, 18)
        ";
        $args = array($vendorID);
        if ($superID == -2) {
            $query .= " AND m.superID<>0 ";
        } elseif ($superID != -1) {
            $query .= " AND m.superID=? ";
            $args[] = $superID;
        }
        if ($filter === false) {
            $query .= " AND p.normal_price <> v.srp ";
        }
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $query .= ' AND p.store_id=? ';
            $args[] = $this->config->get('STORE_ID');
        }

        $query .= ' AND p.upc IN (SELECT upc FROM products WHERE inUse = 1) ';

        $query .= " ORDER BY p.upc";

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);

        $vendorModel = new VendorItemsModel($dbc);

        $ret .= "<table class=\"table table-bordered small\" id=\"mytable\">";

        $ret .= "<thead><tr class=\"thead\">
            <th class=\"thead\">UPC</th>
            <th class=\"thead\">SKU</th>
            <th class=\"thead\">Our Description</th>
            <th class=\"thead\">Adj. Cost</th>
            <th class=\"thead\">Price</th>
            <th class=\"thead\">Margin</th>
            <th class=\"thead\">Last Change</th>
            <th class=\"thead\">Reviewed</th>
            <th class=\"thead\">Raw</th>
            <th class=\"thead\">SRP</th>
            <th class=\"thead\">Margin</th>
            <th class=\"thead\">Var</th>
            <th class=\"thead\">Batch</th>
            <th class=\"thead\">Ignore</th></tr></thead><tbody>";
        $rounder = new PriceRounder();
        while ($row = $dbc->fetch_row($result)) {
            $vendorModel->reset();
            $vendorModel->upc($row['upc']);
            $vendorModel->vendorID($vendorID);
            $vendorModel->load();
            $numRows = $vendorModel->find();
            $multipleVendors = '';
            if (count($numRows) > 1) {
                $multipleVendors = '<span class="glyphicon glyphicon-exclamation-sign"
                    title="Multiple SKUs For This Product">
                    </span> ';
            }
            if ($row['alias']) {
                $alias = $dbc->getRow($aliasP, array($row['upc']));
                $row['vendorDept'] = $alias['vendorDept'];
                $row['srp'] = $alias['srp'] * $alias['multiplier'];
                $row['srp'] = $rounder->round($row['srp']);
            }
            if ($row['difference']) {
            }
            $background = "white";
            $acceptPrtID = array(1, 10);
            if (isset($batchUPCs[$row['upc']]) && !$row['likecoded']) {
                $background = 'selection';
            } elseif (in_array($row['priceRuleTypeID'], $acceptPrtID) || $row['variable_pricing'] == 0 && $row['normal_price'] < 10.00) {
                $background = (
                    ($row['normal_price']+0.10 < $row['rawSRP'])
                    && ($row['srp']-.14 > $row['normal_price']
                    && $row['rawSRP'] - floor($row['rawSRP']) > 0.10)
                ) ?'red':'green';
                if ($row['normal_price']-.10 > $row['rawSRP']) {
                    $background = (
                        ($row['normal_price']-.10 > $row['rawSRP'])
                        && ($row['normal_price']-.14 > $row['srp'])
                        && ($row['rawSRP'] < $row['srp']+.10)
                    )?'yellow':'green';
                }
            } elseif (in_array($row['priceRuleTypeID'], $acceptPrtID) || $row['variable_pricing'] == 0 && $row['normal_price'] >= 10.00) {
                $background = ($row['normal_price'] < $row['rawSRP']
                    && $row['srp'] > $row['normal_price']
                    && $row['rawSRP'] - floor($row['rawSRP']) > 0.10
                    ) ?'red':'green';
                if ($row['normal_price']-0.49 > $row['rawSRP']) {
                    $background = ($row['normal_price']-0.49 > $row['rawSRP']
                        && ($row['normal_price'] > $row['srp'])
                        && ($row['rawSRP'] < $row['srp']+.10) )?'yellow':'green';
                }
            }
            if (isset($batchUPCs[$row['upc']])) {
                $icon = '<span class="glyphicon glyphicon-minus-sign"
                    title="Remove from batch">
                    </span>';
            } else {
                $icon = '<span class="glyphicon glyphicon-plus-sign"
                    title="Add to batch">
                    </span>';
            }
            $brand = rtrim(substr($row['brand'], 0, 15));
            $symb = ($row['difference'] > 0) ? "+" : "";
            $row['date'] = ($row['date']) ? "<span class='grey'> <i>on</i> </span> ".$row['date'] : "";
            $ret .= sprintf("<tr id=row%s class='%s %s'>
                <td class=\"sub\"><a href=\"%sitem/ItemEditorPage.php?searchupc=%s\">%s</a></td>
                <td class=\"sub sku\">%s</td>
                <td class=\"sub\"><strong>%s</strong> %s</td>
                <td class=\"sub adj-cost\">%.3f</td>
                <td class=\"sub price\">%.2f</td>
                <td class=\"sub cmargin\">%.2f%%</td>
                <td class=\"sub change\">%s%.2f %s</td>
                <td class=\"sub reviewed\">%s</td>
                <td class=\"sub raw-srp\">%.2f</td>
                <td onclick=\"reprice('%s');\" class=\"sub srp\">%.2f</td>
                <td class=\"sub dmargin\">%.2f%%</td>
                <td><input class=varp type=checkbox onclick=\"toggleV('%s');\" %s /><span> %s</span></td>
                <td class=white>
                    <a class=\"add-button %s\" href=\"\"
                        onclick=\"addToBatch('%s'); return false;\">
                        <span class=\"glyphicon glyphicon-plus-sign\"
                            title=\"Add item to batch\"></span>
                    </a>
                    <a class=\"remove-button %s\" href=\"\"
                        onclick=\"removeFromBatch('%s'); return false;\">
                        <span class=\"glyphicon glyphicon-minus-sign\"
                            title=\"Remove item from batch\"></span>
                    </a>
                </td>
                <td class=\"clickIgnore\"><input type=\"checkbox\"/></td>
                </tr>",
                $row['upc'],
                $background, $mtp = ($multipleVendors == '') ? '' : 'multiple',
                $this->config->URL, $row['upc'], $row['upc'],
                $row['sku'],
                $temp = (strlen($brand) == 10) ? "$brand~" : $brand,
                $row['description'] . ' ' . $multipleVendors,
                $row['adjusted_cost'],
                $row['normal_price'],
                100*$row['current_margin'],
                $symb, $row['difference'], $row['date'],
                $row['reviewed'],
                $row['rawSRP'],
                $row['upc'],
                $row['srp'],
                100*$row['desired_margin'],
                $row['upc'],
                ($row['variable_pricing']>=1?'checked':''),
                $row['prtDesc'],
                (isset($batchUPCs[$row['upc']])?'collapse':''), $row['upc'],
                (!isset($batchUPCs[$row['upc']])?'collapse':''), $row['upc']
            );
        }
        $ret .= "</tbody></table>";

        return $ret;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);

        $prep = $dbc->prepare("
            SELECT superID,
                super_name
            FROM MasterSuperDepts
            WHERE superID > 0
            GROUP BY superID,
                super_name");
        $res = $dbc->execute($prep);
        $opts = "<option value=\"-1\" selected>All</option>";
        $opts .= "<option value=\"-2\" selected>All Retail</option>";
        while ($row = $dbc->fetch_row($res)) {
            $opts .= "<option value=$row[0]>$row[1]</option>";
        }

        $vmodel = new VendorsModel($dbc);
        $vopts = "";
        foreach ($vmodel->find('vendorName') as $obj) {
            $vopts .= sprintf('<option value="%d">%s</option>',
                $obj->vendorID(), $obj->vendorName());
        }

        $queues = new ShelfTagQueuesModel($dbc);
        $qopts = $queues->toOptions();

        ob_start();
        ?>
        <form action=VendorPricingBatchPage.php method="get" target="_blank">
        <label>Select a Vendor</label>
        <select name="id" class="form-control">
        <?php echo $vopts; ?>
        </select>
        <label>and a Super Department</label>
        <select name=super class="form-control">
        <?php echo $opts; ?>
        </select>
        <label>Show all items</label>
        <select name=filter class="form-control">
        <option>No</option>
        <option>Yes</option>
        </select>
        <label>Shelf Tag Queue</label>
        <select name="queueID" class="form-control">
        <?php echo $qopts; ?>
        </select>
        <br />
        <p>
        <button type=submit class="btn btn-default">Continue</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function javascript_content()
    {
        ob_start();
        ?>
        $('.green').each(function(){
            var price = $(this).find('td:eq(4)').text();
            price = parseFloat(price);
            var srp = $(this).find('td:eq(9)').text();
            srp = parseFloat(srp);
            if (price < srp) {
                var text = $(this).find('td:eq(10)').text();
                $(this).find('td:eq(10)').css('background', 'pink');
            } else {
                var text = $(this).find('td:eq(10)').text();
            }
        });
        var $table = $('#mytable');
        $table.floatThead();
        function showOnlyClass(classname) {
            showAll();
            $('tr').each(function() {
                var hasclass = $(this).closest('tr').hasClass(classname);
                if (!hasclass) $(this).closest('tr').hide();
            });
        }
        function showAll()
        {
            $('tr').each(function() {
                $(this).closest('tr').show();
            });
        }
       $('.clickIgnore').on('click', function(){
           $(this).closest('tr').hide();
       });
       $('.btn-filter').click(function() {
           var active = $(this).hasClass('active') ? true : false;
           if (active === true) {
                $(this).removeClass('active');
           } else {
                $(this).addClass('active');
           }
           $('.btn-filter').each(function(){
               var type = $(this).attr('data-filter-type');
               var curActive = $(this).hasClass('active') ? true : false;
               if (curActive === true) {
                   $('tr').each(function(){
                       if ($(this).hasClass(type)) {
                            $(this).show();
                       }
                   });
               } else {
                   $('tr').each(function(){
                       if ($(this).hasClass(type)) {
                            $(this).hide();
                       }
                   });

               }
           });
       });
       $('.multi-filter').click(function(){
           var active = $(this).hasClass('active') ? true : false;
           if (active === true) {
                $('.btn-filter').each(function(){
                    $(this).removeClass('active');
                });
                $('tr').each(function(){
                    if (!$(this).hasClass('multiple')) {
                        if ($(this).is('.red, .white, .yellow, .blue, .green')) {
                            $(this).hide();
                        }
                    }
                });
                $(this).removeClass('active');
           } else {
                $(this).addClass('active');
                $('tr').each(function(){
                    $(this).show();
                });
                $('.btn-filter').each(function(){
                    $(this).addClass('active');
                });
           }
       });
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Review products from the vendor with current vendor cost,
            retail price, and margin information. The tool creates a price
            change batch in the background. It will add items to this batch
            and automatically create shelf tags.</p>
            <p>The default <em>Show all items</em> setting, No, omits items
            whose current retail price is identical to the margin-based
            suggested retail price.</p>
            ';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }
}

FannieDispatch::conditionalExec();

