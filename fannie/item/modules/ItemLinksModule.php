<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

include_once(dirname(__FILE__).'/../../classlib2.0/item/ItemModule.php');
include_once(dirname(__FILE__).'/../../classlib2.0/lib/FormLib.php');

class ItemLinksModule extends ItemModule {

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        global $FANNIE_URL;
        $upc = BarcodeLib::padUPC($upc);

        $dbc = $this->db();
        $p = $dbc->prepare_statement('SELECT upc FROM products WHERE upc=?');
        $r = $dbc->exec_statement($p,array($upc));

        $ret = '<fieldset id="LinksFieldset">';
        $ret = '<fieldset id="LinksFieldset">';
        $ret .=  "<legend onclick=\"\$('#LinksFieldsetContent').toggle();\">
                <a href=\"\" onclick=\"return false;\">Links</a>
                </legend>";
        $css = ($expand_mode == 1) ? '' : 'display:none;';
        $ret .= '<div id="LinksFieldsetContent" style="' . $css . '">';

        if ($dbc->num_rows($r) > 0){
            $ret .= '<div style="width:40%; float:left;">';
            $ret .= "<li><a href=\"javascript:shelftag('$upc');\">New Shelf Tag</a></li>";
            $ret .= "<li><a href=\"{$FANNIE_URL}item/deleteItem.php?upc=$upc&submit=submit\">Delete this item</a></li>";
            $ret .= '</div>';

            $ret .= '<div style="width:40%; float:left;">';
            $ret .= "<li><a href=\"{$FANNIE_URL}reports/PriceHistory/?upc=$upc\" target=\"_price_history\">Price History</a></li>";
            $ret .= "<li><a href=\"{$FANNIE_URL}reports/RecentSales/?upc=$upc\" target=\"_recentsales\">Recent Sales History</a></li>";
            $ret .= '</div>';

            $ret .= '<div style="clear:left;"></div>';

            $ret .= "<script type=\"text/javascript\">";
            $ret .= "function shelftag(u){";
            $ret .= "testwindow= window.open (\"addShelfTag.php?upc=\"+u, \"New Shelftag\",\"location=0,status=1,scrollbars=1,width=300,height=220\");";
            $ret .= "testwindow.moveTo(50,50);";
            $ret .= "}";
            $ret .= "</script>";
        }
        else {
            $ret .= sprintf('<input type="checkbox" name="newshelftag" value="%s" />
                    Create Shelf Tag</li>',$upc);
        }
        $ret .= '</div>';
        $ret .= '</fieldset>';

        return $ret;
    }

    function SaveFormData($upc){
        $upc = BarcodeLib::padUPC($upc);
        $ret = '';
        if (FormLib::get_form_value('newshelftag','') != ''){
            $ret .= "<script type=\"text/javascript\">";
            $ret .= "testwindow= window.open (\"addShelfTag.php?upc=$upc\", \"New Shelftag\",\"location=0,status=1,scrollbars=1,width=300,height=220\");";
            $ret .= "testwindow.moveTo(50,50);";
            $ret .= "</script>";
        }
        echo $ret; // output javascript to result page
        return True;
    }

}

?>
