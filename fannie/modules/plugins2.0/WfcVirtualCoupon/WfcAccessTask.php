<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class WfcAccessTask extends FannieTask 
{
    public $name = 'WFC Access Renewer';

    public $description = 'Auto-renew access owner accounts when using SNAP or WIC';

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $trans = $this->config->get('TRANS_DB') . $dbc->sep();

        $res = $dbc->query("SELECT card_no,
                SUM(CASE WHEN trans_subtype IN ('EF','EC') THEN 1 ELSE 0 END) AS isSnap
            FROM {$trans}dlog
            WHERE trans_type='T'
                AND memType=5
                AND (
                    trans_subtype IN ('EF','EC')
                    OR
                    description = 'WIC'
                )
            GROUP BY card_no
            HAVING SUM(total) <> 0");

        $record = DTrans::defaults();
        $record['emp_no'] = 1001;
        $record['register_no'] = 30;
        $record['trans_no'] = DTrans::getTransNo($dbc, 1001, 30);
        $record['trans_id'] = 1;
        $record['trans_type'] = 'I';
        $record['upc'] = 'ACCESS';
        $record['description'] = 'ACCESS SIGNUP';
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $dbc->startTransaction();
        while ($row = $dbc->fetchRow($res)) {
            $record['card_no'] = $row['card_no'];
            $record['numflag'] = $row['isSnap'] ? 6 : 8;
            $pInfo = DTrans::parameterize($record, 'datetime', $dbc->now());
            $insP = $dbc->prepare("INSERT INTO {$trans}dtransactions ({$pInfo['columnString']}) VALUES ({$pInfo['valueString']})");
            $dbc->execute($insP, $pInfo['arguments']);
            $record['trans_id']++;
        }
        $dbc->commitTransaction();
    }
}

