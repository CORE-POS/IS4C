<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class OsTillCountsPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;

    protected $header = 'Daily Till Counts';
    protected $title = 'Daily Till Counts';

    protected function post_id_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['OverShortDatabase']);
        $dateID = date('Ymd', strtotime($this->id));
        $storeID = FormLib::get('store');
        $amts = FormLib::get('amt');
        $vars = FormLib::get('var');
        for ($i=0; $i<count($amts); $i++) {
            $model = new DailyTillCountsModel($dbc);
            $model->dateID($dateID);
            $model->storeID($storeID);
            $model->registerNo($i + 1);
            $model->dropAmount($amts[$i]);
            $model->variance($vars[$i]);
            $model->save();
        }

        return '<div class="alert alert-success">Saved</div>'
            . $this->get_id_view();
    }

    protected function get_id_view()
    {
        $dateID = date('Ymd', strtotime($this->id));
        $storeID = FormLib::get('store');
        $prep = $this->connection->prepare("SELECT dropAmount, variance
            FROM " . FannieDB::fqn('DailyTillCounts', 'plugin:OverShortDatabase') . "
            WHERE dateID=?
                AND storeID=?
                AND registerNo=?");
        $form = '';
        for ($i=1; $i<=6; $i++) {
            $current = $this->connection->getRow($prep, array($dateID, $storeID, $i));
            if ($current === false) {
                $current = array('dropAmount' => '', 'variance' => '');
            }
            $form .= '<b>Till #' . $i . '</b><br />';
            $form .= sprintf('<div class="form-group"><div class="input-group">
                <span class="input-group-addon">Drop Amount</span>
                <input type="text" name="amt[]" class="form-control" value="%s" />
                </div></div>', $current['dropAmount']);
            $form .= sprintf('<div class="form-group"><div class="input-group">
                <span class="input-group-addon">A.M. Variance</span>
                <input type="text" name="var[]" class="form-control" value="%s" />
                </div></div>', $current['variance']);
            $form .= '<hr />';
        }
        return <<<HTML
<form method="post" action="OsTillCountsPage.php">
    <h4>Counts for {$this->id}</h4>
    {$form}
    <div class="form-group">
        <input type="hidden" name="id" value="{$this->id}" />
        <input type="hidden" name="store" value="{$storeID}" />
        <button type="submit" class="btn btn-default">Save</button>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a href="OsTillCountsPage.php" class="btn btn-default">Back</a>
    </div>
</form>
HTML;
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="get" action="OsTillCountsPage.php">
<div class="form-group">
    <label>Date</label>
    <input type="text" name="id" class="form-control date-field" />
</div>
<div class="form-group">
    <label>Store</label>
    {$stores['html']}
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default">Continue</button>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

