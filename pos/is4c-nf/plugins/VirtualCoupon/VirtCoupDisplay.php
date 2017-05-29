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

class VirtCoupDisplay extends NoInputCorePage 
{

    private $temp_result;
    private $temp_num_rows;
    private $entered;
    private $db;

    function preprocess()
    {
        /**
          Handle user input

          If input is blank or clear, return to the main page

          If input is numeric, build an appropriate coupon UPC
           and set that to repeat as the next pos2 input
        */
        if(isset($_REQUEST['search'])){
            $input = strtoupper($_REQUEST['search']);
            if ($input === "" || $input === "CL"){
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            }
            else if (is_numeric($input)){
                $upc = "00499999".str_pad((int)$input,5,'0',STR_PAD_LEFT);
                $this->change_page(
                    $this->page_url 
                    . "gui-modules/pos2.php"
                    . '?reginput=' . urlencode($upc)
                    . '&repeat=1');
                return False;
            }
        } 

        /**
          Lookup coupons by member number
        */
        $memberID = CoreLocal::get("memberID");
        $sql = Database::pDataConnect();

        $query = sprintf("select coupID,description FROM houseVirtualCoupons
            WHERE card_no=%d 
                AND " . $sql->curdate() . " BETWEEN start_date AND end_date",
            $memberID);
        $result = $sql->query($query);
        $num_rows = $sql->num_rows($result);

        $this->temp_result = $result;
        $this->temp_num_rows = $num_rows;
        $this->db = $sql;
        return True;
    } // END preprocess() FUNCTION

    function head_content()
    {
        if ($this->temp_num_rows > 0){
            $this->add_onload_command("\$('#search').keypress(processkeypress);\n");
            $this->add_onload_command("\$('#search').focus();\n");
        } else {
            $this->default_parsewrapper_js('reginput','selectform');
            $this->add_onload_command("\$('#reginput').focus();\n");
        }
        ?>
        <script type="text/javascript">
        var prevKey = -1;
        var prevPrevKey = -1;
        function processkeypress(e) {
            var jsKey;
            if (e.keyCode) // IE
                jsKey = e.keyCode;
            else if(e.which) // Netscape/Firefox/Opera
                jsKey = e.which;
            if (jsKey==13) {
                if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
                (prevKey == 108 || prevKey == 76) ){ //CL<enter>
                    $('#search option:selected').each(function(){
                        $(this).val('');
                    });
                }
                $('#selectform').submit();
            }
            prevPrevKey = prevKey;
            prevKey = jsKey;
        }
        </script> 
        <?php
    } // END head() FUNCTION

    function body_content()
    {
        $num_rows = $this->temp_num_rows;
        $result = $this->temp_result;
        $db = $this->db;

        echo "<div class=\"baseHeight\">"
            ."<form id=\"selectform\" method=\"post\" action=\"{$_SERVER['PHP_SELF']}\">";

        /*  No results. Just enter or CL to cancel
         */
        if ($num_rows < 1){
            echo "
            <div class=\"colored centeredDisplay\">
                <span class=\"larger\">";
                echo _("virtual coupons")."<br />"._("no coupons available");
            echo "</span>
                <input type=\"text\" name=\"search\" size=\"15\"
                       onblur=\"\$('#reginput').focus();\" id=\"reginput\" />
                <br />
                press [enter] to cancel
            </div>";
        }
        else {
            /* select box with available coupons */
            echo "<div class=\"listbox\">"
                ."<select name=\"search\" size=\"15\" "
                ."onblur=\"\$('#search').focus();\" ondblclick=\"document.forms['selectform'].submit();\" id=\"search\">";

            $selectFlag = (isset($selectFlag)?$selectFlag:0);
            for ($i = 0; $i < $num_rows; $i++) {
                $row = $db->fetchRow($result);
                if( $i == 0 && $selectFlag == 0) {
                    $selected = "selected";
                } else {
                    $selected = "";
                }
                echo "<option value='".$row["coupID"]."' ".$selected.">"
                    .$row["description"]."\n";
            }
            echo "</select></div>"
                ."<div class=\"listboxText coloredText centerOffset\">"
                ._("use arrow keys to navigate")."<p>"._("clear to cancel")."</div>"
                ."<div class=\"clear\"></div>";
        }
        echo "</form></div>";
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

