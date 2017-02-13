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

use COREPOS\Fannie\API\lib\Store;

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PreviousPromosReport extends FannieReportPage 
{
    public $discoverable = false; // not directly runnable; must start from search

    protected $title = "Fannie : Previous Promos";
    protected $header = "Previous Promos";

    protected $report_headers = array('UPC', 'Brand', 'Description', 'Auto Par', 'Promo 1', 'ADM', 'Promo 2', 'ADM', 'Promo 3', 'ADM');
    protected $required_fields = array('u');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $batchP = $dbc->prepare("
            SELECT b.batchID, b.batchName, b.startDate, b.endDate
            FROM batches AS b
                INNER JOIN batchList AS l ON b.batchID=l.batchID
                INNER JOIN batchType AS t ON b.batchType=t.batchTypeID
            WHERE l.upc=?
                AND t.datedSigns = 1
                AND b.batchType = 1
                AND b.discountType > 0
                AND b.endDate < " . $dbc->curdate() . "
            ORDER BY b.endDate DESC
        ");

        $upcs = FormLib::get('u', array());
        list($inStr, $args) = $dbc->safeInClause($upcs);
        $args[] = Store::getIdByIp();

        $itemP = $dbc->prepare("
            SELECT upc, brand, description, auto_par
            FROM products
            WHERE upc IN ({$inStr})
                AND store_id=?"
        );
        $itemR = $dbc->execute($itemP, $args);
        $data = array();
        while ($itemW = $dbc->fetchRow($itemR)) {
            $record = array($itemW['upc'], $itemW['brand'], $itemW['description'], sprintf('%.2f', $itemW['auto_par']));
            $batchR = $dbc->execute($batchP, array($itemW['upc']));
            for ($i=0; $i<3; $i++) {
                $batchW = $dbc->fetchRow($batchR);
                if (!$batchW) {
                    $record[] = 'n/a';
                    $record[] = 'n/a';
                    continue;
                }
                $record[] = $batchW['batchName'];
                $dlog = DTransactionsModel::selectDlog($batchW['startDate'], $batchW['endDate']);
                $qtyP = $dbc->prepare("
                    SELECT " . DTrans::sumQuantity() . " AS qty
                    FROM {$dlog}
                    WHERE upc=?
                        AND tdate BETWEEN ? AND ?");
                list($realStart,) = explode(' ', $batchW['startDate']);
                list($realEnd,) = explode(' ', $batchW['endDate']);
                $qty = $dbc->getValue($qtyP, array($itemW['upc'], $realStart . ' 00:00:00', $realEnd . ' 23:59:59'));
                $end = new DateTime($batchW['endDate']);
                $diff = $end->diff(new DateTime($batchW['startDate']));
                $record[] = sprintf('%.2f', $qty / ($diff->days + 1));
            }
            $data[] = $record;
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

