<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class SpecialOrdersTask extends FannieTask
{
    public $name = 'Special Orders Task';

    public $description = 'Moves order items that
    have been sold from pending order list to
    completed orders. Also auto-closes orders older
    than 90 days.
    Replaces the old nightly.specialorder.php script as
    well as homeless.specialorder.php.';

    public $default_schedule = array(
        'min' => 0,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    private function cleanFileCache()
    {
        $cachepath = sys_get_temp_dir()."/ordercache/";
        if (!is_dir($cachepath) && !mkdir($cachepath)) {
            return false;
        }
        $dir = opendir($cachepath);
        while (($file = readdir($dir)) !== false) {
            if ($file == "." || $file == "..") continue;
            if (!is_file($cachepath.$file)) continue;
            unlink($cachepath.$file);
        }
        closedir($dir);
    }

    private function getOldCalledWaiting($sql)
    {
        $subquery = "select p.order_id from PendingSpecialOrder as p
            left join SpecialOrders as s
            on p.order_id=s.specialOrderID
            where p.trans_id=0 and s.statusFlag=1
            and ".$sql->datediff($sql->now(),'datetime')." > 30";
        $cwIDs = "(";
        $res = $sql->query($subquery);
        while ($row = $sql->fetchRow($res)) {
            $cwIDs .= $row['order_id'].",";
        }
        $cwIDs = rtrim($cwIDs,",").")";

        return $cwIDs;
    }

    private function get90DaysOld($sql)
    {
        $subquery = "select p.order_id from PendingSpecialOrder as p
            left join SpecialOrders as s
            on p.order_id=s.specialOrderID
            where p.trans_id=0 
            and ".$sql->datediff($sql->now(),'datetime')." > 90";
        $allIDs = "(";
        $res = $sql->query($subquery);
        while ($row = $sql->fetchRow($res)) {
            $allIDs .= $row['order_id'].",";
        }
        $allIDs = rtrim($allIDs,",").")";

        return $allIDs;
    }

    private function closeOrders($sql, $cwIDs, $reason)
    {
        // transfer to completed orders
        $copyQ = "INSERT INTO CompleteSpecialOrder
            SELECT p.* FROM PendingSpecialOrder AS p
            WHERE p.order_id IN $cwIDs";

        // make note in history table
        $historyQ = "INSERT INTO SpecialOrderHistory
                    (order_id, entry_date, entry_type, entry_value)
                    SELECT p.order_id,
                        " . $sql->now() . ",
                        'AUTOCLOSE',
                        '$reason'
                    FROM PendingSpecialOrder AS p
                    WHERE p.order_id IN $cwIDs
                    GROUP BY p.order_id";
        $sql->query($historyQ);

        // clear from pending
        $sql->query($copyQ);
        $delQ = "DELETE FROM PendingSpecialOrder
            WHERE order_id IN $cwIDs";
        $sql->query($delQ);
    }

    private function cleanEmptyOrders($sql)
    {
        $cleanupQ = sprintf("
            SELECT p.order_id 
            FROM PendingSpecialOrder AS p 
                LEFT JOIN SpecialOrders AS o ON p.order_id=o.specialOrderID
            WHERE 
                (
                    o.specialOrderID IS NULL
                    OR %s(o.notes)=0
                )
                OR p.order_id IN (
                    SELECT order_id FROM CompleteSpecialOrder
                    WHERE trans_id=0
                    GROUP BY order_id
                )
            GROUP BY p.order_id
            HAVING MAX(trans_id)=0",
        ($sql->dbmsName()==="mssql" ? 'datalength' : 'length'));
        $cleanupR = $sql->query($cleanupQ);
        $empty = "(";
        $clean=0;
        while ($row = $sql->fetchRow($cleanupR)) {
            $empty .= $row['order_id'].",";
            $clean++;
        }
        $empty = rtrim($empty,",").")";

        $this->cronMsg("Finishing $clean orders");

        if (strlen($empty) > 2){
            //echo "Empties: ".$empty."\n";
            $delQ = "DELETE FROM PendingSpecialOrder WHERE order_id IN $empty AND trans_id=0";
            $delR = $sql->query($delQ);
        }
    }

    private function homelessOrderNotices($sql)
    {
        $OP = $this->config->get('OP_DB') . $sql->sep();

        $query = "
        select s.order_id,description,datetime,
        case when c.lastName ='' then b.LastName else c.lastName END as name
        from PendingSpecialOrder
        as s left join SpecialOrders as c on s.order_id=c.specialOrderID
        left join {$OP}custdata as b on s.card_no=b.CardNo and s.voided=b.personNum
        where s.order_id in (
        select p.order_id from PendingSpecialOrder as p
        left join SpecialOrders as n
        on p.order_id=n.specialOrderID
        where notes LIKE ''
        group by p.order_id
        having max(department)=0 and max(noteSuperID)=0
        and max(trans_id) > 0
        )
        and trans_id > 0
        order by datetime
        ";

        $res = $sql->query($query);
        if ($sql->numRows($res) > 0) {
            $msg_body = "Homeless orders detected!\n\n";
            while ($row = $sql->fetch_row($res)) {
                $msg_body .= $row['datetime'].' - '.(empty($row['name'])?'(no name)':$row['name']).' - '.$row['description']."\n";
                $msg_body .= "http://" . $this->config->get('HTTP_HOST') . '/' . $this->config->get('URL')
                    . "ordering/view.php?orderID=".$row['order_id']."\n\n";
            }
            $msg_body .= "These messages will be sent daily until orders get departments\n";
            $msg_body .= "or orders are closed\n";

            if ($this->config->get('COOP_ID') == 'WFC_Duluth') {
                $to_addr = "buyers, michael";
                $subject = "Incomplete SO(s)";
                mail($to_addr,$subject,$msg_body);
            } else {
                $this->cronMsg($msg_body, FannieTask::TASK_WORST_ERROR);
            }
        }
    }

    public function run()
    {
        // clean cache
        $this->cleanFileCache();

        $sql = FannieDB::get($this->config->get('TRANS_DB'));

        // auto-close called/waiting after 30 days
        $cwIDs = $this->getOldCalledWaiting($sql);
        if (strlen($cwIDs) > 2) {
            $this->closeOrders($sql, $cwIDs, 'Call/Waiting 30');
        }
        // end auto-close

        // auto-close all after 90 days
        $allIDs = $this->get90DaysOld($sql);
        if (strlen($allIDs) > 2){
            $this->closeOrders($sql, $allIDs, '90 Days');
        }
        // end auto-close

        $query = "SELECT CASE WHEN matched > 10 THEN matched ELSE mixMatch END as mixMatch,
                    CASE WHEN matched > 10 THEN mixMatch ELSE matched END AS matched,
                    MAX(datetime) as tdate,
                    MAX(emp_no) as emp,
                    MAX(register_no) AS reg,
                    MAX(trans_no) AS trans 
                  FROM transarchive
                  WHERE charflag='SO' 
                    AND emp_no <> 9999 
                    AND register_no <> 99 
                    AND trans_status NOT IN ('X','Z')
                  GROUP BY mixMatch,matched
                  HAVING sum(total) <> 0";
        $result = $sql->query($query);

        $checkP = $sql->prepare("SELECT order_id
                                 FROM SpecialOrderHistory
                                 WHERE order_id=?
                                    AND entry_type='PURCHASED'
                                    AND entry_date=?
                                    AND entry_value=?");
        $historyP = $sql->prepare("INSERT INTO SpecialOrderHistory
                                    (order_id, entry_date, entry_type, entry_value)
                                   VALUES
                                    (?, ?, 'PURCHASED', ?)");

        $order_ids = array();
        $trans_ids = array();
        while($row = $sql->fetch_row($result)) {
            $order_ids[] = (int)$row['mixMatch'];
            $trans_ids[] = (int)$row['matched'];

            // log to history if entry doesn't already exist
            $args = array(
                (int)$row['mixMatch'],
                $row['tdate'],
                $row['emp'] . '-' . $row['reg'] . '-' . $row['trans'],
            );
            $checkR = $sql->execute($checkP, $args);
            if ($checkR && $sql->num_rows($checkR) == 0) {
                $sql->execute($historyP, $args);
            }
        }

        $where = "( ";
        for($i=0;$i<count($order_ids);$i++){
            $where .= "(order_id=".$order_ids[$i]." AND trans_id=".$trans_ids[$i].") ";
            if ($i < count($order_ids)-1)
                $where .= " OR ";
        }
        $where .= ")";
        if ($where === '( )') {
            $where = '1=1';
        }

        $this->cronMsg("Found ".count($order_ids)." order items");

        // copy item rows to completed and delete from pending
        $copyQ = "INSERT INTO CompleteSpecialOrder SELECT * FROM PendingSpecialOrder WHERE $where";
        $copyR = $sql->query($copyQ);
        $delQ = "DELETE FROM PendingSpecialOrder WHERE $where";
        $delR = $sql->query($delQ);

        $chkQ = "SELECT * FROM PendingSpecialOrder WHERE $where";
        $chkR = $sql->query($chkQ);
        $this->cronMsg("Missed on ".$sql->num_rows($chkR)." items");

        // the trans_id=0 line contains additional, non-item order info
        // this determines where applicable trans_id=0 lines have already
        // been copied to CompletedSpecialOrder
        // this could occur if the order contained multiple items picked up
        // over multiple days
        $oids = "(";
        foreach($order_ids as $o)
            $oids .= $o.",";
        $oids = rtrim($oids,",").")";
        if ($oids === '()') {
            $oids = '(-999)';
        }
        $checkQ = "SELECT order_id FROM CompleteSpecialOrder WHERE trans_id=0 AND order_id IN $oids";
        $checkR = $sql->query($checkQ);
        $done_oids = array();
        while($row = $sql->fetch_row($checkR))
            $done_oids[] = (int)$row['order_id'];
        $todo = array_diff($order_ids,$done_oids);

        $this->cronMsg("Found ".count($todo)." new order headers");

        if (count($todo) > 0){
            $copy_oids = "(";
            foreach($todo as $o)
                $copy_oids .= $o.",";
            $copy_oids = rtrim($copy_oids,",").")";
            //echo "Headers: ".$copy_oids."\n";
            $copyQ = "INSERT INTO CompleteSpecialOrder SELECT * FROM PendingSpecialOrder
                WHERE trans_id=0 AND order_id IN $copy_oids";
            $copyR = $sql->query($copyQ);
        }

        // remove "empty" orders from pending
        $this->cleanEmptyOrders($sql);

        /**
          Find active orders that do not belong to a department
          and fire off emails until someone fixes it
        */
        $this->homelessOrderNotices($sql);
    }
}

