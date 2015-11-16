<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class boxMsg2 extends BasicCorePage 
{
    function head_content(){
        ?>
        <script type="text/javascript">
        function submitWrapper(){
            var str = $('#reginput').val();
            var endorseType = $('#endorseType').val();
            var endorseAmt = $('#endorseAmt').val();
            var cmd = $('#repeat-cmd').val();
            $.ajax({
                url: '<?php echo $this->page_url; ?>ajax-callbacks/AjaxDecision.php',
                type: 'get',
                data: 'input='+str,
                dataType: 'json',
                cache: false,
                success: function(data){
                    if (!data.cleared && endorseType != ''){
                        $.ajax({
                            url: '<?php echo $this->page_url; ?>ajax-callbacks/AjaxEndorse.php',
                            type: 'get',
                            data: 'type='+endorseType+'&amount='+endorseAmt,
                            cache: false,
                            success: function(){
                                location = data.dest_page;
                            }
                        });
                    }
                    else {
                        var changeTo = data.dest_page;
                        if (!data.cleared) {
                            changeTo += "?reginput=" + encodeURIComponent(cmd);
                            changeTo += "&repeat=1";
                        }
                        window.location = changeTo;
                    }
                }
            });
            return false;
        }
        </script>
        <?php
        $this->noscan_parsewrapper_js();
    }

    function preprocess()
    {
        /**
          Bounce through this page and back to pos2.php. This lets
          TenderModules use the msgrepeat feature during input parsing.
        */
        if (isset($_REQUEST['autoconfirm'])) {
            $this->change_page(
                MiscLib::base_url()
                .'gui-modules/pos2.php'
                . '?reginput=' .urlencode(CoreLocal::get('strEntered'))
                . '&repeat=1'
            );
            return false;
        }
        return true;
    }

    function body_content()
    {
        $this->input_header("onsubmit=\"return submitWrapper();\"");
        ?>
        <div class="baseHeight">

        <?php
        $buttons = is_array(CoreLocal::get('boxMsgButtons')) ? CoreLocal::get('boxMsgButtons') : array();
        echo DisplayLib::boxMsg(CoreLocal::get("boxMsg"), "", true, $buttons);
        echo "</div>";
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";
        echo '<input type="hidden" id="endorseType" value="'
            .(isset($_REQUEST['endorse'])?$_REQUEST['endorse']:'')
            .'" />';
        echo '<input type="hidden" id="endorseAmt" value="'
            .(isset($_REQUEST['endorseAmt'])?$_REQUEST['endorseAmt']:'')
            .'" />';
        /**
          Encode the last command entered in the page. With payment
          terminals facing the customer, input processing may happen
          in the background and alter the value of strEntered
        */
        echo '<input type="hidden" id="repeat-cmd" value="'
            . CoreLocal::get('strEntered') . '" />';
        
        CoreLocal::set("boxMsg",'');
        CoreLocal::set("boxMsgButtons", array());
        CoreLocal::set("msgrepeat",2);
        if (!isset($_REQUEST['quiet']))
            MiscLib::errorBeep();
    } // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
    new boxMsg2();

?>
