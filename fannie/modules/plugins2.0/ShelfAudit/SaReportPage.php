<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Based on example code from Wedge Community Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

/**
  @class SaScanningPage
*/
class SaReportPage extends FanniePage {

    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Quantity Report] lists the entered quantites on hand.';
    public $themed = true;
    protected $title = 'ShelfAudit Live Report';
    protected $header = '';

    private $status = '';
    private $store = false;
    private $sql_actions = '';
    private $scans = array();

    function preprocess(){
        global $FANNIE_PLUGIN_SETTINGS,$FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ShelfAuditDB']);
        if (!is_object($dbc) || $dbc->connections[$FANNIE_PLUGIN_SETTINGS['ShelfAuditDB']] === False){
            $this->status = 'bad - cannot connect';
            return True;
        }
        if (FormLib::get_form_value('delete') == 'yes'){
            $query=$dbc->prepare('update sa_inventory set clear=1 where id=?');
            $result=$dbc->execute($query,array(FormLib::get_form_value('id')));
            if ($result) {
                $this->sql_actions='Deleted record.';
            } else {
                $this->sql_actions='Unable to delete record, please try again. <!-- '.$query.' -->';
            }
        } elseif (FormLib::get_form_value('clear') == 'yes'){
            $arch = $dbc->prepare("INSERT INTO SaArchive (tdate, storeID, data) VALUES (?, ?, ?)");
            $dateP = $dbc->prepare("SELECT MIN(datetime) FROM sa_inventory WHERE clear=0 and storeID=?");
            foreach (array(1, 2) as $storeID) {
                $this->store = $storeID;
                $this->getScanData();
                $csv = $this->csv_content();
                $date = $dbc->getValue($dateP, array($storeID));
                $dbc->execute($arch, array($date, $storeID, $csv));
            }
            $query=$dbc->prepare('update sa_inventory set clear=1;');
            $result=$dbc->execute($query);
            if ($result) {
                $this->sql_actions='Cleared old scans.';
                header ("Location: SaReportPage.php");
                return False;
            }
            $this->sql_actions='Unable to clear old scans, try again. <!-- '.$query.' -->';
        }

        $this->store = FormLib::get('store', false);
        if ($this->store === false ) {
            $this->store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        if ($this->config->get('STORE_MODE') !== 'HQ') {
            $this->store = 0;
        }
        $this->getScanData();

        if (!empty($this->scans) && FormLib::get_form_value('excel') == 'yes'){
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename=inventory_scans.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            echo $this->csv_content();
            return False;
        }

        return True;
    }

    private function getScanData()
    {
        global $FANNIE_PLUGIN_SETTINGS,$FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ShelfAuditDB']);
        $order='dept_no,s.section,s.datetime';
        if(FormLib::get_form_value('excel') == 'yes'){
            $order='salesCode, dept_no, s.datetime';
        }
    
        $soQ = "
            SELECT s.id,
                s.datetime,
                s.upc,
                1 AS quantity,
                CASE
                    WHEN s.section=0 THEN 'Backstock'
                    WHEN s.section=1 THEN 'Floor'
                    ELSE 'Unknown'
                END AS section,
                o.description,
                d.dept_no,
                d.dept_name,
                d.salesCode,
                0 AS cost,
                o.total AS normal_retail,
                o.total AS actual_retail,
                '' AS retailstatus,
                o.mixMatch AS vendor,
                COALESCE(c.margin, d.margin, 0) AS margin
            FROM sa_inventory AS s LEFT JOIN ".
                $this->config->get('TRANS_DB') . $dbc->sep() . "PendingSpecialOrder AS o
                ON o.order_id=? AND o.trans_id=? LEFT JOIN " .
                $FANNIE_OP_DB.$dbc->sep().'departments AS d
                ON o.department=d.dept_no LEFT JOIN '.
                $FANNIE_OP_DB.$dbc->sep().'vendorItems AS v
                ON o.upc=v.upc AND v.vendorID=1 LEFT JOIN '.
                $FANNIE_OP_DB.$dbc->sep().'vendorDepartments AS c
                ON v.vendorID=c.vendorID AND v.vendorDept=c.deptID 
            WHERE clear!=1
                AND s.upc=?
                AND s.storeID=?';
        $soP = $dbc->prepare($soQ);
        $soP2 = $dbc->prepare(str_replace('PendingSpecialOrder', 'CompleteSpecialOrder', $soQ));

            $OPDB = $this->config->get('OP_DB') . $dbc->sep();
        $args = array($this->store);
        $super = FormLib::get('super', -1);
        $superAnd = '';
        if ($super >= 0) {
            $superAnd = ' AND m.superID=? ';
            $args[] = $super;
        }
        $q= $dbc->prepare("SELECT
            s.id,
            s.datetime,
            s.upc,
            s.quantity,
            CASE
                WHEN s.section=0 THEN 'Backstock'
                WHEN s.section=1 THEN 'Floor'
                ELSE 'Unknown'
            END AS section,
            CASE 
                WHEN p.description IS NULL AND v.description IS NULL THEN 'Not in POS' 
                WHEN p.description IS NULL AND v.description IS NOT NULL THEN v.description
                ELSE p.description END as description,
            CASE
                WHEN d.dept_name IS NOT NULL THEN d.dept_name
                WHEN z.dept_name IS NOT NULL THEN z.dept_name
                ELSE 'Unknown'
            END as dept_name,
            CASE
                WHEN d.dept_no IS NOT NULL THEN d.dept_no
                WHEN z.dept_no IS NOT NULL THEN z.dept_no
                ELSE -999
            END AS dept_no,
            CASE
                WHEN d.salesCode IS NOT NULL THEN d.salesCode
                WHEN z.salesCode IS NOT NULL THEN z.salesCode
                ELSE 'n/a'
            END AS salesCode,
            CASE WHEN p.cost IS NULL AND v.cost IS NOT NULL THEN v.cost ELSE p.cost END as cost,
            CASE WHEN p.normal_price IS NULL AND v.srp IS NOT NULL THEN v.srp ELSE p.normal_price END as normal_retail,
            CASE 
                WHEN p.discounttype IS NULL AND v.srp IS NOT NULL THEN v.srp
                WHEN p.discounttype > 0 THEN p.special_price 
                ELSE p.normal_price 
            END AS actual_retail,
            CASE WHEN p.discounttype = 2 THEN 'M' ELSE '' END AS retailstatus,
            COALESCE(b.vendorName,'n/a') AS vendor,
            COALESCE(c.margin, d.margin, 0) AS margin

        FROM sa_inventory AS s 
            LEFT JOIN {$OPDB}products AS p ON s.upc=p.upc AND p.store_id=1 
            LEFT JOIN {$OPDB}departments AS d ON p.department=d.dept_no
            LEFT JOIN {$OPDB}MasterSuperDepts AS m ON p.department=m.dept_ID
            LEFT JOIN {$OPDB}vendorItems AS v ON s.upc=v.upc AND v.vendorID=1
            LEFT JOIN {$OPDB}vendorDepartments AS y ON v.vendorDept=y.deptID AND v.vendorID=y.vendorID
            LEFT JOIN {$OPDB}departments AS z ON y.posDeptID=z.dept_no
            LEFT JOIN {$OPDB}vendorItems AS a ON p.upc=a.upc AND p.default_vendor_id=a.vendorID 
            LEFT JOIN {$OPDB}vendors AS b ON a.vendorID=b.vendorID
            LEFT JOIN {$OPDB}vendorDepartments AS c ON a.vendorID=c.vendorID AND a.vendorDept=c.deptID
        WHERE clear!=1
            AND s.storeID=?
            {$superAnd}
        ORDER BY ".$order);
        $r=$dbc->execute($q, $args);
        $upcs = array();
        if ($r) {
            $this->status = 'Good - Connected';
            $num_rows=$dbc->numRows($r);
            if ($num_rows>0) {
                $this->scans=array();
                while ($row = $dbc->fetchRow($r)){
                    if (substr($row['upc'], 0, 5) == '00454') {
                        $orderID = substr($row['upc'], 5, 6);
                        $transID = substR($row['upc'], -2);
                        $args = array($orderID, $transID, $row['upc'], $this->store);
                        $row = $dbc->getRow($soP, $args);
                        if (empty($row['description']) && empty($row['normal_retail'])) {
                            $row = $dbc->getRow($soP2, $args);
                        }
                    }
                    $key = $row['upc'] . $row['section'];
                    if (!isset($upcs[$key])) {
                        $this->scans[$key] = $row;
                        $upcs[$key] = true;
                    }
                }
            } else {
                $this->status = 'Good - No scans';
            }
        } else {
            $this->status = 'Bad - IT problem';
        }
    }

    function css_content(){
        ob_start();
        ?>
#bdiv {
    width: 768px;
    margin: auto;
    text-align: center;
}

body table.shelf-audit {
 font-size: small;
 text-align: center;
 border-collapse: collapse;
 width: 100%;
}

body table.shelf-audit caption {
 font-family: sans-mono, Helvetica, sans, Arial, sans-serif;
 margin-top: 1em;
}

body table.shelf-audit th {
 border-bottom: 2px solid #090909;
}

table.shelf-audit tr:hover {
 background-color:#CFCFCF;
}

.right {
 text-align: right;
}
.small {
 font-size: smaller;
}
#col_a {
 width: 150px;
}
#col_b {
 width: 100px;
}
#col_c {
 width: 270px;
}
#col_d {
 width: 40px;
}
#col_e {
 width: 60px;
}
#col_f {
 width: 20px;
}
#col_g {
 width: 80px;
}
#col_h {
 width: 48px;
}
        <?php
        return ob_get_clean();
    }

    private function estMargin($scans, $code)
    {
        $retail = 0;
        $cost = 0;
        $match = 0;
        $noMatch = 0;
        foreach ($scans as $row) {
            if ($row['salesCode'] != $code) {
                continue;
            }
            if ($row['cost'] == 0 || $row['cost'] == $row['normal_retail']) {
                $noMatch++;
            } else {
                $match++;
                $retail += ($row['quantity'] * $row['normal_retail']);
                $cost += ($row['quantity'] * $row['cost']);
            }
        }

        if ($noMatch > $match || $retail <= 0 || $cost <= 0) {
            $prep = $this->connection->prepare("SELECT margin FROM " . FannieDB::fqn('departments', 'op') . " WHERE salesCode=? AND margin <> 0");
            $margin = $this->connection->getValue($prep, array($code));
            return $margin !== false ? $margin : 0;
        }

        return ($retail - $cost) / $retail;
    }

    function csv_content(){
        $ret = "UPC,Description,Vendor,Account#,Dept#,\"Dept Name\",Qty,Cost,Unit Cost Total,Normal Retail,Status,Normal Retail Total\r\n";
        $totals = array();
        $vendors = array();
        $manuals = array();
        $services = array();
        $adjustUp = array();
        $adjustDown = array();
        foreach($this->scans as $row) {

            /**
             * Deal with special behavior PLUs first since
             * they include invalid quantites that should be 
             * carried into the other totals
             */
            $plu = ltrim($row['upc'], '0');
            $goodQty = true;
            if (strlen($plu) == 5) {
                if (strpos($row['description'], 'INV SERVICE') !== false) {
                    $estMargin = $this->estMargin($this->scans, $row['salesCode']);
                    $row['cost'] = $row['normal_retail'] - ($estMargin * $row['normal_retail']);
                    $row['retailstatus'] .= '*';
                    if (!isset($services[$row['salesCode']])) {
                        $services[$row['salesCode']] = array('qty'=>0.0,'ttl'=>0.0,'normalTtl'=>0.0,'costTtl'=>0.0);
                    }
                    $services[$row['salesCode']]['qty'] += $row['quantity'];
                    $services[$row['salesCode']]['ttl'] += ($row['quantity']*$row['actual_retail']);
                    $services[$row['salesCode']]['normalTtl'] += ($row['quantity']*$row['normal_retail']);
                    $services[$row['salesCode']]['costTtl'] += ($row['quantity']*$row['cost']);
                    $goodQty = false;
                } elseif (strpos($row['description'], ' @ COST')) {
                    if (!isset($manuals[$row['salesCode']])) {
                        $manuals[$row['salesCode']] = array('qty'=>0.0,'ttl'=>0.0,'normalTtl'=>0.0,'costTtl'=>0.0);
                    }
                    $manuals[$row['salesCode']]['qty'] += $row['quantity'];
                    $manuals[$row['salesCode']]['ttl'] += ($row['quantity']*$row['actual_retail']);
                    $manuals[$row['salesCode']]['normalTtl'] += ($row['quantity']*$row['normal_retail']);
                    $manuals[$row['salesCode']]['costTtl'] += ($row['quantity']*$row['cost']);
                    $goodQty = false;
                } elseif (strpos($row['description'], 'ADJ SALES')) {
                    if (!isset($adjustDown[$row['salesCode']])) {
                        $adjustDown[$row['salesCode']] = array('qty'=>0.0,'ttl'=>0.0,'normalTtl'=>0.0,'costTtl'=>0.0);
                    }
                    $adjustDown[$row['salesCode']]['qty'] += $row['quantity'];
                    $adjustDown[$row['salesCode']]['ttl'] += ($row['quantity']*$row['actual_retail']);
                    $adjustDown[$row['salesCode']]['normalTtl'] += ($row['quantity']*$row['normal_retail']);
                    $adjustDown[$row['salesCode']]['costTtl'] += ($row['quantity']*$row['cost']);
                    $goodQty = false;
                } elseif (strpos($row['description'], ' RECV ')) {
                    if (!isset($adjustUp[$row['salesCode']])) {
                        $adjustUp[$row['salesCode']] = array('qty'=>0.0,'ttl'=>0.0,'normalTtl'=>0.0,'costTtl'=>0.0);
                    }
                    $adjustUp[$row['salesCode']]['qty'] += $row['quantity'];
                    $adjustUp[$row['salesCode']]['ttl'] += ($row['quantity']*$row['actual_retail']);
                    $adjustUp[$row['salesCode']]['normalTtl'] += ($row['quantity']*$row['normal_retail']);
                    $adjustUp[$row['salesCode']]['costTtl'] += ($row['quantity']*$row['cost']);
                    $goodQty = false;
                }
            }

            if ($row['cost'] == 0 && $row['margin'] != 0) {
                $row['cost'] = $row['normal_retail'] - ($row['margin'] * $row['normal_retail']);
                $row['retailstatus'] .= '*';
            }
            $ret .= sprintf("%s,\"%s\",\"%s\",%s,%s,%s,%.2f,%.2f,%.2f,%.2f,%s,%.2f,\r\n",
                $row['upc'],$row['description'],$row['vendor'],$row['salesCode'],$row['dept_no'],
                $row['dept_name'],$row['quantity'],$row['cost'], ($row['quantity']*$row['cost']),
                $row['normal_retail'],
                $row['retailstatus'],
                ($row['quantity']*$row['normal_retail'])
            );

            if (!isset($totals[$row['salesCode']])) {
                $totals[$row['salesCode']] = array('qty'=>0.0,'ttl'=>0.0,'normalTtl'=>0.0,'costTtl'=>0.0);
            }
            $totals[$row['salesCode']]['qty'] += $goodQty ? $row['quantity'] : 0;
            $totals[$row['salesCode']]['ttl'] += ($row['quantity']*$row['actual_retail']);
            $totals[$row['salesCode']]['normalTtl'] += ($row['quantity']*$row['normal_retail']);
            $totals[$row['salesCode']]['costTtl'] += ($row['quantity']*$row['cost']);
            if ($row['vendor'] != 'UNFI') {
                $row['vendor'] = 'Non-UNFI';
            }
            if (!isset($vendors[$row['vendor']])) {
                $vendors[$row['vendor']] = array();
            }
            if (!isset($vendors[$row['vendor']][$row['salesCode']])) {
                $vendors[$row['vendor']][$row['salesCode']] = array('qty'=>0.0,'ttl'=>0.0,'normalTtl'=>0.0,'costTtl'=>0.0);
            }
            $vendors[$row['vendor']][$row['salesCode']]['qty'] += $goodQty ? $row['quantity']: 0;
            $vendors[$row['vendor']][$row['salesCode']]['ttl'] += ($row['quantity']*$row['actual_retail']);
            $vendors[$row['vendor']][$row['salesCode']]['normalTtl'] += ($row['quantity']*$row['normal_retail']);
            $vendors[$row['vendor']][$row['salesCode']]['costTtl'] += ($row['quantity']*$row['cost']);

        }
        $ret .= ",,,,,,,,\r\n";
        foreach($totals as $code => $info){
            $ret .= sprintf(",,TOTAL,%s,,,%.2f,,%.2f,,,%.2f,\r\n",
                    $code, $info['qty'], $info['costTtl'], $info['normalTtl']);
        }
        $ret .= ",,,,,,,,\r\n";
        foreach($vendors as $vendor => $sales) {
            foreach ($sales as $code => $info) {
                $ret .= sprintf(",,%s,%s,,,%.2f,,%.2f,,,%.2f,\r\n",
                        $vendor,$code, $info['qty'], $info['costTtl'], $info['normalTtl']);
            }
        }
        if (count($manuals) > 0) {
            $ret .= ",,,,,,,,\r\n";
            foreach($manuals as $code => $info){
                $ret .= sprintf(",,MANUAL,%s,,,,,%.2f,,,%.2f,\r\n",
                        $code, $info['costTtl'], $info['normalTtl']);
            }
        }
        if (count($services) > 0) {
            $ret .= ",,,,,,,,\r\n";
            foreach($services as $code => $info){
                $ret .= sprintf(",,SERVICE,%s,,,,,%.2f,,,%.2f,\r\n",
                        $code, $info['costTtl'], $info['normalTtl']);
            }
        }
        if (count($adjustUp) > 0) {
            $ret .= ",,,,,,,,\r\n";
            foreach($adjustUp as $code => $info){
                $ret .= sprintf(",,RECEIVE ADJUSTMENT,%s,,,,,%.2f,,,%.2f,\r\n",
                        $code, $info['costTtl'], $info['normalTtl']);
            }
        }
        if (count($adjustDown) > 0) {
            $ret .= ",,,,,,,,\r\n";
            foreach($adjustDown as $code => $info){
                $ret .= sprintf(",,SALES ADJUSTMENT,%s,,,,,%.2f,,,%.2f,\r\n",
                        $code, $info['costTtl'], $info['normalTtl']);
            }
        }
        $ret .= ",,,,,,,,\r\n";
        foreach($totals as $code => $info){
            if (isset($adjustDown[$code])) {
                $info['costTtl'] -= $adjustDown[$code]['costTtl'];
                $info['normalTtl'] -= $adjustDown[$code]['normalTtl'];
            }
            if (isset($adjustUp[$code])) {
                $info['costTtl'] -= $adjustUp[$code]['costTtl'];
                $info['normalTtl'] -= $adjustUp[$code]['normalTtl'];
            }
            $ret .= sprintf(",,PRE ADJUSTMENTS,%s,,,%.2f,,%.2f,,,%.2f,\r\n",
                    $code, $info['qty'], $info['costTtl'], $info['normalTtl']);
        }
        return $ret;
    }

    function body_content(){
        ob_start();
        $stores = FormLib::storePicker();
        $model = new MasterSuperDeptsModel($this->connection);
        $model->whichDB($this->config->get('OP_DB'));
        $super = FormLib::get('super', -1);
        $mOpts = $model->toOptions($super);
        $stores['html'] = str_replace('<select', '<select onchange="refilter();" ', $stores['html']);
        ?>
        <script type="text/javascript">
        function refilter() {
            var store = $('select[name=store]').val();
            console.log(store);
            var superID = $('#super').val();
            location = '?store='+store+'&super='+superID;
        }
        </script>
        <div id="bdiv">
            <p><a href="#" onclick="window.open('SaScanningPage.php','scan','width=320, height=200, location=no, menubar=no, status=no, toolbar=no, scrollbars=no, resizable=no');">Enter a new scan</a></p>
            <p><a href="SaHandheldPage.php">Alternate Scan Page</a></p>
            <p><?php echo($this->sql_actions); ?></p>
            <p><?php echo($this->status); ?></p>
            <p><?php echo $stores['html']; ?></p>
            <p><select class="form-control" name="super" id="super" onchange="refilter();">
                <option value="-1">All</option><?php echo $mOpts; ?></select></p>
            <p><a href="?excel=yes&store=<?php echo $this->store; ?>&super=<?php echo $super; ?>">download as csv</a></p>
        <?php
        if ($this->scans) {
            $clear = '<div><a href="SaReportPage.php?clear=yes">Clear Old</a></div>';
            print_r($clear);
        }
        
        $table = '';
        $counter_total = 0;
        foreach($this->scans as $row) {
            
            if (!isset($counter_number)) {
                $counter_number=$row['dept_no'];
                $counter_total=$row['quantity']*$row['normal_retail'];
                
                $caption=$row['dept_name'].' Department';
                
                $table .= '
        <table class="table shelf-audit">
            <caption>'.$caption.'</caption>
            <thead>
                <tr>
                    <th>Date+Time</th>
                    <th>UPC</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Retail (Normal)</th>
                    <th>Retail (Current)</th>
                    <th>Sale</th>
                    <th>Total Retail</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td id="col_a" class="small">'.$row['datetime'].'</td>
                    <td id="col_b">'.$row['upc'].'</td>
                    <td id="col_c">'.$row['description'].'</td>
                    <td id="col_d" class="right">'.$row['quantity'].'</td>
                    <td id="col_e" class="right">'.money_format('%.2n', $row['cost']).'</td>
                    <td id="col_h" class="right">'.money_format('%!.2n', ($row['quantity']*$row['cost'])).'</td>
                    <td id="col_e" class="right">'.money_format('%.2n', $row['normal_retail']).'</td>
                    <td id="col_f" class="right">'.money_format('%.2n', $row['actual_retail']).'</td>
                    <td id="col_g">'.(($row['retailstatus'])?$row['retailstatus']:'&nbsp;').'</td>
                    <td id="col_h" class="right">'.money_format('%!.2n', ($row['quantity']*$row['normal_retail'])).'</td>
                    <td id="col_i"><a href="SaReportPage.php?delete=yes&id='.$row['id'].'">'
                        . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</td>
                </tr>';
            } elseif ($counter_number!=$row['dept_no']) {
                $counter_number=$row['dept_no'];
                $caption=$row['dept_name'].' Department';
                                
                $table .= '
            </tbody>
            <tfoot>
                <tr>
                    <td colspan=6>&nbsp;</td>
                    <td class="right">'.money_format('%.2n', $counter_total).'</td>
                    <td>&nbsp;</td>
                </tr>
            </tfoot>
        </table>
        <table class="table shelf-audit">
            <caption>'.$caption.'</caption>
            <thead>
                <tr>
                    <th>Date+Time</th>
                    <th>UPC</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Retail (Normal)</th>
                    <th>Retail (Current)</th>
                    <th>Sale</th>
                    <th>Total Retail</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td id="col_a" class="small">'.$row['datetime'].'</td>
                    <td id="col_b">'.$row['upc'].'</td>
                    <td id="col_c">'.$row['description'].'</td>
                    <td id="col_d" class="right">'.$row['quantity'].'</td>
                    <td id="col_e" class="right">'.money_format('%.2n', $row['cost']).'</td>
                    <td id="col_h" class="right">'.money_format('%!.2n', ($row['quantity']*$row['cost'])).'</td>
                    <td id="col_e" class="right">'.money_format('%.2n', $row['normal_retail']).'</td>
                    <td id="col_f" class="right">'.money_format('%.2n', $row['actual_retail']).'</td>
                    <td id="col_g">'.(($row['retailstatus'])?$row['retailstatus']:'&nbsp;').'</td>
                    <td id="col_h" class="right">'.money_format('%!.2n', ($row['quantity']*$row['normal_retail'])).'</td>
                    <td id="col_i"><a href="SaReportPage.php?delete=yes&id='.$row['id'].'">'
                        . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</td>
                </tr>';
                
                $counter_total=$row['quantity']*$row['normal_retail'];
            } else {
                $counter_total+=$row['quantity']*$row['normal_retail'];
                
                $table .= '
                <tr>
                    <td id="col_a" class="small">'.$row['datetime'].'</td>
                    <td id="col_b">'.$row['upc'].'</td>
                    <td id="col_c">'.$row['description'].'</td>
                    <td id="col_d" class="right">'.$row['quantity'].'</td>
                    <td id="col_e" class="right">'.money_format('%.2n', $row['cost']).'</td>
                    <td id="col_h" class="right">'.money_format('%!.2n', ($row['quantity']*$row['cost'])).'</td>
                    <td id="col_e" class="right">'.money_format('%.2n', $row['normal_retail']).'</td>
                    <td id="col_f" class="right">'.money_format('%.2n', $row['actual_retail']).'</td>
                    <td id="col_g">'.(($row['retailstatus'])?$row['retailstatus']:'&nbsp;').'</td>
                    <td id="col_h" class="right">'.money_format('%!.2n', ($row['quantity']*$row['normal_retail'])).'</td>
                    <td id="col_i"><a href="SaReportPage.php?delete=yes&id='.$row['id'].'">'
                        . \COREPOS\Fannie\API\lib\FannieUI::deleteIcon() . '</td>
                </tr>';
            }
        }
    
        $table .= '
            </tbody>
            <tfoot>
                <tr>
                    <td colspan=6>&nbsp;</td>
                    <td class="right">'.money_format('%.2n', $counter_total).'</td>
                    <td>&nbsp;</td>
                </tr>
            </tfoot>
        </table>
        </div>
';
        if (!empty($table))
            print_r($table);
        ?>
        <?php

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

