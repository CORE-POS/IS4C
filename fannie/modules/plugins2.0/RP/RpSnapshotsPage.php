<?php

include(__DIR__ . '/../../../config.php');

if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpSnapshotsPage extends FannieReportPage
{
    protected $header = 'View Snapshots';
    protected $title = 'View Snapshots';
    protected $required_fields = array('id');
    protected $report_headers = array('LC', 'Item', 'On Hand 1', 'On Hand 2', 'Par', 'Order Amount');

    public function fetch_report_data()
    {
        $getP = $this->connection->prepare("SELECT data FROM RpSnapshots WHERE rpSnapshotID=?");
        $json = $this->connection->getValue($getP, array($this->form->id));
        $data = json_decode($json, true);
        if (FormLib::get('raw')) {
            echo '<pre>';
            echo json_encode($data, JSON_PRETTY_PRINT);
            echo '</pre><br />';
        }

        $days = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
        echo "Days selected: ";
        for ($i=0; $i < count($data['days']); $i++) {
            if ($data['days'][$i]) {
                echo $days[$i] . ' ';
            }
        }

        $candidates = array(
            count($data['pars']),
            count($data['onHand1']),
            count($data['onHand2']),
            count($data['orderAmt']),
        );

        $index = array();
        if (count($data['pars']) == max($candidates)) {
            $index = array_keys($data['pars']);
        } elseif (count($data['onHand1']) == max($candidates)) {
            $index = array_map(function ($i) { return str_replace('onHand1', '', $i); }, array_keys($data['onHand1']));
        } elseif (count($data['onHand2']) == max($candidates)) {
            $index = array_map(function ($i) { return str_replace('onHand2', '', $i); }, array_keys($data['onHand2']));
        } elseif (count($data['orderAmt']) == max($candidates)) {
            $index = array_map(function ($i) { return str_replace('orderAmt', '', $i); }, array_keys($data['orderAmt']));
        }

        $ret = array();
        $nameP = $this->connection->prepare("SELECT likeCodeDesc FROM likeCodes WHERE likeCode=?");
        foreach ($index as $lc) {
            $name = $this->connection->getValue($nameP, array(str_replace('LC', '', $lc)));
            $record = array(
                $lc,
                $name,
                (isset($data['onHand1']['onHand1' . $lc]) ? $data['onHand1']['onHand1' . $lc] : 0),
                (isset($data['onHand2']['onHand2' . $lc]) ? $data['onHand2']['onHand2' . $lc] : 0),
                (isset($data['pars'][$lc]) ? $data['pars'][$lc] : 0),
                (isset($data['orderAmt']['orderAmt' . $lc]) ? $data['orderAmt']['orderAmt' . $lc] : 0),
            );
            $ret[] = $record;
        }

        return $ret;
    }

    public function form_content()
    {
        $res = $this->connection->query("SELECT s.rpSnapshotID, u.name, s.tdate
            FROM RpSnapshots AS s
                LEFT JOIN Users as u ON s.userID=u.uid
            WHERE tdate >= '2023-02-15'
            ORDER BY tdate DESC");
        $opts = '';
        while ($row = $this->connection->fetchRow($res)) {
            $opts .= sprintf('<option value="%d">%s %s</option>',
                $row['rpSnapshotID'], $row['tdate'], $row['name']);
        }

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Select Snapshot</label>
        <select class="form-control" name="id">
            {$opts}
        </select>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="raw" value="1" /> Include raw data</label>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">View</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

