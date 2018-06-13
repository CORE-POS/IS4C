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
        'filterUsedCoupon',
    );

    protected function post_handler()
    {
        $search = new stdClass();
        $search->args = array();
        $search->where = '1=1 ';
        $search->from = 'custdata AS c LEFT JOIN memtype AS t ON c.memType=t.memtype ';
        foreach ($this->searchMethods as $method) {
            $search = $this->$method($search, $this->form);
        }

        $query = "SELECT c.CardNo,
                c.personNum,
                c.FirstName,
                c.LastName,
                c.Type,
                t.memDesc
            FROM {$search->from}
            WHERE {$search->where}";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $search->args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $key = $row['CardNo'] . ':' . $row['personNum'];
            $data[$key] = $row;
        }

        foreach ($this->filterMethods as $method) {
            $data = $this->$method($data, $this->form);
        }

        if (count($data) > 5000) {
            echo 'Too many results';
            return false;
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
            <th>Owner #</th><th>First Name</th><th>Last Name</th><th>Status</th><th>Type</th></thead>
            <tbody>';
        $copyPaste = "";
        foreach ($data as $d) {
            $checked = in_array($d['CardNo'], $saved) ? 'checked' : '';
            $ret .= "<tr>
                <td><input type=\"checkbox\" onchange=\"checkedCount('#selection-counter', '.savedCB');\" 
                    class=\"savedCB\" {$checked} name=\"saved[]\" value=\"{$d['CardNo']}\" /></td>
                <td><a href=\"MemberEditor.php?memNum={$d['CardNo']}\">{$d['CardNo']}</a></td>
                <td>{$d['FirstName']}</td><td>{$d['LastName']}</td><td>{$d['Type']}</td><td>{$d['memDesc']}</td></tr>";
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
        if ($form->tryGet('status') != '') {
            $search->where .= ' AND c.Type=? ';
            $search->args[] = $form->status;
        }
        if ($form->tryGet('type') != '') {
            $search->where .= ' AND c.memType=? ';
            $search->args[] = $form->type;
        }

        return $search;
    }

    private function searchEquity($search, $form)
    {
        $eOp = $form->tryGet('equityOp');
        switch ($eOp) {
            case '=':
            case '<':
            case '>':
                break;
            default:
                return $search;
        }

        if (trim($form->tryGet('equity')) !== '') {
            $search = $this->addTable($search, FannieDB::fqn('equity_live_balance', 'trans'), 'e', 'memnum');
            $search->where .= " AND e.payments {$eOp} ? ";
            $search->args[] = $form->equity;
        }

        return $search;
    }

    private function searchNames($search, $form)
    {
        if (trim($form->tryGet('fn')) != '') {
            $search->where .= ' AND c.FirstName LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($form->fn)) . '%';
        }
        if (trim($form->tryGet('ln')) != '') {
            $search->where .= ' AND c.LastName LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($form->ln)) . '%';
        }

        return $search;
    }

    private function searchContact($search, $form)
    {
        if (trim($form->tryGet('phone')) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND (m.phone LIKE ? OR m.email_2 LIKE ?) ';
            $phone = '%' . str_replace('*', '%', trim($form->phone)) . '%';
            $search->args[] = $phone;
            $search->args[] = $phone;
        }
        if (trim($form->tryGet('email')) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.email_1 LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($form->email)) . '%';
        }

        return $search;
    }

    private function searchAddress($search, $form)
    {
        if (trim($form->tryGet('addr')) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.street LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($form->addr)) . '%';
        }
        if (trim($form->tryGet('city')) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.city LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($form->city)) . '%';
        }
        if (trim($form->tryGet('state')) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.state LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($form->state)) . '%';
        }
        if (trim($form->tryGet('zip')) != '') {
            $search = $this->addTable($search, FannieDB::fqn('meminfo', 'op'), 'm', 'card_no');
            $search->where .= ' AND m.zip LIKE ? ';
            $search->args[] = '%' . str_replace('*', '%', trim($form->zip)) . '%';
        }

        return $search;
    }

    private function searchJoin($search, $form)
    {
        if (trim($form->tryGet('join1')) != '') {
            $start = trim($form->join1);
            $end = trim($form->tryGet('join2')) != '' ? trim($form->join2) : $start;
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
            $isPrimary = $form->isPrimary;
            if ($isPrimary) {
                $search->where .= ' AND c.personNum=1 ';
            }
        } catch (Exception $ex) {
        }

        return $search;
    }

    private function filterUsedCoupon($data, $form)
    {
        if (is_numeric($form->tryGet('usedCoupon'))) {
            $couponP = $this->connection->prepare("SELECT * FROM houseCoupons WHERE coupID=?");
            $coupon = $this->connection->getRow($couponP, array($form->usedCoupon));
            $dlog = DTransactionsModel::selectDlog($coupon['startDate'], $coupon['endDate']);
            $cardP = $this->connection->prepare("SELECT card_no
                FROM {$dlog}
                WHERE upc=?
                    AND tdate BETWEEN ? AND ?
                GROUP BY card_no");
            $args = array(
                '00499999' . str_pad($form->usedCoupon, 5, '0', STR_PAD_LEFT),
                $coupon['startDate'],
                str_replace('00:00:00', '23:59:59', $coupon['endDate']),
            );
            $cardR = $this->connection->execute($cardP, $args);
            $cards = array();
            while ($cardW = $this->connection->fetchRow($cardR)) {
                $cards[$cardW['card_no']] = $cardW['card_no'];
            }
            foreach (array_keys($data) as $idStr) {
                list($card,) = explode(':', $idStr, 2);
                if (!isset($cards[$card])) {
                    unset($data[$idStr]);
                }
            }
        }

        return $data;
    }

    private function filterHasShopped($data, $form)
    {
        if (is_numeric($form->tryGet('hasShopped'))) {
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
        if (is_numeric($form->tryGet('hasntShopped'))) {
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
        $couponR = $this->connection->query('SELECT coupID, description FROM houseCoupons ORDER BY startDate DESC');
        $coupons = '';
        while ($couponW = $this->connection->fetchRow($couponR)) {
            $coupons .= sprintf('<option value="%s">%s</option>', $couponW['coupID'], $couponW['description']);
        }
        $this->addScript('search.js');
        return <<<HTML
<form method="post" id="memSearchForm" onsubmit="runSearch(); return false;">
<div class="row">
<div class="col-sm-11">
<table class="table table-bordered small">
    <tr>
        <th>Owner #
            <label><input type="checkbox" name="isPrimary" value="1" checked />
                Is Primary Owner</label>
        </th>
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
        <input type="text" name="equity" class="form-control input-sm price-field" /></td>
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
        </td>
    </tr>
    <tr>
        <th>Used Coupon</th>
        <td><select name="usedCoupon" class="form-control input-sm">
            <option value="">Select one...</option>
            {$coupons}
        </select></td>
    </tr>
</table>
</div>
<div class="col-sm-1">
    <button class="btn btn-default btn-sm" type="button" 
        onclick="sendTo('TargetMailList.php?type=all');">Mailing List</button>
    <br /><br />
    <button class="btn btn-default btn-sm" type="button" 
        onclick="sendTo('../modules/plugins2.0/CoreWarehouse/reports/CWMemberProfile.php');">Profile</button>
    <br /><br />
    <button class="btn btn-default btn-sm" type="button" 
        onclick="sendTo('../reports/from-search/AccountNames/AccountNamesFromSearch.php');">All Names</button>
</div>
</div>
<p>
    <button type="submit" class="btn btn-default btn-core">Search</button>
    <button type="reset" class="btn btn-default btn-reset">Reset Form</button>
    &nbsp;&nbsp;&nbsp;&nbsp;
    <span id="selection-counter"></span>
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
<form id="sendForm" method="post" target="_new"></form>
HTML;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->get_view());
        ob_start();
        $phpunit->assertEquals(false, $this->post_handler());
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->setMany(array(
            'card_no' => 1,
            'status' => 'PC',
            'type' => 1,
            'equityOp' => '>',
            'equity' => 0,
            'fn' => 'a',
            'ln' => 'a',
            'phone' => '1',
            'email' => '@',
            'addr' => '1',
            'city' => 'a',
            'state' => 'm',
            'zip' => '1',
            'join1' => '2000-01-01',
            'isPrimary' => 1,
            'usedCoupon' => 1,
            'hasShopped' => 90,
            'hasntShopped' => 30,
        ));
        $this->setForm($form);
        $phpunit->assertEquals(false, $this->post_handler());
        ob_end_clean();
    }
}

FannieDispatch::conditionalExec();

