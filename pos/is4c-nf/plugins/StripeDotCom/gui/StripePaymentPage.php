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

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class StripePaymentPage extends InputPage 
{
    private $payment_id;
    private $payment_url;
    private $payment_amount;

    private function initPayment($amount)
    {
        $apikey = CoreLocal::get('training') ? CoreLocal::get('StripeTestKey') : CoreLocal::get('StripeLiveKey');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/bitcoin/receivers');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT,10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        $postdata = 
              'amount=' . floor($amount*100) 
            . '&currency=' . CoreLocal::get('StripeCurrency')
            . '&email=test@example.com';
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $apikey,
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
        if (!is_array($json)) {
            return false;
        } else {
            return $json;
        }
    }

    private function finalizePayment($id, $amount)
    {
        $apikey = CoreLocal::get('training') ? CoreLocal::get('StripeTestKey') : CoreLocal::get('StripeLiveKey');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/charges');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT,10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        $postdata = 
              'amount=' . floor($amount*100) 
            . '&currency=' . CoreLocal::get('StripeCurrency')
            . '&source=' . $id;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $apikey,
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
        if (!is_array($json) || !isset($json['paid']) || !$json['paid']) {
            return false;
        } else {
            return $json;
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
            $this->payment_id = $payment['id'];
            $this->payment_url = $payment['bitcoin_uri'];
            $this->payment_amount = $_REQUEST['amount'];

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
            $finalize = $this->finalizePayment($_REQUEST['finish'], $_REQUEST['finishamount']);
            if ($finalize === false) {
                CoreLocal::set('boxMsg', 'Error finalizing Bitcoin payment');
                $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
                return false;
            } else {
                TransRecord::addtender('BITCOIN', CoreLocal::get('StripeBitCoinTender'), -1*$_REQUEST['finishamount']);
                CoreLocal::set('strRemembered', 'TO');
                CoreLocal::set('msgrepeat', 1);
                $this->change_page(MiscLib::baseURL()."gui-modules/pos2.php");
                return false;
            }
        }

        return true;
    }

    function head_content()
    {
        ?>
<script type="text/javascript" src="../js/stripe.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    Stripe.setPublishableKey('<?php echo CoreLocal::get('training') ? CoreLocal::get('StripeTestPublic') : CoreLocal::get('StripeLivePublic'); ?>');
    Stripe.bitcoinReceiver.pollReceiver('<?php echo $this->payment_id; ?>', bitcoinReceiverCallback);
});

function bitcoinReceiverCallback()
{
    location = 'StripePaymentPage.php?finish=<?php echo $this->payment_id; ?>&finishamount=<?php echo $this->payment_amount; ?>';
}
</script>
        <?php
    }

    function body_content()
    {
        ?>
        <div class="baseHeight">
        <span class="larger">Scan to complete payment</span><br />
        <img src="StripeQrCode.php?data=<?php echo base64_encode($this->payment_url); ?>" 
            alt="Scan to make payment" />
        <br />
        <span clas="smaller">[clear] to cancel</span>
        </div>
        <?php
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
    new StripePaymentPage();
