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

class AllLanesItemModule extends ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $FANNIE_LANES = FannieConfig::config('LANES');
        $upc = BarcodeLib::padUPC($upc);
        $queryItem = "SELECT * FROM products WHERE upc = ?";

        $ret = '<div id="AllLanesFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\"><a href=\"\" onclick=\"\$('#AllLanesFieldsetContent').toggle();return false;\">
                Lane Status
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="AllLanesFieldsetContent" class="panel-body' . $css . '">';
        
        for($i=0;$i<count($FANNIE_LANES);$i++){
            $f = $FANNIE_LANES[$i];
            $sql = new SQLManager($f['host'],$f['type'],$f['op'],$f['user'],$f['pw']);
            if (!is_object($sql) || $sql->connections[$f['op']] === False){
                $ret .= "<li class=\"alert-danger\">Can't connect to lane ".($i+1)."</li>";
                continue;
            }
            $prep = $sql->prepare_statement($queryItem);
            $resultItem = $sql->exec_statement($prep,array($upc));
            $num = $sql->num_rows($resultItem);

            if ($num == 0){
                $ret .= "<li class=\"alert-danger\">Item <strong>$upc</strong> not found on Lane ".($i+1)."</li>";
            }
            else if ($num > 1){
                $ret .= "<li class=\"alert-danger\">Item <strong>$upc</strong> found multiple times on Lane ".($i+1);
                $ret .= '<ul>';
                while ($rowItem = $sql->fetch_array($resultItem)){
                    $ret .= "<li>{$rowItem['upc']} {$rowItem['description']}</li>";
                }
                $ret .= '</ul></li>';
            }
            else {
                $rowItem = $sql->fetch_array($resultItem);
                $ret .= "<li>Item <span style=\"color:red;\">$upc</span> on Lane ".($i+1)."<ul>";
                $ret .= "<li>Price: {$rowItem['normal_price']}</li>";
                if ($rowItem['special_price'] <> 0){
                    $ret .= "<li class=\"alert-success\">ON SALE: {$rowItem['special_price']}</li>";
                }
                $ret .= "</ul></li>";
            }
        }
        $ret .= '</ul>';
        $ret .= '</div>';
        $ret .= '</div>';
        return $ret;
    }
}

?>
