<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

use COREPOS\pos\parser\Parser;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;

class WicParser extends Parser 
{
    private $mode = false;

    public function check($str)
    {
        if (CoreLocal::get('WicMode') && is_numeric($str) && strlen($str) < 15 && !$this->wicUPC($str)) {
            $this->mode = 'upc';
        } elseif (CoreLocal::get('WicMode') && preg_match('/\d+DP\d+/', $str)) {
            $this->mode = 'openring';
        } elseif ($str == 'WIC') {
            $this->mode = 'menu';
        } else {
            return false;
        }

        return true;
    }

    public function parse($str)
    {
        $ret = $this->default_json();
        $plugin = new WicPlugin();
        switch ($this->mode) {
            case 'upc':
                $ret['main_frame'] = $plugin->pluginURL() . '/WicOverridePage.php?upc=' . $str;
                return $ret;
            case 'openring':
                $ret['output'] = DisplayLib::boxMsg(_('not allowed in WIC mode'));
                return $ret;
            case 'menu':
                $ret['main_frame'] = $plugin->pluginURL() . '/WicMenuPage.php';
                return $ret;
        }

        return $ret;
    }

    private $bulk = array(
        '000000000440',
        '000000000442',
        '000000000443',
        '000000000454',
    );

    private function wicUPC($str)
    {
        $dbc = Database::tDataConnect();
        $dbc->query('UPDATE localtemptrans SET percentDiscount=0');
        $arr = CoreLocal::get('WicOverride');
        $upc = substr('0000000000000' . $str, -13);
        if (is_array($arr) && in_array(ltrim($str, '0'), $arr)) {
            if (in_array($upc, $this->bulk) && (CoreLocal::get('weight') - CoreLocal::get('tare')) > 1) {
                CoreLocal::set('quantity', 1);
                CoreLocal::set('multiplier', 1);
                CoreLocal::set('tare', 0);
            }
            return true;
        }
        $dbc = Database::pDataConnect();
        if (substr($upc, 0, 3) == '002') {
            $upc = substr($upc, 0, 7) . '000000';
        }
        $itemP = $dbc->prepare('SELECT wicable FROM products WHERE upc=?');
        $wicable = $dbc->getValue($itemP, array($upc));
        if ($wicable !== false  && $wicable == 0 && !in_array($upc, $this->bulk)) {
            return false;
        } else {
            if (in_array($upc, $this->bulk) && (CoreLocal::get('weight') - CoreLocal::get('tare')) > 1.10) {
                return false;
            } elseif (in_array($upc, $this->bulk) && (CoreLocal::get('weight') - CoreLocal::get('tare')) > 1) {
                CoreLocal::set('quantity', 1);
                CoreLocal::set('multiplier', 1);
                CoreLocal::set('tare', 0);
            }
            return true;
        }
    }
}

