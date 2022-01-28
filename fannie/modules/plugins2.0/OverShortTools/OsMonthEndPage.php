<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class OsMonthEndPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;

    protected $header = 'Month End Counts';
    protected $title = 'Month End Counts';

    protected function get_id_handler()
    {
        $ret = array(
            'registers' => array(
                '0.01' => 0,
                '0.05' => 0,
                '0.10' => 0,
                '0.25' => 0,
                'Junk' => 0,
                '1.00' => 0,
                '5.00' => 0,
                '10.00' => 0,
                '20.00' => 0,
            ),
            'change' => array(
                '0.01' => 0,
                '0.05' => 0,
                '0.10' => 0,
                '0.25' => 0,
                'Junk' => 0,
            ),
            'safe' => array(
                '0.01' => 0,
                '0.05' => 0,
                '0.10' => 0,
                '0.25' => 0,
                'Junk' => 0,
                '1.00' => 0,
                '5.00' => 0,
                '10.00' => 0,
                '20.00' => 0,
            ),
            'drops' => array(
                array('date'=>'', 'count'=>''),
                array('date'=>'', 'count'=>''),
                array('date'=>'', 'count'=>''),
                array('date'=>'', 'count'=>''),
                array('date'=>'', 'count'=>''),
                array('date'=>'', 'count'=>''),
                array('date'=>'', 'count'=>''),
            ),
            'atm' => array(
                'self' => 0,
                'safe' => 0,
            ),
        );

        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['OverShortDatabase']);
        $model = new MonthEndCountsModel($dbc);
        $model->month(FormLib::get('month'));
        $model->year(FormLib::get('year'));
        $model->store(FormLib::get('store'));
        if ($model->load()) {
            $ret = json_decode($model->data(), true);
        }

        echo json_encode($ret);

        return false;
    }

    protected function post_id_handler()
    {
        list($month, $year, $store) = explode(':', $this->id);
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['OverShortDatabase']);
        $model = new MonthEndCountsModel($dbc);
        $model->month($month);
        $model->year($year);
        $model->store($store);
        $model->data(FormLib::get('json'));
        $model->save();

        return false;
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker();
        $months = '<option value="">Select month...</option>';
        for ($i=1; $i<=12; $i++) {
            $months .= sprintf('<option value="%d">%s</option>', $i, date('F', mktime(0,0,0,$i,1,2000)));
        }
        $this->addScript('js/monthEnd.js');

        return <<<HTML
<p>
    <div class="form-inline">
        <form id="lookupForm" onsubmit="monthEnd.getDate(); return false;">
        <select name="month" id="month" class="form-control">{$months}</select>
        <input type="text" class="form-control" placeholder="Year" name="year" id="year" />
        {$stores['html']}
        <button type="submit" class="btn btn-default">Go</button>
    </form>
    </div>
</p>
<p>
    <table class="table table-striped table-bordered">
        <tr><th>Open Safe</th><th>Registers</th><th>Extra Change</th><th>Safe</th><th>Total</th></tr>
        <tr>
            <td>$0.01</td>
            <td><input id="registers0.01" class="registers form-control input-sm" value="" /></td>
            <td><input id="extra0.01" class="extra form-control input-sm" value="" /></td>
            <td><input id="safe0.01" class="safe form-control input-sm" value="" /></td>
            <td><input id="total0.01" class="data-field form-control input-sm" value="" /></td>
        </tr>
        <tr>
            <td>$0.05</td>
            <td><input id="registers0.05" class="registers form-control input-sm" value="" /></td>
            <td><input id="extra0.05" class="extra form-control input-sm" value="" /></td>
            <td><input id="safe0.05" class="safe form-control input-sm" value="" /></td>
            <td><input id="total0.05" class="data-field form-control input-sm" value="" /></td>
        </tr>
        <tr>
            <td>$0.10</td>
            <td><input id="registers0.10" class="registers form-control input-sm" value="" /></td>
            <td><input id="extra0.10" class="extra form-control input-sm" value="" /></td>
            <td><input id="safe0.10" class="safe form-control input-sm" value="" /></td>
            <td><input id="total0.10" class="data-field form-control input-sm" value="" /></td>
        </tr>
        <tr>
            <td>$0.25</td>
            <td><input id="registers0.25" class="registers form-control input-sm" value="" /></td>
            <td><input id="extra0.25" class="extra form-control input-sm" value="" /></td>
            <td><input id="safe0.25" class="safe form-control input-sm" value="" /></td>
            <td><input id="total0.25" class="data-field form-control input-sm" value="" /></td>
        </tr>
        <tr>
            <td>-</td>
            <td><input id="registersJunk" class="registers form-control input-sm" value="" /></td>
            <td><input id="extraJunk" class="extra form-control input-sm" value="" /></td>
            <td><input id="safeJunk" class="safe form-control input-sm" value="" /></td>
            <td><input id="totalJunk" class="data-field form-control input-sm" value="" /></td>
        </tr>
        <tr>
            <td>$1.00</td>
            <td><input id="registers1.00" class="registers form-control input-sm" value="" /></td>
            <td>&nbsp;</td>
            <td><input id="safe1.00" class="safe form-control input-sm" value="" /></td>
            <td><input id="total1.00" class="data-field form-control input-sm" value="" /></td>
        </tr>
        <tr>
            <td>$5.00</td>
            <td><input id="registers5.00" class="registers form-control input-sm" value="" /></td>
            <td>&nbsp;</td>
            <td><input id="safe5.00" class="safe form-control input-sm" value="" /></td>
            <td><input id="total5.00" class="data-field form-control input-sm" value="" /></td>
        </tr>
        <tr>
            <td>$10.00</td>
            <td><input id="registers10.00" class="registers form-control input-sm" value="" /></td>
            <td>&nbsp;</td>
            <td><input id="safe10.00" class="safe form-control input-sm" value="" /></td>
            <td><input id="total10.00" class="data-field form-control input-sm" value="" /></td>
        </tr>
        <tr>
            <td>$20.00</td>
            <td><input id="registers20.00" class="registers form-control input-sm" value="" /></td>
            <td>&nbsp;</td>
            <td><input id="safe20.00" class="safe form-control input-sm" value="" /></td>
            <td><input id="total20.00" class="data-field form-control input-sm" value="" /></td>
        </tr>
    </table>
    <br />
    <table class="table table-striped table-bordered">
        <tr><th>Drop Totals</th><th>-</th></tr>
        <tr>
            <td><input type="text" class="date-field data-field form-control input-sm" value="" id="date1" />
            <td><input type="text" class="drop-field form-control input-sm" value="" id="drop1" />
        </tr>
        <tr>
            <td><input type="text" class="date-field data-field form-control input-sm" value="" id="date2" />
            <td><input type="text" class="drop-field form-control input-sm" value="" id="drop2" />
        </tr>
        <tr>
            <td><input type="text" class="date-field data-field form-control input-sm" value="" id="date3" />
            <td><input type="text" class="drop-field form-control input-sm" value="" id="drop3" />
        </tr>
        <tr>
            <td><input type="text" class="date-field data-field form-control input-sm" value="" id="date4" />
            <td><input type="text" class="drop-field form-control input-sm" value="" id="drop4" />
        </tr>
        <tr>
            <td><input type="text" class="date-field data-field form-control input-sm" value="" id="date5" />
            <td><input type="text" class="drop-field form-control input-sm" value="" id="drop5" />
        </tr>
        <tr>
            <td><input type="text" class="date-field data-field form-control input-sm" value="" id="date6" />
            <td><input type="text" class="drop-field form-control input-sm" value="" id="drop6" />
        </tr>
        <tr>
            <td><input type="text" class="date-field data-field form-control input-sm" value="" id="date7" />
            <td><input type="text" class="drop-field form-control input-sm" value="" id="drop7" />
        </tr>
    </table>
    <br />
    <table class="table table-striped table-bordered">
        <tr>
            <td>ATM</td>
            <td><input type="text" class="data-field input-sm form-control" value="" id="atmself" /></td>
        </tr>
        <tr>
            <td>ATM (Safe)</td>
            <td><input type="text" class="data-field input-sm form-control" value="" id="atmsafe" /></td>
        </tr>
        <tr>
            <td>ATM (Total)</td>
            <td><input type="text" class="data-field input-sm form-control" value="" id="atmtotal" /></td>
        </tr>
    </table>
</p>
<p>
    <button type="button" class="btn btn-default btn-core" onclick="monthEnd.save();">Save</button>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

