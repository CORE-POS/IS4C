<?php

use COREPOS\Fannie\API\item\ItemText;

/*******************************************************************************

    Copyright 2017 Whole Foods Co-op

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
  @class MyReceiptsModel
*/
class MyReceiptsModel extends BasicModel
{
    protected $name = "MyReceipts";

    protected $columns = array(
    'myReceiptID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'customerID' => array('type'=>'INT', 'index'=>true),
    'posReceiptID' => array('type'=>'VARCHAR(255)'),
    'description' => array('type'=>'VARCHAR(255)'),
    'quantity' => array('type'=>'DECIMAL(10,2)'),
    'price' => array('type'=>'MONEY'),
    'tdate' => array('type'=>'DATETIME'),
    'seqID' => array('type'=>'INT'),
    );

    public function etl($config)
    {
        $settings = $config->get('PLUGIN_SETTINGS');
        $mydb = $settings['MyWebDB'] . $this->connection->sep();
        $opdb = $config->get('OP_DB') . $this->connection->sep();
        $transdb = $config->get('TRANS_DB') . $this->connection->sep();

        $clearP = $this->connection->prepare("DELETE FROM {$mydb}MyReceipts WHERE tdate < ?");
        $clearR = $this->connection->execute($clearP, array(date('Y-m-d', strtotime('90 days ago'))));

        $dateP = $this->connection->prepare("SELECT MAX(tdate) FROM {$mydb}MyReceipts");
        $maxDate = $this->connection->getValue($dateP);
        if (!$maxDate) {
            $maxDate = date('Y-m-d', strtotime('90 days ago'));
        }
        //echo "$maxDate\n";

        $insP = $this->connection->prepare("INSERT INTO {$mydb}MyReceipts
            (customerID, posReceiptID, description, quantity, price, tdate, seqID)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        $query = "SELECT t.upc,
                t.date_id,
                t.tdate,
                t.quantity,
                t.total,
                t.trans_id,
                t.store_id,
                t.card_no,
                t.trans_num,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL('u', 't') . "
            FROM {$transdb}dlog_90_view AS t
                LEFT JOIN {$opdb}products AS p ON t.upc=p.upc AND t.store_id=p.store_id
                LEFT JOIN {$opdb}productUser AS u ON t.upc=u.upc
            WHERE card_no=?
                AND tdate > ?
            ORDER BY tdate DESC";
        $prep = $this->connection->prepare($query);
        $this->connection->startTransaction();
        $memP = $this->connection->prepare("SELECT DISTINCT card_no FROM {$transdb}dlog_90_view AS d
            LEFT JOIN {$opdb}custdata AS c ON d.card_no=c.CardNo
            WHERE c.personNum=1 AND c.type='PC' AND tdate > ?");
        $memR = $this->connection->execute($memP, array($maxDate));
        $num = $this->connection->numRows($memR);
        $count = 1;
        while ($memW = $this->connection->fetchRow($memR)) {
            //echo "$count/$num\r";
            $limiter = array();
            $res = $this->connection->execute($prep, array($memW['card_no'], $maxDate));
            while ($row = $this->connection->fetchRow($res)) {
                $mem = $row['card_no'];
                if (count($limiter) >= 10) {
                    continue;
                }
                $fullID = $row['date_id'] . '-' . $row['store_id'] . '-' . $row['trans_num'];
                $limiter[$fullID] = true;
                list($tdate,) = explode(' ', $row['tdate'], 2);
                $desc = $row['description'];
                if (!empty($row['brand'])) {
                    $desc = $row['brand'] . ' ' . $desc;
                }
                $this->connection->execute($insP, array(
                    $mem,
                    $fullID,
                    $desc,
                    $row['quantity'],
                    $row['total'],
                    $tdate,
                    $row['trans_id'],
                ));
            }
            $count++;
        }
        //echo "\n";
        $this->connection->commitTransaction();
    }

}

