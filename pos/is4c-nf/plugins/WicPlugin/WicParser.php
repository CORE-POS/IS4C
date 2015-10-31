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

    private function wicUPC($str)
    {
        $arr = CoreLocal::get('WicOverride');
        if (is_array($arr) && in_array(ltrim($str, '0'), $arr)) {
            return true;
        }
        $upc = substr('0000000000000' . $str, -13);
        $dbc = Database::pDataConnect();
        $itemP = $dbc->prepare('SELECT wicable FROM products WHERE upc=?');
        $wicable = $dbc->getValue($itemP, array($upc));
        if ($wicable !== false  && $wicable == 0) {
            return false;
        } else {
            return true;
        }
    }
}

