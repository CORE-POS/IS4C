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

class PaycardEmvGift extends PaycardProcessPage 
{
    private $prompt = false;
    private $run_transaction = false;
    private $amount = false;
    private $mode = false;

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        if (isset($_REQUEST['mode'])) {
            $this->mode = $_REQUEST['mode'];
            if ($this->mode != PaycardLib::PAYCARD_MODE_ACTIVATE && $this->mode != PaycardLib::PAYCARD_MODE_ADDVALUE) {
                CoreLocal::set('boxMsg', 'Invalid Gift Card Mode');
                $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');

                return false;
            }
        }
        if (isset($_REQUEST['amount']) && is_numeric($_REQUEST['amount'])) {
            $this->amount = $_REQUEST['amount'];
        }

        if (isset($_REQUEST['reginput'])) {
            $input = strtoupper(trim($_REQUEST['reginput']));
            // CL always exits
            if( $input == "CL") {
                CoreLocal::set("msgrepeat",0);
                CoreLocal::set("toggletax",0);
                CoreLocal::set("togglefoodstamp",0);
                PaycardLib::paycard_reset();
                CoreLocal::set("CachePanEncBlock","");
                CoreLocal::set("CachePinEncBlock","");
                CoreLocal::set("CacheCardType","");
                CoreLocal::set("CacheCardCashBack",0);
                CoreLocal::set('ccTermState','swipe');
                UdpComm::udpSend("termReset");
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            } elseif ($this->amount && ($input == "" || $input == 'MANUAL')) {
                $this->action = "onsubmit=\"return false;\"";    
                $this->add_onload_command("emvSubmit();");
                if ($input == 'MANUAL') {
                    $this->prompt = true;
                }
                $this->run_transaction = true;
            } elseif ($input != "" && is_numeric($input)) {
                // any other input is an alternate amount
                $this->amount = $input / 100.00;
            }
            // if we're still here, we haven't accepted a valid amount yet; display prompt again
        } elseif (isset($_REQUEST['xml-resp'])) {
            $xml = $_REQUEST['xml-resp'];
            $this->emvResponseHandler($xml);
            return false;
        } // post?

        return true;
    }

    function head_content()
    {
        if (!$this->run_transaction) {
            return '';
        }
        $e2e = new MercuryE2E();
        ?>
<script type="text/javascript">
function emvSubmit()
{
    $('div.baseHeight').html('Processing transaction');
    // POST XML request to driver using AJAX
    var xmlData = '<?php echo json_encode($e2e->prepareDataCapGift($this->mode, $this->amount, $this->prompt)); ?>';
    if (xmlData == '"Error"') { // failed to save request info in database
        location = '<?php echo MiscLib::baseURL(); ?>gui-modules/boxMsg2.php';
        return false;
    }
    $.ajax({
        url: 'http://localhost:8999',
        type: 'POST',
        data: xmlData,
        dataType: 'text',
        success: function(resp) {
            // POST result to PHP page in POS to
            // process the result.
            console.log('success');
            console.log(resp);
            var f = $('<form id="js-form"></form>');
            f.append($('<input type="hidden" name="xml-resp" />').val(resp));
            $('body').append(f);
            $('#js-form').submit();
        },
        error: function(resp) {
            // display error to user?
            // go to dedicated error page?
            console.log('error');
            console.log(resp);
            var f = $('<form id="js-form"></form>');
            f.append($('<input type="hidden" name="xml-resp" />').val(resp));
            $('body').append(f);
            $('#js-form').submit();
        }
    });
}
</script>
        <?php
    }

    function body_content()
    {
        ?>
        <div class="baseHeight">
        <?php
        $title = ($this->mode == PaycardLib::PAYCARD_MODE_ACTIVATE) ? 'Activate Gift Card' : 'Add Value to Gift Card';
        $msg = '';
        if (!$this->amount) {
            $msg .= 'Enter amount<br />
                [clear] to cancel';
        } else {
            $msg .= 'Value: $' . sprintf('%.2f', $this->amount) . '
                    [enter] to continue if correct<br>Enter a different amount if incorrect<br>
                    [clear] to cancel';
        }
        // generate message to print
        echo PaycardLib::paycard_msgBox(
                PaycardLib::PAYCARD_TYPE_GIFT,
                $title,
                '',
                $msg
        );
        ?>
        </div>
        <?php
        $this->add_onload_command("\$('#formlocal').append(\$('<input type=\"hidden\" name=\"mode\" />').val({$this->mode}));\n");
        if ($this->amount) {
            $this->add_onload_command("\$('#formlocal').append(\$('<input type=\"hidden\" name=\"amount\" />').val({$this->amount}));\n");
        }
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
    new PaycardEmvGift();
