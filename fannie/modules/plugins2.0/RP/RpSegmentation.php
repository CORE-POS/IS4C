<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpSegmentation extends FannieRESTfulPage
{
    protected $header = 'RP Segmentation';
    protected $title = 'RP Segmentation';

    public function preprocess()
    {
        $this->addRoute('get<segID><store>', 'post<segID><store>');

        return parent::preprocess();
    }

    protected function post_segID_store_handler()
    {
        $json = array('err' => false, 'msg' => '');

        $days = FormLib::get('day', array());
        $segment = array();
        foreach (array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun') as $i => $day) {
            $segment[$day] = isset($days[$i]) ? $days[$i] / 100 : 0;
        }
        $sales = FormLib::get('sales');
        $sales = str_replace(',', '', $sales);
        $retain = FormLib::get('retention');

        $existsP = $this->connection->prepare("SELECT rpSegmentID FROM RpSegments WHERE storeID=? AND startDate=?");
        $exists = $this->connection->getValue($existsP, array($this->store, $this->segID));
        if ($exists) {
            $prep = $this->connection->prepare("UPDATE RpSegments SET sales=?, retention=?, segmentation=? WHERE rpSegmentID=?");
            $saved = $this->connection->execute($prep, array($sales, $retain, json_encode($segment), $exists));
        } else {
            $prep = $this->connection->prepare("INSERT INTO RpSegments
                (storeID, startDate, sales, retention, segmentation) VALUES (?, ?, ?, ?, ?)");
            $saved = $this->connection->execute($prep, array($this->store, $this->segID, $sales, $retain, json_encode($segment)));
        }

        $json['err'] = $saved ? false : true;
        echo json_encode($json);

        return false;
    }

    protected function get_segID_store_handler()
    {
        $ts = strtotime($this->segID);
        if ($ts === false || date('N', $ts) != 1) {
            echo '<div class="alert alert-danger">Not a valid week</div>';
            return false;
        }

        $prep = $this->connection->prepare("SELECT * FROM RpSegments WHERE storeID=? AND startDate=?");
        $row = $this->connection->getRow($prep, array($this->store, $this->segID));
        $plan = 0;
        $retain = 60;
        $json = array();
        if ($row) {
            $plan = $row['sales'];
            $retain = $row['retention'];
            $json = json_decode($row['segmentation'], true);
        }

        $ret = '<p><table class="table table-bordered table-striped small">';
        $ret .= sprintf('<tr><th>Sales</th>
            <th><input type="text" name="sales" value="%s" class="form-control" /></th></tr>',
            number_format($plan));
        $ret .= sprintf('<tr><th>Retention</th><th><div class="input-group">
            <input type="text" name="retention" value="%s" class="form-control" />
            <span class="input-group-addon">%%</span></th></tr>',
            number_format($retain));
        $sum = 0;
        foreach (array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun') as $day) {
            $ret .= sprintf('<tr><td>%s</td>
                <td><div class="input-group">
                    <input type="text" name="day[]" class="form-control plan-day" value="%.2f"
                        style="z-index: 0;" onchange="reSumPlan();" />
                    <span class="input-group input-group-addon">%%</span>
                </div></td></tr>',
                $day,
                isset($json[$day]) ? $json[$day] * 100 : 0);
            $sum += isset($json[$day]) ? $json[$day] : 0;
        }
        $ret .= sprintf('<tr><td></td><td id="sumPercents">%.2f%%</td></tr>', $sum*100);
        $ret .= '</table></p>';
        $ret .= '<p><button type="submit" class="btn btn-default">Save</button></p>';
        $ret .= '<p><textarea id="copyPaste" placeholder="paste here" onchange="
            var arr = $(this).val().split(\'\n\');
            console.log(arr);
            $(\'input.plan-day\').each(function () {
                $(this).val(arr.shift().replace(\'%\',\'\'));
                $(this).trigger(\'change\');
            });
            "></textarea></p>';

        echo $ret;

        return false;
    }

    protected function get_id_handler()
    {
        $prep = $this->connection->prepare("
            SELECT *
            FROM " . FannieDB::fqn('SuperWeeklySales', 'plugin:WarehouseDatabase') . "
            WHERE storeID=?
                AND startDate=?
                AND superID=6");
        $row = $this->connection->getRow($prep, array(FormLib::get('store'), $this->id));
        if (!$row) {
            echo '<div class="alert alert-danger">Data not found</div>';
            return false;
        }

        $ret = '<table class="table table-bordered table-striped small">';
        $ret .= '<tr><th>Sales</th><th>All</th><th>$' . number_format($row['total']) . '</th></tr>';
        $json = json_decode($row['segmentation'], true);
        foreach (array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun') as $day) {
            $ret .= sprintf('<tr><td>%s</td><td>%.2f%%</td><td>$%s</td></tr>',
                $day, $json[$day] * 100, number_format($row['total'] * $json[$day]));
        }
        $ret .= '</table>';

        echo $ret;

        return false;
    }

    protected function get_view()
    {
        $backLink = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'RpDirectPage.php')
            ? 'RpDirectPage.php'
            : 'RpOrderPage.php';
        $store = FormLib::get('store');
        if (!$store) {
            $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        $sSelect = FormLib::storePicker();
        $sSelect['html'] = str_replace('<select', '<select onchange="location=\'RpSegmentation.php?store=\' + this.value;"', $sSelect['html']);

        $segs = '';
        $prep = $this->connection->prepare("SELECT * FROM RpSegments WHERE storeID=? ORDER BY startDate DESC");
        $res = $this->connection->getAllRows($prep, array($store));
        $segID = FormLib::get('segID', false);
        $cur = false;
        foreach ($res as $row) {
            $segs .= sprintf('<option %s value="%d">%s</option>',
                ($row['rpSegmentID'] == $segID ? 'selected' : ''),
                $row['rpSegmentID'], $row['startDate']);
            if ($row['rpSegmentID'] == $segID) {
                $cur = $row;
            } elseif ($cur === false) {
                $cur = $row;
            }
        }

        return <<<HTML
<div class="row">
    <div class="col-sm-5">
    <p>
        <h3>Plan</h3>
        <div id="msgArea"></div>
        <form id="segForm" onsubmit="saveSegment(); return false;">
        <label>Store:</label> {$sSelect['html']}
        <label>Week of:</label>
        <input type="text" class="form-control date-field" name="segID"
            onchange="getPlan(this.value, {$store});" />
        <div id="segFields"></div>
        </form>
    </p>
    </div>
    <div class="col-sm-5">
    <p>
        <h3>Actual</h3>
        <label>Week of:</label>
        <input type="text" class="form-control date-field" 
            onchange="getHistory(this.value, {$store});" />
        <div id="historyDiv"></div>
    </p>
    <p>
        <a href="{$backLink}" class="btn btn-default">Back to Order Guide</a>
    </p>
    </div>
</div>
HTML;
    }

    protected function javascriptContent()
    {
        return <<<JAVASCRIPT
function getPlan(start, store) {
    var dstr = 'segID=' + start + '&store=' + store;
    $.ajax({
        type: 'get',
        data: dstr
    }).done(function (resp) {
        $('#segFields').html(resp);
    });
}
function getHistory(start, store) {
    var dstr = 'id=' + start + '&store=' + store;
    $.ajax({
        type: 'get',
        data: dstr
    }).done(function (resp) {
        $('#historyDiv').html(resp);
    });
}
function saveSegment() {
    var dstr = $('#segForm').serialize();
    $.ajax({
        'type': 'post',
        'data': dstr,
        'dataType': 'json'
    }).done(function (resp) {
        if (resp.err) {
            showBootstrapAlert('#msgArea', 'danger', 'Error saving segment');
        } else {
            showBootstrapAlert('#msgArea', 'success', 'Saved segment');
        }
    });
}
function reSumPlan() {
    var sum = 0;
    $('input.plan-day').each(function () {
        sum += ( $(this).val() * 1 );
    });
    sum = Math.round(sum * 100) / 100;
console.log(sum);
    $('#sumPercents').html(sum + '%');
}
JAVASCRIPT;
    }
}

FannieDispatch::conditionalExec();

