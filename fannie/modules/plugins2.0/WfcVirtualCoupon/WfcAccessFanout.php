<?php

use COREPOS\Fannie\API\data\DataCache;

class WfcAccessFanout extends FannieTask 
{
    public $name = 'WFC Access Fan Out';
    public $description = 'Push new access discount sign-ups out to all lanes';
    public $log_start_stop = false;

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $trans = $this->config->get('TRANS_DB') . $dbc->sep();

        $checkP = $dbc->prepare('SELECT memType, Type FROM custdata WHERE CardNo=?');
        $setP = $dbc->prepare('UPDATE custdata SET memType=5, Discount=10 WHERE CardNo=?');
        $set2P = $dbc->prepare('UPDATE CustomerAccounts SET customerTypeID=5 WHERE cardNo=?');
        $set3P = $dbc->prepare('UPDATE Customers SET discount=10 WHERE cardNo=?');

        $allR = $dbc->query("SELECT card_no, trans_num FROM {$trans}dlog WHERE upc='ACCESS'");
        $pushes = array();
        $notify = array();
        $dbc->startTransaction();
        while ($allW = $dbc->fetchRow($allR)) {
            $card = $allW['card_no'];
            $row = $dbc->getRow($checkP, array($card));
            if ($row['memType'] == 5) {
                // already access member
                continue;
            } elseif ($row['Type'] != 'PC') {
                // invalid signup? 
                $row['trans_num'] = $allW['trans_num'];
                $notify[$card] = $row;
                continue;
            }

            $dbc->execute($setP, array($card));
            $dbc->execute($set2P, array($card));
            $dbc->execute($set3P, array($card));
            $pushes[] = $card;
        }
        $dbc->commitTransaction();

        $this->fanout($dbc, $pushes);
        $notify = $this->filterAlreadySent($notify);
        $this->notify($notify);
    }

    /**
     * Push modified accounts out to the lanes
     * @param $dbc [SQLManager] server database connection
     * @param $pushes [array] owner account numbers
     */
    private function fanout($dbc, $pushes)
    {
        foreach ($pushes as $card) {
            $this->cronMsg("Fanning out {$card}");
            $custdata = new CustdataModel($dbc);
            $custdata->CardNo($card);
            foreach ($custdata->find() as $c) {
                $c->pushToLanes();
            }

            $prep = $dbc->prepare('
                SELECT webServiceUrl FROM Stores WHERE hasOwnItems=1 AND storeID<>?
                ');
            $res = $dbc->execute($prep, array($this->config->get('STORE_ID')));
            while ($row = $dbc->fetchRow($res)) {
                $client = new \Datto\JsonRpc\Http\Client($row['webServiceUrl']);
                $client->query(time(), 'COREPOS\\Fannie\\API\\webservices\\FannieMemberLaneSync', array('id'=>$card));
                $client->send();
            }
        }
    }

    /**
     * Filter pending notifiactions based on whether a notification
     * has already been triggered for a given account. Checks
     * and updates a DataCache file.
     * @param $notify [array] owner number => additional info
     */
    private function filterAlreadySent($notify)
    {
        $ret = array();
        $cache = DataCache::getFile('daily', 'WfcAccessFanout');
        $cache = json_decode($cache);
        if (!is_array($cache)) {
            $cache = array();
        }
        foreach ($notify as $card => $info) {
            if (in_array($card, $cache)) {
                continue;
            }
            $ret[$card] = $info;
            $cache[] = $card;
        }
        DataCache::putFile('daily', json_encode($cache), 'WfcAccessFanout');

        return $ret;
    }

    /**
     * Send notifications on accounts
     * @param $notify [array] owner number => additional info
     */
    private function notify($notify)
    {
        if (count($notify) > 0) {
            $addr = 'andy@wholefoods.coop';
            $subject = 'Bad Access Sign-up(s)';
            $from = "From: automail\r\n";
            $msg = 'Problem on ' . date('Y-m-d') . "\n";
            foreach ($notify as $card => $info) {
                $msg .= "Owner #{$card}, status {$info['Type']}, transaction {$info['trans_num']}\n";
            }
            mail($addr, $subject, $msg, $from);
        }
    }
}

