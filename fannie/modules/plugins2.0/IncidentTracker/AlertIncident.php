<?php

use COREPOS\Fannie\Plugin\IncidentTracker\notifiers\Slack;
use COREPOS\Fannie\Plugin\IncidentTracker\notifiers\Email;

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('IncidentsModel')) {
    include(__DIR__ . '/models/IncidentsModel.php');
}
if (!class_exists('IncidentCommentsModel')) {
    include(__DIR__ . '/models/IncidentCommentsModel.php');
}

class AlertIncident extends FannieRESTfulPage
{
    protected $header = 'Alert';
    protected $title = 'Alert';
    protected $must_authenticate = true;

    public function preprocess()
    {
        $this->addRoute('get<new>', 'get<search>', 'post<escalate>', 'post<id><field><value>');
        return parent::preprocess();
    }

    protected function post_id_field_value_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['IncidentDB']);
        $model = new IncidentsModel($this->connection);
        $model->incidentID($this->id);
        if (trim($this->value) == '') {
            echo 'No value!';
            return false;
        }
        switch ($this->field) {
            case 'personName':
                $model->personName($this->value);
                break;
            case 'personDOB':
                $stamp = strtotime($this->value);
                $model->personDOB($stamp ? date('Y-m-d', $stamp) : null);
                break;
            case 'employees':
                $model->employees($this->value);
                break;
            case 'caseNumber':
                $model->caseNumber($this->value);
                break;
            case 'trespassStart':
                $stamp = strtotime($this->value);
                $model->trespassStart($stamp ? date('Y-m-d', $stamp) : null);
                break;
            case 'trespassEnd':
                $stamp = strtotime($this->value);
                $model->trespassEnd($stamp ? date('Y-m-d', $stamp) : null);
                break;
            case 'police':
                $model->police($this->value);
                break;
            case 'trespass':
                $model->trespass($this->value);
                break;
            default:
                echo 'Unknown';
                return false;
        }
        $model->save();
        echo 'OK';

        return false;
    }

    protected function post_escalate_handler()
    {
        $msg = "Alert needing attention\n";
        $msg .= 'http://key/git/fannie/modules/plugins2.0/IncidentTracker/AlertIncident.php?id=' . $this->escalate . "\n\n";

        try {
            $row = $this->getIncident($this->escalate);
        } catch (Exception $ex) {
            return false; // incident not found; can't escalate
        }
        $comments =  $this->getComments($this->escalate);
        
        $msg .= "Date {$row['tdate']}\n";
        $msg .= "Store {$row['storeName']}\n";
        $msg .= "Type {$row['incidentSubType']}\n";
        $msg .= "Location {$row['incidentLocation']}\n";
        $msg .= "Reported by {$row['reportedBy']}\n";
        $msg .= "Summary: " . $row['details'] . "\n\n";
        foreach ($comments as $c) {
            if ($c['comment']) {
                $msg .= "Additional comment by {$c['userName']}\n";
                $msg .= $c['tdate'] . "\n";
                $msg .= $c['comment'] . "\n\n";
            }
        }

        $subject = 'Alert Needing Attention';
        $to = 'andy@wholefoods.coop,sbroome@wholefoods.coop,michael@wholefoods.coop,shannigan@wholefoods.coop';

        $upP = $this->connection->prepare("
            UPDATE " . FannieDB::fqn('Incidents', 'plugin:IncidentDB') . "
            SET escalate=1
            WHERE incidentID=?");
        $this->connection->execute($upP, array($this->escalate));

        mail($to, $subject, $msg, "From: alerts@wholefoods.coop\r\n");

        return false;
    }

    protected function get_search_view()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $prefix = $settings['IncidentDB'] . $this->connection->sep();
        $commentP = $this->connection->prepare("
            SELECT incidentID
            FROM {$prefix}IncidentComments
            WHERE comment LIKE ? OR comment LIKE ?");
        $cArgs = array(
            '%' . $this->search . '%',
            '%' . str_replace(' ', '%', trim($this->search)) . '%',
        );
        $cIDs = $this->connection->getAllValues($commentP, $cArgs);
        list($inStr, $args) = $this->connection->safeInClause($cIDs);
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
            WHERE (i.incidentID IN ({$inStr}) OR details LIKE ? OR details LIKE ? OR personName LIKE ?)
                AND i.deleted=0
            ORDER BY tdate DESC");
        $args[] = '%' . $this->search . '%';
        $args[] = '%' . str_replace(' ', '%', trim($this->search)) . '%';
        $args[] = '%' . $this->search . '%';
        $searchR = $this->connection->execute($searchP, $args);

        $ret = '
            <p><form class="form-inline" method="get">
                <a href="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" class="btn btn-default">Home</a>
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
        $model->save();

        $modP = $this->connection->prepare('UPDATE Incidents SET modified=? WHERE incidentID=?');
        $this->connection->execute($modP, array(date('Y-m-d H:i:s'), $this->id));

        return 'AlertIncident.php?id=' . $this->id;
    }

    protected function delete_id_handler()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $this->connection->selectDB($settings['IncidentDB']);
        $model = new IncidentsModel($this->connection);
        $model->incidentID($this->id);
        $model->deleted($this->form->undo ? 0 : 1);
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
        $model->modified(date('Y-m-d H:i:s'));
        $model->employees(FormLib::get('staff',''));
        $model->personName(FormLib::get('name',''));
        $dob = strtotime(FormLib::get('dob'));
        if ($dob) {
            $model->personDOB(date('Y-m-d', $dob));
        }
        $model->police(FormLib::get('police', 0));
        $model->caseNumber(FormLib::get('case',''));
        $model->trespass(FormLib::get('trespass', 0));
        $tStart = strtotime(FormLib::get('tStart'));
        if ($tStart) {
            $model->trespassStart(date('Y-m-d', $tStart));
        }
        $tEnd = strtotime(FormLib::get('tEnd'));
        if ($tEnd) {
            $model->trespassStart(date('Y-m-d', $tEnd));
        }
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
        try {
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
        } catch (Exception $ex) {
            // something went wrong here and the new incident doesn't exist
            // letting the redirect happen is OK since it'll show an error
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
            ORDER BY tdate DESC";
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
        if ($row === false) {
            throw new Exception('incident not found: ' . $id);
        }
        $row['reportedBy'] = $row['reportedBy'] == 0 ? 'Staff' : 'Customer';
        $row['police'] = $row['police'] ? 'Yes' : 'No';
        $row['trespass'] = $row['trespass'] ? 'Yes' : 'No';

        return $row;
    }

    private function getPrevNext($modified)
    {
        $prevQ = $this->connection->addSelectLimit("
            SELECT incidentID
            FROM " . FannieDB::fqn('Incidents', 'plugin:IncidentDB') . "
            WHERE modified > ?
            ORDER BY modified", 1); 
        $prevP = $this->connection->prepare($prevQ);
        $prev = $this->connection->getValue($prevP, array($modified));

        $nextQ = $this->connection->addSelectLimit("
            SELECT incidentID
            FROM " . FannieDB::fqn('Incidents', 'plugin:IncidentDB') . "
            WHERE modified < ?
            ORDER BY modified DESC", 1); 
        $nextP = $this->connection->prepare($nextQ);
        $next = $this->connection->getValue($nextP, array($modified));

        $next = sprintf('<a href="AlertIncident.php?id=%d" class="btn btn-default" %s>Next</a>',
            $next, (!$next ? 'disabled' : ''));
        $prev = sprintf('<a href="AlertIncident.php?id=%d" class="btn btn-default" %s>Prev</a>',
            $prev, (!$prev ? 'disabled' : ''));

        return array($prev, $next);
    }

    protected function get_id_handler()
    {
        $this->header .= ' #' . $this->id;

        return true;
    }

    protected function get_id_view()
    {
        try {
            $row = $this->getIncident($this->id);
        } catch (Exception $ex) {
            return '<div class="alert alert-danger">Unknown alert</div>';
        }
        list($prev, $next) = $this->getPrevNext($row['modified']);
        $row['details'] = nl2br($row['details']);
        $img1 = $row['image1'] ? "<img style=\"max-width: 95%;\" src=\"image/{$row['image1']}\" />" : '';
        $img2 = $row['image2'] ? "<img style=\"max-width: 95%;\" src=\"image/{$row['image2']}\" />" : '';
        $row['details'] = preg_replace('/#(\d+)/', '<a href="?id=$1">#$1</a>', $row['details']);
        $row['details'] = preg_replace('`(http|ftp|https)://([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:/~+#-]*[\w@?^=%&/~+#-])?`',
            '<a href="$1://$2$3">$1://$2$3</a>', $row['details']);

        $comments = $this->getComments($this->id);
        $cHtml = '';
        foreach ($comments as $c) {
            $c['comment'] = preg_replace('/#(\d+)/', '<a href="?id=$1">#$1</a>', $c['comment']);
            $c['comment'] = preg_replace('`(http|ftp|https)://([\w_-]+(?:(?:\.[\w_-]+)+))([\w.,@?^=%&:/~+#-]*[\w@?^=%&/~+#-])?`',
                '<a href="$1://$2$3">$1://$2$3</a>', $c['comment']);
            $cmg1 = $c['image1'] ? "<img style=\"max-width: 95%;\" src=\"image/{$c['image1']}\" />" : '';
            $cmg2 = $c['image2'] ? "<img style=\"max-width: 95%;\" src=\"image/{$c['image2']}\" />" : '';
            $cHtml .= sprintf('<div class="panel panel-default">
                <div class="panel panel-heading">%s - %s</div>
                <div class="panel panel-body">%s
                <br />%s%s</div>
                </div>',
                $c['tdate'], $c['userName'],
                nl2br($c['comment']),
                $cmg1, $cmg2
            );
        }

        $deleteURL = "?_method=delete&id={$this->id}&undo=" . ($row['deleted'] ? '1' : '0');
        $deleteVerb = $row['deleted'] ? 'Undelete' : 'Delete';
        $escalated = $row['escalate'] ? 'checked' : '';
        $case = '';
        if ($row['police'] == 'Yes') {
            $case = sprintf('<tr><th>Case #</th><td><input type="text" class="form-control input-sm" value="%s" 
                onchange="saveField(\'caseNumber\', this.value, %d);" /></td></tr>',
                $row['caseNumber'], $this->id);
            $row['police'] = '<select onchange="saveReload(\'police\', this.value, ' . $this->id . ');"
                class="form-control input-sm">
                    <option value="1" selected>Yes</option>
                    <option value="0">No</option>
                </select>';
        } else {
            $row['police'] = '<select onchange="saveReload(\'police\', this.value, ' . $this->id . ');"
                class="form-control input-sm">
                    <option value="1">Yes</option>
                    <option value="0" selected>No</option>
                </select>';
        }
        $tpass = '';
        if ($row['trespass'] == 'Yes') {
            $tpass = sprintf('<tr><th>Starts</th><td><input type="text" class="form-control input-sm date-field" value="%s" 
                onchange="saveField(\'trespassStart\', this.value, %d);" /></td></tr>',
                $row['trespassStart'], $this->id);
            $tpass .= sprintf('<tr><th>Ends</th><td><input type="text" class="form-control input-sm date-field" value="%s" 
                onchange="saveField(\'trespassEnd\', this.value, %d);" /></td></tr>',
                $row['trespassEnd'], $this->id);
            $row['trespass'] = '<select onchange="saveReload(\'trespass\', this.value, ' . $this->id . ');"
                class="form-control input-sm">
                    <option value="1" selected>Yes</option>
                    <option value="0">No</option>
                </select>';
        } else {
            $row['trespass'] = '<select onchange="saveReload(\'trespass\', this.value, ' . $this->id . ');"
                class="form-control input-sm">
                    <option value="1">Yes</option>
                    <option value="0" selected>No</option>
                </select>';
        }

        return <<<HTML
<script type="text/javascript">
function saveField(field, newVal, commentID) {
    var dstr = 'id='+commentID;
    dstr += '&field=' + field;
    dstr += '&value=' + encodeURIComponent(newVal);
    $.ajax({
        url: 'AlertIncident.php',
        data: dstr,
        type: 'post'
    });
}
function saveReload(field, newVal, commentID) {
    var dstr = 'id='+commentID;
    dstr += '&field=' + field;
    dstr += '&value=' + encodeURIComponent(newVal);
    $.ajax({
        url: 'AlertIncident.php',
        data: dstr,
        type: 'post'
    }).done(function (resp) {
        location.reload();
    });
}
</script>
<p>
    <a href="AlertIncident.php" class="btn btn-default">Home</a>
    {$prev}
    {$next}
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
    <th>Staff involved</th><td><input type="text" class="form-control input-sm" value="{$row['employees']}" 
        onchange="saveField('employees', this.value, {$this->id});" /></td>
</tr>
<tr>
    <th>Name</th><td><input type="text" class="form-control input-sm" value="{$row['personName']}" 
        onchange="saveField('personName', this.value, {$this->id});" /></td>
</tr>
<tr>
    <th>DoB</th><td><input type="text" class="form-control input-sm date-field" value="{$row['personDOB']}" 
        onchange="saveField('personDOB', this.value, {$this->id});" /></td>
</tr>
<tr>
    <th>Called police</th><td>{$row['police']}</td>
</tr>
{$case}
<tr>
    <th>Requested trespass</th><td>{$row['trespass']}</td>
</tr>
{$tpass}
<tr>
    <th>Escalate to Store Managers</th>
    <td><input type="checkbox" onchange="\$.ajax({type:'post',data:'escalate={$this->id}'});" {$escalated} /></td>
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
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{$this->id}" />
    <div class="panel panel-default">
        <div class="panel-heading">Add a Comment</div>
        <div class="panel-body">
            <p>
            <textarea name="comment" class="form-control" rows="7"></textarea>
            </p>
            <div class="form-group">
                <label>Image #1</label>
                <input type="file" name="img1" class="form-control" accept="image/*" />
            </div>
            <div class="form-group">
                <label>Image #2</label>
                <input type="file" name="img2" class="form-control" accept="image/*" />
            </div>
            <p>
            <button type="submit" class="btn btn-default">Post Comment</button>
            </p>
        </div>
    </div>
</form>
<p>
    <a href="{$deleteURL}" class="btn btn-danger" onclick="return confirm('{$deleteVerb} this entry?');">{$deleteVerb} this entry</a>
</p>
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
<p><a href="AlertIncident.php" class="btn btn-default">Back to All Alerts</a></p>
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
        <label>Staff involved</label>
        <input type="text" class="form-control" name="staff" value="" />
    </div>
    <div class="form-group">
        <label>Name</label>
        <input type="text" class="form-control" name="name" value="" />
    </div>
    <div class="form-group">
        <label>DoB</label>
        <input type="text" class="form-control date-field" name="dob" value="" />
    </div>
    <div class="form-group">
        <label>Called police
            <input type="checkbox" name="police" value="1" />
        </label>
    </div>
    <div class="form-group">
        <label>Case #</label>
        <input type="text" class="form-control" name="case" value="" />
    </div>
    <div class="form-group">
        <label>Requested trespass
            <input type="checkbox" name="trespass" value="1" />
        </label>
    </div>
    <div class="form-group">
        <label>Start</label>
        <input type="text" class="form-control date-field" name="tStart" value="" />
    </div>
    <div class="form-group">
        <label>End</label>
        <input type="text" class="form-control date-field" name="tEnd" value="" />
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
                AND i.deleted = 0
            ORDER BY tdate DESC";

        $tableLabel = _('All Alerts');
        if (!FormLib::get('all')) {
            $query = str_replace('ORDER BY tdate', 'ORDER BY modified', $query);
            $query = $this->connection->addSelectLimit($query, 30);
            $tableLabel = _('Recent Alerts (<a href="?all=1">Show All</a>)');
        }
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
<tr><th colspan="5" class="text-center">{$tableLabel}</th></tr>
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

