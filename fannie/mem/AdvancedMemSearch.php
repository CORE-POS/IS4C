<?php

include(__DIR__ . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class AdvancedMemSearch extends FannieRESTfulPage
{
    protected $title = 'Advanced Owner Search';
    protected $header = 'Advanced Owner Search';
    public $description = '[Advanced Owner Search] provides a wide variety of parameters for finding accounts';

    private $searchMethods = array(
        'searchByNumber',
        'searchStatus',
        'searchEquity',
        'searchNames',
        'searchContact',
        'searchAddress',
        'searchJoin',
        'searchPrimary',
        'addSaved', // needs to be last
    );

    private $filterMethods = array(
        'filterHasShopped',
        'filterHasNotShopped',
    );

    protected function post_handler()
    {
        $search = new stdClass();
        $search->args = array();
        $search->where = '1=1 ';
        $search->from = 'custdata AS c ';
        foreach ($this->searchMethods as $method) {
            $search = $this->$method($search, $this->form);
        }

        if (count($search->args) == 0) {
            echo 'Too many results';
            return false;
        }

        $query = "SELECT c.CardNo,
                c.personNum,
                c.FirstName,
                c.LastName
            FROM {$search->from}
            WHERE {$search->where}";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $search->args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $key = $row['CardNo'] . ':' . $row['personNum'];
            $data[$key] = $row;
            if (count($data) > 3000) {
                echo 'Too many results';
                return false;
            }
        }

        foreach ($this->filterMethods as $method) {
            $data = $this->$method($data, $this->form);
        }

        echo $this->sendOutputTable($data);
        return false;
    }

    private function addSaved($search, $form)
    {
        try {
            $saved = $form->saved;
            list($inStr, $search->args) = $this->connection->safeInClause($saved, $search->args);
            $search->where = '(' . $search->where . ') OR (c.CardNo IN (' . $inStr . ') ';
            $search = $this->searchPrimary($search, $form);
            $search->where .= ') ';
        } catch (Exception $ex) {
        }

        return $search;
    }

    private function sendOutputTable($data)
    {
        $saved = $this->form->tryGet('saved', array());
        $ret = '<p>Found ' . count($data) . ' matches</p>';
        $ret .= '<table class="table small">
            <thead><tr><th style="width:25px;"><input type="checkbox" onchange="toggleAll(this, \'.savedCB\');" />
            <th>Owner #</th><th>First Name</th><th>Last Name</th></thead>
            <tbody>';
        $copyPaste = "";
        foreach ($data as $d) {
            $checked = in_array($d['CardNo'], $saved) ? 'checked' : '';
            $ret .= "<tr>
                <td><input type=\"checkbox\" class=\"savedCB\" {$checked} name=\"saved[]\" value=\"{$d['CardNo']}\" /></td>
                <td><a href=\"MemberEditor.php?memNum={$d['CardNo']}\">{$d['CardNo']}</a></td>
                <td>{$d['FirstName']}</td><td>{$d['LastName']}</td></tr>";
            $copyPaste .= $d['CardNo'] . "\n";
        }
        $ret .= '</tbody></table>';
        $ret .= '<p><textarea class="form-control input-sm" rows="5">' . $copyPaste . '</textarea></p>';

        return $ret;
    }

    private function searchByNumber($search, $form)
    {
        $nums = explode("\n", $form->tryGet('card_no'));
        $nums = array_filter($nums, function ($i) { return trim($i) != ''; });
        if (count($nums) > 0) {
            list($inStr, $args) = $this->connection->safeInClause($nums);
            $search->where .= " AND c.CardNo IN ({$inStr}) ";
            $search->args = array_merge($search->args, $args);
        }

        return $search;
    }

    private function searchStatus($search, $form)
    {
        if ($form->status != '') {
            $search->where .= ' AND c.Type=? ';
            $search->args[] = $form->status;
        }
        if ($form->type != '') {
            $search->where .= ' AND c.memType=? ';
            $search->args[] = $form->type;
        }

        return $search;
    }

    private function searchEquity($search, $form)
    {
        $eOp = $this->form->equityOp;
        switch ($eOp) {
            case '=':
            case '<':
            case '>':
                break;
            default:
                return $search;
        }

        if (trim($this->form->equity) !== '') {
            $search = $this->addTable($search, FannieDB::fqn('equity_live_balance', 'trans'), 'e', 'memnum');
            $search->where .= " AND e.payments {$eOp} ? ";
            $search->args[] = $this->form->equity;
        }

        return $search;
    }

    private function searchNames($search, $form)
    {
        if (trim($this->form->fn) != '') {
            $search->where .= ' AND c.FirstName LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($this->form->fn)) . '%';
        }
        if (trim($this->form->ln) != '') {
            $search->where .= ' AND c.LastName LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($this->form->ln)) . '%';
        }

        return $search;
    }

    private function searchContact($search, $form)
    {
        if (trim($this->form->phone) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND (m.phone LIKE ? OR m.email_2 LIKE ?) ';
            $phone = '%' . str_replace('*', '%', trim($this->form->phone)) . '%';
            $search->args[] = $phone;
            $search->args[] = $phone;
        }
        if (trim($this->form->email) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.email_1 LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($this->form->email)) . '%';
        }

        return $search;
    }

    private function searchAddress($search, $form)
    {
        if (trim($this->form->addr) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.street LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($this->form->addr)) . '%';
        }
        if (trim($this->form->city) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.city LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($this->form->city)) . '%';
        }
        if (trim($this->form->state) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.state LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($this->form->state)) . '%';
        }
        if (trim($this->form->zip) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.zip LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($this->form->zip)) . '%';
        }

        return $search;
    }

    private function searchJoin($search, $form)
    {
        if (trim($this->form->join1) != '') {
            $start = trim($this->form->join1);
            $end = trim($this->form->join2) != '' ? trim($this->form->join2) : $start;
            $search = $this->addTable($search, FannieDB::fqn('memDates', 'op'), 'd', 'card_no');
            $search->where .= ' AND d.start_date BETWEEN ? AND ? ';
            $search->args[] = $start . ' 00:00:00';
            $search->args[] = $end . ' 23:59:59';
        }

        return $search;
    }

    private function searchPrimary($search, $form)
    {
        try {
            $isPrimary = $this->form->isPrimary;
            if ($isPrimary) {
                $search->where .= ' AND c.personNum=1 ';
            }
        } catch (Exception $ex) {
        }

        return $search;
    }

    private function filterHasShopped($data, $form)
    {
        if (is_numeric($form->hasShopped)) {
            $start = date('Y-m-d', strtotime($form->hasShopped . ' days ago'));
            $end = date('Y-m-d');
            $dlog = DTransactionsModel::selectDlog($start, $end);
            $nums = array_keys($data);
            $nums = array_map(function ($i) {
                list($cardno,) = explode(':', $i, 2);
                return $cardno;
            }, $nums);
            list($inStr, $args) = $this->connection->safeInClause($nums);
            $args[] = $start . ' 00:00:00';
            $args[] = $end . ' 23:59:59';
            $prep = $this->connection->prepare("SELECT card_no
                FROM {$dlog} AS d
                WHERE d.card_no IN ({$inStr})
                    AND d.tdate BETWEEN ? AND ?
                    AND d.upc='TAX'
                GROUP BY card_no");
            $res = $this->connection->execute($prep, $args);
            $valid = array();
            while ($row = $this->connection->fetchRow($res)) {
                $valid[$row['card_no']] = $row['card_no'];
            }
            foreach (array_keys($data) as $key) {
                list($cardno,) = explode(':', $key, 2);
                if (!isset($valid[$cardno])) {
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }

    private function filterHasNotShopped($data, $form)
    {
        if (is_numeric($form->hasntShopped)) {
            $start = date('Y-m-d', strtotime($form->hasntShopped . ' days ago'));
            $end = date('Y-m-d');
            $dlog = DTransactionsModel::selectDlog($start, $end);
            $args = array();
            $args[] = $start . ' 00:00:00';
            $args[] = $end . ' 23:59:59';
            $prep = $this->connection->prepare("SELECT card_no
                FROM {$dlog} AS d
                WHERE d.tdate BETWEEN ? AND ?
                    AND d.upc='TAX'
                GROUP BY card_no");
            $res = $this->connection->execute($prep, $args);
            $invalid = array();
            while ($row = $this->connection->fetchRow($res)) {
                $invalid[$row['card_no']] = $row['card_no'];
            }
            $keepers = array();
            foreach (array_keys($data) as $key) {
                list($cardno,) = explode(':', $key, 2);
                if (!isset($invalid[$cardno])) {
                    $keepers[$key] = $data[$key];
                }
            }
            $data = $keepers;
        }

        return $data;
    }

    private function addTable($obj, $table, $alias, $joinCol)
    {
        if (!strstr($obj->from, $table)) {
            $obj->from .= " LEFT JOIN {$table} AS {$alias} ON c.CardNo={$alias}.{$joinCol}";
        }

        return $obj;
    }

    protected function get_view()
    {
        $memtype = new MemtypeModel($this->connection);
        $memtype = $memtype->toOptions(-999);
        return <<<HTML
<script type="text/javascript">
function runSearch() {
    $('#resultsArea').html('');
    $('#progressBar').show();
    $.ajax({
        data: $('#memSearchForm').serialize(),
        method: 'post',
    }).error(function (e1, e2, e3) {
        $('#progressBar').hide();
        $('#resultsArea').html(JSON.stringify(e1) + ", " + e2 + ", " + e3);
    }).done(function (resp) {
        $('#progressBar').hide();
        $('#resultsArea').html(resp);   
    });
}
function toggleAll(elem, selector) {
    if (elem.checked) {
        $(selector).prop('checked', true);
    } else {
        $(selector).prop('checked', false);
    }
    checkedCount('#selection-counter', selector);
}
</script>
<form method="post" id="memSearchForm" onsubmit="runSearch(); return false;">
<table class="table table-bordered small">
    <tr>
        <th>Owner #</th>
        <td><textarea class="form-control input-sm" rows="2" name="card_no"></textarea></td>
        <th>Status</th>
        <td><select name="status" class="form-control input-sm">
            <option value=""></option>
            <option value="PC">Active Owner</option>
            <option value="REG">Active Non-Owner</option>
            <option value="INACT">Inactive Account</option>
            <option value="TERM">Terminated Account</option>
        </select></td>
        <th>Type</th>
        <td><select name="type" class="form-control input-sm">
            <option value=""></option>{$memtype}
        </select></td>
        <th>Equity</th>
        <td class="form-inline">
        <select name="equityOp" class="form-control input-sm"><option>=</option><option>&gt;</option><option>&lt;</option></select>
        <input type="text" name="equity" class="form-control input-sm" /></td>
    </tr>
    <tr>
        <th>First Name</th>
        <td><input type="text" name="fn" class="form-control input-sm" /></td>
        <th>Last Name</th>
        <td><input type="text" name="ln" class="form-control input-sm" /></td>
        <th>Phone</th>
        <td><input type="text" name="phone" class="form-control input-sm" /></td>
        <th>Email</th>
        <td><input type="text" name="email" class="form-control input-sm" /></td>
    </tr>
    <tr>
        <th>Address</th>
        <td><input type="text" name="addr" class="form-control input-sm" /></td>
        <th>City</th>
        <td><input type="text" name="city" class="form-control input-sm" /></td>
        <th>State</th>
        <td><input type="text" name="state" class="form-control input-sm" /></td>
        <th>Zip</th>
        <td><input type="text" name="zip" class="form-control input-sm" /></td>
    </tr>
    <tr>
        <th>Has Shopped</th>
        <td><select name="hasShopped" class="form-control input-sm">
            <option value=""></option>
            <option value="7">In the last 7 days</option>
            <option value="30">In the last 30 days</option>
            <option value="90">In the last 90 days</option>
        </select></td>
        <th>Hasn't Shopped</th>
        <td><select name="hasntShopped" class="form-control input-sm">
            <option value=""></option>
            <option value="7">In the last 7 days</option>
            <option value="30">In the last 30 days</option>
            <option value="90">In the last 90 days</option>
        </select></td>
        <th>Joined</th>
        <td class="form-inline" colspan="3">
            <input type="text" class="form-control input-sm date-field" name="join1" />
            <input type="text" class="form-control input-sm date-field" name="join2" 
                placeholder="Optional" />
            <label><input type="checkbox" name="isPrimary" value="1" checked />
                Is Primary Owner</label>
        </td>
    </tr>
</table>
<p>
    <button type="submit" class="btn btn-default btn-core">Search</button>
    <button type="reset" class="btn btn-default btn-reset">Reset Form</button>
</p>
<hr />
<div id="progressBar" class="progress collapse">
    <div class="progress-bar progress-bar-striped active"  role="progressbar" 
        aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
        <span class="sr-only">Searching</span>
    </div>
</div>
<div id="resultsArea"></div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

