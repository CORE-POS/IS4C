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
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class fsTotalConfirm extends NoInputCorePage 
{
    private $tendertype;

    private function subtotal($choice)
    {
        if ($choice == 'EF') {
            $chk = PrehLib::fsEligible();
        } else {
            $chk = PrehLib::ttl();
        }

        if ($chk !== true) {
            $this->change_page($chk);
        } else {
            $this->tendertype = $choice;
            $this->change_page($this->page_url."gui-modules/pos2.php");
        }

        return false;
    }

    function preprocess()
    {
        $this->tendertype = "";
        if (isset($_REQUEST["selectlist"])) {
            $choice = $_REQUEST["selectlist"];
            if ($choice == "EF" || $choice == 'EC') {
                return $this->subtotal($choice);
            } elseif ($choice == '') {
                $this->change_page($this->page_url."gui-modules/pos2.php");

                return false;
            }
        }

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
        $default = 'EF';
        if (CoreLocal::get('fntlDefault') === '' || CoreLocal::get('fntlDefault') == 1) {
            $default = 'EC';
        }
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger">Customer is using the</span>
        <form id="selectform" method="post" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">

        <?php $stem = MiscLib::baseURL() . 'graphics/'; ?>
        <?php if (CoreLocal::get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollDown('#selectlist');">
            <img src="<?php echo $stem; ?>down.png" width="16" height="16" />
        </button>
        <?php } ?>
        <select size="2" name="selectlist" 
            id="selectlist" onblur="$('#selectlist').focus();">
        <option value='EC' <?php echo ($default == 'EC') ? 'selected' : ''; ?>>Cash Portion
        <option value='EF' <?php echo ($default == 'EF') ? 'selected' : ''; ?>>Food Portion
        </select>
        <?php if (CoreLocal::get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollUp('#selectlist');">
            <img src="<?php echo $stem; ?>up.png" width="16" height="16" />
        </button>
        <?php } ?>
        <p>
            <button class="pos-button" type="submit">Select [enter]</button>
            <button class="pos-button" type="submit" 
                onclick="$('#selectlist').append($('<option>').val(''));$('#selectlist').val('');">
                Cancel [clear]
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

