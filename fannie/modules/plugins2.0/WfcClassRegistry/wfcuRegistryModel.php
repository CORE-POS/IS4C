<?php

/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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
  @class wfcuRegistryModel
*/
class wfcuRegistryModel extends BasicModel
{

    protected $name = "wfcuRegistry";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)'),
    'class' => array('type'=>'VARCHAR(255)'),
    'first_name' => array('type'=>'VARCHAR(30)','default'=>''),
    'last_name' => array('type'=>'VARCHAR(30)','default'=>''),
    'first_opt_name' => array('type'=>'VARCHAR(30)'),
    'last_opt_name' => array('type'=>'VARCHAR(30)'),
    'phone' => array('type'=>'VARCHAR(30)'),
    'opt_phone' => array('type'=>'VARCHAR(30)'),
    'card_no' => array('type'=>'INT(11)'),
    'payment' => array('type'=>'VARCHAR(30)'),
    'refunded' => array('type'=>'INT(1)'),
    'modified' => array('type'=>'DATETIME'),
    'store_id' => array('type'=>'SMALLINT(6)'),
    'start_time' => array('type'=>'TIME'),
    'date_paid' => array('type'=>'DATETIME'),
    'seat' => array('type'=>'INT(50)'),
    'seatType' => array('type'=>'INT(5)'),
    'details' => array('type'=>'TEXT'),
    'id' => array('type'=>'INT(6)','primary_key'=>TRUE),
    'refund' => array('type'=>'VARCHAR(30)'),
    'amount' => array('type'=>'DECIMAL(10,2)'),
    'email' => array('type'=>'VARCHAR(255)'),
    'childseat' => array('type'=>'TINYINY()'),
    );

    public function getNumSeatAvail($upc)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $args = array($upc);
        $prep = $dbc->prepare("
            SELECT MAX(seat) - SUM(CASE WHEN first_name != '' AND seatType=1 THEN 1 ELSE 0 END) as seatsLeft,
                p.description,
                p.soldOut
            FROM wfcuRegistry AS r
                LEFT JOIN productUser AS p ON LPAD(r.upc,13,'0')=p.upc
                WHERE r.upc = ?;
        ");
        $res = $dbc->execute($prep,$args);
        while ($row = $dbc->fetchRow($res)) {
            $numSeatLeft = $row['seatsLeft'];
        }

        return $numSeatLeft;

    }

    public function setSoldOut($upc)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $localDB = $dbc;
        include(__DIR__.'/../../../src/Credentials/OutsideDB.tunneled.php');
        $remoteDB = $dbc;

        $args = array(str_pad($upc, 13, '0', STR_PAD_LEFT));
        $prep = $dbc->prepare("UPDATE productUser SET soldOut = 1 WHERE upc = ?");
        $res = $localDB->execute($prep, $args);
        $res = $remoteDB->execute($prep, $args);
        if ($er = $dbc->error()) return $er;

        return false;
    }

    public function getFirstAvailSeat($upc)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $prep = $dbc->prepare("
            SELECT id 
            FROM wfcuRegistry 
            WHERE upc = ?
                AND seatType = 1 
                AND LENGTH(first_name) = 0 
                AND LENGTH(last_name) = 0 
            ORDER BY SEAT ASC 
            LIMIT 1; 
        ");
        $res = $dbc->execute($prep, array($upc));
        $row = $dbc->fetchRow($res);
        $id = $row['id'];
        if ($id > 1) {
            return $id;
        } else {
            // if class is full, create new seat
            $maxA = array($upc);
            $maxP = $dbc->prepare("SELECT MAX(seat)+1 AS seat 
                FROM wfcuRegistry WHERE upc = ? AND seatType = 1;");
            $maxR = $dbc->execute($maxP, $maxA);
            $row = $dbc->fetchRow($maxR);
            $maxSeat = $row['seat'];
            $newA = array($upc, $maxSeat);
            $newP = $dbc->prepare("INSERT INTO wfcuRegistry (upc, seat, seatType, first_name, last_name)
                VALUES (?, ?, 1, '', '')");
            $newR = $dbc->execute($newP, $newA);
            
            return self::getFirstAvailSeat($upc);
        }

    }
}

