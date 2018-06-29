<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class ObfPlanPage extends FannieRESTfulPage 
{
    protected $title = 'OBF: Plan';
    protected $header = 'OBF: Plan';

    public $page_set = 'Plugin :: Open Book Financing';
    public $description = '[Plan Entry] sets sales goals for various periods';
    protected $lib_class = 'ObfLibV2';

    protected function post_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $model = new ObfPlansModel($this->connection);
        $model->whichDB($settings['ObfDatabaseV2']);
        $month = FormLib::get('month');
        $model->month($month);
        $year = FormLib::get('year');
        $model->year($year);

        $superP = $this->connection->prepare('SELECT superID
            FROM ' . FannieDB::fqn('ObfCategorySuperDeptMap', 'plugin:ObfDatabaseV2') . '
            WHERE obfCategoryID=?');
        $storeP = $this->connection->prepare('SELECT storeID
            FROM ' . FannieDB::fqn('ObfCategories', 'plugin:ObfDatabaseV2') . '
            WHERE obfCategoryID=?');
        $cutoff = date('Ymd', strtotime('31 days ago'));
        $salesQ = 'SELECT SUM(total) AS ttl,
            SUM(CASE WHEN s.superID=? THEN total ELSE 0 END) AS subTTL
            FROM ' . FannieDB::fqn('sumDeptSalesByDay', 'plugin:WarehouseDatabase') . ' AS d
                INNER JOIN ' . FannieDB::fqn('superdepts', 'op') . ' AS s ON d.department=s.dept_ID
            WHERE d.store_id=? 
                AND date_id >= ? ';
        $ids = FormLib::get('catID');
        $plans = FormLib::get('plan');
        for ($i=0; $i<count($ids); $i++) {
            $supers = $this->connection->getAllValues($superP, array($ids[$i]));
            $store = $this->connection->getValue($storeP, array($ids[$i]));
            $model->storeID($store);
            foreach ($supers as $superID) {
                $args = array($superID, $store, $cutoff);
                list($inStr, $args) = $this->connection->safeInClause($supers, $args);
                $prep = $this->connection->prepare($salesQ . " AND s.superID IN ({$inStr}) ");
                $sales = $this->connection->getRow($prep, $args);
                $model->superID($superID);
                $model->planGoal($plans[$i] * ($sales['subTTL'] / $sales['ttl']));
                $model->save();
            }
        }

        return "ObfPlanPage.php?year={$year}&month={$month}";
    }

    protected function get_view()
    {
        $res = $this->connection->query('
            SELECT obfCategoryID, name, storeID
            FROM ' . FannieDB::fqn('ObfCategories', 'plugin:ObfDatabaseV2') . '
            WHERE hasSales=1');
        $form = '';
        $year = FormLib::get('year');
        $month = FormLib::get('month');
        if (FormLib::get('ym')) {
            $year = substr(FormLib::get('ym'), 0, 4);
            $month = ltrim(substr(FormLib::get('ym'), -2), '0');
        }
        $superP = $this->connection->prepare('SELECT superID
            FROM ' . FannieDB::fqn('ObfCategorySuperDeptMap', 'plugin:ObfDatabaseV2') . '
            WHERE obfCategoryID=?');
        $sumQ = 'SELECT sum(planGoal) FROM ' . FannieDB::fqn('ObfPlans', 'plugin:ObfDatabaseV2') . '
            WHERE storeID=? AND month=? AND year=? AND superID IN ';
        while ($row = $this->connection->fetchRow($res)) {
            $supers = $this->connection->getAllValues($superP, array($row['obfCategoryID']));
            $args = array($row['storeID'], $month, $year);
            list($inStr, $args) = $this->connection->safeInClause($supers, $args);
            $prep = $this->connection->prepare($sumQ . "({$inStr})");
            $sum = $this->connection->getValue($prep, $args);
            $form .= sprintf('<div class="form-group">
                <label>%s</label>
                <input type="hidden" name="catID[]" value="%d" />
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" name="plan[]" value="%.2f" 
                        class="form-control" />
                </div>
                </div>',
                $row['name'], $row['obfCategoryID'], $sum);
        }

        $opts = '';
        $res = $this->connection->query('SELECT year, month
            FROM ' . FannieDB::fqn('ObfPlans', 'plugin:ObfDatabaseV2') . '
            GROUP BY year, month
            ORDER BY year, month');
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= sprintf('<option %s value="%d%02d">%d-%02d</option>',
                ($year == $row['year'] && $month == $row['month'] ? 'selected' : ''),
                $row['year'], $row['month'], $row['year'], $row['month']);
        }
        
        return <<<HTML
<p class="input-group">
    <span class="input-group-addon">Viewing</span>
    <select class="form-control" onchange="location='ObfPlanPage.php?ym='+this.value;">
        <option value="">New Entry</option>
        {$opts}
    </select>
</p>
<form method="post" action="ObfPlanPage.php">
    <div class="form-group form-inline">
        <label>Month</label>
        <input type="number" min="1" max="12" step="1" required name="month" class="form-control" 
            value="{$month}" />
        <label>Year</label>
        <input type="text" min="2000" max="2999" step="1" required name="year" class="form-control"
            value="{$year}" />
    </div>
    {$form}
    <p>
        <button type="submit" class="btn btn-default btn-core">Save</button>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <a class="btn btn-default" href="ObfIndexPageV2.php">Home</a>
    </p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

