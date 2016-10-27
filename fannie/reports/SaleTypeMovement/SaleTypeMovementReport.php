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

class SaleTypeMovementReport extends FannieReportPage 
{
    public $description = '[Sale Type Movement] breaks down sales by type of promotion';
    public $report_set = 'Movement Reports';

    protected $report_headers = array('Type', '$ Sales', '# Items', '% Sales');
    protected $sort_direction = 1;
    protected $sort_column = 1;
    protected $title = "Fannie : Sale Type Report";
    protected $header = "Sale Type Report";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $parts = FormLib::standardItemFromWhere(); 
        $query = $parts['query'];
        $args = $parts['args'];
        $query = '
            SELECT t.upc,
                t.discounttype,
                t.unitPrice,
                SUM(t.total) AS ttl
            ' . $query . '
            GROUP BY t.upc,
                t.discounttype,
                t.unitPrice';

        $lcP = $dbc->prepare('SELECT likeCode FROM upcLike WHERE upc=?');

        $batchP = $dbc->prepare('
            SELECT b.batchType,
                t.typeDesc
            FROM batchList AS l
                INNER JOIN batches AS b ON b.batchID=l.batchID
                INNER JOIN batchType AS t ON b.batchType=t.batchTypeID
            WHERE b.startDate < ?
                AND b.endDate > ?
                AND b.discounttype=?
                AND l.salePrice=?
                AND (l.upc=? OR l.upc=?)
            ORDER BY l.batchID DESC'); 

        $ruleP = $dbc->prepare('
            SELECT t.description 
            FROM products AS p
                INNER JOIN PriceRules AS r ON p.price_rule_id=r.priceRuleID
                INNER JOIN PriceRuleTypes AS t ON r.priceRuleTypeID=t.priceRuleTypeID
            WHERE upc=?');

        $sales = array('Normal'=>array('Normal', 0.00, 0), 'Other Sale'=>array('Other Sale', 0.00, 0));
        $res = $dbc->execute($query, $args);
        $sum = 0.0;
        while ($row = $dbc->fetchRow($res)) {
            if ($row['discounttype'] > 0) {
                $lc = $dbc->getValue($lcP, $row['upc']);
                $bArgs = array(
                    $this->form->date2 . ' 23:59:59',
                    $this->form->date1 . ' 00:00:00',
                    $row['discounttype'],
                    $row['unitPrice'],
                    $row['upc'],
                    ($lc ? 'LC' . $lc : 'notLikeCode'),
                );
                $batch = $dbc->getRow($batchP, $bArgs);
                if ($batch) {
                    if (!isset($sales[$batch['typeDesc']])) {
                        $sales[$batch['typeDesc']] = array($batch['typeDesc'], 0.00, 0);
                    }
                    $sales[$batch['typeDesc']][1] += $row['ttl'];
                    $sales[$batch['typeDesc']][2] += 1;
                } else {
                    $sales['Other Sale'][1] += $row['ttl'];
                    $sales['Other Sale'][2] += 1;
                }
            } else {
                $rule = $dbc->getValue($ruleP, array($row['upc']));
                if ($rule) {
                    if (!isset($sales[$rule])) {
                        $sales[$rule] = array($rule, 0.00, 0);
                    }
                    $sales[$rule][1] += $row['ttl'];
                    $sales[$rule][2] += 1;
                } else {
                    $sales['Normal'][1] += $row['ttl'];
                    $sales['Normal'][2] += 1;
                }
            }
            $sum += $row['ttl']; 
        }

        $data = array();
        foreach ($sales as $type => $record) {
            $record[1] = sprintf('%.2f', $record[1]);
            $record[] = $sum == 0 ? 0 : sprintf('%.2f%%', 100*($record[1]/$sum));
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sum = array_reduce($data, function($c,$i) { return $c+$i[1];});
        return array('Total', $sum, '', '');
    }

    public function form_content()
    {
        $form = FormLib::dateAndDepartmentForm();
        $form = str_replace('buyer', 'super-dept', $form);
        $form = str_replace('deptStart', 'dept-start', $form);
        $form = str_replace('deptEnd', 'dept-end', $form);

        return $form;
    }
}

FannieDispatch::conditionalExec();


