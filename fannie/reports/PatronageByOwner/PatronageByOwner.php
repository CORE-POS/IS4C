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

class PatronageByOwner extends FannieReportPage 
{
    public $description = '[Patronage] lists top patrons by purchases/avg basket';
    public $report_set = 'Membership :: Patronage';
    public $themed = true;

    protected $report_headers = array('Owner', 'Total Purchased', 'Avg Bskt', 'Transactions');
    protected $sort_direction = 1;
    protected $title = "Fannie : Patronage by Owner Report";
    protected $header = "Patronage by Owner Report";
    protected $required_fields = array('num');

    public function report_description_content()
    {
        return array('Patronage');
    }

    public function fetch_report_data()
    {
        $card_no = array();
        $id = array();   
        $total = array();       // Total Spent for desired Range
        $avg = array();         // Average Basket
        $numTran = array();     // Number of transactions for selected Range for each Owner
        
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        
        $query = "SELECT card_no, sum(unitPrice*quantity) as T  
                FROM dlog_90_view
                WHERE upc != 0
                GROUP BY card_no 
                ORDER BY T desc limit " . ((int)$this->form->num);
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            $id[] = count($card_no);
            $card_no[] = $row['card_no'];
            $total[] = $row['T'];  
        }     
    
        for($i=0; $i<count($card_no);$i++) {
            $query = "SELECT trans_num, month(tdate) as mt, day(tdate) as dt, year(tdate) as yt 
                    FROM dlog_90_view 
                    WHERE card_no = $card_no[$i] and upc = 'tax'
                    GROUP BY trans_num, mt, dt, yt 
                    ORDER BY mt, dt, yt
                    ;";
            $result = $dbc->query($query);
            $count = 0;
            while ($row = $dbc->fetch_row($result)) {
                $count++;
            }
            $numTran[] = $count;
        }
        
        $info = array();
        for($i=0; $i<count($card_no);$i++) {
            $table_row = array(
                $card_no[$i],
                $total[$i],
                $total[$i] / $numTran[$i],
                $numTran[$i],
            );
            $info[] = $table_row;
        }
        
        return $info;
    }

    public function form_content()
    {
        $this->add_onload_command('$(\'#num\').focus()');
        return '<form method="post" action="PatronageByOwner.php" id="form1">
            <label>Enter Range (# of owners)</label>
            <input type="text" name="num" value="" class="form-control"
                required id="num" />
            <p>
            <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            View "best" patrons by total spent for the last 3 months/
            Enter the desired member range of patrons to sort.
            </p>';
    }

}

FannieDispatch::conditionalExec();

