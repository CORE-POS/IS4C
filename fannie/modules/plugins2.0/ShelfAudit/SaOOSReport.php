<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class SaOOSReport extends FannieReportPage 
{
    public $description = '[Out of Stocks] shows the number of out-of-stocks in a given time period';
    public $report_set = 'Out of Stocks';

    protected $title = "Fannie : Out of Stocks Report";
    protected $header = "Out of Stocks Report";
    protected $report_headers = array('Vendor','Super','UPC','Brand','Item','# Times');
    protected $required_fields = array('date1', 'date2');
    protected $new_tablesorter = true;

    public function fetch_report_data()
    {
        try {
            $dlog = DTransactionsModel::selectDTrans($this->form->date1, $this->form->date2);
        } catch (Exception $ex) {
            return array();
        }

        $store = FormLib::get('store');
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $parts = FormLib::standardItemFromWhere();
        $parts['query'] = str_replace('dlog', 'dtransactions', $parts['query']);
        $parts['query'] = str_replace('t.tdate', 't.datetime', $parts['query']);

        $query = "SELECT t.upc, t.description, SUM(t.quantity) AS qty,
                p.brand, v.vendorName, m.super_name
            " . $parts['query'] . "
                AND trans_status='X'
                AND mixMatch='OOS'
            GROUP BY t.upc, t.description, p.brand, m.super_name";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $parts['args']);

        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['vendorName'] ? $row['vendorName'] : '',
                $row['super_name'],
                sprintf('<a href="SaOOSItemReport.php?upc=%s&store=%d">%s</a>',
                    $row['upc'], $store, $row['upc']),
                $row['brand'] ? $row['brand'] : '',
                $row['description'],
                $row['qty'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        return FormLib::dateAndDepartmentForm(true);
    }
}

FannieDispatch::conditionalExec();

