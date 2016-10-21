<?php
include('../../../../config.php');
if (!class_exists('FannieAPI.php')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class OwnersByStoreReport extends FannieReportPage
{
    protected $report_headers = array('Store', 'Member Count', 'Total Spending', 'Avg. Spending');

    public function fetch_report_data()
    {
        $query = "select card_no, store_id, sum(total) 
            from trans_archive.dlogBig
            where tdate between '2016-09-01 00:00:00' and '2016-09-30 00:00:00' 
                and trans_type in ('I','D') 
                and store_id <> 50 
                AND memType IN (1,3,5)
            group by card_no, store_id 
            order by store_id";
        $dbc = $this->connection;
        $res = $dbc->query($query);
        $hillside = $denfeld = $both = array();
        while ($row = $dbc->fetchRow($res)) {
            if ($row['store_id'] == 1) {
                $hillside[$row['card_no']] = $row[2];
            } elseif ($row['store_id'] == 2) {
                if (isset($hillside[$row['card_no']])) {
                    $both[$row['card_no']] = $row[2] + $hillside[$row['card_no']];
                    unset($hillside[$row['card_no']]);
                } else {
                    $denfeld[$row['card_no']] = $row[2];
                }
            }
        }

        $count = $ttl = 0;
        foreach ($hillside as $entry) {
            $count++;
            $ttl += $entry;
        }
        $h_record = array('Hillside only', $count, sprintf('%.2f', $ttl), sprintf('%.2f', $ttl/$count));
        $count = $ttl = 0;
        foreach ($denfeld as $entry) {
            $count++;
            $ttl += $entry;
        }
        $d_record = array('Denfeld only', $count, sprintf('%.2f', $ttl), sprintf('%.2f', $ttl/$count));
        $count = $ttl = 0;
        foreach ($both as $entry) {
            $count++;
            $ttl += $entry;
        }
        $b_record = array('Both', $count, sprintf('%.2f', $ttl), sprintf('%.2f', $ttl/$count));

        return array(
            $h_record,
            $d_record,
            $b_record,
        );
    }
}

FannieDispatch::conditionalExec();

