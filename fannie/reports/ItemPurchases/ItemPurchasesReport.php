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

class ItemPurchasesReport extends FannieReportPage 
{

    protected $title = "Fannie : Item Purchases";
    protected $header = "Item Purchases Report";
    protected $report_headers = array('Date','Receipt#','Total ($)','Owner#','Name');
    protected $required_fields = array('date1', 'date2');
    protected $report_cache = 'day';

    public $description = '[Item Purchases] lists each transaction containing a particular item';

    function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $upc = FormLib::get_form_value('upc','0');
        if (is_numeric($upc))
            $upc = BarcodeLib::padUPC($upc);

        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        $lookupTrans = 'SELECT register_no, emp_no, trans_no,
                            YEAR(tdate) AS year,
                            MONTH(tdate) AS month,
                            DAY(tdate) AS day
                        FROM ' . $dlog . ' AS d
                        WHERE upc=?
                        GROUP BY register_no, emp_no, trans_no,
                            YEAR(tdate),
                            MONTH(tdate),
                            DAY(tdate)
                        HAVING SUM(total) <> 0';
        $lookupP = $dbc->prepare($lookupTrans);
        $lookupR = $dbc->execute($lookupP, array($upc));

        $data = array();
        // get trans-specific info
        $transQ = 'SELECT register_no, emp_no, trans_no, 
                    YEAR(tdate) AS year,
                    MONTH(tdate) AS month,
                    DAY(tdate) AS day,
                    MAX(card_no) AS card_no,
                    SUM(CASE WHEN trans_type IN (\'I\',\'D\') THEN total ELSE 0 END) as ttl,
                    MAX(LastName) AS ln,
                    MAX(FirstName) as fn
                   FROM ' . $dlog . ' AS d
                    LEFT JOIN custdata AS c ON d.card_no=c.CardNo AND c.personNum=1
                   WHERE tdate BETWEEN ? AND ?
                    AND register_no=?
                    AND emp_no=?
                    AND trans_no=? 
                   GROUP BY register_no, emp_no, trans_no,
                    YEAR(tdate),
                    MONTH(tdate),
                    DAY(tdate)';
        $transP = $dbc->prepare($transQ);
        while ($row = $dbc->fetch_row($lookupR)) {
            $args = array(
                date('Y-m-d 00:00:00', mktime(0,0,0,$row['month'],$row['day'],$row['year'])),
                date('Y-m-d 23:59:59', mktime(0,0,0,$row['month'],$row['day'],$row['year'])),
                $row['register_no'],
                $row['emp_no'],
                $row['trans_no'],
            );
            $trans = $dbc->execute($transP, $args); 
            if ($dbc->num_rows($trans) == 0) {
                continue; // trans not found? shouldn't really happen
            }
            $info = $dbc->fetch_row($trans);
            $date = date('Y-m-d', mktime(0,0,0,$info['month'],$info['day'],$info['year']));
            $trans_num = $info['emp_no'] . '-' . $info['register_no'] . '-' . $info['trans_no'];
            $record = array(
                $date,
                $trans_num,
                sprintf('%.2f', $info['ttl']),
                $info['card_no'],
                $info['ln'] . ', ' . $info['fn'],
            );
            $data[] = $record;
        }

        return $data;
    }
    
    function report_description_content()
    {
        return array(
            'Transactions containing UPC ' . FormLib::get('upc'),
        );
    }
    
    function form_content(){
?>
<div id=main>   
<form method = "get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr> 
            <th>UPC</th>
            <td>
            <input type=text name=upc size=14 id=upc  />
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
        $this->add_onload_command("\$('#date1').datepicker({dateFormat:'yy-mm-dd'});\n");
        $this->add_onload_command("\$('#date2').datepicker({dateFormat:'yy-mm-dd'});\n");
    }
}

FannieDispatch::conditionalExec(false);

?>
