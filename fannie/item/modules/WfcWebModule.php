<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op, Duluth, MN

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

class WfcWebModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $FANNIE_URL = FannieConfig::config('URL');
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();
        $ret = '<div id="WebListingFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#WebListingFieldsetContent').toggle();return false;\">
                Website Listing</a>
                </div>";

        $pu = new ProductUserModel($dbc);
        $pu->upc($upc);
        $pu->load();

        $css = ($expand_mode == 1 || $pu->enableOnline()) ? '' : ' collapse';
        $ret .= '<div id="WebListingFieldsetContent" class="panel-body' . $css . '">';

        $ret .= '<div class="form-group">
            <label>
                <input type="checkbox" name="u_online" value="1" '
                . ($pu->enableOnline() ? 'checked' : '') . ' />
                Sell Online
            </label>
            </div>';  
        $ret .= '<div class="form-group">
            <label>
                <input type="checkbox" name="u_soldout" value="1" '
                . ($pu->soldOut() ? 'checked' : '') . ' />
                Sold Out
            </label>
            </div>';
        $ret .= '<input type="hidden" name="u_already_online" value="'
            . ($pu->enableOnline() ? 1 : 0) . '" />';

        if ($dbc->tableExists('productExpires')) {
            $e = new ProductExpiresModel($dbc);
            $e->upc($upc);
            $e->load();
            $ret .= '<div class="form-group">'
                    . '<label>Expires</label> '
                    . '<input type="text" class="form-control date-field" id="u_expires" name="u_expires" 
                        value="' . ($e->expires() == '' ? '' : date('Y-m-d', strtotime($e->expires()))) . '" />'
                    . '</div>';
        }

        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    public function SaveFormData($upc)
    {
        $local = $this->db();
        $upc = BarcodeLib::padUPC($upc);
        $pu = new ProductUserModel($local);
        $pu->upc($upc);
        $pu->enableOnline(FormLib::get('u_online') == 1 ? 1 : 0);
        $pu->soldOut(FormLib::get('u_soldout') == 1 ? 1 : 0);
        $pu->save();

        include(dirname(__FILE__) . '/../../src/Credentials/OutsideDB.tunneled.php');
        $remote = $dbc;

        $pu->load();
        if ($pu->enableOnline() && $remote->isConnected()) {
            $pu->setConnection($remote);
            $pu->save();

            $prod = new ProductsModel($local);
            $prod->upc($upc);
            $prod->load();
            $prod->setConnection($remote);
            $prod->save();
        } elseif (FormLib::get('u_already_online') && $remote->isConnected()) {
            $prod = new ProductsModel($remote);
            $prod->upc($upc);
            $prod->delete();
        }

        if ($local->tableExists('productExpires')) {
            $e = new ProductExpiresModel($local);
            $e->upc($upc);
            $e->expires(FormLib::getDate('u_expires', date('Y-m-d')));
            $e->save();
            if ($e->expires() && $remote->isConnected()) {
                $e->setConnection($remote);
                $e->save();
            }
        }

    }
}
