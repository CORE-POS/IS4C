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
if (!class_exists('\\FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CWImpactReport extends FannieReportPage 
{
    public $discoverable = false;

    protected $header = 'Impact Report';
    protected $title = 'Impact Report';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array();

    public function fetch_report_data()
    {
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['WarehouseDatabase']);
        $opdb = $this->config->get('OP_DB') . $dbc->sep();

        $dateIDs = array(
            date('Ymd', strtotime(FormLib::get('date1'))),
            date('Ymd', strtotime(FormLib::get('date2'))),
        );
        $dates = array(
            date('Y-m-d', strtotime(FormLib::get('date1'))),
            date('Y-m-d', strtotime(FormLib::get('date2'))),
        );

        $dlog = DTransactionsModel::selectDlog($dates[0], $dates[1]);
        $dates[0] .= ' 00:00:00';
        $dates[1] .= ' 23:59:59';

        $accessP = $dbc->prepare("
            SELECT -1*SUM(total)
            FROM {$dlog}
            WHERE upc='DISCOUNT'
                AND memType=5
                AND tdate BETWEEN ? AND ?");
        $access = $dbc->getValue($accessP, $dates);

        $data = array();
        $data[] = array('Access Discount', number_format($access, 2), '');

        $memSales = $dbc->prepare("
            SELECT SUM(total) AS ttl,
                SUM(CASE WHEN memType IN (1,3,5,6) THEN total ELSE 0 END) AS memTTL
            FROM sumMemTypeSalesByDay
            WHERE date_id BETWEEN ? AND ?");
        $memInfo = $dbc->getRow($memSales, $dateIDs);
        $data[] = array('', '', '');
        $data[] = array('Sales to Owners', number_format($memInfo['memTTL'],2), sprintf('%.2f%%', $memInfo['memTTL'] / $memInfo['ttl'] * 100));

        $upcP = $dbc->prepare("
            SELECT upc
            FROM sumUpcSalesByDay
            WHERE date_id BETWEEN ? AND ?
            GROUP BY upc");
        $upcR = $dbc->execute($upcP, $dateIDs);
        $upcs = array();
        while ($upcW = $dbc->fetchRow($upcR)) {
            $upcs[] = $upcW[0];
        }

        list($inStr, $args) = $dbc->safeInClause($upcs);
        $skuStatP = $dbc->prepare("
            SELECT m.super_name,
                SUM(CASE WHEN (p.numflag & (1<<16)) <> 0 THEN 1 ELSE 0 END) as organic,
                SUM(CASE WHEN p.local = 1 THEN 1 ELSE 0 END) AS local,
                COUNT(*) AS allitems
            FROM {$opdb}products AS p
                INNER JOIN {$opdb}MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE p.upc IN ({$inStr})
                AND p.store_id=1
            GROUP BY m.super_name
            ORDER BY m.super_name");
        $skuR = $dbc->execute($skuStatP, $args);
        $organic = array();
        $local = array();
        $ttls = array(0, 0, 0);
        while ($skuW = $dbc->fetchRow($skuR)) {
            $organic[] = array(
                'Organic ' . $skuW['super_name'],
                number_format($skuW['organic'], 0),
                sprintf('%.2f%%', $skuW['organic'] / $skuW['allitems'] * 100),
            );
            $local[] = array(
                'Local ' . $skuW['super_name'],
                number_format($skuW['local'], 0),
                sprintf('%.2f%%', $skuW['local'] / $skuW['allitems'] * 100),
            );
            $ttls[0] += $skuW['organic'];
            $ttls[1] += $skuW['local'];
            $ttls[2] += $skuW['allitems'];
        }

        $data[] = array('', '', '');
        $data[] = array('Organic SKUs', '', '');
        $data = array_merge($data, $organic);
        $data[] = array('Organic Total', number_format($ttls[0],0), sprintf('%.2f%%', $ttls[0] / $ttls[2] * 100));
        $data[] = array('', '', '');
        $data[] = array('Local SKUs', '', '');
        $data = array_merge($data, $local);
        $data[] = array('Local Total', number_format($ttls[1],0), sprintf('%.2f%%', $ttls[1] / $ttls[2] * 100));

        $totalP = $dbc->prepare("
            SELECT SUM(total) AS ttl
            FROM sumDeptSalesByDay
            WHERE department < 600
                AND date_id BETWEEN ? AND ?");
        $totalSales = $dbc->getValue($totalP, $dateIDs);

        $organicR = $dbc->query("
            SELECT upc
            FROM {$opdb}products
            WHERE (numflag & (1<<16)) <> 0
            GROUP BY upc");
        $upcs = array();
        while ($row = $dbc->fetchRow($organicR)) {
            $upcs[] = $row['upc'];
        }
        list($inStr, $args) = $dbc->safeInClause($upcs);
        $data[] = array('', '', '');
        $data[] = array('Organic Sales', '', '');
        $salesP = $dbc->prepare("
            SELECT m.super_name,
                SUM(total) AS ttl
            FROM {$dlog} AS s
                INNER JOIN {$opdb}products AS p ON s.upc=p.upc AND p.store_id=1
                INNER JOIN {$opdb}MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE s.upc IN ({$inStr})
                AND tdate BETWEEN ? AND ?
            GROUP BY m.super_name
            ORDER BY m.super_name");
        $args[] = $dates[0];
        $args[] = $dates[1];
        $salesR = $dbc->execute($salesP, $args);
        $orgTTL = 0;
        while ($row = $dbc->fetchRow($salesR)) {
            $data[] = array(
                'Organic ' . $row['super_name'] . ' Sales',
                number_format($row['ttl'], 2),
                sprintf('%.2f%%', $row['ttl'] / $totalSales * 100),
            );
            $orgTTL += $row['ttl'];
        }
        $data[] = array('Total', number_format($orgTTL, 2), sprintf('%.2f%%', $orgTTL / $totalSales * 100));

        $salesP = $dbc->prepare("
            SELECT m.super_name,
                SUM(total) AS ttl
            FROM {$dlog} AS s
                INNER JOIN {$opdb}MasterSuperDepts AS m ON s.department=m.dept_ID
            WHERE trans_type = 'I'
                AND s.numflag=1 
                AND tdate BETWEEN ? AND ?
            GROUP BY m.super_name
            ORDER BY m.super_name");

        $salesR = $dbc->execute($salesP, $dates);
        $localTTL = 0;
        $data[] = array('', '', '');
        $data[] = array('Local Sales', '', '');
        while ($row = $dbc->fetchRow($salesR)) {
            $data[] = array(
                'Local ' . $row['super_name'] . ' Sales',
                number_format($row['ttl'], 2),
                sprintf('%.2f%%', $row['ttl'] / $totalSales * 100),
            );
            $localTTL += $row['ttl'];
        }
        $data[] = array('Total', number_format($localTTL, 2), sprintf('%.2f%%', $localTTL / $totalSales * 100));

        $depts = array(260, 261);
        $res = $dbc->query("SELECT dept_ID FROM {$opdb}superdepts WHERE superID IN (6, 11)");
        while ($row = $dbc->fetchRow($res)) {
            $depts[] = $row['dept_ID'];
        }
        list($inStr, $args) = $dbc->safeInClause($depts);
        $freshP = $dbc->prepare("
            SELECT m.super_name,
                SUM(total) AS ttl
            FROM {$dlog} AS s
                INNER JOIN {$opdb}MasterSuperDepts AS m ON s.department=m.dept_ID
            WHERE s.department IN ({$inStr})
                AND s.trans_type IN ('I', 'D')
                AND tdate BETWEEN ? AND ?
            GROUP BY m.super_name
            ORDER BY m.super_name");
        $args[] = $dates[0];
        $args[] = $dates[1];
        $salesR = $dbc->execute($freshP, $args);
        $freshTTL = 0;
        $data[] = array('', '', '');
        $data[] = array('Fresh Sales', '', '');
        while ($row = $dbc->fetchRow($salesR)) {
            $data[] = array(
                'Fresh ' . $row['super_name'] . ' Sales',
                number_format($row['ttl'], 2),
                sprintf('%.2f%%', $row['ttl'] / $totalSales * 100),
            );
            $freshTTL += $row['ttl'];
        }
        $data[] = array('Total', number_format($freshTTL, 2), sprintf('%.2f%%', $freshTTL / $totalSales * 100));

        return $data;
    }

    public function form_content()
    {
        $ret = '<form method="get">'
            . FormLib::standardDateFields()
            . '<div class="form-group">
                <button type="submit" class="btn btn-default">Submit</button>
               </div>
            </form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

