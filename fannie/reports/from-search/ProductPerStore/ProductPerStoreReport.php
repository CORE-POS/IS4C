<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ProductPerStoreReport extends FannieReportPage 
{
    public $discoverable = false; // not directly runnable; must start from search

    protected $title = "Fannie : Product Per StoreReport";
    protected $header = "Product Per Store Report";

    protected $report_headers = array('UPC', 'Brand', 'Description');
    protected $required_fields = array('u');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $upcs = FormLib::get('u', array());
        $model = new StoresModel($dbc);
        $model->hasOwnItems(1);
        $stores = $model->find('storeID');

        $query = "
            SELECT p.upc,
                p.brand,
                p.description,
                p.auto_par,
                q.qtyLastQuarter AS qty,
                q.totalLastQuarter AS ttl,
                q.percentageDeptSales AS share
            FROM products AS p
                LEFT JOIN " . $this->config->get('ARCHIVE_DB') . $dbc->sep() . "productSummaryLastQuarter AS q
                ON p.upc=q.upc AND p.store_id=q.storeID
            WHERE p.upc=?
                AND p.store_id=?";
        $prep = $dbc->prepare($query);
        $data = array();
        foreach ($upcs as $upc) {
            $record = array();
            foreach ($stores as $store) {
                $row = $dbc->getRow($prep, array($upc, $store->storeID()));
                if ($row === false && count($record) == 0) {
                    $record[] = $upc;
                    $record[] = 'n/a';
                    $record[] = 'n/a';
                } elseif ($row && count($record) == 0) {
                    $record[] = $upc;
                    $record[] = $row['brand'];
                    $record[] = $row['description'];
                }
                if ($row) {
                    $record[] = sprintf('%.2f', $row['auto_par']);
                    $record[] = sprintf('%.2f', $row['qty']);
                    $record[] = sprintf('%.2f', $row['ttl']);
                } else {
                    $record[] = 0;
                    $record[] = 0;
                    $record[] = 0;
                }
            }
            $data[] = $record;
        }
        foreach ($stores as $store) {
            $this->report_headers[] = $store->description() . ' Daily Par';
            $this->report_headers[] = $store->description() . ' Qty LQ';
            $this->report_headers[] = $store->description() . ' $ LQ';
        }

        return $data;
    }

    public function form_content()
    {
        global $FANNIE_URL;
        return "Use <a href=\"{$FANNIE_URL}item/AdvancedItemSearch.php\">Search</a> to
            select items for this report";;
    }
}

FannieDispatch::conditionalExec();

