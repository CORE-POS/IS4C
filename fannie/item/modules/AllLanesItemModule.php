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

class AllLanesItemModule extends \COREPOS\Fannie\API\item\ItemModule 
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
        $ret .= '<div id="AllLanesFieldsetContent" class="panel-body' . $css . '"><ul>';
        $ret .= '</div>';

        $ret .= '</div>';
        return $ret;
    }

    public function getFormJavascript($upc)
    {
        $script = <<<JAVASCRIPT
function pollLanes() {
    if (window.$) {
        $(document).ready(function(){
            var req = {
                jsonrpc: '2.0',
                method: '\\\\COREPOS\\\\Fannie\\\\API\\\\webservices\\\\FannieLaneStatusService',
                id: new Date().getTime(),
                params: { upc: {{UPC}} }
            };
            $.ajax({
                url: '../ws/',
                type: 'post',
                data: JSON.stringify(req),
                dataType: 'json',
                contentType: 'application/json'
            }).done(function(resp) {
                for (var i=0; i<resp.result.length; i++) {
                    var lane = resp.result[i];
                    var elem = $('<li>');
                    if (lane.online === false) {
                        elem.addClass('alert-danger').html('Cannot connect to lane ' + (i+1));
                    } else if (lane.itemFound === 0) {
                        elem.addClass('alert-danger').html('Item not found on lane ' + (i+1));
                    } else {
                        if (lane.itemFound > 1) {
                            elem.addClass('alert-danger').html('Item found multiple items on lane ' + (i+1));
                        } else {
                            elem.html('Item <span style="color:red;">' + lane.itemUPC + '</span> on lane ' + (i+1));
                        }
                        var sublist = $('<ul>');
                        sublist.append($('<li>').html('Price: ' + lane.itemPrice));
                        if (lane.itemOnSale) {
                            sublist.append($('<li>').addClass('alert-success').html('On Sale: ' + lane.itemSalePrice));
                        }
                        elem.append(sublist);
                    }
                    $('#AllLanesFieldsetContent ul:first').append(elem);
                }
            });
        });
    } else { 
        setTimeout(pollLanes, 50);
    }
}
pollLanes();
JAVASCRIPT;
        return str_replace('{{UPC}}', ltrim($upc, '0'), $script);
    }
}

