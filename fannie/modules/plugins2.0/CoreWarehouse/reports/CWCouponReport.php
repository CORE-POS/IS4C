<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CWCouponReport extends FannieReportPage 
{
    public $description = '[In-Depth Coupon Report] lists in store coupon usages with
        additional information about member shopping habits before and after the
        coupon. Requires CoreWarehouse plugin.';
    public $themed = true;
    public $report_set = 'Membership';
    protected $header = 'Coupon Impact on Member Shopping';
    protected $title = 'Coupon Impact on Member Shopping';
    protected $required_fields = array('coupon_id', 'date1', 'date2');
    protected $report_headers = array('Mem#', 'Date', 'Trans#', 'Coupon Amount', 'Coupon Basket',
        '', 'Prev 1', 'Prev 2', 'Prev 3', 'Prev 4', 'Prev 5', 'Prev Avg.',
        '', 'Next 1', 'Next 2', 'Next 3', 'Next 4', 'Next 5', 'Next Avg.');

    public function fetch_report_data()
    {
        $dbc = $this->connection;

        try {
            $dlog = DTransactionsModel::selectDlog($this->form->date1, $this->form->date2);

            $args = array(
                '00499999' . str_pad($this->form->coupon_id, 5, '0', STR_PAD_LEFT),
                $this->form->date1 . ' 00:00:00',
                $this->form->date2 . ' 23:59:59',
            );
        } catch (Exception $ex) {
            return array();
        }
        $prep = $dbc->prepare(
            'SELECT d.card_no,
                d.trans_num,
                d.tdate,
                d.total
             FROM ' . $dlog . ' AS d
             WHERE d.trans_type=\'T\'
                AND d.upc=?
                AND d.tdate BETWEEN ? AND ?
        ');
        $res = $dbc->execute($prep, $args);

        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['WarehouseDatabase']);

        $couponBasketP = $dbc->prepare('
            SELECT 
                retailTotal+nonRetailTotal,
                start_time,
                end_time
            FROM transactionSummary
            WHERE date_id = ?
                AND trans_num=?
        ');
        $beforeBasketP = $dbc->prepare('
            SELECT date_id,
                trans_num,
                retailTotal+nonRetailTotal
            FROM transactionSummary
            WHERE card_no = ?
                AND date_id <= ?
                AND start_time < ?
            ORDER BY start_time DESC LIMIT 5
        ');
        $afterBasketP = $dbc->prepare('
            SELECT date_id,
                trans_num,
                retailTotal+nonRetailTotal
            FROM transactionSummary
            WHERE card_no = ?
                AND date_id >= ?
                AND start_time > ?
            ORDER BY start_time LIMIT 5
        ');

        $report = array();
        while ($w = $dbc->fetchRow($res)) {
            $record = array(
                $w['card_no'],
                date('Y-m-d', strtotime($w['tdate'])),
                $w['trans_num'],
                $w['total']
            );
            $date_id = date('Ymd', strtotime($w['tdate']));

            $r = $dbc->execute($couponBasketP, array($date_id, $w['trans_num']));
            $basket = $dbc->fetchRow($r);
            $record[] = $basket[0];
            $record[] = '';

            $before_count = 0;
            $avg_before = 0.0;
            $befores = array();
            if ($w['card_no'] != 11 && $w['card_no'] != 9) {
                $r = $dbc->execute($beforeBasketP, array($w['card_no'], $date_id, $basket['start_time']));
                while ($before = $dbc->fetchRow($r)) {
                    $befores[] = substr($before[0], 0, 4) . '-' . substr($before[0], 4, 2) . '-' . substr($before[0], -2) . ' ' . $before[2];
                    $avg_before += $before[2];
                    $before_count++;
                    if ($before_count > 5) {
                        break;
                    }
                }
            } 
            if ($before_count != 0) {
                $avg_before /= $before_count;
            } else {
                $avg_before = 0;
            }
            for ($i=$before_count; $i<5; $i++) {
                $record[] = 'n/a';
            }
            foreach (array_reverse($befores) as $b) {
                $record[] = $b;
            }
            $record[] = sprintf('%.2f', $avg_before);
            $record[] = '';

            $after_count = 0;
            $avg_after = 0.0;
            if ($w['card_no'] != 11 && $w['card_no'] != 9) {
                $r = $dbc->execute($afterBasketP, array($w['card_no'], $date_id, $basket['start_time']));
                while ($after = $dbc->fetchRow($r)) {
                    $record[] = substr($after[0], 0, 4) . '-' . substr($after[0], 4, 2) . '-' . substr($after[0], -2) . ' ' . $after[2];
                    $avg_after += $after[2];
                    $after_count++;
                    if ($after_count > 5) {
                        break;
                    }
                }
            }
            if ($after_count != 0) {
                $avg_after /= $after_count;
            } else {
                $avg_after = 0;
            }
            for ($i=$after_count; $i<5; $i++) {
                $record[] = 'n/a';
            }
            $record[] = sprintf('%.2f', $avg_after);

            $report[] = $record;
        }

        return $report;
    }

    public function calculate_footers($data)
    {
        $ttl = 0;
        $pre = 0;
        $post = 0;
        foreach ($data as $row) {
            $ttl += $row[4];
            $pre += $row[11];
            $post += $row[18];
        }

        return array('Uses', count($data), '', sprintf('%.2f', $ttl/count($data)), sprintf('%.2f', $ttl), '',
            '', '', '', '', '',
            sprintf('%.2f', $pre / count($data)), '', 
            '', '', '', '', '',
            sprintf('%.2f', $post / count($data))
        );
    }


    public function form_content()
    {
        $ret = '<form method="get">
            <div class="col-sm-5">
                <label>Coupon</label>
                <select name="coupon_id" class="form-control">';
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $hc = new HouseCouponsModel($dbc);
        foreach ($hc->find() as $obj) {
            if ($obj->description() == '') {
                continue;
            }
            $ret .= sprintf('<option value="%s">%s</option>',
                $obj->coupID(), $obj->description());
        }
        $ret .= '</select>
            <p>
                <button type="submit" class="btn btn-default">Submit</button>
            </p>
            </div>';
        $ret .= FormLib::standardDateFields();
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

