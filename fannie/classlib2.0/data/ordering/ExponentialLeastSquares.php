<?php

namespace COREPOS\Fannie\API\data\ordering;
use COREPOS\Fannie\API\lib\Stats;
use \FannieConfig;
use \InventoryCountsModel;
use \DateTime;
use \DateInterval;

/**
 * @class ExponentialLeastSquares
 * Calculate automated ordering pars based on sales history using
 * exponential least squares fitting for a trend line
 */
class ExponentialLeastSquares
{
    /**
     * @param $json [array] current configuration
     * @return [string] HTML form fragment
     *
     * Exponential smoothing takes four configuration values
     * - loss [percent] expected loss rate
     * - minPoints [integer] number of sales data points necessary
     *      to calculate pars
     * - default [integer] par used when data is insufficient
     */
    public function renderParams($json)
    {
        if (!isset($json['loss'])) {
            $json['loss'] = 0;
        }
        if (!isset($json['default'])) {
            $json['default'] = 1;
        }
        if (!isset($json['minPoints'])) {
            $json['minPoints'] = 5;
        }
        $json['alpha'] = sprintf('%.2f', 100*$json['alpha']);
        $json['loss'] = sprintf('%.2f', 100*$json['loss']);

        return <<<HTML
<div class="form-group">
    <label>Loss Factor</label>
    <div class="input-group">
        <input type="number" min="0" max="100" step="1" name="esLoss" class="form-control" value="{$json['loss']}" />
        <span class="input-group input-group-addon">%</span>
    </div>
</div>
<div class="form-group">
    <label>Min. Data Points</label>
    <input type="number" min="1" max="20" step="1" name="esMin" value="{$json['minPoints']}" class="form-control" />
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
                'loss' => $form->esLoss / 100.00,
                'default' => $form->esDefault,
                'minPoints' => $form->esMin,
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
    public function updatePars($dbc, $vendorID, $deptID, $storeID, $json)
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
        $dbc->startTransaction();
        while ($itemW = $dbc->fetchRow($itemR)) {
            // get daily sales the item at a given store
            // track days to fill in zero datapoints when
            // there are gaps in sales history
            $currentDay = null;
            $points = array();
            $default = true;
            $par = $json['default'];
            $salesR = $dbc->execute($salesP, array($storeID, $itemW['upc'], $today));
            $xVal = 0;
            while ($salesW = $dbc->fetchRow($salesR)) {
                $rowDay = new DateTime(date('Y-m-d', mktime(0,0,0, $salesW['month'], $salesW['day'], $salesW['year'])));
                while ($currentDay !== null && $rowDay > $currentDay) {
                    $points[] = array($xVal, 0);
                    $xVal++;
                    $currentDay = $currentDay->add($p1d);
                }
                $currentDay = $rowDay;
                $points[] = array($xVal, $salesW['qty']);
                $xVal++;
            }
            // calculate par if appropriate
            if (count($points) > 0 && count($points) > $json['minPoints']) {
                $default = false;
                $coeff = Stats::exponentialFit($points);
                $par = exp($coeff->a) * exp($coeff->b * count($points));
                $par *= (1 + $json['loss']);
            }

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
                if (!$default) {
                    $i->par($par);
                    $i->save();
                }
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
        $dbc->commitTransaction();
    }
}

