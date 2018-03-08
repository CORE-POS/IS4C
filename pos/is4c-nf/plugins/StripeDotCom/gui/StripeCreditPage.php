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
use COREPOS\pos\lib\FormLib;

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class StripeCreditPage extends NoInputCorePage 
{
    private $payment_id;
    private $payment_url;
    private $payment_amount;

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
        var_dump(htmlentities($postdata));
        $curl_handle = $this->getCurlHandle('https://api.stripe.com/v1/charges', $postdata);

        return $this->curlExec($curl_handle);
    }

    private function curlExec($curl_handle)
    {
        $response = curl_exec($curl_handle);
        $errNo = curl_errno($curl_handle);
        $status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        var_dump($response);
        var_dump($errNo);
        var_dump($status);

        if ($response === false) {
            return false;
        }
        if ($errNo != 0 || $status != 200) {
            return false;
        }
        $json = json_decode($response, true);

        return is_array($json) ? $json : false;
    }

    function preprocess()
    {
        if (FormLib::get('token', false)) {
            $token = json_decode(FormLib::get('token'), true);
            $amt = FormLib::get('amount');
            $result = $this->createCharge($amt, $token['id']);

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
    card.focus();

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
}
function stripeTokenHandler(token) {
    $('#token-field').val(JSON.stringify(token));
    $('#token-form').submit();
}
</script>
        <?php
    }

    function body_content()
    {
        $amt = FormLib::get('amount');
        echo <<<HTML
<div class="baseHeight">
    <form action="StripeCreditPage.php" method="post" id="payment-form">
        <div class="form-row">
            <label for="card-element">Credit or debit card</label>
            <div id="card-element">
            <!-- A Stripe Element will be inserted here. -->
            </div>
            <!-- Used to display form errors. -->
            <div id="card-errors" role="alert"></div>
        </div>
        <button>Submit Payment</button>
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

