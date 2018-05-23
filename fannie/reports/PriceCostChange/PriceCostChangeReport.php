<?php

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class PriceCostChangeReport extends FannieReportPage
{
    protected $header = 'Price/Cost Change Report';
    protected $title = 'Price/Cost Change Report';
    public $description = '[Price/Cost Change Report] lists price or cost changes for a given department set and time period';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('UPC', 'Brand', 'Description', 'Price', 'Modified');
    protected $new_tablesorter = true;
    protected $sort_column = 4;
    protected $sort_direction = 1;

    public function fetch_report_data()
    {
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $deptStart = FormLib::get('deptStart');
        $deptEnd = FormLib::get('deptEnd');
        $deptMulti = FormLib::get('departments', array());
        $store = FormLib::get('store', 0);
        $buyer = FormLib::get('buyer', '');

        $args = array($date1.' 00:00:00', $date2.' 23:59:59');
        $where = ' 1=1 ';
        if ($buyer !== '') {
            if ($buyer == -2) {
                $where .= ' AND s.superID != 0 ';
            } elseif ($buyer != -1) {
                $where .= ' AND s.superID=? ';
                $args[] = $buyer;
            }
        }
        if ($buyer != -1) {
            list($conditional, $args) = DTrans::departmentClause($deptStart, $deptEnd, $deptMulti, $args, 'p');
            $where .= $conditional;
        }
        /**
         * Be more verbose until sure that store filtering
         * isn't suppressing relevant changes
        $where .= ' AND ' . DTrans::isStoreID($store, 'p');
        $args[] = $store;
         */

        $historyTable = 'prodPriceHistory';
        $pricingCol = 'price';
        if (FormLib::get('priceCost') == 'Cost') {
            $historyTable = 'ProdCostHistory';
            $pricingCol = 'cost';
            $this->report_headers[4] = 'Cost';
        }

        $query = "SELECT h.upc, p.brand, p.description, h.{$pricingCol}, h.modified
            FROM {$historyTable} AS h
                INNER JOIN products AS p ON p.upc=h.upc AND p.store_id=h.storeID ";
        if ($buyer !== '' && $buyer > -1) {
            $query .= 'LEFT JOIN superdepts AS s ON p.department=s.dept_ID ';
        } elseif ($buyer !== '' && $buyer == -2) {
            $query .= 'LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID ';
        }
        $query .= " WHERE h.modified BETWEEN ? AND ?
                AND {$where}";

        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row[$pricingCol],
                $row['modified'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        $extraSelect = <<<HTML
<div class="form-group">
    <label class="col-sm-4 control-label">Changes in</label>
    <div class="col-sm-8">
        <select name="priceCost" class="form-control">
            <option>Price</option>
            <option>Cost</option>
        </select>
    </div>
</div>
HTML;
        $json = json_encode($extraSelect);
        $this->addOnloadCommand("\$('#date-dept-form-left-col').html({$json});");
        
        return FormLib::dateAndDepartmentForm();
    }
}

FannieDispatch::conditionalExec();

