<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class BasketLimitedReport extends FannieReportPage {

    public $description = '[Small Basket Report] lists sales for transactions containing a limited
    number of items - i.e., what do people buy when they\'re only purchasing one or two things?';

    protected $report_headers = array('UPC', 'Description', '# Trans', 'Qty', '$');
    protected $sort_column = 2;
    protected $sort_direction = 1;
    protected $report_cache = 'day';
    protected $title = "Fannie : Basket Limited Report";
    protected $header = "Basket Limited Report Report";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $qty = FormLib::get('qty', 1);

        $create = $dbc->prepare_statement("CREATE TABLE groupingTempBS (upc VARCHAR(13), quantity double, total decimal(10,2), trans_num varchar(50))");
        $dbc->exec_statement($create);

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $setupQ = $dbc->prepare_statement("INSERT INTO groupingTempBS
            SELECT upc, quantity, total, trans_num
            FROM $dlog AS d WHERE tdate BETWEEN ? AND ?
            AND trans_type IN ('I','D')
            GROUP BY year(tdate),month(tdate),day(tdate),trans_num 
            HAVING COUNT(*) <= ?");
        $dbc->exec_statement($setupQ,array($date1.' 00:00:00',$date2.' 23:59:59',$qty));

        $reportQ = $dbc->prepare_statement('
            SELECT g.upc,
                p.description,
                SUM(g.quantity) AS qty,
                COUNT(DISTINCT trans_num) AS num,
                SUM(total) AS ttl
            FROM groupingTempBS as g '
                . DTrans::joinProducts('g', 'p') . '
            GROUP BY g.upc,
                p.description
            HAVING sum(total) <> 0
            ORDER BY count(*) DESC
        ');
        $reportR = $dbc->exec_statement($reportQ);

        $data = array();
        while($w = $dbc->fetch_row($reportR)) {
            $record = array($w['upc'], 
                            empty($w['description']) ? 'n/a' : $w['description'], 
                            $w[3], 
                            sprintf('%.2f',$w[2]), 
                            sprintf('%.2f',$w[4]));
            $data[] = $record;
        }

        $drop = $dbc->prepare_statement("DROP TABLE groupingTempBS");
        $dbc->exec_statement($drop);

        return $data;
    }

    public function report_description_content()
    {
        return array(
            'Basket Size '.FormLib::get('qty', 1).' or less'
        );
    }
    
    public function form_content()
    {
        ob_start();
?>
<div id=main>
<form method ="get" action="BasketLimitedReport.php">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr> 
            <td> 
                <p><b>Size Limit (Qty)</b></p>
                <p><b>Excel</b></p>
            </td>
            <td>
                <p>
                <input type=text name=qty id=qty value="1"  />
                </p>
                <p>
                <input type=checkbox name=excel id=excel /> 
                </p>
            </td>
            <td>
                <p><b>Date Start</b> </p>
                <p><b>End</b></p>
            </td>
            <td>
                <p>
                <input type=text id=date1 name=date1 />
                </p>
                <p>
                <input type=text id=date2 name=date2 />
                </p>
            </td>
        </tr>
        <tr>
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
            <td colspan="2" rowspan="2">
                <?php echo FormLib::date_range_picker(); ?>
            </td>
        </tr>
    </table>
</form>
</div>
<?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

?>
