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
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class StripePaymentPage extends InputCorePage 
{
    private $payment_id;
    private $payment_url;
    private $payment_amount;

    private function getCurlHandle($url, $postdata)
    {
        $apikey = CoreLocal::get('training') ? CoreLocal::get('StripeTestKey') : CoreLocal::get('StripeLiveKey');
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, 'https://api.stripe.com/v1/bitcoin/receivers');
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT,10);
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        curl_setopt($curl_handle, CURLOPT_POST, true);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $apikey,
        ));
    }

    private function initPayment($amount)
    {
        $postdata = 
              'amount=' . floor($amount*100) 
            . '&currency=' . CoreLocal::get('StripeCurrency')
            . '&email=test@example.com';
        $curl_handle = $this->getCurlHandle('https://api.stripe.com/v1/bitcoin/receivers', $postdata);

        return $this->curlExec($curl_handle);
    }

    private function curlExec($curl_handle)
    {
        $response = curl_exec($curl_handle);
        $errNo = curl_errno($curl_handle);
        $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

        if ($response === false) {
            return false;
        }
        if ($errNo != 0 || $status != 200) {
            return false;
        }
        $json = json_decode($response, true);

        return is_array($json) ? $json : false;
    }

    private function finalizePayment($id, $amount)
    {
        $postdata = 
              'amount=' . floor($amount*100) 
            . '&currency=' . CoreLocal::get('StripeCurrency')
            . '&source=' . $id;
        $curl_handle = $this->getCurlHandle('https://api.stripe.com/v1/charges', $postdata);

        return $this->curlExec($curl_handle);
    }

    function preprocess()
    {
        // initialize the payment on bitcoinpay.com
        // when page first loads
        if (FormLib::get('amount') !== '') {
            $payment = $this->initPayment(FormLib::get('amount'));
            if ($payment === false) {
                CoreLocal::set('boxMsg', 'Error initializing Bitcoin payment');
                $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
                return false;
            }
            $this->payment_id = $payment['id'];
            $this->payment_url = $payment['bitcoin_uri'];
            $this->payment_amount = FormLib::get('amount');

            return true;
        }

        // Check for clear button to cancel request
        if (FormLib::get('reginput') !== '') {
            $input = strtoupper(trim(FormLib::get('reginput')));
            // CL always exits
            if ($input == "CL") {
                $this->change_page(MiscLib::baseURL()."gui-modules/pos2.php");
                return false;
            }
        } 

        // Check for payment complete notification
        // add tender and return to main screen
        if (FormLib::get('finish') !== '') {
            $finalize = $this->finalizePayment(FormLib::get('finish'), FormLib::get('finishamount'));
            if ($finalize === false) {
                CoreLocal::set('boxMsg', 'Error finalizing Bitcoin payment');
                $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
                return false;
            } else {
                TransRecord::addtender('BITCOIN', CoreLocal::get('StripeBitCoinTender'), -1*FormLib::get('finishamount'));
                $this->change_page(MiscLib::baseURL()."gui-modules/pos2.php?reginput=TO&repeat=1");
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

AutoLoader::dispatch();

