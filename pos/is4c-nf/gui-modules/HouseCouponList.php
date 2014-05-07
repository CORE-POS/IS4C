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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class HouseCouponList extends NoInputPage 
{

    function preprocess()
    {
        global $CORE_LOCAL;
        if (isset($_REQUEST['selectlist'])) {
            if (!empty($_REQUEST['selectlist'])) {
                $CORE_LOCAL->set('strRemembered', $_REQUEST['selectlist']);
                $CORE_LOCAL->set('msgrepeat', 1);
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
        global $CORE_LOCAL;

        $prefix = $CORE_LOCAL->get('houseCouponPrefix');
        if ($prefix == '') {
            $prefix = '00499999';
        }

        $db = Database::pDataConnect();
        $query = "SELECT h.coupID, h.description
                FROM houseCoupons AS h
                WHERE h.description <> ''
                    AND " . $db->datediff('endDate', $db->now()) . " >= 0
                ORDER BY h.description";
    
        $result = $db->query($query);
        $num_rows = $db->num_rows($result);
        ?>

        <div class="baseHeight">
        <div class="listbox">
        <form name="selectform" method="post" id="selectform" 
            action="<?php echo $_SERVER['PHP_SELF']; ?>" >
        <select name="selectlist" size="10" id="selectlist"
            onblur="$('#selectlist').focus()" >

        <?php
        $selected = "selected";
        for ($i = 0; $i < $num_rows; $i++) {
            $row = $db->fetch_array($result);
            printf('<option value="%s" %s>%d. %s</option>',
                    ($prefix . str_pad($row['coupID'], 5, '0', STR_PAD_LEFT)),
                    $selected, ($i+1), $row['description']
            );
            $selected = "";
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
    } // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    new HouseCouponList();
}

?>
