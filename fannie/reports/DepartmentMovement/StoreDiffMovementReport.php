<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class StoreDiffMovementReport extends FannieReportPage 
{
    public $description = '[Store Diff Movement] shows movement discrepancies by store';
    public $report_set = 'Multistore';

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Dept#', 'Dept', 'Qty', '$');
    protected $sort_direction = 1;
    protected $sort_column = 8;
    protected $title = "Fannie : Store Movement Differences Report";
    protected $header = "Store Movement Differences Report";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $parts = FormLib::standardItemFromWhere(); 
        $query = $parts['query'];
        $args = $parts['args'];

        $query = '
            SELECT t.upc,
                p.brand,
                p.description,
                p.department,
                d.dept_name,
                ' . DTrans::sumQuantity('t') . ' AS qty,
                SUM(total) AS ttl
            ' . $query . ' 
                AND t.trans_type=\'I\'
                AND t.charflag<>\'SO\'
            GROUP BY t.upc,
                p.brand,
                p.description,
                p.department,
                d.dept_name';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $record = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['department'],
                $row['dept_name'],
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['ttl']),
            );
            $data[$row['upc']] = $record;
        }

        $upcs = array_keys($data);
        if (count($upcs) == 0) {
            return array();
        }
        $start_date = FormLib::getDate('date1', date('Y-m-d'));
        $end_date = FormLib::getDate('date2', date('Y-m-d'));
        $dlog = DTransactionsModel::selectDlog($start_date, $end_date);
        $args = array($start_date . ' 00:00:00', $end_date . ' 23:59:59');
        list($upcIn, $args) = $dbc->safeInClause($upcs, $args);
        $storeP = $dbc->prepare("
            SELECT t.upc,
                SUM(t.total) AS ttl,
                " . DTrans::sumQuantity('t') . " AS qty
            FROM $dlog AS t
            WHERE tdate BETWEEN ? AND ?
                AND t.upc IN ({$upcIn})
                AND t.store_id=?
            GROUP BY t.upc");
        $store = FormLib::get('store', 0);
        $model = new StoresModel($dbc);
        $model->hasOwnItems(1);
        $model->storeID($store, '<>');
        foreach ($model->find() as $obj) {
            $this->report_headers[] = $obj->description() . ' Qty';
            $this->report_headers[] = 'Qty Diff';
            $this->report_headers[] = $obj->description() . ' $';
            $this->report_headers[] = '$ Diff';
            $firstRow = $data[$upcs[0]];
            $width = count($firstRow) + 4;
            $storeArgs = array_merge($args, array($obj->storeID()));
            $res = $dbc->execute($storeP, $storeArgs);
            while ($row = $dbc->fetchRow($res)) {
                $upc = $row['upc'];
                $curQty = $data[$upc][5];
                $curTtl = $data[$upc][6];
                $data[$upc][] = sprintf('%.2f', $row['qty']);
                $data[$upc][] = sprintf('%.2f', $curQty - $row['qty']);
                $data[$upc][] = sprintf('%.2f', $row['ttl']);
                $data[$upc][] = sprintf('%.2f', $curTtl - $row['ttl']);
            }
            for ($i=0; $i<count($upcs); $i++) {
                $upc = $upcs[$i];
                if (count($data[$upc]) < $width) {
                    $curQty = $data[$upc][5];
                    $curTtl = $data[$upc][6];
                    $data[$upc][] = 0;
                    $data[$upc][] = $curQty;
                    $data[$upc][] = 0;
                    $data[$upc][] = $curTtl;
                    $data[$upc]['meta'] = FannieReportPage::META_BOLD;
                }
            }
        }

        $ret = array();
        foreach ($data as $upc => $row) {
            $ret[] = $row;
        } 

        return $ret;
    }

    public function form_content()
    {
        $form = FormLib::dateAndDepartmentForm();
        $form = str_replace('buyer', 'super-dept', $form);
        $form = str_replace('deptStart', 'dept-start', $form);
        $form = str_replace('deptEnd', 'dept-end', $form);
        $form = str_replace('Store', 'Baseline Store', $form);

        return $form;
    }
}

FannieDispatch::conditionalExec();


