<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\gui\NoInputCorePage;
include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class VpPage extends NoInputCorePage 
{
    function preprocess()
    {
        $choice = FormLib::get('selectlist');
        if ($choice !== '') {

            switch ($choice) {
                case 'CASH':
                    $amt = -1*FormLib::get('amount');
                    TransRecord::addFlaggedTender('PATRONAGE VOUCHER', 'VV', $amt, 0, 'VV');
                    $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php?reginput=TO&repeat=1');
                    return false;
                case 'DONATE':
                    TransRecord::addFlaggedTender('PATRONAGE DONATION', 'VV', 0, 0, 'DN');
                    break;
                case 'CHECK':
                    TransRecord::addFlaggedTender('PATRONAGE CHECK REQUEST', 'VV', 0, 0, 'CK');
                    break;
                case 'CL':
                default:
                    break;
            }
            $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php');

            return false;
        }

        return true;
    }
    
    function head_content(){
        ?>
        <script type="text/javascript" src="../../js/selectSubmit.js"></script>
        <?php
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
        $this->add_onload_command("\$('#selectlist').focus();\n");
    } // END head() FUNCTION

    function body_content() 
    {
        $stem = MiscLib::baseURL() . 'graphics/';
        $dbc = Database::mDataConnect();
        $prep = $dbc->prepare("
            SELECT cardNo, amount
            FROM VirtualVouchers
            WHERE redeemed=0
                AND expired=0
                AND cardNo=?
                AND amount > 0
        ");
        $info = $dbc->getRow($prep, array(CoreLocal::get('memberID')));
        $msg = sprintf('Redeem $%.2f voucher as', $info['amount']);
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger"><?php echo $msg; ?></span>
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
            <option value="CASH">Tender</option>
            <option value="DONATE">Donation</option>
            <option value="CHECK">Check Request</option>
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
        <input type="hidden" name="amount" value="<?php echo $info['amount']; ?>" />
        </form>
        </div>
        <?php
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

