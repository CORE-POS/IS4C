<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class FreshDealsMovementReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Fresh Deals Movement";
    protected $header = "Fresh Deals Movement";
    public $description = '[Fresh Deals Movement] lists weekly sales for a set of items for copy/pasting';
    public $report_set = 'Movement Reports';
    protected $required_fields = array('items', 'type');

    protected $report_headers = array('Order', 'Item', 'Brand', 'Description', 'Weekly Qty');
    protected $new_tablesorter = true;

    public function fetch_report_data()
    {
        $items = explode("\n", $this->form->items);
        $ts1 = strtotime('last tuesday');
        // -13 => previous wednesday and then back another full week
        $ts2 = mktime(0, 0, 0, date('n',$ts1), date('j',$ts1)-13, date('Y', $ts1));
        $store = FormLib::get('store');
        $dlog = DTransactionsModel::selectDlog(date('Y-m-d', $ts2), date('Y-m-d', $ts1));
        switch ($this->form->type) {
            case 'lc':
                list($query, $args) = $this->lcQuery($dlog, $items, $store);
                break;
            case 'scale':
                list($query, $args) = $this->scaleQuery($dlog, $items, $store);
                break;
            default:
            case 'upc':
                list($query, $args) = $this->upcQuery($dlog, $items, $store);
                break;
        }

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $prep = $dbc->prepare($query);
        // force output order to match input order
        $order = array();
        for ($i=0; $i<count($args); $i++) {
            $order[$args[$i]] = $i+1;
        }
        $args[] = $store;
        $args[] = date('Y-m-d 00:00:00', $ts2);
        $args[] = date('Y-m-d 23:59:59', $ts1);
        $res = $dbc->execute($prep, $args);
        $data = array();
        $cpt = array();
        $cpi = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $order[$row['upc']],
                $row['upc'],
                $row['brand'],
                $row['description'],
                sprintf('%.2f', $row['qty']/2),
            );
            $cpt[$order[$row['upc']]] = $row['qty']/2;
            $cpi[$order[$row['upc']]] = array($row['brand'], $row['description'], $row['upc'], $row['size'], $row['cost'], $row['normal_price']);
        }

        $table = '<table class="table small table-bordered">
            <tr><th>Copy/Paste Items</th>';
        ksort($cpi);
        foreach ($cpi as $id => $row) {
            $table .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%.2f</td><td>%.2f</td></tr>',
                $row[0], $row[1], $row[3], $row[2], $row[4], $row[5]);
        }
        $table .= '</table>';

        $table .= '<table class="table small table-bordered">
            <tr><th>Copy/Paste Movement</th>';
        ksort($cpt);
        foreach ($cpt as $id => $qty) {
            $table .= sprintf('<tr><td>%.2f</td></tr>', $qty);
        }
        $table .= '</table>';
        if ($this->report_format == 'html') {
            echo $table;
        }

        return $data;
    }

    private function lcQuery($dlog, $items, $store)
    {
        $args = array_map(function($i){ return trim($i); }, $items);
        $inStr = str_repeat('?,', count($args));
        $inStr = substr($inStr, 0, strlen($inStr)-1);
        $query = '
            SELECT u.likeCode AS upc,
                \'\' AS brand,
                l.likeCodeDesc AS description,
                ' . DTrans::sumQuantity('t').' as qty
            FROM ' . $dlog . ' AS t
                INNER JOIN upcLike AS u ON t.upc=u.upc
                LEFT JOIN likeCodes AS l ON u.likeCode=l.likeCode
            WHERE u.likeCode IN (' . $inStr . ')
                AND ' . DTrans::isStoreID($store, 't') . '
                AND t.tdate BETWEEN ? AND ?
            GROUP BY u.likeCode,
                l.likeCodeDesc'; 

        return array($query, $args);
    }

    private function scaleQuery($dlog, $items, $store)
    {
        $args = array_map(function($i){ return '002' . str_pad($i, 4, '0', STR_PAD_LEFT) . '000000'; }, $items);
        $inStr = str_repeat('?,', count($args));
        $inStr = substr($inStr, 0, strlen($inStr)-1);
        $query = '
            SELECT t.upc,
                p.brand,
                p.description,
                ' . DTrans::sumQuantity('t').' as qty
            FROM ' . $dlog . ' AS t
                ' . DTrans::joinProducts() . '
            WHERE t.upc IN (' . $inStr . ')
                AND ' . DTrans::isStoreID($store, 't') . '
                AND t.tdate BETWEEN ? AND ?
            GROUP BY t.upc,
                p.brand,
                p.description'; 

        return array($query, $args);
    }

    private function upcQuery($dlog, $items, $store)
    {
        $args = array_filter($items, function($i){ return trim($i) !== '' ? true : false; });
        $args = array_map(function($i){ 
            $item = trim($i);
            // trim check digit based on dashes or spacing
            if (strlen($item) > 2 && ($item[strlen($item)-2] == ' ' || $item[strlen($item)-2] == '-') && is_numeric($item[strlen($item)-1])) {
                $item = substr($item, 0, strlen($item)-2);
            }
            $item = str_replace(' ', '', $item);
            $item = str_replace('-', '', $item);

            return BarcodeLib::padUPC($item);
        }, $items);
        $inStr = str_repeat('?,', count($args));
        $inStr = substr($inStr, 0, strlen($inStr)-1);
        $query = '
            SELECT t.upc,
                COALESCE(u.brand, p.brand) AS brand,
                COALESCE(u.description, p.description) AS description,
                ' . DTrans::sumQuantity('t').' as qty,
                CASE 
                    WHEN p.scale=1 THEN \'LB\' 
                    ELSE CASE WHEN p.size IS NULL OR p.size=\'\' OR p.size=\'0\' THEN v.size ELSE p.size END
                END AS size,
                p.cost,
                p.normal_price
            FROM ' . $dlog . ' AS t
                ' . DTrans::joinProducts() . '
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                LEFT JOIN productUser AS u ON p.upc=u.upc
            WHERE t.upc IN (' . $inStr . ')
                AND ' . DTrans::isStoreID($store, 't') . '
                AND t.tdate BETWEEN ? AND ?
            GROUP BY t.upc,
                p.brand,
                p.description'; 

        return array($query, $args);
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        return '<form method="get">
            <div class="form-group">
                <label>Items</label>
                <textarea name="items" class="form-control" rows="10"></textarea>
            </div>
            <div class="form-group">
                <label>These are</label>
                <select name="type" class="form-control">
                    <option value="upc">UPCs</option>
                    <option value="lc">Like Codes</option>
                    <option value="scale">Scale PLUs</option>
                </select>
            </div>
            <div class="form-group">
                <label>Store</label>
                ' . $stores['html'] . '
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Submit</button>
            </div>
            </form>';
    }

    public function helpContent()
    {
        return '<p>This report calculates average weekly sales (in quantity) from
            a set of UPCs, like codes, or scale PLUs. Averages are currently calculated over
            a two week period ending last Tuesday.
            </p>
            <p>
            Copy/paste a list of UPCs, like codes, or scale PLUs into the Items field (one per line)
            and indicate what time of item identifiers they are. Besides a conventional report there
            will also be a copy/paste table that lists weekly movement <em>in the same order</em> as
            the items were entered. Ideally you should be able to copy a column of items from the Fresh
            Deals spreadsheet, run the report, and copy the movement numbers back into the spreadsheet.
            </p>';
    }
}

FannieDispatch::conditionalExec();

