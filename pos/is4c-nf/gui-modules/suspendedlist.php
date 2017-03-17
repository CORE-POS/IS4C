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
use COREPOS\pos\lib\TransRecord;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class suspendedlist extends NoInputCorePage 
{
    private $tempResult = array();

    function head_content()
    {
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <?php
    } // END head() FUNCTION

    function preprocess()
    {
        /* form submitted */
        if ($this->form->tryGet('selectlist', false) !== false) {
            if ($this->form->selectlist !== '') { // selected a transaction
                $tmp = explode("::",$this->form->selectlist);
                $this->doResume($tmp[0],$tmp[1],$tmp[2]);
                // if it is a member transaction, verify correct name
                $url = $this->page_url."gui-modules/pos2.php";
                if ($this->session->get('memberID') != '0' && $this->session->get('memberID') != $this->session->get('defaultNonMem')) {
                    $url = $this->page_url.'gui-modules/memlist.php?idSearch='.$this->session->get('memberID');
                }
                $this->change_page($url);
                return false;
            }
            // pressed clear
            $this->change_page($this->page_url."gui-modules/pos2.php");

            return false;
        }


        $this->tempResult = $this->getTransactions();
        
        /* if there are suspended transactions available, 
         * store the result and row count as class variables
         * so they can be retrieved in body_content()
         *
         * otherwise notify that there are no suspended
         * transactions
         */
        if (count($this->tempResult) > 0) {
            return true;
        }
        $this->session->set("boxMsg",_("no suspended transaction"));
        $this->change_page($this->page_url."gui-modules/boxMsg2.php");

        return false;
    } // END preprocess() FUNCTION

    private function getTransactions()
    {
        $queryLocal = "SELECT register_no, emp_no, trans_no, SUM(total) AS total "
            ." FROM suspended "
            ." WHERE datetime >= " . date("'Y-m-d 00:00:00'")
            ." GROUP BY register_no, emp_no, trans_no";

        $dbc = Database::tDataConnect();
        $result = "";
        if ($this->session->get("standalone") == 1) {
            $result = $dbc->query($queryLocal);
        } else {
            $dbc = Database::mDataConnect();
            $result = $dbc->query($queryLocal);
        }

        $ret = array();
        while ($row = $dbc->fetchRow($result)) {
            $ret[] = $row;
        }

        return $ret;
    }

    function body_content()
    {
        echo "<div class=\"baseHeight\">"
            ."<div class=\"listbox\">"
            ."<form id=\"selectform\" method=\"post\" action=\""
            .filter_input(INPUT_SERVER, 'PHP_SELF') . "\">\n"
            ."<select name=\"selectlist\" size=\"15\" onblur=\"\$('#selectlist').focus();\"
                id=\"selectlist\">";

        $selected = "selected";
        foreach ($this->tempResult as $row) {
            echo "<option value='".$row["register_no"]."::".$row["emp_no"]."::".$row["trans_no"]."' ".$selected
                ."> lane ".substr(100 + $row["register_no"], -2)." Cashier ".substr(100 + $row["emp_no"], -2)
                ." #".$row["trans_no"]." -- $".$row["total"]."\n";
            $selected = "";
        }

        echo "</select>\n</div>\n";
        if ($this->session->get('touchscreen')) {
            echo '<div class="listbox listboxText">'
                . DisplayLib::touchScreenScrollButtons('#selectlist')
                . '</div>';
        }
        echo "<div class=\"listboxText coloredText centerOffset\">"
            . _("use arrow keys to navigate")
            . '<p><button type="submit" class="pos-button wide-button coloredArea">'
            . _('OK') . ' <span class="smaller">' . _('[enter]') . '</span>
                </button></p>'
            . '<p><button type="submit" class="pos-button wide-button errorColoredArea"
                onclick="$(\'#selectlist\').append($(\'<option>\').val(\'\'));$(\'#selectlist\').val(\'\');">'
            . _('Cancel') . ' <span class="smaller">' . _('[clear]') . '</span>
                </button></p>'
            ."</div><!-- /.listboxText coloredText .centerOffset -->"
            ."</form>"
            ."<div class=\"clear\"></div>";
        echo "</div>";
        $this->add_onload_command("\$('#selectlist').focus();");
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
    } // END body_content() FUNCTION

    private function safeCols($arr)
    {
        $cols = '';
        foreach ($arr as $name) {
            if ($name == 'trans_id') continue;
            $cols .= $name.',';
        }

        return substr($cols,0,strlen($cols)-1);
    }

    private function suspendedQuery($cols, $emp, $reg, $trans)
    {
        return sprintf("SELECT {$cols} 
                    FROM suspended 
                    WHERE 
                        register_no = %d
                        AND emp_no = %d
                        AND trans_no = %d
                        AND datetime >= " . date("'Y-m-d 00:00:00'") . "
                    ORDER BY trans_id",
                    $reg, $emp, $trans);
    }

    private function doResume($reg,$emp,$trans)
    {
        $queryDel = "DELETE FROM suspended WHERE register_no = ".$reg." AND emp_no = "
            .$emp." AND trans_no = ".$trans;

        $dbc = Database::tDataConnect();
        $success = false;

        // use SQLManager's transfer method when not in stand alone mode
        // to eliminate the cross server query - andy 8/31/07
        if ($this->session->get("standalone") == 0){
            $dbc->addConnection($this->session->get("mServer"),$this->session->get("mDBMS"),
                $this->session->get("mDatabase"),$this->session->get("mUser"),$this->session->get("mPass"));
            if (CoreLocal::get('CoreCharSet') != '') {
                $dbc->setCharSet(CoreLocal::get('CoreCharSet'), CoreLocal::get('mDatabase'));
            }

            $cols = Database::getMatchingColumns($dbc, "localtemptrans", "suspended");
            // localtemptrans might not actually be empty; let trans_id
            // populate via autoincrement rather than copying it from
            // the suspended table
            $cols = $this->safeCols(explode(',', $cols));

            $remoteQ = $this->suspendedQuery($cols, $emp, $reg, $trans);
            $success = $dbc->transfer($this->session->get("mDatabase"),$remoteQ,
                $this->session->get("tDatabase"),"insert into localtemptrans ({$cols})");
            if ($success) {
                $dbc->query($queryDel,$this->session->get("mDatabase"));
            }
            $dbc->close($this->session->get("mDatabase"), true);
        } else {    
            // localtemptrans might not actually be empty; let trans_id
            // populate via autoincrement rather than copying it from
            // the suspended table
            $def = $dbc->tableDefinition('localtemptrans');
            $cols = $this->safeCols(array_keys($def));

            $localQ = $this->suspendedQuery($cols, $emp, $reg, $trans);
            $success = $dbc->query("insert into localtemptrans ({$cols}) ".$localQ);
            if ($success) {
                $dbc->query($queryDel);
            }
        }

        $queryUpdate = "update localtemptrans set register_no = ".$this->session->get("laneno").", emp_no = ".$this->session->get("CashierNo")
            .", trans_no = ".$this->session->get("transno");

        $dbc->query($queryUpdate);

        /**
          Add a log record after succesfully
          resuming a suspended transaction.
          Log record added after resume so records
          preceeding the log record were part of
          the original transaction and records
          following the log record were part of
          the resumed transaction
        */
        if ($success) {
            TransRecord::addLogRecord(array(
                'upc' => 'RESUME',
                'description' => $emp . '-' . $reg . '-' . $trans,
                'charflag' => 'SR',
            ));
        }

        Database::getsubtotals();
    }

    public function unitTest($phpunit)
    {
        $trans = $this->getTransactions();
        $phpunit->assertInternalType('array', $trans);
        $this->tempResult = $trans;
        ob_start();
        $this->head_content();
        $this->body_content();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
        $cols = $this->safeCols(array('trans_no','trans_id'));
        $phpunit->assertEquals('trans_no', $cols);
        $phpunit->assertNotEquals(0, strlen($this->suspendedQuery($cols, 1, 1, 1)));
    }
}

AutoLoader::dispatch();

