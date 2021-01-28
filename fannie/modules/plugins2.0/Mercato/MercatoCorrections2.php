<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class MercatoCorrections2 extends FannieRESTfulPage
{
    protected $header = 'Mercato Corrections';
    protected $title = 'Mercato Corrections';

    protected function post_id_view()
    {
        list($date, $tnum) = explode(':', $this->id);
        list($emp, $reg, $trans) = explode('-', $tnum);
        $storeID = FormLib::get('storeID');
        $rtax = sprintf('%.2f', FormLib::get('rtax'));
        $dtax = sprintf('%.2f', FormLib::get('dtax'));
        $upcs = FormLib::get('upc');
        $tIDs = FormLib::get('trans_id');
        $qtys = FormLib::get('qty');
        $ttls = FormLib::get('total');

        for ($i=0; $i<count($upcs); $i++) {
            $fix1P = $this->connection->prepare("UPDATE " . FannieDB::fqn('dlog_15', 'trans') . "
                SET quantity=?, ItemQtty=?, unitPrice=?, total=?, regPrice=?
                WHERE tdate BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND upc=?
                    AND trans_id=?");
            $fix2P = $this->connection->prepare("UPDATE " . FannieDB::fqn('transarchive', 'trans') . "
                SET quantity=?, ItemQtty=?, unitPrice=?, total=?, regPrice=?
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND upc=?
                    AND trans_id=?");
            $fix3P = $this->connection->prepare("UPDATE " . FannieDB::fqn('bigArchive', 'arch') . "
                SET quantity=?, ItemQtty=?, unitPrice=?, total=?, regPrice=?
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND upc=?
                    AND trans_id=?");

            $fixArgs = array(
                $qtys[$i],
                $qtys[$i],
                $ttls[$i] / $qtys[$i],
                $ttls[$i],
                $ttls[$i] / $qtys[$i],
                $date, $date . ' 23:59:59',
                $emp,
                $reg,
                $trans,
                $upcs[$i],
                $tIDs[$i],
            );

            $this->connection->execute($fix1P, $fixArgs);
            $this->connection->execute($fix2P, $fixArgs);
            $this->connection->execute($fix3P, $fixArgs);
        }

        if ($rtax + $dtax > 0) {
            $fix1P = $this->connection->prepare("UPDATE " . FannieDB::fqn('dlog_15', 'trans') . "
                SET total=?
                WHERE tdate BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND upc='TAX'");
            $fix2P = $this->connection->prepare("UPDATE " . FannieDB::fqn('transarchive', 'trans') . "
                SET total=?
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND upc='TAX'");
            $fix3P = $this->connection->prepare("UPDATE " . FannieDB::fqn('bigArchive', 'arch') . "
                SET total=?
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND upc='TAX'");

            $fixArgs = array(
                $rtax + $dtax,
                $date, $date . ' 23:59:59',
                $emp,
                $reg,
                $trans,
            );

            $this->connection->execute($fix1P, $fixArgs);
            $this->connection->execute($fix2P, $fixArgs);
            $this->connection->execute($fix3P, $fixArgs);
        }

        if ($rtax > 0) {
            $fix1P = $this->connection->prepare("UPDATE " . FannieDB::fqn('dlog_15', 'trans') . "
                SET regPrice=?
                WHERE tdate BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND description LIKE '%Regular%'
                    AND upc='TAXLINEITEM'");
            $fix2P = $this->connection->prepare("UPDATE " . FannieDB::fqn('transarchive', 'trans') . "
                SET regPrice=?
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND description LIKE '%Regular%'
                    AND upc='TAXLINEITEM'");
            $fix3P = $this->connection->prepare("UPDATE " . FannieDB::fqn('bigArchive', 'arch') . "
                SET regPrice=?
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND description LIKE '%Regular%'
                    AND upc='TAXLINEITEM'");

            $fixArgs = array(
                $rtax,
                $date, $date . ' 23:59:59',
                $emp,
                $reg,
                $trans,
            );

            $this->connection->execute($fix1P, $fixArgs);
            $this->connection->execute($fix2P, $fixArgs);
            $this->connection->execute($fix3P, $fixArgs);
        }

        if ($dtax > 0) {
            $fix1P = $this->connection->prepare("UPDATE " . FannieDB::fqn('dlog_15', 'trans') . "
                SET regPrice=?
                WHERE tdate BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND description LIKE '%Deli%'
                    AND upc='TAXLINEITEM'");
            $fix2P = $this->connection->prepare("UPDATE " . FannieDB::fqn('transarchive', 'trans') . "
                SET regPrice=?
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND upc=?
                    AND description LIKE '%Deli%'
                    AND upc='TAXLINEITEM'");
            $fix3P = $this->connection->prepare("UPDATE " . FannieDB::fqn('bigArchive', 'arch') . "
                SET regPrice=?
                WHERE datetime BETWEEN ? AND ?
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND upc=?
                    AND description LIKE '%Deli%'
                    AND upc='TAXLINEITEM'");

            $fixArgs = array(
                $dtax,
                $date, $date . ' 23:59:59',
                $emp,
                $reg,
                $trans,
            );

            $this->connection->execute($fix1P, $fixArgs);
            $this->connection->execute($fix2P, $fixArgs);
            $this->connection->execute($fix3P, $fixArgs);
        }

        $ttlP = $this->connection->prepare("SELECT SUM(total)
            FROM " . FannieDB::fqn('dlog_15', 'trans') . "
            WHERE tdate BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND trans_type <> 'T'");
        $ttl = $this->connection->getValue($ttlP, array(
            $date, $date . ' 23:59:59',
            $emp,
            $reg,
            $trans,
        ));

        $fix1P = $this->connection->prepare("UPDATE " . FannieDB::fqn('dlog_15', 'trans') . "
            SET total=?
            WHERE tdate BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND trans_type = 'T'");

        $fix2P = $this->connection->prepare("UPDATE " . FannieDB::fqn('transarchive', 'trans') . "
            SET total=?
            WHERE datetime BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND trans_type = 'T'");

        $fix3P = $this->connection->prepare("UPDATE " . FannieDB::fqn('bigArchive', 'arch') . "
            SET total=?
            WHERE datetime BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND trans_type = 'T'");

        $fixArgs = array(
            -1 * $ttl,
            $date, $date . ' 23:59:59',
            $emp,
            $reg,
            $trans,
        );
        $this->connection->execute($fix1P, $fixArgs);
        $this->connection->execute($fix2P, $fixArgs);
        $this->connection->execute($fix3P, $fixArgs);

        return <<<HTML
<p>
<ul>
    <li><a href="../../../admin/LookupReceipt/RenderReceiptPage.php?date={$date}&receipt={$tnum}">View Receipt</a></li>
    <li><a href="MercatoCorrections2.php">Fix Another</a></li>
</ul>
</p>
HTML;
    }

    protected function get_id_view()
    {
        list($date, $tnum) = explode(':', $this->id);
        list($e, $r, $t) = explode('-', $tnum);
        $prep = $this->connection->prepare("SELECT upc, description, trans_id, store_id
            FROM " . FannieDB::fqn('dlog_15', 'trans') . "
            WHERE tdate BETWEEN ? AND ?
                AND trans_num=?
                AND trans_type='I'
                AND total=0");
        $res = $this->connection->execute($prep, array($date, $date . ' 23:59:59', $tnum));
        $table = '<table class="table">
            <tr><th>Item</th><th>Qty</th><th>$</th></tr>';
        $storeID = '??';
        while ($row = $this->connection->fetchRow($res)) {
            $table .= sprintf('<tr><td>
                <input type="hidden" name="upc[]" value="%s" />
                <input type="hidden" name="trans_id[]" value="%d" />
                %s</td>
                <td><input type="text" name="qty[]" class="form-control" /></td>
                <td><input type="text" name="total[]" class="form-control" /></td>
                </tr>', $row['upc'], $row['trans_id'], $row['description']);
            $storeID = $row['store_id'];
        }
        $table .= '</table>';
        return <<<HTML
<form method="post" action="MercatoCorrections2.php">
    <p>{$date} #{$t} ({$storeID})</p>
    <input type="hidden" name="id" value="{$this->id}" />
    <input type="hidden" name="storeID" value="{$storeID}" />
    {$table}
    <div class="form-group">
        <label>Regular Tax</label>
        <input type="text" class="form-control" name="rtax" value="0" />
    </div>
    <div class="form-group">
        <label>Deli Tax</label>
        <input type="text" class="form-control" name="dtax" value="0" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Update Transaction</button>
    </div>
</form>
HTML;
    }

    protected function get_view()
    {
        $res = $this->connection->query("SELECT date_id, max(tdate) AS tdate, trans_num, store_id
            FROM " . FannieDB::fqn('dlog_15', 'trans') . "
            WHERE register_no=40
            GROUP BY date_id, trans_num, store_id
            HAVING ABS(SUM(total)) > 0.005
        ");
        $ret = '<ul>';
        while ($row = $this->connection->fetchRow($res)) {
            list($date,) = explode(' ', $row['tdate']);
            list($emp,$reg,$trans) = explode('-', $row['trans_num']);
            $ret .= sprintf('<li><a href="MercatoCorrections2.php?id=%s:%s">%s #%s</a> (%d)',
                $date, $row['trans_num'], $date, $trans, $row['store_id']);
        }
        $ret .= '</ul>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

