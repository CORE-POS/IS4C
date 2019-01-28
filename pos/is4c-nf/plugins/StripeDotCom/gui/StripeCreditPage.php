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

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\TransRecord;

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class StripeCreditPage extends NoInputCorePage 
{
    /**
     * If the charge was successful, we need to print a signature slip
     * to faciliate that the page is drawn again but visually blank.
     * In the background an ajax request prints the signature slip before
     * redirecting the browser back to the main screen
     */
    private $done = false;

    private function getCurlHandle($url, $postdata)
    {
        $apikey = CoreLocal::get('training') ? CoreLocal::get('StripeTestKey') : CoreLocal::get('StripeLiveKey');
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_handle, CURLOPT_TIMEOUT,10);
        curl_setopt($curl_handle, CURLOPT_HEADER, false);
        curl_setopt($curl_handle, CURLOPT_POST, true);
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $apikey,
        ));

        return $curl_handle;
    }

    private function createCharge($amount, $token)
    {
        $postdata = 
              'amount=' . floor($amount*100) 
            . '&currency=' . CoreLocal::get('StripeCurrency')
            . '&description=' . urlencode(CoreLocal::get('StripeChargeName'))
            . '&source=' . $token;
        $curl_handle = $this->getCurlHandle('https://api.stripe.com/v1/charges', $postdata);

        return $this->curlExec($curl_handle);
    }

    private function curlExec($curl_handle)
    {
        $response = curl_exec($curl_handle);
        $errNo = curl_errno($curl_handle);
        $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        $time = curl_getinfo($curl_handle, CURLINFO_TOTAL_TIME);

        if ($response === false) {
            return false;
        }
        if ($errNo != 0 || $status != 200) {
            return false;
        }
        $json = json_decode($response, true);
        $json['http'] = $status;
        $json['seconds'] = $time;

        return is_array($json) ? $json : false;
    }

    function preprocess()
    {
        if (FormLib::get('token', false)) {
            $token = json_decode(FormLib::get('token'), true);
            $amt = FormLib::get('amount');
            $reqDT = date('Y-m-d H:i:s');
            $result = $this->createCharge($amt, $token['id']);
            $respDT = date('Y-m-d H:i:s');

            $issuer = $token['card']['brand'];
            $pan = str_repeat('*', 12) . $token['card']['last4'];
            $transType = $amt > 0 ? 'Sale' : 'Refund';
            $live = CoreLocal::get('training') ? 0 : 1;
            $responseCode = 0;
            $resultCode = 0;
            $resultMsg = 'Error';
            $appr = '';
            $seconds = is_array($result) ? $result['seconds'] : 0;
            $http = is_array($result) ? $result['http'] : 0;
            if ($result['paid']) {
                $responseCode = 1;
                $resultCode = 1;
                $resultMsg = 'Approved';
                $appr = $result['id'];
            } elseif (is_array($result)) {
                $resultMsg = substr($result['failure_message'], 0, 100);
                $responseCode = $result['failure_code'];
            }
            $dbc = Database::tDataConnect();
            $insP = $dbc->prepare("INSERT INTO PaycardTransactions (
                    dateID, empNo, registerNo, transNo, transID, processor, refNum,
                    live, cardType, transType, amount, PAN, issuer, name, manual,
                    requestDatetime, responseDatetime, seconds, commErr, httpCode,
                    validResponse, xResultCode, xApprovalNumber, xResponseCode, xResultMessage
                ) VALUES (
                    ?, ?, ?, ?, ?, 'Stripe', ?,
                    ?, 'Credit', ?, ?, ?, ?, 'Cardholder', 0,
                    ?, ?, ?, ?, ?,
                    1, ?, ?, ?, ?
                )");
            $args = array(
                date('Ymd'), CoreLocal::get('CashierNo'), CoreLocal::get('laneno'),
                CoreLocal::get('transno'), CoreLocal::get('LastID')+1, $appr,
                $live, $transType, $amt, $pan, $issuer,
                $reqDT, $respDT, $seconds, (is_array($result) ? 0 : 1), $http,
                $resultCode, substr($appr, 0, 20), $responseCode, $resultMsg,
            );
            $dbc->execute($insP, $args);
            $ptID = $dbc->insertID();
            if ($result['paid']) {
                TransRecord::addFlaggedTender('Credit Card', 'CC', -1*$amt, $ptID, 'PT');
                $this->done = true;
                $this->addOnloadCommand("sigAndHome();");
                return true;
            } 

            CoreLocal::set('boxMsg', _('Payment failed') . '<br />' . $resultMsg);
            $this->change_page(MiscLib::baseURL()."gui-modules/boxMsg2.php");
            return false;
        }

        return true;
    }

    function head_content()
    {
        ?>
<style type="text/css">
/**
 ** The CSS shown here will not be introduced in the Quickstart guide, but shows
 ** how you can use CSS to style your Element's container.
 **/
.StripeElement {
  background-color: white;
  height: 40px;
  padding: 10px 12px;
  border-radius: 4px;
  border: 1px solid transparent;
  box-shadow: 0 1px 3px 0 #e6ebf1;
  -webkit-transition: box-shadow 150ms ease;
  transition: box-shadow 150ms ease;
}

.StripeElement--focus {
  box-shadow: 0 1px 3px 0 #cfd7df;
}

.StripeElement--invalid {
  border-color: #fa755a;
}

.StripeElement--webkit-autofill {
  background-color: #fefde5 !important;
}
</style>
<script src="https://js.stripe.com/v3/"></script>
<script type="text/javascript">
function initStripe() {
    // Create a Stripe client.
    var stripe = Stripe('pk_test_6pRNASCoBOKtIshFeQd4XMUh');

    // Create an instance of Elements.
    var elements = stripe.elements();

    // Custom styling can be passed to options when creating an Element.
    // (Note that this demo uses a wider set of styles than the guide below.)
    var style = {
        base: {
            color: '#32325d',
            lineHeight: '18px',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };

    // Create an instance of the card Element.
    var card = elements.create('card', {style: style});

    // Add an instance of the card Element into the `card-element` <div>.
    card.mount('#card-element');

    // Handle real-time validation errors from the card Element.
    card.addEventListener('change', function(event) {
        var displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Handle form submission.
    var form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        stripe.createToken(card).then(function(result) {
            if (result.error) {
                // Inform the user if there was an error.
                var errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
            } else {
                // Send the token to your server.
                stripeTokenHandler(result.token);
            }
        });
    });

    $(document).keyup(function (ev) {
        console.log(ev.which);
    });

    card.on('ready', function() {
        card.focus();
    });
}
function stripeTokenHandler(token) {
    $('#token-field').val(JSON.stringify(token));
    $('#token-form').submit();
}
function sigAndHome() {
    $.ajax({url: '../../../ajax/AjaxEnd.php',
        cache: false,
        type: 'post',
        data: 'receiptType='+$('#rp_type').val()+'&ref=<?php echo ReceiptLib::receiptNumber(); ?>'
    }).always(function(data) {
        window.location = '../../../gui-modules/pos2.php?reginput=TO&repeat=1';
    });
}
</script>
        <?php
    }

    function body_content()
    {
        if ($this->done) {
            echo '<div class="baseHeight">Finishing payment</div>';
            return;
        }
        $amt = FormLib::get('amount');
        echo <<<HTML
<div class="baseHeight">
    <form action="StripeCreditPage.php" method="post" id="payment-form">
        <div class="form-row centeredDisplay">
            <label for="card-element" class="coloredArea rounded" style="margin: 5px; padding: 5px;">Enter credit or debit card</label>
            <div id="card-element">
            <!-- A Stripe Element will be inserted here. -->
            </div>
            <!-- Used to display form errors. -->
            <div id="card-errors" role="alert"></div>
        </div>
        <!--<button>Submit Payment</button>-->
    </form>
    <form action="StripeCreditPage.php" method="post" id="token-form">
        <input type="hidden" name="token" id="token-field" value="" />
        <input type="hidden" name="amount" value="{$amt}" />
    </form>
</div>
HTML;
        $this->addOnloadCommand("initStripe();");
    }
}

AutoLoader::dispatch();

