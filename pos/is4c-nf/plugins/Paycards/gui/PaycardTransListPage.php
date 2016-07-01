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

use COREPOS\pos\lib\Authenticate;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\gui\NoInputCorePage;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardTransListPage extends NoInputCorePage 
{

    function preprocess()
    {
        $this->conf = new PaycardConf();
        // check for posts before drawing anything, so we can redirect
        if (FormLib::get('selectlist', false) !== false) {
            $ptid = FormLib::get('selectlist');

            if ($ptid == 'CL' || $ptid == '') {
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;
            }

            $this->change_page('PaycardTransLookupPage.php?id=' . $ptid . '&mode=lookup');
            return false;

        } // post?
        return True;
    }

    function body_content()
    {
        $local = array();
        $other = array();
        $dbc = Database::tDataConnect();
        $localQ = 'SELECT amount, PAN, refNum FROM PaycardTransactions GROUP BY amount, PAN, refNum';
        $localR = $dbc->query($localQ);
        while($row = $dbc->fetchRow($localR)) {
            $local['_l' . $row['refNum']] = '(CURRENT)' . $row['PAN'] . ' : ' . sprintf('%.2f', $row['amount']);
        }
        if ($this->conf->get('standalone') == 0) {

            $emp = $this->conf->get('CashierNo');
            $sec = Authenticate::getPermission($emp);
            $supervisor = $sec >= 30 ? true : false;

            $dbc = Database::mDataConnect();
            $otherQ = 'SELECT MIN(requestDatetime) as dt, amount, PAN, refNum,
                        empNo AS cashierNo, registerNo AS laneNo, transNo
                        FROM PaycardTransactions 
                        WHERE dateID=' . date('Ymd');
            if (!$supervisor) {
                $otherQ .= ' AND registerNo=' . ((int)$this->conf->get('laneno')) . '
                           AND empNo=' . ((int)$this->conf->get('CashierNo'));
            }
            $otherQ .= ' GROUP BY amount, PAN, refNum
                        ORDER BY requestDatetime DESC';
            $otherR = $dbc->query($otherQ);
            while($row = $dbc->fetchRow($otherR)) {
                $other[$row['refNum']] = $row['dt'] . ' : ' 
                                        . $row['cashierNo'] . '-' . $row['laneNo'] . '-' . $row['transNo'] . ' : ' 
                                        . sprintf('%.2f', $row['amount']);
            }
        }
        ?>
        <div class="baseHeight">
        <div class="listbox">
        <form name="selectform" method="post" id="selectform" 
            action="<?php echo $_SERVER['PHP_SELF']; ?>" >
        <select name="selectlist" size="10" id="selectlist"
            onblur="$('#selectlist').focus()" >
        <?php
        $selected = 'selected';
        foreach($local as $id => $label) {
            printf('<option %s value="%s">%s</option>',
                    $selected, $id, $label);
            $selected = '';
        }
        foreach($other as $id => $label) {
            printf('<option %s value="%s">%s</option>',
                    $selected, $id, $label);
            $selected = '';
        }
        if (count($local) == 0 && count($other) == 0) {
            echo '<option value="" selected>No transactions found</option>';
        }
        ?>
        </select>
        </form>
        </div>
        <div class="listboxText coloredText centerOffset">
        <?php echo _("use arrow keys to navigate"); ?><br />
        <?php echo _("enter to reprint receipt"); ?><br />
        <?php echo _("clear to cancel"); ?>
        </div>
        <div class="clear"></div>
        </div>
        <?php
        $this->addOnloadCommand("\$('#selectlist').keypress(processkeypress);\n");
        $this->addOnloadCommand("\$('#selectlist').focus();\n");
    }

    function head_content()
    {
        ?>
        <script type="text/javascript" >
        var prevKey = -1;
        var prevPrevKey = -1;
        function processkeypress(e) {
            var jsKey;
            if (e.keyCode) // IE
                jsKey = e.keyCode;
            else if(e.which) // Netscape/Firefox/Opera
                jsKey = e.which;
            if (jsKey==13) {
                if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
                (prevKey == 108 || prevKey == 76) ){ //CL<enter>
                    $('#selectlist option:selected').val('');
                }
                $('#selectform').submit();
            }
            prevPrevKey = prevKey;
            prevKey = jsKey;
        }
        </script> 
        <?php
    }
}

AutoLoader::dispatch();

