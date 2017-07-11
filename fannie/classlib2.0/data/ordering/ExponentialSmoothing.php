<?php

namespace COREPOS\Fannie\API\data\ordering;
use COREPOS\Fannie\API\lib\Stats;
use \FannieConfig;
use \InventoryCountsModel;
use \DateTime;
use \DateInterval;

/**
 * @class ExponentialSmoothing
 * Calculate automated ordering pars based on sales history using
 * exponential smoothing
 */
class ExponentialSmoothing
{
    /**
     * @param $json [array] current configuration
     * @return [string] HTML form fragment
     *
     * Exponential smoothing takes four configuration values
     * - alpha [percent] the smoothing factor
     * - loss [percent] expected loss rate
     * - minPoints [integer] number of sales data points necessary
     *      to calculate pars
     * - default [integer] par used when data is insufficient
     */
    public function renderParams($json)
    {
        if (!isset($json['alpha'])) {
            $json['alpha'] = 0.7;
        }
        if (!isset($json['loss'])) {
            $json['loss'] = 0;
        }
        if (!isset($json['carrythrough'])) {
            $json['carrythrough'] = 1;
        }
        if (!isset($json['default'])) {
            $json['default'] = 1;
        }
        if (!isset($json['minPoints'])) {
            $json['minPoints'] = 3;
        }
        $json['alpha'] = sprintf('%.2f', 100*$json['alpha']);
        $json['loss'] = sprintf('%.2f', 100*$json['loss']);
        $json['carrythrough'] = sprintf('%.2f', 100*$json['carrythrough']);

        return <<<HTML
<div class="form-group">
    <label>Loss Factor</label>
    <div class="input-group">
        <input type="number" min="0" max="100" step="1" name="esLoss" class="form-control" value="{$json['loss']}" />
        <span class="input-group input-group-addon">%</span>
    </div>
</div>
<div class="form-group">
    <label>Smoothing Factor (Alpha)</label>
    <div class="input-group">
        <input type="number" min="1" max="100" step="1" name="esAlpha" class="form-control" value="{$json['alpha']}" />
        <span class="input-group input-group-addon">%</span>
    </div>
</div>
<div class="form-group">
    <label>Min. Data Points</label>
    <input type="number" min="1" max="20" step="1" name="esMin" value="{$json['minPoints']}" class="form-control" />
</div>
<div class="form-group">
    <label>Carrythrough Rate for Zero Sales Days</label>
    <div class="input-group">
        <input type="number" min="1" max="100" step="1" name="carrythrough" class="form-control" value="{$json['carrythrough']}" />
        <span class="input-group input-group-addon">%</span>
    </div>
</div>
<div class="form-group">
    <label>Default</label>
    <input type="number" min="1" max="20" step="1" name="esDefault" value="{$json['default']}" class="form-control" />
</div>
HTML;
    }

    /**
     * @param $form [ValueContainer] HTTP GET/POST parameters
     * @param $model [BasicModel] database record for parameters
     *
     * Save form values back to the database
     */
    public function saveParams($form, $model)
    {
        try {
            $json = array(
                'alpha' => $form->esAlpha / 100.00,
                'loss' => $form->esLoss / 100.00,
                'default' => $form->esDefault,
                'minPoints' => $form->esMin,
                'carrythrough' => $form->carrythrough / 100.00,
            );
            $model->parameters(json_encode($json));
            return $model->save();
        } catch (\Exception $ex) { }

        return false;
    }

    /**
     * @param $dbc [SQLManager] database connection
     * @param $vendorID [integer]
     * @param $deptID [integer] POS department number
     * @param $storeID [integer]
     * @param $json [array] current configuration
     *
     * Examine recent sales history and set or update pars for all
     * items that are enrolled in inventory
     */
    public function getNewPars($dbc, $vendorID, $deptID, $storeID, $json)
    {
        $dlog = FannieConfig::config('TRANS_DB') . $dbc->sep() . 'dlog_15';
        $today = date('Y-m-d 00:00:00');

        $salesP = $dbc->prepare("
            SELECT YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                SUM(quantity) AS qty
            FROM {$dlog}
            WHERE store_id=?
                AND upc=?
                AND trans_status <> 'R'
                AND tdate < ?
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate)
            HAVING SUM(total) <> 0
            ORDER BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate)");
        $p1d = new DateInterval('P1D');

        // lookup all items active for that store, vendor,
        // and optionally POS department
        $itemQ = "SELECT p.upc, p.store_id
            FROM products AS p
            WHERE p.default_vendor_id=? 
                AND p.inUse=1
                AND p.store_id=? "
                . ($deptID != 0 ? ' AND p.department=? ' : '');
        $itemP = $dbc->prepare($itemQ);
        $args = array($vendorID, $storeID);
        if ($deptID != 0) {
            $args[] = $deptID;
        }
        $itemR = $dbc->execute($itemP, $args);
        $ret = array();
        while ($itemW = $dbc->fetchRow($itemR)) {
            // get daily sales the item at a given store
            // track days to fill in zero datapoints when
            // there are gaps in sales history
            $currentDay = null;
            $lastQty = 0;
            $points = array();
            $par = $json['default'];
            $salesR = $dbc->execute($salesP, array($storeID, $itemW['upc'], $today));
            while ($salesW = $dbc->fetchRow($salesR)) {
                $rowDay = new DateTime(date('Y-m-d', mktime(0,0,0, $salesW['month'], $salesW['day'], $salesW['year'])));
                while ($currentDay !== null && $rowDay > $currentDay) {
                    $lastQty *= $json['carrythrough'];
                    $points[] = $lastQty;
                    $currentDay = $currentDay->add($p1d);
                }
                $currentDay = $rowDay;
                $points[] = $salesW['qty'];
                $lastQty = $salesW['qty'];
            }
            // calculate par if appropriate
            if (count($points) > 0 && count($points) > $json['minPoints']) {
                $par = Stats::expSmoothing($points, $json['alpha']);
                $par *= (1 + $json['loss']);
                $ret[] = array('upc' => $itemW['upc'], 'storeID' => $storeID, 'par' => $par);
            }
        }

        return $ret;
    }

    public function setPar($dbc, $upc, $storeID, $par)
    {
        // Save the par
        //   If a par exists already it should only be replaced
        //   when the new par can be calculated from sales data.
        //   Otherwise an item with no sales at all could jump
        //   back up to the default par.
        $inv = new InventoryCountsModel($dbc);
        $inv->upc($itemW['upc']);
        $inv->storeID($storeID);
        $inv->mostRecent(1);
        $found = false;
        foreach ($inv->find() as $i) {
            $i->par($par);
            $i->save();
            $found = true;
        }
        if (!$found) {
            $inv->mostRecent(1);
            $inv->count(0);
            $inv->par($par);
            $inv->countDate(date('Y-m-d H:i:s'));
            $inv->save();
        }
    }
}

if (basename($_SERVER['PHP_SELF']) == 'ExponentialSmoothing.php') {
    include(__DIR__ . '/../../../config.php');
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
    $obj = new ExponentialSmoothing();
    $dbc = \FannieDB::get('is4c_op');
    $model = new \ParAlgorithmsModel($dbc);
    $model->vendorID(242);
    $model->deptID(0);
    $model->storeID(1);
    if ($model->load()) {
        $json = json_decode($model->parameters(), true);
        $obj->updatePars($dbc, 242, 0, 1, $json);
        $obj->updatePars($dbc, 242, 0, 2, $json);
    }
}

