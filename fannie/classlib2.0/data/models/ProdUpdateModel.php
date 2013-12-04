<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

if (!class_exists('FannieDB')) {
    include(dirname(__FILE__).'/../FannieDB.php');
}
if (!class_exists('BarcodeLib')) {
    include(dirname(__FILE__).'/../../lib/BarcodeLib.php');
}
if (!function_exists('checkLogin')) {
    include(dirname(__FILE__).'/../../../auth/login.php');
}

class ProdUpdateModel {

    public static function add($upc,$fields){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (!is_numeric($upc))
            return False;
        if (!is_int($upc) && !ctype_digit($upc))
            return False;
        $upc = substr($upc,0,13);
        $upc = BarcodeLib::padUPC($upc);

        $args = array();
        $q = 'INSERT INTO prodUpdate (';
        // translate some field names from products => prodUpdate
        foreach($fields as $name => $value){
            if ($name === 0 || $name === True) continue;
            switch($name){
            case 'description':
            case 'tax':
            case 'scale':
            case 'inUse':
                if ($name === 0 || $name === True)
                    break; // switch does loose comparison...
                $q .= $name.',';
                $args[] = $value;    
                break;
            case 'price':
            case 'normal_price':
                $q .= 'price,';
                $args[] = $value;
                break;
            case 'dept':
            case 'department':
                $q .= 'dept,';
                $args[] = $value;
                break;
            case 'fs':
            case 'foodstamp':
                $q .= 'fs,';
                $args[] = $value;
                break;
            case 'forceQty':
            case 'qttyEnforced':
                $q .= 'forceQty,';
                $args[] = $value;
                break;
            case 'noDisc':
            case 'discount':
                $q .= 'noDisc,';
                $args[] = $value;
                break;
            default:
                break;
            }
        }

        if ($q != 'INSERT INTO prodUpdate ('){
            $q .= 'upc,';
            $args[] = $upc;

            $q .= 'user,';
            $current_user = checkLogin();
            $uid = getUID($current_user);
            if ($current_user === False || $uid === False)
                $args[] = 0;
            else
                $args[] = $uid;

            $q .= 'modified) VALUES (';
            foreach($args as $a) $q .= '?,';
            $q .= $dbc->now().')';

            $insP = $dbc->prepare_statement($q);
            $insR = $dbc->exec_statement($insP, $args);
            if ($insR === False) return False;
        }
        return True;
    }

}

?>
