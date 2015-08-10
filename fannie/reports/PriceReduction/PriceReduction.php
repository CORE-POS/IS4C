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

    protected $report_headers = array('UPC', 'Description', 'Cost', 'Price', 'actMarg', '+/- Marg', 'SRP', 'RoundSRP', '+/-:Price');
    protected $sort_direction = 1;
    protected $title = "Fannie : Price Reduction Report";
    protected $header = "Price Reduction Report";
    protected $required_fields = array('degree');

    public function fetch_report_data()
    {        
        $count = 0;
        $item = array();       
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
        $roundSRP = array();

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

        $rounder = new \COREPOS\Fannie\API\item\PriceRounder();
        // Calculations
        for($i=0; $i<count($upc); $i++) {
            $marg[] = ($price[$i] - $cost[$i]) / $price[$i];
            $devMarg[] = $marg[$i] - $deptMarg[$i];
            $desiredPrice = 0;
            $desiredPrice = $cost[$i] / (1 - $deptMarg[$i]);
            $srp[] = $rounder->round($desiredPrice);
            $devPrice[] = $price[$i] - $srp[$i];
        }
    
        for ($i = 0; $i < count($upc); $i++){
            if( ($upc[$i] != NULL) && ($srp[$i] > 0) && ($devPrice[$i] > 0) && ($devPrice[$i] >= $_POST['degree']) ) {
                $item[$i][0] = $upc[$i];
                $item[$i][1] = $cost[$i];
                $item[$i][2] = $price[$i];
                $item[$i][3] = $marg[$i];
                $item[$i][4] = $devMarg[$i];
                $item[$i][5] = $srp[$i];
                $item[$i][6] = $roundSRP[$i];
                $item[$i][7] = $devPrice[$i];
            }
        }
        return $info;
    }
    
    public function form_content()
    {
        $this->add_onload_command('$(\'#startdate\').focus()');
        return '<form method="post" action="PriceRounder.php" id="form1">
            <label>Select %/degree to check margins to</label>
            <input type="text" name="degree" value="" class="form-control"
                required id="degree" />
            <select form="form1" name="dept">
                <option value="0.03">0.03</option>
                    <option value="0.01">0.01</option>
                        <option value="0.02">0.02</option>
                        <option value="0.03">0.03</option>
                        <option value="0.04">0.04</option>
                        <option value="0.05">0.05</option>
                        <option value="0.06">0.06</option>
                        <option value="0.07">0.07</option>
                        <option value="0.08">0.08</option>
                        <option value="0.09">0.09</option>
                        <option value="0.1">0.1</option>
                        <option value="0.11">0.11</option>
                        <option value="0.12">0.12</option>
                        <option value="0.13">0.13</option>
                        <option value="0.14">0.14</option>
                        <option value="0.15">0.15</option>
                        <option value="0.16">0.16</option>
                        <option value="0.17">0.17</option>
                        <option value="0.18">0.18</option>
                        <option value="0.19">0.19</option>
                        <option value="0.2">0.2</option>
                        <option value="0.21">0.21</option>
                        <option value="0.22">0.22</option>
                        <option value="0.23">0.23</option>
                        <option value="0.24">0.24</option>
                        <option value="0.25">0.25</option>
                        <option value="0.26">0.26</option>
                        <option value="0.27">0.27</option>
                        <option value="0.28">0.28</option>
                        <option value="0.29">0.29</option>
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

