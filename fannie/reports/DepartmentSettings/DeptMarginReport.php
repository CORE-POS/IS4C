<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class DeptMarginReport extends FannieReportPage 
{
    public $description = '[Department Margins Report] shows information about chart of account margin settings';
    public $report_set = 'Finance';

    protected $report_headers = array('Account #', 'Regular Margin', 'UNFI Margin', '% UNFI', 'Est. Actual Margin');
    protected $title = "Fannie : Department Settings";
    protected $header = "Department Settings";
    protected $required_fields = array('store', 'date');

    public function fetch_report_data()
    {
        try {
            $store = $this->form->store;
            $date = $this->form->date;
        } catch (Exception $ex) {
            return array();
        }

        $dbc = $this->connection;
        $query = "
            SELECT d.salesCode,
                AVG(d.margin) AS deptMargin,
                AVG(t.margin) AS unfiMargin,
                SUM(CASE WHEN p.default_vendor_id=1 THEN 1 ELSE 0 END) / COUNT(*) AS unfiMix
            FROM products AS p
                INNER JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND v.vendorID=1
                LEFT JOIN vendorDepartments AS t ON v.vendorID=t.vendorID AND t.deptID=v.vendorDept
            WHERE p.store_id=?
                AND p.last_sold >= ?
            GROUP BY d.salesCode";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($store, $date));
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $diff = $row['unfiMargin'] - $row['deptMargin'];
            $diff *= (1 - $row['unfiMix']);
            $avg = $row['unfiMargin'] - $diff;
            if ($row['unfiMargin'] == 0) {
                $avg = $row['deptMargin'];
            }
            $data[] = array(
                $row['salesCode'],
                sprintf('%.2f%%', $row['deptMargin'] * 100),
                sprintf('%.2f%%', $row['unfiMargin'] * 100),
                sprintf('%.2f%%', $row['unfiMix'] * 100),
                sprintf('%.2f%%', $avg * 100),
            );
        }

        return $data;
    }

    public function form_content()
    {
        $store = FormLib::storePicker();
        $date = date('Y-m-d', strtotime('last quarter'));
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Store</label>
        {$store['html']}
    </div>
    <div class="form-group">
        <label>Sold since</label>
        <input type="text" class="form-control date-field" name="date" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();


