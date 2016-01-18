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

/** @class PaycardProcessPage

    This class automatically includes the header and footer
    and also defines some useful javascript functions

    Normally the submit process looks like this:
     - Cashier presses enter, POST-ing to the page
     - In preprocess(), the included javascript function
       paycard_submitWrapper() is queued using
       BasicCorePage:add_onload_command()
     - The $action property get set to something "safe"
       like onsubmit="return false;" so that repeatedly
       pressing enter won't cause multiple submits 
 */

class PaycardProcessPage extends BasicCorePage 
{
    /**
       The input form action. See BasicCorePage::input_header()
       for format information
    */
    protected $action = '';

    public function getHeader()
    {
        ob_start();
        $my_url = $this->page_url;
        ?>
        <!DOCTYPE html>
        <html>
        <?php
        echo "<head>";
        echo "<title>COREPOS</title>";
        // 18Aug12 EL Add content/charset.
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
        echo "<link rel=\"stylesheet\" type=\"text/css\"
            href=\"{$my_url}css/pos.css\">";
        if (MiscLib::win32()) {
            echo "<script type=\"text/javascript\"
                src=\"{$my_url}js/jquery-1.8.3.min.js\"></script>";
        } else {
            echo "<script type=\"text/javascript\"
                src=\"{$my_url}js/jquery.js\"></script>";
        }
        $this->paycard_jscript_functions();
        $this->head_content();
        echo "</head>";
        echo '<body class="'.$this->body_class.'">';
        echo "<div id=\"boundingBox\">";
        $this->input_header($this->action);

        return ob_get_clean();
    }

    public function getFooter()
    {
        $ret = "<div id=\"footer\">"
            . DisplayLib::printfooter()
            . "</div>\n"
            . "</div>\n";
        ob_start();
        $this->scale_box();
        $this->scanner_scale_polling(false);
        $ret .= ob_get_clean();

        return $ret;
    }

    /**
       Include some paycard submission javascript functions.
       Automatically called during page print.
    */
    protected function paycard_jscript_functions()
    {
        $plugin_info = new Paycards();
        ?>
        <script type="text/javascript">
        function paycard_submitWrapper(){
            $.ajax({url: '<?php echo $plugin_info->pluginUrl(); ?>/ajax/ajax-paycard-auth.php',
                cache: false,
                type: 'post',
                dataType: 'json',
                success: function(data){
                    var destination = data.main_frame;
                    if (data.receipt){
                        $.ajax({url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-end.php',
                            cache: false,
                            type: 'post',
                            data: 'receiptType='+data.receipt+'&ref=<?php echo ReceiptLib::receiptNumber(); ?>',
                            error: function(){
                                location = destination;
                            },
                            success: function(data){
                                location = destination;
                            }
                        });
                    }
                    else
                        location = destination;
                },
                error: function(){
                    location = '<?php echo $this->page_url; ?>/gui-modules/pos2.php';
                }
            });
            paycard_processingDisplay();
            return false;
        }
        function paycard_processingDisplay(){
            var content = $('div.baseHeight').html();
            if (content.length >= 23)
                content = 'Waiting for response.';
            else
                content += '.';
            $('div.baseHeight').html(content);
            setTimeout('paycard_processingDisplay()',1000);
        }
        </script>
        <?php
    }

    protected function emvResponseHandler($xml, $balance=false)
    {
        $e2e = new MercuryE2E();
        $json = array();
        $plugin_info = new Paycards();
        $json['main_frame'] = $plugin_info->pluginUrl().'/gui/PaycardEmvSuccess.php';
        $json['receipt'] = false;
        $func = $balance ? 'handleResponseDataCapBalance' : 'handleResponseDataCap';
        $success = $e2e->$func($xml);
        if ($success === PaycardLib::PAYCARD_ERR_OK) {
            $json = $e2e->cleanup($json);
            CoreLocal::set("strEntered","");
            CoreLocal::set("strRemembered","");
            CoreLocal::set("msgrepeat",0);
            if ($json['receipt']) {
                $json['main_frame'] .= '?receipt=' . $json['receipt'];
            }
        } else {
            CoreLocal::set("msgrepeat",0);
            $json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';
        }
        $this->change_page($json['main_frame']);
    }
}

