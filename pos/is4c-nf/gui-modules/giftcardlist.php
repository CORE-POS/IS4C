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
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class giftcardlist extends NoInputCorePage 
{

    function preprocess()
    {
        try {
            $this->session->set("prefix",$this->form->selectlist);
            $this->change_page($this->page_url."gui-modules/pos2.php");
            return false;
        } catch (Exception $ex) {}

        return true;
    }
    
    function head_content(){
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <?php
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
        $this->add_onload_command("\$('#selectlist').focus();\n");
    } // END head() FUNCTION

    function body_content() 
    {
        $stem = MiscLib::baseURL() . 'graphics/';
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger"><?php echo _('gift card transaction'); ?></span>
        <form name="selectform" method="post" id="selectform"
            action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <?php if ($this->session->get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollDown('#selectlist');">
            <img src="<?php echo $stem; ?>down.png" width="16" height="16" />
        </button>
        <?php } ?>
        <select id="selectlist" name="selectlist" 
            onblur="$('#selectlist').focus()">
        <option value=""><?php echo _('Sale'); ?>
        <option value="AC"><?php echo _('Activate'); ?>
        <option value="AV"><?php echo _('Add Value'); ?>
        <option value="PV"><?php echo _('Balance'); ?>
        </select>
        <?php if ($this->session->get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollUp('#selectlist');">
            <img src="<?php echo $stem; ?>up.png" width="16" height="16" />
        </button>
        <?php } ?>
        <p>
            <button class="pos-button" type="submit"><?php echo _('Select [enter]'); ?></button>
            <button class="pos-button" type="submit" onclick="$('#selectlist').val('');">
                <?php echo _('Cancel [clear]'); ?>
            </button>
        </p>
        </div>
        </form>
        </div>
        <?php
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

