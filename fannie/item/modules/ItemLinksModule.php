<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

class ItemLinksModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    public function width()
    {
        return self::META_WIDTH_FULL;
    }

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $FANNIE_URL = FannieConfig::config('URL');
        $upc = BarcodeLib::padUPC($upc);


        $ret = '';
        $ret = '<div id="LinksFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#LinksContents').toggle();return false;\">
                Links
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="LinksContents" class="panel-body' . $css . '">';
        // class="col-lg-1" works pretty well with META_WIDTH_HALF
        $ret .= '<div id="LinksList" class="col-sm-5">';

        $dbc = $this->db();
        $p = $dbc->prepare('SELECT upc FROM products WHERE upc=?');
        $r = $dbc->execute($p,array($upc));

        if ($dbc->num_rows($r) > 0){
            $ret .= '<div style="width:40%; float:left;">';
            $ret .= "<li><a href=\"javascript:shelftag('$upc');\">" .
                "New Shelf Tag</a></li>";
            $ret .= "<li><a href=\"{$FANNIE_URL}item/DeleteItemPage.php?id=$upc" .
                "\">Delete this item</a></li>";
            $ret .= '</div>';

            $ret .= '<div style="width:40%; float:left;">';
            $ret .= "<li><a href=\"{$FANNIE_URL}reports/PriceHistory/?upc=$upc\" " .
                "target=\"_price_history\">Price History</a></li>";
            $ret .= "<li><a href=\"{$FANNIE_URL}reports/RecentSales/?upc=$upc\" " .
                "target=\"_recentsales\">Recent Sales History</a></li>";
            $ret .= '</div>';

            $ret .= '<div style="clear:left;"></div>';

            $ret .= "<script type=\"text/javascript\">";
            $ret .= "function shelftag(u){";
            $ret .= "testwindow= window.open (\"addShelfTag.php?upc=\"+u, " .
                "\"New Shelftag\",\"location=0,status=1,scrollbars=1,width=300," .
                "height=650\");";
            $ret .= "testwindow.moveTo(50,50);";
            $ret .= "}";
            $ret .= "</script>";
        }
        else {
            $ret .= sprintf('<input type="checkbox" name="newshelftag" value="%s" />
                    Create Shelf Tag</li>',$upc);
        }

        $ret .= '</div>' . '<!-- /#LinksList -->';
        $ret .= '</div>' . '<!-- /#LinksContents -->';
        $ret .= '</div>' . '<!-- /#LinksFieldset -->';

        return $ret;
    }

    function SaveFormData($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $ret = '';
        try {
            if ($this->form->newshelftag !== '') {
                $ret .= "<script type=\"text/javascript\">";
                $ret .= "testwindow= window.open (\"addShelfTag.php?upc=$upc\", \"New Shelftag\",\"location=0,status=1,scrollbars=1,width=300,height=220\");";
                $ret .= "testwindow.moveTo(50,50);";
                $ret .= "</script>";
            }
        } catch (Exception $ex) {}
        echo $ret; // output javascript to result page
        return True;
    }

}

