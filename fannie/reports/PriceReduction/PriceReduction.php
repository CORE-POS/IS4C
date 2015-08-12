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

class PriceReduction extends FannieReportPage 
{
    public $description = '[Price Reduction Report] lists items above desired margin';
    public $report_set = 'Reports';
    public $themed = true;

    protected $report_headers = array('UPC', 'Description', 'Cost', 'Price', 'Margin Goal', 'actMarg', '+/- Marg', 'SRP', 'RoundSRP', '+/-:Price');
    protected $sort_direction = 1;
    protected $title = "Fannie : Price Reduction Report";
    protected $header = "Price Reduction Report";
    protected $required_fields = array('degree');

    public function fetch_report_data()
    {        
        $count = 0;
        $item = array();       
        $upc = array();
        $desc = array(); 
        $cost = array(); 
        $price = array(); 
        $marg = array(); 
        $var = array();
        $srp = array();
        $movement = array();
        $dept = array();
        $deptID = array();
        $vendor = array();
        $deptMarg = array();    //The Margin We're Using
        $devMarg = array();
        $devPrice = array();
        $uMarg = array();       //UNFI margin
        $dMarg = array();       //Department margin
        $roundSRP = array();
        
        // Connect
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        // Create List of Items
        $query = "SELECT P.upc, P.description, P.cost, P.normal_price, P.department, 
                P.modified, V.vendorDept, V.vendorID, D.margin as uMarg, D.vendorID, 
                D.deptID, S.margin as dMarg, sum(L.quantity)
                FROM is4c_op.products as P
                LEFT JOIN vendorItems as V ON P.upc = V.upc
                LEFT JOIN vendorDepartments as D ON (D.vendorID = V.vendorID) and (D.deptID = V.vendorDept) 
                LEFT JOIN is4c_trans.dlog_90_view as L ON (L.upc = V.upc)
                LEFT JOIN departments as S ON (P.department = S.dept_no)
                WHERE P.inUse = 1 and P.price_rule_id = 0 
                    AND P.cost <> 0
                GROUP BY P.upc 
                ORDER BY P.modified
                ;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
        if ($_POST['dept'] == 1) {
            if ( ($row['department'] >= 1 && $row['department'] <= 25) || ($row['department'] >= 239 && $row['department'] <= 259) ) {
                $upc[] = $row['upc'];
                $desc[] = $row['description'];
                $cost[] = $row['cost'];
                $price[] = $row['normal_price'];
                $dept[] = $row['department'];
                $uMarg = $row['uMarg'];
                $dMarg = $row['dMarg'];
                if($uMarg == NULL) { 
                    $deptMarg[] = $dMarg;
                } else {
                    $deptMarg[] = $uMarg;
                }
            }
        } else if ($_POST['dept'] == 2) {
            if ($row['department'] >= 1 && $row['department'] <= 25) {
                $upc[] = $row['upc'];
                $desc[] = $row['description'];
                $cost[] = $row['cost'];
                $price[] = $row['normal_price'];
                $dept[] = $row['department'];
                
                $uMarg = $row['uMarg'];
                $dMarg = $row['dMarg'];
                if($uMarg == NULL) { 
                    $deptMarg[] = $dMarg;
                } else {
                    $deptMarg[] = $uMarg;
                }
            } 
        } else if ($_POST['dept'] == 3) {
            if ($row['department'] >= 151 && $row['department'] <= 191) {
                $upc[] = $row['upc'];
                $desc[] = $row['description'];
                $cost[] = $row['cost'];
                $price[] = $row['normal_price'];
                $dept[] = $row['department'];
                
                $uMarg = $row['uMarg'];
                $dMarg = $row['dMarg'];
                if($uMarg == NULL) { 
                    $deptMarg[] = $dMarg;
                } else {
                    $deptMarg[] = $uMarg;
                }
            }
        } else if ($_POST['dept'] == 4) {
            if ($row['department'] >= 86 && $row['department'] <= 128) {
                $upc[] = $row['upc'];
                $desc[] = $row['description'];
                $cost[] = $row['cost'];
                $price[] = $row['normal_price'];
                $dept[] = $row['department'];
                
                $uMarg = $row['uMarg'];
                $dMarg = $row['dMarg'];
                if($uMarg == NULL) { 
                    $deptMarg[] = $dMarg;
                } else {
                    $deptMarg[] = $uMarg;
                }
            }
        } else if ($_POST['dept'] == 5) {
            if ($row['department'] >= 240 && $row['department'] <= 250) {
                $upc[] = $row['upc'];
                $desc[] = $row['description'];
                $cost[] = $row['cost'];
                $price[] = $row['normal_price'];
                $dept[] = $row['department'];
                
                $uMarg = $row['uMarg'];
                $dMarg = $row['dMarg'];
                if($uMarg == NULL) { 
                    $deptMarg[] = $dMarg;
                } else {
                    $deptMarg[] = $uMarg;
                }
            }
        }
        
            
            

        }
        echo count($upc) . " items found<br>";

        $rounder = new \COREPOS\Fannie\API\item\PriceRounder();
        // Calculations
        for($i=0; $i<count($upc); $i++) {
            $marg[] = ($price[$i] - $cost[$i]) / $price[$i];
            $devMarg[] = $marg[$i] - $deptMarg[$i];
            $desiredPrice = 0;
            $desiredPrice = $cost[$i] / (1 - $deptMarg[$i]);
            $srp[] = $desiredPrice;
            $roundSRP[] = $rounder->round($desiredPrice);
            $devPrice[] = $price[$i] - $srp[$i];
        }
    
        for ($i = 0; $i < count($upc); $i++){
            if( ($upc[$i] != NULL) && ($srp[$i] > 0) && ($devPrice[$i] > 0) && ($devMarg[$i] >= $_POST['degree']) ) {
                $item[] = array(
                    $upc[$i],
                    $desc[$i],
                    $cost[$i],
                    $price[$i],
                    sprintf('%.3f%%', $deptMarg[$i]*100),
                    sprintf('%.3f%%', $marg[$i]*100),
                    sprintf('%.3f%%', $devMarg[$i]*100),
                    sprintf('%.2f', $srp[$i]),
                    $roundSRP[$i],
                    sprintf('%.2f', $devPrice[$i]),
                );
            }
        }
        
        return $item;
    }
    
    public function form_content()
    {
        $this->add_onload_command('$(\'#startdate\').focus()');
        return '<form method="post" id="form1">
            <label>Select %/degree to check margins to</label>
            <input type="text" name="degree" value="" class="form-control"
                required id="degree" />
            <select form="form1" name="dept">
                <option value="1">Bulk</option>
                <option value="2">Cool</option>
                <option value="3">Grocery</option>
                <option value="4">Wellness</option>                
                <option value="5">General Merch</option>                
            </select>
            <p>
            <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </form>';
    }
    
    public function helpContent()
    {
        return '<p>
            This report reviews every item currently in use on sales floor 
            and checks the actual margin against our desired margin in order
            to locate items that may be reduced in price.
            </p>';
    }

}

FannieDispatch::conditionalExec();

