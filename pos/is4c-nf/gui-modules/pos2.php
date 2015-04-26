<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

session_cache_limiter('nocache');

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class pos2 extends BasicPage {

    var $display;

    function preprocess()
    {
        $this->display = "";

        $sd = MiscLib::scaleObject();
        //$st = MiscLib::sigTermObject();

        $entered = "";
        if (isset($_REQUEST["reginput"])) {
            $entered = strtoupper(trim($_REQUEST["reginput"]));
        }

        if (substr($entered, -2) == "CL") $entered = "CL";

        if ($entered == "RI") $entered = CoreLocal::get("strEntered");

        if (CoreLocal::get("msgrepeat") == 1 && $entered != "CL") {
            $entered = CoreLocal::get("strRemembered");
            CoreLocal::set('strRemembered', '');
        }
        CoreLocal::set("strEntered",$entered);

        $json = array();
        if ($entered != ""){

            if (in_array("Paycards",CoreLocal::get("PluginList"))){
                /* this breaks the model a bit, but I'm putting
                 * putting the CC parser first manually to minimize
                 * code that potentially handles the PAN */
                if(CoreLocal::get("PaycardsCashierFacing")=="1" && substr($entered,0,9) == "PANCACHE:"){
                    /* cashier-facing device behavior; run card immediately */
                    $entered = substr($entered,9);
                    CoreLocal::set("CachePanEncBlock",$entered);
                }

                $pe = new paycardEntered();
                if ($pe->check($entered)){
                    $valid = $pe->parse($entered);
                    $entered = "PAYCARD";
                    CoreLocal::set("strEntered","");
                    $json = $valid;
                }

                CoreLocal::set("quantity",0);
                CoreLocal::set("multiple",0);
            }

            /* FIRST PARSE CHAIN:
             * Objects belong in the first parse chain if they
             * modify the entered string, but do not process it
             * This chain should be used for checking prefixes/suffixes
             * to set up appropriate session variables.
             */
            $parser_lib_path = $this->page_url."parser-class-lib/";
            if (!is_array(CoreLocal::get("preparse_chain")))
                CoreLocal::set("preparse_chain",PreParser::get_preparse_chain());

            foreach (CoreLocal::get("preparse_chain") as $cn){
                if (!class_exists($cn)) continue;
                $p = new $cn();
                if ($p->check($entered))
                    $entered = $p->parse($entered);
                    if (!$entered || $entered == "")
                        break;
            }

            if ($entered != "" && $entered != "PAYCARD"){
                /* 
                 * SECOND PARSE CHAIN
                 * these parser objects should process any input
                 * completely. The return value of parse() determines
                 * whether to call lastpage() [list the items on screen]
                 */
                if (!is_array(CoreLocal::get("parse_chain")))
                    CoreLocal::set("parse_chain",Parser::get_parse_chain());

                $result = False;
                foreach (CoreLocal::get("parse_chain") as $cn){
                    if (!class_exists($cn)) continue;
                    $p = new $cn();
                    if ($p->check($entered)){
                        $result = $p->parse($entered);
                        break;
                    }
                }
                if ($result && is_array($result)) {

                    // postparse chain: modify result
                    if (!is_array(CoreLocal::get("postparse_chain"))) {
                        CoreLocal::set("postparse_chain",PostParser::getPostParseChain());
                    }
                    foreach (CoreLocal::get('postparse_chain') as $class) {
                        if (!class_exists($class)) {
                            continue;
                        }
                        $obj = new $class();
                        $result = $obj->parse($result);
                    }

                    $json = $result;
                    if (isset($result['udpmsg']) && $result['udpmsg'] !== False){
                        if (is_object($sd))
                            $sd->WriteToScale($result['udpmsg']);
                        /*
                        if (is_object($st))
                            $st->WriteToScale($result['udpmsg']);
                        */
                    }
                }
                else {
                    $arr = array(
                        'main_frame'=>false,
                        'target'=>'.baseHeight',
                        'output'=>DisplayLib::inputUnknown());
                    $json = $arr;
                    if (is_object($sd)){
                        $sd->WriteToScale('errorBeep');
                    }
                }
            }
        }
        CoreLocal::set("msgrepeat",0);
        if (isset($json['main_frame']) && $json['main_frame'] != False){
            $this->change_page($json['main_frame']);
            return False;
        }
        if (isset($json['output']) && !empty($json['output']))
            $this->display = $json['output'];

        if (isset($json['retry']) && $json['retry'] != False){
            $this->add_onload_command("setTimeout(\"inputRetry('".$json['retry']."');\", 150);\n");
        }

        if (isset($json['receipt']) && $json['receipt'] != False){
            $ref = isset($json['trans_num']) ? $json['trans_num'] : ReceiptLib::mostRecentReceipt();
            $this->add_onload_command("receiptFetch('" . $json['receipt'] . "', '" . $ref . "');\n");
        }

        if (CoreLocal::get('CustomerDisplay') === true) {
            $child_url = MiscLib::baseURL() . 'gui-modules/posCustDisplay.php';
            $this->add_onload_command("setCustomerURL('{$child_url}');\n");
            $this->add_onload_command("reloadCustomerDisplay();\n");
        }

        return true;
    }

    function head_content()
    {
        ?>
        <script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
        <script type="text/javascript" src="<?php echo $this->page_url; ?>js/CustomerDisplay.js"></script>
        <script type="text/javascript">
        function submitWrapper(){
            var str = $('#reginput').val();
            $('#reginput').val('');
            clearTimeout(screenLockVar);
            runParser(str,'<?php echo $this->page_url; ?>');
            enableScreenLock();
            return false;
        }
        function parseWrapper(str){
            $('#reginput').val(str);
            $('#formlocal').submit();
        }
        var screenLockVar;
        function enableScreenLock(){
            screenLockVar = setTimeout('lockScreen()', <?php printf('%d', CoreLocal::get("timeout")); ?>);
        }
        function lockScreen(){
            location = '<?php echo $this->page_url; ?>gui-modules/login3.php';
        }
        function receiptFetch(r_type, ref){
            $.ajax({
                url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-end.php',
                type: 'get',
                data: 'receiptType='+r_type+'&ref='+ref,
                dataType: 'json',
                cache: false,
                error: function() {
                    var icon = $('#receipticon').attr('src');
                    var newicon = icon.replace(/(.*graphics)\/.*/, "$1/deadreceipt.gif");
                    $('#receipticon').attr('src', newicon);
                },
                success: function(data){
                    if (data.sync){
                        ajaxTransactionSync('<?php echo $this->page_url; ?>');
                    }
                    if (data.error) {
                        var icon = $('#receipticon').attr('src');
                        var newicon = icon.replace(/(.*graphics)\/.*/, "$1/deadreceipt.gif");
                        $('#receipticon').attr('src', newicon);
                    }
                },
                error: function(e1){
                }
            });
        }
        function inputRetry(str){
            parseWrapper(str);
        }
        /**
          Replace instances of 'SCAL' with the scale's weight. The command
          is triggered by the E keypress but that letter is never actually
          added to the input.
        */
        function getScaleWeight()
        {
            var current_input = $('#reginput').val().toUpperCase();
            if (current_input.indexOf('SCAL') != -1) {
                var wgt = $.trim($('#scaleBottom').html());
                wgt = parseFloat(wgt);
                if (isNaN(wgt) || wgt == 0.00) {
                    // weight not available
                    return true;
                } else {
                    var new_input = current_input.replace('SCAL', wgt);
                    $('#reginput').val(new_input);
                    
                    return false;
                }
            }

            return true;
        }
        </script>
        <?php
    }

    function body_content()
    {
        $lines = CoreLocal::get('screenLines');
        if (!$lines === '' || !is_numeric($lines)) {
            $lines = 11;
        }
        $this->input_header('action="pos2.php" onsubmit="return submitWrapper();"');
        if (CoreLocal::get("timeout") != "")
            $this->add_onload_command("enableScreenLock();\n");
        $this->add_onload_command("\$('#reginput').keydown(function(ev){
                    switch(ev.which){
                    case 33:
                        parseWrapper('U$lines');
                        break;
                    case 38:
                        parseWrapper('U');
                        break;
                    case 34:
                        parseWrapper('D$lines');
                        break;
                    case 40:
                        parseWrapper('D');
                        break;
                    case 9:
                        parseWrapper('TFS');
                        return false;
                    case 69:
                    case 101:
                        return getScaleWeight();
                    }
                });\n");
        /*
        if (CoreLocal::get("msgrepeat") == 1)
            $this->add_onload_command("submitWrapper();");
        */
        ?>
        <div class="baseHeight">
        <?php

        CoreLocal::set("quantity",0);
        CoreLocal::set("multiple",0);
        CoreLocal::set("casediscount",0);

        // set memberID if not set already
        if (!CoreLocal::get("memberID")) {
            CoreLocal::set("memberID","0");
        }

        if (CoreLocal::get("plainmsg") && strlen(CoreLocal::get("plainmsg")) > 0) {
            echo DisplayLib::printheaderb();
            echo "<div class=\"centerOffset\">";
            echo DisplayLib::plainmsg(CoreLocal::get("plainmsg"));
            CoreLocal::set("plainmsg",0);
            echo "</div>";
        }
        elseif (!empty($this->display))
            echo $this->display;
        else
            echo DisplayLib::lastpage();

        echo "</div>"; // end base height

        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";

        if (CoreLocal::get("touchscreen")) {
            echo '<div style="text-align: center;">
            <button type="submit" 
                class="quick_button pos-button coloredBorder"
                style="margin: 0 10px 0 0;"
                onclick="parseWrapper(\'QO1001\');">
                Items
            </button>
            <button type="submit"
                class="quick_button pos-button coloredBorder"
                style="margin: 0 10px 0 0;"
                onclick="parseWrapper(\'QO1002\');">
                Total
            </button>
            <button type="submit" 
                class="quick_button pos-button coloredBorder"
                style="margin: 0 10px 0 0;"
                onclick="parseWrapper(\'QO1003\');">
                Member
            </button>
            <button type="submit" 
                class="quick_button pos-button coloredBorder"
                style="margin: 0 10px 0 0;"
                onclick="parseWrapper(\'QO1004\');">
                Tender
            </button>
            <button type="submit"
                class="quick_button pos-button coloredBorder"
                style="margin: 0 10px 0 0;"
                onclick="parseWrapper(\'QO1005\');">
                Misc
            </button>
            </div>';
        }
    } // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
    new pos2();

?>
