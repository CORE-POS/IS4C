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
use COREPOS\pos\lib\TransRecord;
include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class AccessConfirmPage extends NoInputCorePage 
{
    private $tendertype = '';

    function preprocess()
    {
        try {
            $choice = $this->form->selectlist;
            if ($choice == "RECUR") {
                $pluginInfo = new Paycards();
                $this->change_page($pluginInfo->pluginUrl().'/gui/PaycardEmvRecurring.php');
                return false;
            } elseif ($choice == "MATCH") {
                TransRecord::addcomment('MATCHING FUNDS');
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;
            } elseif ($choice == '') {
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;
            }
        } catch (Exception $ex) {
        }

        return true;
    }
    
    function head_content()
    {
        ?>
        <script type="text/javascript" src="../../js/selectSubmit.js"></script>
        <?php
    }

    function body_content() 
    {
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger"><?php _('Payment options'); ?></span>
        <form id="selectform" method="post" action="<?php echo AutoLoader::ownURL(); ?>">

        <?php $stem = MiscLib::baseURL() . 'graphics/'; ?>
        <?php if ($this->session->get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollDown('#selectlist');">
            <img src="<?php echo $stem; ?>down.png" width="16" height="16" />
        </button>
        <?php } ?>
        <select size="2" name="selectlist" 
            id="selectlist" onblur="$('#selectlist').focus();">
        <option value='RECUR' selected><?php echo _('Recurring Payment'); ?>
        <option value='MATCH'><?php echo _('Matching Funds'); ?>
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
        </div>
        </form>
        </div>
        <?php
        $this->add_onload_command("\$('#selectlist').focus();\n");
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

