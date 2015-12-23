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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class suspendedlist extends NoInputCorePage 
{
    private $temp_result;
    private $temp_num_rows;
    private $temp_db;

    function head_content()
    {
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <?php
    } // END head() FUNCTION

    function preprocess()
    {
        /* form submitted */
        if (isset($_REQUEST['selectlist'])){
            if (!empty($_REQUEST['selectlist'])){ // selected a transaction
                $tmp = explode("::",$_REQUEST['selectlist']);
                $this->doResume($tmp[0],$tmp[1],$tmp[2]);
                // if it is a member transaction, verify correct name
                if (CoreLocal::get('memberID') != '0' && CoreLocal::get('memberID') != CoreLocal::get('defaultNonMem')) {
                    $this->change_page($this->page_url.'gui-modules/memlist.php?idSearch='.CoreLocal::get('memberID'));
                } else {
                    $this->change_page($this->page_url."gui-modules/pos2.php");
                }
            } else { // pressed clear
                $this->change_page($this->page_url."gui-modules/pos2.php");
            }

            return false;
        }

        $query_local = "SELECT register_no, emp_no, trans_no, SUM(total) AS total "
            ." FROM suspended "
            ." WHERE datetime >= " . date("'Y-m-d 00:00:00'")
            ." GROUP BY register_no, emp_no, trans_no";

        $dbc_a = Database::tDataConnect();
        $result = "";
        if (CoreLocal::get("standalone") == 1) {
            $result = $dbc_a->query($query_local);
        } else {
            $dbc_a = Database::mDataConnect();
            $result = $dbc_a->query($query_local);
        }

        $num_rows = $result ? $dbc_a->num_rows($result) : 0;
        
        /* if there are suspended transactions available, 
         * store the result and row count as class variables
         * so they can be retrieved in body_content()
         *
         * otherwise notify that there are no suspended
         * transactions
         */
        if ($num_rows > 0){
            $this->temp_result = $result;
            $this->temp_num_rows = $num_rows;
            $this->temp_db = $dbc_a;

            return true;
        } else {
            CoreLocal::set("boxMsg",_("no suspended transaction"));
            $this->change_page($this->page_url."gui-modules/pos2.php");    

            return false;
        }

        return true;
    } // END preprocess() FUNCTION

    function body_content()
    {
        $num_rows = $this->temp_num_rows;
        $result = $this->temp_result;
        $dbc = $this->temp_db;

        echo "<div class=\"baseHeight\">"
            ."<div class=\"listbox\">"
            ."<form id=\"selectform\" method=\"post\" action=\""
            .filter_input(INPUT_SERVER, 'PHP_SELF') . "\">\n"
            ."<select name=\"selectlist\" size=\"15\" onblur=\"\$('#selectlist').focus();\"
                id=\"selectlist\">";

        $selected = "selected";
        for ($i = 0; $i < $num_rows; $i++) {
            $row = $dbc->fetch_array($result);
            echo "<option value='".$row["register_no"]."::".$row["emp_no"]."::".$row["trans_no"]."' ".$selected
                ."> lane ".substr(100 + $row["register_no"], -2)." Cashier ".substr(100 + $row["emp_no"], -2)
                ." #".$row["trans_no"]." -- $".$row["total"]."\n";
            $selected = "";
        }

        echo "</select>\n</div>\n";
        if (CoreLocal::get('touchscreen')) {
            echo '<div class="listbox listboxText">'
                . DisplayLib::touchScreenScrollButtons('#selectlist')
                . '</div>';
        }
        echo "<div class=\"listboxText coloredText centerOffset\">"
            . _("use arrow keys to navigate")
            . '<p><button type="submit" class="pos-button wide-button coloredArea">
                OK <span class="smaller">[enter]</span>
                </button></p>'
            . '<p><button type="submit" class="pos-button wide-button errorColoredArea"
                onclick="$(\'#selectlist\').append($(\'<option>\').val(\'\'));$(\'#selectlist\').val(\'\');">
                Cancel <span class="smaller">[clear]</span>
                </button></p>'
            ."</div><!-- /.listboxText coloredText .centerOffset -->"
            ."</form>"
            ."<div class=\"clear\"></div>";
        echo "</div>";
        $this->add_onload_command("\$('#selectlist').focus();");
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
    } // END body_content() FUNCTION

    private function doResume($reg,$emp,$trans)
    {
        $query_del = "DELETE FROM suspended WHERE register_no = ".$reg." AND emp_no = "
            .$emp." AND trans_no = ".$trans;

        $dbc_a = Database::tDataConnect();
        $success = false;

        // use SQLManager's transfer method when not in stand alone mode
        // to eliminate the cross server query - andy 8/31/07
        if (CoreLocal::get("standalone") == 0){
            $dbc_a->addConnection(CoreLocal::get("mServer"),CoreLocal::get("mDBMS"),
                CoreLocal::get("mDatabase"),CoreLocal::get("mUser"),CoreLocal::get("mPass"));

            $cols = Database::getMatchingColumns($dbc_a, "localtemptrans", "suspended");
            // localtemptrans might not actually be empty; let trans_id
            // populate via autoincrement rather than copying it from
            // the suspended table
            if(substr($cols,-9) == ',trans_id') {
                $cols = substr($cols, 0, strlen($cols)-9);
            }

            $remoteQ = "SELECT {$cols} 
                        FROM suspended 
                        WHERE 
                            register_no = $reg 
                            AND emp_no = $emp
                            AND trans_no = $trans
                            AND datetime >= " . date("'Y-m-d 00:00:00'") . "
                        ORDER BY trans_id";
            $success = $dbc_a->transfer(CoreLocal::get("mDatabase"),$remoteQ,
                CoreLocal::get("tDatabase"),"insert into localtemptrans ({$cols})");
            if ($success) {
                $dbc_a->query($query_del,CoreLocal::get("mDatabase"));
            }
            $dbc_a->close(CoreLocal::get("mDatabase"), true);
        } else {    
            // localtemptrans might not actually be empty; let trans_id
            // populate via autoincrement rather than copying it from
            // the suspended table
            $def = $dbc_a->tableDefinition('localtemptrans');
            $cols = '';
            foreach($def as $name=>$info){
                if ($name == 'trans_id') continue;
                $cols .= $name.',';
            }
            $cols = substr($cols,0,strlen($cols)-1);

            $localQ = "SELECT {$cols} 
                        FROM suspended 
                        WHERE 
                            register_no = $reg 
                            AND emp_no = $emp
                            AND trans_no = $trans
                            AND datetime >= " . date("'Y-m-d 00:00:00'") . "
                        ORDER BY trans_id";
            $success = $dbc_a->query("insert into localtemptrans ({$cols}) ".$localQ);
            if ($success) {
                $dbc_a->query($query_del);
            }
        }

        $query_update = "update localtemptrans set register_no = ".CoreLocal::get("laneno").", emp_no = ".CoreLocal::get("CashierNo")
            .", trans_no = ".CoreLocal::get("transno");

        $dbc_a->query($query_update);

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
}

AutoLoader::dispatch();

