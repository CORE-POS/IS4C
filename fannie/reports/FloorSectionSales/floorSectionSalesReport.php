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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class floorSectionSalesReport extends FannieReportPage 
{
    protected $header = "Floor Sections Report";
    protected $title = "Fannie :: Sales Report by Floor Sections";
    protected $report_cache = 'none';
    protected $report_headers = array('UPC','Brand','Description','Auto Par','On Sale','Sale Price','Last on Sale');
    protected $required_fields = array('floorsection');

    public $description = '[Sales Report by Floor Sections] lists sales/non-sales items in respect to physical 
        product locations.';
    public $report_set = 'Sales';
    protected $new_tablesorter = true;

    function getSaleItems()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $start = FormLib::get_form_value('date1','');
        $end = FormLib::get_form_value('date2','');
        $batchesModel = new BatchesModel($dbc);
        $batchListModel = new BatchListModel($dbc);
        $batchesModel->startDate($start);
        $batchesModel->endDate($end);
        
        $batches = array();
        $items = array();
        foreach ($batchesModel->find() as $dataA) {
            $batchListModel->batchID($data->batchID());
            foreach ($batchListModel()->find() as $dataB) {
                $upc = $dataB->upc();
                $items[$upc]['salePrice'] = $dataB->salePrice;
            }
        }
       
        return $items; 
    }
    
    private function getFloorSections() 
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
       
        $ret = '';
        $prepQ = $dbc->prepare("SELECT * FROM FloorSections");
        $prepR = $dbc->execute($prepQ);
        $ret .= '<select name="floorsection" class="form-control">';
        $ret .= '<option value="0">Select A Section</option>'; 
        while ($row = $dbc->fetchRow($prepR)) {
            $ret .= sprintf('<option class="store'.$row['storeID'].'" 
                value="%d">%s</option>',$row['floorSectionID'],$row['name']);
        }
        $ret .= '</select>';
        
        return $ret;
    }
    
    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $start = FormLib::get_form_value('date1','');
        $end = FormLib::get_form_value('date2','');
        $floorsection = FormLib::get('floorsection');
        $data = array();
        $store = FormLib::get('store');
        
        $fsModel = new FloorSectionsModel($dbc);
        $fsModel->floorSectionID($floorsection);
        $fsModel->load();
        
        //deletme 
        echo '<div class="alert alert-danger" align="center">This page is currently under development.
            If you are seeineg this message, please do not use this report.</div>';
        echo '<h4>Viewing products in <strong>'.$fsModel->name().'</strong></h4>';
        
        $inSectionA = array($floorsection);
        $inSectionP = $dbc->prepare("
            SELECT upc
            FROM FloorSectionProductMap 
            WHERE floorSectionID = ?
        ");
        $inSectionR = $dbc->execute($inSectionP,$inSectionA);
        $upcs = array();
        while ($row = $dbc->fetchRow($inSectionR)) {
            $upcs[] = $row['upc'];
        }
        
        $onSaleA = array($start, $store);
        list($inClause,$onSaleA) = $dbc->safeInClause($upcs, $onSaleA);

        //$onSaleA = array($start,$store);
        $onSaleQ = "
            SELECT 
                p.upc,p.brand,p.description,p.auto_par,
                b.batchID,
                l.salePrice,
                v.vendorID
            FROM products AS p
                INNER JOIN batchList AS l ON p.upc=l.upc 
                INNER JOIN batches AS b ON l.batchID=b.batchID AND b.startDate = ?
                INNER JOIN vendorSKUtoPLU AS v ON p.upc=v.upc
            WHERE p.inUse = 1
                AND p.store_id = ?
                AND p.upc IN (".$inClause.")
        ";
            
        $onSaleP = $dbc->prepare($onSaleQ);
        $onSaleR = $dbc->execute($onSaleP,$onSaleA);
        $i = 0;
        while ($row = $dbc->fetchRow($onSaleR)) {
            $upc = $row['upc'];
            $data[$i][0] = $upc;
            $data[$i][1] = $row['brand'];
            $data[$i][2] = $row['description'];
            $data[$i][3] = round($row['auto_par'],2);
            $data[$i][4] = 'Yes'; 
            $data[$i][5] = $row['salePrice'];
            $data[$i][6] = 'currently on sale';
            $i++;
        }
        if ($err = $dbc->error()) {
            echo '<div class="alert alert-danger">'.$err.'</div>';
        }
        
        $model = new ProductsModel($dbc);
        foreach ($upcs as $upc) {
            //$data[$i][0] = $upc;
            $args = array($upc,'1');
            $prep = $dbc->prepare("
                SELECT 
                    *
                FROM products AS p
                WHERE p.upc = ? 
                    AND p.store_id = ? 
                    AND p.inUse = 1
            ");
            $res = $dbc->execute($prep,$args);
            while ($row = $dbc->fetchRow($res)) {
                $data[$i][0] = $upc;
                $data[$i][1] = $row['brand'];
                $data[$i][2] = $row['description'];
                $data[$i][3] = round($row['auto_par'],2);
                if (empty($data[$i][4])) {
                    $data[$i][4] = 'No';
                }
                $data[$i][5] = 'n/a';
                $data[$i][6] = 'n/a';
                
                $a = array($upc);
                $p = $dbc->prepare("
                    SELECT 
                        b.startDate, b.endDate
                    FROM batches AS b 
                        LEFT JOIN batchList AS l ON b.batchID=l.batchID 
                    WHERE l.upc = ? 
                    ORDER BY b.startDate DESC 
                    LIMIT 1;
                ");
                $r = $dbc->execute($p,$a);
                while ($row = $dbc->fetchRow($r)) {
                    if (substr($row['startDate'],0,4) < 2000) {
                        $data[$i][6] = 'n/a';
                    } else {
                        $data[$i][6] = substr($row['startDate'],0,10);
                    }
                }
                
                $i++;
            }
            if ($er = $dbc->error()) {
                echo '<div class="alert alert-danger">'.$er.'</div>';
            }
            
        }

        return $data;
    }

    function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        ob_start();
        echo '<hr />';
        echo '<form method="get" class="form-group">';
        echo '<div class="row">';

        $test = 'abc';
        
        $rightCol = <<<HTML
<div class="col-sm-7">
    <p>
        <div class="row">
            <div class="col-sm-3">
                <label>Start Date</label>
            </div>
            <div class="col-sm-4">
                <input class="form-control date-field" name="date1" id="date1" />
            </div>
        </div>
    </p>        
    <p>
        <div class="row">
            <div class="col-sm-3">
                <label>End Date</label>
            </div>
            <div class="col-sm-4">
                <input class="form-control date-field" name="date2" id="date2" />
            </div>
        </div>
    </p>
    <p>
        <div class="row">
            <div class="col-sm-3">
                <label>Store(s)</label>
            </div>
            <div class="col-sm-4">
                {{STORES}}
            </div>
        </div>
    </p>
    <p>
        <div class="row">
            <div class="col-sm-3">
                <label>Floor Section</label>
            </div>
            <div class="col-sm-4">
                {{FLOORSECTIONS}}
            </div>
        </div>
    </p>
    <p>
        <div class="row">
            <div class="col-sm-3">
                <label>Excel 
            </div>
            <div class="col-sm-4">
                <input type="checkbox" name="excel" value="xls" />
                </label>
            </div>
        </div>
    </p>
    <p>
            <div class="row">
                <div class="col-sm-3">
                </div>
                <div class="col-sm-4">
                    <button type="submit" class="btn btn-default form-control">Run Report</button>
                </div>
            </div>
    </p>
</div>
</div>
HTML;
        $stores = FormLib::storePicker();
        $html = str_replace('{{STORES}}', $stores['html'], $rightCol);
        $sections = $this->getFloorSections();
        $html = str_replace('{{FLOORSECTIONS}}', $sections, $html);
        echo $html;
        
        echo '</form>';

        return ob_get_clean();
    }
    
    public function javascriptContent()
    {
        ob_start();
        ?>
$(document).ready(function () {
    $('select[name=store]').change(function () {
        var storeSelected = 'store'+$(this).val();
        $('select[name=floorsection]').find('option').each(function () {
            $(this).show();
            optName = $(this).attr('class');
            if (optName != storeSelected ) {
                $(this).hide();
            }
        });
        
    });
});
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Show sales information for products in respect to physical product 
            locations.</p>';
    }
}

FannieDispatch::conditionalExec();

