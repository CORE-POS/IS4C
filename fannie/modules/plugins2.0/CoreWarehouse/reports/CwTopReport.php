<?php

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class CwTopReport extends FannieReportPage 
{
    public $discoverable = false;
    protected $required_fields = array('top');
    protected $header = 'Top Shoppers Report';
    protected $title = 'Top Shoppers Report';
    protected $report_headers = array('Account#', 'First Name', 'Last Name', 'Spending $', 'Hillside %', 'Denfeld %');
    protected $sort_column = 3;
    protected $sort_direction = 1;

    public function report_content() 
    {
        $default = parent::report_content();

        if ($this->report_format == 'html') {
            $default .= '<div class="col-sm-10 col-sm-offset-1" id="addrDiv"></div>';
        }

        return $default;
    }

    public function fetch_report_data()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $warehouse = $settings['WarehouseDatabase'] . $this->connection->sep();
        $myweb = $settings['MyWebDB'] . $this->connection->sep();
        $store = FormLib::get('store');

        $query = "SELECT s.card_no,
                MAX(c.FirstName) AS FirstName,
                MAX(c.LastName) AS LastName,
                MAX(m.stat) AS stat,
                SUM(s.retailTotal) AS ttl
            FROM {$warehouse}sumMemSalesByDay AS s
                LEFT JOIN custdata AS c ON c.CardNo=s.card_no AND c.personNum=1
                LEFT JOIN {$myweb}MyStats AS m ON s.card_no=m.customerID AND m.statID=4
            WHERE " . DTrans::isStoreID($store, 's') . "
                AND s.date_id >= ? "; 
        if (FormLib::get('members')) {
            $query .= " AND c.Type='PC' ";
        }
        $query .= "GROUP BY s.card_no
            ORDER BY SUM(s.retailTotal) DESC";
        $query = $this->connection->addSelectLimit($query, FormLib::get('top'));

        $since = FormLib::get('since');
        $dateID = date('Ymd', strtotime($since));
        $args = array($store, $dateID);
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $data = array();
        $mems = array();
        while ($row = $this->connection->fetchRow($res)) {
            $record = array(
                sprintf('<a href="CWMemberSummaryReport.php?id=%d">%s</a>', $row['card_no'], $row['card_no']),
                $row['FirstName'],
                $row['LastName'],
                sprintf('%.2f', $row['ttl']),
            );
            $parts = json_decode($row['stat'], true);
            $record[] = $parts['hillside'];
            $record[] = $parts['denfeld'];
            $data[] = $record;
            $mems[] = $row['card_no'];
        }

        $table = '(BatchGeo)<br /><table class="table table-bordered small">
            <tr><th>ID</th><th>Address</th><th>City</th><th>State</th><th>Zip</th></tr>';
        list($inStr, $args) = $this->connection->safeInClause($mems);
        $prep = $this->connection->prepare("
            SELECT card_no, street, city, state, zip
            FROM meminfo
            WHERE card_no IN ({$inStr})");  
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $row['card_no'], $row['street'], $row['city'], $row['state'], $row['zip']);
        }
        $table = str_replace("'", '', $table);
        $table = str_replace("\n", '', $table);
        $table .= '</table>';
        $this->addOnloadCommand("\$('#addrDiv').html('{$table}');");

        return $data;
    }

    public function form_content()
    {
        $since = date('Y-m-d', strtotime('6 months ago'));
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Top X Accounts</label>
        <input type="text" name="top" value="50" class="form-control" />
    </div>
    <div class="form-group">
        <label>Since</label>
        <input type="text" name="since" value="{$since}" class="form-control" />
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="members" value="1" checked />
            Only Active Accounts</label>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

