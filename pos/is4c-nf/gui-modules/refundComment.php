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
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class RefundComment extends NoInputCorePage 
{
    function preprocess()
    {
        try {
            $input = $this->form->selectlist;
            $qstr = '';
            if ($input == "CL" || $input == ''){
                $this->session->set("refundComment","");
                $this->session->set("refund",0);
            } elseif ($input == "Other"){
                return True;
            } else {
                $input = str_replace("'","",$input);
                $output = $this->session->get("refundComment");
                $qstr = '?reginput=' . urlencode($output) . '&repeat=1';

                // add comment calls additem(), which wipes
                // out refundComment; save it
                TransRecord::addcomment("RF: ".$input);
                $this->session->set("refundComment", $output);
                $this->session->set("refund",1);
            }
            $this->change_page($this->page_url."gui-modules/pos2.php" . $qstr);
            return false;
        } catch (Exception $ex) {}

        return true;
    }
    
    function head_content(){
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <?php
    } // END head() FUNCTION

    function body_content() 
    {
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger"><?php echo _('reason for refund'); ?></span>
        <form name="selectform" method="post" 
            id="selectform" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <?php
        if ($this->form->tryGet('selectlist') == 'Other') {
        ?>
            <input type="text" id="selectlist" name="selectlist" 
                onblur="$('#selectlist').focus();" />
        <?php
        } else {
            $stem = MiscLib::baseURL() . 'graphics/';
        ?>
            <?php if ($this->session->get('touchscreen')) { ?>
            <button type="button" class="pos-button coloredArea"
                onclick="scrollDown('#selectlist');">
                <img src="<?php echo $stem; ?>down.png" width="16" height="16" />
            </button>
            <?php } ?>
            <select name="selectlist" id="selectlist"
                onblur="$('#selectlist').focus();">
            <option><?php echo _('Overcharge'); ?></option>
            <option><?php echo _('Spoiled'); ?></option>
            <option><?php echo _('Did not Need'); ?></option>
            <option><?php echo _('Did not Like'); ?></option>
            <option><?php echo _('Other'); ?></option>
            </select>
            <?php if ($this->session->get('touchscreen')) { ?>
            <button type="button" class="pos-button coloredArea"
                onclick="scrollUp('#selectlist');">
                <img src="<?php echo $stem; ?>up.png" width="16" height="16" />
            </button>
            <?php } ?>
        <?php
            $this->addOnloadCommand("selectSubmit('#selectlist', '#selectform')\n");
        }
        ?>
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
        $this->addOnloadCommand("\$('#selectlist').focus();\n");
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

