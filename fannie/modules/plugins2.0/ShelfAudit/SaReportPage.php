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
            $query=$dbc->prepare('delete from sa_inventory where id=?');
            $result=$dbc->execute($query,array(FormLib::get_form_value('id')));
            if ($result) {
                $this->sql_actions='Deleted record.';
            } else {
                $this->sql_actions='Unable to delete record, please try again. <!-- '.$query.' -->';
            }
        } elseif (FormLib::get_form_value('clear') == 'yes'){
            $query=$dbc->prepare('update sa_inventory set clear=1;');
            $result=$dbc->execute($query);
            if ($result) {
                $this->sql_actions='Cleared old scans.';
                header ("Location: SaReportPage.php");
                return False;
            }
            $this->sql_actions='Unable to clear old scans, try again. <!-- '.$query.' -->';
        }

        $order='dept_no,s.section,s.datetime';
        if(FormLib::get_form_value('excel') == 'yes'){
            $order='salesCode, dept_no, s.datetime';
        }
    
        $this->store = FormLib::get('store', false);
        if ($this->store === false ) {
            $this->store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }
        if ($this->config->get('STORE_MODE') !== 'HQ') {
            $store = 0;
        }
        $soP = $dbc->prepare("
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
                AND s.storeID=?');

            $OPDB = $this->config->get('OP_DB') . $dbc->sep();
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
                LEFT JOIN {$OPDB}vendorItems AS v ON s.upc=v.upc AND v.vendorID=1
                LEFT JOIN {$OPDB}vendorDepartments AS y ON v.vendorDept=y.deptID AND v.vendorID=y.vendorID
                LEFT JOIN {$OPDB}departments AS z ON y.posDeptID=z.dept_no
                LEFT JOIN {$OPDB}vendorItems AS a ON p.upc=a.upc AND p.default_vendor_id=a.vendorID 
                LEFT JOIN {$OPDB}vendors AS b ON a.vendorID=b.vendorID
                LEFT JOIN {$OPDB}vendorDepartments AS c ON a.vendorID=c.vendorID AND a.vendorDept=c.deptID
            WHERE clear!=1
                AND s.storeID=?
            ORDER BY ".$order);
        $r=$dbc->execute($q, array($this->store));
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
                    }
                    $key = $row['upc'];
                    if (!isset($upcs[$key])) {
                        $this->scans[$key] = $row;
                        $upcs[$key] = true;
                    } else {
                        $this->scans[$key]['quantity'] += $row['quantity'];
                    }
                }
            } else {
                $this->status = 'Good - No scans';
            }
        } else {
            $this->status = 'Bad - IT problem';
        }

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

    function csv_content(){
        $ret = "UPC,Description,Vendor,Account#,Dept#,\"Dept Name\",Qty,Cost,Unit Cost Total,Normal Retail,Status,Normal Retail Total\r\n";
        $totals = array();
        $vendors = array();
        foreach($this->scans as $row) {
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
            if (!isset($totals[$row['salesCode']]))
                $totals[$row['salesCode']] = array('qty'=>0.0,'ttl'=>0.0,'normalTtl'=>0.0,'costTtl'=>0.0);
            $totals[$row['salesCode']]['qty'] += $row['quantity'];
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
            $vendors[$row['vendor']][$row['salesCode']]['qty'] += $row['quantity'];
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
        return $ret;
    }

    function body_content(){
        ob_start();
        $stores = FormLib::storePicker();
        $stores['html'] = str_replace('<select', '<select onchange="location=\'?store=\'+this.value;" ', $stores['html']);
        ?>
        <div id="bdiv">
            <p><a href="#" onclick="window.open('SaScanningPage.php','scan','width=320, height=200, location=no, menubar=no, status=no, toolbar=no, scrollbars=no, resizable=no');">Enter a new scan</a></p>
            <p><a href="SaHandheldPage.php">Alternate Scan Page</a></p>
            <p><?php echo($this->sql_actions); ?></p>
            <p><?php echo($this->status); ?></p>
            <p><?php echo $stores['html']; ?></p>
            <p><a href="?excel=yes&store=<?php echo $this->store; ?>">download as csv</a></p>
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

