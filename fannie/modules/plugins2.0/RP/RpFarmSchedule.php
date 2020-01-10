<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class RpFarmSchedule extends FannieRESTfulPage
{
    protected $header = 'Direct Schedule';
    protected $title = 'Direct Schedule';

    protected function post_id_handler()
    {
        $farm = FormLib::get('farmID');
        $primary = FormLib::get('primary') ? 1 : 0;
        $start = FormLib::get('start');
        $end = FormLib::get('end');

        $findP = $this->connection->prepare("SELECT rpFarmScheduleID FROM RpFarmSchedules WHERE isPrimary=? AND startDate=? AND endDate=? and likeCode=?");
        $found = $this->connection->getValue($findP, array($primary, $start, $end, $this->id));
        if ($found) {
            $prep = $this->connection->prepare("UPDATE RpFarmSchedules SET rpFarmID=? WHERE rpFarmScheduleID=?");
            $this->connection->execute($prep, array($farm, $found));
        } else {
            $prep = $this->connection->prepare("INSERT INTO RpFarmSchedules (rpFarmID, isPrimary, startDate, endDate, likeCode)
                VALUES (?, ?, ?, ?, ?)");
            $this->connection->execute($prep, array($farm, $primary, $start, $end, $this->id));
        }
        echo "OK";

        return false;
    }

    protected function javascript_content()
    {
        return <<<JAVASCRIPT
function saveSchedule(lc, farm, primary, start, end) {
    var dstr = 'id='+lc;
    dstr += '&farmID='+farm;
    dstr += '&primary='+primary;
    dstr += '&start='+start;
    dstr += '&end='+end;
    $.ajax({
        data: dstr,
        type: 'post'
    });
}
JAVASCRIPT;
    }

    protected function get_view()
    {
        $timePeriods = array();
        $ts = mktime(0,0,0,date('n'),1,date('Y'));
        for ($i=0; $i<4; $i++) {
            $start = date('Y-m-d', $ts);
            $key = date('M', $ts);
            $ts = mktime(0, 0, 0, date('n',$ts)+1, 1, date('Y',$ts));
            $end = date('Y-m-t', $ts);
            $key .= '-' . date('M',$ts);
            $ts = mktime(0, 0, 0, date('n',$ts)+1, 1, date('Y',$ts));
            $timePeriods[$key] = array($start, $end);
        }

        $farmR = $this->connection->query("SELECT rpFarmID, name FROM RpFarms ORDER BY name");
        $farms = array();
        while ($row = $this->connection->fetchRow($farmR)) {
            $farms[$row['rpFarmID']] = $row['name'];
        }

        $res = $this->connection->query('SELECT r.likeCode, l.likeCodeDesc
            FROM RpLocalLCs AS r
                INNER JOIN likeCodes AS l ON r.likeCode=l.likeCode
            ORDER BY l.likeCodeDesc');
        $ret = '<table class="table table-bordered">
            <tr><th>LC</th><th>Item</th>';
        foreach ($timePeriods as $label => $dates) {
            $ret .= '<td colspan="2">' . $label . '</td>';
        }
        $ret .= '</tr>';
        $findP = $this->connection->prepare("SELECT rpFarmID FROM RpFarmSchedules
            WHERE isPrimary=? AND startDate=? AND endDate=? AND likeCode=?");
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr><td>%d</td><td>%s</td>', $row['likeCode'], $row['likeCodeDesc']);
            foreach ($timePeriods as $label => $dates) {
                $opts = '<option></option>';
                $farmID = $this->connection->getValue($findP, array(1, $dates[0], $dates[1], $row['likeCode']));
                foreach ($farms as $id => $name) {
                    $selected = $farmID && $farmID == $id ? 'selected' : '';
                    $opts .= "<option {$selected} value=\"{$id}\">{$name}</option>";
                }
                $ret .= sprintf('<td><select class="form-control input-sm"
                    onchange="saveSchedule(%d, this.value, 1, \'%s\', \'%s\');">
                    %s</select></td>',
                    $row['likeCode'], $dates[0], $dates[1], $opts);

                $opts = '<option></option>';
                $farmID = $this->connection->getValue($findP, array(0, $dates[0], $dates[1], $row['likeCode']));
                foreach ($farms as $id => $name) {
                    $selected = $farmID && $farmID == $id ? 'selected' : '';
                    $opts .= "<option {$selected} value=\"{$id}\">{$name}</option>";
                }
                $ret .= sprintf('<td><select class="form-control input-sm"
                    onchange="saveSchedule(%d, this.value, 0, \'%s\', \'%s\');">
                    %s</select></td>',
                    $row['likeCode'], $dates[0], $dates[1], $opts);
            }
        }
        $ret .= '</table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

