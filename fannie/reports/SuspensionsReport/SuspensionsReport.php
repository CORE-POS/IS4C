<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SuspensionsReport extends FannieReportPage 
{

    protected $title = "Fannie : Suspensions";
    protected $header = "Suspensions Report";
    protected $report_headers = array('Mem#', 'Type', 'Date', 'Reason(s)', 'Last Name', 'First Name',
                                      'Address', 'City', 'State', 'Zip', 'Phone');
    protected $required_fields = array('date1', 'date2');
    protected $report_cache = 'none';

    public $description = '[Suspensions] lists all members made inactive during a given date range';
    public $report_set = 'Membership';
    public $themed = true;

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $code = FormLib::get_form_value('reason','0');

        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59');
        $query = 'SELECT s.cardno as card_no,
                    s.type as sus_type,
                    s.suspDate,
                    r.textStr
                  FROM suspensions AS s
                    LEFT JOIN reasoncodes AS r ON s.reasoncode & r.mask <> 0
                  WHERE s.suspDate BETWEEN ? AND ?';
        if ($code != 0) {
            $args[] = $code;
            $query .= ' AND s.reasoncode & ? <> 0 ';
        }
        $query .= ' ORDER BY s.cardno';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);

        $data = array();
        $num = $dbc->num_rows($res);
        $record = array(0,1,2,'',4,5,6,7,8,9,10);
        for($i=0;$i<$num;$i++) {
            $row = $dbc->fetch_row($res);
            if ($row['card_no'] != $record[0] && $record[0] != 0) {
                $record[3] = substr($record[3], 0, strlen($record[3])-2);
                $account = \COREPOS\Fannie\API\member\MemberREST::get($record[0]);
                $record[6] = $account['addressFirstLine'];
                $record[7] = $account['city'];
                $record[8] = $account['state'];
                $record[9] = $account['zip'];
                foreach ($account['customers'] as $customer) {
                    $record[4] = $customer['lastName'];
                    $record[5] = $customer['firstName'];
                    $record[10] = $customer['phone'];
                }
                $data[] = $record;
                $record = array(0,1,2,'',4,5,6,7,8,9,10);
            }
            $record[0] = $row['card_no'];
            $record[1] = $row['sus_type'] == 'I' ? 'INACTIVE' : 'TERMED';
            $record[2] = $row['suspDate'];
            $record[3] .= $row['textStr'] . ', ';

            $last_row = ($i == $num-1) ? true : false;
            if ($last_row) {
                $record[3] = substr($record[3], 0, strlen($record[3])-2);
                $account = \COREPOS\Fannie\API\member\MemberREST::get($record[0]);
                $record[6] = $account['addressFirstLine'];
                $record[7] = $account['city'];
                $record[8] = $account['state'];
                $record[9] = $account['zip'];
                foreach ($account['customers'] as $customer) {
                    $record[4] = $customer['lastName'];
                    $record[5] = $customer['firstName'];
                    $record[10] = $customer['phone'];
                }
                $data[] = $record;
            }
        }

        return $data;
    }

    function report_description_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $reason = 'Any Reason';
        $mask = FormLib::get('reason', 0);
        if ($mask != 0) {
            $code = new ReasoncodesModel($dbc);
            $code->mask($mask);
            if ($code->load()) {
                $reason = $code->textStr();
            } else {
                $reason = 'Unknown?';
            }
        }
        return array(
            'Accounts suspended for: ' . $reason,
        );
    }
    
    function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $codes = new ReasoncodesModel($dbc);
        ob_start();
?>
<form method = "get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<div class="col-sm-4">
    <div class="form-group"> 
        <label>Reason</label>
        <select name="reason" class="form-control">
            <option value="0">Any Reason</option>
            <?php foreach($codes->find() as $obj) {
                printf('<option value="%d">%s</option>',
                        $obj->mask(),
                        $obj->textStr()
                );
            } ?>
        </select>
    </div>
    <div class="form-group"> 
        <label>Date Start</label>
        <input type=text id=date1 name=date1 required
            class="form-control date-field" />
    </div>
    <div class="form-group"> 
        <label>Date End</label>
        <input type=text id=date2 name=date2 required
            class="form-control date-field" />
    </div>
    <div class="form-group"> 
        <input type="checkbox" name="excel" id="excel" value="xls" />
        <label for="excel">Excel</label>
    </div>
    <div class="form-group"> 
        <button type=submit name=submit value="Submit"
            class="btn btn-default">Submit</button>
        <button type=reset name=reset value="Start Over"
            class="btn btn-default">Start Over</button>
    </div>
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
        return '<p>
            List members whose status was changed to
            inactive during the given date range.
            </p>';
    }
}

FannieDispatch::conditionalExec();

