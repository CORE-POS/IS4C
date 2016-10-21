<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class DeliCateringAjax {}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    include(dirname(__FILE__).'/../../../config.php');
    if (!class_exists('FannieAPI')) {
        include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
    }

    $card_no = $_GET['card_no'];
    $json = array('name'=>null,'phone'=>null,'altPhone'=>null,'email'=>null);
    //$dbc = FannieDB::get($this->config->get('OP_DB'));
    $dbc = FannieDB::get($FANNIE_OP_DB);

    if ($card_no > 0) {
        $prep = $dbc->prepare('
            SELECT 
                firstName,
                lastName,
                cardNo,
                phone,
                altPhone,
                email
            FROM Customers
            WHERE cardNo = ?
            LIMIT 1
        ');
        $res = $dbc->execute($prep, $card_no);
        while ($row = $dbc->fetch_row($res)) {
            
            if ($row['firstName']) {
                $json['name'] = ucwords(strtolower($row['firstName'])) . ' ' . 
                    ucwords(strtolower($row['lastName']));
            }
            
            if ($row['phone']) $json['phone'] = $row['phone'];
            if ($row['altPhone']) $json['alt_phone'] = $row['altPhone'];
            if ($row['email']) $json['email'] = $row['email'];
        }
        if ($dbc->error()) {
            echo $dbc->error(). "<br>";
        } 
        
        echo json_encode($json);
        
    }   
}