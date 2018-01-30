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

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\PrehLib;
include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class MGScreen extends NoInputCorePage 
{
    private $type = '';
    private $step = 0;

    function preprocess()
    {
        /**
          The "selectlist" field is on the step==0 screen
          to choose a transaction type. Clear from this screen
          backs all the way out. Otherwise it proceeds to the
          step==1 screen
        */
        try {
            $choice = strtoupper($this->form->selectlist);
            if ($choice == 'CL' || $choice == '') {
                $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php');
                return false;
            }
            $this->type = $choice;
            $this->step = 1;
            return true;
        } catch (Exception $ex) {}

        /**
          The "amount" and "type" fields are on the step==1 screen.
          Clear goes back to the step==0 screen. Otherwise the
          necessary open ring is built and passed back to pos2.php
          to be processed.
        */
        try {
            $amount = strtoupper($this->form->amount);
            $type = strtoupper($this->form->type);
            if ($amount == 'CL' || $amount == '') {
                $this->step = 0;
                return true;
            }
            $dept = 0;
            switch ($type) {
                case 'MG':
                    $dept = 851;
                    break;
                case 'BP':
                    $dept = 852;
                    break;
                case 'IN':
                    $dept = 853;
                    break;
                case 'OUT':
                    $dept = 853;
                    $amount = '-' . $amount;
                    break;
            }
            $input = $amount . 'DP' . $dept . '0';
            $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php?input=' . $input . '&repeat=1');
            return false;
        } catch (Exception $ex) {}

        return true;
    }
    
    function head_content()
    {
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <?php
    } // END head() FUNCTION

    function body_content() 
    {
        $msg = $this->step == 0 ? _('Transasction Type') : _('Enter Amount');
        $stem = MiscLib::baseURL() . 'graphics/';
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger"><?php echo $msg; ?></span>
        <form id="selectform" method="post" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">

        <?php if ($this->step == 0) { ?>
            <?php if ($this->session->get('touchscreen')) { ?>
                <button type="button" class="pos-button coloredArea"
                    onclick="scrollDown('#selectlist');">
                    <img src="<?php echo $stem; ?>down.png" width="16" height="16" />
                </button>
            <?php } ?>
            <select size="4" name="selectlist" 
                id="selectlist" onblur="$('#selectlist').focus();">
                <option value="MO" selected>Money Order</option>
                <option value="BP">Bill Pay</option>
                <option value="IN">Send Money</option>
                <option value="OUT">Receive Money</option>
            </select>
            <?php if ($this->session->get('touchscreen')) { ?>
                <button type="button" class="pos-button coloredArea"
                    onclick="scrollUp('#selectlist');">
                    <img src="<?php echo $stem; ?>up.png" width="16" height="16" />
                </button>
            <?php } ?>
            <p>
                <button class="pos-button" type="submit"><?php echo _('Select [enter]'); ?></button>
                <button class="pos-button" type="submit" 
                    onclick="$('#selectlist').append($('<option>').val(''));$('#selectlist').val('');">
                    <?php echo _('Cancel [clear]'); ?>
                </button>
            </p>
        <?php } else { ?>
            <input type="text" id="selectlist" name="amount" tabindex="0" onblur="$('#selectlist').focus()" />
            <input type="hidden" name="type" value="<?php echo $type; ?>" />
        <?php } ?>
        </form>
        </div>
        </div>
        <?php
        $this->add_onload_command("\$('#selectlist').focus();\n");
        if ($this->step == 0) {
            $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
        }
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

