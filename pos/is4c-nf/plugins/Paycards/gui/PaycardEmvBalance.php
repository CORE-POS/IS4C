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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardEmvBalance extends PaycardProcessPage 
{
    private $prompt = false;
    private $runTransaction = false;

    function preprocess()
    {
        // check for posts before drawing anything, so we can redirect
        if (FormLib::get('reginput',false) !== false) {
            $input = strtoupper(trim(FormLib::get('reginput')));
            // CL always exits
            if ($input == "CL") {
                $this->conf->reset();
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;
            } elseif ($input == "" || $input == 'MANUAL') {
                $this->action = "onsubmit=\"return false;\"";    
                $this->addOnloadCommand("emvSubmit();");
                if ($input == 'MANUAL') {
                    $this->prompt = true;
                }
                $this->runTransaction = true;
            }
            // if we're still here, we haven't accepted a valid amount yet; display prompt again
        } elseif (FormLib::get('xml-resp') !== '') {
            $xml = FormLib::get('xml-resp');
            $this->emvResponseHandler($xml, true);
            return false;
        }

        return true;
    }

    function head_content()
    {
        if (!$this->runTransaction) {
            return '';
        }
        $e2e = new MercuryDC();
        ?>
<script type="text/javascript" src="../js/emv.js"></script>
<script type="text/javascript">
function emvSubmit() {
    $('div.baseHeight').html('Processing transaction');
    // POST XML request to driver using AJAX
    var xmlData = '<?php echo json_encode($e2e->prepareDataCapBalance($this->conf->get('CacheCardType'), $this->prompt)); ?>';
    if (xmlData == '"Error"') { // failed to save request info in database
        location = '<?php echo MiscLib::baseURL(); ?>gui-modules/boxMsg2.php';
        return false;
    }
    emv.submit(xmlData);
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
        echo PaycardLib::paycardMsgBox("Check Card Balance?",
            "",
            "[enter] to continue<br>[clear] to cancel");
        ?>
        </div>
        <?php
    }
}

AutoLoader::dispatch();

