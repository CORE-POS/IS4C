<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Community Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ShrinkAdjustor extends FannieRESTfulPage
{
    protected $header = 'Adjust Entries';
    protected $title = 'Adjust Entries';
    public $description = '[Shrink Adjustor] can correct shrink entries from past dates.';

    function preprocess()
    {
        $this->addRoute('get<adjust>', 'post<adjust>');
        return parent::preprocess();
    }

    protected function post_adjust_handler()
    {
        $json = json_decode(base64_decode($this->adjust), true);
        $date = date('Y-m-d', strtotime($json['date']));
        $original = json_decode(base64_decode(FormLib::get('original')), true);
        $qty = FormLib::get('qty');
        $cost = FormLib::get('cost');
        $total = FormLib::get('ttl');
        $dept = FormLib::get('dept');
        $reason = FormLib::get('reason');
        $loss_con = FormLib::get('loss_con');

        // entry was changed
        if ($original['quantity'] != $qty 
            || $original['cost'] != $cost 
            || $original['total'] != $total
            || $original['department'] != $dept
            || $original['numflag'] != $reason
            || ($original['charflag'] != '' && $original['charflag'] != $loss_con)) {

            // lookup the original shrink record transaction
            $query = "SELECT MAX(datetime) AS dt, MAX(trans_id) AS id
                FROM " . $this->archiveTable($date) . "
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?";
            $args = array(
                $date . ' 00:00:00',
                $date . ' 23:59:59',
                $json['emp'],
                $json['reg'],
                $json['trans'],
            ); 
            $prep = $this->connection->prepare($query);
            $res = $this->connection->execute($prep, $args);
            $row = $this->connection->fetchRow($res);
            $tdate = date("'Y-m-d H:i:s'", strtotime($row['dt']));
            $trans_id = $row['id'] + 1;

            // add a new record that reverses the original
            $record = DTrans::defaults();
            $record['emp_no'] = $json['emp'];
            $record['register_no'] = $json['reg'];
            $record['trans_no'] = $json['trans'];
            $record['trans_id'] = $trans_id;
            $record['upc'] = $original['upc'];
            $record['description'] = $original['description'];
            $record['department'] = $original['department'];
            $record['trans_type'] = 'I';
            $record['quantity'] = -1*$original['quantity'];
            $record['ItemQtty'] = -1*$original['ItemQtty'];
            $record['unitPrice'] = -1*($original['total']/$original['quantity']);
            $record['regPrice'] = -1*($original['total']/$original['quantity']);
            $record['total'] = -1*$original['total'];
            $record['cost'] = -1*$original['cost'];
            $record['numflag'] = $original['numflag'];
            $record['charflag'] = $original['charflag'];
            $record['trans_status'] = 'Z';
            $record['store_id'] = $original['store_id'];
            $info = DTrans::parameterize($record, 'datetime', $tdate);

            $query = 'INSERT INTO ' . $this->archiveTable($date) . '
                (' . $info['columnString'] . ')
                VALUES
                (' . $info['valueString'] . ')';
            $prep = $this->connection->prepare($query);
            $res = $this->connection->execute($prep, $info['arguments']);

            // add to transarchive, too, in case that's relevant. nightly rotation will
            // fix if it doesn't technically belong
            $query = 'INSERT INTO ' . $this->config->get('TRANS_DB') . $this->connection->sep() . 'transarchive
                (' . $info['columnString'] . ')
                VALUES
                (' . $info['valueString'] . ')';
            $prep = $this->connection->prepare($query);
            $res = $this->connection->execute($prep, $info['arguments']);

            // add a new adjustment record if the values as non-zero
            if ($qty != 0 && ($cost != 0 || $total != 0)) {
                $trans_id++;
                $record['trans_id'] = $trans_id;
                $record['department'] = $dept;
                $record['quantity'] = $qty;
                $record['ItemQtty'] = $qty;
                $record['unitPrice'] = sprintf('%.2f', $total/$qty);
                $record['regPrice'] = sprintf('%.2f', $total/$qty);
                $record['total'] = $total;
                $record['cost'] = $cost;
                $record['numflag'] = $reason;
                $record['charflag'] = $loss_con;
                $info = DTrans::parameterize($record, 'datetime', $tdate);

                $query = 'INSERT INTO ' . $this->archiveTable($date) . '
                    (' . $info['columnString'] . ')
                    VALUES
                    (' . $info['valueString'] . ')';
                $prep = $this->connection->prepare($query);
                $res = $this->connection->execute($prep, $info['arguments']);

                // same logic w/ using both tables
                $query = 'INSERT INTO ' . $this->config->get('TRANS_DB') . $this->connection->sep() . 'transarchive
                    (' . $info['columnString'] . ')
                    VALUES
                    (' . $info['valueString'] . ')';
                $prep = $this->connection->prepare($query);
                $res = $this->connection->execute($prep, $info['arguments']);
            }
        }
        
        $url = sprintf('ShrinkAdjustor.php?id=%s&month=%d&year=%d',
                    $original['upc'],
                    date('n', strtotime($date)),
                    date('Y', strtotime($date))
        );

        return $url;
    }

    private function archiveTable($date)
    {
        if ($this->config->get('ARCHIVE_METHOD') == 'partitions') {
            return $this->config->get('ARCHIVE_DB') . $this->connection->sep() . 'bigArchive';
        } else {
            $str = date('Ym', strtotime($date));
            return $this->config->get('ARCHIVE_DB') . $this->connection->sep() . 'transArchive' . $str;
        }
    }

    protected function get_adjust_view()
    {
        $json = json_decode(base64_decode($this->adjust), true);

        $date = date('Y-m-d', strtotime($json['date']));
        $dtrans = DTransactionsModel::selectDTrans($date);

        $query = $this->connection->prepare("
            SELECT quantity,
                department,
                cost,
                total,
                numflag,
                charflag,
                upc,
                description,
                store_id
            FROM {$dtrans}
            WHERE datetime BETWEEN ? AND ?
                AND trans_status='Z'
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND trans_id=?");
        $args = array(
            $date . ' 00:00:00',
            $date . ' 23:59:59',
            $json['emp'],
            $json['reg'],
            $json['trans'],
            $json['id'],
        );
        $res = $this->connection->execute($query, $args);
        if ($this->connection->numRows($res) == 0) {
            return '<div class="alert alert-danger">No entry found</div>';
        }
        $row = $this->connection->fetchRow($res);
        if ($row['charflag'] == 'C') {
            $loss_con = '<option value="L">Loss</option><option value="C" selected>Contribute</option>';
        } else {
            $loss_con = '<option value="L" selected>Loss</option><option value="C">Contribute</option>';
        }
        $reasons = new ShrinkReasonsModel($this->connection);
        $r_opts = $reasons->toOptions($row['numflag']);
        $depts = new DepartmentsModel($this->connection);
        $d_opts = $depts->toOptions($row['department']);
        $original = base64_encode(json_encode($row));

        return <<<HTML
<form method="post">
    <h3>
        Adjusting {$row['upc']} - {$row['description']} from {$date}
    </h3>
    <input type="hidden" name="adjust" value="{$this->adjust}" />
    <input type="hidden" name="original" value="{$original}" />
    <div class="form-group">
        <label>Quantity</label>
        <input type="number" min="-999" max="999" step="0.01" value="{$row['quantity']}" 
            class="form-control" name="qty" />
    </div>
    <div class="form-group">
        <label>Total Cost</label>
        <input type="number" min="-9999" max="9999" step="0.01" value="{$row['cost']}" 
            class="form-control" name="cost" />
    </div>
    <div class="form-group">
        <label>Total Retail</label>
        <input type="number" min="-9999" max="9999" step="0.01" value="{$row['total']}" 
            class="form-control" name="ttl" />
    </div>
    <div class="form-group">
        <label>Department</label>
        <select name="dept" class="form-control">
            {$d_opts}
        </select>
    </div>
    <div class="form-group">
        <label>Reason</label>
        <select name="reason" class="form-control">
            {$r_opts}
        </select>
    </div>
    <div class="form-group">
        <label>Loss/Contrib?</label>
        <select name="loss_con" class="form-control">
            {$loss_con}
        </select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Submit</button>
        <button type="reset" class="btn btn-default btn-reset">Reset</button>
    </div>
</form>
HTML;
    }

    protected function get_id_view()
    {
        $month = FormLib::get('month');
        $year = FormLib::get('year');
        $tstamp = mktime(0,0,0,$month,1,$year);
        $date1 = date('Y-m-01', $tstamp);
        $date2 = date('Y-m-t', $tstamp);

        $dtrans = DTransactionsModel::selectDTrans($date1, $date2);

        $query = "
            SELECT upc,
                t.description,
                cost,
                dept_name,
                quantity,
                total,
                register_no,
                emp_no,
                trans_no,
                trans_id,
                datetime,
                s.description AS shrinkReason
            FROM {$dtrans} AS t
                LEFT JOIN departments AS d ON t.department=d.dept_no
                LEFT JOIN ShrinkReasons AS s ON t.numflag=s.shrinkReasonID
            WHERE datetime BETWEEN ? AND ?
                AND upc=?
                AND trans_status='Z'
                AND emp_no <> 9999
                AND register_no <> 99
            ORDER BY datetime DESC";
        $prep = $this->connection->prepare($query);
        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59', BarcodeLib::padUPC($this->id));
        $res = $this->connection->execute($prep, $args);
        if ($this->connection->numRows($res) == 0) {
            $ret = '<p>No results</p>';
        } else {
            $ret = '<table class="table table-bordered">
                <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Department</th>
                    <th>Quantity</th>
                    <th>Total Cost</th>
                    <th>Total Retail</th>
                    <th>Reason</th>
                </tr>';
            while ($row = $this->connection->fetchRow($res)) {
                $adjust = array(
                    'date' => $row['datetime'],
                    'emp' => $row['emp_no'],
                    'reg' => $row['register_no'],
                    'trans' => $row['trans_no'],
                    'id' => $row['trans_id'],
                );
                $key = base64_encode(json_encode($adjust));
                $ret .= sprintf('<tr>
                    <td>%s</td><td>%s</td><td>%s</td>
                    <td>%.2f</td><td>%.2f</td><td>%.2f</td>
                    <td>%s</td><td><a href="?adjust=%s" class="btn btn-default">Adjust entry</a></tr>',
                    $row['datetime'], $row['description'], $row['dept_name'], 
                    $row['quantity'], $row['cost'], $row['total'], 
                    $row['shrinkReason'], $key
                );
            }
            $ret .= '</table>';
        }

        return $this->get_view() . '<hr />' . $ret;
    }

    protected function get_view()
    {
        $months = '';
        for ($i=0; $i<12; $i++) {
            $tstamp = mktime(0,0,0,$i+1,1,2000);
            $months .= sprintf('<option %s value="%d">%s</option>',
                (($i+1) == date('n') ? 'selected' : ''),
                ($i+1), date('F', $tstamp));
        }
        $year = date('Y');
        $this->addScript('../autocomplete.js');
        $ws = $this->config->get('URL') . 'ws/';
        $this->addOnloadCommand("bindAutoComplete('#upc', '$ws', 'item');\n");
        $this->addOnloadCommand('$(\'#upc\').focus();');

        return <<<HTML
<p>
<form method="get" class="form-inline">
    <div class="form-group">
        <label>UPC</label>
        <input type="text" class="form-control" name="id" id="upc" />
    </div>
    <div class="form-group">
        <label>Month</label>
        <select class="form-control" name="month">
            {$months}
        </select>
    </div>
    <div class="form-group">
        <label>Year</label>
        <input type="number" class="form-control" name="year" value="{$year}" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    </div>
</form>
</p>
HTML;
    }
}

FannieDispatch::conditionalExec();

