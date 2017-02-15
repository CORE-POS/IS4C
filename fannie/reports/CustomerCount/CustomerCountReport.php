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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    * 03Apr13 AT incorporated changes below in class-based version
    *
    * 13Feb13 Eric Lee This report counts the number of transaction, not the
    *          number of, possibly unique, customers.
    *         Correct handling of date, memType, trans_num in various tables/views.
    *         Get member Type headings from table, except for "WFC"
    *         Add a report heading.
    *         Change WHERE trans_type from "in ('I','D')" to "= 'T'" because card_no,
    *          which is needed to derive memType is always available there but
    *          sometimes not for I and D items. Day and Grand totals are the same
    *          but member type counts more accurate with T.
*/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CustomerCountReport extends FannieReportPage {

    private $memtypes;
    protected $title = "Fannie : Customer Report";
    protected $header = "Customer Report";
    protected $report_cache = 'day';
    protected $required_fields = array('date1', 'date2');

    public $description = '[Customer Count] lists the number of customers per day, separated by membership type.';
    public $themed = true;
    public $report_set = 'Membership';

    function preprocess()
    {
        // dynamic column headers
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $typeQ = $dbc->prepare("SELECT memtype,memDesc FROM memtype ORDER BY memtype");
        $typeR = $dbc->execute($typeQ);
        $this->memtypes = array();
        $this->report_headers = array('Date');
        while($typeW = $dbc->fetch_row($typeR)){
            $this->report_headers[] = $typeW['memDesc'];
            $this->memtypes[$typeW['memtype']] = $typeW['memDesc'];
        }
        $this->report_headers[] = 'Total';

        return parent::preprocess();
    }

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $store = FormLib::get('store', 0);

        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        $date1 .= ' 00:00:00';
        $date2 .= ' 23:59:59';

        $sales = "SELECT year(tdate) as year, month(tdate) as month,
            day(tdate) as day,max(memType) as memType,trans_num
            FROM $dlog as t
            WHERE 
            tdate BETWEEN ? AND ?
            and trans_type = 'T'
            AND upc <> 'RRR'
            AND " . DTrans::isStoreID($store, 't') . "
            group by year(tdate),month(tdate),day(tdate),trans_num
            order by year(tdate),month(tdate),day(tdate),max(memType)";
        $salesP = $dbc->prepare($sales);
        $result = $dbc->execute($salesP, array($date1, $date2, $store));

        /**
          Create result records based on date and increment them
          when the same type is encountered again
        */
        $ret = array();
        while ($row = $dbc->fetchRow($result)){
            $stamp = date("M j, Y",mktime(0,0,0,$row['month'],$row['day'],$row['year']));
            if (!isset($ret[$stamp])){ 
                $ret[$stamp] = array("date"=>$stamp);
                foreach($this->memtypes as $id=>$desc)
                    $ret[$stamp][$id] = 0;
                $ret[$stamp]['ttl'] = 0;
            }
            $ret[$stamp]["ttl"]++;
            if (!isset($ret[$stamp][$row['memType']]))
                $ret[$stamp][$row['memType']] = 0;
            $ret[$stamp][$row['memType']]++;
        }
        $ret = $this->dekey_array($ret);
        return $ret;
    }
    
    /**
      Sum the quantity and total columns
    */
    function calculate_footers($data){
        $sum = 0;
        $num_columns = isset($data[0]) ? count($data[0]) : 0;
        foreach($data as $row){
            $sum += $row[$num_columns-1];
        }
        $ret = array('Grand Total');
        for ($i=0; $i<$num_columns-2; $i++) {
            $ret[] = null;
        }
        $ret[] = $sum;

        return $ret;
    }

    function form_content()
    {
        list($lastMonday, $lastSunday) = \COREPOS\Fannie\API\lib\Dates::lastWeek();
        $store = FormLib::storePicker();
        ob_start();
?>
<form action=CustomerCountReport.php method=get>
<div class="col-sm-6">
<p>
    <label>Start Date</label>
    <input type=text id=date1 name=date1 value="<?php echo $lastMonday; ?>"
        class="form-control date-field" required />
</p>
<p>
    <label>End Date</label>
    <input type=text id=date2 name=date2 value="<?php echo $lastSunday; ?>" 
        class="form-control date-field" required />
</p>
<p>
    <label>Store</label>
    <?php echo $store['html']; ?>
</p>
<p>
    <button type=submit name=submit value="Submit" class="btn btn-default">Submit</button>
    <label><input type=checkbox name=excel id="excel" value=xls /> Excel</label>
</p>
</div>
<div class="col-sm-6">
    <?php echo FormLib::dateRangePicker(); ?>
</div>
</form>
<?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>This report lists the number of transactions per day
            broken down by member type. This is the number of <em>total</em>
            customers not the number of <em>unique</em>. If the same member
            shops twice in one day that\'s two transactions.</p>';
    }
}

FannieDispatch::conditionalExec();

