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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class tenderlist extends NoInputCorePage 
{

    /**
      Input processing function
    */
    function preprocess()
    {
        // a selection was made
        if (isset($_REQUEST['search'])){
            $entered = strtoupper($_REQUEST['search']);

            if ($entered == "" || $entered == "CL"){
                // should be empty string
                // javascript causes this input if the
                // user presses CL{enter}
                // Redirect to main screen
                CoreLocal::set("tenderTotal","0");    
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            }

            if (!empty($entered)){ 
                // built department input string and set it
                // to be the next POS entry
                // Redirect to main screen
                $input = CoreLocal::get("tenderTotal").$entered;
                CoreLocal::set("msgrepeat",1);
                CoreLocal::set("strRemembered",$input);
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            }
        }
        return True;
    } // END preprocess() FUNCTION

    /**
      Pretty standard javascript for
      catching CL typed in a select box
    */
    function head_content(){
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <?php
    } // END head() FUNCTION

    /**
      Build a <select> form that submits
      back to this script
    */
    function body_content()
    {
        $db = Database::pDataConnect();
        $q = "SELECT TenderCode,TenderName FROM tenders 
            WHERE MaxAmount > 0
            ORDER BY TenderName";
        $r = $db->query($q);

        echo "<div class=\"baseHeight\">"
            ."<div class=\"listbox\">"
            ."<form name=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\""
            ." id=\"selectform\">"
            ."<select name=\"search\" id=\"search\" "
            ."size=\"15\" onblur=\"\$('#search').focus();\">";

        $selected = "selected";
        while($row = $db->fetch_row($r)){
            echo "<option value='".$row["TenderCode"]."' ".$selected.">";
            echo $row['TenderName'];
            echo '</option>';
            $selected = "";
        }
        echo "</select>"
            ."</div>";
        if (CoreLocal::get('touchscreen')) {
            echo '<div class="listbox listboxText">'
                . DisplayLib::touchScreenScrollButtons()
                . '</div>';
        }
        echo "<div class=\"listboxText coloredText centerOffset\">";
        if (CoreLocal::get("tenderTotal") >= 0) {
            echo _("tendering").' $';
        } else {
            echo _("refunding").' $';
        }
        printf('%.2f',abs(CoreLocal::get("tenderTotal"))/100);
        echo '<br />';
        echo _("use arrow keys to navigate")
            . '<p><button type="submit" class="pos-button wide-button coloredArea">
                OK <span class="smaller">[enter]</span>
                </button></p>'
            . '<p><button type="submit" class="pos-button wide-button errorColoredArea"
                onclick="$(\'#search\').append($(\'<option>\').val(\'\'));$(\'#search\').val(\'\');">
                Cancel <span class="smaller">[clear]</span>
                </button></p>'
            ."</div><!-- /.listboxText coloredText .centerOffset -->"
            ."</form>"
            ."<div class=\"clear\"></div>";
        echo "</div>";

        $this->add_onload_command("selectSubmit('#search', '#selectform')\n");
        $this->add_onload_command("\$('#search').focus();\n");
    } // END body_content() FUNCTION

}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
    new tenderlist();

?>
