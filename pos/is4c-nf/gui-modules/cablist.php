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

class cablist extends NoInputPage 
{

    function head_content()
    {
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <script type="text/javascript">
        function submitWrapper(){
            var ref = $('#selectlist').val();
            if (ref != ""){
                $.ajax({
                    url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-cabreceipt.php',
                    type: 'get',
                    cache: false,
                    data: 'input='+ref,
                    success: function(data){
                        location='<?php echo $this->page_url; ?>gui-modules/pos2.php';
                    }
                });
            }
            else {
                location='<?php echo $this->page_url; ?>gui-modules/pos2.php';
            }

            return false;
        }
        </script> 
        <?php
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
        $this->add_onload_command("\$('#selectlist').focus();\n");
    }
    
    function body_content()
    {
        global $CORE_LOCAL;

        $db = Database::pDataConnect();
        $query = "SELECT frontendsecurity FROM employees WHERE emp_no=".$CORE_LOCAL->get("CashierNo");
        $result = $db->query($query);
        $fes = 0;
        if ($db->num_rows($result) > 0)
            $fes = array_pop($db->fetch_row($result));

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
            $db = Database::tDataConnect();
            if ($CORE_LOCAL->get("standalone") == 0) {
                $query = str_replace("localtranstoday","dtransactions",$query);
                $db = Database::mDataConnect();
            }
            $result = $db->query($query);

        } else {
            $db = Database::tDataConnect();

            $query = "select emp_no, register_no, trans_no, sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
            ."from localtranstoday where register_no = " . $CORE_LOCAL->get("laneno")
            ." AND emp_no = " . $CORE_LOCAL->get("CashierNo")
            ." AND datetime >= " . $db->curdate()
            ." group by register_no, emp_no, trans_no
            having sum((case when trans_type='T' THEN -1*total ELSE 0 end)) >= 30
            order by trans_no desc";

            $result = $db->query($query);
        }

        $num_rows = $db->num_rows($result);
        ?>

        <div class="baseHeight">
        <div class="listbox">
        <form id="selectform" name="selectform" onsubmit="return submitWrapper();">
        <select name="selectlist" size="10" onblur="$('#selectlist').focus()"
            id="selectlist">

        <?php
        $selected = "selected";
        for ($i = 0; $i < $num_rows; $i++) {
            $row = $db->fetch_array($result);
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
        </form>
        </div>
        <div class="listboxText coloredText centerOffset">
        use arrow keys to navigate<br />[enter] to reprint receipt<br />[clear] to cancel
        </div>
        <div class="clear"></div>
        </div>

        <?php
    } // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
    new cablist();

?>
