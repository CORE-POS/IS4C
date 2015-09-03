<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class PaycardTransLookupPage extends BasicCorePage 
{

    function preprocess()
    {
        if (isset($_REQUEST['doLookup'])) {
            $ref = $_REQUEST['id'];
            $local = $_REQUEST['local'];
            $mode = $_REQUEST['mode'];

            $obj = null;
            $resp = array();
            foreach(CoreLocal::get('RegisteredPaycardClasses') as $rpc) {
                $obj = new $rpc();
                if ($obj->myRefNum($ref)) {
                    break;
                } else {
                    $obj = null;
                }
            }

            if ($obj === null) {
                $resp['output'] = DisplayLib::boxMsg('Invalid Transaction ID' . '<br />Local System Error', '', true);
                $resp['confirm_dest'] = MiscLib::base_url() . 'gui-modules/pos2.php';
                $resp['cancel_dest'] = MiscLib::base_url() . 'gui-modules/pos2.php';
            } else if ($local == 0 && $mode == 'verify') {
                $resp['output'] = DisplayLib::boxMsg('Cannot Verify - Already Complete' . '<br />Local System Error', '', true);
                $resp['confirm_dest'] = MiscLib::base_url() . 'gui-modules/pos2.php';
                $resp['cancel_dest'] = MiscLib::base_url() . 'gui-modules/pos2.php';
            } else {
                $resp = $obj->lookupTransaction($ref, $local, $mode);
            }

            echo JsonLib::array_to_json($resp);

            return false;
        }

        return true;
    }

    function body_content()
    {
        $this->input_header('onsubmit="lookupFormCallback();return false;"');
        echo '<div class="baseHeight">';
        $id = $_REQUEST['id'];
        $local = false;
        if (substr($id, 0, 2) == '_l') {
            $local = true;
            $id = substr($id, 2);
        }
        $mode = $_REQUEST['mode'];
        $msg = 'Looking up transaction';
        if ($mode == 'verify') {
            $msg = 'Verifying transaction';
        }
        echo DisplayLib::boxMsg($msg . '<br />Please wait', '', true);
        echo '</div>'; // baseHeight

        printf('<input type="hidden" id="refNum" value="%s" />', $id);
        printf('<input type="hidden" id="local" value="%d" />', ($local) ? 1 : 0);
        printf('<input type="hidden" id="lookupMode" value="%s" />', $mode);

        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>\n";

        $this->add_onload_command('performLookup();');
    }

    function head_content()
    {
        ?>
<script type="text/javascript">
var gettingResult = 1;
var enter_url = '';
var clear_url = '';
function performLookup()
{
    $.ajax({
        type: 'get',  
        data: 'doLookup=1&id='+$('#refNum').val()+'&local='+$('#local').val()+'&mode='+$('#lookupMode').val(),
        dataType: 'json',
        success: function(resp) {
            $('.baseHeight').html(resp.output);
            enter_url = resp.confirm_dest;
            clear_url = resp.cancel_dest;
            gettingResult = 0;
        }
    });
}
function lookupFormCallback()
{
    if (gettingResult == 1) {
        return false;
    }

    var reginput = $('#reginput').val();

    if (reginput == '') {
        location = enter_url;
    } else if (reginput.toUpperCase() == 'CL') {
        location = clear_url;
    } else {
        $('#reginput').val('');
    }
}
</script>
        <?php
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
    new PaycardTransLookupPage();
