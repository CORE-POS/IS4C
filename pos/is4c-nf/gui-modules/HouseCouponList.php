<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class HouseCouponList extends NoInputCorePage 
{
    function preprocess()
    {
        try {
            $qstr = '';
            if ($this->form->selectlist != '') {
                $qstr .= '?reginput=' . urlencode($this->form->selectlist) . '&repeat=1';
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
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
        $this->add_onload_command("\$('#selectlist').focus();\n");
    }
    
    function body_content()
    {
        $prefix = $this->session->get('houseCouponPrefix');
        if ($prefix == '') {
            $prefix = '00499999';
        }

        $dbc = Database::pDataConnect();
        $query = "SELECT h.coupID, h.description
                FROM houseCoupons AS h
                WHERE h.description <> ''
                    AND h.description NOT LIKE '%*'
                    AND h.startDate <= " . $dbc->now() . "
                    AND " . $dbc->datediff('endDate', $dbc->now()) . " >= 0
                ORDER BY h.description";
    
        $result = $dbc->query($query);
        $numRows = $dbc->numRows($result);
        ?>

        <div class="baseHeight">
        <div class="listbox">
        <form name="selectform" method="post" id="selectform" 
            action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>" >
        <select name="selectlist" size="15" id="selectlist"
            style="min-width: 200px;"
            onblur="$('#selectlist').focus()" >

        <?php
        $selected = "selected";
        for ($i = 0; $i < $numRows; $i++) {
            $row = $dbc->fetchRow($result);
            printf('<option value="%s" %s>%d. %s</option>',
                    ($prefix . str_pad($row['coupID'], 5, '0', STR_PAD_LEFT)),
                    $selected, ($i+1), $row['description']
            );
            $selected = "";
        }
        ?>

        </select>
        </div>
        <?php
        if ($this->session->get('touchscreen')) {
            echo '<div class="listbox listboxText">'
                . DisplayLib::touchScreenScrollButtons('#selectlist')
                . '</div>';
        }
        ?>
        <div class="listboxText coloredText centerOffset">
        <?php echo _("use arrow keys to navigate"); ?><br />
        <p>
            <button type="submit" class="pos-button wide-button coloredArea">
            <?php echo _('Reprint'); ?> <span class="smaller"><?php echo _('[enter]'); ?></span>
            </button>
        </p>
        <p>
            <button type="submit" class="pos-button wide-button errorColoredArea"
            onclick="$('#selectlist').append($('<option>').val(''));$('#selectlist').val('');">
            <?php echo _('Cancel'); ?> <span class="smaller"><?php echo _('[clear]'); ?></span>
        </button></p>
        </div>
        </form>
        <div class="clear"></div>
        </div>

        <?php
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        $debug = $this->session->Debug_Redirects;
        $this->session->Debug_Redirects = 1;
        $this->form = new COREPOS\common\mvc\ValueContainer();
        $this->form->selectlist = '9999';
        ob_start();
        $phpunit->assertEquals(false, $this->preprocess());
        ob_end_clean();
        $this->session->Debug_Redirects = $debug;
    }
}

AutoLoader::dispatch();

