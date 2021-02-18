<?php

use COREPOS\Fannie\API\FannieUploadPage;
use COREPOS\Fannie\API\item\ItemText;

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class MercatoTxnChecker extends FannieUploadPage
{
    protected $header = 'Mercato Transaction Checker';
    protected $title = 'Mercato  Transaction Checker';

    protected $preview_opts = array(
        'date' => array(
            'name' => 'date',
            'display_name' => 'Date (UTC)',
            'default' => 1,
            'required' => true,
        ),
        'store' => array(
            'name' => 'store',
            'display_name' => 'Store ID',
            'default' => 3,
            'required' => true,
        ),
        'order' => array(
            'name' => 'order',
            'display_name' => 'Order ID',
            'default' => 6,
            'required' => true,
        ),
        'total' => array(
            'name' => 'total',
            'display_name' => 'Total',
            'default' => 14,
            'required' => true,
        ),
    );

    private $results = array();

    public function process_file($linedata, $indexes)
    {
        $orderID = false;
        $total = 0;
        $date = false;
        $store = 0;
        array_shift($linedata); // drop headers
        $orders = array();
        foreach ($linedata as $data) {
            $orderID = $data[$indexes['order']];
            if (!is_numeric($orderID)) {
                continue;
            }
            if (!isset($orders[$orderID])) {
                $utc = new DateTime($data[$indexes['date']] . ' UTC');
                $local = $utc->setTimeZone(new DateTimeZone('America/Chicago'));
                $store = $data[$indexes['store']] == 1692 ? 1 : 2;
                $date = $local->format('Y-m-d');
                $orders[$orderID] = array(
                    'date' => $date,
                    'store' => $store,
                    'toal' => 0
                );
            }
            $orders[$orderID]['total'] += $data[$indexes['total']];
        }
        foreach ($orders as $orderID => $data) {
            $dlog = DTransactionsModel::selectDlog($data['date']);
            $prep = $this->connection->prepare("SELECT -1 * SUM(total) AS ttl FROM {$dlog}
                WHERE tdate BETWEEN ? AND ?
                    AND emp_no=1001
                    AND register_no=40
                    AND trans_no=?
                    AND store_id=?
                    AND trans_type='T'");
            $val = $this->connection->getValue($prep, array($data['date'], $data['date'] . ' 23:59:59', $orderID, $data['store']));
            if (abs($val - $data['total']) > 0.005) {
                $this->results[] = $data['date'] . ', Store #' . $data['store'] . ', Order# ' . $orderID
                    . ', POS says ' . $val . ', Report says ' . $data['total'];
            }
        }

        return true;
    }

    public function results_content()
    {
        $ret = '<ul>';
        foreach ($this->results as $r) {
            $ret .= '<li>' . $r . '</li>';
        }
        $ret .= '</ul>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

