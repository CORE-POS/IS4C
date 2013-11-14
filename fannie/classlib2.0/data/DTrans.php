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

/**
  @class DTrans
*/
class DTrans 
{

    /**
      Array of default values for dtransaction-style tables
      The column 'datetime' is ommitted. Normally an SQL
      function like NOW() is used there and cannot be
      a parameter
    */
    public static $DEFAULTS = array(
        'register_no'=>0,
        'emp_no'=>0,
        'trans_no'=>0,
        'upc'=>'0',
        'description'=>'',
        'trans_type'=>'',
        'trans_subtype'=>'',
        'trans_status'=>'',
        'department'=>'',
        'quantity'=>0,
        'scale'=>0,
        'cost'=>0,
        'unitPrice'=>'',
        'total'=>'',
        'regPrice'=>'',
        'tax'=>0,
        'foodstamp'=>0,
        'discount'=>0,
        'memDiscount'=>0,
        'discountable'=>0,
        'discounttype'=>0,
        'voided'=>0,
        'percentDiscount'=>0,
        'ItemQtty'=>0,
        'volDiscType'=>0,
        'volume'=>0,
        'volSpecial'=>0,
        'mixMatch'=>'',
        'matched'=>0,
        'memType'=>'',
        'staff'=>'',
        'numflag'=>0,
        'charflag'=>'',
        'card_no'=>0,
        'trans_id'=>0
    );

    public static function parameterize($arr, $datecol='', $datefunc='')
    {
        $columns = !empty($datecol) && !empty($datefunc) ? $datecol.',' : '';
        $values = !empty($datecol) && !empty($datefunc) ? $datefunc.',' : '';
        $args = array();
        foreach($arr as $key => $val) {
            // validate column names
            if (!isset(self::$DEFAULTS[$key])) {
                continue;
            }
            $columns .= $key.',';
            $values .= '?,';
            $args[] = $val;
        }
        $columns = substr($columns,0,strlen($columns)-1);
        $values = substr($values,0,strlen($values)-1);

        return array(
            'columnString' => $columns,
            'valueString' => $values,
            'arguments' => $args
        );
    }

}

