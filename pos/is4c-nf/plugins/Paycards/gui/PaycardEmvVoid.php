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

class PaycardEmvVoid extends PaycardProcessPage 
{
    private $prompt = false;
    private $id = false;

    function preprocess()
    {
        $dbc = Database::tDataConnect();
        $q = 'SELECT MAX(paycadTransactionID) FROM PaycardTransactions';
        $r = $dbc->query($q);
        if ($r && $dbc->numRows($r)) {
            $w = $dc->fetchRow($r);
            $this->id = $w[0];
        }
        if (!$this->id) {
            CoreLocal::set('boxMsg', 'Cannot locate transaction to void');
            $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
            return false;
        }
        CoreLocal::set('paycard_mode', PaycardLib::PAYCARD_MODE_VOID);

        // check for posts before drawing anything, so we can redirect
        if (isset($_REQUEST['reginput'])) {
            $input = strtoupper(trim($_REQUEST['reginput']));
            // CL always exits
            if ($input == "CL") {
                PaycardLib::paycard_reset();
                CoreLocal::set("msgrepeat",1);
                CoreLocal::set("strRemembered",'TO');
                CoreLocal::set("toggletax",0);
                CoreLocal::set("togglefoodstamp",0);
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;
            } elseif (Authenticate::checkASSWORD($input)) {
                $this->action = "onsubmit=\"return false;\"";    
                $this->add_onload_command("emvSubmit();");
            }
            // if we're still here, we haven't accepted a valid amount yet; display prompt again
        } elseif (isset($_REQUEST['xml-resp'])) {
            $xml = $_REQUEST['xml-resp'];
            $e2e = new MercuryE2E();
            $json = array();
            $plugin_info = new Paycards();
            $json['main_frame'] = $plugin_info->plugin_url().'/gui/PaycardEmvSuccess.php';
            $json['receipt'] = false;
            $success = $e2e->handleResponseDataCap($xml);
            if ($success === PaycardLib::PAYCARD_ERR_OK) {
                $json = $e2e->cleanup($json);
                CoreLocal::set("strRemembered","");
                CoreLocal::set("msgrepeat",0);
            } else {
                CoreLocal::set("msgrepeat",0);
                $json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';
            }
            header('Location: ' . $json['main_frame']);
            return false;
        }

        return true;
    }

    function head_content()
    {
        $e2e = new MercuryE2E();
        ?>
<script type="text/javascript">
function emvSubmit()
{
    $('div.baseHeight').html('Processing transaction');
    // POST XML request to driver using AJAX
    var xmlData = '<?php echo json_encode($e2e->prepareDataCapVoid($this->id)); ?>';
    if (xmlData == '"Error"') { // failed to save request info in database
        location = '<?php echo MiscLib::baseURL(); ?>gui-modules/boxMsg2.php';
        return false;
    }
    $.ajax({
        url: 'http://localhost:9000',
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
        // generate message to print
        $amt = CoreLocal::get("paycard_amount");
        if ($amt > 0) {
            echo PaycardLib::paycard_msgBox(
                $type,
                "Void " . PaycardLib::paycard_moneyFormat($amt) . " Payment?",
                "Please enter password then",
                "[enter] to continue voiding or<br>[clear] to cancel the void"
            );
        } else {
            echo PaycardLib::paycard_msgBox(
                $type,"
                Void " . PaycardLib::paycard_moneyFormat($amt) . " Refund?",
                "Please enter password then",
                "[enter] to continue voiding or<br>[clear] to cancel the void"
            );
        }
        ?>
        </div>
        <?php
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
    new PaycardEmvVoid();
