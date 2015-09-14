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

class rplist extends NoInputCorePage 
{

    function preprocess()
    {
        if (isset($_REQUEST['selectlist'])) {
            if (!empty($_REQUEST['selectlist'])) {
                $print_class = CoreLocal::get('ReceiptDriver');
                if ($print_class === '' || !class_exists($print_class)) {
                    $print_class = 'ESCPOSPrintHandler';
                }
                $PRINT_OBJ = new $print_class();
                $receipt = ReceiptLib::printReceipt('reprint', $_REQUEST['selectlist']);
                if (session_id() != '') {
                    session_write_close();
                }
                if(is_array($receipt)) {
                    if (!empty($receipt['any'])) {
                        $PRINT_OBJ->writeLine($receipt['any']);
                    }
                    if (!empty($receipt['print'])) {
                        $PRINT_OBJ->writeLine($receipt['print']);
                    }
                } elseif(!empty($receipt)) {
                    $PRINT_OBJ->writeLine($receipt);
                }
            }
            $this->change_page($this->page_url."gui-modules/pos2.php");

            return false;
        }

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
        $db = Database::tDataConnect();
        $query = "
            SELECT register_no, 
                emp_no, 
                trans_no, 
                SUM(CASE 
                    WHEN trans_type='T' AND department=0 THEN -1 * total 
                    ELSE 0 
                END) AS total 
            FROM localtranstoday 
            WHERE register_no = ?
                AND emp_no = ?
                AND datetime >= " . $db->curdate() . "
            GROUP BY register_no, 
                emp_no, 
                trans_no 
            ORDER BY trans_no DESC";
        $args = array(CoreLocal::get('laneno'), CoreLocal::get('CashierNo')); 
        $prep = $db->prepare($query);
        $result = $db->execute($prep, $args);
        $num_rows = $db->num_rows($result);
        ?>

        <div class="baseHeight">
        <div class="listbox">
        <form name="selectform" method="post" id="selectform" 
            action="<?php echo $_SERVER['PHP_SELF']; ?>" >
        <select name="selectlist" size="15" id="selectlist"
            onblur="$('#selectlist').focus()" >

        <?php
        $selected = "selected";
        for ($i = 0; $i < $num_rows; $i++) {
            $row = $db->fetch_array($result);
            echo "<option value='".$row["register_no"]."::".$row["emp_no"]."::".$row["trans_no"]."'";
            echo $selected;
            echo ">lane ".substr(100 + $row["register_no"], -2)." Cashier ".substr(100 + $row["emp_no"], -2)
                ." #".$row["trans_no"]." -- $".
                sprintf('%.2f',$row["total"]);
            $selected = "";
        }
        ?>

        </select>
        </div>
        <?php
        if (CoreLocal::get('touchscreen')) {
            echo '<div class="listbox listboxText">'
                . DisplayLib::touchScreenScrollButtons('#selectlist')
                . '</div>';
        }
        ?>
        <div class="listboxText coloredText centerOffset">
        <?php echo _("use arrow keys to navigate"); ?><br />
        <p>
            <button type="submit" class="pos-button wide-button coloredArea">
            Reprint <span class="smaller">[enter]</span>
            </button>
        </p>
        <p>
            <button type="submit" class="pos-button wide-button errorColoredArea"
            onclick="$('#selectlist').append($('<option>').val(''));$('#selectlist').val('');">
            Cancel <span class="smaller">[clear]</span>
        </button></p>
        </div>
        </form>
        <div class="clear"></div>
        </div>

        <?php
    } // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
    new rplist();

?>
