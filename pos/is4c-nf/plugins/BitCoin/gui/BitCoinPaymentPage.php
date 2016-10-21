<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op
    Modifications copyright 2010 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

use COREPOS\pos\lib\gui\InputCorePage;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class BitCoinPaymentPage extends InputCorePage 
{
    private $payment_id;
    private $payment_url;

    private function initPayment($amount)
    {
        $url = '/api/v1/payment/btc';
        if (CoreLocal::get('training')) {
            $domain = 'https://private-anon-772bbea0d-bitcoinpaycom.apiary-mock.com';
        } else {
            $domain = 'https://www.bitcoinpay.com';
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $domain . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT,10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, sprintf('
            {
                "settled_currency", "%s",
                "currency", "%s",
                "price", %.2f,
                "reference": "%s.%d-%d-%d.%d"
            }
        ', CoreLocal::get('BitCoinCurrency'), CoreLocal::get('BitCoinCurrency'),
            $amount,date('Y-m-d'), CoreLocal::get('Cashier'), CoreLocal::get('laneno'),
            CoreLocal::get('trans_no'), (CoreLocal::get('LastID')+1)
        ));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-type: application/json',
            'Authorization: Token ' . CoreLocal::get('BitCoinApiKey'),
        ));

        $response = curl_exec($ch);
        if ($response === false) {
            return false;
        }

        $errNo = curl_errno($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($errNo != 0 || $status != 200) {
            return false;
        }

        $json = json_decode($response, true);
        if (!is_array($json) || !isset($json['data'])) {
            return false;
        } else {
            return $json['data'];
        }
    }

    function preprocess()
    {
        // initialize the payment on bitcoinpay.com
        // when page first loads
        if (isset($_REQUEST['amount'])) {
            $payment = $this->initPayment($_REQUEST['amount']);
            if ($payment === false) {
                CoreLocal::set('boxMsg', 'Error initializing Bitcoin payment');
                $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
                return false;
            }
            $this->payment_id = $payment['payment_id'];
            $this->payment_url = $payment['payment_url'];

            return true;
        }

        // Check for clear button to cancel request
        if (isset($_REQUEST['reginput'])) {
            $input = strtoupper(trim($_REQUEST['reginput']));
            // CL always exits
            if ($input == "CL") {
                $this->change_page(MiscLib::baseURL()."gui-modules/pos2.php");
                return false;
            }
        } 

        // Check for payment complete notification
        // add tender and return to main screen
        if (isset($_REQUEST['finish'])) {
            TransRecord::addtender('BITCOIN', CoreLocal::get('BitCoinTender'), -1*$_REQUEST['finish']);
            $this->change_page(MiscLib::baseURL()."gui-modules/pos2.php?reginput=TO&repeat=1");
            return false;
        }

        return true;
    }

    function head_content()
    {
        if (CoreLocal::get('training')) {
            $domain = 'https://private-anon-772bbea0d-bitcoinpaycom.apiary-mock.com';
        } else {
            $domain = 'https://www.bitcoinpay.com';
        }
        ?>
<script type="text/javascript">
function monitorPaymentStatus(payment_id)
{
    var api_key = '<?php echo CoreLocal::get('BitCoinApiKey'); ?>';
    var domain = '<?php echo $domain; ?>';
    $.ajax({
        url: domain + '/api/v1/payment/btc/' + payment_id,
        headers: { 'Authorization': api_key },
        type: 'GET',
        dataType: 'json'
    }).done(function(resp) {
        if (resp.data && resp.data.status && resp.data.status == 'confirmed') {
            location = 'BitCoinPaymentPage.php?finish=' + resp.data.settled_amount;
        } else {
            setTimeout(function(){ monitorPaymentStatus(payment_id); }, 1000);
        }
    }).fail(function() {
        setTimeout(function(){ monitorPaymentStatus(payment_id); }, 1000);
    });
}
</script>
        <?php
    }

    function body_content()
    {
        ?>
        <div class="baseHeight">
        <span class="larger">Scan to complete payment</span><br />
        <img src="../../StripeDotCom/gui/StripeQrCode.php?data=<?php echo base64_encode($this->payment_url); ?>" 
            alt="Scan to make payment" />
        <br />
        <span clas="smaller">[clear] to cancel</span>
        </div>
        <?php
        $this->add_onload_command("setTimeout(function(){ monitorPaymentStatus('{$this->payment_id}'); }, 2000);\n");
    }
}

AutoLoader::dispatch();

