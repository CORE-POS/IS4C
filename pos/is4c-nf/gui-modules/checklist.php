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
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class checklist extends NoInputCorePage 
{
    private function handleInput($entered)
    {
        $entered = strtoupper($entered);

        if ($entered == "" || $entered == "CL"){
            // should be empty string
            // javascript causes this input if the
            // user presses CL{enter}
            // Redirect to main screen
            $this->change_page($this->page_url."gui-modules/pos2.php");
            return False;
        }

        if (!empty($entered)){ 
            // built department input string and set it
            // to be the next POS entry
            // Redirect to main screen
            $input = $this->form->tryGet('amt') . 'CQ' . $entered;
            $this->change_page(
                $this->page_url 
                . "gui-modules/pos2.php"
                . '?reginput=' . $input
                . '&repeat=1');
            return False;
        }

        return true;
    }

    /**
      Input processing function
    */
    function preprocess()
    {
        // a selection was made
        try {
            return $this->handleInput($this->form->search);
        } catch (Exception $ex) {}

        return true;
    } // END preprocess() FUNCTION

    /**
      Pretty standard javascript for
      catching CL typed in a select box
    */
    function head_content()
    {
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
        $amount = $this->form->tryGet('amt', 0);
        $dbc = Database::pDataConnect();
        $res = $dbc->query("SELECT TenderCode,TenderName FROM tenders WHERE TenderName LIKE '%check%' ORDER BY TenderName");

        echo "<div class=\"baseHeight\">"
            ."<div class=\"listbox\">"
            ."<form name=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\""
            ." id=\"selectform\">"
            ."<select name=\"search\" id=\"search\" "
            ."size=\"15\" onblur=\"\$('#search').focus();\">";

        $selected = "selected";
        while ($row = $dbc->fetchRow($res)) {
            echo "<option value='".$row["TenderCode"]."' ".$selected.">";
            echo $row['TenderName'];
            echo '</option>';
            $selected = "";
        }
        echo "</select>"
            .'<input type="hidden" name="amt" value="' . $amount . '" />'
            ."</form>"
            ."</div>"
            ."<div class=\"listboxText coloredText centerOffset\">"
            ._("[Clear] to Cancel") . "</div>"
            ."<div class=\"clear\"></div>";
        echo "</div>";

        $this->add_onload_command("selectSubmit('#search', '#selectform')\n");
        $this->add_onload_command("\$('#search').focus();\n");
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        ob_start();
        $phpunit->assertEquals(false, $this->handleInput(''));
        $phpunit->assertEquals(false, $this->handleInput('CL'));
        $phpunit->assertEquals(false, $this->handleInput('7'));
        ob_get_clean();
    }
}

AutoLoader::dispatch();

