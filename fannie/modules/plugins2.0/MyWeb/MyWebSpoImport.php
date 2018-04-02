<?php

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('SpecialOrderLib')) {
    include_once(__DIR__ . '/../../../ordering/SpecialOrderLib.php');
}
if (!class_exists('OrderItemLib')) {
    include_once(__DIR__ . '/../../../ordering/OrderItemLib.php');
}

/**
 * @class MyWebSpoImport
 *
 * Cron task to query the website and import new, pending re-orders.
 * This is normally triggered via MySpoMailPipe but should still run
 * periodically in case some step in the email delivery chain hiccups.
 *
 * Concurrent runs are prevented via simple file lock. If orders stop
 * importing check for a /tmp/MyWebSpoImport.lock file.
 *
 * This is intended to be functionally identical to duplicating an order
 * although the end-user could select line items from several different
 * orders.
 */
class MyWebSpoImport extends FannieTask 
{
    public $name = 'My Web SPO Import Task';
    public $description = 'Imports special order data from online orders';

    public function run()
    {
        if ($this->isLocked()) {
            return true;
        }
        $this->lock();

        $local = FannieDB::get($this->config->get('OP_DB'));
        require(__DIR__ . '/../../../src/Credentials/OutsideDB.tunneled.php');
        $tagP = $dbc->prepare('UPDATE ReorderQueue SET processed=? WHERE reorderQueueID=?');

        $res = $dbc->query('SELECT * FROM ReorderQueue WHERE processed=0');
        while ($row = $dbc->fetchRow($res)) {
            $json = json_decode($row['json'], true);
            if (!is_array($json) || count($json) == 0) {
                // invalid data. mark it so it doesn't persist
                // indefinitely
                $dbc->execute($tagP, array(2, $row['reorderQueueID']));
                continue;
            }

            $spoLib = new SpecialOrderLib($local, $this->config);
            $orderID = $spoLib->createEmptyOrder();
            $this->setMember($local, $orderID, $row);
            $this->addItems($local, $orderID, $json, $row['cardNo']);

            $dbc->execute($tagP, array(1, $row['reorderQueueID']));
        }
        $this->unlock();
    }

    private function setMember($dbc, $orderID, $queue)
    {
        $cardNo = $queue['cardNo'];
        $person = $this->findPersonByName($dbc, $cardNo, $queue['name']);

        $setP = $dbc->prepare('UPDATE ' . FannieDB::fqn('PendingSpecialOrder', 'trans') . ' SET card_no=?, voided=?, mixMatch=\'website\' WHERE order_id=?');
        $setR = $dbc->execute($setP, array($cardNo, $person, $orderID));

        $setP = $dbc->prepare('UPDATE ' . FannieDB::fqn('PendingSpecialOrder', 'trans') . ' AS p
                        INNER JOIN ' . FannieDB::fqn('meminfo', 'op') . ' AS m ON p.card_no=m.card_no
                        INNER JOIN ' . FannieDB::fqn('SpecialOrders', 'trans') . ' AS o ON p.order_id=o.specialOrderID
                    SET o.street=m.street,
                        o.city=m.city,
                        o.zip=m.zip,
                        o.phone=?,
                        o.email=?,
                        o.notes=?,
                        o.storeID=?,
                        o.statusFlag=?,
                        o.subStatus=?
                    WHERE p.order_id=?');
        $setR = $dbc->execute($setP, array(
            $queue['phone'],
            $queue['email'],
            $queue['notes'],
            $queue['storeID'],
            $queue['confirm'] ? 3 : 0,
            time(),
            $orderID,
        ));

        $this->setContactMethod($dbc, $cardNo, $orderID);
    }

    private function findPersonByName($dbc, $cardNo, $name)
    {
        $person = 1;
        $findP = $dbc->prepare('SELECT personNum FROM custdata WHERE CardNo=? AND (FirstName LIKE ? OR LastName LIKE ?)');
        foreach (explode(' ', $name) as $part) {
            $part = trim($part);
            if (strlen($part) < 2) continue;
            $match = $dbc->getValue($findP, array($cardNo, '%' . $part . '%', '%' . $part . '%'));
            if ($match !== false) {
                $person = $match;
                break;
            }
        }

        return $person;
    }

    private function setContactMethod($dbc, $cardNo, $orderID)
    {
        $contactQ = "
            SELECT sendEmails
            FROM " . FannieDB::fqn('PendingSpecialOrder', 'trans') . " AS p
                INNER JOIN " . FannieDB::fqn('SpecialOrders', 'trans') . " AS s ON p.order_id=s.specialOrderID
            WHERE p.card_no=?
                AND p.order_id <> ?
            ORDER BY p.order_id DESC";
        $contact = $dbc->getValue($dbc->prepare($contactQ), array($cardNo, $orderID));
        if ($contact === false) {
            $contactQ = str_replace('PendingSpecialOrder', 'CompleteSpecialOrder', $contactQ);
            $contact = $dbc->getValue($dbc->prepare($contactQ), array($cardNo, $orderID));
        }
        if ($contact !== false) {
            $upP = $dbc->prepare('UPDATE ' . FannieDB::fqn('SpecialOrders', 'trans') . '  SET sendEmails=? WHERE specialOrderID=?');
            $dbc->execute($upP, array($contact, $orderID));
        }
    }

    private function addItems($dbc, $orderID, $items, $cardNo)
    {
        $PENDING = FannieDB::fqn('PendingSpecialOrder', 'trans');
        $tidP = $dbc->prepare("SELECT MAX(trans_id),MAX(voided),MAX(numflag) 
                FROM {$PENDING} WHERE order_id=?");
        $tidW = $dbc->getRow($tidP, array($orderID));

        $copyP = $dbc->prepare("
            INSERT INTO {$PENDING}
            SELECT ?,".$dbc->now().",
            register_no,emp_no,trans_no,upc,description,
            trans_type,trans_subtype,trans_status,
            department,quantity,scale,cost,unitPrice,
            total,regPrice,tax,foodstamp,discount,
            memDiscount,discountable,discounttype,
            voided,percentDiscount,ItemQtty,volDiscType,
            volume,VolSpecial,mixMatch,matched,memtype,
            staff,0,'',card_no,?
            FROM " . FannieDB::fqn('CompleteSpecialOrder', 'trans') . " WHERE order_id=? AND trans_id=?");
        $transID = $tidW[0] + 1;
        foreach ($items as $item) {
            $row = OrderItemLib::getItem($item['upc']);
            if ($item['upc'] == '0000000000000' || (empty($row['description']) && $row['normal_price'] == 0)) {
                $dbc->execute($copyP, array($orderID, $transID, $item['orderID'], $item['transID']));
                $transID++;
                continue;
            }

            $mempricing = OrderItemLib::memPricing($cardNo);
            $row['department'] = OrderItemLib::mapDepartment($row['department']);
            $unitPrice = OrderItemLib::getUnitPrice($row, $mempricing);
            $casePrice = OrderItemLib::getCasePrice($row, $mempricing);
            if ($unitPrice == $row['normal_price'] && !OrderItemLib::useSalePrice($row, $mempricing)) {
                $item['discounttype'] = 0;
            }

            $ins_array = array(
                'order_id' => $orderID,
                'datetime'=>date('Y-m-d H:i:s'),
                'emp_no'=>1001,
                'register_no'=>30,
                'trans_no'=>$orderID,
                'trans_subtype'=>"",
                'trans_status'=>"",
                'scale'=>0,
                'tax'=>0, // handled lane-side
                'foodstamp'=>0,
                'discount'=>0,
                'memDiscount'=>0,
                'percentDiscount'=>0,
                'volDiscType'=>0,
                'matched'=>0,
                'volume'=>0,
                'VolSpecial'=>0,
                'memType'=>0,
                'staff'=>0,
                'charflag'=>"",   
            );
            $ins_array['upc'] = $item['upc'];
            $ins_array['card_no'] = $cardNo;
            $ins_array['trans_type'] = 'I';
            $ins_array['ItemQtty'] = $item['qty'];
            $ins_array['quantity'] = $row['caseSize'];
            $ins_array['mixMatch'] = $row['vendorName'];
            $ins_array['description'] = substr($row['description'], 0, 32) . ' SO';
            $ins_array['department'] = $row['department'];
            $ins_array['discountable'] = $row['discountable'];
            $ins_array['discounttype'] = $row['discounttype'];
            $ins_array['cost'] = $row['cost'];
            $ins_array['unitPrice'] = $unitPrice;
            $ins_array['total'] = $casePrice * $item['qty'];
            $ins_array['regPrice'] = $row['normal_price'] * $row['caseSize'] * $item['qty'];
            $ins_array['trans_id'] = $transID;
            $ins_array['voided'] = $tidW[1];
            $ins_array['numflag'] = $tidW[2];
            $dbc->smartInsert($PENDING, $ins_array);

            $transID++;
        }
    }
}

