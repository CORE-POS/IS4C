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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\PrintHandlers\PrintHandler;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardEmvCaAdmin extends NoInputCorePage 
{
    private $menu = array(
        'KC' => 'Key Change',
        'KR' => 'Key Report',
        'SR' => 'Stats Report',
        'DR' => 'Decline Report',
        'PR' => 'Parameter Report',
        'PD' => 'Parameter Download',
    );
    private $map = array(
        'KC' => 'keyChange',
        'KR' => 'keyReport',
        'SR' => 'statsReport',
        'DR' => 'declineReport',
        'PR' => 'paramReport',
        'PD' => 'paramDownload',
    );

    private $xml = false;
    private $output = 'receipt';
    
    function preprocess()
    {
        $this->conf = new PaycardConf();
        $caAdmin = new DatacapCaAdmin();
        if (FormLib::get("selectlist", false) !== false) {
            /** generate XML based on menu choice **/
            switch (FormLib::get('selectlist')) {
                case 'KC':
                case 'PD':
                    $this->output = 'display';
                    // intentional fallthrough
                case 'KR':
                case 'SR':
                case 'DR':
                case 'PR':
                    $method = $this->map[FormLib::get('selectlist')];
                    $this->xml = $caAdmin->$method();
                    break;
                case 'CL':
                default:
                    $this->change_page('PaycardEmvMenu.php');
                    return false;
            }
        } elseif (FormLib::get('xml-resp')) {
            /** parse response XML and display a dialog box
                or print a receipt **/
            $xml = FormLib::get('xml-resp');
            $output = FormLib::get('output-method');
            $resp = $caAdmin->parseResponse($xml);
            if ($output == 'display' || $resp['receipt'] === false) {
                $this->conf->set('boxMsg', '<strong>' . $resp['status'] . '</strong><br />' . $resp['msg-text']);
                $this->conf->set('strRemembered', '');
                $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
                return false;
            }
            $PRINTOBJ = PrintHandler::factory($this->conf->get('ReceiptDriver'));
            $receiptBody = implode("\n", $resp['receipt']);
            $receiptBody .= "\n\n\n\n\n\n\n";
            $receiptBody .= chr(27).chr(105);
            if (session_id() != '') {
                session_write_close();
            }
            $PRINTOBJ->writeLine($receiptBody);
            $this->change_page($this->page_url."gui-modules/pos2.php");

            return false;
        }

        return true;
    }
    
    function head_content()
    {
        if ($this->xml === false) {
            echo '<script type="text/javascript" src="../../../js/selectSubmit.js"></script>';
            $this->addOnloadCommand("selectSubmit('#selectlist', '#selectform')\n");
            $this->addOnloadCommand("\$('#selectlist').focus();\n");
        } else {
            ?>
<script type="text/javascript">
function emvSubmit() {
    $('div.baseHeight').html('Processing transaction');
    // POST XML request to driver using AJAX
    var xmlData = '<?php echo json_encode($this->xml); ?>';
    var output_method = '<?php echo $this->output; ?>';
    if (xmlData == '"Error"') { // failed to save request info in database
        location = '<?php echo MiscLib::baseURL(); ?>gui-modules/boxMsg2.php';
        return false;
    }
    $.ajax({
        url: 'http://localhost:8999',
        type: 'POST',
        data: xmlData,
        dataType: 'text'
    }).done(function(resp) {
        // POST result to PHP page in POS to
        // process the result.
        $('div.baseHeight').html('Finishing transaction');
        var f = $('<form id="js-form"></form>');
        f.append($('<input type="hidden" name="xml-resp" />').val(resp));
        f.append($('<input type="hidden" name="output-method" />').val(output_method);
        $('body').append(f);
        $('#js-form').submit();
    }).fail(function(resp) {
        // display error to user?
        // go to dedicated error page?
        $('div.baseHeight').html('Finishing transaction');
        var f = $('<form id="js-form"></form>');
        f.append($('<input type="hidden" name="xml-resp" />').val(resp));
        f.append($('<input type="hidden" name="output-method" />').val(output_method);
        $('body').append(f);
        $('#js-form').submit();
    });
}
</script>
            <?php
            $this->addOnloadCommand('emvSubmit();');
        }
    } // END head() FUNCTION

    function body_content() 
    {
        $stem = MiscLib::baseURL() . 'graphics/';
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger">process admin transaction</span>
        <form name="selectform" method="post" id="selectform"
            action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <?php if ($this->conf->get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollDown('#selectlist');">
            <img src="<?php echo $stem; ?>down.png" width="16" height="16" />
        </button>
        <?php } ?>
        <select id="selectlist" name="selectlist" size="5" style="width: 10em;"
            onblur="$('#selectlist').focus()">
        <?php
        $first = true;
        foreach ($this->menu as $val => $label) {
            printf('<option %s value="%s">%s</option>',
                ($first ? 'selected' : ''), $val, $label);
            $first = false;
        }
        ?>
        </select>
        <?php if ($this->conf->get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollUp('#selectlist');">
            <img src="<?php echo $stem; ?>up.png" width="16" height="16" />
        </button>
        <?php } ?>
        <p>
            <button class="pos-button" type="submit">Select [enter]</button>
            <button class="pos-button" type="submit" onclick="$('#selectlist').val('');">
                Cancel [clear]
            </button>
        </p>
        </div>
        </form>
        </div>
        <?php
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

