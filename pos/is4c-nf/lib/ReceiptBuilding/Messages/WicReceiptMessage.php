<?php

namespace COREPOS\pos\lib\ReceiptBuilding\Messages;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use \CoreLocal;

class WicReceiptMessage extends ReceiptMessage
{
    public $standalone_receipt_type = 'wicSlip';

    private $balanceOnly = false;

    public function standalone_receipt($ref, $reprint=False)
    {
        $this->balanceOnly = true;
        return 
            ReceiptLib::printReceiptHeader(time(), $ref)
            . $this->message(1, $ref, $reprint)
            . ReceiptLib::centerString("................................................")."\n"
            . ReceiptLib::centerString("E L I G I B L E    I T E M S") . "\n"
            . ReceiptLib::centerString("................................................")."\n"
            . $this->potentialItems(CoreLocal::get('EWicBalance'))
            . ReceiptLib::centerString("................................................")."\n";
    }

    public function potentialItems($wicData)
    {
        $ret = "";
        $categories = array();
        foreach ($wicData as $balanceRecord) {
            if (isset($balanceRecord['subcat']) && $balanceRecord['qty'] > 0) {
                $key = $balanceRecord['cat']['eWicCategoryID']
                    . ':' . $balanceRecord['subcat']['eWicSubCategoryID'];
                $categories[$key] = $balanceRecord['qty'];
            } elseif ($balanceRecord['qty'] > 0) {
                $key = $balanceRecord['cat']['eWicCategoryID'];
                $categories[$key] = $balanceRecord['qty'];
            }
        }

        $dbc = Database::tDataConnect();
        $appliedP = $dbc->prepare("
            SELECT c.trans_id
            FROM couponApplied AS c
                INNER JOIN localtemptrans AS t ON c.trans_id=t.trans_id
            WHERE t.upc=?");
        $couponP = $dbc->prepare("SELECT SUM(total) FROM localtemptrans WHERE upc LIKE ?");
        $res = $dbc->query('SELECT t.upc, SUM(quantity) AS qty, SUM(total) AS ttl, description, eWicCategoryID, eWicSubCategoryID, e.broadband,
                MAX(discountable) AS discountable, MAX(percentDiscount) AS percentDiscount, t.upc, e.multiplier
            FROM localtemptrans AS t
                INNER JOIN ' . CoreLocal::get('pDatabase') . $dbc->sep() . 'EWicItems AS e ON t.upc=e.upc
            GROUP BY upc, description, eWicCategoryID, eWicSubCategoryID, e.broadband, t.upc, e.multiplier
            HAVING SUM(total) > 0
            ORDER BY e.broadband, e.multiplier DESC');
        $couponCache = array();
        while ($row = $dbc->fetchRow($res)) {
            $manu = substr($row['upc'], 3, 5);
            if ($manu != '00000' && !isset($couponCache[$manu])) {
                $couponApplied = $dbc->getValue($appliedP, array($row['upc']));
                if ($couponApplied) {
                    $couponValue = $dbc->getValue($couponP, array('005' . $manu . '%'));
                    $row['ttl'] += $couponValue; // coupons are negative
                    if ($row['ttl'] < 0.005) { // coupon made item free
                        continue;
                    }
                }
                $couponCache[$manu] = true;
            }
            if ($row['discountable'] && $row['percentDiscount']) {
                $row['ttl'] *= (1 - ($row['percentDiscount'] / 100));
            }
            /**
                Check if the whole category is available. If not
                check whether the specific subcategory is availble.
                Keep $key to later decrement the appropriate quantity
            */
            $add = false;
            $key = $row['eWicCategoryID'];
            if ($row['broadband'] && isset($categories[$key]) && $categories[$key] > 0) {
                $add = true;
            } else {
                $key = $row['eWicCategoryID'] . ':' . $row['eWicSubCategoryID'];
                if (isset($categories[$key]) && $categories[$key] > 0) {
                    $add = true;
                }
            }
            if ($add) {
                if ($row['eWicCategoryID'] == 19) {
                    if ($row['ttl'] > $categories[$key]) {
                        $row['ttl'] = $categories[$key];
                    }
                    $categories[$key] -= $row['ttl'];
                } else {
                    while ($row['qty'] * $row['multiplier'] > $categories[$key]) {
                        $price = $row['ttl'] / $row['qty'];
                        $row['qty'] -= 1;
                        $row['ttl'] -= $price;
                    }
                    // package size exceeds remaing quantity
                    if ($row['qty'] <= 0) {
                        continue;
                    }
                    $categories[$key] -= $row['qty'] * $row['multiplier'];
                }
                $ret .= str_pad($row['description'], 36, ' ', STR_PAD_RIGHT)
                    . str_pad($row['qty'] . 'x', 8, ' ', STR_PAD_LEFT)
                    . str_pad($row['ttl'], 8, ' ', STR_PAD_LEFT)
                    . "\n";
            }
        }

        return $ret;
    }

    public function select_condition()
    {
        return "MAX(CASE WHEN trans_subtype IN ('EW') THEN trans_id ELSE 0 END)";
    }

    public function message($val, $ref, $reprint=False)
    {
        $date = date('Ymd');
        list($emp, $reg, $trans) = ReceiptLib::parseRef($ref);
        $slip = '';

        $dbc = Database::tDataConnect();
        if ($reprint) {
            $dbc = Database::mDataConnect();
            if ($dbc === false) {
                return '';
            }
        }

        $transType = $dbc->concat('p.cardType', "' '", 'p.transType', '');

        $query = "SELECT p.amount,
                    p.name,
                    p.PAN,
                    p.refNum,
                    $transType AS ebtMode,
                    p.xResultMessage,
                    p.xTransactionID,
                    p.xBalance,
                    p.requestDatetime AS datetime
                  FROM PaycardTransactions AS p
                  WHERE dateID=" . date('Ymd') . "
                    AND empNo=" . $emp . "
                    AND registerNo=" . $reg . "
                    AND transNo=" . $trans . "
                    AND p.validResponse=1
                    AND p.xResultMessage LIKE '%APPROVE%'
                    AND p.cardType = 'EWIC'
                  ORDER BY p.requestDatetime";
        $result = $dbc->query($query);
        $prevRefNum = false;
        $prevMode = false;
        while ($row = $dbc->fetchRow($result)) {

            // failover to mercury's backup server can
            // result in duplicate refnums. this is
            // by design (theirs, not CORE's)
            if ($row['refNum'] == $prevRefNum && $row['ebtMode'] == $prevMode) {
                continue;
            }
            $slip .= ReceiptLib::centerString("................................................")."\n";
            // store header
            for ($i=1; $i<= CoreLocal::get('chargeSlipCount'); $i++) {
                $slip .= ReceiptLib::centerString(CoreLocal::get("chargeSlip" . $i))."\n";
            }
            $slip .= "\n";
            $col1 = array();
            $col2 = array();
            $col1[] = $row['ebtMode'];
            $col2[] = "Entry Method: swiped\n";
            $col1[] = "Sequence: " . $row['xTransactionID'];
            $col2[] = "Card: ".$row['PAN'];
            $col1[] = "Authorization: " . $row['xResultMessage'];
            $col2[] = ReceiptLib::boldFont() . "Amount: " . $row['amount'] . ReceiptLib::normalFont();
            $slip .= ReceiptLib::twoColumns($col1, $col2);

            $slip .= ReceiptLib::centerString("................................................")."\n";

            $prevRefNum = $row['refNum'];
        }

        if ($this->balanceOnly) {
            $slip = '';
        }

        $dbc = Database::tDataConnect();
        $emvP = $dbc->prepare('
            SELECT content
            FROM EmvReceipt
            WHERE dateID=?
                AND empNo=?
                AND registerNo=?
                AND transNo=?
                AND content LIKE \'%Benefits expire%\'
            ORDER BY transID DESC, tdate DESC
        ');
        $balance = $dbc->getValue($emvP, array($date, $emp, $reg, $trans));
        $slip .= $balance ? $balance : '';

        return $slip;
    }
}

