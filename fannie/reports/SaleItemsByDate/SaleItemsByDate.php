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

class SaleItemsByDate extends FannieReportPage 
{
    public $description = '[Sale Items/Batch Start Date] lists all sales items with a given start date';
    public $report_set = 'Batches';
    public $themed = true;

    protected $report_headers = array('Brand', 'Description', 'Size', 'Sale Price', 'UPC');
    protected $sort_direction = 1;
    protected $title = "Fannie : Sale Items by Start-Date Report";
    protected $header = "List Sale Items by Batch Date Report";
    protected $required_fields = array('startdate');

    public function report_description_content()
    {
        return array('Items from batches beginning on ');
    }

    public function fetch_report_data()
    {        
        $item = array();
        $batchID = array();
        $upc = array();
        $salePrice = array();
        $owner = array();
        $size = array();
        
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        
        //procure batchIDs from 'batches'
        $query = "select batchID, owner from batches where startDate='{$this->form->startdate} 00:00:00';";
        
        if(FormLib::get('dept') == 2) {
            $query = "select batchID, owner from batches where startDate='{$this->form->startdate} 00:00:00' and (owner='Bulk' or owner='BULK');";
        }
        if(FormLib::get('dept') == 3) {
            $query = "select batchID, owner from batches where startDate='{$this->form->startdate} 00:00:00' and (owner='Cool' or owner='COOL');";   
        }
        if(FormLib::get('dept') == 4){
            $query = "select batchID, owner from batches where startDate='{$this->form->startdate} 00:00:00' and (owner='Grocery' or owner='GROCERY');"; 
        }
        if(FormLib::get('dept') == 5){
            $query = "select batchID, owner from batches where startDate='{$this->form->startdate} 00:00:00' and (owner='HBC');";   
        }
        
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            $batchID[] = $row['batchID'];
            $owner[] = $row['owner'];
        }     

        echo count($batchID) . " batches found\n";
    
        //procure upcs from 'batchList' --this is going to pull every upc of every item that is going on sale
        for ($i = 0; $i < count($batchID); $i++){
            $query = "SELECT upc, salePrice 
            FROM batchList where batchID='$batchID[$i]';
            ";   
            $result = $dbc->query($query);
                while ($row = $dbc->fetch_row($result)) {
                    $upc[] = $row['upc'];
                    $salePrice[] = $row['salePrice'];
                }
            }
        echo count($upc) . " items found for this sales period <br>";

        //procure description of items based on 'upc's, and return their descriptions, organized by department and brand 
        for ($i = 0; $i < count($upc); $i++){
            $query = "SELECT p.upc, u.brand, u.description, v.size from products as p
                    LEFT JOIN productUser as u ON p.upc=u.upc
                    LEFT JOIN vendorItems as v ON v.upc=p.upc
                    WHERE p.upc = '$upc[$i]' order by 'brand';"; 
            $result = $dbc->query($query);
                while ($row = $dbc->fetch_row($result)) {
                    $item[$i][0] = $row['brand'];
                    $item[$i][1] = $row['description'];
                    $item[$i][2] = $row['size'];
                    $item[$i][3] = $salePrice[$i];
                    $item[$i][4] = $row['upc'];
                    
                }
        }
        sort($item);
        return $item;
    }

    public function form_content()
    {
        $this->add_onload_command('$(\'#startdate\').focus()');
        return '<form method="get" action="SaleItemsByDate.php" id="form1">
            <div class="form-group">
            <label>Enter Batch Start Date and Select a Department</label>
            <input type="text" name="startdate" value="" class="form-control date-field"
                required id="startdate" />
            </div>
            <div class="form-group">
            <select form="form1" name="dept" class="form-control">
                <option value="1">All Departments</option>
                <option value="2">Bulk</option>
                <option value="3">Cool</option>
                <option value="4">Grocery</option>
                <option value="5">Wellness</option>
            </select>
            </div>
            <p>
            <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            View all Accounts Receivable (AR) activity for a given member.
            Enter the desired member number.
            </p>';
    }

}

FannieDispatch::conditionalExec();

