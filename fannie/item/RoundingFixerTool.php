<?php
/*******************************************************************************

    Copyright 2017 Whole Foods Community Co-op

    This file is part of CORE-POS.

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
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class RoundingFixerTool extends FannieRESTfulPage
{
    protected $must_authenticate = True;
    protected $auth_classes = array('admin');

    protected $header = 'Rounding Fixer Tool';
    protected $title = 'Rounding Fixer Tool';
    protected $upcs = array();

    public $description = '[Rounding Fixer Tool] finds as set of 32
        products ending in $x.x5 and creates a batch changing price
        endings to $x.x9';

    function preprocess()
    {
        $this->__routes[] = 'get<createBatch>';
        $this->__routes[] = 'get<createBatchAdv>';

        return parent::preprocess();
    }

    function get_view() {

        $this->addScript('../src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile('../src/javascript/chosen/bootstrap-chosen.css');
        $this->addOnloadCommand("\$('select.chosen').chosen();\n");
        $vendors = new VendorsModel($this->connection);
        $vOpts = $vendors->toOptions();
        $supers = new MasterSuperDeptsModel($this->connection);
        $sOpts = $supers->toOptions(-999);
        return <<<HTML
<p>Use this page to create a price-change batch for a set of
    products ending in \$x.x5 and updating them to \$x.x9 to follow
    WFC price-rounding-rules.</p>
<form method="get" action="RoundingFixerTool.php">
<div class="form-group">
    <label>Vendor</label>
    <select name="vendor" class="form-control chosen">
        <option value="">Optional</option>
        {$vOpts}
    </select>
</div>
<div class="form-group">
    <label>Super Department</label>
    <select name="super" class="form-control">
        <option value="">Optional</option>
        {$sOpts}
    </select>
</div>
<input type="hidden" name="i" value="1" />
<ul>
    <li><div class="form-group"><button type="submit" name="createBatch"
        value="true" class="btn btn-default">Create A</button> Update x.x5 to x.x9</div></li>
    <li><div class="form-group"><button type="submit" name="createBatchAdv"
        value="true" class="btn btn-default">Create B</button> Correct invalid price endings</div></li>
</ul>
HTML;
    }

    function get_createBatchAdv_view()
    {
        $i = FormLib::get('i');
        $rounder = new \COREPOS\Fannie\API\item\PriceRounder();
        $date1 = new DateTime();
        $date2 = new DateTime();
        $date2 = $date2->sub(new DateInterval('P2W'));
        $dlog = DTransactionsModel::selectDlog(
            $date2->format('Y-m-d 00:00:00'), $date1->format('Y-m-d 00:00:00'));
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $vendor = FormLib::get('vendor');
        $super = FormLib::get('super');
        $ret = "";

        $args = array(
            $date2->format('Y-m-d H:i:s'),
            $date1->format('Y-m-d H:i:s'),
        );
        $extraWhere = '';
        if ($vendor) {
            $extraWhere .= ' AND p.default_vendor_id=? ';
            $args[] = $vendor;
        }
        if ($super) {
            $extraWhere .= ' AND m.superID=? ';
            $args[] = $super;
        }
        $prep = $dbc->prepare("
            SELECT p.department
            FROM ". $dlog ." AS d
                LEFT JOIN products AS p ON d.upc=p.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE d.tdate BETWEEN ? AND ?
                AND (RIGHT(normal_price, 2) NOT IN (
                    29, 39, 49, 69, 79, 89, 99)
                AND normal_price BETWEEN 0 AND 1)
                OR (RIGHT(normal_price, 2) NOT IN (
                    19, 39, 49, 69, 89, 99)
                AND normal_price BETWEEN 1 AND 3)
                OR (RIGHT(normal_price, 2) NOT IN (
                   39, 69, 99)
                AND normal_price BETWEEN 3 AND 6)
                OR (RIGHT(normal_price, 2) NOT IN (
                   99)
                AND normal_price BETWEEN 6 AND 1
                )
                AND p.price_rule_id = 0
                " . $extraWhere . "
                AND p.upc NOT IN (
                    SELECT upc
                    FROM batchList AS bl
                    LEFT JOIN batches AS b ON bl.batchID=b.batchID
                    WHERE batchName like 'UNFI ROUNDING FIXER%'
                )
            GROUP BY p.department
            ORDER BY SUM(total) DESC
            LIMIT 1;
        ");
        $department = $dbc->getValue($prep, $args);
        $orderBy = 'p.department';
        if ($department) {
            $orderBy = sprintf('CASE WHEN p.department=%d THEN 1 ELSE p.department+99999 END', $department);
        }
        $prep = $dbc->prepare("
            SELECT p.upc, p.brand, p.description, p.normal_price, p.department,
                m.super_name
            FROM ". $dlog ." AS d
                LEFT JOIN products AS p ON d.upc=p.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE d.tdate BETWEEN ? AND ?
                AND (RIGHT(normal_price, 2) NOT IN (
                    29, 39, 49, 69, 79, 89, 99)
                AND normal_price BETWEEN 0 AND 1)
                OR (RIGHT(normal_price, 2) NOT IN (
                    19, 39, 49, 69, 89, 99)
                AND normal_price BETWEEN 1 AND 3)
                OR (RIGHT(normal_price, 2) NOT IN (
                   39, 69, 99)
                AND normal_price BETWEEN 3 AND 6)
                OR (RIGHT(normal_price, 2) NOT IN (
                   99)
                AND normal_price BETWEEN 6 AND 1
                )
                AND p.price_rule_id = 0
                " . $extraWhere . "
                AND p.upc NOT IN (
                    SELECT upc
                    FROM batchList AS bl
                    LEFT JOIN batches AS b ON bl.batchID=b.batchID
                    WHERE batchName like 'UNFI ROUNDING FIXER%'
                )
            GROUP BY d.upc
            ORDER BY " . $orderBy . "
            LIMIT 32;
        ");
        $res = $dbc->execute($prep, $args);
        $upcs = array();
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $upcs[$row['upc']] = $row['normal_price'];
            $data[$row['upc']]['brand'] = $row['brand'];
            $data[$row['upc']]['description'] = $row['description'];
            $data[$row['upc']]['dept'] = $row['super_name'];
            $this->upcs[$row['upc']] = $rounder->round($row['normal_price']);
        }

        $table = "<div class='table-responsive'><table class='table table-condensed small'>
            <thead><th>upc</th><th>Brand</th><th>Description</th><th>Department</th>
            <th>Current Price</th><th>New Price</th></thead>";
        foreach ($upcs as $upc => $price) {
            $table .= "<tr>";
            $newprice = $rounder->round($price);
            $table .= "<td>$upc</td><td>{$data[$upc]['brand']}</td>
                <td>{$data[$upc]['description']}</td><td>{$data[$upc]['dept']}</td>
                <td>$price</td><td>$newprice</td>";
            $table .= "</tr>";
        }
        $table .= "</table></div>";

        list($saved, $batchID) = $this->createBatch($dbc, $i);
        $name = "UNFI ROUNDING FIXER ".$date1->format('Y-m-d')." [$i]";

        if ($saved == true) {
            $alertType = 'success';
            $alertVerb = ' creation successful';
            $i++;
        } else {
            $alertType = 'danger';
            $alertVerb = ' creation failed';
        }

        return <<<HTML
<div class="container">
<p><a class="btn btn-default" href="{$_SERVER['PHP_SELF']}?createBatchAdv=true&i=$i">
    Create</a> another batch</p>
<div class="alert alert-$alertType">
<div>Batch: "$name" $alertVerb.</div>
<p><a href="../batches/newbatch/EditBatchPage.php?id=$batchID" target="_blank">
    view</a> in Batch Editor.</p>
<div>
<h4>$name</h4>
$table
</div></div>
HTML;
    }

    function get_createBatch_view()
    {
        $i = FormLib::get('i');
        $date1 = new DateTime();
        $date2 = new DateTime();
        $date2 = $date2->sub(new DateInterval('P2W'));
        $dlog = DTransactionsModel::selectDlog(
            $date2->format('Y-m-d 00:00:00'), $date1->format('Y-m-d 00:00:00'));
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $vendor = FormLib::get('vendor');
        $super = FormLib::get('super');
        $ret = "";

        $args = array(
            $date2->format('Y-m-d H:i:s'),
            $date1->format('Y-m-d H:i:s'),
        );
        $extraWhere = '';
        if ($vendor) {
            $extraWhere .= ' AND p.default_vendor_id=? ';
            $args[] = $vendor;
        }
        if ($super) {
            $extraWhere .= ' AND m.superID=? ';
            $args[] = $super;
        }

        $prep = $dbc->prepare("
            SELECT p.department
            FROM ". $dlog ." AS d
                LEFT JOIN products AS p ON d.upc=p.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE d.tdate BETWEEN ? AND ?
                AND RIGHT(p.normal_price, 1) = 5
                AND p.price_rule_id = 0
                " . $extraWhere . "
                AND p.upc NOT IN (
                    SELECT upc
                    FROM batchList AS bl
                    LEFT JOIN batches AS b ON bl.batchID=b.batchID
                    WHERE batchName like 'UNFI ROUNDING FIXER%'
                )
            GROUP BY p.department
            ORDER BY SUM(d.total) DESC
            LIMIT 1;
        ");
        $department = $dbc->getValue($prep, $args);
        $orderBy = 'p.department';
        if ($department) {
            $orderBy = sprintf('CASE WHEN p.department=%d THEN 1 ELSE p.department+99999 END', $department);
        }
        $orderBy = "MAX(p.last_sold) DESC";
        $prep = $dbc->prepare("
            SELECT p.upc, p.brand, p.description, p.normal_price, p.department,
                m.super_name, v.srp
            FROM ". $dlog ." AS d
                LEFT JOIN products AS p ON d.upc=p.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
            WHERE d.tdate BETWEEN ? AND ?
                AND RIGHT(p.normal_price, 1) = 5
                AND p.price_rule_id = 0
                " . $extraWhere . "
                AND p.upc NOT IN (
                    SELECT upc
                    FROM batchList AS bl
                    LEFT JOIN batches AS b ON bl.batchID=b.batchID
                    WHERE batchName like 'UNFI ROUNDING FIXER%'
                )
            GROUP BY d.upc
            ORDER BY " . $orderBy . "
        ");
        $res = $dbc->execute($prep, $args);
        $upcs = array();
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $upcs[$row['upc']] = $row['normal_price'] + .04;
            $data[$row['upc']]['brand'] = $row['brand'];
            $data[$row['upc']]['description'] = $row['description'];
            $data[$row['upc']]['dept'] = $row['super_name'];
            $this->upcs[$row['upc']] = $row['normal_price'] + .04;;
            if (substr($row['srp'], -1) == '9' && $row['srp'] > $row['normal_price']) {
                $this->upcs[$row['upc']] = $row['srp'];
            }
            if (count($this->upcs) >= 32) {
                break;
            }
        }

        $table = "<div class='table-responsive'><table class='table table-condensed small'>
            <thead><th>upc</th><th>Brand</th><th>Description</th><th>Department</th>
            <th>Current Price</th><th>New Price</th></thead>";
        foreach ($upcs as $upc => $price) {
            $table .= "<tr>";
            $curprice = $price - 0.04;
            $table .= "<td>$upc</td><td>{$data[$upc]['brand']}</td>
                <td>{$data[$upc]['description']}</td><td>{$data[$upc]['dept']}</td>
                <td>$curprice</td><td>$price</td>";
            $table .= "</tr>";
        }
        $table .= "</table></div>";

        list($saved, $batchID) = $this->createBatch($dbc, $i);
        $name = "UNFI ROUNDING FIXER ".$date1->format('Y-m-d')." [$i]";

        if ($saved == true) {
            $alertType = 'success';
            $alertVerb = ' creation successful';
            $i++;
        } else {
            $alertType = 'danger';
            $alertVerb = ' creation failed';
        }

        return <<<HTML
<div class="container">
<p><a class="btn btn-default" href="{$_SERVER['PHP_SELF']}?createBatch=true&i=$i">
    Create</a> another batch</p>
<div class="alert alert-$alertType">
<div>Batch: "$name" $alertVerb.</div>
<p><a href="../batches/newbatch/EditBatchPage.php?id=$batchID" target="_blank">
    view</a> in Batch Editor.</p>
<div>
<h4>$name</h4>
$table
</div></div>
HTML;
    }

    private function createBatch($dbc, $i)
    {
        $upcs = $this->upcs;
        $date = new DateTime();
        $name = "UNFI ROUNDING FIXER ".$date->format('Y-m-d')." [$i]";
        $batch = new BatchesModel($dbc);
        $batch->startDate('');
        $batch->endDate('');
        $batch->batchName($name);
        $batch->batchType(4);
        $batch->discountType(0);
        $batch->priority(null);
        $batch->owner('IT');
        $batchID = $batch->save();
        $bu = new BatchUpdateModel($dbc);
        $bu->batchID($batchID);
        $bu->logUpdate($bu::UPDATE_CREATE);

        if ($this->config->get('STORE_MODE') === 'HQ') {
            StoreBatchMapModel::initBatch($batchID);
        }

        if ($dbc->tableExists('batchowner')) {
            $insQ = $dbc->prepare("insert batchowner values (?,?)");
            $insR = $dbc->execute($insQ,array($batchID,$owner));
        }

        //add items to batch
        $dbc->startTransaction();
        $bu = new BatchUpdateModel($dbc);
        foreach ($upcs as $upc => $price) {
            $list = new BatchListModel($dbc);
            $list->upc(BarcodeLib::padUPC($upc));
            $list->batchID($batchID);
            $list->salePrice($price);
            $list->groupSalePrice($price);
            $list->active(0);
            $list->pricemethod(0);
            $list->quantity(0);
            $saved = $list->save();
            $bu->reset();
            $bu->upc(BarcodeLib::padUPC($upc));
            $bu->batchID($batchID);
            $bu->logUpdate($bu::UPDATE_ADDED);
        }
        $dbc->commitTransaction();

        return array($saved, $batchID);
    }

    public function helpContent()
    {
        return <<<HTML
<p>Click the <strong>create</strong> button to make a price-change batch to
    update the ending digit of 32 products from x.x5 to x.x9</p>
<label>Conditions for eligible products</label>
<ul>
    <li>Price ends in x.x5</li>
    <li>Does not use a variable pricing rule.</li>
    <li>Has sales within the last 2 weeks.</li>
    <li>Comes from the vendor 1: UNFI.</li>
</ul>
HTML;
    }

}
FannieDispatch::conditionalExec();
