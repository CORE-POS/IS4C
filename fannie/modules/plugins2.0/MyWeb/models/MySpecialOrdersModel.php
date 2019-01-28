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
  @class SpecialOrdersModel
*/
class MySpecialOrdersModel extends BasicModel
{
    protected $name = "MySpecialOrders";

    protected $columns = array(
    'specialOrderID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'customerID' => array('type'=>'INT', 'index'=>true),
    'orderDate' => array('type'=>'DATETIME'),
    'upc' => array('type'=>'VARCHAR(13)'),
    'brand' => array('type'=>'VARCHAR(255)'),
    'description' => array('type'=>'VARCHAR(255)'),
    'price' => array('type'=>'MONEY'),
    'caseSize' => array('type'=>'SMALLINT'),
    'numCases' => array('type'=>'SMALLINT'),
    'status' => array('type'=>'VARCHAR(255)'),
    'originalOrderID' => array('type'=>'INT'),
    'originalTransID' => array('type'=>'INT'),
    );

    public function etl($config)
    {
        $settings = $config->get('PLUGIN_SETTINGS');
        $mydb = $settings['MyWebDB'] . $this->connection->sep();
        $opdb = $config->get('OP_DB') . $this->connection->sep();
        $transdb = $config->get('TRANS_DB') . $this->connection->sep();
        $this->whichDB($settings['MyWebDB']);

        $this->connection->query("TRUNCATE TABLE {$mydb}MySpecialOrders");

        $cutoff = date('Y-m-d', strtotime('1 year ago'));
        $query = "SELECT o.quantity,
                o.ItemQtty,
                o.total,
                o.datetime,
                " . ItemText::longDescriptionSQL('u', 'o') . ",
                " . ItemText::longBrandSQL() . ",
                o.upc,
                s.statusFlag,
                o.card_no,
                o.order_id,
                o.trans_id
            FROM {$transdb}CompleteSpecialOrder AS o
                INNER JOIN {$transdb}SpecialOrders AS s ON o.order_id=s.specialOrderID
                LEFT JOIN {$opdb}custdata AS c ON o.card_no=c.CardNo
                LEFT JOIN {$opdb}products AS p ON o.upc=p.upc AND p.store_id=1
                LEFT JOIN {$opdb}productUser AS u ON o.upc=u.upc
            WHERE o.datetime > ?
                AND s.statusFlag IN (5, 7)
                AND s.noDuplicate=0
                AND o.trans_id > 0
                AND c.personNum=1
                AND c.type='PC'";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($cutoff));
        $this->connection->startTransaction();
        $num = $this->connection->numRows($res);
        $count = 1;
        while ($row = $this->connection->fetchRow($res)) {
            //echo "$count/$num\r";
            $this->reset();
            $this->customerID($row['card_no']);
            list($tdate,) = explode(' ', $row['datetime'], 2);
            $this->orderDate($tdate);
            $this->upc($row['upc']);
            $this->brand($row['brand']);
            $this->description($row['description']);
            $this->price($row['total']);
            $this->caseSize($row['quantity']);
            $this->numCases($row['ItemQtty']);
            $this->originalOrderID($row['order_id']);
            $this->originalTransID($row['trans_id']);
            switch ($row['statusFlag']) {
                case 5:
                case 7:
                    $this->status('Completed');
                    break;
                default:
                    $this->status('Unknown');
                    break;
            }
            $this->save();
            $count++;
        }
        //echo "\n";
        $this->connection->commitTransaction();

        $query = "SELECT o.quantity,
                o.ItemQtty,
                o.total,
                o.datetime,
                " . ItemText::longDescriptionSQL('u', 'o') . ",
                " . ItemText::longBrandSQL() . ",
                o.upc,
                s.statusFlag,
                o.card_no,
                o.order_id,
                o.trans_id
            FROM {$transdb}PendingSpecialOrder AS o
                INNER JOIN {$transdb}SpecialOrders AS s ON o.order_id=s.specialOrderID
                LEFT JOIN {$opdb}custdata AS c ON o.card_no=c.CardNo
                LEFT JOIN {$opdb}products AS p ON o.upc=p.upc AND p.store_id=1
                LEFT JOIN {$opdb}productUser AS u ON o.upc=u.upc
            WHERE s.statusFlag IN (0, 1, 2, 3, 4, 5)
                AND o.trans_id > 0
                AND c.personNum=1
                AND c.type='PC'";
        $res = $this->connection->query($query);
        $this->connection->startTransaction();
        $num = $this->connection->numRows($res);
        $count = 1;
        while ($row = $this->connection->fetchRow($res)) {
            //echo "$count/$num\r";
            $this->reset();
            $this->customerID($row['card_no']);
            list($tdate,) = explode(' ', $row['datetime'], 2);
            $this->orderDate($tdate);
            $this->upc($row['upc']);
            $this->brand($row['brand']);
            $this->description($row['description']);
            $this->price($row['total']);
            $this->caseSize($row['quantity']);
            $this->numCases($row['ItemQtty']);
            $this->originalOrderID($row['order_id']);
            $this->originalTransID($row['trans_id']);
            switch ($row['statusFlag']) {
                case 0:
                case 1:
                case 2:
                case 3:
                    $this->status('Processing order');
                    break;
                case 4:
                    $this->status('Ordered from Supplier');
                    break;
                case 5:
                    $this->status('At the Store');
                    break;
                default:
                    $this->status('Unknown');
                    break;
            }
            $this->save();
            $count++;
        }
        //echo "\n";
        $this->connection->commitTransaction();
    }
}

