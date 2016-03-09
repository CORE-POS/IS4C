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

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

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

    private $xml = false;
    private $output = 'receipt';
    
    function preprocess()
    {
        if (isset($_REQUEST["selectlist"])) {
            /** generate XML based on menu choice **/
            switch ($_REQUEST['selectlist']) {
                case 'KC':
                    $this->xml = DatacapCaAdmin::keyChange();
                    $this->output = 'display';
                    break;
                case 'PD':
                    $this->xml = DatacapCaAdmin::paramDownload();
                    $this->output = 'display';
                    break;
                case 'KR':
                    $this->xml = DatacapCaAdmin::keyReport();
                    break;
                case 'SR':
                    $this->xml = DatacapCaAdmin::statsReport();
                    break;
                case 'DR':
                    $this->xml = DatacapCaAdmin::declineReport();
                    break;
                case 'PR':
                    $this->xml = DatacapCaAdmin::paramReport();
                    break;
                case 'CL':
                default:
                    $this->change_page('PaycardEmvMenu.php');
                    return false;
            }
        } elseif (isset($_REQUEST['xml-resp'])) {
            /** parse response XML and display a dialog box
                or print a receipt **/
            $xml = $_REQUEST['xml-resp'];
            $output = $_REQUEST['output-method'];
            $resp = DatacapCaAdmin::parseResponse($xml);
            if ($output == 'display' || $resp['receipt'] === false) {
                CoreLocal::set('boxMsg', '<strong>' . $resp['status'] . '</strong><br />' . $resp['msg-text']);
                CoreLocal::set('strRemembered', '');
                $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
                return false;
            } else {
                $print_class = CoreLocal::get('ReceiptDriver');
                if ($print_class === '' || !class_exists($print_class)) {
                    $print_class = 'ESCPOSPrintHandler';
                }
                $PRINT_OBJ = new $print_class();
                $receipt_body = implode("\n", $resp['receipt']);
                $receipt_body .= "\n\n\n\n\n\n\n";
                $receipt_body .= chr(27).chr(105);
                if (session_id() != '') {
                    session_write_close();
                }
                $PRINT_OBJ->writeLine($receipt_body);
                $this->change_page($this->page_url."gui-modules/pos2.php");

                return false;
            }
        }

        return true;
    }
    
    function head_content()
    {
        if ($this->xml === false) {
            echo '<script type="text/javascript" src="../../../js/selectSubmit.js"></script>';
            $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
            $this->add_onload_command("\$('#selectlist').focus();\n");
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
            action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <?php if (CoreLocal::get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollDown('#selectlist');">
            <img src="<?php echo $stem; ?>down.png" width="16" height="16" />
        </button>
        <?php } ?>
        <select id="selectlist" name="selectlist" size="5" style="width: 10em;"
            onblur="$('#selectlist').focus()">
        <?php
        $i = 0;
        foreach ($this->menu as $val => $label) {
            printf('<option %s value="%s">%s</option>',
                ($i == 0 ? 'selected' : ''), $val, $label);
            $i++;
        }
        ?>
        </select>
        <?php if (CoreLocal::get('touchscreen')) { ?>
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

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    new PaycardEmvCaAdmin();
}

