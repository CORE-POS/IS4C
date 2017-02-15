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
use COREPOS\pos\lib\Authenticate;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class cablist extends NoInputCorePage 
{

    function head_content()
    {
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <script type="text/javascript" src="js/cablist.js"></script>
        <?php
        $this->addOnloadCommand("selectSubmit('#selectlist', '#selectform', false, true)\n");
        $this->addOnloadCommand("\$('#selectlist').focus();\n");
    }

    private function getTransactions()
    {
        $fes = Authenticate::getPermission($this->session->get('CashierNo'));
        /* if front end security >= 25, pull all
         * available receipts; other wise, just
         * current cashier's receipt */

        $result = -1;
        if ($fes >= 25) {
            $query = "select emp_no, register_no, trans_no, sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
            ."from localtranstoday "
            ." group by register_no, emp_no, trans_no
            having sum((case when trans_type='T' THEN -1*total ELSE 0 end)) >= 30
            order by register_no,emp_no,trans_no desc";
            $dbc = Database::tDataConnect();
            if ($this->session->get("standalone") == 0) {
                $query = str_replace("localtranstoday","dtransactions",$query);
                $dbc = Database::mDataConnect();
            }
            $result = $dbc->query($query);

        } else {
            $dbc = Database::tDataConnect();

            $query = "
                SELECT emp_no, 
                    register_no, 
                    trans_no, 
                    SUM((CASE WHEN trans_type = 'T' THEN -1 * total ELSE 0 END)) AS total 
                FROM localtranstoday 
                WHERE register_no = ?
                    AND emp_no = ?
                    AND datetime >= " . $dbc->curdate() . "
                GROUP BY register_no, 
                    emp_no, 
                    trans_no
                HAVING SUM((CASE WHEN trans_type='T' THEN -1*total ELSE 0 END)) >= 30
                ORDER BY trans_no desc";
            $args = array($this->session->get('laneno'), $this->session->get('CashierNo'));
            $prep = $dbc->prepare($query);
            $result = $dbc->execute($prep, $args);
        }

        $ret = array();
        while ($row = $dbc->fetchRow($result)) {
            $ret[] = $row;
        }

        return $ret;
    }
    
    function body_content()
    {
        $trans = $this->getTransactions();
        $num_rows = count($trans);
        ?>

        <div class="baseHeight">
        <div class="listbox">
        <form id="selectform" name="selectform" 
            onsubmit="return cablist.submitWrapper('<?php echo $this->page_url; ?>');">
        <select name="selectlist" size="15" onblur="$('#selectlist').focus()"
            id="selectlist">

        <?php
        $selected = "selected";
        foreach ($trans as $row) {
            echo "<option value='".$row["emp_no"]."-".$row["register_no"]."-".$row["trans_no"]."'";
            echo $selected;
            echo ">lane ".substr(100 + $row["register_no"], -2)." Cashier ".$row["emp_no"]
                ." #".$row["trans_no"]." -- $".$row["total"];
            $selected = "";
        }
        if ($num_rows == 0) {
            echo "<option selected value=\"\">None found</option>";
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
}

AutoLoader::dispatch();

