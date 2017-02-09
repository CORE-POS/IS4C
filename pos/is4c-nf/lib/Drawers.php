<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;

/**
  @class Drawer
*/
class Drawers 
{
    private $session;
    private $dbc;

    public function __construct($session, $dbc)
    {
        $this->session = $session;
        $this->dbc = $dbc;
    }

    public function kick() 
    {
        $pin = self::current();
        if ($pin == 1) {
            ReceiptLib::writeLine(chr(27).chr(112).chr(0).chr(48)."0");
        } elseif ($pin == 2) {
            ReceiptLib::writeLine(chr(27).chr(112).chr(1).chr(48)."0");
        }
    }

    /**
      Which drawer is currently in use
      @return
        1 - Use the first drawer
        2 - Use the second drawer
        0 - Current cashier has no drawer

      This always returns 1 when dual drawer mode
      is enabled. Assignments in the table aren't
      relevant.
    */
    public function current()
    {
        if ($this->session->get('dualDrawerMode') !== 1) {
            return 1;
        }

        $dbc = $this->dbc;
        $chkQ = 'SELECT drawer_no FROM drawerowner WHERE emp_no=' . $this->session->get('CashierNo');
        $chkR = $dbc->query($chkQ);
        if ($dbc->numRows($chkR) == 0) {
            return 0;
        }
        $chkW = $dbc->fetchRow($chkR);

        return $chkW['drawer_no'];
    }

    /**
      Assign drawer to cashier
      @param $emp the employee number
      @param $num the drawer number
      @return success True/False
    */
    public function assign($emp,$num)
    {
        $dbc = $this->dbc;
        $upQ = sprintf('UPDATE drawerowner SET emp_no=%d WHERE drawer_no=%d',$emp,$num);
        $upR = $dbc->query($upQ);

        return ($upR !== false) ? true : false;
    }

    /**
      Unassign drawer
      @param $num the drawer number
      @return success True/False
    */
    public function free($num)
    {
        $dbc = $this->dbc;
        $upQ = sprintf('UPDATE drawerowner SET emp_no=NULL WHERE drawer_no=%d',$num);
        $upR = $dbc->query($upQ);

        return ($upR !== false) ? true : false;
    }

    /**
      Get list of available drawers
      @return array of drawer numbers
    */
    public function available()
    {
        $dbc = $this->dbc;
        $query = 'SELECT drawer_no FROM drawerowner WHERE emp_no IS NULL ORDER BY drawer_no';
        $res = $dbc->query($query);
        $ret = array();
        while ($row = $dbc->fetchRow($res)) {
            $ret[] = $row['drawer_no'];
        }

        return $ret;
    }
}

