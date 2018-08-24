<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class WhoCreatedReport extends FannieReportPage 
{
    public $description = '[Who Created Report] shows items created in a given time period and who entered them';
    protected $new_tablesorter = true;
    protected $title = "Who Created";
    protected $header = "Who Created Report";
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('UPC', 'Brand', 'Description', 'Vendor', 'Created', 'User');
    protected $sort_column = 5;

    public function fetch_report_data()
    {
        $prep = $this->connection->prepare('
            SELECT upc, brand, description, vendorName, MIN(created) AS created
            FROM products AS p
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
            WHERE created BETWEEN ? AND ?
            GROUP BY upc, brand, description, vendorName');
        $args = array($this->form->date1 . ' 00:00:00', $this->form->date2 . ' 23:59:59');
        $res = $this->connection->execute($prep, $args);
        $updateP = $this->connection->prepare('
            SELECT user, name
            FROM prodUpdate AS p
                LEFT JOIN Users AS u ON p.user=u.uid
            WHERE p.upc=?
                AND p.modified BETWEEN ? AND ?
            ORDER BY p.modified'); 
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            list($date,) = explode(' ', $row['created'], 2);
            $args = array($row['upc'], $date . ' 00:00:00', $date . ' 23:59:59');
            $user = $this->connection->getRow($updateP, $args);
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['vendorName'] ? $row['vendorName'] : 'n/a',
                $row['created'],
                $user['name'] ? $user['name'] : 'Unknown',
            );
        }

        return $data;
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    {$dates}
    <div class="form-group">
        <button class="btn btn-default btn-core">Submit</button>
    </div>
</form>
HTML;
    }

}

FannieDispatch::conditionalExec();
