<?php

/*******************************************************************************

    Copyright 2018 Whole Foods Co-op

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
        

/**
  @class MyEquityModel
*/
class MyEquityModel extends BasicModel
{
    protected $name = "MyEquity";

    protected $columns = array(
    'customerID' => array('type'=>'INT', 'primary_key', true),
    'total' => array('type'=>'MONEY'),
    'madePayment' => array('type'=>'TINYINT', 'default'=>0),
    );

    public function etl($config)
    {
        $settings = $config->get('PLUGIN_SETTINGS');
        $mydb = $settings['MyWebDB'] . $this->connection->sep();
        $opdb = $config->get('OP_DB') . $this->connection->sep();
        $transdb = $config->get('TRANS_DB') . $this->connection->sep();

        $this->connection->startTransaction();
        $chkP = $this->connection->prepare("SELECT customerID FROM {$mydb}MyEquity WHERE customerID=?");
        $upP = $this->connection->prepare("UPDATE {$mydb}MyEquity SET total=? WHERE customerID=?");
        $insP = $this->connection->prepare("INSERT INTO {$mydb}MyEquity (customerID, total) VALUES (?, ?)");
        $res = $this->connection->query("SELECT memnum, payments FROM {$transdb}equity_live_balance WHERE payments > 0");
        while ($row = $this->connection->fetchRow($res)) {
            $chk = $this->connection->getValue($chkP, array($row['memnum']));
            if ($chk) {
                $this->connection->execute($upP, array($row['payments'], $row['memnum']));
            } else {
                $this->connection->execute($insP, array($row['memnum'], $row['payments']));
            }
        }
        $this->connection->commitTransaction();
    }
}

