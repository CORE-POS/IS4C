<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

use COREPOS\pos\plugins\Plugin;
use COREPOS\pos\lib\Database;

class WedgeStaffCharge extends Plugin {

    public $plugin_description = 'Legacy staff charge functionality that does not
                belong in main code base';

    public function plugin_transaction_reset()
    {
        /**
          @var casediscount
          Line item case discount percentage (as
          integer; 5 = 5%). This feature may be redundant
          in that it could be handled with the generic
          line-item discount. It more or less just differs
          in that the messages say "Case".
        */
        CoreLocal::set("casediscount",0);
    }

    public function pluginEnable(){
        // create database structures
        $db = Database::pDataConnect();
        if (!$db->table_exists('chargecode')){
            $chargeCodeQ = "CREATE TABLE chargecode (
                staffID varchar(4),
                chargecode varchar(6))";
            $db->query($chargeCodeQ);
        }
        if (!$db->table_exists('chargecodeview')){
            $ccView = "CREATE VIEW chargecodeview AS
                SELECT c.staffID, c.chargecode, d.blueLine
                FROM chargecode AS c, custdata AS d
                WHERE c.staffID = d.CardNo";
            $db->query($ccView);
        }
    }

    public function pluginDisable(){
        $db = Database::pDataConnect();
        // always remove view
        if ($db->table_exists('chargecodeview')){
            $db->query('DROP VIEW chargecodeview');
        }
        // only remove table if it's empty
        if ($db->table_exists('chargecode')){
            $chk = $db->query('SELECT staffID FROM chargecode');
            if ($db->num_rows($chk) == 0)
                $db->query('DROP TABLE chargecode');
        }
    }
}

