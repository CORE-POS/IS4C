<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class MercatoCorrections extends FannieRESTfulPage
{
    protected $header = 'Mercato Corrections';
    protected $title = 'Mercato Corrections';

    protected function post_id_view()
    {
        list($date, $tnum, $transID) = explode(':', $this->id);
        list($emp, $reg, $trans) = explode('-', $tnum);

        $upc = BarcodeLib::padUPC(FormLib::get('upc'));

        $itemP = $this->connection->prepare("SELECT department, description FROM products WHERE upc=?");
        $item = $this->connection->getRow($itemP, array($upc));
        if ($item === false) {
            return '<div class="alert alert-danger">Invalid UPC. Item not found</div>';
        }

        $fix1P = $this->connection->prepare("UPDATE " . FannieDB::fqn('dlog_15', 'trans') . "
            SET department=?, description=?, upc=?
            WHERE tdate BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND (upc='0000000000000' OR department IS NULL)
                AND trans_id=?");
        $fix2P = $this->connection->prepare("UPDATE " . FannieDB::fqn('transarchive', 'trans') . "
            SET department=?, description=?, upc=?
            WHERE datetime BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND (upc='0000000000000' OR department IS NULL)
                AND trans_id=?");
        $fix3P = $this->connection->prepare("UPDATE " . FannieDB::fqn('bigArchive', 'arch') . "
            SET department=?, description=?, upc=?
            WHERE datetime BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND (upc='0000000000000' OR department IS NULL)
                AND trans_id=?");

        $fixArgs = array(
            $item['department'], $item['description'], $upc,
            $date, $date . ' 23:59:59',
            $emp,
            $reg,
            $trans,
            $transID,
        );

        $this->connection->execute($fix1P, $fixArgs);
        $this->connection->execute($fix2P, $fixArgs);
        $this->connection->execute($fix3P, $fixArgs);

        return <<<HTML
<p>
<ul>
    <li><a href="../../../admin/LookupReceipt/RenderReceiptPage.php?date={$date}&receipt={$tnum}">View Receipt</a></li>
    <li><a href="MercatoCorrections.php">Fix Another</a></li>
</ul>
</p>
HTML;
    }

    protected function get_id_view()
    {
        list($date, $tnum, $tID) = explode(':', $this->id);
        list($e, $r, $t) = explode('-', $tnum);
        return <<<HTML
<form method="post" action="MercatoCorrections.php">
    <p>{$date} #{$t}</p>
    <input type="hidden" name="id" value="{$this->id}" />
    <div class="form-group">
        <label>Correct UPC</label>
        <input type="text" class="form-control" name="upc" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Update Transaction</button>
    </div>
</form>
HTML;
    }

    protected function get_view()
    {
        $res = $this->connection->query("SELECT tdate, trans_num, trans_id
            FROM " . FannieDB::fqn('dlog_15', 'trans') . "
            WHERE trans_type='I'
                AND register_no=40
                AND total <> 0
                AND (upc='0000000000000' OR department is NULL)
        ");
        $ret = '<ul>';
        while ($row = $this->connection->fetchRow($res)) {
            list($date,) = explode(' ', $row['tdate']);
            list($emp,$reg,$trans) = explode('-', $row['trans_num']);
            $ret .= sprintf('<li><a href="MercatoCorrections.php?id=%s:%s:%d">%s #%s</a>',
                $date, $row['trans_num'], $row['trans_id'], $date, $trans);
        }
        $ret .= '</ul>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

