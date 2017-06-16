<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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
use COREPOS\pos\lib\DeptLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\parser\parse\DeptKey;

include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class B2BListPage extends NoInputCorePage 
{
    private $boxSize = 10;

    function preprocess()
    {
        $entered = "";
        try {
            $entered = strtoupper(trim($this->form->search));
        } catch (Exception $ex) {
            return true;
        }

        // add comment w/ account number & open ring
        // flag open ring with invoice ID to mark it as paid later
        if (!empty($entered)) {
            $json = json_decode(base64_decode($this->form->search), true);
            TransRecord::addcomment($json['coding']);
            $dept = new DeptLib($this->session);
            $this->session->set('msgrepeat', 1); // this to bypass department amount limits
            $ret = $dept->deptkey(100 * $json['amount'], 7030);
            $dbc = Database::tDataConnect();
            $res = $dbc->query('SELECT MAX(trans_id) FROM localtemptrans WHERE trans_type=\'D\' AND department=703');
            $row = $dbc->fetchRow($res);
            $upP = $dbc->prepare("UPDATE localtemptrans SET charflag='B2', numflag=? WHERE trans_id=?");
            $dbc->execute($upP, array($json['id'], $row[0]));
        }

        $this->change_page($this->page_url."gui-modules/pos2.php");
        return false;
    } // END preprocess() FUNCTION

    function head_content()
    {
        // Javascript is only really needed if there are results
        ?>
        <script type="text/javascript" src="../../js/selectSubmit.js"></script>
        <?php
    } // END head() FUNCTION

    function body_content()
    {
        $this->addOnloadCommand("selectSubmit('#search', '#selectform', '#filter-span')\n");

        // originally 390
        $maxSelectWidth = $this->session->get('touchscreen') ? 470 : 530;
        echo "<div class=\"baseHeight\">"
            ."<div class=\"listbox\">"
            ."<form name=\"selectform\" method=\"post\" action=\""
            . filter_input(INPUT_SERVER, 'PHP_SELF') . "\""
            ." id=\"selectform\">"
            ."<select name=\"search\" id=\"search\" "
            .' style="min-height: 200px; min-width: 220px;'
            ." max-width: {$maxSelectWidth}px;\""
            ."size=".$this->boxSize." onblur=\"\$('#search').focus();\" "
            ."ondblclick=\"document.forms['selectform'].submit();\">";

        $dbc = Database::mDataConnect();
        $mAlt = Database::mAltName();
        $prep = $dbc->prepare("SELECT * FROM {$mAlt}B2BInvoices WHERE cardNo=? AND isPaid=0 ORDER BY createdDate DESC");
        $res = $dbc->execute($prep, array(CoreLocal::get('memberID')));
        $selected = "selected";
        while ($row = $dbc->fetchRow($res)) {
            $amount = MiscLib::truncate2($row['amount']);
            $for = $row['description'];
            $coding = $row['coding'];
            $b2bID = $row['b2bInvoiceID'];
            $date = date('Y-m-d', strtotime($row['createdDate']));
            $value = base64_encode(json_encode(array('amount'=>$amount, 'id'=>$b2bID, 'coding'=>$coding)));

            printf('<option %s value="%s">#%s %s $%.2f %s</option>',
                $selected, $value, $b2bID, $date, $amount, $for);
            $selected = "";
        }
        echo "</select>"
            . '<div id="filter-span"></div>'
            ."</div>";
        if ($this->session->get('touchscreen')) {
            echo '<div class="listbox listboxText">'
                . DisplayLib::touchScreenScrollButtons()
                . '</div>';
        }
        echo "<div class=\"listboxText coloredText centerOffset\">"
            . _("use arrow keys") . '<br />' . _("to navigate") . '<br />' . _("the list")
            . '<p><button type="submit" class="pos-button wide-button coloredArea">'
            . _('OK') . ' <span class="smaller">' . _('[enter]') . '</span>
                </button></p>'
            . '<p><button type="submit" class="pos-button wide-button errorColoredArea"
                onclick="$(\'#search\').append($(\'<option>\').val(\'\'));$(\'#search\').val(\'\');">'
            . _('Cancel') . ' <span class="smaller">' . _('[clear]') . '</span>
                </button></p>'
            ."</div><!-- /.listboxText coloredText .centerOffset -->"
            ."</form>"
            ."<div class=\"clear\"></div>";
        echo "</div>";

        $this->addOnloadCommand("\$('#search').focus();\n");
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

