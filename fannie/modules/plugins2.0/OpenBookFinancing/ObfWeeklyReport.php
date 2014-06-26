<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ObfWeeklyReport extends FannieReportPage
{
    protected $header = 'OBF: Weekly Report';
    protected $title = 'OBF: Weekly Report';

    protected $required_fields = array('weekID');

    protected $report_headers = array(
        array('', 'Actual', 'Last Year', '%', 'GAP Goal', '%', 'Forecast', '%', 'Current O/U', 'QTD O/U'),
        array('', 'Actual', 'Last Year', '%', 'GAP Goal', '%', 'Forecast', '%', 'Current O/U', 'QTD O/U'),
        array('', 'Actual', 'Last Year', '%', 'GAP Goal', '%', 'Forecast', '%', 'Current O/U', 'QTD O/U'),
        array('', 'Actual', 'Last Year', '%', 'GAP Goal', '%', 'Forecast', '%', 'Current O/U', 'QTD O/U'),
        array('', 'Actual', 'Last Year', '%', 'GAP Goal', '%', 'Forecast', '%', 'Current O/U', 'QTD O/U'),
        array('', 'Actual', 'Last Year', '%', 'GAP Goal', '%', 'Forecast', '%', 'Current O/U', 'QTD O/U'),
        array('', 'Actual', '%', '', 'Plan', '%', '', 'Est. Bonus', '', ''),
    );

    public function report_description_content()
    {
		global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);
        
        $week = new ObfWeeksModel($dbc);
        $week->obfWeekID(FormLib::get('weekID'));
        $week->load();
        $start_ts = strtotime($week->startDate());
        $end_ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+6, date('Y', $start_ts));

        return array('Week ' . date('F d, Y', $start_ts) . ' to ' . date('F d, Y', $end_ts));
    }

    public function fetch_report_data()
    {
		global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);
        
        $week = new ObfWeeksModel($dbc);
        $week->obfWeekID(FormLib::get('weekID'));
        $week->load();

        $colors = array(
            '#999966',
            '#336699',
            '#993333',
            '#47A347',
            '#64537F',
            '#FF9933',
            'yellow',
        );
        
        $quarter_base = strtotime($week->startDate());
        $quarter_year = date('Y', $quarter_base);
        $quarter_num = ceil(date('n', $quarter_base) / 3);
        $quarter_start = mktime(0, 0, 0, (3*$quarter_num) - 2, 1, $quarter_year);
        $quarter_end = mktime(0, 0, 0, 3*$quarter_num, 1, $quarter_year);
        $quarter_weeks = array();
        $query = 'SELECT obfWeekID
                  FROM ObfWeeks
                  WHERE endDate BETWEEN ? AND ?
                    AND startDate < ?';
        $prep = $dbc->prepare($query);
        $args = array(
            date('Y-m-d 00:00:00', $quarter_start),
            date('Y-m-t 23:59:59', $quarter_end),
            date('Y-m-d 00:00:00', strtotime($week->startDate())),
        );
        $result = $dbc->execute($prep, $args);
        $weekIDs = array();
        $weekIN = '';
        while($row = $dbc->fetch_row($result)) {
            $weekIDs[] = $row['obfWeekID'];
            $weekIN .= '?,';
        }
        $weekIN = substr($weekIN, 0, strlen($weekIN)-1);
        if (count($weekIDs) == 0) {
            $weekIDs = array(-999);
            $weekIN = '?';
        }

        $labor = new ObfLaborModel($dbc);
        $labor->obfWeekID($week->obfWeekID());
        
        $start_ts = strtotime($week->startDate());
        $end_ts = mktime(0, 0, 0, date('n', $start_ts), date('j', $start_ts)+6, date('Y', $start_ts));
        $start_ly = strtotime($week->previousYear());
        $end_ly = mktime(0, 0, 0, date('n', $start_ly), date('j', $start_ly)+6, date('Y', $start_ly));

        $sales = new ObfSalesCacheModel($dbc);
        $sales->obfWeekID($week->obfWeekID());
        $num_cached = $sales->find();
        if (count($num_cached) == 0) {
            $salesQ = 'SELECT 
                        m.obfCategoryID as id,
                        m.superID,
                        SUM(t.total) AS sales,
                        AVG(m.growthTarget) AS growthTarget
                       FROM __table__ AS t
                        INNER JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'superdepts AS s
                            ON t.department=s.dept_ID
                        INNER JOIN ObfCategorySuperDeptMap AS m
                            ON s.superID=m.superID
                        LEFT JOIN ObfCategories AS c
                            ON m.obfCategoryID=c.obfCategoryID
                       WHERE c.hasSales=1
                        AND t.tdate BETWEEN ? AND ?
                        AND t.trans_type IN (\'I\', \'D\')
                       GROUP BY m.obfCategoryID, m.superID';

            $transQ = 'SELECT 
                        YEAR(t.tdate) AS year,
                        MONTH(t.tdate) AS month,
                        DAY(t.tdate) AS day,
                        t.trans_num
                       FROM __table__ AS t
                        INNER JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'superdepts AS s
                            ON t.department=s.dept_ID
                        INNER JOIN ObfCategorySuperDeptMap AS m
                            ON s.superID=m.superID
                       WHERE 
                        t.tdate BETWEEN ? AND ?
                        AND t.trans_type IN (\'I\', \'D\')
                        AND t.upc <> \'RRR\'
                       GROUP BY 
                        YEAR(t.tdate),
                        MONTH(t.tdate),
                        DAY(t.tdate),
                        t.trans_num';

            $dlog1 = DTransactionsModel::selectDlog(date('Y-m-d', $start_ts), date('Y-m-d', $end_ts));
            $dlog2 = DTransactionsModel::selectDlog(date('Y-m-d', $start_ly), date('Y-m-d', $end_ly));
            $args = array(date('Y-m-d 00:00:00', $start_ts), date('Y-m-d 23:59:59', $end_ts));

            $transQ = str_replace('__table__', $dlog1, $transQ);
            $transP = $dbc->prepare($transQ);
            $transR = $dbc->execute($transP, array($args));
            if ($transR) {
                $sales->transactions($dbc->num_rows($transR));
            } else {
                $sales->transactions(0);
            }

            $oneQ = str_replace('__table__', $dlog1, $salesQ);
            $oneP = $dbc->prepare($oneQ);
            $oneR = $dbc->execute($oneP, $args);
            while($w = $dbc->fetch_row($oneR)) {
                $sales->obfCategoryID($w['id']);
                $sales->superID($w['superID']);
                $sales->actualSales($w['sales']);
                $sales->growthTarget($w['growthTarget']);
                $sales->save();
            }
            
            $sales->reset();
            $sales->obfWeekID($week->obfWeekID());

            $twoQ = str_replace('__table__', $dlog2, $salesQ);
            $twoP = $dbc->prepare($twoQ);
            $args = array(date('Y-m-d 00:00:00', $start_ly), date('Y-m-d 23:59:59', $end_ly));
            $twoR = $dbc->execute($twoP, $args);
            while($w = $dbc->fetch_row($twoR)) {
                $sales->obfCategoryID($w['id']);
                $sales->superID($w['superID']);
                $sales->lastYearSales($w['sales']);
                $sales->save();
            }
        }

        $data = array();
        $total_sales = array(0, 0);
        $total_trans = 0;
        $total_hours = 0;
        $total_wages = 0;
        $proj_total = 0;
        $total_proj_wages = 0;
        $total_proj_hours = 0;
        $forecast_total = 0;
        $qtd_plan = 0;
        $qtd_sales = 0;
        $qtd_hours = 0;
        $qtd_wages = 0;
        $qtd_proj_hours = 0;
        $qtd_proj_wages = 0;
        $qtd_sales_ou = 0;
        $qtd_hours_ou = 0;
        $qtd_wages_ou = 0;

        $categories = new ObfCategoriesModel($dbc);
        $categories->hasSales(1);
        $salesP = $dbc->prepare('SELECT s.actualSales,
                                    s.lastYearSales,
                                    s.growthTarget,
                                    n.super_name,
                                    s.superID,
                                    s.transactions
                                 FROM ObfSalesCache AS s
                                    LEFT JOIN ' . $FANNIE_OP_DB . $dbc->sep() . 'superDeptNames
                                        AS n ON s.superID=n.superID
                                 WHERE s.obfWeekID=?
                                    AND s.obfCategoryID=?
                                 ORDER BY s.superID,n.super_name');

        $quarterSalesP = $dbc->prepare('SELECT SUM(s.actualSales) AS actual,
                                            SUM(s.lastYearSales) AS lastYear,
                                            SUM(s.lastYearSales * (1+s.growthTarget)) AS plan
                                        FROM ObfSalesCache AS s
                                        WHERE obfWeekID IN (' . $weekIN . ')
                                            AND obfCategoryID = ?
                                            AND superID=?'); 
        $quarterLaborP = $dbc->prepare('SELECT SUM(hours) AS hours,
                                            SUM(wages) AS wages,
                                            AVG(laborTarget) as laborTarget,
                                            AVG(averageWage) as averageWage
                                        FROM ObfLabor AS l
                                        WHERE obfWeekID IN (' . $weekIN . ')
                                            AND obfCategoryID=?');

        foreach ($categories->find('name') as $category) {
            $data[] = array($category->name(), '', '', '', '', '', '', '', '', '', 
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
            );
            $sum = array(0.0, 0.0);
            $dept_proj = 0.0;
            $salesR = $dbc->execute($salesP, array($week->obfWeekID(), $category->obfCategoryID()));
            $qtd_dept_plan = 0;
            $qtd_dept_sales = 0;
            $qtd_dept_ou = 0;
            while($w = $dbc->fetch_row($salesR)) {
                $proj = ($w['lastYearSales'] * $w['growthTarget']) + $w['lastYearSales'];

                $quarter = $dbc->execute($quarterSalesP, array_merge($weekIDs, array($category->obfCategoryID(), $w['superID'])));
                if ($dbc->num_rows($quarter) == 0) {
                    $quarter = array('actual'=>0, 'lastYear'=>0, 'plan'=>0);
                } else {
                    $quarter = $dbc->fetch_row($quarter);
                }
                $qtd_dept_plan += $quarter['plan'];
                $qtd_dept_sales += $quarter['actual'];

                $record = array(
                    $w['super_name'],
                    number_format($w['actualSales'], 0),
                    number_format($w['lastYearSales'], 0),
                    sprintf('%.2f%%', 100 * ($w['actualSales']-$w['lastYearSales'])/$w['lastYearSales']),
                    number_format($proj, 0),
                    sprintf('%.2f%%', 100 * ($w['actualSales']-$proj)/$proj),
                    '',
                    '',
                    number_format($w['actualSales'] - $proj, 0),
                    number_format(($w['actualSales'] - $proj) + ($quarter['actual'] - $quarter['plan']), 0),
                    'meta' => FannieReportPage::META_COLOR,
                    'meta_background' => $colors[0],
                    'meta_foreground' => 'black',
                );
                $sum[0] += $w['actualSales'];
                $sum[1] += $w['lastYearSales'];
                $total_sales[0] += $w['actualSales'];
                $total_sales[1] += $w['lastYearSales'];
                if ($total_trans == 0) {
                    $total_trans = $w['transactions'];
                }
                $proj_total += $proj;
                $dept_proj += $proj;
                $qtd_plan += $quarter['plan'];
                $qtd_sales += $quarter['actual'];
                $qtd_sales_ou += ($quarter['actual'] - $quarter['plan']);
                $qtd_dept_ou += ($quarter['actual'] - $quarter['plan']);
                $data[] = $record;
            }

            $labor->obfCategoryID($category->obfCategoryID());
            $labor->load();
            $record = array(
                'Total',
                number_format($sum[0], 0),
                number_format($sum[1], 0),
                sprintf('%.2f%%', 100 * ($sum[0]-$sum[1])/$sum[1]),
                number_format($dept_proj, 0),
                sprintf('%.2f%%', 100 * ($sum[0]-$dept_proj)/$dept_proj),
                number_format($labor->forecastSales(), 0),
                sprintf('%.2f%%', 100 * ($sum[0]-$labor->forecastSales())/$labor->forecastSales()),
                number_format($sum[0] - $dept_proj, 0),
                number_format(($sum[0] - $dept_proj) + ($qtd_dept_ou), 0),
                'meta' => FannieReportPage::META_COLOR | FannieReportPage::META_BOLD,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $data[] = $record;
            $forecast_total += $labor->forecastSales();

            $proj_wages = $dept_proj * $labor->laborTarget();
            $proj_hours = $proj_wages / $labor->averageWage();

            $quarter = $dbc->execute($quarterLaborP, array_merge($weekIDs, array($labor->obfCategoryID())));
            if ($dbc->num_rows($quarter) == 0) {
                $quarter = array('hours'=>0, 'wages'=>0, 'laborTarget'=>0, 'averageWage'=>0);
            } else {
                $quarter = $dbc->fetch_row($quarter);
            }
            $qt_proj_labor = $qtd_dept_plan * $quarter['laborTarget'];
            $qt_proj_hours = $qt_proj_labor / $quarter['averageWage'];
            $qtd_hours += $quarter['hours'];
            $qtd_proj_hours += $qt_proj_hours;
            $qtd_proj_wages += $qt_proj_labor;

            $data[] = array(
                'Hours',
                number_format($labor->hours(), 0),
                '',
                '',
                number_format($proj_hours, 0),
                '',
                number_format($proj_hours, 0),
                '',
                number_format($labor->hours() - $proj_hours, 0),
                number_format(($labor->hours() - $proj_hours) + ($quarter['hours'] - $qt_proj_hours), 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $total_hours += $labor->hours();
            $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

            $data[] = array(
                'Personnel (est)',
                number_format($labor->wages(), 0),
                '',
                '',
                number_format($proj_wages, 0),
                '',
                number_format($proj_wages, 0),
                '',
                number_format($labor->wages() - $proj_wages, 0),
                number_format(($labor->wages() - $proj_wages) + ($quarter['wages'] - $qt_proj_labor), 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $total_wages += $labor->wages();
            $qtd_wages += $quarter['wages'];
            $qtd_wages_ou += ($quarter['wages'] - $qt_proj_labor);
            $total_proj_wages += $proj_wages;
            $total_proj_hours += $proj_hours;

            $data[] = array(
                '% of Sales',
                sprintf('%.2f%%', $labor->wages() / $sum[0] * 100),
                '',
                '',
                sprintf('%.2f%%', $proj_wages / $proj * 100),
                '',
                sprintf('%.2f%%', $proj_wages / $labor->forecastSales() * 100),
                '',
                '',
                '',
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $quarter_actual_sph = (($sum[0] + $qtd_dept_sales)/($labor->hours()+$quarter['hours']));
            $quarter_proj_sph = ($proj+$qtd_dept_plan)/($proj_hours + $qt_proj_hours);
            $data[] = array(
                'Sales per Hour',
                number_format($sum[0] / $labor->hours(), 2),
                '',
                '',
                number_format($proj / $proj_hours, 2),
                '',
                number_format($labor->forecastSales() / $proj_hours, 2),
                '',
                number_format(($sum[0]/$labor->hours()) - ($proj / $proj_hours), 2),
                number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

            if (count($colors) > 1) {
                array_shift($colors);
            }
        }

        $cat = new ObfCategoriesModel($dbc);
        $cat->hasSales(0);
        foreach ($cat->find('name') as $c) {
            $data[] = array($c->name(), '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
            );
            $labor->obfCategoryID($c->obfCategoryID());
            $labor->load();

            $quarter = $dbc->execute($quarterLaborP, array_merge($weekIDs, array($labor->obfCategoryID())));
            if ($dbc->num_rows($quarter) == 0) {
                $quarter = array('hours'=>0, 'wages'=>0, 'laborTarget'=>0, 'averageWage'=>0);
            } else {
                $quarter = $dbc->fetch_row($quarter);
            }
            $qt_proj_labor = $qtd_plan * $quarter['laborTarget'];
            $qt_proj_hours = $qt_proj_labor / $quarter['averageWage'];
            $qtd_hours += $quarter['hours'];
            $qtd_proj_hours += $qt_proj_hours;
            $qtd_proj_wages += $qt_proj_labor;

            $proj_wages = $proj_total * $labor->laborTarget();
            $proj_hours = $proj_wages / $labor->averageWage();
            $data[] = array(
                'Hours',
                number_format($labor->hours(), 0),
                '',
                '',
                number_format($proj_hours, 0),
                '',
                number_format($proj_hours, 0),
                '',
                number_format($labor->hours() - $proj_hours, 0),
                number_format(($labor->hours() - $proj_hours) + ($quarter['hours'] - $qt_proj_hours), 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $qtd_hours_ou += ($quarter['hours'] - $qt_proj_hours);

            $data[] = array(
                'Personnel (est)',
                number_format($labor->wages(), 0),
                '',
                '',
                number_format($proj_wages, 0),
                '',
                number_format($proj_wages, 0),
                '',
                number_format($labor->wages() - $proj_wages, 0),
                number_format(($labor->wages() - $proj_wages) + ($quarter['wages'] - $qt_proj_labor), 0),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );
            $qtd_wages += $quarter['wages'];
            $qtd_wages_ou += ($quarter['wages'] - $qt_proj_labor);

            $data[] = array(
                '% of Sales',
                sprintf('%.2f%%', $labor->wages() / $total_sales[0] * 100),
                '',
                '',
                sprintf('%.2f%%', $proj_wages / $proj_total * 100),
                '',
                sprintf('%.2f%%', $proj_wages / $proj_total * 100),
                '',
                '',
                '',
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $quarter_actual_sph = (($total_sales[0] + $qtd_sales)/($labor->hours()+$quarter['hours']));
            $quarter_proj_sph = ($proj_total+$qtd_plan)/($proj_hours + $qt_proj_hours);
            $data[] = array(
                'Sales per Hour',
                number_format($total_sales[0] / $labor->hours(), 2),
                '',
                '',
                sprintf('%.2f', $proj_total / $proj_hours),
                '',
                sprintf('%.2f', $proj_total / $proj_hours),
                '',
                number_format(($total_sales[0]/$labor->hours()) - ($proj_total / $proj_hours), 2),
                number_format($quarter_actual_sph - $quarter_proj_sph, 2),
                'meta' => FannieReportPage::META_COLOR,
                'meta_background' => $colors[0],
                'meta_foreground' => 'black',
            );

            $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);

            $total_hours += $labor->hours();
            $total_wages += $labor->wages();
            $total_proj_wages += $proj_wages;
            $total_proj_hours += $proj_hours;

            if (count($colors) > 1) {
                array_shift($colors);
            }
        }

        $data[] = array('Total Store', '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
        );
        $data[] = array(
            'Sales',
            number_format($total_sales[0], 0),
            number_format($total_sales[1], 0),
            sprintf('%.2f%%', 100 * ($total_sales[0]-$total_sales[1])/$total_sales[1]),
            number_format($proj_total, 0),
            sprintf('%.2f%%', 100 * ($total_sales[0]-$proj_total)/$proj_total),
            number_format($forecast_total, 0),
            sprintf('%.2f%%', 100 * ($total_sales[0]-$forecast_total)/$forecast_total),
            number_format($total_sales[0] - $proj_total, 0),
            number_format(($total_sales[0] - $proj_total) + $qtd_sales_ou, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Hours',
            number_format($total_hours, 0),
            '',
            '',
            number_format($total_proj_hours, 0),
            '',
            number_format($total_proj_hours, 0),
            '',
            number_format($total_hours - $total_proj_hours, 0),
            number_format(($total_hours - $total_proj_hours) + $qtd_hours_ou, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Personnel (est)',
            number_format($total_wages, 0),
            '',
            '',
            number_format($total_proj_wages, 0),
            '',
            number_format($total_proj_wages, 0),
            '',
            number_format($total_wages - $total_proj_wages, 0),
            number_format(($total_wages - $total_proj_wages) + $qtd_wages_ou, 0),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            '% of Sales',
            sprintf('%.2f%%', $total_wages / $total_sales[0] * 100),
            '',
            '',
            sprintf('%.2f%%', $total_proj_wages / $proj_total * 100),
            '',
            sprintf('%.2f%%', $total_proj_wages / $forecast_total * 100),
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $quarter_actual_sph = (($total_sales[0] + $qtd_sales)/($total_hours+$qtd_hours));
        $quarter_proj_sph = ($proj_total+$qtd_plan)/($total_proj_hours + $qtd_proj_hours);
        $data[] = array(
            'Sales per Hour',
            number_format($total_sales[0] / $total_hours, 2),
            '',
            '',
            sprintf('%.2f', $proj_total / $total_proj_hours),
            '',
            sprintf('%.2f', $forecast_total / $total_proj_hours),
            '',
            number_format(($total_sales[0]/$total_hours) - ($proj_total/$total_proj_hours), 2),
            number_format($quarter_actual_sph - $quarter_proj_sph, 2),
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Transactions',
            number_format($total_trans),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        $data[] = array(
            'Average Basket',
            number_format($total_sales[0] / $total_trans, 2),
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        if (count($colors) > 1) {
            array_shift($colors);
        }

        $data[] = array('meta'=>FannieReportPage::META_REPEAT_HEADERS);
        $data[] = array('Quarter to Date', '', '', '', '', '', '', '', '', '',
                        'meta' => FannieReportPage::META_BOLD | FannieReportPage::META_COLOR,
                        'meta_background' => $colors[0],
                        'meta_foreground' => 'black',
        );
        $data[] = array(
            'Sales',
            number_format($qtd_sales+$total_sales[0], 0),
            '',
            '',
            number_format($qtd_plan+$proj_total, 0),
            '',
            '',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );
        $data[] = array(
            'Personnel',
            number_format($qtd_wages+$total_wages, 0),
            number_format(($qtd_wages+$total_wages) / ($qtd_sales+$total_sales[0]) * 100, 2) . '%',
            '',
            number_format($qtd_proj_wages+$total_proj_wages, 0),
            number_format(($qtd_proj_wages+$total_proj_wages) / ($qtd_plan+$proj_total) * 100, 2) . '%',
            '',
            '',
            '',
            '',
            'meta' => FannieReportPage::META_COLOR,
            'meta_background' => $colors[0],
            'meta_foreground' => 'black',
        );

        return $data;
    }

    public function form_content()
    {
		global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ObfDatabase']);

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
        $ret .= 'Week Starting: <select name="weekID">';
        $model = new ObfWeeksModel($dbc);
        foreach ($model->find('startDate', true) as $week) {
            $ret .= sprintf('<option value="%d">%s</option>',
                            $week->obfWeekID(),
                            $week->startDate()
            );
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" value="Get Report" />';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();
