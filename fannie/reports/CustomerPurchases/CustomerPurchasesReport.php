<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class CustomerPurchasesReport extends FannieReportPage 
{
    public $description = '[Member Purchases] lists items purchased by a given member in a given date range.';
    public $report_set = 'Membership';

    protected $title = "Fannie : What Did I Buy?";
    protected $header = "What Did I Buy? Report";
    protected $report_headers = array('Date','UPC','Description','Dept','Cat','Qty','$');
    protected $required_fields = array('date1', 'date2');

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $FANNIE_OP_DB = $this->config->get('OP_DB');
        $dbc->selectDB($FANNIE_OP_DB);
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $card_no = FormLib::get_form_value('card_no','0');

        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        $query = "select month(t.tdate),day(t.tdate),year(t.tdate),
              t.upc,p.description,
              t.department,d.dept_name,m.super_name,
              sum(t.quantity) as qty,
              sum(t.total) as ttl from
              $dlog as t left join {$FANNIE_OP_DB}.products as p on t.upc = p.upc 
              left join {$FANNIE_OP_DB}.departments AS d ON t.department=d.dept_no
              left join {$FANNIE_OP_DB}.MasterSuperDepts AS m ON t.department=m.dept_ID
              where t.card_no = ? AND
              trans_type IN ('I','D') AND
              tdate BETWEEN ? AND ?
              group by year(t.tdate),month(t.tdate),day(t.tdate),
              t.upc,p.description
              order by year(t.tdate),month(t.tdate),day(t.tdate)";
        $args = array($card_no, $date1.' 00:00:00',$date2.' 23:59:59');
    
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);

        /**
          Simple report
        
          Issue a query, build array of results
        */
        $ret = array();
        while ($row = $dbc->fetchRow($result)){
            $ret[] = $this->rowToRecord($row);
        }
        return $ret;
    }

    private function rowToRecord($row)
    {
        $record = array();
        $record[] = $row[0]."/".$row[1]."/".$row[2];
        $record[] = $row['upc'];
        $record[] = $row['description'];
        $record[] = $row['department'].' '.$row['dept_name'];
        $record[] = $row['super_name'];
        $record[] = $row['qty'];
        $record[] = $row['ttl'];

        return $record;
    }

    function report_description_content()
    {
        $ret = array();
        $ret[] = "For owner #".FormLib::get_form_value('card_no');
        return $ret;
    }
    
    /**
      Sum the quantity and total columns
    */
    function calculate_footers($data){
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach($data as $row){
            $sumQty += $row[5];
            $sumSales += $row[6];
        }
        return array('Total',null,null,null,null,$sumQty,$sumSales);
    }

    function form_content()
    {
        ob_start();
?>
<form method = "get"> 
<div class="col-sm-4">
    <div class="form-group">
        <label><?php echo _('Owner #'); ?></label>
        <input type=text name=card_no id=card_no  class="form-control" />
    </div>
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
    <p>
        <button type=submit class="btn btn-default btn-core">Submit</button>
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
        return '<p>
            List items purchased by a given customer in a given date range.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array(0=>1, 1=>1, 2=>2000, 'upc'=>'4011',
            'description'=>'test', 'department'=>1, 'dept_name'=>'test',
            'super_name'=>'test', 'qty'=>1, 'ttl'=>1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
        $phpunit->assertInternalType('array', $this->calculate_footers($this->dekey_array(array($data))));
    }
}

FannieDispatch::conditionalExec();

