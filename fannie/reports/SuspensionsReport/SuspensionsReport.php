<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
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

    function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $code = FormLib::get_form_value('reason','0');

        $args = array($date1 . ' 00:00:00', $date2 . ' 23:59:59');
        $query = 'SELECT s.cardno as card_no,
                    s.type as sus_type,
                    s.suspDate,
                    r.textStr,
                    c.LastName,
                    c.FirstName,
                    m.street,
                    m.city,
                    m.state,
                    m.zip,
                    m.phone
                  FROM suspensions AS s
                    LEFT JOIN custdata AS c ON s.cardno=c.CardNo AND c.personNum=1
                    LEFT JOIN meminfo AS m ON s.cardno=m.card_no
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
                $data[] = $record;
                $record = array(0,1,2,'',4,5,6,7,8,9,10);
            }
            $record[0] = $row['card_no'];
            $record[1] = $row['sus_type'] == 'I' ? 'INACTIVE' : 'TERMED';
            $record[2] = $row['suspDate'];
            $record[3] .= $row['textStr'] . ', ';
            $record[4] = $row['LastName'];
            $record[5] = $row['FirstName'];
            $record[6] = $row['street'];
            $record[7] = $row['city'];
            $record[8] = $row['state'];
            $record[9] = $row['zip'];
            $record[10] = $row['phone'];

            $last_row = ($i == $num-1) ? true : false;
            if ($last_row) {
                $record[3] = substr($record[3], 0, strlen($record[3])-2);
                $data[] = $record;
            }
        }

        return $data;
    }

    function report_description_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
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
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $codes = new ReasoncodesModel($dbc);
?>
<div id=main>   
<form method = "get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr> 
            <th>Reason</th>
            <td>
            <select name="reason">
            <option value="0">Any Reason</option>
            <?php foreach($codes->find() as $obj) {
                printf('<option value="%d">%s</option>',
                        $obj->mask(),
                        $obj->textStr()
                );
            } ?>
            </select>
            </td>
            <td>
            <input type="checkbox" name="excel" id="excel" value="xls" />
            <label for="excel">Excel</label>
            </td>   
        </tr>
        <tr>
            <th>Date Start</th>
            <td>    
                       <input type=text size=14 id=date1 name=date1 />
            </td>
            <td rowspan="3">
            <?php echo FormLib::date_range_picker(); ?>
            </td>
        </tr>
        <tr>
            <th>End</th>
            <td>
                        <input type=text size=14 id=date2 name=date2 />
               </td>

        </tr>
        <tr>
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
        </tr>
    </table>
</form>
</div>
<?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');
    }
}

FannieDispatch::conditionalExec(false);

?>
