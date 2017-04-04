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

class VariableItems extends FannieReportPage 
{
    public $description = '[Variably Priced Items Report] lists all active variably priced items';
    public $report_set = 'Price Reports';
    public $themed = true;

    protected $report_headers = array('Upc', 'Brand', 'Description', 'Dept. No.', 'Dept. Name', 'Cost', 'Price', 'Desired Marg.', 'Actual Marg.', 'Marg. +/-');
    protected $sort_direction = 1;
    protected $title = "Fannie : Variably Priced Items Report";
    protected $header = "Variably Priced Items Report";

    public function report_description_content()
    {
        return array('Find items marked variable that are in use.');
    }

    public function fetch_report_data()
    {        
        
        $item = array();
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        
        //procure batchIDs from 'batches'
        $query = "
            SELECT p.upc,
            p.description,
            p.department,
            p.brand,
            S.margin as dMarg,
            D.margin as uMarg,
            CASE WHEN department>=1 and department<=25 
                OR (department>=251 and department<=259) THEN \"BULK\"
            WHEN department>=26 and department<=59 THEN \"COOL\" 
            WHEN department>=61 and department<=78 OR department=150 
                or (department>=226 AND department<=234) THEN \"DELI\"
            WHEN department>=86 and department<=124 THEN \"WELLNESS\"
            WHEN department>=151 and department<=190 THEN \"GROCERY\"
            WHEN department>=204 and department<=209 THEN \"PRODUCE\"
            WHEN department>=240 and department<=244 THEN \"GENERAL MERCH\"
            WHEN department>=260 and department<=261 THEN \"MEAT\"
                END as deptName,
            p.cost,
            p.normal_price,
            ( (p.normal_price-p.cost) / p.normal_price) as actual_margin
            FROM products AS p
                LEFT JOIN vendorItems as V ON p.upc = V.upc AND p.default_vendor_id = V.vendorID
                LEFT JOIN vendorDepartments as D ON (V.vendorID = D.vendorID) AND (D.deptID = V.vendorDept) 
                LEFT JOIN departments as S ON (p.department = S.dept_no)
            WHERE p.inUse=1 
                AND p.price_rule_id=1
            ORDER BY p.department, p.brand
        ;";
        $result = $dbc->query($query);
        $upc = array();
        while ($row = $dbc->fetch_row($result)) {
            $upc[] = $row['upc'];
            $brand[] = $row['brand'];
            $desc[] = $row['description'];
            $deptNo[] = $row['department'];
            $deptName[] = $row['deptName'];
            $cost[] = $row['cost'];
            $price[] = $row['normal_price'];
            $marg[] = $row['actual_margin'];
            $uMarg = $row['uMarg'];
            
            $dMarg = $row['dMarg'];
            if($uMarg == NULL) { 
                $deptMarg[] = $dMarg;
            } else {
                $deptMarg[] = $uMarg;
            }
        }     
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        }
        
        for ($i=0; $i<count($upc); $i++){
            $margDiff[] = $marg[$i] - $deptMarg[$i];
        }
        
        echo count($upc) . " variable items found in POS (in use).<br>";
        
        $item = array();
        for ($i=0; $i<count($upc); $i++) {
            $item[$i][0] = $upc[$i];
            $item[$i][1] = $brand[$i];
            $item[$i][2] = $desc[$i];
            $item[$i][3] = $deptNo[$i];
            $item[$i][4] = $deptName[$i];
            $item[$i][5] = $cost[$i];
            $item[$i][6] = $price[$i];
            $item[$i][7] = sprintf("%01.2f", $deptMarg[$i]);
            $item[$i][8] = sprintf("%01.2f", $marg[$i]);
            $item[$i][9] = sprintf("%01.2f", $margDiff[$i]);
        }
        return $item;
    }

    public function helpContent()
    {
        return '<p>Report returns all items
        that are marked as being generically 
        Variably Priced. 
        </p>';
    }

    public function form_content()
    {
        return '<!-- not needed for this report -->';
    }

}

FannieDispatch::conditionalExec();

