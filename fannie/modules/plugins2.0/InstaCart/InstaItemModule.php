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

class InstaItemModule extends \COREPOS\Fannie\API\item\ItemModule 
{

    public function width()
    {
        return self::META_WIDTH_FULL;
    }

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);
        $settings = FannieConfig::config('PLUGIN_SETTINGS');

        $ret = '';
        $ret = '<div id="InstaItemDiv" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#InstaItemContents').toggle();return false;\">
                InstaCart
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="InstaItemContents" class="panel-body' . $css . '">';
        $ret .= '<div id="InstaItemTable" class="col-sm-5">';

        $dbc = $this->db();
        $dbName = $settings['InstaCartDB'] . $dbc->sep();
        if ($settings['InstaCartMode'] == 1) {
            $prep = $dbc->prepare("SELECT upc FROM {$dbName}InstaCartIncludes WHERE upc=?");
            $val = $dbc->getValue($prep, array($upc));
            $ret .= sprintf('<label><input type="checkbox" name="instaI" value="1" %s />
                        Include this item on InstaCart</label>',
                        ($val ? 'checked' : ''));
        } else {
            $prep = $dbc->prepare("SELECT upc FROM {$dbName}InstaCartExcludes WHERE upc=?");
            $val = $dbc->getValue($prep, array($upc));
            $ret .= sprintf('<label><input type="checkbox" name="instaE" value="1" %s />
                        Exclude this item from InstaCart</label>',
                        ($val ? 'checked' : ''));
        }

        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    public function saveFormData($upc)
    {
        $dbc = $this->connection;
        $settings = FannieConfig::config('PLUGIN_SETTINGS');
        $upc = BarcodeLib::padUPC($upc);
        $dbName = $settings['InstaCartDB'] . $dbc->sep();
        if ($settings['InstaCartMode'] == 1) {
            $inc = FormLib::get('instaI', false);
            if ($inc) {
                $prep = $dbc->prepare("INSERT INTO {$dbName}InstaIncludes (upc) VALUES (?)");
                $dbc->execute($prep, array($upc));
            } else {
                $prep = $dbc->prepare("DELETE FROM {$dbName}InstaIncludes WHERE upc=?");
                $dbc->execute($prep, array($upc));
            }
        } else {
            $inc = FormLib::get('instaE', false);
            if ($inc) {
                $prep = $dbc->prepare("INSERT INTO {$dbName}InstaExcludes (upc) VALUES (?)");
                $dbc->execute($prep, array($upc));
            } else {
                $prep = $dbc->prepare("DELETE FROM {$dbName}InstaExcludes WHERE upc=?");
                $dbc->execute($prep, array($upc));
            }
        }

        return true;
    }
}

