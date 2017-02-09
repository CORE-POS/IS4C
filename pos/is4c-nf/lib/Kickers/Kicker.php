<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

namespace COREPOS\pos\lib\Kickers;
use COREPOS\pos\lib\Database;
use \CoreLocal;

/**
  @class Kicker
  Base class for opening cash drawer

*/
class Kicker 
{

    private static $builtin = array(
        'AlwaysKick',
        'Harvest_Kicker',
        'Kicker',
        'MCC_Kicker',
        'NoKick',
        'RAFC_Kicker',
        'WEFC_Toronto_Kicker',
        'WFC_Kicker',
        'YPSI_Kicker',
    );

    public static function factory($class)
    {
        if ($class != '' && in_array($class, self::$builtin)) {
            $class = 'COREPOS\\pos\\lib\Kickers\\' . $class;
            return new $class();
        } elseif ($class != '' && class_exists($class)) {
            return new $class();
        }

        return new self();
    }

    /**
      Determine whether to open the drawer
      @param $trans_num [string] transaction identifier
      @return boolean
    */
    public function doKick($trans_num)
    {
        if (CoreLocal::get('training') == 1) {
            return false;
        }
        $dbc = Database::tDataConnect();

        $query = "SELECT trans_id   
                  FROM localtranstoday 
                  WHERE 
                    (trans_subtype = 'CA' and total <> 0)
                    AND " . $this->refToWhere($trans_num);

        $result = $dbc->query($query);
        $numRows = $dbc->numRows($result);

        return ($numRows > 0) ? true : false;
    }

    protected function refToWhere($ref)
    {
        list($emp, $reg, $trans) = explode('-', $ref, 3);
        return sprintf(' emp_no=%d AND register_no=%d AND trans_no=%d ',
                        $emp, $reg, $trans);
    }

    /**
      Determine whether to open the drawer when
      a cashier signs in
      @return boolean
    */
    public function kickOnSignIn()
    {
        if (CoreLocal::get('training') == 1) {
            return false;
        }

        return true;
    }

    /**
      Determine whether to open the drawer when
      a cashier signs out
      @return boolean
    */
    public function kickOnSignOut()
    {
        if (CoreLocal::get('training') == 1) {
            return false;
        }

        return true;
    }

    protected function sessionOverride()
    {
        // use session to override default behavior
        // based on specific cashier actions rather
        // than transaction state
        $override = CoreLocal::get('kickOverride');
        CoreLocal::set('kickOverride',false);

        return $override ? true : false;
    }
}

