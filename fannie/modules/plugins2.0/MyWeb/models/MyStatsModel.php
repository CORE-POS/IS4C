<?php

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
  @class MyStatsModel
*/
class MyStatsModel extends BasicModel
{

    protected $name = "MyStats";

    protected $columns = array(
    'myStatID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'customerID' => array('type'=>'INT', 'index'=>true),
    'statID' => array('type'=>'INT'),
    'stat' => array('type'=>'VARCHAR(255)'),
    );

    private $statData = array(
        1 => array(
            'name' => 'Apples & Oranges',
            'appleLikeCodes' => array(4, 5, 27, 28, 29, 30, 31, 32, 33, 35, 47, 48, 84, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 111, 128, 190, 192, 201, 204, 271, 316, 420, 547, 548, 563, 569, 596, 600, 601, 603, 605, 606, 608, 611, 613, 614, 710, 711, 712, 713, 714, 716, 718, 727, 735, 775, 776, 777, 811, 848, 851, 852, 856, 907, 913),
            'orangeLikeCodes' => array(58, 196, 248, 285, 286, 287, 288, 289, 290, 291, 381, 577, 632, 658, 731, 925),
        ),
        2 => array(
            'name' => 'Coffee',
            'depts' => array(245, 251, 259, 69),
        ),
        3 => array(
            'name' => 'Bacon',
            'baconUPCs' => array('0027340000000', '0078679100710', '0078679109101', '0078679199701', '0027511000000', '0002531710100', '0061697371115', '0064864906058'),
        ),
        4 => array(
            'name' => 'Store Preference',
        ),
    );

    public function etl($config)
    {
        $settings = $config->get('PLUGIN_SETTINGS');
        $mydb = $settings['MyWebDB'] . $this->connection->sep();
        $opdb = $config->get('OP_DB') . $this->connection->sep();
        $transdb = $config->get('TRANS_DB') . $this->connection->sep();

        $this->connection->query("TRUNCATE TABLE {$mydb}MyStats");
        $insP = $this->connection->prepare("INSERT INTO {$mydb}MyStats
            (customerID, statID, stat) VALUES (?, ?, ?)");

        $apples = array();
        list($inStr, $args) = $this->connection->safeInClause($this->statData[1]['appleLikeCodes']);
        $prep = $this->connection->prepare("SELECT upc FROM upcLike WHERE likeCode IN ({$inStr})");
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $apples[] = $row['upc'];
        }
        $oranges = array();
        list($inStr, $args) = $this->connection->safeInClause($this->statData[1]['orangeLikeCodes']);
        $prep = $this->connection->prepare("SELECT upc FROM {$opdb}upcLike WHERE likeCode IN ({$inStr})");
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $oranges[] = $row['upc'];
        }

        list($appleIn, $appleArgs) = $this->connection->safeInClause($apples);
        $appleP = $this->connection->prepare("
            SELECT SUM(total)
            FROM {$transdb}dlog_90_view
            WHERE upc IN ({$appleIn})
                AND card_no=?");

        list($orangeIn, $orangeArgs) = $this->connection->safeInClause($oranges);
        $orangeP = $this->connection->prepare("
            SELECT SUM(total)
            FROM {$transdb}dlog_90_view
            WHERE upc IN ({$orangeIn})
                AND card_no=?");

        list($coffeeIn, $coffeeArgs) = $this->connection->safeInClause($this->statData[2]['depts']);
        $coffeeP = $this->connection->prepare("
            SELECT SUM(total)
            FROM {$transdb}dlog_90_view
            WHERE department IN ({$coffeeIn})
                AND card_no=?");

        list($baconIn, $baconArgs) = $this->connection->safeInClause($this->statData[3]['baconUPCs']);
        $baconP = $this->connection->prepare("
            SELECT SUM(total)
            FROM {$transdb}dlog_90_view
            WHERE upc IN ({$baconIn})
                AND card_no=?");

        $storeP = $this->connection->prepare("
            SELECT SUM(CASE WHEN store_id=1 THEN -total ELSE 0 END) AS hillside,
                SUM(CASE WHEN store_id=2 THEN -total ELSE 0 END) AS denfeld
            FROM {$transdb}dlog_90_view
            WHERE trans_type='T'
                AND card_no=?");

        $memR = $this->connection->query("SELECT DISTINCT card_no FROM {$transdb}dlog_90_view AS d
            LEFT JOIN {$opdb}custdata AS c ON d.card_no=c.CardNo
            WHERE c.personNum=1 AND c.type='PC'");
        $num = $this->connection->numRows($memR);
        $count = 1;
        $maxes = array('coffee'=>array(0,0,0,0,0), 'bacon'=>array(0,0,0,0,0));
        $owners = array();
        $this->connection->startTransaction();
        while ($memW = $this->connection->fetchRow($memR)) {
            //echo "$count/$num\r";
            $owners[] = $memW['card_no'];
            $apples = $this->connection->getValue($appleP, array_merge($appleArgs, array($memW['card_no'])));
            $oranges = $this->connection->getValue($orangeP, array_merge($orangeArgs, array($memW['card_no'])));
            if ($oranges < 0 || $oranges == null) $oranges = 0;
            if ($apples < 0 || $apples == null) $apples = 0;
            $ttl = $apples + $oranges;
            if ($apples > $oranges && $ttl != 0) {
                $stat = array('pref'=>'apples', 'by'=>sprintf('%.2f', ($apples / $ttl)*100));
            } elseif ($oranges > $apples && $ttl != 0) {
                $stat = array('pref'=>'oranges', 'by'=>sprintf('%.2f', ($oranges / $ttl)*100));
            } elseif ($ttl != 0) {
                $stat = array('pref'=>'equal');
            } else {
                $stat = array('pref'=>'neither');
            }
            $this->connection->execute($insP, array($memW['card_no'], 1, json_encode($stat)));

            $coffee = $this->connection->getValue($coffeeP, array_merge($coffeeArgs, array($memW['card_no'])));
            $coffee = $coffee < 0 || $coffee == null ? 0 : $coffee;
            if ($coffee > $maxes['coffee'][4]) {
                array_pop($maxes['coffee']);
                array_push($maxes['coffee'], $coffee);
                rsort($maxes['coffee']);
            }
            $stat = array('ttl'=>$coffee,'percent'=>0);
            $this->connection->execute($insP, array($memW['card_no'], 2, json_encode($stat)));

            $bacon = $this->connection->getValue($baconP, array_merge($baconArgs, array($memW['card_no'])));
            $bacon = $bacon < 0 || $bacon == null ? 0 : $bacon;
            if ($bacon > $maxes['bacon'][4]) {
                array_pop($maxes['bacon']);
                array_push($maxes['bacon'], $bacon);
                rsort($maxes['bacon']);
            }
            $stat = array('ttl'=>$bacon,'percent'=>0);
            $this->connection->execute($insP, array($memW['card_no'], 3, json_encode($stat)));

            $stores = $this->connection->getRow($storeP, array($memW['card_no']));
            if ($stores['hillside'] < 0 || $stores['hillside'] == null) $stores['hillside'] = 0;
            if ($stores['denfeld'] < 0 || $stores['denfeld'] == null) $stores['denfeld'] = 0;
            $ttl = $stores['hillside'] + $stores['denfeld'];
            if ($ttl == 0) {
                $stat = array('hillside' => '0.00', 'denfeld'=>'0.00');
            } else {
                $stat = array('hillside' => sprintf('%.2f', ($stores['hillside']/$ttl)*100),
                    'denfeld' => sprintf('%.2f', ($stores['denfeld']/$ttl)*100));
            }
            $this->connection->execute($insP, array($memW['card_no'], 4, json_encode($stat)));

            $count++;
        }
        //echo "\n";
        $this->connection->commitTransaction();

        $maxes['coffee'] = array_sum($maxes['coffee']) / 5;
        //echo "Coffee max: {$maxes['coffee']}\n";
        if ($maxes['coffee'] > 0) {
            $this->connection->startTransaction();
            $upP = $this->connection->prepare("UPDATE {$mydb}MyStats SET stat=? WHERE myStatID=?");
            $res = $this->connection->query("SELECT myStatID, stat FROM {$mydb}MyStats WHERE statID=2");
            while ($row = $this->connection->fetchRow($res)) {
                $json = json_decode($row['stat'], true);
                $json['percent'] = sprintf('%.2f', ($json['ttl'] / $maxes['coffee']) * 100);
                $this->connection->execute($upP, array(json_encode($json), $row['myStatID']));
            }
            $this->connection->commitTransaction();
        }
        $maxes['bacon'] = array_sum($maxes['bacon']) / 5;
        //echo "Bacon max: {$maxes['bacon']}\n";
        if ($maxes['bacon'] > 0) {
            $this->connection->startTransaction();
            $upP = $this->connection->prepare("UPDATE {$mydb}MyStats SET stat=? WHERE myStatID=?");
            $res = $this->connection->query("SELECT myStatID, stat FROM {$mydb}MyStats WHERE statID=3");
            while ($row = $this->connection->fetchRow($res)) {
                $json = json_decode($row['stat'], true);
                $json['percent'] = sprintf('%.2f', ($json['ttl'] / $maxes['bacon']) * 100);
                $this->connection->execute($upP, array(json_encode($json), $row['myStatID']));
            }
            $this->connection->commitTransaction();
        }
    }
}

