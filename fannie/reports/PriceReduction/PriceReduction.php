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
    public $description = '[Price Reduction Report] lists items above desired margin';
    public $report_set = 'Reports';
    public $themed = true;

    protected $report_headers = array('UPC', 'Description', 'Cost', 'Price', 'actMarg', '+/- Marg', 'SRP', '+/-:Price');
    protected $sort_direction = 1;
    protected $title = "Fannie : Price Reduction Report";
    protected $header = "Price Reduction Report";
    //protected $required_fields = array('startdate');

    public function report_description_content()
    {
        return array('Items located in system: ');
    }

    public function fetch_report_data()
    {        
        $count = 0;
        $info = array(    
            $upc = array(),
            $desc = array(),   
            $cost = array(), 
            $price = array(), 
            $marg = array(), 
            $var = array(),
            $srp = array(),
            $movement = array(),
            $dept = array(),
            $deptID = array(),
            $vendor = array(),
            $deptMarg = array(),    //The Margin We're Using
            $devMarg = array(),
            $devPrice = array(),
            $uMarg = array(),       //UNFI margin
            $dMarg = array(),       //Department margin
        );       

        // Connect
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        // Create List of Items
        $query = "SELECT P.upc, P.description, P.cost, P.normal_price, P.department, 
                P.modified, V.vendorDept, V.vendorID, D.margin as uMarg, D.vendorID, D.deptID, S.margin as dMarg, sum(L.quantity)
                FROM is4c_op.products as P
                LEFT JOIN vendorItems as V ON P.upc = V.upc
                LEFT JOIN vendorDepartments as D ON (D.vendorID = V.vendorID) and (D.deptID = V.vendorDept) 
                LEFT JOIN is4c_trans.dlog_90_view as L ON (L.upc = V.upc)
                LEFT JOIN departments as S ON (P.department = S.dept_no)
                WHERE P.inUse = 1 and P.price_rule_id = 0 
                GROUP BY P.upc 
                ORDER BY P.modified
                ;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            $upc[] = $row['upc'];
            $desc[] = $row['description'];
            $cost[] = $row['cost'];
            $price[] = $row['normal_price'];
            $dept[] = $row['department'];
            
            $uMarg = $row['uMarg'];
            $dMarg = $row['dMarg'];
            if($uMarg < 0.01) { 
                $deptMarg[] = $dMarg;
            } else {
                $deptMarg[] = $uMarg;
            }
            // add movement (sales) of items

        }
        echo mysql_errno() . ": " . mysql_error(). "<br>";
        echo count($upc) . " items found<br>";

        // Calculations
        for($i=0; $i<count($upc); $i++) {
            $marg[] = ($price[$i] - $cost[$i]) / $price[$i];
            $devMarg[] = $marg[$i] - $deptMarg[$i];
            $desiredPrice = 0;
            $desiredPrice = $cost[$i] / (1 - $deptMarg[$i]);
            $srp[] = $desiredPrice; //instead of $desiredPrice, we should have "$srp[] = $rounder->round($desiredPrice);" 
            $devPrice[] = $price[$i] - $srp[$i];
        }
    
        return $info;
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

