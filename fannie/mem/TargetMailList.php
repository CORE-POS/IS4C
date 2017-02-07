<?php 
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}

class TargetMailList extends FannieReportPage
{
    protected $title = 'Targeted Mailing List';
    protected $header = 'Targeted Mailing List';

    protected $required_fields = array('type');
    protected $report_headers = array('Mem#', 'Last Name', 'First Name', 'Address', 'Address2', 'City', 'State', 'Zip', 'Phone', 'Email');
    public $description = '[Targeted Mailing List] lists contact information for selected customers.';

    protected function selectFrom()
    {
        return "
               SELECT c.CardNo, 
                  LastName, 
                  FirstName, 
                  street,
                  city,
                  state,
                  zip,
                  phone,
                  memType,
                  email_1
              FROM custdata AS c
                  LEFT JOIN meminfo AS m ON c.CardNo=m.card_no
                  LEFT JOIN suspensions AS s ON s.cardno=c.CardNo
                  LEFT JOIN memDates AS d ON c.CardNo=d.card_no
                  LEFT JOIN core_warehouse.MemberSummary AS y ON c.CardNo=y.card_no ";
    }

    private function listFromForm($field)
    {
        $val = FormLib::get($field);
        $lines = explode("\n", $val);
        $lines = array_map(function ($i) { return trim($i); }, $lines);
        return array_filter($lines, function($i) { return $i != ''; });
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $query = $this->selectFrom();
        $where = 'WHERE c.personNum=1 ';
        $types = FormLib::get('type', array());

        list($inStr, $args) = $dbc->safeInClause($types);
        if (FormLib::get('inactive', false)) {
            $where .= " AND (c.memType IN ({$inStr}) OR s.memtype2 IN ({$inStr})) AND c.Type IN ('PC', 'INACT') ";
            list($inStr, $args) = $dbc->safeInClause($types, $args); // add types to $args again
        } else {
            $where .= " AND c.memType IN ({$inStr}) ";
        }

        if (FormLib::get('dateLimit', false)) {
            $where .= ' AND y.lastVisit < ? ';
            $args[] = FormLib::get('dateLimit');
        }

        if (FormLib::get('getsMail') == 1) {
            $where .= ' AND m.ads_OK=1 ';
        }

        if (FormLib::get('join1', false) && FormLib::get('join2', false)) {
            $where .= ' AND d.start_date BETWEEN ? AND ? ';
            $args[] = FormLib::get('join1') . ' 00:00:00';
            $args[] = FormLib::get('join2') . ' 23:59:59';
        }

        $states = $this->listFromForm('states');
        if (count($states) != 0) {
            list($inStr, $args) = $dbc->safeInClause($states, $args);
            $where .= " AND m.state IN ({$inStr}) ";
        }

        $zips = $this->listFromForm('zips');
        if (count($zips) != 0) {
            list($inStr, $args) = $dbc->safeInClause($zips, $args);
            $where .= " AND m.zip IN ({$inStr}) ";
        }

        $query .= $where;
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            list($street, $addr2) = array_pad(explode("\n", $row['street'], 2), 2, null);
            $data[] = array(
                $row['CardNo'],
                $row['LastName'],
                $row['FirstName'] === null ? '' : $row['FirstName'],
                $street,
                $addr2 === null ? '' : $addr2,
                $row['city'],
                $row['state'],
                $row['zip'],
                $row['phone'],
                $row['email_1'] === null ? '' : $row['email_1'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        $types = new MemtypeModel($this->connection);
        $ret = '<form method="get">
            <div class="panel panel-default">
                <div class="panel-heading">Type(s)</div>
                <div class="panel-body">';
        foreach ($types->find() as $t) {
            $ret .= sprintf('<label><input type="checkbox" name="type[]" %s value="%d" />%s</label><br />',
                ($t->custdataType() == 'PC' ? 'checked' : ''), $t->memtype(), $t->memDesc());
        }
        $cutoff = date('Y-m-t', strtotime('last month'));
        $ret .= '</div></div>
            <div class="panel panel-default">
                <div class="panel-heading">Filter(s)</div>
                <div class="panel-body">';
        $ret .= sprintf('<div class="form-group">
                <label>Hasn\'t shopped since (min: %s)</label>
                <input type="text" name="dateLimit" class="form-control date-field" />
                </div>', $cutoff);
        $ret .= '<div class="form-group">
                <label>Joined between</label>
                    <div class="form-inline">
                        <input type="text" name="join1" class="form-control date-field" />
                        and
                        <input type="text" name="join2" class="form-control date-field" />
                    </div>
                </div>';
        $ret .= '<div class="form-group">
                <label><input type="checkbox" name="inactive" value="1" /> Include inactive accounts</label>
                <br />
                <label><input type="checkbox" name="getsMail" value="1" checked /> Obey "Gets Mail" preference</label>
                </div>';
        $ret .= '<div class="form-group">
                <label>Limit to these states (one per line)</label>
                <textarea name="states" class="form-control" rows="5"></textarea>
                </div>
                <div class="form-group">
                <label>Limit to these zip codes (one per line)</label>
                <textarea name="zips" class="form-control" rows="5"></textarea>
                </div>
        </div></div>
        <p><button class="btn btn-default">Get List</button></p>
        </form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

