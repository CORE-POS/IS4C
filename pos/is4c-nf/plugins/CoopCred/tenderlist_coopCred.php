<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class tenderlist_coopCred extends NoInputCorePage 
{

    /**
      Input processing function
    */
    function preprocess(){
        global $CORE_LOCAL;

        // a selection was made
        if (isset($_REQUEST['search'])){
            $entered = strtoupper($_REQUEST['search']);

            if ($entered == "" || $entered == "CL"){
                /* should be empty string
                 * javascript selectSubmit() causes this input if the
                 * user presses CL{enter}
                 * Redirect to main screen
                 */
                $CORE_LOCAL->set("tenderTotal","0");    
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            }

            if (!empty($entered)){ 
                /* built department input string and set it
                 * to be the next POS entry
                 * Redirect to main screen
                 */
                $input = $CORE_LOCAL->get("tenderTotal").$entered;
                $this->change_page(
                    $this->page_url
                    ."gui-modules/pos2.php"
                    . '?reginput=' . urlencode($input)
                    . '&repeat=1');
                return False;
            }
        }
        return True;
    } // END preprocess() FUNCTION

    /**
      Catch CL typed in a select box and return "".
    */
    function head_content(){
        ?>
        <script type="text/javascript" src="../../js/selectSubmit.js"></script>
        <?php
    } // END head_content() FUNCTION

    /*
     * Build a <select> form that submits back to this script.
     * Technical note:
     * The <select> can accept typed input that overrides
     *  the <option value>.
     *  See how preprocess() handles "CL"
    */
    function body_content(){
        global $CORE_LOCAL;
        $db = Database::pDataConnect();
        /* Only tenders the member may actually use. */
        $q = "SELECT t.TenderCode, p.tenderKeyCap, p.tenderName
            FROM opdata.tenders t
            JOIN coop_cred_lane.CCredPrograms p ON t.TenderCode = p.tenderType
            JOIN coop_cred_lane.CCredMemberships m ON p.programID = m.programID
            WHERE t.MaxAmount >0
            AND m.cardNo =" . $CORE_LOCAL->get("memberID") .
            " AND m.creditOK =1
            ORDER BY p.programID";
        $r = $db->query($q);

        echo "<div class=\"baseHeight\">"
            ."<div class=\"listbox\">";
        echo "<form name=\"selectform\" method=\"post\" " .
            "action=\"{$_SERVER['PHP_SELF']}\" " .
            "id=\"selectform\">";

        if ($db->num_rows($r) > 0) {
            $selectSize = ($db->num_rows($r) < 15) ? $db->num_rows($r) : 15;
            $paddingDepth = sprintf("%.1f",($selectSize / 2));
            echo "<select name=\"search\" id=\"search\" " .
                "size=\"" . $selectSize . "\" " .
                "onblur=\"\$('#search').focus();\">";
            $selected = "selected";
            while($row = $db->fetch_row($r)){
                echo "<option value='".$row["TenderCode"]."' ".$selected.">";
                echo $row['tenderKeyCap'] .
                    " &nbsp; " .
                    $row['TenderCode'] .
                    " &nbsp; " .
                    $row['tenderName'] .
                    "";
                echo '</option>';
                $selected = "";
            }
            echo "</select>" .
                "</form>" .
                "</div><!-- /.listbox -->";

            /* Text to the right of the list. */
            echo "<div class=\"listboxText coloredText\" ".
                "style='padding-top:{$paddingDepth}em;'>";
            if ($CORE_LOCAL->get("tenderTotal") >= 0)
                echo _("Tendering").' $';
            else
                echo _("Refunding").' $';
            printf('%.2f',abs($CORE_LOCAL->get("tenderTotal"))/100);
            echo '<br /><span style="font-weight:400;">' .
                _("[Clear] to exit") .
                "<br />or Browser Back and use the Q-tender" .
                "</span>";
            echo "</div><!-- /.listbox coloredText -->";
        } else {
            echo "<select name=\"search\" id=\"search\" "
                ."size=\"1\" " .
                "onblur=\"\$('#search').focus();\">" .
                "<option value='CL' SELECTED>" .
                _("No Coop Cred available") . "</option>" .
                "</select>";
            echo "</div><!-- /.listbox -->";
            /* Message under the listbox */
            echo "<div class=\"coloredText\" " .
                "style='clear:both; text-align:left; font-weight:bold;'>";
            echo _("There are no active Coop Cred tenders") .
                 '<br /> &nbsp; ' .
                 _("available to the Member.");
            echo '<br /><span style="font-weight:400;">' .
                _("[Enter] or [Clear] to exit") .
                "</span>";
            echo "</div><!-- /.coloredText -->";
            echo "</form>";
        }
        echo "<div class=\"clear\"></div>";
        echo "</div><!-- /.baseHeight -->";

        $this->add_onload_command("selectSubmit('#search', '#selectform')\n");
        $this->add_onload_command("\$('#search').focus();\n");

    } // END body_content() FUNCTION

}

AutoLoader::dispatch();

