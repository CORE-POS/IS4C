<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class GumPosLayer
{
    /**
      Create a POS transaction
      @param $emp_no [int] employee ID
      @param $register_no [int] lane ID
      @param $lines [array] of records
      @return 
        - success: [string] transaction identifier
        - failure: [boolean] false
        - disabled: [boolean] true

      Each record is a set of key/value pairs 
      with the following keys:
      amount        => purchase amount
      department    => department ID#
      description   => text description
    */
    public static function writeTransaction($emp_no, $register_no, $lines)
    {
        return true;    
    }

    /**
      Get info about member
      @param $card_no [int] member ID#
      @return [object] CustdataModel or [boolean] false on failure
    */
    public static function getCustdata($card_no)
    {
        return new CustdataModel(null);
    }

    /**
      Get member contact info
      @param $card_no [int] member ID#
      @return [object] MeminfoModel or [boolean] false on failure
    */
    public static function getMeminfo($card_no)
    {
        return new MeminfoModel(null);
    }
}

