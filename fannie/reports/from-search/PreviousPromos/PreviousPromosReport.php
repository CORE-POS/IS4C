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

    protected $report_headers = array('UPC', 'SKU', 'Brand', 'Description', 'Auto Par', 'Case Size', 'Promo 1', 'ADM', 'Promo 2', 'ADM', 'Promo 3', 'ADM', 'Avg All', 'xDays');
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
        $store = FormLib::get('store', false);
        if ($store === false) {
            $store = Store::getIdByIp();
        }
        $args[] = $store > 0 ? $store : 1;
        $days = FormLib::get('days', 1);

        $itemP = $dbc->prepare("
            SELECT p.upc, p.brand, p.description, auto_par, v.units, v.sku
            FROM products AS p
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
            WHERE p.upc IN ({$inStr})
                AND store_id=?"
        );
        $itemR = $dbc->execute($itemP, $args);
        $data = array();
        while ($itemW = $dbc->fetchRow($itemR)) {
            $record = array(
                $itemW['upc'],
                ($itemW['sku'] ? $itemW['sku'] : 'n/a'),
                $itemW['brand'],
                $itemW['description'],
                sprintf('%.2f', $itemW['auto_par']),
                $itemW['units'],
            );
            $batchR = $dbc->execute($batchP, array($itemW['upc']));
            $averages = array();
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
                        AND " . DTrans::isStoreID($store) . "
                        AND tdate BETWEEN ? AND ?");
                list($realStart,) = explode(' ', $batchW['startDate']);
                list($realEnd,) = explode(' ', $batchW['endDate']);
                $qty = $dbc->getValue($qtyP, array($itemW['upc'], $store, $realStart . ' 00:00:00', $realEnd . ' 23:59:59'));
                $end = new DateTime($batchW['endDate']);
                $diff = $end->diff(new DateTime($batchW['startDate']));
                $avg = sprintf('%.2f', $qty / ($diff->days + 1));
                if ($avg > 0) {
                    $averages[] = $avg;
                }
                $record[] = $avg;
            }
            $all_avg = count($averages) == 0 ? 0 : array_sum($averages) / count($averages);
            $record[] = sprintf('%.2f', $all_avg);
            $record[] = sprintf('%.2f', $days * $all_avg);
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

    public function report_description_content()
    {
        if ($this->report_format != 'html') {
            return array();
        }

        $url = $this->config->get('URL');
        $this->add_script($url . 'src/javascript/jquery.js');
        $dates_form = '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
        foreach ($_POST as $key => $value) {
            if ($key != 'store') {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $dates_form .= sprintf('<input type="hidden" name="%s[]" value="%s" />', $key, $v);
                    }
                } else {
                    $dates_form .= sprintf('<input type="hidden" name="%s" value="%s" />', $key, $value);
                }
            }
        }
        $stores = FormLib::storePicker();
        $days = FormLib::get('days', 1);
        $dates_form .= '
            <input type="hidden" name="excel" value="" id="excel" />
            Days
            <input type="text" name="days" value="' . $days . '" onchange="var d=this.value; $(\'.reportColumn13\').each(function(){ var b = $(this).siblings(\'.reportColumn12\').html(); $(this).html(Math.round(d*b*100)/100); });" />
            ' . $stores['html'] . '
            <button type="submit" onclick="$(\'#excel\').val(\'\');return true;">Change Store</button>
            <button type="submit" onclick="$(\'#excel\').val(\'csv\');return true;">Download</button>
            </form>';

        return array($dates_form);
    }
}

FannieDispatch::conditionalExec();

