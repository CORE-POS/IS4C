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

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\ReceiptLib;

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
    protected $conf;

    public function __construct($session, $form)
    {
        $this->conf = new PaycardConf();
        parent::__construct($session, $form);
    }

    public function getHeader()
    {
        ob_start();
        $myUrl = $this->page_url;
        ?>
        <!DOCTYPE html>
        <html>
        <?php
        echo "<head>";
        echo "<title>COREPOS</title>";
        // 18Aug12 EL Add content/charset.
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
        echo "<link rel=\"stylesheet\" type=\"text/css\"
            href=\"{$myUrl}css/pos.css\">";
        $jquery = MiscLib::win32() ? 'jquery-1.8.3.min.js' : 'jquery.js';
        echo "<script type=\"text/javascript\"
            src=\"{$myUrl}js/{$jquery}\"></script>";
        $this->paycardJscriptFunctions();
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
    protected function paycardJscriptFunctions()
    {
        $pluginInfo = new Paycards();
        ?>
        <script type="text/javascript">
        function paycard_submitWrapper(){
            $.ajax({url: '<?php echo $pluginInfo->pluginUrl(); ?>/ajax/AjaxPaycardAuth.php',
                cache: false,
                type: 'post',
                dataType: 'json'
            }).done(function(data) {
                var destination = data.main_frame;
                if (data.receipt){
                    $.ajax({url: '<?php echo $this->page_url; ?>ajax-callbacks/AjaxEnd.php',
                        cache: false,
                        type: 'post',
                        data: 'receiptType='+data.receipt+'&ref=<?php echo ReceiptLib::receiptNumber(); ?>'
                    }).always(function() {
                        window.location = destination;
                    });
                } else {
                    window.location = destination;
                }
            }).fail(function(){
                window.location = '<?php echo $this->page_url; ?>gui-modules/pos2.php';
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
        $e2e = new MercuryDC();
        $json = array();
        $pluginInfo = new Paycards();
        $json['main_frame'] = $pluginInfo->pluginUrl().'/gui/PaycardEmvSuccess.php';
        $json['receipt'] = false;
        $func = $balance ? 'handleResponseDataCapBalance' : 'handleResponseDataCap';
        $success = $e2e->$func($xml);
        $this->conf->set("msgrepeat",0);
        if ($success === PaycardLib::PAYCARD_ERR_OK) {
            $json = $e2e->cleanup($json);
            $this->conf->set("strEntered","");
            $this->conf->set("strRemembered","");
            if ($json['receipt']) {
                $json['main_frame'] .= '?receipt=' . $json['receipt'];
            }
        } else {
            $json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';
        }
        $this->change_page($json['main_frame']);
    }
}

