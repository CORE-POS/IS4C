<?php

/**
 * This page does not really have a UI and just exists
 * to run javascript and process responses. It flows like this:
 *
 * 1. First page load
 *      a. Generate Balance transaction XML
 *      b. Submit XML to driver via javascript
 *      c. POST driver response XML back to this page
 * 2. Second page load
 *      a. Bail out on error
 *      b. Generate Sale transaction XML
 *      b. Submit XML to driver via javascript
 *      c. POST driver response XML back to this page
 * 3. Third page load
 *      a. Bail out on error
 *      b. Examine response and apply tender
 */

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\LaneLogger;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\plugins\Paycards\xml\BetterXmlData;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardEmvWic extends PaycardProcessPage 
{
    private $runTransaction = false;

    function preprocess()
    {
        // I haven't tracked down the cause but some mechanisms for getting here
        // do NOT unset msgrepeat which leads to an approval immediately triggering
        // into another ewic transaction
        $this->conf->set('msgrepeat', 0);
        if (FormLib::get('xml-resp', false) !== false) {
            $xml = FormLib::get('xml-resp');
            $log = new LaneLogger();
            $log->debug("WIC RESPONSE: " . $xml);
            if ($this->conf->get('EWicStep') == 0) {
                $e2e = new MercuryDC();
                $this->conf->set('EWicLast4', false);
                $success = $e2e->handleResponseDataCapBalance($xml);
                if ($success === PaycardLib::PAYCARD_ERR_OK) {
                    $this->conf->set('EWicStep', 1);
                    $cur = $this->session->get('receiptToggle');
                    $this->conf->set('receiptToggle', 1);
                    $receipt = ReceiptLib::printReceipt('wicSlip', ReceiptLib::receiptNumber());
                    $this->conf->set('receiptToggle', $cur);
                    ReceiptLib::writeLine($receipt);
                } else {
                    $this->cleanup();
                    $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
                    return false;
                }
            } elseif ($this->conf->get('EWicStep') == 1) {
                $e2e = new MercuryDC();
                $success = $e2e->handleResponseDataCap($xml);
                if ($success === PaycardLib::PAYCARD_ERR_OK) {
                    $this->tenderResponse($xml);
                    $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php?reginput=TO&repeat=1');
                } else {
                    $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
                }
                $this->cleanup();
                return false;
            }
        } elseif (FormLib::get('reginput', false) === false) {
            $this->cleanup();
            $this->runTransaction = true;
            $this->addOnloadCommand('balanceSubmit();');
        } else {
            $input = FormLib::get('reginput', false);
            if (strtoupper($input) == 'CL') {
                $this->cleanup();
                $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php');
                return false;
            } elseif (strtoupper($input) == 'RP') {
                $cur = $this->session->get('receiptToggle');
                $this->conf->set('receiptToggle', 1);
                $receipt = ReceiptLib::printReceipt('wicSlip', ReceiptLib::receiptNumber());
                $this->conf->set('receiptToggle', $cur);
                ReceiptLib::writeLine($receipt);
                return true;
            } elseif ($input === '' && $this->conf->get('EWicStep') == 1) {
                $this->runTransaction = true;
                $this->addOnloadCommand('emvSubmit();');
            }
        }

        return true;
    }

    private function cleanup()
    {
        $this->conf->reset();
        $this->conf->set('EWicStep', 0);
        $this->conf->set('EWicLast4', false);
    }

    /**
     * Adjust item pricing on anything approved for a lower
     * amount and then add the tender record
     */
    private function tenderResponse($xml)
    {
        $dbc = Database::tDataConnect();
        $setUnit = $dbc->prepare("UPDATE localtemptrans SET unitPrice=?, charflag='WO' WHERE upc=?");
        $setTotal = $dbc->prepare("UPDATE localtemptrans SET total=unitPrice*quantity WHERE upc=?");
        $translateP = $dbc->prepare("SELECT * FROM " . $this->conf->get('pDatabase') . $dbc->sep() . "EWicItems WHERE upcCheck=?");
        $aliasP = $dbc->prepare("SELECT * FROM " . $this->conf->get('pDatabase') . $dbc->sep() . "EWicItems WHERE alias=?");
        $better = new BetterXmlData($xml);
        $i = 1;
        while (true) {
            $status = $better->query('/RStream/TranResponse/ItemData/ItemStatus' . $i);
            $status = strtolower($status);
            if ($status === 'approved for lower price') {
                $price = $better->query('/RStream/TranResponse/ItemData/ItemPrice' . $i);
                $qty = $better->query('/RStream/TranResponse/ItemData/ItemQty' . $i);
                $wicUPC = $better->query('/RStream/TranResponse/ItemData/UPCItem' . $i);
                $unit = $price / $qty;
                $ours = $dbc->getRow($translateP, array($wicUPC));
                if ($ours === false) {
                    $ours = $dbc->getRow($aliasP, array($wicUPC));
                }
                $dbc->execute($setUnit, array($unit, $ours['upc']));
                $dbc->execute($setTotal, array($ours['upc']));
            } elseif ($status === 'approved for lower qty') {
                // this creates kind of a mess if items are rung with *
                // so a single line item might be partly approved
            }

            $i++;
            if ($i > 1000) break;
        }

        $amount = $better->query('/RStream/TranResponse/Amount/Authorize');
        $amount = "" . (-1*$amount);
        $ptP = $dbc->prepare('SELECT MAX(paycardTransactionID) FROM PaycardTransactions');
        $ptID = $dbc->getValue($ptP);
        TransRecord::addFlaggedTender('EWIC', 'EW', $amount, $ptID, 'PT');
    }

    /**
     * Get item data XML for eligible items
     * @param $arr [array] EWic Balance Data
     * @return [string] XML
     *
     * This is a fairly simplistic approach. It gets all EWic items
     * from the current transaction and just checks whether its category
     * and subcategory is present in the balance data. This will result in
     * sending excess quantities sometimes but partial approvals need to
     * get handled with the response anyway.
     *
     * Erring on the side of sending too many items seems better for the
     * customer. Any mistake in an overly stringent approach here and *not*
     * sending an item that's actually eligible means the customer can't
     * fully utilize their balance.
     */
    private function getItemData($arr)
    {
        $ret = '';
        $categories = array('cat'=>array(), 'sub'=>array());
        foreach ($arr as $balanceRecord) {
            $categories['cat'][$balanceRecord['cat']['eWicCategoryID']] = true;
            if (isset($balanceRecord['subcat'])) {
                $categories['sub'][$balanceRecord['subcat']['eWicSubCategoryID']] = true;
            }
        }

        $dbc = Database::tDataConnect();
        $res = $dbc->query('SELECT SUM(t.quantity) AS qty, SUM(t.total) AS ttl,
            CASE WHEN e.alias IS NOT NULL THEN e.alias ELSE e.upc END AS upc,
            e.upcCheck, e.eWicCategoryID, e.eWicSubCategoryID
            FROM localtemptrans AS t
                INNER JOIN ' . $this->conf->get('pDatabase') . $dbc->sep() . 'EWicItems AS e ON t.upc=e.upc
            GROUP BY
            CASE WHEN e.alias IS NOT NULL THEN e.alias ELSE e.upc END,
            e.upcCheck, e.eWicCategoryID, e.eWicSubCategoryID');
        $i = 1;
        $total = 0;
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upcCheck'];
            $add = false;
            if ($row['eWicCategoryID'] && isset($categories['cat'][$row['eWicCategoryID']])) {
                $ret .= "<UPCItem{$i}>{$upc}</UPCItem{$i}>";
                $add = true;
            }
            if ($add) {
                $ret .= "<ItemQty{$i}>" . sprintf('%.2f', $row['qty']) . "</ItemQty{$i}>";
                $ret .= "<ItemPrice{$i}>" . sprintf('%.2f', $row['ttl']) . "</ItemPrice{$i}>";
                $i++;
                $total += $row['ttl'];
            }
        }
        $this->conf->set('paycard_amount', $total);

        return $ret;
    }

    function head_content()
    {
        $url = MiscLib::baseURL();
        echo '<script type="text/javascript" src="' . $url . '/js/singleSubmit.js"></script>';
        echo '<script type="text/javascript" src="../js/emv.js?date=20180308"></script>';
        if (!$this->runTransaction) {
            return '';
        }
        $e2e = new MercuryDC($this->conf->get('PaycardsDatacapName'));
        $manual = FormLib::get('manual') ? true : false;
        $xml = '';
        if ($this->conf->get('EWicStep') == 0) {
            $this->conf->set('paycard_id', $this->conf->get('LastID')+1);
            $this->conf->set('EWicAcAcq', false);
            $xml = $e2e->prepareDataCapBalance('EWICVAL', $manual);
        } elseif ($this->conf->get('EWicStep') == 1) {
            $this->conf->set('paycard_id', $this->conf->get('LastID')+1);
            $xml = $e2e->prepareDataCapWic($this->getItemData($this->conf->get('EWicBalance')), 'PreAuthCapture', $this->conf->get('EWicLast4'));
        }
        $log = new LaneLogger();
        $log->debug("WIC REQUEST: " . $xml);
        ?>
<script type="text/javascript">
function balanceSubmit() {
    emv.setWaitingMsg('Getting balance');
    emv.showProcessing('div.baseHeight');
    var xmlData = '<?php echo json_encode($xml); ?>';
    <?php if ($this->conf->Get('training') == 1) { ?>
    emv.setURL('../ajax/AjaxPaycardTest.php');
    <?php } ?>
    emv.submit(xmlData);
    $(document).keyup(checkForCancel);
}
function emvSubmit() {
    emv.setWaitingMsg('Authorizing purchase');
    emv.showProcessing('div.baseHeight');
    var xmlData = '<?php echo json_encode($xml); ?>';
    // POST XML request to driver using AJAX
    if (xmlData == '"Error"') { // failed to save request info in database
        location = '<?php echo MiscLib::baseURL(); ?>gui-modules/boxMsg2.php';
        return false;
    }
    <?php if ($this->conf->Get('training') == 1) { ?>
    emv.setURL('../ajax/AjaxPaycardTest.php');
    <?php } ?>
    emv.submit(xmlData);
    $(document).keyup(checkForCancel);
}
var ccKey1;
var ccKey2;
function checkForCancel(ev) {
    var jsKey = ev.which ? ev.which : ev.keyCode;
    if (jsKey == 13 && (ccKey2 == 99 || ccKey2 == 67) && (ccKey1 == 108 || ccKey1 == 76)) {
        $.ajax({
            url: 'PaycardEmvPage.php',
            data: 'cancel=1'
        }).done(function(){
        });
    }
    ccKey2 = ccKey1;
    ccKey1 = jsKey;
}
</script>
        <?php
    }

    function body_content()
    {
        echo '<div class="baseHeight">';
        if ($this->conf->get('EWicStep') == 1) {
            $msg = '<div style="font-size: 85%">';
            foreach ($this->conf->get('EWicBalance') as $line) {
                $msg .= $line['qty'] . ' ' . $line['cat']['name'] . '<br />';
            }
            $msg .= '</div>';
            echo PaycardLib::paycardMsgBox('Current Balance', $msg, '[enter] to continue, [clear] to cancel');
        }
        echo '</div>';
    }
}

AutoLoader::dispatch();

