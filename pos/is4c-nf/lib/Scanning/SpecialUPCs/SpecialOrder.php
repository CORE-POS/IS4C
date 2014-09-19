<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

/**
   @class SpecialOrder
   WFC Electronic Special Orders

   Special order upc format:
   prefix orderID transID
   00454  xxxxxx  xx
  
   e.g., orderID #1, transID #1:
   0045400000101

   These IDs are used to locate the
   special order record in the 
   PendingSpecialOrder table on
   the server database
*/

class SpecialOrder extends SpecialUPC 
{

    public function isSpecial($upc)
    {
        if (substr($upc,0,5) == "00454") {
            return true;
        }

        return false;
    }

    public function handle($upc,$json)
    {
        global $CORE_LOCAL;

        $orderID = substr($upc,5,6);
        $transID = substr($upc,11,2);

        if ((int)$transID === 0) {
            $json['output'] = DisplayLib::boxMsg(_("Not a valid order"));
            return $json;
        }

        $db = Database::mDataConnect();
        $query = sprintf("SELECT upc,description,department,
                quantity,unitPrice,total,regPrice,d.dept_tax,d.dept_fs,
                ItemQtty,p.discountable
                FROM PendingSpecialOrder as p LEFT JOIN
                is4c_op.departments AS d ON p.department=d.dept_no
                WHERE order_id=%d AND trans_id=%d",
                $orderID,$transID);
        $result = $db->query($query);

        if ($db->num_rows($result) != 1) {
            $json['output'] = DisplayLib::boxMsg(_("Order not found"));
            return $json;
        }

        $row = $db->fetch_array($result);
        TransRecord::addRecord(array(
            'upc' => $row['upc'],
            'description' => $row['description'],
            'trans_type' => 'I',
            'department' => $row['department'],
            'quantity' => $row['quantity'],
            'unitPrice' => $row['unitPrice'],
            'total' => $row['total'],
            'regPrice' => $row['regPrice'],
            'tax' => $row['dept_tax'],
            'foodstamp' => $row['dept_fs'],
            'discountable' => $row['discountable'],
            'ItemQtty' => $row['ItemQtty'],
            'mixMatch' => $orderID,
            'matched' => $transID,
            'charflag' => 'SO',
        ));
        $json['output'] = DisplayLib::lastpage();
        $json['udpmsg'] = 'goodBeep';
        $json['redraw_footer'] = True;

        return $json;
    }
}

