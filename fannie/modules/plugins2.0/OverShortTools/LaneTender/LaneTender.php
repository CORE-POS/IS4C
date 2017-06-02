<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class LaneTender extends FannieReportPage 
{

    protected $title = "Lane Tender Report";
    protected $header = "Lane Tender Report";
    protected $required_fields = array('date1', 'date2');

    protected $report_headers = array('Cashier', 'Lane', 'Cash', 'Charge', 'G.Cert', 'EBT-F', 'EBT-C',
        'Instore Coup', 'Gift Card', 'Rebate', 'Store Cred.', 'Elec. Check', 'Check' );

    public $description = '[Tender Report] lists POS tenders against actual counts over a given date range.';
    public $discoverable = false;
    public $report_set = 'Cashiering';

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $d1 = FormLib::get('date1', date('Y-m-d'));
        $d2 = FormLib::get('date2', date('Y-m-d'));
        $emp_no = FormLib::get('emp_no', false);
        
        $dlog = DTransactionsModel::selectDlog($d1,$d2);
        
        $data = array( array( array() ) );
        for ($i=1; $i<7; $i++) {
            $data[$i] = array( 
                'tenders' => array(
                    'CA' => array(0, 0),
                    'EF' => array(0, 0),
                    'RC' => array(0, 0),
                    'TC' => array(0, 0),
                    'EC' => array(0, 0),
                    'IC' => array(0, 0),
                    'GD' => array(0, 0),
                    'RC' => array(0, 0),
                    'SC' => array(0, 0),
                    'TK' => array(0, 0),
                    'CK' => array(0, 0),
                    'MI' => array(0, 0)
                ),
                'cashiers' => array(
                    'name' => array(),
                    'no' => array()
                )
            );
        }
        
        $query = '
            SELECT sum(d.total) as amount,
                d.emp_no,
                d.trans_subtype,
                d.register_no,
                e.FirstName
            FROM ' . $dlog . ' AS d
                LEFT JOIN employees AS e ON e.emp_no=d.emp_no
            WHERE (
                d.trans_subtype=\'CA\'
                OR d.trans_subtype=\'MI\'
                OR d.trans_subtype=\'EF\'
                OR d.trans_subtype=\'RC\'
                OR d.trans_subtype=\'TC\'
                OR d.trans_subtype=\'EC\'
                OR d.trans_subtype=\'IC\'
                OR d.trans_subtype=\'GD\'
                OR d.trans_subtype=\'SC\'
                OR d.trans_subtype=\'TK\'
                OR d.trans_subtype=\'CK\'
                )
                AND d.tdate BETWEEN ? AND ?
        ';
        if ($emp_no) {
            $query .= ' AND emp_no=' . $emp_no;
        }
        $query .= ' GROUP BY d.emp_no, d.trans_subtype
            ORDER BY d.emp_no
        ';
        $statement = $dbc->prepare($query);
        $args = array($d1.' 00:00:00', $d2.' 23:59:59');
        $result = $dbc->execute($statement, $args);
        $cashierNames = array();
        while ($row = $dbc->fetch_row($result)) {
            $data[$row['register_no']]['tenders'][$row['trans_subtype']][0] += $row['amount'];
            $data[$row['register_no']]['cashiers']['name'][] = $row['FirstName'];
            $data[$row['register_no']]['cashiers']['no'][]  = $row['emp_no'];
            if (!isset($cashierNames[$row['emp_no']])) {
                $cashierNames[$row['emp_no']] = $row['FirstName'];
            }
            sort($cashierNames);
        }
        
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['OverShortDatabase']);
        $query = '
            SELECT sum(amt) as amount,
                emp_no AS lane,
                tender_type
            FROM dailyCounts
            WHERE (
                tender_type=\'CA\'
                OR tender_type=\'MI\'
                OR tender_type=\'EF\'
                OR tender_type=\'RC\'
                OR tender_type=\'TC\'
                OR tender_type=\'EC\'
                OR tender_type=\'IC\'
                OR tender_type=\'GD\'
                OR tender_type=\'SC\'
                OR tender_type=\'TK\'
                OR tender_type=\'CK\'
                )
                AND date BETWEEN ? AND ? 
            GROUP BY lane, tender_type
        ';
        $statement = $dbc->prepare($query);
        $args = array($d1.' 00:00:00', $d2.' 23:59:59');
        $result = $dbc->execute($statement, $args);
        while ($row = $dbc->fetch_row($result)) {
            $data[$row['lane']]['tenders'][$row['tender_type']][1] += $row['amount'];
        }
        
        $ret = array( array() );
        foreach ($cashierNames as $key => $value) {
            for ($i=1; $i<7; $i++) {
                if (in_array($key, $data[$i]['cashiers']['no'])) {
                    if (!isset($ret[$key][0])) $ret[$key][0] = '';
                    if (!isset($ret[$key][1])) $ret[$key][1] = '';
                    for ($i=2; $i<13; $i++) {
                        if (!isset($ret[$key][$i])) $ret[$key][$i] = 0;
                    }
                    $ret[$key][0] = $value;
                    $ret[$key][1] .= $i . ", ";
                    $ret[$key][2] += $data[$i]['tenders']['CA'][0] + $data[$i]['tenders']['CA'][1];
                    $ret[$key][3] += $data[$i]['tenders']['MI'][0] + $data[$i]['tenders']['MI'][1];
                    $ret[$key][4] += $data[$i]['tenders']['TC'][0] + $data[$i]['tenders']['TC'][1];
                    $ret[$key][5] += $data[$i]['tenders']['EF'][0] + $data[$i]['tenders']['EF'][1];
                    $ret[$key][6] += $data[$i]['tenders']['RC'][0] + $data[$i]['tenders']['RC'][1];
                    $ret[$key][7] += $data[$i]['tenders']['TC'][0] + $data[$i]['tenders']['TC'][1];
                    $ret[$key][8] += $data[$i]['tenders']['IC'][0] + $data[$i]['tenders']['IC'][1];
                    $ret[$key][9] += $data[$i]['tenders']['GD'][0] + $data[$i]['tenders']['GD'][1];
                    $ret[$key][10] += $data[$i]['tenders']['SC'][0] + $data[$i]['tenders']['SC'][1];
                    $ret[$key][11] += $data[$i]['tenders']['TK'][0] + $data[$i]['tenders']['TK'][1];
                    $ret[$key][12] += $data[$i]['tenders']['CK'][0] + $data[$i]['tenders']['CK'][1];
                }
            }
        }
        
        return $ret;  
    }
    
    function form_content()
    {
        global $FANNIE_URL;
        ob_start();
?>
<form method = "get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<div class="col-sm-4">
    <div class="form-group">
        <label>Date Start</label>
        <input type=text id=date1 name=date1 class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>End Start</label>
        <input type=text id=date2 name=date2 class="form-control date-field" required />
    </div>
    <div class="form-group">
        <input type="checkbox" name="excel" id="excel" value="xls" />
        <label for="excel">Excel</label>
    </div>
    <div class="form-group">
        <label>Cashier#</label><i> optional</i>
        <input type=text name=emp_no id=emp_no  class="form-control" />
    </div>
    <p>
        <button type=submit class="btn btn-default btn-submit">Submit</button>
        <button type=reset class="btn btn-default btn-reset">Start Over</button>
    </p>
</div>
<div class="col-sm-4">
    <?php echo FormLib::date_range_picker(); ?>
</div>
</form>
<?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Select a date range and cashier <i>(opt.)</i>  
        to calculate discrepancies between POS and counted 
        totals.
            </p>';
    }
}

FannieDispatch::conditionalExec();

