<?php

use COREPOS\Fannie\Plugin\IncidentTracker\notifiers\Slack;
use COREPOS\Fannie\Plugin\IncidentTracker\notifiers\Email;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class AlertIncident extends FannieRESTfulPage
{
    protected $header = 'Alert';
    protected $title = 'Alert';
    protected $must_authenticate = true;

    public function preprocess()
    {
        $this->addRoute('get<new>', 'get<search>');
        return parent::preprocess();
    }

    protected function get_search_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['IncidentDB'] . $this->connection->sep();
        $searchP = $this->connection->prepare("
            SELECT i.*,
                COALESCE(t.incidentSubType, 'Other') AS incidentSubType,
                COALESCE(l.incidentLocation, 'Other') AS incidentLocation,
                u.name AS userName,
                s.description AS storeName
            FROM {$prefix}Incidents AS i
                LEFT JOIN {$prefix}IncidentSubTypes AS t ON i.incidentSubTypeID=t.incidentSubTypeID
                LEFT JOIN {$prefix}IncidentLocations AS l ON i.incidentLocationID=l.incidentLocationID
                LEFT JOIN Users as u ON i.uid=u.uid
                LEFT JOIN Stores AS s ON i.storeID=s.storeID
            WHERE details LIKE ? OR details LIKE ?
            ORDER BY tdate DESC");
        $args = array(
            '%' . $this->search . '%',
            '%' . str_replace(' ', '%', trim($this->search)) . '%',
        );
        $searchR = $this->connection->execute($searchP, $args);

        $ret = '
            <p><form class="form-inline" method="get">
                <a href="AlertIncident.php" class="btn btn-default">Home</a>
                |
                <input type="text" name="search" id="search" class="form-control" placeholder="search" />
                <button class="btn btn-default" type="submit">Search</button>
            </form></p>
            <table class="table table-bordered">';
        $matched = false;
        while ($row = $this->connection->fetchRow($searchR)) {
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td>
                <td><a href="?id=%d">View #%d</a></td><td>%s</td></tr>',
                $row['tdate'], $row['storeName'], $row['userName'], $row['incidentID'], $row['incidentID'], substr($row['details'], 0, 100));
            $matched = true;
        }
        $ret .= !$matched ? '<tr><td colspan="4">No matches</td></tr>' : '';
        $ret .= '</table>';
        $this->addOnloadCommand("\$('#search').focus();");

        return $ret;
    }

    protected function post_id_handler()
    {
        $uid = FannieAuth::getUID($this->current_user);
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['IncidentDB']);
        $model = new IncidentCommentsModel($this->connection);
        $model->incidentID($this->id);
        $model->userID($uid);
        $model->tdate(date('Y-m-d H:i:s'));
        $model->comment(FormLib::get('comment'));
        $model->save();

        return 'AlertIncident.php?id=' . $this->id;
    }

    protected function post_handler()
    {
        $uid = FannieAuth::getUID($this->current_user);
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['IncidentDB']);
        $model = new IncidentsModel($this->connection);

        $model->incidentTypeID(1);
        $model->incidentSubTypeID(FormLib::get('subtype'));
        $model->incidentLocationID(FormLib::get('location'));
        $model->reportedBy(FormLib::get('reported'));
        $model->tdate(date('Y-m-d H:i:s'));
        $model->police(FormLib::get('police', 0));
        $model->trespass(FormLib::get('trespass', 0));
        $model->details(FormLib::get('details'));
        $model->uid($uid);
        $model->storeID(FormLib::get('store'));

        if (!empty($_FILES['img1']['tmp_name']) && file_exists($_FILES['img1']['tmp_name'])) {
            $ext = pathinfo($_FILES['img1']['name'], PATHINFO_EXTENSION);
            $file = md5(rand());
            while (file_exists(__DIR__  . "/image/{$file}.{$ext}")) {
                $file = md5(rand());
            }
            move_uploaded_file($_FILES['img1']['tmp_name'], __DIR__ . "/image/{$file}.{$ext}");
            $model->image1($file . '.' . $ext);
        }
        if (!empty($_FILES['img2']['tmp_name']) && file_exists($_FILES['img2']['tmp_name'])) {
            $ext = pathinfo($_FILES['img2']['name'], PATHINFO_EXTENSION);
            $file = md5(rand());
            while (file_exists(__DIR__  . "/image/{$file}.{$ext}")) {
                $file = md5(rand());
            }
            move_uploaded_file($_FILES['img2']['tmp_name'], __DIR__ . "/image/{$file}.{$ext}");
            $model->image2($file . '.' . $ext);
        }
        $id = $model->save();

        $this->connection->selectDB($this->config->get('OP_DB'));
        $incident = $this->getIncident($id);
        $prefix = $settings['IncidentDB'] . $this->connection->sep();
        $res = $this->connection->query("SELECT * FROM {$prefix}IncidentNotifications WHERE incidentTypeID=1");
        while ($row = $this->connection->fetchRow($res)) {
            try {
                switch (strtolower($row['method'])) {
                    case 'slack':
                        $slack = new Slack();
                        $slack->send($incident, $row['address']);
                        break;
                    case 'email':
                        $email = new Email();
                        $email->send($incident, $row['address']);
                        break;
                }
            } catch (Exception $ex) {}
        }

        return 'AlertIncident.php?id=' . $id;
    }

    protected function getComments($id)
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['IncidentDB'] . $this->connection->sep();

        $query = "SELECT i.*,
                u.name AS userName
            FROM {$prefix}IncidentComments AS i
                LEFT JOIN Users as u ON i.userID=u.uid
            WHERE incidentID=?
            ORDER BY tdate";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($id));
        $ret = array();
        while ($row = $this->connection->fetchRow($res)) {
            $ret[] = $row;
        }

        return $ret;
    }

    protected function getIncident($id)
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['IncidentDB'] . $this->connection->sep();

        $query = "SELECT i.*,
                COALESCE(t.incidentSubType, 'Other') AS incidentSubType,
                COALESCE(l.incidentLocation, 'Other') AS incidentLocation,
                u.name AS userName,
                s.description AS storeName
            FROM {$prefix}Incidents AS i
                LEFT JOIN {$prefix}IncidentSubTypes AS t ON i.incidentSubTypeID=t.incidentSubTypeID
                LEFT JOIN {$prefix}IncidentLocations AS l ON i.incidentLocationID=l.incidentLocationID
                LEFT JOIN Users as u ON i.uid=u.uid
                LEFT JOIN Stores AS s ON i.storeID=s.storeID
            WHERE i.incidentID=?";
        $prep = $this->connection->prepare($query);
        $row = $this->connection->getRow($prep, array($id));
        $row['reportedBy'] = $row['reportedBy'] == 0 ? 'Staff' : 'Customer';
        $row['police'] = $row['police'] ? 'Yes' : 'No';
        $row['trespass'] = $row['trespass'] ? 'Yes' : 'No';

        return $row;
    }

    protected function get_id_view()
    {
        $row = $this->getIncident($this->id);
        $row['details'] = nl2br($row['details']);
        $img1 = $row['image1'] ? "<img src=\"image/{$row['image1']}\" />" : '';
        $img2 = $row['image2'] ? "<img src=\"image/{$row['image2']}\" />" : '';
        $row['details'] = preg_replace('/#(\d+)/', '<a href="?id=$1">#$1</a>', $row['details']);
        $row['details'] = preg_replace('`(http|ftp|https)://([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:/~+#-]*[\w@?^=%&/~+#-])?`',
            '<a href="$1://$2$3">$1://$2$3</a>', $row['details']);

        $comments = $this->getComments($this->id);
        $cHtml = '';
        foreach ($comments as $c) {
            $cHtml .= sprintf('<div class="panel panel-default">
                <div class="panel panel-heading">%s - %s</div>
                <div class="panel panel-body">%s</div>
                </div>',
                $c['tdate'], $c['userName'],
                nl2br($c['comment'])
            );
        }

        return <<<HTML
<p>
    <a href="AlertIncident.php" class="btn btn-default">Home</a>
</p>
<table class="table table-bordered">
<tr>
    <th>Date</th><td>{$row['tdate']}</td>
</tr>
<tr>
    <th>Store</th><td>{$row['storeName']}</td>
</tr>
<tr>
    <th>Type</th><td>{$row['incidentSubType']}</td>
</tr>
<tr>
    <th>Location</th><td>{$row['incidentLocation']}</td>
</tr>
<tr>
    <th>Reported by</th><td>{$row['reportedBy']}</td>
</tr>
<tr>
    <th>Entered by</th><td>{$row['userName']}</td>
</tr>
<tr>
    <th>Called police</th><td>{$row['police']}</td>
</tr>
<tr>
    <th>Requested trespass</th><td>{$row['trespass']}</td>
</tr>
</table>
<div class="panel panel-default">
    <div class="panel-body">
    {$row['details']}
    </div>
    <p>
        {$img1}
        {$img2}
    </p>
</div>
{$cHtml}
<form method="post">
    <input type="hidden" name="id" value="{$this->id}" />
    <div class="panel panel-default">
        <div class="panel-heading">Add a Comment</div>
        <div class="panel-body">
            <p>
            <textarea name="comment" class="form-control" rows="7"></textarea>
            </p>
            <p>
            <button type="submit" class="btn btn-default">Post Comment</button>
            </p>
        </div>
    </div>
</form>
HTML;
    }

    protected function get_new_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['IncidentDB'] . $this->connection->sep();

        $typeR = $this->connection->query("
            SELECT s.*
            FROM {$prefix}IncidentSubTypes AS s
                INNER JOIN {$prefix}IncidentSubTypeTypeMap AS m ON s.incidentSubTypeID=m.incidentSubTypeID
            WHERE m.incidentTypeID=1
            ORDER BY s.incidentSubType");
        $types = '';
        while ($typeW = $this->connection->fetchRow($typeR)) {
            $types .= sprintf('<option value="%d">%s</option>', $typeW['incidentSubTypeID'], $typeW['incidentSubType']);
        }

        $locR = $this->connection->query("
            SELECT s.*
            FROM {$prefix}IncidentLocations AS s
                INNER JOIN {$prefix}IncidentLocationTypeMap AS m ON s.incidentLocationID=m.incidentLocationID
            WHERE m.incidentTypeID=1
            ORDER BY s.incidentLocation");
        $loc = '';
        while ($typeW = $this->connection->fetchRow($locR)) {
            $loc .= sprintf('<option value="%d">%s</option>', $typeW['incidentLocationID'], $typeW['incidentLocation']);
        }

        $stores = FormLib::storePicker();

        return <<<HTML
<form method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <label>Type of Alert</label>
        <select name="subtype" required class="form-control">
            <option value="">Select One</option>
            {$types}
            <option value="-1">Other</option>
        </select>
    </div>
    <div class="form-group">
        <label>Location</label>
        <select name="location" required class="form-control">
            <option value="">Select One</option>
            {$loc}
            <option value="-1">Other</option>
        </select>
    </div>
    <div class="form-group">
        <label>Reported by</label>
        <select name="reported" required class="form-control">
            <option value="">Select One</option>
            <option value="0">Staff</option>
            <option value="1">Customer</option>
        </select>
    </div>
    <div class="form-group">
        <label>Called police
            <input type="checkbox" name="police" value="1" />
        </label>
    </div>
    <div class="form-group">
        <label>Requested trespass
            <input type="checkbox" name="trespass" value="1" />
        </label>
    </div>
    <div class="form-group">
        <label>Tales of Truculence and Tomfoolery</label>
        <textarea name="details" class="form-control" rows="10"></textarea>
    </div>
    <div class="form-group">
        <label>Image #1</label>
        <input type="file" name="img1" class="form-control" accept="image/*" />
    </div>
    <div class="form-group">
        <label>Image #2</label>
        <input type="file" name="img2" class="form-control" accept="image/*" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Save Alert</button>
    </div>
</form>
HTML;
    }

    protected function get_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $table = $settings['IncidentDB'] . $this->connection->sep();
        $query = "SELECT i.*, 
                    COALESCE(s.incidentSubType, 'Other') AS incidentSubType, 
                    u.name,
                    t.description AS storeName
                FROM {$table}Incidents AS i
                LEFT JOIN {$table}IncidentSubTypes AS s ON i.incidentSubTypeID=s.incidentSubTypeID
                LEFT JOIN Users AS u ON i.uid=u.uid
                LEFT JOIN Stores AS t ON i.storeID=t.storeID
            WHERE i.incidentTypeID=1
            ORDER BY tdate DESC";
        $query = $this->connection->addSelectLimit($query, 30);
        $res = $this->connection->query($query);
        $table = '';
        $byDay = array();
        $byCat = array();
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td><a href="?id=%d">View #%d</a><td>%s...</td></tr>',
                $row['tdate'], $row['storeName'], $row['name'], $row['incidentID'], $row['incidentID'], substr($row['details'], 0, 200));
            list($date,) = explode(' ', $row['tdate'], 2);
            if (!isset($byDay[$date])) {
                $byDay[$date] = 0;
            }
            $byDay[$date]++;
            if (!isset($byCat[$row['incidentSubType']])) {
                $byCat[$row['incidentSubType']] = 0;
            }
            $byCat[$row['incidentSubType']]++;
        }

        $start = new DateTime(min(array_keys($byDay)));
        $end = new DateTime();
        $plus = new DateInterval('P1D');
        $lineData = array();
        $lineLabels = array();
        while ($start < $end) {
            $key = $start->format('Y-m-d');
            $lineLabels[] = $key;
            $lineData[] = array('x'=>$key, 'y'=>(isset($byDay[$key]) ? $byDay[$key] : 0));
            $start = $start->add($plus);
        }
        $lineData = json_encode($lineData);
        $lineLabels = json_encode($lineLabels);

        $pie = array('data'=>array(), 'labels'=>array());
        foreach ($byCat as $key=>$val) {
            $pie['data'][] = $val;
            $pie['labels'][] = $key;
        }
        $pieData = json_encode($pie['data']);
        $pieLabels = json_encode($pie['labels']);

        $this->addScript($this->config->get('URL') . 'src/javascript/Chart.min.js');
        $this->addOnloadCommand('drawCharts();');

        return <<<HTML
<p class="form-inline">
    <form method="get" class="form-inline">
        <a href="?new=1" class="btn btn-default">New Alert</a>
        |
        <input type="text" name="search" class="form-control" placeholder="Search incidents" />
        <button type="submit" class="btn btn-default">Search</button>
    </form>
</p>
<table class="table small table-bordered">
<tr><th colspan="5" class="text-center">Recent Alerts</th></tr>
    {$table}
</table>
<div class="row">
    <div class="col-sm-5">
        <canvas id="byDay" width="300" height="300"></canvas>
    </div>
    <div class="col-sm-5">
        <canvas id="byCat" width="300" height="300"></canvas>
    </div>
</div>
<script type="text/javascript">
function drawCharts() {
    var ctx = document.getElementById('byDay').getContext('2d');
    var line = new Chart(ctx, {
        type: 'line',
        responsive: false,
        data: {
            datasets: [{
                data: {$lineData},
                fill: false,
                label: 'Alerts per Day',
                backgroundColor: "#3366cc",
                pointBackgroundColor: "#3366cc",
                pointBorderColor: "#3366cc",
                borderColor: "#3366cc"
            }],
            labels: {$lineLabels}
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        stepSize: 1
                    }
                }]
            }
        }
    });

    var ctx2 = document.getElementById('byCat').getContext('2d');
    var pie = new Chart(ctx2, {
        type: 'pie',
        responsive: false,
        data: {
            datasets: [{
                data: {$pieData},
                backgroundColor: ["#3366cc", "#dc3912", "#ff9900", "#109618", "#990099", "#0099c6", "#dd4477", "#66aa00", "#b82e2e", "#316395", "#994499", "#22aa99", "#aaaa11", "#6633cc", "#e67300", "#8b0707", "#651067", "#329262", "#5574a6", "#3b3eac"]
            }],
            labels: {$pieLabels}
        }
    });
}
</script>
HTML;
    }
}

FannieDispatch::conditionalExec();

