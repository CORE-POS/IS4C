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
  @class MyRoundUpsModel
*/
class MyRoundUpsModel extends BasicModel
{
    protected $name = "MyRoundUps";

    protected $columns = array(
    'myRoundUpID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'customerID' => array('type'=>'INT', 'index'=>true),
    'year' => array('type'=>'INT'),
    'month' => array('type'=>'TINYINT'),
    'total' => array('type'=>'MONEY'),
    );

    public function etl($config)
    {
        $settings = $config->get('PLUGIN_SETTINGS');
        $mydb = $settings['MyWebDB'] . $this->connection->sep();
        $opdb = $config->get('OP_DB') . $this->connection->sep();
        $transdb = $config->get('TRANS_DB') . $this->connection->sep();

        $clearP = $this->connection->prepare("DELETE FROM {$mydb}MyRoundUps WHERE year < ?");
        $lastYear = date('Y') - 1;
        $clearR = $this->connection->execute($clearP, array($lastYear));

        $cutoff = date('Y-m-d', mktime(0,0,0,date('n')-1,1,date('Y')));
        $cutoff = '2017-01-01';
        $dlog = DTransactionsModel::selectDlog($cutoff, date('Y-m-d'));
        $chkP = $this->connection->prepare("SELECT myRoundUpID FROM {$mydb}MyRoundUps WHERE customerID=? AND year=? AND month=?");
        $upP = $this->connection->prepare("UPDATE {$mydb}MyRoundUps SET total=? WHERE myRoundUpID=?");
        $insP = $this->connection->prepare("INSERT INTO {$mydb}MyRoundUps (customerID, year, month, total) VALUES (?, ?, ?, ?)");

        $dlogP = $this->connection->prepare("SELECT card_no, YEAR(tdate) AS year, MONTH(tdate) AS month, SUM(total) AS ttl
                    FROM {$dlog}
                    WHERE tdate >= ?
                        AND department=701
                    GROUP BY card_no, YEAR(tdate), MONTH(tdate)");
        $dlogR = $this->connection->execute($dlogP, array($cutoff));
        $this->connection->startTransaction();
        while ($row = $this->connection->fetchRow($dlogR)) {
            $ruID = $this->connection->getValue($chkP, array($row['card_no'], $row['year'], $row['month']));
            if ($ruID) {
                $this->connection->execute($upP, array($row['ttl'], $ruID));
            } else {
                $this->connection->execute($insP, array($row['card_no'], $row['year'], $row['month'], $row['ttl']));
            }
        }
        $this->connection->commitTransaction();
    }
}

