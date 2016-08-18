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
include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class WicMenuPage extends NoInputCorePage 
{
    function preprocess()
    {
        if (isset($_REQUEST["selectlist"])) {
            switch ($_REQUEST['selectlist']) {
                case 'WICON':
                    CoreLocal::set('WicMode', true);
                    $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php');
                    return false;
                case 'WICOFF':
                    CoreLocal::set('WicMode', false);
                    $this->change_page(MiscLib::baseURL() . 'gui-modules/pos2.php');
                    return false;
                case 'WICT':
                    if (CoreLocal::get('ttlflag') == 0) {
                        CoreLocal::set('boxMsg', _('transaction must be totaled before tender can be accepted'));
                        $this->change_page(MiscLib::baseURL() . 'gui-modules/boxMsg2.php');
                    } else {
                        $plugin = new WicPlugin();
                        $this->change_page($plugin->pluginURL() . '/WicTenderPage.php');
                    }
                    return false;
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
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger">WIC Mode is <?php echo CoreLocal::get('WicMode') ? 'ON' : 'OFF'; ?></span>
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
        if (CoreLocal::get('WicMode')) {
            echo '<option value="WICOFF" selected>Exit WIC Mode</option>';
        } else {
            echo '<option value="WICON" selected>Enter WIC Mode</option>';
        }
        ?>
            <option value="WICT">Tender WIC</option>
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

AutoLoader::dispatch();

