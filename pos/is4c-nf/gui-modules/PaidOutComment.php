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
use COREPOS\pos\lib\TransRecord;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class PaidOutComment extends NoInputCorePage 
{

    function preprocess()
    {
        try {
            $input = $this->form->selectlist;
            $qstr = '';
            if ($input == "Other"){
                return true;
            } elseif ($input != 'CL') {
                $input = str_replace("'","",$input);
                $qstr = '?reginput=' . $input . '&repeat=1';
                TransRecord::addcomment("PO: ".$input);
            }
            $this->change_page($this->page_url."gui-modules/pos2.php" . $qstr);
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
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored">
        <span class="larger"><?php echo 'reason for paidout'; ?></span>
        <form name="selectform" method="post" 
            id="selectform" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <?php
        if ($this->form->tryGet('selectlist') == 'Other') {
        ?>
            <input type="text" id="selectlist" name="selectlist" 
                onblur="$('#selectlist').focus();" />
        <?php
        } else {
        ?>
            <select name="selectlist" id="selectlist"
                onblur="$('#selectlist').focus();">
            <option><?php echo _('Paid to Supplier'); ?></option>
            <option><?php echo _('Store Use'); ?></option>
            <option><?php echo _('Coupon'); ?></option>
            <option><?php echo _('Other'); ?></option>
            <option><?php echo _('Discount'); ?></option>
            <option><?php echo _('Gift Card Refund'); ?></option>
            </select>
        <?php
        }
        ?>
        </form>
        <p>
        <span class="smaller"><?php echo _('[clear] to cancel'); ?></span>
        </p>
        </div>
        </div>    
        <?php
        $this->addOnloadCommand("\$('#selectlist').focus();\n");
        $this->addOnloadCommand("selectSubmit('#selectlist', '#selectform')\n");
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        $this->form = new COREPOS\common\mvc\ValueContainer();
        $debug = $this->session->Debug_Redirects;
        $this->session->Debug_Redirects = 1;
        ob_start();
        $this->form->selectlist = 'Other';
        $phpunit->assertEquals(true, $this->preprocess());
        $this->form->selectlist = 'Test';
        $phpunit->assertEquals(false, $this->preprocess());
        ob_end_clean();
        $this->session->Debug_Redirects = $debug;
    }
}

AutoLoader::dispatch();

