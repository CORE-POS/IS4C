<?php
/*******************************************************************************

    Copyright 2021 Whole Foods Co-op

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
use COREPOS\pos\lib\PrintHandlers\PrintHandler;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\TransRecord;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class RefundTransaction extends NoInputCorePage 
{
    function preprocess()
    {
        if ($this->form->tryGet('selectlist', false) !== false) {
            if (empty($this->form->selectlist)) {
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;
            } elseif (is_numeric($this->form->selectlist)) {
                $tID = $this->form->selectlist;
                $rfIDs = $this->form->tryGet('rfids', '');
                $rfIDs = explode(',', $rfIDs);
                if (in_array($tID, $rfIDs)) {
                    $item = $this->getItem($tID);
                    if (is_array($item)) {
                        $reginput = 'RF' . $item['quantity'] . '*' . $item['upc'];
                        CoreLocal::set('refundUnitOverride', $item['total'] / $item['quantity']); 
                        $this->change_page(
                            $this->page_url
                            . "gui-modules/pos2.php"
                            . '?reginput=' . urlencode($reginput)
                            . '&repeat=1');

                        return false;
                    }
                }
            }
        }

        return true;
    }

    function head_content()
    {
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js?date=20180611"></script>
        <?php
        $this->addOnloadCommand("\$('#selectlist').focus();\n");
    }

    private function getItem($tID)
    {
        $date = $this->form->tryGet('date');
        $txn = $this->form->tryGet('tn');

        $table = 'trans_archive.dlogBig';
        if ($date == date('Y-m-d')) {
            $table = 'dlog';
        }
        $dbc = Database::mDataConnect();
        $prep = $dbc->prepare("SELECT upc, quantity, total
            FROM {$table}
            WHERE tdate BETWEEN ? AND ?
                AND trans_num=?
                AND trans_id=?");
        return $dbc->getRow($prep, array($date, $date . ' 23:59:59', $txn, $tID));
    }

    private function getLines()
    {
        $date = $this->form->tryGet('date');
        $txn = $this->form->tryGet('tn');

        $table = 'trans_archive.dlogBig';
        if ($date == date('Y-m-d')) {
            $table = 'dlog';
        }
        $dbc = Database::mDataConnect();
        if ($dbc === false) {
            return array();
        }
        $prep = $dbc->prepare("SELECT description, quantity, total, trans_id, trans_type, trans_status
            FROM {$table}
            WHERE ((trans_type='I' AND voided=0 AND trans_status <> 'V') OR trans_type <> 'I')
                AND tdate BETWEEN ? AND ?
                AND trans_num=?");
        $res = $dbc->execute($prep, array($date, $date . ' 23:59:59', $txn));
        $ret = array();
        $refundable = array();
        while ($row = $dbc->fetchRow($res)) {
            $line = str_pad($row['description'], 35, ' ');
            if ($row['trans_status'] == 'R') {
                $line = '(RF) ' . $line;
            }
            if ($row['trans_type'] == 'I') {
                $line .= str_pad($row['quantity'] . 'x', 8, ' ');
                $line .= $row['total'];
                if ($row['trans_status'] != 'R') {
                    $refundable[] = $row['trans_id'];
                }
            } else {
                $line .= str_repeat(' ', 8) . $row['total'];
            }
            $line = str_replace(' ', '&nbsp;', $line);
            $ret[$row['trans_id']] = $line;
        }

        return array($ret, $refundable);
    }

    
    function body_content()
    {
        list($lines, $refundables) = $this->getLines();
        $rfIDs = implode(',', $refundables);
        $date = $this->form->tryGet('date');
        $txn = $this->form->tryGet('tn');
        ?>
        <div class="baseHeight">
        <div class="listbox">
        <form name="selectform" method="post" id="selectform" 
            action="<?php echo AutoLoader::ownURL(); ?>" >
        <select name="selectlist" size="15" id="selectlist" style="font-family: monospace;"
            onblur="$('#selectlist').focus()" onchange="updatePreview(this.value);" >

        <?php
        $selected = 'selected';
        foreach ($lines as $id => $val) {
            printf('<option %s value="%d">%s</option>', $selected, $id, $val);
            $selected = '';
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
            <?php echo _('Refund Item'); ?> <span class="smaller"><?php echo _('[enter]'); ?></span>
            </button>
        </p>
        <p>
            <button type="submit" class="pos-button wide-button errorColoredArea"
            onclick="$('#selectlist').append($('<option>').val(''));$('#selectlist').val('');">
            <?php echo _('Cancel'); ?> <span class="smaller"><?php echo _('[clear]'); ?></span>
        </button></p>
        </div>
        <input type="hidden" name="rfids" value="<?php echo $rfIDs; ?>" />
        <input type="hidden" name="date" value="<?php echo $date; ?>" />
        <input type="hidden" name="tn" value="<?php echo $txn; ?>" />
        </form>
        <div class="clear"></div>
        </div>

        <?php
    } // END body_content() FUNCTION

}

AutoLoader::dispatch();

