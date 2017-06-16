<?php

use COREPOS\Fannie\API\lib\Store;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ParsPage extends FannieRESTfulPage
{
    private $algorithms = array(
        'COREPOS-Fannie-API-data-ordering-ExponentialSmoothing',
        'COREPOS-Fannie-API-data-ordering-ExponentialLeastSquares',
    );

    protected $header = 'Par Algorithm';
    protected $title = 'Par Algorithm';
    public $discoverable = false;

    public function preprocess()
    {
        $this->addRoute(
            'get<disable><store>',
            'get<recalc><store>'
        );

        return parent::preprocess();
    }

    protected function get_disable_store_handler()
    {
        $model = new ParAlgorithmsModel($this->connection);
        $model->vendorID($this->disable);
        $model->storeID($this->store);
        $model->deptID(0);
        $model->delete();

        return 'ParsPage.php?id=' . $this->id . '&store=' . $this->store;
    }

    protected function get_recalc_store_handler()
    {
        list($algo, $json) = $this->getAlgo($this->recalc, $this->store, 0);
        $algo->updatePars($this->connection, $this->recalc, 0, $this->store, $json);

        return true;
    }

    protected function get_recalc_store_view()
    {
        $this->id = $this->recalc;
        return '<div class="alert alert-success">Pars adjusted</div>' . $this->get_id_view();
    }

    protected function post_id_handler()
    {
        $store = FormLib::get('store');
        list($algo,) = $this->getAlgo($this->id, $store, 0);

        $model = new ParAlgorithmsModel($this->connection);
        $model->vendorID($this->id);
        $model->storeID($store);
        $model->deptID(0);
        $model->algorithm(FormLib::get('algo'));
        $algo->saveParams($this->form, $model);
        //var_dump($_POST); exit;

        return 'ParsPage.php?id=' . $this->id . '&store=' . $store;
    }

    protected function getAlgo($vendorID, $storeID, $deptID)
    {
        $model = new ParAlgorithmsModel($this->connection);
        $model->vendorID($vendorID);
        $model->storeID($storeID);
        $model->deptID($deptID);
        $model->load();
        $algo = ($model->algorithm() && class_exists($model->algorithm())) ? $model->algorithm() : $this->algorithms[0];
        if (FormLib::get('algo', false) !== false) {
            $algo = str_replace('-', '\\', FormLib::get('algo'));
        }
        $algo = str_replace('-', '\\', $algo);
        $obj = new $algo();
        $json = json_decode($model->parameters(), true);
        if (!is_array($json)) {
            $json = array();
        }

        return array($obj, $json);
    }

    public function get_id_view()
    {
        $store = FormLib::get('store', false);
        if (!$store) {
            $store = Store::getIdByIp();
        }
        $deptID = FormLib::get('dept', 0);
        list($algo, $json) = $this->getAlgo($this->id, $store, $deptID);
        $enabled = $json !== array();
        $extras = '<div class="alert alert-info">Not enabled</div>';
        if ($enabled) {
            $extras = sprintf('<div class="form-group">
                <a class="btn btn-default" href="ParsPage.php?recalc=%d&store=%d">Run Pars Now</a>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <a href="ParsPage.php?disable=%d&store=%d" class="btn btn-default btn-danger">Disable</a>
                </div>',
                $this->id, $store, $this->id, $store);
        }
        $picker = FormLib::storePicker();
        $rendered = $algo->renderParams($json);
        $picker['html'] = str_replace('<select', "<select onchange=\"window.location='ParsPage.php?id={$this->id}&store='+this.value;\" ", $picker['html']);
        $vendor = $this->connection->prepare("SELECT vendorName FROM vendors WHERE vendorID=?");
        $vendor = $this->connection->getValue($vendor, array($this->id));

        $deptP = $this->connection->prepare('
            SELECT d.dept_no, d.dept_name
            FROM departments AS d
                INNER JOIN products AS p ON d.dept_no=p.department
            WHERE p.default_vendor_id=?
                AND p.store_id=?
            GROUP BY d.dept_no, d.dept_name
            ORDER BY d.dept_no');
        $deptR = $this->connection->execute($deptP, array($this->id, $store));
        $deptSelect = '<select name="dept" class="form-control" id="dept"><option value="0">All Departments</option>';
        while ($deptW = $this->connection->fetchRow($deptR)) {
            $deptSelect .= sprintf('<option %s value="%d">%d %s</option>',
                ($dept == $deptW['dept_no'] ? 'selected' : ''),
                $deptW['dept_no'], $deptW['dept_no'], $deptW['dept_name']);
        }
        $deptSelect .= '</select>';

        $algoName = str_replace('\\', '-', get_class($algo));
        $algoSelect = '<select name="algo" class="form-control" 
            onchange="window.location=\'ParsPage.php?id=' . $this->id . '&store=' . $store . '&algo=\'+this.value;">';
        foreach ($this->algorithms as $a) {
            $algoSelect .= sprintf('<option %s>%s</option>', ($algoName == $a ? 'selected' : ''), $a);
        }
        $algoSelect .= '</select>';

        return <<<HTML
<form method="post">
    <h3>{$vendor}</h3>
    <input type="hidden" name="id" value="{$this->id}" />
    <div class="form-group">
        <label>Store</label>
        {$picker['html']}
    </div>
    <div class="form-group">
        <label>Department</label>
        {$deptSelect}
    </div>
    <div class="form-group">
        <label>Algorithm</label>
        {$algoSelect}
    </div>
    {$rendered}
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Save</button>
    </div>
    {$extras}
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

